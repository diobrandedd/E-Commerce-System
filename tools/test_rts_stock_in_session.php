<?php

declare(strict_types=1);

/**
 * RTS stock-in session regression.
 *
 * Uses transaction rollbacks; no inventory/session changes persist.
 * Run phase76 first. If the schema or a SKU fixture is unavailable, this test skips.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/rts_stock_in_sessions.php';

$pdo = rts_pdo();
$table = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rts_stock_in_sessions'"
);
if ((int) $table->fetchColumn() < 1) {
    echo "SKIP RTS stock-in session regression: run migration phase76.\n";
    exit(0);
}

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
$sku = $pdo->query('SELECT * FROM product_sku_barcodes ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($userId < 1 || $sku === false) {
    echo "SKIP RTS stock-in session regression: user/SKU fixture unavailable.\n";
    exit(0);
}

function rts_test_assert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo "OK {$message}\n";
}

function rts_test_stock_qty(PDO $pdo, array $sku): float
{
    if ((string) $sku['kind'] === 'honey') {
        $column = match ((string) $sku['size']) {
            'liter' => 'current_liter',
            '250ml' => 'current_250ml',
            '350ml' => 'current_350ml',
            'gallon' => 'current_gallon',
            default => throw new RuntimeException('Unsupported honey SKU fixture.'),
        };
        $st = $pdo->prepare(
            "SELECT COALESCE(SUM({$column}), 0) FROM rts_inventory_rows
             WHERE category = 'honey'
               AND LOWER(COALESCE(NULLIF(TRIM(variant), ''), 'other')) = ?
               AND COALESCE(inventory_pool, 'sellable') = 'sellable'"
        );
        $st->execute([honey_normalize_variant((string) $sku['variant'])]);
        return (float) $st->fetchColumn();
    }
    $label = dept_pouch_product_label((string) $sku['product_key']);
    $st = $pdo->prepare(
        "SELECT COALESCE(SUM(current_qty), 0) FROM rts_inventory_rows
         WHERE category = 'other' AND product_label = ?
           AND COALESCE(inventory_pool, 'sellable') = 'sellable'"
    );
    $st->execute([$label]);
    return (float) $st->fetchColumn();
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO rts_stock_in_sessions (started_by, status) VALUES (?, "draft")')->execute([$userId]);
    $sessionId = (int) $pdo->lastInsertId();

    $one = rts_stock_in_session_scan($pdo, $sessionId, $userId, (string) $sku['code'], 1, 'test-scan-one');
    $two = rts_stock_in_session_scan($pdo, $sessionId, $userId, (string) $sku['code'], 1, 'test-scan-two');
    rts_test_assert($one['session']['total_qty'] === 1, 'first SKU scan stages one');
    rts_test_assert($two['session']['total_qty'] === 2, 'second presentation increments draft');

    $replay = rts_stock_in_session_scan($pdo, $sessionId, $userId, (string) $sku['code'], 1, 'test-scan-two');
    rts_test_assert($replay['result'] === 'replayed' && $replay['session']['total_qty'] === 2, 'request retry is idempotent');

    $itemId = (int) $two['session']['items'][0]['id'];
    $edited = rts_stock_in_session_update_qty($pdo, $sessionId, $itemId, $userId, 7);
    rts_test_assert($edited['total_qty'] === 7, 'SKU quantity is editable');
    $removed = rts_stock_in_session_remove($pdo, $sessionId, $itemId, $userId);
    rts_test_assert($removed['line_count'] === 0, 'draft line can be removed');
    $pdo->rollBack();

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO rts_stock_in_sessions (started_by, status) VALUES (?, "draft")')->execute([$userId]);
    $sessionId = (int) $pdo->lastInsertId();
    $before = rts_test_stock_qty($pdo, $sku);
    rts_stock_in_session_scan($pdo, $sessionId, $userId, (string) $sku['code'], 2, 'test-confirm');
    rts_test_assert(rts_test_stock_qty($pdo, $sku) === $before, 'draft scan does not mutate inventory');
    $confirmed = rts_stock_in_session_confirm($pdo, $sessionId, $userId);
    rts_test_assert($confirmed['status'] === 'confirmed', 'confirm closes the session');
    rts_test_assert(rts_test_stock_qty($pdo, $sku) === $before + 2.0, 'confirm applies the reviewed quantity');
    $pdo->rollBack();

    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO rts_stock_in_sessions (started_by, status) VALUES (?, "draft")')->execute([$userId]);
    $sessionId = (int) $pdo->lastInsertId();
    $before = rts_test_stock_qty($pdo, $sku);
    rts_stock_in_session_scan($pdo, $sessionId, $userId, (string) $sku['code'], 3, 'test-discard');
    $discarded = rts_stock_in_session_discard($pdo, $sessionId, $userId);
    rts_test_assert($discarded['status'] === 'discarded', 'cancel closes the draft as discarded');
    rts_test_assert(rts_test_stock_qty($pdo, $sku) === $before, 'cancel leaves inventory unchanged');
    $pdo->rollBack();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "PASS RTS stock-in session regression\n";
