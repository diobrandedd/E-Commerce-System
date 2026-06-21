<?php
require dirname(__DIR__) . '/includes/db.php';
$pdo = ims_pdo();
$out = [
    'users_spa' => $pdo->query(
        'SELECT u.id, u.username, u.name, u.employee_id, r.name, IFNULL(d.name, \'\'), u.is_active
         FROM users u JOIN roles r ON r.id = u.role_id
         LEFT JOIN departments d ON d.id = u.dept_id ORDER BY u.id'
    )->fetchAll(PDO::FETCH_NUM),
    'app_users' => $pdo->query(
        'SELECT id, username, full_name, IFNULL(employee_id, \'\'), role, status FROM app_users ORDER BY id'
    )->fetchAll(PDO::FETCH_NUM),
    'stocks' => $pdo->query(
        'SELECT s.id, s.name, d.name, s.category, s.unit, IFNULL(s.supplier_code, \'\')
         FROM stocks s JOIN departments d ON d.id = s.dept_id
         ORDER BY d.name, s.category, s.name'
    )->fetchAll(PDO::FETCH_NUM),
    'honey_products' => $pdo->query(
        "SELECT p.id, IFNULL(p.variant, ''), IFNULL(p.size, '')
         FROM products p JOIN departments d ON d.id = p.dept_id
         WHERE d.name = 'honey' AND p.name = 'Honey' ORDER BY p.variant, p.size"
    )->fetchAll(PDO::FETCH_NUM),
    'dept_products' => $pdo->query(
        "SELECT p.id, p.name, d.name, IFNULL(p.dept_product_key, '')
         FROM products p JOIN departments d ON d.id = p.dept_id
         WHERE p.dept_product_key IN ('pt','brc','jar_cacao','honey_comb')
            OR p.name IN ('Paragis Tea','Black Rice Coffee','Jar Cacao','Honey Comb')
         ORDER BY d.name, p.name"
    )->fetchAll(PDO::FETCH_NUM),
    'dept_inv' => $pdo->query('SELECT product_key, current_qty FROM dept_inventory_rows')->fetchAll(PDO::FETCH_KEY_PAIR),
    'fulfillment' => $pdo->query(
        'SELECT fpb.stock_id, s.name FROM fulfillment_packing_balances fpb
         JOIN stocks s ON s.id = fpb.stock_id ORDER BY s.name'
    )->fetchAll(PDO::FETCH_NUM),
];
$path = dirname(__DIR__) . '/docs/opening_inventory/export_data.json';
file_put_contents($path, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Wrote $path\n";
