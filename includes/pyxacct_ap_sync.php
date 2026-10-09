<?php

declare(strict_types=1);

/**
 * Push priced honey warehouse stock-ins into PyxAcct accounts payable via HTTP API.
 * Failures are swallowed so Tracker stock-in never breaks if PyxAcct is offline.
 *
 * Settings (app_settings):
 *   pyxacct_honey_ap_sync_enabled — Tracker kill switch (default on)
 *   pyxacct_api_base_url          — e.g. http://localhost/PyxAcct
 *   pyxacct_api_token             — must match PyxAcct integration.tracker_honey_token
 *
 * Deprecated: config pyxacct_db (direct MySQL). Auto-sync no longer uses it.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app_settings.php';

/** Tracker-side kill switch (app_settings). Default on. */
function pyxacct_honey_ap_sync_enabled_on_tracker(PDO $rtsPdo): bool
{
    $raw = app_setting_get($rtsPdo, 'pyxacct_honey_ap_sync_enabled', '1');

    return $raw === '1' || strtolower((string) $raw) === 'true' || $raw === 'yes';
}

function pyxacct_honey_ap_sync_enabled_save(PDO $rtsPdo, bool $enabled, ?int $userId = null): bool
{
    app_setting_set($rtsPdo, 'pyxacct_honey_ap_sync_enabled', $enabled ? '1' : '0', $userId);

    return $enabled;
}

function pyxacct_api_base_url_get(PDO $rtsPdo): string
{
    return rtrim(trim((string) app_setting_get($rtsPdo, 'pyxacct_api_base_url', '')), '/');
}

function pyxacct_api_token_get(PDO $rtsPdo): string
{
    return trim((string) app_setting_get($rtsPdo, 'pyxacct_api_token', ''));
}

function pyxacct_api_settings_save(
    PDO $rtsPdo,
    bool $enabled,
    string $baseUrl,
    ?string $token,
    ?int $userId = null
): array {
    pyxacct_honey_ap_sync_enabled_save($rtsPdo, $enabled, $userId);

    $baseUrl = rtrim(trim($baseUrl), '/');
    if ($baseUrl !== '' && !preg_match('#^https?://#i', $baseUrl)) {
        throw new RuntimeException('PyxAcct API base URL must start with http:// or https://');
    }
    app_setting_set($rtsPdo, 'pyxacct_api_base_url', $baseUrl, $userId);

    if ($token !== null) {
        $token = trim($token);
        // Empty string clears; non-empty replaces. null (caller omitted) keeps existing.
        app_setting_set($rtsPdo, 'pyxacct_api_token', $token, $userId);
    }

    return pyxacct_api_settings_public($rtsPdo);
}

/** @return array{enabled:bool,api_base_url:string,token_set:bool,description:string} */
function pyxacct_api_settings_public(PDO $rtsPdo): array
{
    return [
        'enabled' => pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo),
        'api_base_url' => pyxacct_api_base_url_get($rtsPdo),
        'token_set' => pyxacct_api_token_get($rtsPdo) !== '',
        'description' => 'When on, priced honey warehouse stock-ins POST to PyxAcct accounts payable.',
    ];
}

/**
 * GET ingest_status on the configured PyxAcct URL.
 *
 * @return array{ok:bool,sync_enabled?:bool,error?:string,http_status?:int}
 */
function pyxacct_test_connection(PDO $rtsPdo): array
{
    $base = pyxacct_api_base_url_get($rtsPdo);
    $token = pyxacct_api_token_get($rtsPdo);
    if ($base === '' || $token === '') {
        return [
            'ok' => false,
            'error' => 'Set PyxAcct API base URL and token first.',
        ];
    }

    $url = $base . '/api/v1/payables.php?action=ingest_status';
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not initialize HTTP client.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_HTTPHEADER => [
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
        return ['ok' => false, 'error' => 'Connection failed: ' . ($err !== '' ? $err : 'curl error ' . $errno)];
    }

    $decoded = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'http_status' => $status,
            'error' => 'Unexpected response from PyxAcct (HTTP ' . $status . ').',
        ];
    }

    if ($status >= 200 && $status < 300 && !empty($decoded['ok'])) {
        $data = is_array($decoded['data'] ?? null) ? $decoded['data'] : [];

        return [
            'ok' => true,
            'sync_enabled' => !empty($data['sync_enabled']),
            'http_status' => $status,
        ];
    }

    $msg = trim((string) ($decoded['error'] ?? ''));

    return [
        'ok' => false,
        'http_status' => $status,
        'error' => $msg !== '' ? $msg : 'PyxAcct rejected the request (HTTP ' . $status . ').',
    ];
}

/**
 * Create or update an open payable for a priced honey warehouse row.
 * Safe to call repeatedly (idempotent via source_key).
 */
function pyxacct_sync_honey_warehouse_payable(PDO $rtsPdo, int $warehouseRowId): void
{
    if ($warehouseRowId < 1) {
        return;
    }

    try {
        if (!pyxacct_honey_ap_sync_enabled_on_tracker($rtsPdo)) {
            return;
        }

        $base = pyxacct_api_base_url_get($rtsPdo);
        $token = pyxacct_api_token_get($rtsPdo);
        if ($base === '' || $token === '') {
            return;
        }

        $st = $rtsPdo->prepare(
            'SELECT id, supplier_name, supplier_code, arrival_date, variant,
                    initial_qty, unit_cost_per_gallon, created_at
             FROM honey_warehouse_rows
             WHERE id = ?
             LIMIT 1'
        );
        $st->execute([$warehouseRowId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        $unit = $row['unit_cost_per_gallon'] !== null ? (float) $row['unit_cost_per_gallon'] : null;
        $qty = (float) ($row['initial_qty'] ?? 0);
        if ($unit === null || $unit <= 0 || $qty <= 0) {
            return;
        }

        $amount = round($qty * $unit, 2);
        if ($amount <= 0) {
            return;
        }

        $vendor = trim((string) ($row['supplier_name'] ?? ''));
        if ($vendor === '') {
            $vendor = trim((string) ($row['supplier_code'] ?? '')) ?: 'Honey supplier';
        }
        $date = (string) ($row['arrival_date'] ?: substr((string) $row['created_at'], 0, 10));
        $variant = (string) ($row['variant'] ?? '');
        $qtyLabel = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
        $description = sprintf('Honey stock-in · %s · %s gal @ %0.2f', $variant, $qtyLabel, $unit);
        $sourceKey = 'tracker:honey_row:' . $warehouseRowId;
        $meta = [
            'kind' => 'honey_warehouse',
            'honey_row_id' => $warehouseRowId,
            'variant' => $variant,
            'qty' => $qty,
            'unit_cost' => $unit,
        ];

        $url = $base . '/api/v1/payables.php?action=ingest_honey';
        $payload = json_encode(
            [
                'source_key' => $sourceKey,
                'vendor_name' => $vendor,
                'description' => $description,
                'amount' => $amount,
                'incurred_date' => $date,
                'meta' => $meta,
            ],
            JSON_UNESCAPED_UNICODE
        );
        if ($payload === false) {
            return;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
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
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $status < 200 || $status >= 300) {
            $snippet = is_string($body) ? substr($body, 0, 200) : '';
            error_log(
                'pyxacct_sync_honey_warehouse_payable #' . $warehouseRowId
                . ' HTTP ' . $status . ' errno=' . $errno . ' ' . $snippet
            );
        }
    } catch (Throwable $e) {
        error_log('pyxacct_sync_honey_warehouse_payable #' . $warehouseRowId . ': ' . $e->getMessage());
    }
}
