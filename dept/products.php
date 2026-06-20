<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
requireRole([IMS_ROLE_EXECUTIVE, IMS_ROLE_PROD_MANAGER, IMS_ROLE_TL, IMS_ROLE_STAFF], '../auth/login.php');

ims_session_start();
$user = ims_current_user();
$role = (string) ($user['role'] ?? '');
$userId = (int) ($user['id'] ?? 0);
$userDeptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;

$canViewAll = ims_can_view_all_departments($role);
$canManageProducts = ims_is_executive($role) || ims_is_prod_manager($role) || ims_is_tl($role);
$canAddStocks = ims_is_executive($role);
$canDeductStock = !ims_pos_authenticated();
$showPricing = ims_is_executive($role);
$canEditPricing = ims_is_executive($role);

if (!$canViewAll && $userDeptId < 1) {
    http_response_code(400);
    echo 'Your account has no department assigned.';
    exit;
}

$notice = '';
$error = '';
$ajaxResponse = null;

if (!isset($_SESSION['ims_csrf']) || !is_string($_SESSION['ims_csrf']) || $_SESSION['ims_csrf'] === '') {
    $_SESSION['ims_csrf'] = bin2hex(random_bytes(24));
}
$csrf = (string) $_SESSION['ims_csrf'];

try {
    $pdo = ims_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Database unavailable.';
    exit;
}

/**
 * @return array{id:int,dept_id:int,name:string,size:?string,variant:?string,selling_price:float,baseline_cost:float,current_cost:float,dept_name:string}
 */
function ims_fetch_product_scope(PDO $pdo, int $productId, bool $canViewAll, int $userDeptId): array
{
    $sql = 'SELECT p.id, p.dept_id, p.name, p.size, p.variant, p.selling_price, p.baseline_cost,
                   COALESCE(SUM(f.qty_per_unit * s.current_price), 0) AS current_cost,
                   d.name AS dept_name
            FROM products p
            INNER JOIN departments d ON d.id = p.dept_id
            LEFT JOIN product_formula f ON f.product_id = p.id
            LEFT JOIN stocks s ON s.id = f.stock_id
            WHERE p.id = ?';
    $args = [$productId];
    if (!$canViewAll) {
        $sql .= ' AND p.dept_id = ?';
        $args[] = $userDeptId;
    }
    $sql .= ' GROUP BY p.id LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Product not found or out of your scope.');
    }
    return [
        'id' => (int) $row['id'],
        'dept_id' => (int) $row['dept_id'],
        'name' => (string) $row['name'],
        'size' => isset($row['size']) ? (string) $row['size'] : null,
        'variant' => isset($row['variant']) ? (string) $row['variant'] : null,
        'selling_price' => (float) $row['selling_price'],
        'baseline_cost' => (float) $row['baseline_cost'],
        'current_cost' => (float) $row['current_cost'],
        'dept_name' => (string) $row['dept_name'],
    ];
}

function ims_recalculate_product_baseline(PDO $pdo, int $productId): float
{
    $st = $pdo->prepare('SELECT COALESCE(SUM(qty_per_unit * price_at_creation), 0) FROM product_formula WHERE product_id = ?');
    $st->execute([$productId]);
    $baseline = (float) $st->fetchColumn();
    $upd = $pdo->prepare('UPDATE products SET baseline_cost = ? WHERE id = ?');
    $upd->execute([$baseline, $productId]);
    return $baseline;
}

function ims_apply_stock_price_change(PDO $pdo, int $stockId, float $oldPrice, float $newPrice, int $userId): void
{
    $stAffected = $pdo->prepare('SELECT DISTINCT product_id FROM product_formula WHERE stock_id = ?');
    $stAffected->execute([$stockId]);
    $affectedIds = array_map(static fn(array $r): int => (int) $r['product_id'], $stAffected->fetchAll());

    $oldCostMap = [];
    if ($affectedIds !== []) {
        $in = implode(',', array_fill(0, count($affectedIds), '?'));
        $stOldCosts = $pdo->prepare(
            "SELECT pf.product_id, COALESCE(SUM(pf.qty_per_unit * s.current_price), 0) AS current_cost
             FROM product_formula pf
             INNER JOIN stocks s ON s.id = pf.stock_id
             WHERE pf.product_id IN ($in)
             GROUP BY pf.product_id"
        );
        $stOldCosts->execute($affectedIds);
        foreach ($stOldCosts->fetchAll() as $r) {
            $oldCostMap[(int) $r['product_id']] = round((float) $r['current_cost'], 2);
        }
    }

    $updStock = $pdo->prepare('UPDATE stocks SET current_price = ? WHERE id = ?');
    $updStock->execute([$newPrice, $stockId]);

    $insPriceHistory = $pdo->prepare(
        'INSERT INTO stock_price_history (stock_id, old_price, new_price, changed_by)
         VALUES (?, ?, ?, ?)'
    );
    $insPriceHistory->execute([$stockId, $oldPrice, $newPrice, $userId]);

    if ($affectedIds === []) {
        return;
    }

    $in = implode(',', array_fill(0, count($affectedIds), '?'));
    $stNewCosts = $pdo->prepare(
        "SELECT pf.product_id, COALESCE(SUM(pf.qty_per_unit * s.current_price), 0) AS current_cost
         FROM product_formula pf
         INNER JOIN stocks s ON s.id = pf.stock_id
         WHERE pf.product_id IN ($in)
         GROUP BY pf.product_id"
    );
    $stNewCosts->execute($affectedIds);
    $newCostMap = [];
    foreach ($stNewCosts->fetchAll() as $r) {
        $newCostMap[(int) $r['product_id']] = round((float) $r['current_cost'], 2);
    }

    $stLast = $pdo->prepare('SELECT new_cost FROM product_cost_history WHERE product_id = ? ORDER BY id DESC LIMIT 1');
    $insCostHist = $pdo->prepare(
        'INSERT INTO product_cost_history (product_id, old_cost, new_cost, triggered_by_stock_id)
         VALUES (?, ?, ?, ?)'
    );
    foreach ($affectedIds as $pid) {
        $stLast->execute([$pid]);
        $last = $stLast->fetchColumn();
        $compareOld = $last !== false ? round((float) $last, 2) : ($oldCostMap[$pid] ?? 0.00);
        $newCost = $newCostMap[$pid] ?? 0.00;
        if ($compareOld !== $newCost) {
            $insCostHist->execute([$pid, $compareOld, $newCost, $stockId]);
        }
    }
}

/**
 * @return array<string,mixed>
 */
function ims_build_product_detail(PDO $pdo, int $productId, bool $canViewAll, int $userDeptId): array
{
    $product = ims_fetch_product_scope($pdo, $productId, $canViewAll, $userDeptId);

    $stFormula = $pdo->prepare(
        'SELECT pf.id, pf.stock_id, pf.qty_per_unit, pf.price_at_creation,
                s.name AS stock_name, s.unit, s.current_price
         FROM product_formula pf
         INNER JOIN stocks s ON s.id = pf.stock_id
         WHERE pf.product_id = ?
         ORDER BY pf.id ASC'
    );
    $stFormula->execute([$productId]);
    $rows = [];
    $stockIds = [];
    foreach ($stFormula->fetchAll() as $r) {
        $qty = (float) $r['qty_per_unit'];
        $baselineUnit = (float) $r['price_at_creation'];
        $currentUnit = (float) $r['current_price'];
        $rows[] = [
            'id' => (int) $r['id'],
            'stock_id' => (int) $r['stock_id'],
            'stock_name' => (string) $r['stock_name'],
            'unit' => (string) $r['unit'],
            'qty_per_unit' => $qty,
            'price_at_creation' => $baselineUnit,
            'current_price' => $currentUnit,
            'baseline_line_cost' => $qty * $baselineUnit,
            'current_line_cost' => $qty * $currentUnit,
            'delta' => ($qty * $currentUnit) - ($qty * $baselineUnit),
        ];
        $stockIds[] = (int) $r['stock_id'];
    }
    $stockIds = array_values(array_unique($stockIds));

    $stCostHist = $pdo->prepare(
        'SELECT old_cost, new_cost, recalculated_at
         FROM product_cost_history
         WHERE product_id = ?
         ORDER BY id DESC
         LIMIT 100'
    );
    $stCostHist->execute([$productId]);
    $costHistory = $stCostHist->fetchAll();

    $stockHistory = [];
    if ($stockIds !== []) {
        $in = implode(',', array_fill(0, count($stockIds), '?'));
        $stStockHist = $pdo->prepare(
            "SELECT sph.stock_id, sph.old_price, sph.new_price, sph.changed_at, s.name AS stock_name
             FROM stock_price_history sph
             INNER JOIN stocks s ON s.id = sph.stock_id
             WHERE sph.stock_id IN ($in)
             ORDER BY sph.id DESC
             LIMIT 200"
        );
        $stStockHist->execute($stockIds);
        $stockHistory = $stStockHist->fetchAll();
    }

    return [
        'product' => $product,
        'formula_rows' => $rows,
        'cost_history' => $costHistory,
        'stock_history' => $stockHistory,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $wantsJson = ((string) ($_POST['ajax'] ?? '0')) === '1';
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postedCsrf)) {
        $error = 'Session expired. Refresh and try again.';
        if ($wantsJson) {
            $ajaxResponse = ['ok' => false, 'error' => $error];
        }
    } else {
        $action = trim((string) ($_POST['action'] ?? ''));
        try {
            if ($action === 'add_stock_entry') {
                if (!$canAddStocks) {
                    throw new RuntimeException('Only Executive can add stocks.');
                }
                $stockName = trim((string) ($_POST['stock_name'] ?? ''));
                $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
                $qty = (float) ($_POST['qty'] ?? 0);
                $batchingDateRaw = trim((string) ($_POST['batching_date'] ?? ''));
                $supplierBatchNo = trim((string) ($_POST['supplier_batch_no'] ?? ''));
                $supplierName = trim((string) ($_POST['supplier_name'] ?? ''));
                $supplierCode = trim((string) ($_POST['supplier_code'] ?? ''));
                $currentPrice = trim((string) ($_POST['current_price'] ?? ''));
                $deptId = $canViewAll ? (int) ($_POST['dept_id'] ?? 0) : $userDeptId;

                if ($deptId < 1) {
                    throw new RuntimeException('Department is required.');
                }
                if ($stockName === '') {
                    throw new RuntimeException('Stock name is required.');
                }
                if ($qty <= 0) {
                    throw new RuntimeException('Quantity must be greater than zero.');
                }
                if ($batchingDateRaw === '') {
                    throw new RuntimeException('Batching date is required.');
                }
                $batchingDate = str_replace('T', ' ', $batchingDateRaw);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $batchingDate)) {
                    throw new RuntimeException('Invalid batching date format.');
                }
                if (!str_contains($batchingDate, ':')) {
                    $batchingDate .= ' 00:00:00';
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $batchingDate)) {
                    $batchingDate .= ':00';
                }
                if (strlen($unit) > 32) {
                    throw new RuntimeException('Unit is too long.');
                }
                if (strlen($supplierBatchNo) > 120) {
                    throw new RuntimeException('Supplier batch no is too long.');
                }
                $priceVal = 0.00;
                if ($currentPrice !== '') {
                    if (!is_numeric($currentPrice) || (float) $currentPrice < 0) {
                        throw new RuntimeException('Price cannot be negative.');
                    }
                    $priceVal = round((float) $currentPrice, 2);
                }

                $pdo->beginTransaction();
                $stStock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE dept_id = ? AND name = ? LIMIT 1 FOR UPDATE');
                $stStock->execute([$deptId, $stockName]);
                $existing = $stStock->fetch();
                $stockId = (int) ($existing['id'] ?? 0);

                if ($stockId > 0) {
                    $oldPrice = round((float) ($existing['current_price'] ?? 0), 2);
                    $upd = $pdo->prepare(
                        'UPDATE stocks
                         SET unit = ?, supplier_name = ?, supplier_code = ?
                         WHERE id = ?'
                    );
                    $upd->execute([
                        $unit,
                        $supplierName === '' ? null : $supplierName,
                        $supplierCode === '' ? null : $supplierCode,
                        $stockId,
                    ]);
                    if ($priceVal !== $oldPrice) {
                        ims_apply_stock_price_change($pdo, $stockId, $oldPrice, $priceVal, $userId);
                    }
                } else {
                    $insStock = $pdo->prepare(
                        'INSERT INTO stocks (name, dept_id, unit, current_price, supplier_name, supplier_code, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insStock->execute([
                        $stockName,
                        $deptId,
                        $unit,
                        $priceVal,
                        $supplierName === '' ? null : $supplierName,
                        $supplierCode === '' ? null : $supplierCode,
                        $userId,
                    ]);
                    $stockId = (int) $pdo->lastInsertId();
                }

                $insEntry = $pdo->prepare(
                    'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insEntry->execute([
                    $stockId,
                    $deptId,
                    $qty,
                    $batchingDate,
                    $supplierBatchNo === '' ? null : $supplierBatchNo,
                    $userId,
                ]);
                $pdo->commit();
                $notice = 'Stock entry saved.';

                if ($wantsJson) {
                    $stStockRow = $pdo->prepare(
                        'SELECT s.id, s.name, s.unit, s.current_price, s.supplier_name, s.supplier_code, d.name AS dept_name,
                                (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
                         FROM stocks s
                         INNER JOIN departments d ON d.id = s.dept_id
                         WHERE s.id = ? LIMIT 1'
                    );
                    $stStockRow->execute([$stockId]);
                    $row = $stStockRow->fetch() ?: [];
                    $ajaxResponse = ['ok' => true, 'message' => $notice, 'stock' => $row];
                }
            } elseif ($action === 'create_product') {
                if (!$canManageProducts) {
                    throw new RuntimeException('You are not allowed to create products.');
                }
                $name = trim((string) ($_POST['name'] ?? ''));
                $deptId = $canViewAll ? (int) ($_POST['dept_id'] ?? 0) : $userDeptId;
                $size = trim((string) ($_POST['size'] ?? ''));
                $variant = trim((string) ($_POST['variant'] ?? ''));
                $sellingPriceRaw = trim((string) ($_POST['selling_price'] ?? '0'));

                if ($name === '') {
                    throw new RuntimeException('Product name is required.');
                }
                if ($deptId < 1) {
                    throw new RuntimeException('Department is required.');
                }
                if ($size !== '' && !in_array($size, ['liter', '350ml', '250ml', 'gallon'], true)) {
                    throw new RuntimeException('Invalid size value.');
                }
                if ($variant !== '' && !in_array($variant, ['light', 'amber', 'dark'], true)) {
                    throw new RuntimeException('Invalid variant value.');
                }
                if (!$canEditPricing) {
                    $sellingPriceRaw = '0';
                }
                if (!is_numeric($sellingPriceRaw) || (float) $sellingPriceRaw < 0) {
                    throw new RuntimeException('Selling price must be 0 or greater.');
                }
                $sellingPrice = (float) $sellingPriceRaw;

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
                    $userId,
                ]);
                $productId = (int) $pdo->lastInsertId();
                $notice = 'Product created.';

                if ($wantsJson) {
                    $stRow = $pdo->prepare(
                        'SELECT p.id, p.name, p.size, p.variant, p.baseline_cost, p.selling_price,
                                d.name AS dept_name
                         FROM products p
                         INNER JOIN departments d ON d.id = p.dept_id
                         WHERE p.id = ? LIMIT 1'
                    );
                    $stRow->execute([$productId]);
                    $row = $stRow->fetch() ?: [];
                    $ajaxResponse = ['ok' => true, 'message' => $notice, 'product' => $row];
                }
            } elseif ($action === 'edit_stock') {
                if (!$canAddStocks) {
                    throw new RuntimeException('Only Executive can edit stocks.');
                }
                $stockId = (int) ($_POST['stock_id'] ?? 0);
                $stockName = trim((string) ($_POST['stock_name'] ?? ''));
                $unit = trim((string) ($_POST['unit'] ?? 'pcs'));
                $supplierName = trim((string) ($_POST['supplier_name'] ?? ''));
                $supplierCode = trim((string) ($_POST['supplier_code'] ?? ''));
                $newPriceRaw = trim((string) ($_POST['current_price'] ?? ''));
                if ($stockId < 1) {
                    throw new RuntimeException('Invalid stock.');
                }
                if ($stockName === '') {
                    throw new RuntimeException('Stock name is required.');
                }
                if (strlen($unit) > 32) {
                    throw new RuntimeException('Unit is too long.');
                }
                if (!is_numeric($newPriceRaw) || (float) $newPriceRaw < 0) {
                    throw new RuntimeException('Price must be 0 or greater.');
                }
                $newPrice = round((float) $newPriceRaw, 2);

                $pdo->beginTransaction();
                $sql = 'SELECT id, dept_id, current_price FROM stocks WHERE id = ? LIMIT 1 FOR UPDATE';
                $args = [$stockId];
                if (!$canViewAll) {
                    $sql = 'SELECT id, dept_id, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1 FOR UPDATE';
                    $args = [$stockId, $userDeptId];
                }
                $stLock = $pdo->prepare($sql);
                $stLock->execute($args);
                $locked = $stLock->fetch();
                if (!$locked) {
                    throw new RuntimeException('Stock not found or out of your scope.');
                }
                $oldPrice = round((float) ($locked['current_price'] ?? 0), 2);

                $upd = $pdo->prepare(
                    'UPDATE stocks
                     SET name = ?, unit = ?, supplier_name = ?, supplier_code = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $stockName,
                    $unit,
                    $supplierName === '' ? null : $supplierName,
                    $supplierCode === '' ? null : $supplierCode,
                    $stockId,
                ]);
                if ($newPrice !== $oldPrice) {
                    ims_apply_stock_price_change($pdo, $stockId, $oldPrice, $newPrice, $userId);
                }
                $pdo->commit();
                $notice = 'Stock updated.';

                if ($wantsJson) {
                    $stRow = $pdo->prepare(
                        'SELECT s.id, s.name, s.unit, s.current_price, s.supplier_name, s.supplier_code, d.name AS dept_name,
                                (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
                         FROM stocks s
                         INNER JOIN departments d ON d.id = s.dept_id
                         WHERE s.id = ? LIMIT 1'
                    );
                    $stRow->execute([$stockId]);
                    $row = $stRow->fetch() ?: [];
                    $ajaxResponse = ['ok' => true, 'message' => $notice, 'stock' => $row];
                }
            } elseif ($action === 'add_formula_row' || $action === 'update_formula_row' || $action === 'delete_formula_row') {
                if (!$canManageProducts) {
                    throw new RuntimeException('You are not allowed to update formulas.');
                }
                $productId = (int) ($_POST['product_id'] ?? 0);
                if ($productId < 1) {
                    throw new RuntimeException('Invalid product.');
                }
                $product = ims_fetch_product_scope($pdo, $productId, $canViewAll, $userDeptId);

                $pdo->beginTransaction();
                if ($action === 'add_formula_row') {
                    $stockId = (int) ($_POST['stock_id'] ?? 0);
                    $qtyRaw = trim((string) ($_POST['qty_per_unit'] ?? ''));
                    if ($stockId < 1) {
                        throw new RuntimeException('Stock is required.');
                    }
                    if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
                        throw new RuntimeException('qty_per_unit must be > 0.');
                    }
                    $qty = (float) $qtyRaw;
                    $stStock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
                    $stStock->execute([$stockId, $product['dept_id']]);
                    $stock = $stStock->fetch();
                    if (!$stock) {
                        throw new RuntimeException('Stock not found in same department.');
                    }
                    $price = (float) $stock['current_price'];
                    $ins = $pdo->prepare(
                        'INSERT INTO product_formula (product_id, stock_id, qty_per_unit, price_at_creation)
                         VALUES (?, ?, ?, ?)'
                    );
                    $ins->execute([$productId, $stockId, $qty, $price]);
                } elseif ($action === 'update_formula_row') {
                    $formulaId = (int) ($_POST['formula_id'] ?? 0);
                    $stockId = (int) ($_POST['stock_id'] ?? 0);
                    $qtyRaw = trim((string) ($_POST['qty_per_unit'] ?? ''));
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
                    $stStock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
                    $stStock->execute([$stockId, $product['dept_id']]);
                    $stock = $stStock->fetch();
                    if (!$stock) {
                        throw new RuntimeException('Stock not found in same department.');
                    }
                    $price = (float) $stock['current_price'];
                    $upd = $pdo->prepare(
                        'UPDATE product_formula
                         SET stock_id = ?, qty_per_unit = ?, price_at_creation = ?
                         WHERE id = ?'
                    );
                    $upd->execute([$stockId, $qty, $price, $formulaId]);
                } else {
                    $formulaId = (int) ($_POST['formula_id'] ?? 0);
                    if ($formulaId < 1) {
                        throw new RuntimeException('Invalid formula row.');
                    }
                    $del = $pdo->prepare('DELETE FROM product_formula WHERE id = ? AND product_id = ?');
                    $del->execute([$formulaId, $productId]);
                }

                ims_recalculate_product_baseline($pdo, $productId);
                $pdo->commit();
                $detail = ims_build_product_detail($pdo, $productId, $canViewAll, $userDeptId);
                $ajaxResponse = ['ok' => true, 'message' => 'Formula updated.', 'detail' => $detail];
            } elseif ($action === 'fetch_product_view') {
                $productId = (int) ($_POST['product_id'] ?? 0);
                if ($productId < 1) {
                    throw new RuntimeException('Invalid product.');
                }
                $detail = ims_build_product_detail($pdo, $productId, $canViewAll, $userDeptId);
                $ajaxResponse = ['ok' => true, 'detail' => $detail];
            } elseif ($action === 'add_deduction') {
                if (!$canDeductStock) {
                    throw new RuntimeException('Stock deduction is not available for this session.');
                }
                $stockId = (int) ($_POST['stock_id'] ?? 0);
                $qtyRaw = trim((string) ($_POST['qty'] ?? ''));
                $reason = trim((string) ($_POST['reason'] ?? ''));
                $deptId = $canViewAll ? (int) ($_POST['dept_id'] ?? 0) : $userDeptId;
                if ($stockId < 1 || $deptId < 1) {
                    throw new RuntimeException('Department and stock are required.');
                }
                if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
                    throw new RuntimeException('Deduction qty must be > 0.');
                }
                if ($reason === '') {
                    throw new RuntimeException('Reason is required.');
                }
                $qty = (float) $qtyRaw;

                $stStock = $pdo->prepare('SELECT id FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
                $stStock->execute([$stockId, $deptId]);
                if (!$stStock->fetch()) {
                    throw new RuntimeException('Stock not found for selected department.');
                }
                $ins = $pdo->prepare(
                    'INSERT INTO stock_deductions (stock_id, dept_id, qty, reason, deducted_by)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $ins->execute([$stockId, $deptId, $qty, $reason, $userId]);
                $notice = 'Stock deduction saved.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
            if ($wantsJson) {
                $ajaxResponse = ['ok' => false, 'error' => $error];
            }
        }
    }

    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ajaxResponse ?? ['ok' => false, 'error' => 'Unhandled request']);
        exit;
    }
}

$departments = [];
$stocks = [];
$products = [];
$deductions = [];

try {
    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? $stDept->fetchAll() : [];

    $sqlStocks = 'SELECT s.id, s.name, s.unit, s.current_price, s.dept_id, s.supplier_name, s.supplier_code, s.created_at,
                         d.name AS dept_name,
                         (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
                  FROM stocks s
                  INNER JOIN departments d ON d.id = s.dept_id';
    $argsStocks = [];
    if (!$canViewAll) {
        $sqlStocks .= ' WHERE s.dept_id = ?';
        $argsStocks[] = $userDeptId;
    }
    $sqlStocks .= ' ORDER BY d.name ASC, s.name ASC';
    $stStocks = $pdo->prepare($sqlStocks);
    $stStocks->execute($argsStocks);
    $stocks = $stStocks->fetchAll();

    $sqlProducts = 'SELECT
                      p.id, p.name, p.dept_id, p.size, p.variant, p.selling_price, p.baseline_cost, p.created_at,
                      d.name AS dept_name, u.name AS created_by_name,
                      COALESCE(SUM(f.qty_per_unit * s.current_price), 0) AS current_cost,
                      COUNT(f.id) AS formula_count
                    FROM products p
                    INNER JOIN departments d ON d.id = p.dept_id
                    INNER JOIN users u ON u.id = p.created_by
                    LEFT JOIN product_formula f ON f.product_id = p.id
                    LEFT JOIN stocks s ON s.id = f.stock_id';
    $argsProducts = [];
    if (!$canViewAll) {
        $sqlProducts .= ' WHERE p.dept_id = ?';
        $argsProducts[] = $userDeptId;
    }
    $sqlProducts .= ' GROUP BY p.id ORDER BY p.created_at DESC, p.id DESC';
    $stProducts = $pdo->prepare($sqlProducts);
    $stProducts->execute($argsProducts);
    $products = $stProducts->fetchAll();

    $sqlDeductions = 'SELECT sd.id, sd.qty, sd.reason, sd.created_at,
                             d.name AS dept_name, s.name AS stock_name, u.name AS deducted_by_name
                      FROM stock_deductions sd
                      INNER JOIN departments d ON d.id = sd.dept_id
                      INNER JOIN stocks s ON s.id = sd.stock_id
                      INNER JOIN users u ON u.id = sd.deducted_by';
    $argsDed = [];
    if (ims_is_staff($role)) {
        $sqlDeductions .= ' WHERE sd.dept_id = ? AND sd.deducted_by = ?';
        $argsDed = [$userDeptId, $userId];
    } elseif (ims_is_tl($role)) {
        $sqlDeductions .= ' WHERE sd.dept_id = ?';
        $argsDed = [$userDeptId];
    }
    $sqlDeductions .= ' ORDER BY sd.created_at DESC, sd.id DESC';
    $stDeductions = $pdo->prepare($sqlDeductions);
    $stDeductions->execute($argsDed);
    $deductions = $stDeductions->fetchAll();
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load products/deductions. Run phase 3 and phase 4 migrations.';
}

$pageTitle = 'Costing Module';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    :root { --bg:#fffdf6; --panel:#fff; --border:#e8dcc5; --text:#3f2f1e; --muted:#7e6a4b; --accent:#d99200; --danger:#c65a4a; --ok:#2d6a4f; }
    * { box-sizing:border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); padding:1rem; }
    .card { background:var(--panel); border:1px solid var(--border); border-radius:12px; padding:0.95rem 1rem; margin-bottom:0.9rem; }
    .row { display:grid; gap:0.65rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .overview { display:grid; gap:0.65rem; grid-template-columns:repeat(4,minmax(0,1fr)); }
    .overview .card-mini { border:1px solid var(--border); border-radius:10px; padding:0.65rem; background:#fffdfa; }
    @media (max-width:980px){ .row, .overview { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px){ .row, .overview { grid-template-columns:1fr; } }
    label { display:block; font-size:0.78rem; color:var(--muted); margin:0.25rem 0 0.15rem; }
    input, select, textarea { width:100%; padding:0.5rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    textarea { min-height:72px; resize:vertical; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.45rem 0.7rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .muted { color:var(--muted); }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .toolbar { display:flex; gap:0.5rem; flex-wrap:wrap; }
    .pill { display:inline-flex; align-items:center; border:1px solid var(--border); border-radius:999px; padding:0.1rem 0.45rem; font-size:0.78rem; }
    .pill.up { border-color:#dc9a9a; color:#b32121; background:#fff2f2; }
    .pill.down { border-color:#99cba9; color:#1f6f3d; background:#eefdf2; }
    .pill.same { color:#6b5a3c; background:#fff8e8; }
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.42); display:none; align-items:center; justify-content:center; z-index:1000; padding:1rem; }
    .modal-backdrop.open { display:flex; }
    .modal-card { width:min(980px, 100%); max-height:92vh; overflow:auto; background:#fff; border:1px solid var(--border); border-radius:12px; padding:0.9rem; }
    .modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }
    .modal-title { margin:0; font-size:1.05rem; }
    .modal-close { border:1px solid var(--border); background:#fff; border-radius:8px; width:34px; height:34px; cursor:pointer; }
    .feedback { margin:0.4rem 0 0; font-size:0.9rem; }
    table { width:100%; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.2rem;font-size:1.25rem;"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="muted" style="margin:0 0 0.55rem;">
      Logged in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
      (<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>)
    </p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
      <a class="btn" href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role($role), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>
      <a class="btn" href="beginning_inventory.php">Beginning inventory</a>
      <a class="btn" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">How the costing module works</h2>
    <div class="overview">
      <div class="card-mini">
        <strong>Step 1 — Set stock prices</strong>
        <p class="muted" style="margin:0.35rem 0 0;">Executive adds stock and updates price from stock list.</p>
      </div>
      <div class="card-mini">
        <strong>Step 2 — Build product formula</strong>
        <p class="muted" style="margin:0.35rem 0 0;">Each product stores qty per stock line and baseline snapshot.</p>
      </div>
      <div class="card-mini">
        <strong>Step 3 — Price updates</strong>
        <p class="muted" style="margin:0.35rem 0 0;">Changing stock price recalculates affected product current costs.</p>
      </div>
      <div class="card-mini">
        <strong>Step 4 — Executive view</strong>
        <p class="muted" style="margin:0.35rem 0 0;">Compare current vs baseline and inspect full cost history.</p>
      </div>
    </div>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Quick actions</h2>
    <div class="toolbar">
      <?php if ($canAddStocks): ?>
        <button class="btn primary" type="button" data-open-modal="addStockModal">Add stock</button>
      <?php endif; ?>
      <?php if ($canManageProducts): ?>
        <button class="btn primary" type="button" data-open-modal="createProductModal">Create product</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Stock price management</h2>
    <table id="stocksTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Stock</th>
          <th>Unit</th>
          <th>Price</th>
          <th>Supplier</th>
          <th>Last entry</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stocks as $s): ?>
          <tr data-stock-id="<?= (int) $s['id'] ?>">
            <td><?= (int) $s['id'] ?></td>
            <td class="cell-stock-dept"><?= htmlspecialchars((string) $s['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="cell-stock-name"><?= htmlspecialchars((string) $s['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="cell-stock-unit"><?= htmlspecialchars((string) $s['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="cell-stock-price"><?= number_format((float) $s['current_price'], 2) ?></td>
            <td class="cell-stock-supplier">
              <?= htmlspecialchars((string) ($s['supplier_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
              <span class="muted">(<?= htmlspecialchars((string) ($s['supplier_code'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>)</span>
            </td>
            <td class="cell-stock-last"><?= htmlspecialchars((string) ($s['last_entry_at'] ?? $s['created_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ($canAddStocks): ?>
                <button
                  class="btn js-edit-stock"
                  type="button"
                  data-stock-id="<?= (int) $s['id'] ?>"
                  data-stock-name="<?= htmlspecialchars((string) $s['name'], ENT_QUOTES, 'UTF-8') ?>"
                  data-stock-unit="<?= htmlspecialchars((string) $s['unit'], ENT_QUOTES, 'UTF-8') ?>"
                  data-stock-price="<?= htmlspecialchars((string) $s['current_price'], ENT_QUOTES, 'UTF-8') ?>"
                  data-stock-supplier-name="<?= htmlspecialchars((string) ($s['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-stock-supplier-code="<?= htmlspecialchars((string) ($s['supplier_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                >Edit</button>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Products</h2>
    <table id="productsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Product</th>
          <th>Formula rows</th>
          <?php if ($showPricing): ?>
            <th>Baseline</th>
            <th>Current</th>
            <th>Trend</th>
          <?php endif; ?>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr data-product-id="<?= (int) $p['id'] ?>">
            <td><?= (int) $p['id'] ?></td>
            <td><?= htmlspecialchars((string) $p['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <strong class="cell-product-name"><?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
              <span class="muted">
                size: <?= htmlspecialchars((string) ($p['size'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                | variant: <?= htmlspecialchars((string) ($p['variant'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
              </span>
            </td>
            <td class="cell-product-formula-count"><?= (int) $p['formula_count'] ?></td>
            <?php if ($showPricing): ?>
              <td class="cell-product-baseline"><?= number_format((float) $p['baseline_cost'], 2) ?></td>
              <td class="cell-product-current"><?= number_format((float) $p['current_cost'], 2) ?></td>
              <td>
                <?php
                    $baseline = round((float) $p['baseline_cost'], 2);
                    $current = round((float) $p['current_cost'], 2);
                    $pillClass = 'same';
                    $pillLabel = 'No change';
                    if ($current > $baseline) {
                        $pillClass = 'up';
                        $pillLabel = '+' . number_format($current - $baseline, 2);
                    } elseif ($current < $baseline) {
                        $pillClass = 'down';
                        $pillLabel = '-' . number_format($baseline - $current, 2);
                    }
                ?>
                <span class="pill <?= htmlspecialchars($pillClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pillLabel, ENT_QUOTES, 'UTF-8') ?></span>
              </td>
            <?php endif; ?>
            <td><button class="btn js-view-product" type="button" data-product-id="<?= (int) $p['id'] ?>">View</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Stock deductions</h2>
    <table id="deductionsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Stock</th>
          <th>Qty</th>
          <th>Reason</th>
          <th>Deducted by</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deductions as $d): ?>
          <tr>
            <td><?= (int) $d['id'] ?></td>
            <td><?= htmlspecialchars((string) $d['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['stock_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['qty'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['reason'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['deducted_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $d['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canAddStocks): ?>
    <div class="modal-backdrop" id="addStockModal" aria-hidden="true">
      <div class="modal-card">
        <div class="modal-head">
          <h3 class="modal-title">Add stock entry</h3>
          <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form id="addStockForm" method="post" action="products.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="add_stock_entry">
          <input type="hidden" name="ajax" value="1">
          <div class="row">
            <?php if ($canViewAll): ?>
              <div>
                <label for="modal_dept_id">Department</label>
                <select id="modal_dept_id" name="dept_id" required>
                  <option value="">Select department</option>
                  <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <div>
              <label for="modal_stock_name">Stock name</label>
              <input id="modal_stock_name" name="stock_name" required maxlength="160">
            </div>
            <div>
              <label for="modal_unit">Unit</label>
              <input id="modal_unit" name="unit" maxlength="32" value="pcs">
            </div>
            <div>
              <label for="modal_qty">Qty</label>
              <input id="modal_qty" name="qty" type="number" min="0.01" step="0.01" required>
            </div>
            <div>
              <label for="modal_batching_date">Batching date</label>
              <input id="modal_batching_date" name="batching_date" type="datetime-local" required>
            </div>
            <div>
              <label for="modal_supplier_batch_no">Supplier batch no</label>
              <input id="modal_supplier_batch_no" name="supplier_batch_no" maxlength="120">
            </div>
            <div>
              <label for="modal_supplier_name">Supplier name</label>
              <input id="modal_supplier_name" name="supplier_name" maxlength="160">
            </div>
            <div>
              <label for="modal_supplier_code">Supplier code</label>
              <input id="modal_supplier_code" name="supplier_code" maxlength="64">
            </div>
            <div>
              <label for="modal_current_price">Price</label>
              <input id="modal_current_price" name="current_price" type="number" min="0" step="0.01" value="0">
            </div>
            <div style="display:flex;align-items:flex-end;">
              <button class="btn primary" type="submit" style="width:100%;">Save stock</button>
            </div>
          </div>
          <p id="addStockFeedback" class="feedback muted"></p>
        </form>
      </div>
    </div>

    <div class="modal-backdrop" id="editStockModal" aria-hidden="true">
      <div class="modal-card">
        <div class="modal-head">
          <h3 class="modal-title">Edit stock</h3>
          <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form id="editStockForm" method="post" action="products.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="edit_stock">
          <input type="hidden" name="ajax" value="1">
          <input type="hidden" name="stock_id" id="edit_stock_id">
          <div class="row">
            <div>
              <label for="edit_stock_name">Stock name</label>
              <input id="edit_stock_name" name="stock_name" required maxlength="160">
            </div>
            <div>
              <label for="edit_stock_unit">Unit</label>
              <input id="edit_stock_unit" name="unit" maxlength="32">
            </div>
            <div>
              <label for="edit_stock_price">Price</label>
              <input id="edit_stock_price" name="current_price" type="number" min="0" step="0.01" required>
            </div>
            <div>
              <label for="edit_stock_supplier_name">Supplier name</label>
              <input id="edit_stock_supplier_name" name="supplier_name" maxlength="160">
            </div>
            <div>
              <label for="edit_stock_supplier_code">Supplier code</label>
              <input id="edit_stock_supplier_code" name="supplier_code" maxlength="64">
            </div>
            <div style="display:flex;align-items:flex-end;">
              <button class="btn primary" type="submit" style="width:100%;">Save changes</button>
            </div>
          </div>
          <p id="editStockFeedback" class="feedback muted"></p>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($canManageProducts): ?>
    <div class="modal-backdrop" id="createProductModal" aria-hidden="true">
      <div class="modal-card">
        <div class="modal-head">
          <h3 class="modal-title">Create product</h3>
          <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <form id="createProductForm" method="post" action="products.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="create_product">
          <input type="hidden" name="ajax" value="1">
          <div class="row">
            <?php if ($canViewAll): ?>
              <div>
                <label for="modal_dept_id_create">Department</label>
                <select id="modal_dept_id_create" name="dept_id" required>
                  <option value="">Select department</option>
                  <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>
            <div>
              <label for="modal_name_create">Product name</label>
              <input id="modal_name_create" name="name" required maxlength="160">
            </div>
            <div>
              <label for="modal_size_create">Size (honey only)</label>
              <select id="modal_size_create" name="size">
                <option value="">— none —</option>
                <option value="liter">liter</option>
                <option value="350ml">350ml</option>
                <option value="250ml">250ml</option>
                <option value="gallon">gallon</option>
              </select>
            </div>
            <div>
              <label for="modal_variant_create">Variant (honey only)</label>
              <select id="modal_variant_create" name="variant">
                <option value="">— none —</option>
                <option value="light">light</option>
                <option value="amber">amber</option>
                <option value="dark">dark</option>
              </select>
            </div>
            <?php if ($canEditPricing): ?>
              <div>
                <label for="modal_selling_price_create">Selling price</label>
                <input id="modal_selling_price_create" name="selling_price" type="number" min="0" step="0.01" value="0">
              </div>
            <?php endif; ?>
            <div style="display:flex;align-items:flex-end;">
              <button class="btn primary" type="submit" style="width:100%;">Create product</button>
            </div>
          </div>
          <p id="createProductFeedback" class="feedback muted"></p>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <div class="modal-backdrop" id="productViewModal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-head">
        <h3 class="modal-title" id="productViewTitle">Product details</h3>
        <button type="button" class="modal-close" data-close-modal>&times;</button>
      </div>

      <div id="productViewSummary" class="card" style="margin-bottom:0.7rem;">
        <p class="muted" style="margin:0;">Loading...</p>
      </div>

      <div class="card" style="margin-bottom:0.7rem;">
        <h4 style="margin:0 0 0.45rem;">Product formula & live cost</h4>
        <table id="productFormulaTable">
          <thead>
            <tr>
              <th>Stock</th>
              <th>Qty / unit</th>
              <?php if ($showPricing): ?>
                <th>Baseline price</th>
                <th>Current price</th>
                <th>Line cost</th>
                <th>Vs baseline</th>
              <?php endif; ?>
              <?php if ($canManageProducts): ?>
                <th>Action</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody id="productFormulaBody"></tbody>
        </table>
      </div>

      <?php if ($canManageProducts): ?>
        <div class="card" style="margin-bottom:0.7rem;">
          <h4 style="margin:0 0 0.45rem;">Add formula row</h4>
          <form id="formulaAddForm" method="post" action="products.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="add_formula_row">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="product_id" id="formula_add_product_id">
            <div class="row">
              <div>
                <label for="formula_add_stock_id">Stock</label>
                <select id="formula_add_stock_id" name="stock_id" required>
                  <option value="">Select stock</option>
                  <?php foreach ($stocks as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars((string) $s['name'] . ' [' . $s['dept_name'] . ']', ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="formula_add_qty">Qty per unit</label>
                <input id="formula_add_qty" name="qty_per_unit" type="number" min="0.0001" step="0.0001" required>
              </div>
              <div style="display:flex;align-items:flex-end;">
                <button class="btn primary" type="submit" style="width:100%;">Add formula row</button>
              </div>
            </div>
          </form>

          <h4 style="margin:0.8rem 0 0.45rem;">Edit selected formula row</h4>
          <form id="formulaEditForm" method="post" action="products.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="update_formula_row">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="product_id" id="formula_edit_product_id">
            <input type="hidden" name="formula_id" id="formula_edit_id">
            <div class="row">
              <div>
                <label for="formula_edit_stock_id">Stock</label>
                <select id="formula_edit_stock_id" name="stock_id" required>
                  <option value="">Select stock</option>
                  <?php foreach ($stocks as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars((string) $s['name'] . ' [' . $s['dept_name'] . ']', ENT_QUOTES, 'UTF-8') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="formula_edit_qty">Qty per unit</label>
                <input id="formula_edit_qty" name="qty_per_unit" type="number" min="0.0001" step="0.0001" required>
              </div>
              <div style="display:flex;align-items:flex-end;">
                <button class="btn primary" type="submit" style="width:100%;">Save formula row</button>
              </div>
            </div>
          </form>
          <p id="formulaFeedback" class="feedback muted"></p>
        </div>
      <?php endif; ?>

      <?php if ($showPricing): ?>
        <div class="card" style="margin-bottom:0.7rem;">
          <h4 style="margin:0 0 0.45rem;">Cost history (this product)</h4>
          <table>
            <thead>
              <tr>
                <th>Recalculated at</th>
                <th>Old cost</th>
                <th>New cost</th>
                <th>Delta</th>
              </tr>
            </thead>
            <tbody id="productCostHistoryBody"></tbody>
          </table>
        </div>

        <div class="card" style="margin-bottom:0;">
          <h4 style="margin:0 0 0.45rem;">Stock price history (stocks in this product)</h4>
          <table>
            <thead>
              <tr>
                <th>Stock</th>
                <th>Changed at</th>
                <th>Old price</th>
                <th>New price</th>
                <th>Delta</th>
              </tr>
            </thead>
            <tbody id="stockHistoryBody"></tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  const CAN_MANAGE_PRODUCTS = <?= json_encode($canManageProducts) ?>;
  const SHOW_PRICING = <?= json_encode($showPricing) ?>;
  const CSRF = <?= json_encode($csrf, JSON_UNESCAPED_SLASHES) ?>;
  let activeProductId = 0;

  const stocksTable = $('#stocksTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });
  const productsTable = $('#productsTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });
  $('#deductionsTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });

  function setFeedback(el, msg, ok) {
    if (!el.length) return;
    el.text(msg || '');
    el.removeClass('ok err muted');
    el.addClass(ok ? 'ok' : 'err');
  }

  function closeModal($modal) {
    $modal.removeClass('open').attr('aria-hidden', 'true');
  }

  function openModal($modal) {
    $modal.addClass('open').attr('aria-hidden', 'false');
  }

  function invalidateDataTableRows() {
    stocksTable.rows().invalidate().draw(false);
    productsTable.rows().invalidate().draw(false);
  }

  function postAction(formData) {
    return fetch('products.php', {
      method: 'POST',
      body: formData,
      headers: { 'Accept': 'application/json' }
    }).then(r => r.json());
  }

  function formatMoney(v) {
    return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function trendPill(delta) {
    const val = Number(delta || 0);
    if (val > 0) return `<span class="pill up">+${formatMoney(val)}</span>`;
    if (val < 0) return `<span class="pill down">-${formatMoney(Math.abs(val))}</span>`;
    return '<span class="pill same">No change</span>';
  }

  function applyStockRow(row) {
    if (!row || !row.id) return;
    const $tr = $(`#stocksTable tbody tr[data-stock-id="${row.id}"]`);
    if ($tr.length) {
      $tr.find('.cell-stock-dept').text(row.dept_name || '');
      $tr.find('.cell-stock-name').text(row.name || '');
      $tr.find('.cell-stock-unit').text(row.unit || '');
      $tr.find('.cell-stock-price').text(formatMoney(row.current_price));
      const supplier = `${row.supplier_name || '—'} (${row.supplier_code || '—'})`;
      $tr.find('.cell-stock-supplier').text(supplier);
      $tr.find('.cell-stock-last').text(row.last_entry_at || '');
      const $btn = $tr.find('.js-edit-stock');
      $btn.attr('data-stock-name', row.name || '');
      $btn.attr('data-stock-unit', row.unit || '');
      $btn.attr('data-stock-price', row.current_price || 0);
      $btn.attr('data-stock-supplier-name', row.supplier_name || '');
      $btn.attr('data-stock-supplier-code', row.supplier_code || '');
    } else {
      window.location.reload();
      return;
    }
    invalidateDataTableRows();
  }

  function applyProductRow(row) {
    if (!row || !row.id) return;
    const $tr = $(`#productsTable tbody tr[data-product-id="${row.id}"]`);
    if ($tr.length) {
      $tr.find('.cell-product-name').text(row.name || '');
    } else {
      window.location.reload();
      return;
    }
    invalidateDataTableRows();
  }

  function renderProductDetail(detail) {
    if (!detail || !detail.product) return;
    const product = detail.product;
    const baseline = Number(product.baseline_cost || 0);
    const current = Number(product.current_cost || 0);
    const delta = current - baseline;

    $('#productViewTitle').text(`${product.name} — formula & live cost`);
    $('#productViewSummary').html(
      `<strong>${product.name}</strong> [${product.dept_name}]<br>` +
      `<span class="muted">Size: ${product.size || '—'} | Variant: ${product.variant || '—'}</span>` +
      (SHOW_PRICING ? `<div style="margin-top:0.35rem;">Baseline: <strong>${formatMoney(baseline)}</strong> | Current: <strong>${formatMoney(current)}</strong> | ${trendPill(delta)}</div>` : '')
    );

    const formulaRows = detail.formula_rows || [];
    const formulaHtml = formulaRows.map((r) => {
      const base = Number(r.price_at_creation || 0);
      const curr = Number(r.current_price || 0);
      const lineCurrent = Number(r.current_line_cost || 0);
      const d = Number(r.delta || 0);
      const actionHtml = CAN_MANAGE_PRODUCTS
        ? `<button type="button" class="btn js-formula-edit" data-formula-id="${r.id}" data-stock-id="${r.stock_id}" data-qty="${r.qty_per_unit}">Edit</button>
           <button type="button" class="btn js-formula-delete" data-formula-id="${r.id}">Delete</button>`
        : '';
      return `<tr>
        <td>${r.stock_name} <span class="muted">(${r.unit})</span></td>
        <td>${r.qty_per_unit}</td>
        ${SHOW_PRICING ? `<td>${formatMoney(base)}</td><td>${formatMoney(curr)}</td><td>${formatMoney(lineCurrent)}</td><td>${trendPill(d)}</td>` : ''}
        ${CAN_MANAGE_PRODUCTS ? `<td>${actionHtml}</td>` : ''}
      </tr>`;
    }).join('');
    $('#productFormulaBody').html(formulaHtml || '<tr><td colspan="7" class="muted">No formula rows yet.</td></tr>');

    if (CAN_MANAGE_PRODUCTS) {
      $('#formula_add_product_id').val(product.id);
      $('#formula_edit_product_id').val(product.id);
      $('#formula_edit_id').val('');
      $('#formula_edit_qty').val('');
      $('#formula_edit_stock_id').val('');
    }

    if (SHOW_PRICING) {
      const costHistory = detail.cost_history || [];
      const costHtml = costHistory.map((h) => {
        const oldCost = Number(h.old_cost || 0);
        const newCost = Number(h.new_cost || 0);
        return `<tr>
          <td>${h.recalculated_at || ''}</td>
          <td>${formatMoney(oldCost)}</td>
          <td>${formatMoney(newCost)}</td>
          <td>${trendPill(newCost - oldCost)}</td>
        </tr>`;
      }).join('');
      $('#productCostHistoryBody').html(costHtml || '<tr><td colspan="4" class="muted">No cost history yet.</td></tr>');

      const stockHistory = detail.stock_history || [];
      const stockHtml = stockHistory.map((h) => {
        const oldP = Number(h.old_price || 0);
        const newP = Number(h.new_price || 0);
        return `<tr>
          <td>${h.stock_name || ''}</td>
          <td>${h.changed_at || ''}</td>
          <td>${formatMoney(oldP)}</td>
          <td>${formatMoney(newP)}</td>
          <td>${trendPill(newP - oldP)}</td>
        </tr>`;
      }).join('');
      $('#stockHistoryBody').html(stockHtml || '<tr><td colspan="5" class="muted">No stock price history yet.</td></tr>');
    }
  }

  function loadProductDetail(productId) {
    const data = new FormData();
    data.append('csrf', CSRF);
    data.append('ajax', '1');
    data.append('action', 'fetch_product_view');
    data.append('product_id', String(productId));
    return postAction(data).then((res) => {
      if (!res.ok) throw new Error(res.error || 'Could not load product detail.');
      renderProductDetail(res.detail);
    });
  }

  $('[data-open-modal]').on('click', function () {
    const id = $(this).data('open-modal');
    openModal($('#' + id));
  });
  $('[data-close-modal]').on('click', function () {
    closeModal($(this).closest('.modal-backdrop'));
  });
  $('.modal-backdrop').on('click', function (e) {
    if (e.target === this) closeModal($(this));
  });
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('.modal-backdrop.open').each(function () { closeModal($(this)); });
    }
  });

  $('#addStockForm').on('submit', function (e) {
    e.preventDefault();
    const $feedback = $('#addStockFeedback');
    setFeedback($feedback, 'Saving...', true);
    postAction(new FormData(this))
      .then((res) => {
        if (!res.ok) throw new Error(res.error || 'Add stock failed.');
        setFeedback($feedback, res.message || 'Saved.', true);
        applyStockRow(res.stock || null);
        setTimeout(() => closeModal($('#addStockModal')), 350);
        this.reset();
      })
      .catch((err) => setFeedback($feedback, err.message, false));
  });

  $('#createProductForm').on('submit', function (e) {
    e.preventDefault();
    const $feedback = $('#createProductFeedback');
    setFeedback($feedback, 'Saving...', true);
    postAction(new FormData(this))
      .then((res) => {
        if (!res.ok) throw new Error(res.error || 'Create product failed.');
        setFeedback($feedback, res.message || 'Saved.', true);
        applyProductRow(res.product || null);
        setTimeout(() => closeModal($('#createProductModal')), 350);
        this.reset();
      })
      .catch((err) => setFeedback($feedback, err.message, false));
  });

  $(document).on('click', '.js-edit-stock', function () {
    $('#edit_stock_id').val($(this).data('stock-id'));
    $('#edit_stock_name').val($(this).data('stock-name'));
    $('#edit_stock_unit').val($(this).data('stock-unit'));
    $('#edit_stock_price').val($(this).data('stock-price'));
    $('#edit_stock_supplier_name').val($(this).data('stock-supplier-name'));
    $('#edit_stock_supplier_code').val($(this).data('stock-supplier-code'));
    $('#editStockFeedback').text('').removeClass('ok err').addClass('muted');
    openModal($('#editStockModal'));
  });

  $('#editStockForm').on('submit', function (e) {
    e.preventDefault();
    const $feedback = $('#editStockFeedback');
    setFeedback($feedback, 'Saving...', true);
    postAction(new FormData(this))
      .then((res) => {
        if (!res.ok) throw new Error(res.error || 'Edit stock failed.');
        setFeedback($feedback, res.message || 'Saved.', true);
        applyStockRow(res.stock || null);
        setTimeout(() => closeModal($('#editStockModal')), 350);
      })
      .catch((err) => setFeedback($feedback, err.message, false));
  });

  $(document).on('click', '.js-view-product', function () {
    activeProductId = Number($(this).data('product-id') || 0);
    if (activeProductId < 1) return;
    openModal($('#productViewModal'));
    $('#productViewSummary').html('<p class="muted" style="margin:0;">Loading...</p>');
    loadProductDetail(activeProductId).catch((err) => {
      $('#productViewSummary').html(`<p class="err" style="margin:0;">${err.message}</p>`);
    });
  });

  if (CAN_MANAGE_PRODUCTS) {
    $('#formulaAddForm').on('submit', function (e) {
      e.preventDefault();
      const $feedback = $('#formulaFeedback');
      setFeedback($feedback, 'Saving formula row...', true);
      postAction(new FormData(this))
        .then((res) => {
          if (!res.ok) throw new Error(res.error || 'Add formula failed.');
          setFeedback($feedback, 'Formula row added.', true);
          renderProductDetail(res.detail);
          invalidateDataTableRows();
          this.reset();
        })
        .catch((err) => setFeedback($feedback, err.message, false));
    });

    $(document).on('click', '.js-formula-edit', function () {
      $('#formula_edit_id').val($(this).data('formula-id'));
      $('#formula_edit_stock_id').val($(this).data('stock-id'));
      $('#formula_edit_qty').val($(this).data('qty'));
      $('#formulaFeedback').text('Editing selected formula row.').removeClass('err').addClass('muted');
    });

    $('#formulaEditForm').on('submit', function (e) {
      e.preventDefault();
      const $feedback = $('#formulaFeedback');
      if (!$('#formula_edit_id').val()) {
        setFeedback($feedback, 'Select a formula row first by clicking Edit.', false);
        return;
      }
      setFeedback($feedback, 'Updating formula row...', true);
      postAction(new FormData(this))
        .then((res) => {
          if (!res.ok) throw new Error(res.error || 'Update formula failed.');
          setFeedback($feedback, 'Formula row updated.', true);
          renderProductDetail(res.detail);
          invalidateDataTableRows();
        })
        .catch((err) => setFeedback($feedback, err.message, false));
    });

    $(document).on('click', '.js-formula-delete', function () {
      if (!activeProductId) return;
      const formulaId = Number($(this).data('formula-id') || 0);
      if (formulaId < 1) return;
      if (!window.confirm('Delete this formula row?')) return;
      const data = new FormData();
      data.append('csrf', CSRF);
      data.append('ajax', '1');
      data.append('action', 'delete_formula_row');
      data.append('product_id', String(activeProductId));
      data.append('formula_id', String(formulaId));
      const $feedback = $('#formulaFeedback');
      setFeedback($feedback, 'Deleting formula row...', true);
      postAction(data)
        .then((res) => {
          if (!res.ok) throw new Error(res.error || 'Delete formula failed.');
          setFeedback($feedback, 'Formula row deleted.', true);
          renderProductDetail(res.detail);
          invalidateDataTableRows();
        })
        .catch((err) => setFeedback($feedback, err.message, false));
    });
  }
});
  </script>
</body>
</html>
