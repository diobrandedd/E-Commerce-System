<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();
echo 'DB=' . $pdo->query('SELECT DATABASE()')->fetchColumn() . PHP_EOL;

$st = $pdo->query(
    "SELECT id, product_key, product_label, current_qty, initial_qty, last_stock_in_at
     FROM dept_inventory_rows WHERE product_key='pt'"
);
echo "--- dept_inventory_rows ---\n";
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- recent sku events ---\n";
$st = $pdo->query(
    "SELECT e.id, e.qty, e.dept_request_id, e.scanned_by, e.scanned_at, s.product_key, u.full_name
     FROM sku_barcode_stock_events e
     LEFT JOIN product_sku_barcodes s ON s.id = e.sku_id
     LEFT JOIN users u ON u.id = e.scanned_by
     WHERE e.kind='dept' AND s.product_key='pt'
     ORDER BY e.id DESC LIMIT 20"
);
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo 'in_stock units: ' . $pdo->query(
    "SELECT COUNT(*) FROM dept_pouch_units WHERE product_key='pt' AND status='in_stock'"
)->fetchColumn() . PHP_EOL;
echo 'all units: ' . $pdo->query(
    "SELECT COUNT(*) FROM dept_pouch_units WHERE product_key='pt'"
)->fetchColumn() . PHP_EOL;

echo "--- recent unique scans ---\n";
$st = $pdo->query(
    "SELECT c.id, c.code, c.status, c.scanned_at, c.scanned_by
     FROM dept_pouch_barcodes c
     INNER JOIN dept_pouch_barcode_batches b ON b.id=c.batch_id
     WHERE b.product_key='pt' AND c.scanned_at IS NOT NULL
     ORDER BY c.scanned_at DESC LIMIT 15"
);
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- requests ---\n";
$st = $pdo->query(
    "SELECT id, batch_no, quantity, sku_scanned_qty, status, created_at
     FROM dept_pouch_requests WHERE product_key='pt' ORDER BY id DESC LIMIT 8"
);
print_r($st->fetchAll(PDO::FETCH_ASSOC));
