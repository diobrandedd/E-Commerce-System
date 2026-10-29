<?php

declare(strict_types=1);

/**
 * Push cash walk-in POS receipts into PyxAcct revenue.
 * Uses the same API base URL and token as honey AP sync.
 * Walk-in posting has its own kill switch and defaults off.
 *
 * Shipped packs and AR checkouts are not posted.
 * Failures are logged and do not undo the RTS sale.
 */

require_once __DIR__ . '/pyxacct_ap_sync.php';
require_once __DIR__ . '/rts_schema.php';

function pyxacct_walkin_sync_enabled(PDO $rtsPdo): bool
{
    $raw = app_setting_get($rtsPdo, 'pyxacct_walkin_sync_enabled', '0');

    return $raw === '1' || strtolower((string) $raw) === 'true' || $raw === 'yes';
}

function pyxacct_walkin_sync_enabled_save(PDO $rtsPdo, bool $enabled, ?int $userId = null): bool
{
    app_setting_set($rtsPdo, 'pyxacct_walkin_sync_enabled', $enabled ? '1' : '0', $userId);

    return $enabled;
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,status:int,error?:string,data?:array<string,mixed>}
 */
function pyxacct_api_post(string $base, string $token, string $action, array $payload): array
{
    $url = $base . '/api/v1/entries.php?action=' . rawurlencode($action);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['ok' => false, 'status' => 0, 'error' => 'Could not encode the receipt.'];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'status' => 0, 'error' => 'Could not initialize HTTP client.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'X-PyxAcct-Token: ' . $token,
        ],
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        return ['ok' => false, 'status' => $status, 'error' => 'Connection failed: ' . ($err !== '' ? $err : 'curl error ' . $errno)];
    }

    $decoded = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($decoded)) {
        return ['ok' => false, 'status' => $status, 'error' => 'Unexpected response from PyxAcct (HTTP ' . $status . ').'];
    }
    if ($status >= 200 && $status < 300 && !empty($decoded['ok'])) {
        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];

        return ['ok' => true, 'status' => $status, 'data' => $data];
    }

    $msg = trim((string) ($decoded['error'] ?? ''));

    return [
        'ok' => false,
        'status' => $status,
        'error' => $msg !== '' ? $msg : 'PyxAcct rejected the request (HTTP ' . $status . ').',
    ];
}

/**
 * @return array{id:int,receipt_no:string,total:float,entry_date:string,is_ar:bool,refunded:bool}|null
 */
function pyxacct_walkin_load_sale(PDO $pdo, int $saleId): ?array
{
    if ($saleId < 1) {
        return null;
    }
    $hasAr = rts_db_has_column($pdo, 'pos_sales', 'is_ar');
    $hasRefund = rts_db_has_column($pdo, 'pos_sales', 'refunded_at');
    $arExpr = $hasAr ? 'COALESCE(is_ar, 0)' : '0';
    $refundExpr = $hasRefund ? 'refunded_at' : 'NULL';
    $st = $pdo->prepare(
        "SELECT id, receipt_no, total_amount, sale_date, {$arExpr} AS is_ar, {$refundExpr} AS refunded_at
         FROM pos_sales
         WHERE id = ?
         LIMIT 1"
    );
    $st->execute([$saleId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $date = substr((string) ($row['sale_date'] ?? ''), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = date('Y-m-d');
    }

    return [
        'id' => (int) $row['id'],
        'receipt_no' => trim((string) ($row['receipt_no'] ?? '')),
        'total' => round((float) ($row['total_amount'] ?? 0), 2),
        'entry_date' => $date,
        'is_ar' => (int) ($row['is_ar'] ?? 0) === 1,
        'refunded' => !empty($row['refunded_at']),
    ];
}

function pyxacct_sync_walkin_sale(PDO $rtsPdo, int $saleId): void
{
    try {
        if (!pyxacct_walkin_sync_enabled($rtsPdo)) {
            return;
        }
        $base = pyxacct_api_base_url_get($rtsPdo);
        $token = pyxacct_api_token_get($rtsPdo);
        if ($base === '' || $token === '') {
            return;
        }
        $sale = pyxacct_walkin_load_sale($rtsPdo, $saleId);
        if ($sale === null || $sale['receipt_no'] === '') {
            return;
        }
        if ($sale['is_ar']) {
            return;
        }
        $sourceKey = 'rts:pos_sale:' . $sale['id'];
        if ($sale['refunded']) {
            pyxacct_void_walkin_sale($rtsPdo, $sale['id']);

            return;
        }
        if ($sale['total'] <= 0) {
            return;
        }
        $result = pyxacct_api_post($base, $token, 'ingest_walkin', [
            'source_key' => $sourceKey,
            'receipt_no' => $sale['receipt_no'],
            'amount' => $sale['total'],
            'entry_date' => $sale['entry_date'],
            'meta' => [
                'pos_sale_id' => $sale['id'],
                'receipt_no' => $sale['receipt_no'],
            ],
        ]);
        if (empty($result['ok'])) {
            error_log('pyxacct_sync_walkin_sale #' . $saleId . ': ' . ($result['error'] ?? 'failed'));
        }
    } catch (Throwable $e) {
        error_log('pyxacct_sync_walkin_sale #' . $saleId . ': ' . $e->getMessage());
    }
}

function pyxacct_void_walkin_sale(PDO $rtsPdo, int $saleId): void
{
    try {
        if ($saleId < 1 || !pyxacct_walkin_sync_enabled($rtsPdo)) {
            return;
        }
        $base = pyxacct_api_base_url_get($rtsPdo);
        $token = pyxacct_api_token_get($rtsPdo);
        if ($base === '' || $token === '') {
            return;
        }
        $result = pyxacct_api_post($base, $token, 'void_walkin', [
            'source_key' => 'rts:pos_sale:' . $saleId,
        ]);
        if (empty($result['ok'])) {
            error_log('pyxacct_void_walkin_sale #' . $saleId . ': ' . ($result['error'] ?? 'failed'));
        }
    } catch (Throwable $e) {
        error_log('pyxacct_void_walkin_sale #' . $saleId . ': ' . $e->getMessage());
    }
}

/**
 * Post existing cash walk-in receipts and void refunded ones.
 *
 * @return array{posted:int,updated:int,voided:int,skipped:int,failed:int}
 */
function pyxacct_sync_existing_walkin(PDO $rtsPdo): array
{
    $counts = ['posted' => 0, 'updated' => 0, 'voided' => 0, 'skipped' => 0, 'failed' => 0];
    if (!pyxacct_walkin_sync_enabled($rtsPdo)) {
        throw new RuntimeException('Turn on walk-in sync before syncing existing receipts.');
    }
    $base = pyxacct_api_base_url_get($rtsPdo);
    $token = pyxacct_api_token_get($rtsPdo);
    if ($base === '' || $token === '') {
        throw new RuntimeException('Set the PyxAcct API base URL and token first.');
    }

    $hasAr = rts_db_has_column($rtsPdo, 'pos_sales', 'is_ar');
    $hasRefund = rts_db_has_column($rtsPdo, 'pos_sales', 'refunded_at');
    $arExpr = $hasAr ? 'COALESCE(is_ar, 0)' : '0';
    $refundExpr = $hasRefund ? 'refunded_at' : 'NULL';
    $st = $rtsPdo->query(
        "SELECT id, receipt_no, total_amount, sale_date, {$arExpr} AS is_ar, {$refundExpr} AS refunded_at
         FROM pos_sales
         ORDER BY id ASC"
    );
    if ($st === false) {
        throw new RuntimeException('Could not read walk-in receipts.');
    }

    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = (int) ($row['id'] ?? 0);
        $receipt = trim((string) ($row['receipt_no'] ?? ''));
        $total = round((float) ($row['total_amount'] ?? 0), 2);
        $isAr = (int) ($row['is_ar'] ?? 0) === 1;
        $refunded = !empty($row['refunded_at']);
        $date = substr((string) ($row['sale_date'] ?? ''), 0, 10);
        if ($id < 1 || $receipt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $counts['skipped']++;
            continue;
        }
        if ($isAr && !$refunded) {
            $counts['skipped']++;
            continue;
        }

        $sourceKey = 'rts:pos_sale:' . $id;
        if ($refunded) {
            $result = pyxacct_api_post($base, $token, 'void_walkin', ['source_key' => $sourceKey]);
            if (empty($result['ok'])) {
                $counts['failed']++;
                error_log('pyxacct_sync_existing_walkin void #' . $id . ': ' . ($result['error'] ?? 'failed'));
                continue;
            }
            $kind = (string) ($result['data']['result'] ?? '');
            if ($kind === 'voided') {
                $counts['voided']++;
            } else {
                $counts['skipped']++;
            }
            continue;
        }
        if ($total <= 0) {
            $counts['skipped']++;
            continue;
        }
        $result = pyxacct_api_post($base, $token, 'ingest_walkin', [
            'source_key' => $sourceKey,
            'receipt_no' => $receipt,
            'amount' => $total,
            'entry_date' => $date,
            'meta' => [
                'pos_sale_id' => $id,
                'receipt_no' => $receipt,
            ],
        ]);
        if (empty($result['ok'])) {
            $counts['failed']++;
            error_log('pyxacct_sync_existing_walkin #' . $id . ': ' . ($result['error'] ?? 'failed'));
            continue;
        }
        $kind = (string) ($result['data']['result'] ?? '');
        if ($kind === 'created' || $kind === 'restored') {
            $counts['posted']++;
        } elseif ($kind === 'updated') {
            $counts['updated']++;
        } else {
            $counts['skipped']++;
        }
    }

    return $counts;
}
