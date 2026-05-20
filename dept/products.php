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
$canUpdateStockPrice = ims_is_executive($role);
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
                $priceVal = null;
                if ($currentPrice !== '') {
                    $priceVal = (float) $currentPrice;
                    if ($priceVal < 0) {
                        throw new RuntimeException('Price cannot be negative.');
                    }
                }
                if (strlen($unit) > 32) {
                    throw new RuntimeException('Unit is too long.');
                }
                if (strlen($supplierBatchNo) > 120) {
                    throw new RuntimeException('Supplier batch no is too long.');
                }

                $pdo->beginTransaction();
                $stStock = $pdo->prepare('SELECT id FROM stocks WHERE dept_id = ? AND name = ? LIMIT 1');
                $stStock->execute([$deptId, $stockName]);
                $stockId = (int) $stStock->fetchColumn();

                if ($stockId > 0) {
                    $upd = $pdo->prepare(
                        'UPDATE stocks
                         SET unit = ?, supplier_name = ?, supplier_code = ?, current_price = COALESCE(?, current_price)
                         WHERE id = ?'
                    );
                    $upd->execute([
                        $unit,
                        $supplierName === '' ? null : $supplierName,
                        $supplierCode === '' ? null : $supplierCode,
                        $priceVal,
                        $stockId,
                    ]);
                } else {
                    $insStock = $pdo->prepare(
                        'INSERT INTO stocks (name, dept_id, unit, current_price, supplier_name, supplier_code, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insStock->execute([
                        $stockName,
                        $deptId,
                        $unit,
                        $priceVal ?? 0,
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
                        'SELECT s.id, s.name, s.current_price, d.name AS dept_name
                         FROM stocks s
                         INNER JOIN departments d ON d.id = s.dept_id
                         WHERE s.id = ? LIMIT 1'
                    );
                    $stStockRow->execute([$stockId]);
                    $row = $stStockRow->fetch();
                    $ajaxResponse = [
                        'ok' => true,
                        'message' => $notice,
                        'stock' => [
                            'id' => (int) ($row['id'] ?? $stockId),
                            'name' => (string) ($row['name'] ?? $stockName),
                            'dept_name' => (string) ($row['dept_name'] ?? ''),
                            'current_price' => (float) ($row['current_price'] ?? ($priceVal ?? 0)),
                        ],
                    ];
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
                $notice = 'Product created.';
                if ($wantsJson) {
                    $productId = (int) $pdo->lastInsertId();
                    $stDeptName = $pdo->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
                    $stDeptName->execute([$deptId]);
                    $deptName = (string) ($stDeptName->fetchColumn() ?: '');
                    $ajaxResponse = [
                        'ok' => true,
                        'message' => $notice,
                        'product' => [
                            'id' => $productId,
                            'name' => $name,
                            'dept_name' => $deptName,
                        ],
                    ];
                }
            } elseif ($action === 'add_formula') {
                if (!$canManageProducts) {
                    throw new RuntimeException('You are not allowed to update formulas.');
                }
                $productId = (int) ($_POST['product_id'] ?? 0);
                $stockId = (int) ($_POST['stock_id'] ?? 0);
                $qtyRaw = trim((string) ($_POST['qty_per_unit'] ?? ''));

                if ($productId < 1 || $stockId < 1) {
                    throw new RuntimeException('Product and stock are required.');
                }
                if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
                    throw new RuntimeException('qty_per_unit must be > 0.');
                }
                $qtyPerUnit = (float) $qtyRaw;

                $sqlProduct = 'SELECT id, dept_id FROM products WHERE id = ?';
                $argsProduct = [$productId];
                if (!$canViewAll) {
                    $sqlProduct .= ' AND dept_id = ?';
                    $argsProduct[] = $userDeptId;
                }
                $stProduct = $pdo->prepare($sqlProduct . ' LIMIT 1');
                $stProduct->execute($argsProduct);
                $product = $stProduct->fetch();
                if (!$product) {
                    throw new RuntimeException('Product not found or out of your scope.');
                }

                $stStock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE id = ? AND dept_id = ? LIMIT 1');
                $stStock->execute([$stockId, (int) $product['dept_id']]);
                $stock = $stStock->fetch();
                if (!$stock) {
                    throw new RuntimeException('Stock not found in the same department as product.');
                }

                $priceAtCreation = (float) ($stock['current_price'] ?? 0);

                $pdo->beginTransaction();
                $insFormula = $pdo->prepare(
                    'INSERT INTO product_formula (product_id, stock_id, qty_per_unit, price_at_creation)
                     VALUES (?, ?, ?, ?)'
                );
                $insFormula->execute([$productId, $stockId, $qtyPerUnit, $priceAtCreation]);

                $stCost = $pdo->prepare('SELECT COALESCE(SUM(qty_per_unit * price_at_creation), 0) FROM product_formula WHERE product_id = ?');
                $stCost->execute([$productId]);
                $baseline = (float) $stCost->fetchColumn();

                $updProduct = $pdo->prepare('UPDATE products SET baseline_cost = ? WHERE id = ?');
                $updProduct->execute([$baseline, $productId]);
                $pdo->commit();
                $notice = 'Formula line added and baseline cost recalculated.';
                if ($wantsJson) {
                    $ajaxResponse = ['ok' => true, 'message' => $notice];
                }
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
                if ($wantsJson) {
                    $ajaxResponse = ['ok' => true, 'message' => $notice];
                }
            } elseif ($action === 'update_stock_price') {
                if (!$canUpdateStockPrice) {
                    throw new RuntimeException('Only executive can update stock prices.');
                }
                $stockId = (int) ($_POST['stock_id'] ?? 0);
                $newPriceRaw = trim((string) ($_POST['new_price'] ?? ''));
                if ($stockId < 1) {
                    throw new RuntimeException('Stock is required.');
                }
                if (!is_numeric($newPriceRaw) || (float) $newPriceRaw < 0) {
                    throw new RuntimeException('New price must be 0 or greater.');
                }
                $newPrice = round((float) $newPriceRaw, 2);

                $pdo->beginTransaction();
                $stStock = $pdo->prepare('SELECT id, current_price FROM stocks WHERE id = ? LIMIT 1 FOR UPDATE');
                $stStock->execute([$stockId]);
                $stock = $stStock->fetch();
                if (!$stock) {
                    throw new RuntimeException('Stock not found.');
                }
                $oldPrice = round((float) $stock['current_price'], 2);
                if ($oldPrice === $newPrice) {
                    $pdo->rollBack();
                    $notice = 'Price is unchanged; no update needed.';
                } else {
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

                    if ($affectedIds !== []) {
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

                    $pdo->commit();
                    $notice = 'Stock price updated; cost histories recalculated.';
                }
                if ($wantsJson) {
                    $ajaxResponse = ['ok' => true, 'message' => $notice];
                }
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
$priceHistory = [];
$costHistory = [];

try {
    $stDept = $pdo->query('SELECT id, name FROM departments ORDER BY name ASC');
    $departments = $stDept ? $stDept->fetchAll() : [];

    $sqlStocks = 'SELECT s.id, s.name, s.unit, s.current_price, s.dept_id, d.name AS dept_name
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

    $formulaExpr = $showPricing
        ? 'GROUP_CONCAT(CONCAT(s.name, " x ", f.qty_per_unit, " @ ", f.price_at_creation) SEPARATOR " | ")'
        : 'GROUP_CONCAT(CONCAT(s.name, " x ", f.qty_per_unit) SEPARATOR " | ")';

    $sqlProducts = 'SELECT
                      p.id, p.name, p.dept_id, p.size, p.variant, p.selling_price, p.baseline_cost, p.created_at,
                      d.name AS dept_name, u.name AS created_by_name,
                      COALESCE(SUM(f.qty_per_unit * s.current_price), 0) AS current_cost,
                      ' . $formulaExpr . ' AS formula_lines
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

    if ($showPricing) {
        $stPriceHist = $pdo->query(
            'SELECT sph.id, sph.old_price, sph.new_price, sph.changed_at,
                    s.name AS stock_name, d.name AS dept_name, u.name AS changed_by_name
             FROM stock_price_history sph
             INNER JOIN stocks s ON s.id = sph.stock_id
             INNER JOIN departments d ON d.id = s.dept_id
             INNER JOIN users u ON u.id = sph.changed_by
             ORDER BY sph.changed_at DESC, sph.id DESC
             LIMIT 500'
        );
        $priceHistory = $stPriceHist ? $stPriceHist->fetchAll() : [];

        $stCostHist = $pdo->query(
            'SELECT pch.id, pch.old_cost, pch.new_cost, pch.recalculated_at,
                    p.name AS product_name, d.name AS dept_name, s.name AS trigger_stock_name
             FROM product_cost_history pch
             INNER JOIN products p ON p.id = pch.product_id
             INNER JOIN departments d ON d.id = p.dept_id
             INNER JOIN stocks s ON s.id = pch.triggered_by_stock_id
             ORDER BY pch.recalculated_at DESC, pch.id DESC
             LIMIT 500'
        );
        $costHistory = $stCostHist ? $stCostHist->fetchAll() : [];
    }
} catch (Throwable $e) {
    $error = $error !== '' ? $error : 'Could not load products/deductions. Run phase 3 and phase 4 migrations.';
}

$pageTitle = 'Products & Deductions';
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
    @media (max-width:980px){ .row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px){ .row { grid-template-columns:1fr; } }
    label { display:block; font-size:0.78rem; color:var(--muted); margin:0.25rem 0 0.15rem; }
    input, select, textarea { width:100%; padding:0.5rem 0.55rem; border:1px solid var(--border); border-radius:8px; background:#fffdfa; }
    textarea { min-height:72px; resize:vertical; }
    .btn { border:1px solid var(--border); border-radius:9px; background:#fff9ef; color:var(--text); padding:0.45rem 0.7rem; text-decoration:none; font-weight:600; cursor:pointer; }
    .btn.primary { background:var(--accent); border-color:var(--accent); color:#111; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .muted { color:var(--muted); }
    .err { color:var(--danger); margin:0 0 0.55rem; font-size:0.9rem; }
    .ok { color:var(--ok); margin:0 0 0.55rem; font-size:0.9rem; }
    .toolbar { display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.6rem; }
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.42); display:none; align-items:center; justify-content:center; z-index:1000; padding:1rem; }
    .modal-backdrop.open { display:flex; }
    .modal-card { width:min(880px, 100%); max-height:92vh; overflow:auto; background:#fff; border:1px solid var(--border); border-radius:12px; padding:0.9rem; }
    .modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }
    .modal-title { margin:0; font-size:1.05rem; }
    .modal-close { border:1px solid var(--border); background:#fff; border-radius:8px; width:34px; height:34px; cursor:pointer; }
    .feedback { margin:0.4rem 0 0; font-size:0.9rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1 style="margin:0 0 0.2rem;font-size:1.25rem;">Products & Stock Deductions</h1>
    <p class="muted" style="margin:0 0 0.55rem;">
      Logged in as <strong><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
      (<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>)
    </p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="ok"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
      <a class="btn" href="../<?= htmlspecialchars(ims_redirect_dashboard_by_role($role), ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a>
      <a class="btn" href="stock_entries.php">Stock entries</a>
      <a class="btn" href="beginning_inventory.php">Beginning inventory</a>
      <a class="btn" href="../auth/logout.php">Logout</a>
    </div>
  </div>

  <?php if ($canManageProducts): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Quick actions</h2>
      <div class="toolbar">
        <?php if ($canAddStocks): ?>
          <button class="btn primary" type="button" data-open-modal="addStockModal">Add stock (modal)</button>
        <?php endif; ?>
        <button class="btn primary" type="button" data-open-modal="createProductModal">Create product (modal)</button>
      </div>
      <p class="muted" style="margin:0;">Forms are now modal-based for cleaner page flow. Successful modal submit updates dropdowns immediately.</p>
    </div>

    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Add product formula line</h2>
      <form method="post" action="products.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="add_formula">
        <div class="row">
          <div>
            <label for="product_id_formula">Product</label>
            <select id="product_id_formula" name="product_id" required>
              <option value="">Select product</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= (int) $p['id'] ?>">
                  <?= htmlspecialchars((string) $p['name'] . ' [' . (string) $p['dept_name'] . ']', ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="stock_id_formula">Stock</label>
            <select id="stock_id_formula" name="stock_id" required>
              <option value="">Select stock</option>
              <?php foreach ($stocks as $s): ?>
                <option value="<?= (int) $s['id'] ?>">
                  <?= htmlspecialchars(
                      (string) $s['name'] . ' [' . (string) $s['dept_name'] . ']' . ($showPricing ? (' @ ' . number_format((float) $s['current_price'], 2)) : ''),
                      ENT_QUOTES,
                      'UTF-8'
                  ) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="qty_per_unit_formula">Qty per unit</label>
            <input id="qty_per_unit_formula" name="qty_per_unit" type="number" min="0.0001" step="0.0001" required>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn primary" type="submit" style="width:100%;">Add formula line</button>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($canUpdateStockPrice): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Update stock price (Executive only)</h2>
      <form method="post" action="products.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="update_stock_price">
        <div class="row">
          <div>
            <label for="stock_id_price">Stock</label>
            <select id="stock_id_price" name="stock_id" required>
              <option value="">Select stock</option>
              <?php foreach ($stocks as $s): ?>
                <option value="<?= (int) $s['id'] ?>">
                  <?= htmlspecialchars((string) $s['name'] . ' [' . (string) $s['dept_name'] . '] current @ ' . number_format((float) $s['current_price'], 2), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="new_price">New price</label>
            <input id="new_price" name="new_price" type="number" min="0" step="0.01" required>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn primary" type="submit" style="width:100%;">Update price + recalc costs</button>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Record stock deduction</h2>
    <form method="post" action="products.php">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="add_deduction">
      <div class="row">
        <?php if ($canViewAll): ?>
          <div>
            <label for="dept_id_deduction">Department</label>
            <select id="dept_id_deduction" name="dept_id" required>
              <option value="">Select department</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars((string) $d['name'], ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div>
          <label for="stock_id_deduction">Stock</label>
          <select id="stock_id_deduction" name="stock_id" required>
            <option value="">Select stock</option>
            <?php foreach ($stocks as $s): ?>
              <option value="<?= (int) $s['id'] ?>">
                <?= htmlspecialchars((string) $s['name'] . ' [' . (string) $s['dept_name'] . ']', ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="qty_deduction">Qty to deduct</label>
          <input id="qty_deduction" name="qty" type="number" min="0.01" step="0.01" required>
        </div>
        <div>
          <label for="reason_deduction">Reason</label>
          <textarea id="reason_deduction" name="reason" required></textarea>
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button class="btn primary" type="submit" style="width:100%;">Save deduction</button>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Products</h2>
    <table id="productsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Department</th>
          <th>Product</th>
          <?php if ($showPricing): ?>
            <th>Selling</th>
            <th>Baseline Cost</th>
            <th>Current Cost</th>
            <th>Cost trend</th>
          <?php endif; ?>
          <th>Formula</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><?= (int) $p['id'] ?></td>
            <td><?= htmlspecialchars((string) $p['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <strong><?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
              <span class="muted">size: <?= htmlspecialchars((string) ($p['size'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> | variant: <?= htmlspecialchars((string) ($p['variant'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <?php if ($showPricing): ?>
              <td><?= number_format((float) $p['selling_price'], 2) ?></td>
              <td><?= number_format((float) $p['baseline_cost'], 2) ?></td>
              <td><?= number_format((float) $p['current_cost'], 2) ?></td>
              <td>
                <?php
                  $baseline = round((float) $p['baseline_cost'], 2);
                  $current = round((float) $p['current_cost'], 2);
                  $cls = 'muted';
                  $label = 'same';
                  if ($current < $baseline) { $cls = 'ok'; $label = 'lower'; }
                  elseif ($current > $baseline) { $cls = 'err'; $label = 'higher'; }
                ?>
                <span class="<?= htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') ?>" style="font-weight:600;"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
              </td>
            <?php endif; ?>
            <td><?= htmlspecialchars((string) ($p['formula_lines'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $p['created_by_name'], ENT_QUOTES, 'UTF-8') ?><br><span class="muted"><?= htmlspecialchars((string) $p['created_at'], ENT_QUOTES, 'UTF-8') ?></span></td>
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

  <?php if ($showPricing): ?>
    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Stock price history</h2>
      <table id="stockPriceHistoryTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Department</th>
            <th>Stock</th>
            <th>Old price</th>
            <th>New price</th>
            <th>Changed by</th>
            <th>Changed at</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($priceHistory as $h): ?>
            <tr>
              <td><?= (int) $h['id'] ?></td>
              <td><?= htmlspecialchars((string) $h['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $h['stock_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= number_format((float) $h['old_price'], 2) ?></td>
              <td><?= number_format((float) $h['new_price'], 2) ?></td>
              <td><?= htmlspecialchars((string) $h['changed_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $h['changed_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2 style="margin:0 0 0.45rem;font-size:1.05rem;">Product cost history</h2>
      <table id="productCostHistoryTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Department</th>
            <th>Product</th>
            <th>Old cost</th>
            <th>New cost</th>
            <th>Triggered by stock</th>
            <th>Recalculated at</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($costHistory as $h): ?>
            <tr>
              <td><?= (int) $h['id'] ?></td>
              <td><?= htmlspecialchars((string) $h['dept_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $h['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= number_format((float) $h['old_cost'], 2) ?></td>
              <td><?= number_format((float) $h['new_cost'], 2) ?></td>
              <td><?= htmlspecialchars((string) $h['trigger_stock_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $h['recalculated_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

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
              <input id="modal_current_price" name="current_price" type="number" min="0" step="0.01">
            </div>
            <div style="display:flex;align-items:flex-end;">
              <button class="btn primary" type="submit" style="width:100%;">Save stock</button>
            </div>
          </div>
          <p id="addStockFeedback" class="feedback muted"></p>
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

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script>
$(function () {
  $('#productsTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });
  $('#deductionsTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });
  if ($('#stockPriceHistoryTable').length) {
    $('#stockPriceHistoryTable').DataTable({ pageLength: 15, order: [[0, 'desc']] });
  }
  if ($('#productCostHistoryTable').length) {
    $('#productCostHistoryTable').DataTable({ pageLength: 15, order: [[0, 'desc']] });
  }

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
    if (e.key === 'Escape') $('.modal-backdrop.open').each(function () { closeModal($(this)); });
  });

  $('#createProductForm').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $feedback = $('#createProductFeedback');
    setFeedback($feedback, 'Saving...', true);
    fetch($form.attr('action'), {
      method: 'POST',
      body: new FormData($form[0]),
      headers: { 'Accept': 'application/json' }
    })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) throw new Error(data.error || 'Create product failed.');
        setFeedback($feedback, data.message || 'Product created.', true);
        if (data.product && data.product.id) {
          const label = `${data.product.name} [${data.product.dept_name}]`;
          $('#product_id_formula').append(
            $('<option>', { value: String(data.product.id), text: label, selected: true })
          );
        }
        setTimeout(() => closeModal($('#createProductModal')), 450);
        $form[0].reset();
      })
      .catch(err => setFeedback($feedback, err.message, false));
  });

  $('#addStockForm').on('submit', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $feedback = $('#addStockFeedback');
    setFeedback($feedback, 'Saving...', true);
    fetch($form.attr('action'), {
      method: 'POST',
      body: new FormData($form[0]),
      headers: { 'Accept': 'application/json' }
    })
      .then(r => r.json())
      .then(data => {
        if (!data.ok) throw new Error(data.error || 'Add stock failed.');
        setFeedback($feedback, data.message || 'Stock saved.', true);
        if (data.stock && data.stock.id) {
          const baseLabel = `${data.stock.name} [${data.stock.dept_name}]`;
          const pricingLabel = `${baseLabel} @ ${Number(data.stock.current_price || 0).toFixed(2)}`;
          const $optFormula = $('<option>', { value: String(data.stock.id), text: pricingLabel });
          const $optDeduction = $('<option>', { value: String(data.stock.id), text: baseLabel });
          const $optPrice = $('<option>', { value: String(data.stock.id), text: `${baseLabel} current @ ${Number(data.stock.current_price || 0).toFixed(2)}` });

          $('#stock_id_formula').append($optFormula.clone());
          $('#stock_id_deduction').append($optDeduction.clone());
          if ($('#stock_id_price').length) $('#stock_id_price').append($optPrice.clone());
        }
        setTimeout(() => closeModal($('#addStockModal')), 450);
        $form[0].reset();
      })
      .catch(err => setFeedback($feedback, err.message, false));
  });
});
  </script>
</body>
</html>
