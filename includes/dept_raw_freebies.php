<?php

declare(strict_types=1);

/**
 * Freebie sample pouches for PT / BRC / Jar Cacao raw leaf products.
 * Create: deduct leaf pool + credit freebie on-hand.
 * Give out: deduct freebie on-hand only.
 */

require_once __DIR__ . '/app_settings.php';
require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/dept_inventory.php';

const DEPT_RAW_FREEBIE_PRODUCT_KEYS = ['pt', 'brc', 'jar_cacao'];
const DEPT_RAW_FREEBIE_GRAMS_DEFAULT = 5.0;
const DEPT_RAW_FREEBIE_GRAMS_MIN = 0.1;
const DEPT_RAW_FREEBIE_GRAMS_MAX = 500.0;

function dept_raw_freebie_setting_key(string $productKey): string
{
    return $productKey . '.freebie_grams';
}

function dept_raw_freebie_product_allowed(string $productKey): bool
{
    return in_array($productKey, DEPT_RAW_FREEBIE_PRODUCT_KEYS, true);
}

function dept_raw_freebie_grams(PDO $pdo, string $productKey): float
{
    if (!dept_raw_freebie_product_allowed($productKey)) {
        return 0.0;
    }
    $raw = app_setting_get($pdo, dept_raw_freebie_setting_key($productKey), null);
    if ($raw === null || $raw === '') {
        return DEPT_RAW_FREEBIE_GRAMS_DEFAULT;
    }
    $n = (float) $raw;
    if (!is_finite($n) || $n < DEPT_RAW_FREEBIE_GRAMS_MIN || $n > DEPT_RAW_FREEBIE_GRAMS_MAX) {
        return DEPT_RAW_FREEBIE_GRAMS_DEFAULT;
    }

    return $n;
}

function dept_raw_freebie_grams_save(PDO $pdo, string $productKey, float $grams, ?int $userId = null): float
{
    if (!dept_raw_freebie_product_allowed($productKey)) {
        throw new RuntimeException('Freebie grams are only for Paragis, Black Rice, and Jar Cacao.');
    }
    if (!is_finite($grams) || $grams < DEPT_RAW_FREEBIE_GRAMS_MIN || $grams > DEPT_RAW_FREEBIE_GRAMS_MAX) {
        throw new RuntimeException(
            'Freebie grams must be between ' . DEPT_RAW_FREEBIE_GRAMS_MIN . ' and ' . DEPT_RAW_FREEBIE_GRAMS_MAX . '.'
        );
    }
    app_setting_set($pdo, dept_raw_freebie_setting_key($productKey), (string) $grams, $userId);

    return dept_raw_freebie_grams($pdo, $productKey);
}

function dept_raw_freebie_ensure_balance_row(PDO $pdo, string $productKey): void
{
    if (!dept_raw_freebie_product_allowed($productKey)) {
        return;
    }
    $pdo->prepare(
        'INSERT IGNORE INTO dept_raw_freebie_balances (product_key, on_hand) VALUES (?, 0)'
    )->execute([$productKey]);
}

function dept_raw_freebie_on_hand(PDO $pdo, string $productKey): int
{
    if (!dept_raw_freebie_product_allowed($productKey)) {
        return 0;
    }
    dept_raw_freebie_ensure_balance_row($pdo, $productKey);
    $st = $pdo->prepare('SELECT on_hand FROM dept_raw_freebie_balances WHERE product_key = ? LIMIT 1');
    $st->execute([$productKey]);
    $v = $st->fetchColumn();

    return $v === false ? 0 : max(0, (int) $v);
}

/**
 * @return array{
 *   product_key: string,
 *   action: string,
 *   qty_samples: int,
 *   grams_per_sample: float,
 *   grams_total: float,
 *   bags_deducted: float,
 *   freebie_on_hand: int,
 *   bags_on_hand: float,
 *   log_id: int
 * }
 */
function dept_raw_record_freebie_create(
    PDO $pdo,
    int $userId,
    string $productKey,
    int $qtySamples,
    string $reason = ''
): array {
    if (!dept_raw_freebie_product_allowed($productKey)) {
        throw new RuntimeException('Freebies are only for Paragis, Black Rice, and Jar Cacao.');
    }
    if ($qtySamples < 1) {
        throw new RuntimeException('Sample quantity must be a whole number ≥ 1.');
    }
    $reason = trim($reason);
    $gramsEach = dept_raw_freebie_grams($pdo, $productKey);
    if ($gramsEach <= 0) {
        throw new RuntimeException('Set freebie grams in Settings before creating freebies.');
    }
    $gramsTotal = round($qtySamples * $gramsEach, 4);
    $gramsPerBag = dept_raw_pool_grams_per_bag($pdo, $productKey);
    $bags = round($gramsTotal / $gramsPerBag, 6);
    if ($bags <= 0) {
        throw new RuntimeException('Calculated leaf deduct is zero. Check freebie grams settings.');
    }

    $label = $reason !== '' ? 'freebie: ' . substr($reason, 0, 100) : 'freebie';
    dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, $bags, $label);

    dept_raw_freebie_ensure_balance_row($pdo, $productKey);
    $upd = $pdo->prepare(
        'UPDATE dept_raw_freebie_balances SET on_hand = on_hand + ? WHERE product_key = ?'
    );
    $upd->execute([$qtySamples, $productKey]);

    $ins = $pdo->prepare(
        'INSERT INTO dept_raw_freebie_log
         (product_key, action, qty_samples, grams_per_sample, grams_total, bags_deducted, reason, reported_by)
         VALUES (?, \'create\', ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $productKey,
        $qtySamples,
        $gramsEach,
        $gramsTotal,
        $bags,
        substr($reason, 0, 255),
        $userId,
    ]);

    return [
        'product_key' => $productKey,
        'action' => 'create',
        'qty_samples' => $qtySamples,
        'grams_per_sample' => $gramsEach,
        'grams_total' => $gramsTotal,
        'bags_deducted' => $bags,
        'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, $productKey),
        'log_id' => (int) $pdo->lastInsertId(),
    ];
}

/**
 * @return array{
 *   product_key: string,
 *   action: string,
 *   qty_samples: int,
 *   freebie_on_hand: int,
 *   log_id: int
 * }
 */
function dept_raw_record_freebie_give_out(
    PDO $pdo,
    int $userId,
    string $productKey,
    int $qtySamples,
    string $reason
): array {
    if (!dept_raw_freebie_product_allowed($productKey)) {
        throw new RuntimeException('Freebies are only for Paragis, Black Rice, and Jar Cacao.');
    }
    if ($qtySamples < 1) {
        throw new RuntimeException('Sample quantity must be a whole number ≥ 1.');
    }
    $reason = trim($reason);
    if ($reason === '') {
        throw new RuntimeException('Reason is required.');
    }

    dept_raw_freebie_ensure_balance_row($pdo, $productKey);
    $st = $pdo->prepare('SELECT on_hand FROM dept_raw_freebie_balances WHERE product_key = ? FOR UPDATE');
    $st->execute([$productKey]);
    $onHand = (int) ($st->fetchColumn() ?: 0);
    if ($onHand < $qtySamples) {
        throw new RuntimeException(
            'Not enough freebies on hand (' . $onHand . ' available, need ' . $qtySamples . ').'
        );
    }
    $pdo->prepare(
        'UPDATE dept_raw_freebie_balances SET on_hand = on_hand - ? WHERE product_key = ?'
    )->execute([$qtySamples, $productKey]);

    $ins = $pdo->prepare(
        'INSERT INTO dept_raw_freebie_log
         (product_key, action, qty_samples, grams_per_sample, grams_total, bags_deducted, reason, reported_by)
         VALUES (?, \'give_out\', ?, NULL, NULL, NULL, ?, ?)'
    );
    $ins->execute([
        $productKey,
        $qtySamples,
        substr($reason, 0, 255),
        $userId,
    ]);

    return [
        'product_key' => $productKey,
        'action' => 'give_out',
        'qty_samples' => $qtySamples,
        'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),
        'log_id' => (int) $pdo->lastInsertId(),
    ];
}

/**
 * @return array{
 *   rows: list<array<string, mixed>>,
 *   total: int,
 *   page: int,
 *   per_page: int,
 *   total_pages: int,
 *   freebie_on_hand: int,
 *   freebie_grams: float
 * }
 */
function dept_raw_freebie_history(PDO $pdo, string $productKey, int $page = 1, int $perPage = 20): array
{
    if (!dept_raw_freebie_product_allowed($productKey)) {
        throw new RuntimeException('Unknown freebie product.');
    }
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $cnt = $pdo->prepare('SELECT COUNT(*) FROM dept_raw_freebie_log WHERE product_key = ?');
    $cnt->execute([$productKey]);
    $total = (int) $cnt->fetchColumn();

    $st = $pdo->prepare(
        'SELECT l.id, l.product_key, l.action, l.qty_samples, l.grams_per_sample, l.grams_total,
                l.bags_deducted, l.reason, l.reported_by, l.created_at,
                u.name AS reported_by_name
         FROM dept_raw_freebie_log l
         LEFT JOIN users u ON u.id = l.reported_by
         WHERE l.product_key = ?
         ORDER BY l.created_at DESC, l.id DESC
         LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $st->execute([$productKey]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $rows[] = [
            'id' => (int) $row['id'],
            'product_key' => (string) $row['product_key'],
            'action' => (string) $row['action'],
            'qty_samples' => (int) $row['qty_samples'],
            'grams_per_sample' => $row['grams_per_sample'] !== null ? (float) $row['grams_per_sample'] : null,
            'grams_total' => $row['grams_total'] !== null ? (float) $row['grams_total'] : null,
            'bags_deducted' => $row['bags_deducted'] !== null ? (float) $row['bags_deducted'] : null,
            'reason' => (string) $row['reason'],
            'reported_by' => (int) $row['reported_by'],
            'reported_by_name' => $row['reported_by_name'] !== null ? (string) $row['reported_by_name'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }

    $totalPages = max(1, (int) ceil($total / $perPage));

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),
        'freebie_grams' => dept_raw_freebie_grams($pdo, $productKey),
    ];
}
