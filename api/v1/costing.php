<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/suppliers.php';
require_once __DIR__ . '/../../includes/honey_volume.php';
require_once __DIR__ . '/_costing_helpers.php';
require_once __DIR__ . '/../../includes/material_inventory.php';

/**
 * Phase 4 — Product Costing API.  Reuses the transaction logic implemented
 * in `dept/products.php` so the SPA can drive the same flows over JSON.
 *
 *   GET  ?action=stocks_list
 *   GET  ?action=products_list
 *   GET  ?action=product_view&product_id=...
 *   POST ?action=stock_update_price
 *   POST ?action=formula_add
 *   POST ?action=formula_update
 *   POST ?action=formula_delete
 *   POST ?action=product_create
 *   POST ?action=product_update_selling_price
 */

api_dispatch([
    'stocks_list' => 'api_costing_stocks_list',
    'products_list' => 'api_costing_products_list',
    'product_view' => 'api_costing_product_view',
    'stock_update_price' => 'api_costing_stock_update_price',
    'formula_add' => 'api_costing_formula_add',
    'formula_update' => 'api_costing_formula_update',
    'formula_delete' => 'api_costing_formula_delete',
    'product_create' => 'api_costing_product_create',
    'product_update_selling_price' => 'api_costing_product_update_selling_price',
]);

// --------------------------------------------------------------------------
// Handlers — helpers live in _costing_helpers.php so other endpoints can
// reuse them without retriggering the dispatcher.
// --------------------------------------------------------------------------

function api_costing_stocks_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_costing_can_view_all($role);
    if (!$canViewAll && $userDeptId < 1) {
        api_fail('Your account has no department assigned.', 400);
    }
    $pdo = ims_pdo();

    $hasCategory = material_inventory_has_columns($pdo);
    $categoryCol = $hasCategory ? 's.category,' : '';

    $sql = "SELECT s.id, s.name, s.unit, s.current_price, s.dept_id, s.supplier_id,
                   {$categoryCol}
                   s.supplier_name, s.supplier_code, s.created_at,
                   d.name AS dept_name,
                   sup.first_name AS supplier_first_name,
                   sup.last_name AS supplier_last_name,
                   (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at,
                   (SELECT sph.old_price FROM stock_price_history sph
                    WHERE sph.stock_id = s.id ORDER BY sph.id DESC LIMIT 1) AS previous_price,
                   (SELECT sph.changed_at FROM stock_price_history sph
                    WHERE sph.stock_id = s.id ORDER BY sph.id DESC LIMIT 1) AS price_changed_at
            FROM stocks s
            INNER JOIN departments d ON d.id = s.dept_id
            LEFT JOIN suppliers sup ON sup.id = s.supplier_id";
    $params = [];
    if (!$canViewAll) {
        $sql .= ' WHERE s.dept_id = ?';
        $params[] = $userDeptId;
    }
    $sql .= ' ORDER BY d.name ASC,
              COALESCE(sup.last_name, ""), COALESCE(sup.first_name, ""),
              s.name ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['supplier_first_name']) || !empty($row['supplier_last_name'])) {
            $row['supplier_display_name'] = supplier_display_name(
                (string) ($row['supplier_first_name'] ?? ''),
                (string) ($row['supplier_last_name'] ?? '')
            );
        } else {
            $row['supplier_display_name'] = null;
        }
        unset($row['supplier_first_name'], $row['supplier_last_name']);
        $rows[] = api_costing_enrich_stock_row($row);
    }
    api_ok([
        'stocks' => $rows,
        'show_pricing' => api_costing_show_pricing($role),
        'can_edit_stocks' => api_costing_can_edit_stocks($role),
    ]);
}

function api_costing_products_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_costing_can_view_all($role);
    if (!$canViewAll && $userDeptId < 1) {
        api_fail('Your account has no department assigned.', 400);
    }
    $pdo = ims_pdo();

    $sql = 'SELECT p.id, p.name, p.dept_product_key, p.dept_id, p.size, p.variant, p.selling_price, p.baseline_cost, p.created_at,
                   d.name AS dept_name, u.name AS created_by_name,
                   COALESCE(SUM(f.qty_per_unit * s.current_price), 0) AS current_cost,
                   COUNT(f.id) AS formula_count
            FROM products p
            INNER JOIN departments d ON d.id = p.dept_id
            INNER JOIN users u ON u.id = p.created_by
            LEFT JOIN product_formula f ON f.product_id = p.id
            LEFT JOIN stocks s ON s.id = f.stock_id';
    $params = [];
    if (!$canViewAll) {
        $sql .= ' WHERE p.dept_id = ?';
        $params[] = $userDeptId;
    }
    $sql .= ' GROUP BY p.id ORDER BY p.created_at DESC, p.id DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    api_ok([
        'products' => $st->fetchAll(),
        'show_pricing' => api_costing_show_pricing($role),
        'can_manage_products' => api_costing_can_manage_products($role),
    ]);
}

function api_costing_product_view(): void
{
    api_method_allowed(['GET', 'POST']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_costing_can_view_all($role);
    if (!$canViewAll && $userDeptId < 1) {
        api_fail('Your account has no department assigned.', 400);
    }

    $productId = (int) ($_REQUEST['product_id'] ?? 0);
    if ($productId < 1) {
        api_fail('product_id is required.', 422);
    }

    $pdo = ims_pdo();
    $detail = api_costing_build_product_detail($pdo, $productId, $canViewAll, $userDeptId, api_costing_show_pricing($role));
    api_ok($detail);
}

function api_costing_stock_update_price(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);

    $stockId = (int) ($input['stock_id'] ?? 0);
    $stockName = trim((string) ($input['name'] ?? ''));
    $unit = trim((string) ($input['unit'] ?? 'pcs'));
    $supplierName = trim((string) ($input['supplier_name'] ?? ''));
    $supplierCode = trim((string) ($input['supplier_code'] ?? ''));
    $priceRaw = trim((string) ($input['current_price'] ?? ''));

    if ($stockId < 1) {
        api_fail('Invalid stock.', 422);
    }
    if ($stockName === '') {
        api_fail('Stock name is required.', 422);
    }
    if (strlen($unit) > 32) {
        api_fail('Unit is too long.', 422);
    }
    if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
        api_fail('Price must be 0 or greater.', 422);
    }
    $newPrice = round((float) $priceRaw, 2);

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $stLock = $pdo->prepare('SELECT id, dept_id, current_price FROM stocks WHERE id = ? LIMIT 1 FOR UPDATE');
        $stLock->execute([$stockId]);
        $locked = $stLock->fetch();
        if (!$locked) {
            throw new RuntimeException('Stock not found.');
        }
        $oldPrice = round((float) ($locked['current_price'] ?? 0), 2);

        $upd = $pdo->prepare(
            'UPDATE stocks SET name = ?, unit = ?, supplier_name = ?, supplier_code = ? WHERE id = ?'
        );
        $upd->execute([
            $stockName,
            $unit,
            $supplierName === '' ? null : $supplierName,
            $supplierCode === '' ? null : $supplierCode,
            $stockId,
        ]);
        if ($newPrice !== $oldPrice) {
            api_costing_apply_price_change($pdo, $stockId, $oldPrice, $newPrice, (int) $user['id']);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok(['updated' => true, 'stock_id' => $stockId]);
}

function api_costing_product_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!api_costing_can_manage_products($role)) {
        api_fail('Forbidden.', 403);
    }
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_costing_can_view_all($role);
    $canEditPricing = $role === 'executive';

    $name = trim((string) ($input['name'] ?? ''));
    $deptId = $canViewAll ? (int) ($input['dept_id'] ?? 0) : $userDeptId;
    $size = trim((string) ($input['size'] ?? ''));
    $variant = trim((string) ($input['variant'] ?? ''));
    $sellingRaw = trim((string) ($input['selling_price'] ?? '0'));

    if ($name === '') {
        api_fail('Product name is required.', 422);
    }
    if ($deptId < 1) {
        api_fail('Department is required.', 422);
    }
    if ($size !== '' && !in_array($size, ['liter', '350ml', '250ml', 'gallon'], true)) {
        api_fail('Invalid size.', 422);
    }
    if ($variant !== '' && !in_array($variant, ['light', 'amber', 'dark'], true)) {
        api_fail('Invalid variant.', 422);
    }
    if (!$canEditPricing) {
        $sellingRaw = '0';
    }
    if (!is_numeric($sellingRaw) || (float) $sellingRaw < 0) {
        api_fail('Selling price must be 0 or greater.', 422);
    }
    $sellingPrice = (float) $sellingRaw;

    $pdo = ims_pdo();
    $ins = $pdo->prepare(
        'INSERT INTO products (name, dept_id, size, variant, selling_price, baseline_cost, created_by)
         VALUES (?, ?, ?, ?, ?, 0, ?)'
    );
    $ins->execute([
        $name,
        $deptId,
        $size === '' ? null : $size,
        $variant === '' ? null : $variant,
        $sellingPrice,
        (int) $user['id'],
    ]);
    api_ok(['id' => (int) $pdo->lastInsertId()]);
}

function api_costing_product_update_selling_price(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);

    $productId = (int) ($input['product_id'] ?? 0);
    $sellingRaw = trim((string) ($input['selling_price'] ?? ''));

    if ($productId < 1) {
        api_fail('Invalid product.', 422);
    }
    if (!is_numeric($sellingRaw) || (float) $sellingRaw < 0) {
        api_fail('Selling price must be 0 or greater.', 422);
    }
    $sellingPrice = round((float) $sellingRaw, 2);

    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
    $st->execute([$productId]);
    if (!$st->fetch()) {
        api_fail('Product not found.', 404);
    }

    $upd = $pdo->prepare('UPDATE products SET selling_price = ? WHERE id = ?');
    $upd->execute([$sellingPrice, $productId]);

    api_ok([
        'updated' => true,
        'product_id' => $productId,
        'selling_price' => $sellingPrice,
    ]);
}

function api_costing_formula_mutate(string $action): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!api_costing_can_manage_products($role)) {
        api_fail('Forbidden.', 403);
    }
    $userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    $canViewAll = api_costing_can_view_all($role);

    $productId = (int) ($input['product_id'] ?? 0);
    if ($productId < 1) {
        api_fail('Invalid product.', 422);
    }

    $pdo = ims_pdo();
    $product = api_costing_fetch_product_scope($pdo, $productId, $canViewAll, $userDeptId);

    $pdo->beginTransaction();
    try {
        if ($action === 'add') {
            $stockId = (int) ($input['stock_id'] ?? 0);
            $qtyRaw = trim((string) ($input['qty_per_unit'] ?? ''));
            if ($stockId < 1) {
                throw new RuntimeException('Stock is required.');
            }
            if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
                throw new RuntimeException('qty_per_unit must be > 0.');
            }
            $qty = (float) $qtyRaw;
            $stStock = $pdo->prepare('SELECT id, name, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
            $stStock->execute([$stockId, $product['dept_id']]);
            $stock = $stStock->fetch();
            if (!$stock) {
                throw new RuntimeException('Stock not found in same department.');
            }
            $qty = api_costing_resolve_formula_qty($product, (string) $stock['name'], $qty);
            $price = (float) $stock['current_price'];
            $ins = $pdo->prepare(
                'INSERT INTO product_formula (product_id, stock_id, qty_per_unit, price_at_creation) VALUES (?, ?, ?, ?)'
            );
            $ins->execute([$productId, $stockId, $qty, $price]);
        } elseif ($action === 'update') {
            $formulaId = (int) ($input['formula_id'] ?? 0);
            $stockId = (int) ($input['stock_id'] ?? 0);
            $qtyRaw = trim((string) ($input['qty_per_unit'] ?? ''));
            if ($formulaId < 1 || $stockId < 1) {
                throw new RuntimeException('Invalid formula row.');
            }
            if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
                throw new RuntimeException('qty_per_unit must be > 0.');
            }
            $qty = (float) $qtyRaw;
            $stOwn = $pdo->prepare('SELECT id FROM product_formula WHERE id = ? AND product_id = ? LIMIT 1');
            $stOwn->execute([$formulaId, $productId]);
            if (!$stOwn->fetch()) {
                throw new RuntimeException('Formula row not found.');
            }
            $stStock = $pdo->prepare('SELECT id, name, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
            $stStock->execute([$stockId, $product['dept_id']]);
            $stock = $stStock->fetch();
            if (!$stock) {
                throw new RuntimeException('Stock not found in same department.');
            }
            $qty = api_costing_resolve_formula_qty($product, (string) $stock['name'], $qty);
            $price = (float) $stock['current_price'];
            $upd = $pdo->prepare(
                'UPDATE product_formula SET stock_id = ?, qty_per_unit = ?, price_at_creation = ? WHERE id = ?'
            );
            $upd->execute([$stockId, $qty, $price, $formulaId]);
        } else { // delete
            $formulaId = (int) ($input['formula_id'] ?? 0);
            if ($formulaId < 1) {
                throw new RuntimeException('Invalid formula row.');
            }
            $del = $pdo->prepare('DELETE FROM product_formula WHERE id = ? AND product_id = ?');
            $del->execute([$formulaId, $productId]);
        }

        api_costing_recalculate_baseline($pdo, $productId);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    $detail = api_costing_build_product_detail($pdo, $productId, $canViewAll, $userDeptId, api_costing_show_pricing($role));
    api_ok($detail);
}

function api_costing_formula_add(): void
{
    api_costing_formula_mutate('add');
}

function api_costing_formula_update(): void
{
    api_costing_formula_mutate('update');
}

function api_costing_formula_delete(): void
{
    api_costing_formula_mutate('delete');
}
