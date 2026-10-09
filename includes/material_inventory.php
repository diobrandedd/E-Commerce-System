<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/catalog_bootstrap.php';
require_once __DIR__ . '/permissions.php';

const MATERIAL_CATEGORIES = ['raw_material', 'packaging', 'supply', 'costing_only'];

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_view(array $user): bool
{
    return ims_user_can($user, 'packaging.view') || ims_user_can($user, 'supplies.view');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_catalog(array $user): bool
{
    return ims_user_can($user, 'packaging.catalog');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_adjust(array $user): bool
{
    return ims_user_can($user, 'packaging.adjust');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_request(array $user): bool
{
    return ims_user_can($user, 'packaging.request') || ims_user_can($user, 'supplies.request');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_request_packaging(array $user): bool
{
    return ims_user_can($user, 'packaging.request');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_stock_in_packaging(array $user): bool
{
    require_once __DIR__ . '/auth_check.php';

    return ims_user_can($user, 'packaging.stock_in')
        && ims_is_executive((string) ($user['role'] ?? ''));
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_deduct_packaging(array $user): bool
{
    return ims_user_can($user, 'packaging.deduct');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_set_packaging_on_hand(array $user): bool
{
    return ims_user_can($user, 'packaging.set_on_hand');
}

/** @deprecated use material_inventory_role_can_set_packaging_on_hand */
function material_inventory_role_can_set_packaging(array $user): bool
{
    return material_inventory_role_can_set_packaging_on_hand($user);
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_stock_in_supply(array $user): bool
{
    return ims_user_can($user, 'supplies.stock_in');
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_request_supply(array $user): bool
{
    return material_inventory_role_can_issue_supply($user);
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_issue_supply(array $user): bool
{
    return ims_user_can($user, 'supplies.issue');
}

/** @return array{can_stock_in: bool, can_issue_request: bool, can_set_on_hand: bool, can_create_catalog: bool, can_view_extended_log: bool} */
function material_supply_detail_permissions(array $user): array
{
    return [
        'can_stock_in' => material_inventory_role_can_stock_in_supply($user),
        'can_issue_request' => material_inventory_role_can_issue_supply($user),
        'can_set_on_hand' => ims_user_can($user, 'packaging.set_on_hand'),
        'can_create_catalog' => material_inventory_role_can_create_supply_catalog($user),
        'can_view_extended_log' => ims_user_can($user, 'packaging.set_on_hand'),
    ];
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function material_inventory_role_can_create_supply_catalog(array $user): bool
{
    return ims_user_can($user, 'supplies.catalog');
}

function material_supply_canonical_dept_id(PDO $pdo): int
{
    $st = $pdo->query("SELECT id FROM departments WHERE name = 'csr' LIMIT 1");
    $id = $st ? $st->fetchColumn() : false;
    if ($id !== false && (int) $id > 0) {
        return (int) $id;
    }
    $st = $pdo->query('SELECT id FROM departments ORDER BY id ASC LIMIT 1');
    $id = $st ? $st->fetchColumn() : false;

    return $id !== false ? (int) $id : 0;
}

function material_inventory_has_columns(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'category'"
        );
        $cache = ((int) $st->fetchColumn()) > 0;
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

function material_inventory_has_pool_columns(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'pool_yield_qty'"
        );
        $cache = ((int) $st->fetchColumn()) > 0;
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

function material_inventory_has_barcode_column(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stocks' AND COLUMN_NAME = 'barcode'"
        );
        $cache = ((int) $st->fetchColumn()) > 0;
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

function material_packaging_barcode_for_id(int $stockId): string
{
    return 'PKG' . str_pad((string) max(0, $stockId), 6, '0', STR_PAD_LEFT);
}

/**
 * Ensure a packaging stock has a permanent Code128-safe barcode.
 */
function material_packaging_ensure_barcode(PDO $pdo, int $stockId): string
{
    if ($stockId < 1 || !material_inventory_has_barcode_column($pdo)) {
        return material_packaging_barcode_for_id($stockId);
    }
    $st = $pdo->prepare('SELECT barcode, category FROM stocks WHERE id = ? LIMIT 1');
    $st->execute([$stockId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return material_packaging_barcode_for_id($stockId);
    }
    $existing = trim((string) ($row['barcode'] ?? ''));
    if ($existing !== '') {
        return $existing;
    }
    if ((string) ($row['category'] ?? '') !== 'packaging') {
        return material_packaging_barcode_for_id($stockId);
    }
    $code = material_packaging_barcode_for_id($stockId);
    try {
        $pdo->prepare('UPDATE stocks SET barcode = ? WHERE id = ? AND (barcode IS NULL OR barcode = "")')
            ->execute([$code, $stockId]);
    } catch (Throwable) {
        // Unique collision: leave lookup to caller / next ensure.
    }
    $again = $pdo->prepare('SELECT barcode FROM stocks WHERE id = ? LIMIT 1');
    $again->execute([$stockId]);
    $saved = trim((string) $again->fetchColumn());

    return $saved !== '' ? $saved : $code;
}

/** Latest purchase lot qty for capacity % (supplier_batch_no LIKE 'lot:%'). */
function material_packaging_last_lot_qty(PDO $pdo, int $stockId): ?float
{
    if ($stockId < 1) {
        return null;
    }
    $st = $pdo->prepare(
        "SELECT qty FROM stock_entries
         WHERE stock_id = ? AND supplier_batch_no LIKE 'lot:%' AND qty > 0
         ORDER BY created_at DESC, id DESC
         LIMIT 1"
    );
    $st->execute([$stockId]);
    $qty = $st->fetchColumn();
    if ($qty === false) {
        return null;
    }
    $n = (float) $qty;

    return $n > 0 ? round($n, 2) : null;
}

/**
 * @param list<int> $stockIds
 * @return array<int, float>
 */
function material_packaging_last_lot_qty_map(PDO $pdo, array $stockIds): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $stockIds), static fn (int $id): bool => $id > 0)));
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare(
        "SELECT se.stock_id, se.qty
         FROM stock_entries se
         INNER JOIN (
             SELECT stock_id, MAX(CONCAT(LPAD(UNIX_TIMESTAMP(created_at), 12, '0'), LPAD(id, 12, '0'))) AS mx
             FROM stock_entries
             WHERE stock_id IN ({$placeholders})
               AND supplier_batch_no LIKE 'lot:%'
               AND qty > 0
             GROUP BY stock_id
         ) latest ON latest.stock_id = se.stock_id
           AND CONCAT(LPAD(UNIX_TIMESTAMP(se.created_at), 12, '0'), LPAD(se.id, 12, '0')) = latest.mx
         WHERE se.supplier_batch_no LIKE 'lot:%'"
    );
    $st->execute($ids);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $sid = (int) $row['stock_id'];
        $qty = round((float) $row['qty'], 2);
        if ($qty > 0) {
            $out[$sid] = $qty;
        }
    }

    return $out;
}

/**
 * @param array<string, mixed> $row
 * @param float|null $lastLotQty optional preloaded last lot qty
 */
function material_inventory_enrich_packaging_row(array $row, ?float $lastLotQty = null): array
{
    $row['on_hand'] = round((float) ($row['on_hand'] ?? 0), 2);
    $row['current_price'] = round((float) ($row['current_price'] ?? 0), 2);
    $row['can_adjust'] = false;
    $row['can_set_packaging'] = (string) ($row['category'] ?? '') === 'packaging';
    $row['can_stock_in'] = in_array((string) ($row['category'] ?? ''), ['packaging', 'supply'], true);
    $productionDeduct = (int) ($row['production_deduct'] ?? 1);
    $row['production_deduct'] = $productionDeduct;
    $row['is_pool_packaging'] = (string) ($row['category'] ?? '') === 'packaging' && $productionDeduct === 0;
    if ($row['is_pool_packaging']) {
        $row['pool_yield_qty'] = $row['pool_yield_qty'] !== null ? (float) $row['pool_yield_qty'] : null;
        $row['pool_yield_unit'] = $row['pool_yield_unit'] ?? null;
        $row['pool_estimated_remaining'] = catalog_pool_estimated_remaining($row);
    } else {
        $row['pool_yield_qty'] = null;
        $row['pool_yield_unit'] = null;
        $row['pool_estimated_remaining'] = null;
    }

    $isPackaging = (string) ($row['category'] ?? '') === 'packaging';
    if ($isPackaging) {
        $barcode = trim((string) ($row['barcode'] ?? ''));
        if ($barcode === '' && isset($row['id'])) {
            $barcode = material_packaging_barcode_for_id((int) $row['id']);
        }
        $row['barcode'] = $barcode !== '' ? $barcode : null;
        $lotQty = $lastLotQty;
        $row['last_lot_qty'] = $lotQty !== null && $lotQty > 0 ? round($lotQty, 2) : null;
        if ($row['last_lot_qty'] !== null && $row['last_lot_qty'] > 0) {
            $row['level_pct'] = round(max(0.0, min(100.0, ($row['on_hand'] / $row['last_lot_qty']) * 100.0)), 1);
        } else {
            $row['level_pct'] = null;
        }
    } else {
        $row['barcode'] = null;
        $row['last_lot_qty'] = null;
        $row['level_pct'] = null;
    }

    return $row;
}

/** @return array<string, mixed>|null */
function material_inventory_fetch_stock(PDO $pdo, int $stockId, bool $forUpdate = false): ?array
{
    $sql = 'SELECT s.*, d.name AS dept_name
            FROM stocks s
            INNER JOIN departments d ON d.id = s.dept_id
            WHERE s.id = ?
            LIMIT 1';
    if ($forUpdate) {
        $sql = str_replace('LIMIT 1', 'LIMIT 1 FOR UPDATE', $sql);
    }
    $st = $pdo->prepare($sql);
    $st->execute([$stockId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return list<array<string, mixed>> */
function material_inventory_list(PDO $pdo, ?string $category = null, bool $activeOnly = true): array
{
    if (!material_inventory_has_columns($pdo)) {
        return [];
    }
    $where = ['s.category IN (\'raw_material\', \'packaging\', \'supply\')'];
    $params = [];
    if ($category !== null && $category !== '' && in_array($category, MATERIAL_CATEGORIES, true)) {
        $where = ['s.category = ?'];
        $params[] = $category;
    }
    if ($activeOnly) {
        $where[] = 's.is_active = 1';
    }
    $poolCols = material_inventory_has_pool_columns($pdo)
        ? ', s.production_deduct, s.pool_yield_qty, s.pool_yield_unit'
        : '';
    $barcodeCol = material_inventory_has_barcode_column($pdo) ? ', s.barcode' : '';
    $sql = 'SELECT s.id, s.name, s.dept_id, s.unit, s.category, s.on_hand, s.is_active,
                   s.current_price, s.supplier_id, s.supplier_name, s.supplier_code'
        . $poolCols . $barcodeCol . ',
                   d.name AS dept_name,
                   (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
            FROM stocks s
            INNER JOIN departments d ON d.id = s.dept_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY s.category ASC, s.name ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $packagingIds = [];
    foreach ($rows as $row) {
        if ((string) ($row['category'] ?? '') === 'packaging') {
            $packagingIds[] = (int) $row['id'];
        }
    }
    $lotMap = material_packaging_last_lot_qty_map($pdo, $packagingIds);
    foreach ($rows as &$row) {
        $sid = (int) ($row['id'] ?? 0);
        $lotQty = $lotMap[$sid] ?? null;
        if (
            (string) ($row['category'] ?? '') === 'packaging'
            && material_inventory_has_barcode_column($pdo)
            && trim((string) ($row['barcode'] ?? '')) === ''
        ) {
            $row['barcode'] = material_packaging_ensure_barcode($pdo, $sid);
        }
        $row = material_inventory_enrich_packaging_row($row, $lotQty);
    }

    return $rows;
}

/**
 * @return array<string, mixed>
 */
function material_inventory_create(
    PDO $pdo,
    int $userId,
    string $name,
    int $deptId,
    string $category,
    string $unit = 'pcs'
): array {
    if (!material_inventory_has_columns($pdo)) {
        throw new RuntimeException('Inventory arrangement migration not applied.');
    }
    if (!in_array($category, ['packaging', 'supply'], true)) {
        throw new RuntimeException('Catalog items must be packaging or supply.');
    }
    $name = trim($name);
    $unit = trim($unit) !== '' ? trim($unit) : 'pcs';
    if ($name === '') {
        throw new RuntimeException('Name is required.');
    }
    // Packaging (shown as Supplies in SPA) and supply catalogs are org-wide — dept is internal only.
    $deptId = material_supply_canonical_dept_id($pdo);
    if ($deptId < 1) {
        throw new RuntimeException('No department available for supplies catalog.');
    }
    $dup = $pdo->prepare(
        'SELECT id FROM stocks WHERE name = ? AND category = ? AND supplier_id IS NULL LIMIT 1'
    );
    $dup->execute([$name, $category]);
    if ($dup->fetch()) {
        throw new RuntimeException(
            $category === 'supply'
                ? 'A supply item with this name already exists.'
                : 'A stock with this name already exists.'
        );
    }
    $ins = $pdo->prepare(
        'INSERT INTO stocks (name, dept_id, unit, category, on_hand, is_active, current_price, created_by)
         VALUES (?, ?, ?, ?, 0, 1, 0, ?)'
    );
    $ins->execute([$name, $deptId, $unit, $category, $userId]);
    $id = (int) $pdo->lastInsertId();
    if ($category === 'packaging') {
        material_packaging_ensure_barcode($pdo, $id);
    }
    $row = material_inventory_fetch_stock($pdo, $id);
    if ($row === null) {
        throw new RuntimeException('Could not create stock.');
    }

    return $row;
}

function material_inventory_update(
    PDO $pdo,
    int $stockId,
    ?string $name = null,
    ?string $unit = null,
    ?bool $isActive = null
): array {
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if (!in_array((string) $row['category'], ['packaging', 'supply'], true)) {
        throw new RuntimeException('Only packaging and supply catalog items can be edited.');
    }
    $fields = [];
    $params = [];
    if ($name !== null) {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Name cannot be empty.');
        }
        $fields[] = 'name = ?';
        $params[] = $name;
    }
    if ($unit !== null) {
        $unit = trim($unit);
        if ($unit === '') {
            throw new RuntimeException('Unit cannot be empty.');
        }
        $fields[] = 'unit = ?';
        $params[] = $unit;
    }
    if ($isActive !== null) {
        $fields[] = 'is_active = ?';
        $params[] = $isActive ? 1 : 0;
    }
    if ($fields === []) {
        return $row;
    }
    $params[] = $stockId;
    $pdo->prepare('UPDATE stocks SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    $updated = material_inventory_fetch_stock($pdo, $stockId);
    if ($updated === null) {
        throw new RuntimeException('Stock not found after update.');
    }

    return $updated;
}

/**
 * @return array{stock_id: int, on_hand: float, entry_id: int}
 */
function material_inventory_adjust(
    PDO $pdo,
    int $stockId,
    string $action,
    float $amount,
    int $userId,
    ?string $reason = null
): array {
    if (!in_array($action, ['add', 'deduct'], true)) {
        throw new RuntimeException('Invalid action.');
    }
    if ($amount <= 0) {
        throw new RuntimeException('Amount must be positive.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'supply') {
        throw new RuntimeException('Only supply items support add/deduct adjustments. Use set values for packaging.');
    }
    $current = round((float) ($row['on_hand'] ?? 0), 2);
    $delta = $action === 'add' ? $amount : -$amount;
    $next = round($current + $delta, 2);
    if ($next < 0) {
        throw new RuntimeException('Insufficient on-hand quantity.');
    }
    $signedQty = round($delta, 2);
    $note = $reason !== null && trim($reason) !== '' ? trim($reason) : ($action === 'add' ? 'Material add' : 'Material deduct');
    $batching = date('Y-m-d H:i:s');

    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        $signedQty,
        $batching,
        substr($note, 0, 120),
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    return ['stock_id' => $stockId, 'on_hand' => $next, 'entry_id' => $entryId];
}

/**
 * Set absolute unit price and on-hand for packaging catalog rows (executive / HR).
 *
 * @return array{stock_id: int, on_hand: float, current_price: float, entry_id: ?int}
 */
function material_packaging_set_values(
    PDO $pdo,
    int $stockId,
    float $price,
    float $onHand,
    int $userId,
    ?string $note = null
): array {
    if ($price < 0) {
        throw new RuntimeException('Price must be 0 or greater.');
    }
    if ($onHand < 0) {
        throw new RuntimeException('On-hand quantity cannot be negative.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can be updated this way.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldPrice = round((float) ($row['current_price'] ?? 0), 2);
    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $newPrice = round($price, 2);
    $newOnHand = round($onHand, 2);
    $entryId = null;

    if ($newOnHand !== $oldOnHand) {
        $delta = round($newOnHand - $oldOnHand, 2);
        $auditNote = $note !== null && trim($note) !== '' ? trim($note) : 'Set on-hand balance';
        $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$newOnHand, $stockId]);
        $ins = $pdo->prepare(
            'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $stockId,
            (int) $row['dept_id'],
            $delta,
            date('Y-m-d H:i:s'),
            substr('set_balance: ' . $auditNote, 0, 120),
            $userId,
        ]);
        $entryId = (int) $pdo->lastInsertId();
    }

    if ($newPrice !== $oldPrice) {
        if (!function_exists('api_costing_apply_price_change')) {
            require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';
        }
        api_costing_apply_price_change($pdo, $stockId, $oldPrice, $newPrice, $userId);
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'current_price' => $newPrice,
        'entry_id' => $entryId,
    ];
}

/**
 * Update rough yield estimate for pool-only packaging (executive).
 *
 * @return array{stock_id: int, pool_yield_qty: float, pool_yield_unit: ?string}
 */
function material_packaging_set_pool_yield(
    PDO $pdo,
    int $stockId,
    float $poolYieldQty,
    ?string $poolYieldUnit,
    int $userId
): array {
    if (!material_inventory_has_pool_columns($pdo)) {
        throw new RuntimeException('Pool yield columns missing. Run migration_phase27_packaging_bootstrap.sql.');
    }
    if ($poolYieldQty <= 0) {
        throw new RuntimeException('Yield estimate must be greater than zero.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials support yield estimates.');
    }
    if ((int) ($row['production_deduct'] ?? 1) !== 0) {
        throw new RuntimeException('Yield estimates apply to pool-only packaging only.');
    }
    $unit = $poolYieldUnit !== null && trim($poolYieldUnit) !== '' ? trim($poolYieldUnit) : ($row['pool_yield_unit'] ?? null);
    $yield = round($poolYieldQty, 4);
    $pdo->prepare('UPDATE stocks SET pool_yield_qty = ?, pool_yield_unit = ? WHERE id = ?')
        ->execute([$yield, $unit, $stockId]);

    if (!function_exists('api_costing_apply_price_change')) {
        require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';
    }
    $price = round((float) ($row['current_price'] ?? 0), 2);
    api_costing_apply_price_change($pdo, $stockId, $price, $price, $userId);

    return [
        'stock_id' => $stockId,
        'pool_yield_qty' => $yield,
        'pool_yield_unit' => $unit,
    ];
}

/**
 * Parse `lot: ₱500.00/100roll` (or legacy `.../100pcs`) batch labels from stock-in.
 *
 * @return array{total: float, qty: float, unit: string}|null
 */
function material_parse_lot_batch_label(string $label): ?array
{
    if (!preg_match(
        '/^lot:\s*[^\d]*([\d,]+\.?\d*)\s*\/\s*([\d,]+\.?\d*)\s*([a-z][a-z0-9_]*)/iu',
        $label,
        $m
    )) {
        return null;
    }

    return [
        'total' => (float) str_replace(',', '', $m[1]),
        'qty' => (float) str_replace(',', '', $m[2]),
        'unit' => strtolower((string) $m[3]),
    ];
}

/** @deprecated Use material_parse_lot_batch_label */
function material_packaging_parse_lot_batch_label(string $label): ?array
{
    return material_parse_lot_batch_label($label);
}

/**
 * Derive pool unit cost from lot purchase entries. Pre-lot on-hand (e.g. legacy set_values)
 * is valued at the average lot purchase unit so bad manual prices do not poison WAC.
 */
function material_pool_unit_cost_from_lots(PDO $pdo, int $stockId, float $poolOnHand): ?float
{
    $st = $pdo->prepare(
        "SELECT qty, supplier_batch_no FROM stock_entries
         WHERE stock_id = ? AND supplier_batch_no LIKE 'lot:%'
         ORDER BY created_at ASC"
    );
    $st->execute([$stockId]);

    $lotPurchaseValue = 0.0;
    $lotPurchaseQty = 0.0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $parsed = material_parse_lot_batch_label((string) $row['supplier_batch_no']);
        if ($parsed === null || $parsed['qty'] <= 0) {
            continue;
        }
        $lotPurchaseValue += $parsed['total'];
        $lotPurchaseQty += (float) $row['qty'];
    }

    if ($lotPurchaseQty <= 0) {
        return null;
    }

    $avgLotUnit = $lotPurchaseValue / $lotPurchaseQty;
    $nonLotQty = max(0.0, round($poolOnHand - $lotPurchaseQty, 2));
    $totalValue = $lotPurchaseValue + ($nonLotQty * $avgLotUnit);

    if ($poolOnHand <= 0) {
        return round($avgLotUnit, 6);
    }

    return round($totalValue / $poolOnHand, 6);
}

function material_packaging_pool_unit_cost_from_lots(PDO $pdo, int $stockId, float $poolOnHand): ?float
{
    return material_pool_unit_cost_from_lots($pdo, $stockId, $poolOnHand);
}

function material_packaging_is_bubble_sized_stock_name(string $name): bool
{
    return (bool) preg_match('/^bubble wrap roll - \d+ cm$/i', trim($name));
}

function material_packaging_is_foam_sized_stock_name(string $name): bool
{
    return (bool) preg_match('/^insulation foam roll - /i', trim($name));
}

function material_packaging_generic_foam_roll_stock_name(): string
{
    return 'Insulation foam roll';
}

function material_packaging_is_generic_foam_roll_name(string $name): bool
{
    return strtolower(trim($name)) === strtolower(material_packaging_generic_foam_roll_stock_name());
}

/** Jar Cacao parcel recipes — sized cut yields (recipe-only SKUs, edited from generic foam row). */
function material_packaging_foam_cacao_cut_stock_names(): array
{
    return [
        '9x12' => 'Insulation foam roll - 9x12',
        '12_5x12' => 'Insulation foam roll - 12.5x12',
    ];
}

/**
 * @return list<array{key: string, label: string, stock_name: string, stock_id: int|null, pool_yield_qty: float|null}>
 */
function material_packaging_foam_cacao_cut_yields(PDO $pdo): array
{
    $labels = [
        '9x12' => '9" x 12"',
        '12_5x12' => '12.5" x 12"',
    ];
    $out = [];
    foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {
        $st = $pdo->prepare(
            'SELECT id, pool_yield_qty FROM stocks
             WHERE supplier_id IS NULL AND is_active = 1 AND name = ?
             LIMIT 1'
        );
        $st->execute([$stockName]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $out[] = [
            'key' => $key,
            'label' => $labels[$key] ?? $key,
            'stock_name' => $stockName,
            'stock_id' => $row !== false ? (int) $row['id'] : null,
            'pool_yield_qty' => $row !== false && $row['pool_yield_qty'] !== null
                ? (float) $row['pool_yield_qty']
                : null,
        ];
    }

    return $out;
}

/**
 * @return array{cuts: list<array{key: string, stock_id: int, pool_yield_qty: float}>}
 */
function material_packaging_set_foam_cacao_cut_yields(
    PDO $pdo,
    float $cuts9x12,
    float $cuts12_5x12,
    int $userId
): array {
    if ($cuts9x12 <= 0 || $cuts12_5x12 <= 0) {
        throw new RuntimeException('Each cut yield must be greater than zero.');
    }
    $values = [
        '9x12' => round($cuts9x12, 4),
        '12_5x12' => round($cuts12_5x12, 4),
    ];
    $saved = [];
    foreach (material_packaging_foam_cacao_cut_stock_names() as $key => $stockName) {
        $st = $pdo->prepare(
            'SELECT id FROM stocks WHERE supplier_id IS NULL AND is_active = 1 AND name = ? LIMIT 1'
        );
        $st->execute([$stockName]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Foam cut SKU not found: ' . $stockName);
        }
        $stockId = (int) $row['id'];
        $yield = $values[$key] ?? 0.0;
        material_packaging_set_pool_yield($pdo, $stockId, $yield, 'foam_piece', $userId);
        $saved[] = [
            'key' => $key,
            'stock_id' => $stockId,
            'pool_yield_qty' => $yield,
        ];
    }

    return ['cuts' => $saved];
}

/** Fulfillment / parcel-ship packaging — not product manufacturing BOM. */
function material_inventory_is_fulfillment_parcel_stock_name(string $name): bool
{
    $n = strtolower(trim($name));
    if ($n === '') {
        return false;
    }
    if (str_starts_with($n, 'parcel ')) {
        return true;
    }

    return str_starts_with($n, 'bubble wrap roll')
        || str_starts_with($n, 'cushion piece')
        || str_starts_with($n, 'cushion roll')
        || str_starts_with($n, 'courier pouch')
        || str_starts_with($n, 'insulation foam roll')
        || str_starts_with($n, 'cardboard box');
}

/**
 * Stocks that may appear on product COGS formulas (not parcel packaging tier editor).
 *
 * @param array<string, mixed> $row joined stocks row with category, is_active, name
 */
function material_inventory_eligible_for_product_formula_row(array $row): bool
{
    if (!(int) ($row['is_active'] ?? 1)) {
        return false;
    }
    $cat = (string) ($row['category'] ?? '');
    if ($cat === 'costing_only' || $cat === 'supply') {
        return false;
    }
    if (!in_array($cat, ['raw_material', 'packaging'], true)) {
        return false;
    }
    if ($cat === 'packaging' && material_inventory_is_fulfillment_parcel_stock_name((string) ($row['name'] ?? ''))) {
        return false;
    }

    return true;
}

/**
 * WAC / current price for unsized warehouse bubble roll (shared across sized recipe lines).
 */
function material_packaging_generic_bubble_roll_unit_cost(PDO $pdo): ?float
{
    $st = $pdo->prepare(
        'SELECT id, on_hand, current_price FROM stocks
         WHERE supplier_id IS NULL AND is_active = 1
           AND LOWER(TRIM(name)) = ?
         LIMIT 1'
    );
    $st->execute(['bubble wrap roll']);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    $stockId = (int) $row['id'];
    $onHand = (float) ($row['on_hand'] ?? 0);
    $lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
    if ($lotUnit !== null && $lotUnit > 0) {
        return round($lotUnit, 6);
    }
    $price = round((float) ($row['current_price'] ?? 0), 6);

    return $price > 0 ? $price : null;
}

/**
 * WAC / current price for unsized warehouse insulation foam roll (shared across sized recipe lines).
 */
function material_packaging_generic_foam_roll_unit_cost(PDO $pdo): ?float
{
    $st = $pdo->prepare(
        'SELECT id, on_hand, current_price FROM stocks
         WHERE supplier_id IS NULL AND is_active = 1
           AND LOWER(TRIM(name)) = ?
         LIMIT 1'
    );
    $st->execute(['insulation foam roll']);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    $stockId = (int) $row['id'];
    $onHand = (float) ($row['on_hand'] ?? 0);
    $lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
    if ($lotUnit !== null && $lotUnit > 0) {
        return round($lotUnit, 6);
    }
    $price = round((float) ($row['current_price'] ?? 0), 6);

    return $price > 0 ? $price : null;
}

/**
 * Stock in a packaging purchase lot: add qty to pool and update unit cost (WAC when pool > 0).
 *
 * @return array{stock_id: int, on_hand: float, current_price: float, lot_unit: float, entry_id: int}
 */
function material_packaging_stock_in_lot(
    PDO $pdo,
    int $stockId,
    float $lotTotalPrice,
    float $lotQty,
    int $userId,
    ?string $note = null,
    ?float $poolYieldQty = null
): array {
    if ($lotTotalPrice < 0) {
        throw new RuntimeException('Total price must be 0 or greater.');
    }
    if ($lotQty <= 0) {
        throw new RuntimeException('Quantity received must be greater than 0.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can receive stock-in lots.');
    }
    $stockName = trim((string) $row['name']);
    if (preg_match('/^bubble wrap roll - \d+ cm$/i', $stockName)) {
        throw new RuntimeException(
            'Stock in unsized Bubble wrap roll only. Width (13 / 17 / 22 cm) is assigned when issuing to packing.'
        );
    }
    if (preg_match('/^insulation foam roll - /i', $stockName)) {
        throw new RuntimeException(
            'Stock in unsized Insulation foam roll only. Cut size is applied automatically when shipping cacao parcels.'
        );
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $oldPrice = round((float) ($row['current_price'] ?? 0), 6);
    $lotUnit = round($lotTotalPrice / $lotQty, 6);
    $newOnHand = round($oldOnHand + $lotQty, 2);

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    if ($stockUnit === '') {
        $stockUnit = 'pcs';
    }
    if ($stockUnit === 'roll' && abs($lotQty - round($lotQty)) > 0.0001) {
        throw new RuntimeException('Roll quantity must be a whole number (e.g. 1 roll, 2 rolls).');
    }
    $isPoolPackaging = ((int) ($row['production_deduct'] ?? 0) === 0) && $stockUnit === 'roll';
    if ($poolYieldQty !== null && (!$isPoolPackaging || $poolYieldQty <= 0)) {
        throw new RuntimeException('Pool yield must be greater than 0 for roll-based pool packaging.');
    }
    $batchLabel = sprintf(
        'lot: ₱%s/%s%s',
        number_format($lotTotalPrice, 2, '.', ''),
        rtrim(rtrim(number_format($lotQty, 4, '.', ''), '0'), '.'),
        $stockUnit
    );
    if ($note !== null && trim($note) !== '') {
        $batchLabel .= ' — ' . trim($note);
    }
    $batchLabel = substr($batchLabel, 0, 120);

    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        $lotQty,
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$newOnHand, $stockId]);
    if ($poolYieldQty !== null && $isPoolPackaging) {
        $yieldUnit = trim((string) ($row['pool_yield_unit'] ?? ''));
        if ($yieldUnit === '') {
            $yieldUnit = 'pcs';
        }
        $pdo->prepare('UPDATE stocks SET pool_yield_qty = ?, pool_yield_unit = ? WHERE id = ?')
            ->execute([round($poolYieldQty, 4), $yieldUnit, $stockId]);
    }

    $derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);
    $newPrice = $derivedPrice ?? round($lotUnit, 6);

    $oldPriceRounded = round($oldPrice, 2);
    $newPriceRounded = round($newPrice, 2);
    if ($newPriceRounded !== $oldPriceRounded) {
        if (!function_exists('api_costing_apply_price_change')) {
            require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';
        }
        api_costing_apply_price_change($pdo, $stockId, $oldPriceRounded, $newPriceRounded, $userId);
    } else {
        $pdo->prepare('UPDATE stocks SET current_price = ? WHERE id = ?')->execute([$newPriceRounded, $stockId]);
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'current_price' => $newPriceRounded,
        'lot_unit' => round($lotUnit, 6),
        'entry_id' => $entryId,
    ];
}

/**
 * Executive count correction — set absolute on-hand without changing unit cost.
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int|null}
 */
function material_packaging_set_on_hand(
    PDO $pdo,
    int $stockId,
    float $onHand,
    int $userId,
    ?string $note = null
): array {
    if ($onHand < 0) {
        throw new RuntimeException('On-hand quantity cannot be negative.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials support count correction.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $newOnHand = round($onHand, 2);
    $entryId = null;

    if ($newOnHand !== $oldOnHand) {
        $delta = round($newOnHand - $oldOnHand, 2);
        $auditNote = $note !== null && trim($note) !== '' ? trim($note) : 'Count correction';
        $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$newOnHand, $stockId]);
        $ins = $pdo->prepare(
            'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $stockId,
            (int) $row['dept_id'],
            $delta,
            date('Y-m-d H:i:s'),
            substr('count_correction: ' . $auditNote, 0, 120),
            $userId,
        ]);
        $entryId = (int) $pdo->lastInsertId();
        require_once __DIR__ . '/inventory_edit_log.php';
        inventory_edit_log_write(
            $pdo,
            $userId,
            'stocks',
            $stockId,
            'on_hand',
            (string) $oldOnHand,
            (string) $newOnHand,
            'set_on_hand'
        );
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'entry_id' => $entryId,
    ];
}

/**
 * Record packaging taken for production use (production manager / executive).
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int}
 */
function material_packaging_deduct_for_use(
    PDO $pdo,
    int $stockId,
    float $qty,
    int $userId,
    string $reason
): array {
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be greater than 0.');
    }
    $reason = trim($reason);
    if ($reason === '') {
        throw new RuntimeException('Reason is required.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can be deducted for use.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    if ($stockUnit === '') {
        $stockUnit = 'pcs';
    }
    if ($stockUnit === 'roll' && abs($qty - round($qty)) > 0.0001) {
        throw new RuntimeException('Roll quantity must be a whole number (e.g. 1 roll).');
    }

    $current = round((float) ($row['on_hand'] ?? 0), 2);
    $deductQty = round($qty, 2);
    $next = round($current - $deductQty, 2);
    if ($next < 0) {
        throw new RuntimeException('Insufficient on-hand quantity.');
    }

    $batchLabel = substr('use: ' . $reason, 0, 120);
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        -$deductQty,
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);

    return [
        'stock_id' => $stockId,
        'on_hand' => $next,
        'entry_id' => $entryId,
    ];
}

/** Username whose login password is required to remove packaging catalog rows. */
const MATERIAL_PACKAGING_REMOVE_PASSWORD_USERNAME = 'Noli_F';

/**
 * Verify plain password against the active IMS user Noli_F (live password_hash).
 */
function material_packaging_verify_remove_password(PDO $pdo, string $plain): bool
{
    $plain = (string) $plain;
    if ($plain === '') {
        return false;
    }
    $st = $pdo->prepare(
        'SELECT password_hash FROM users WHERE username = ? AND is_active = 1 LIMIT 1'
    );
    $st->execute([MATERIAL_PACKAGING_REMOVE_PASSWORD_USERNAME]);
    $hash = $st->fetchColumn();
    if ($hash === false || $hash === null || $hash === '') {
        return false;
    }

    return password_verify($plain, (string) $hash);
}

/**
 * Soft-remove packaging catalog row (is_active = 0). Requires Noli_F password.
 *
 * @return array{stock_id: int, name: string, is_active: int}
 */
function material_packaging_catalog_remove(PDO $pdo, int $stockId, string $confirmPassword): array
{
    if (!material_packaging_verify_remove_password($pdo, $confirmPassword)) {
        throw new RuntimeException('Invalid removal password.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging catalog items can be removed here.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('This item is already removed from the catalog.');
    }
    $updated = material_inventory_update($pdo, $stockId, null, null, false);

    return [
        'stock_id' => (int) $updated['id'],
        'name' => (string) $updated['name'],
        'is_active' => (int) ($updated['is_active'] ?? 0),
    ];
}

/**
 * Report packaging damaged: deduct warehouse on-hand (damage: stock entry) and write damaged-pool log.
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int, damage_log_id: int}
 */
function material_packaging_report_damage(
    PDO $pdo,
    int $stockId,
    float $qty,
    int $userId,
    string $reason
): array {
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be greater than 0.');
    }
    $reason = trim($reason);
    if ($reason === '') {
        throw new RuntimeException('Reason is required.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can be reported as damaged.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    if ($stockUnit === '') {
        $stockUnit = 'pcs';
    }
    if ($stockUnit === 'roll' && abs($qty - round($qty)) > 0.0001) {
        throw new RuntimeException('Roll quantity must be a whole number (e.g. 1 roll).');
    }

    $current = round((float) ($row['on_hand'] ?? 0), 2);
    $deductQty = round($qty, 2);
    $next = round($current - $deductQty, 2);
    if ($next < 0) {
        throw new RuntimeException('Insufficient on-hand quantity.');
    }

    $batchLabel = substr('damage: ' . $reason, 0, 120);
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        -$deductQty,
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);

    $log = $pdo->prepare(
        'INSERT INTO packaging_material_damage_log (stock_id, qty, reason, stock_entry_id, reported_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $log->execute([
        $stockId,
        $deductQty,
        substr($reason, 0, 255),
        $entryId,
        $userId,
    ]);
    $damageLogId = (int) $pdo->lastInsertId();

    return [
        'stock_id' => $stockId,
        'on_hand' => $next,
        'entry_id' => $entryId,
        'damage_log_id' => $damageLogId,
    ];
}

/**
 * Sum damaged qty per packaging stock for a date range (inclusive days).
 *
 * @return array<int, float> stock_id => qty
 */
function material_packaging_damage_totals(PDO $pdo, string $from, string $to): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        throw new RuntimeException('from and to must be YYYY-MM-DD.');
    }
    if ($from > $to) {
        throw new RuntimeException('from must be on or before to.');
    }

    $st = $pdo->prepare(
        "SELECT d.stock_id, SUM(d.qty) AS qty
         FROM packaging_material_damage_log d
         INNER JOIN stocks s ON s.id = d.stock_id AND s.category = 'packaging'
         WHERE d.created_at >= ? AND d.created_at <= ?
         GROUP BY d.stock_id"
    );
    $st->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $out[(int) $row['stock_id']] = round((float) ($row['qty'] ?? 0), 2);
    }

    return $out;
}

/** Snap fulfillment roll consumption to 1/16 increments. */
function material_fulfillment_snap_sixteenths(float $qty): float
{
    if ($qty <= 0) {
        return 0.0;
    }

    return round(round($qty * 16.0) / 16.0, 4);
}

/**
 * Deduct fulfillment packaging for a parcel ship (fractional rolls allowed).
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int, qty_deducted: float}
 */
function material_packaging_deduct_fulfillment_line(
    PDO $pdo,
    int $stockId,
    float $qty,
    string $lineUnit,
    int $userId,
    string $reason,
    int $parcelId
): array {
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be greater than 0.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can be deducted.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    $deductQty = round($qty, 4);
    if (in_array($lineUnit, ['bubble_piece', 'foam_piece', 'column'], true)) {
        $yield = (float) ($row['pool_yield_qty'] ?? 0);
        if ($yield <= 0) {
            throw new RuntimeException('Roll yield is not configured for ' . (string) $row['name'] . '.');
        }
        $deductQty = material_fulfillment_snap_sixteenths($qty / $yield);
    } elseif ($stockUnit === 'roll') {
        $deductQty = material_fulfillment_snap_sixteenths($qty);
    } else {
        $deductQty = round($qty, 2);
    }
    if ($deductQty <= 0) {
        throw new RuntimeException('Deduct quantity rounds to zero.');
    }

    $current = round((float) ($row['on_hand'] ?? 0), 4);
    $next = round($current - $deductQty, 4);
    if ($next < -0.0001) {
        throw new RuntimeException(
            'Insufficient fulfillment packaging on hand for ' . (string) $row['name'] . '.'
        );
    }

    $batchLabel = substr('parcel #' . $parcelId . ': ' . $reason, 0, 120);
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        -$deductQty,
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);

    return [
        'stock_id' => $stockId,
        'on_hand' => $next,
        'entry_id' => $entryId,
        'qty_deducted' => $deductQty,
    ];
}

function parcel_fulfillment_already_deducted(PDO $pdo, int $parcelId): bool
{
    try {
        $st = $pdo->prepare('SELECT 1 FROM online_parcel_fulfillment_deduct_lines WHERE parcel_id = ? LIMIT 1');
        $st->execute([$parcelId]);

        return (bool) $st->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

/**
 * @return list<array{stock_id: int, qty: float, unit: string}>
 */
function parcel_fulfillment_apply_deduct(PDO $pdo, int $parcelId, int $userId, string $itemsRaw): array
{
    if (!parcel_pack_tables_ready($pdo)) {
        return [];
    }
    if (parcel_fulfillment_already_deducted($pdo, $parcelId)) {
        return [];
    }

    if (!function_exists('fulfillment_packing_consume_line')) {
        require_once __DIR__ . '/fulfillment_packing.php';
    }

    $lines = parcel_pack_fulfillment_lines_for_items_raw($pdo, $itemsRaw);
    $deducted = [];
    foreach ($lines as $line) {
        $logStockId = (int) $line['stock_id'];
        if (fulfillment_packing_tables_ready($pdo)) {
            $result = fulfillment_packing_consume_line(
                $pdo,
                (int) $line['stock_id'],
                (float) $line['qty'],
                (string) $line['unit'],
                (string) $line['stock_name'],
                $parcelId
            );
            $qtyDeducted = (float) $result['qty_consumed'];
            $logStockId = (int) $result['stock_id'];
        } else {
            $legacy = material_packaging_deduct_fulfillment_line(
                $pdo,
                (int) $line['stock_id'],
                (float) $line['qty'],
                (string) $line['unit'],
                $userId,
                (string) $line['stock_name'],
                $parcelId
            );
            $qtyDeducted = (float) $legacy['qty_deducted'];
        }
        $pdo->prepare(
            'INSERT INTO online_parcel_fulfillment_deduct_lines (parcel_id, stock_id, qty, unit)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $parcelId,
            $logStockId,
            $qtyDeducted,
            (string) $line['unit'],
        ]);
        $deducted[] = [
            'stock_id' => $logStockId,
            'qty' => $qtyDeducted,
            'unit' => (string) $line['unit'],
        ];
    }

    return $deducted;
}

function parcel_fulfillment_revert_deduct(PDO $pdo, int $parcelId): void
{
    if ($parcelId < 1) {
        return;
    }
    if (!function_exists('fulfillment_packing_tables_ready')) {
        require_once __DIR__ . '/fulfillment_packing.php';
    }
    try {
        $st = $pdo->prepare(
            'SELECT stock_id, qty FROM online_parcel_fulfillment_deduct_lines WHERE parcel_id = ?'
        );
        $st->execute([$parcelId]);
        $lines = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        return;
    }

    foreach ($lines as $line) {
        $stockId = (int) ($line['stock_id'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        if ($stockId < 1 || $qty <= 0) {
            continue;
        }
        if (fulfillment_packing_tables_ready($pdo)) {
            $pdo->prepare(
                'UPDATE fulfillment_packing_balances
                 SET consumed_qty = GREATEST(0, consumed_qty - ?)
                 WHERE stock_id = ?'
            )->execute([$qty, $stockId]);
        } else {
            $pdo->prepare('UPDATE stocks SET on_hand = on_hand + ? WHERE id = ?')->execute([$qty, $stockId]);
        }
    }
    if ($lines !== []) {
        $pdo->prepare('DELETE FROM online_parcel_fulfillment_deduct_lines WHERE parcel_id = ?')->execute([$parcelId]);
    }
}

/** @return list<array<string, mixed>> */
function material_pool_recent_entries(PDO $pdo, int $stockId, int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $st = $pdo->prepare(
        'SELECT se.id, se.qty, se.supplier_batch_no, se.created_at, u.name AS entered_by_name
         FROM stock_entries se
         LEFT JOIN users u ON u.id = se.entered_by
         WHERE se.stock_id = ?
         ORDER BY se.created_at DESC
         LIMIT ' . $limit
    );
    $st->execute([$stockId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['qty'] = round((float) ($row['qty'] ?? 0), 2);
        $label = (string) ($row['supplier_batch_no'] ?? '');
        $parsed = material_parse_lot_batch_label($label);
        $row['entry_kind'] = str_starts_with($label, 'lot:')
            ? 'lot'
            : (str_starts_with($label, 'count_correction:')
                ? 'count_correction'
                : (str_starts_with($label, 'use:')
                    ? 'use'
                    : (str_starts_with($label, 'damage:')
                        ? 'damage'
                        : (str_starts_with($label, 'issue:') ? 'issue' : 'other'))));
        if ($parsed !== null) {
            $row['lot_total_price'] = $parsed['total'];
            $row['lot_qty'] = $parsed['qty'];
            $row['lot_unit'] = round($parsed['total'] / $parsed['qty'], 6);
        }
    }

    return $rows;
}

/** @return list<array<string, mixed>> */
function material_packaging_recent_entries(PDO $pdo, int $stockId, int $limit = 25): array
{
    return material_pool_recent_entries($pdo, $stockId, $limit);
}

/**
 * Cross-item packaging scan/manual stock-out history (`use:` entries only).
 *
 * @param array{
 *   q?: string,
 *   stock_id?: int,
 *   user_id?: int,
 *   from?: string,
 *   to?: string
 * } $filters
 * @return array{
 *   rows: list<array<string, mixed>>,
 *   total: int,
 *   page: int,
 *   per_page: int,
 *   actors: list<array{id: int, name: string}>
 * }
 */
function material_packaging_stock_out_history(
    PDO $pdo,
    array $filters = [],
    int $page = 1,
    int $perPage = 50
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $where = [
        "s.category = 'packaging'",
        'se.qty < 0',
        "se.supplier_batch_no LIKE 'use:%'",
    ];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(s.name LIKE ? OR se.supplier_batch_no LIKE ? OR u.name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $stockId = (int) ($filters['stock_id'] ?? 0);
    if ($stockId > 0) {
        $where[] = 'se.stock_id = ?';
        $params[] = $stockId;
    }

    $userId = (int) ($filters['user_id'] ?? 0);
    if ($userId > 0) {
        $where[] = 'se.entered_by = ?';
        $params[] = $userId;
    }

    $from = trim((string) ($filters['from'] ?? ''));
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'se.created_at >= ?';
        $params[] = $from . ' 00:00:00';
    }

    $to = trim((string) ($filters['to'] ?? ''));
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'se.created_at <= ?';
        $params[] = $to . ' 23:59:59';
    }

    $whereSql = implode(' AND ', $where);

    $countSt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         LEFT JOIN users u ON u.id = se.entered_by
         WHERE {$whereSql}"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSt = $pdo->prepare(
        "SELECT se.id, se.stock_id, se.qty, se.supplier_batch_no, se.created_at, se.entered_by,
                s.name AS stock_name, s.unit,
                u.name AS entered_by_name
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         LEFT JOIN users u ON u.id = se.entered_by
         WHERE {$whereSql}
         ORDER BY se.created_at DESC, se.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $listSt->execute($params);
    $rawRows = $listSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $rows = [];
    foreach ($rawRows as $row) {
        $label = (string) ($row['supplier_batch_no'] ?? '');
        $reason = $label;
        if (str_starts_with($label, 'use:')) {
            $reason = trim(substr($label, 4));
        }
        $qtyAbs = abs(round((float) ($row['qty'] ?? 0), 2));
        $rows[] = [
            'id' => (int) $row['id'],
            'stock_id' => (int) $row['stock_id'],
            'stock_name' => (string) $row['stock_name'],
            'unit' => (string) ($row['unit'] ?? 'pcs'),
            'qty' => $qtyAbs,
            'reason' => $reason,
            'entered_by' => (int) ($row['entered_by'] ?? 0),
            'entered_by_name' => $row['entered_by_name'] !== null && $row['entered_by_name'] !== ''
                ? (string) $row['entered_by_name']
                : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }

    $actorsSt = $pdo->query(
        "SELECT DISTINCT u.id, u.name
         FROM stock_entries se
         INNER JOIN stocks s ON s.id = se.stock_id
         INNER JOIN users u ON u.id = se.entered_by
         WHERE s.category = 'packaging'
           AND se.qty < 0
           AND se.supplier_batch_no LIKE 'use:%'
         ORDER BY u.name ASC"
    );
    $actorsRaw = $actorsSt ? ($actorsSt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    $actors = [];
    foreach ($actorsRaw as $actor) {
        $actors[] = [
            'id' => (int) $actor['id'],
            'name' => (string) $actor['name'],
        ];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'actors' => $actors,
    ];
}

/** @return list<array<string, mixed>> */
function material_supply_recent_entries(PDO $pdo, int $stockId, int $limit = 25): array
{
    return material_pool_recent_entries($pdo, $stockId, $limit);
}

/**
 * Stock in a supply purchase lot: add qty to pool and update unit cost (WAC when pool > 0).
 *
 * @return array{stock_id: int, on_hand: float, current_price: float, lot_unit: float, entry_id: int}
 */
function material_supply_stock_in_lot(
    PDO $pdo,
    int $stockId,
    float $lotTotalPrice,
    float $lotQty,
    int $userId,
    ?string $note = null
): array {
    if ($lotTotalPrice < 0) {
        throw new RuntimeException('Total price must be 0 or greater.');
    }
    if ($lotQty <= 0) {
        throw new RuntimeException('Quantity received must be greater than 0.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'supply') {
        throw new RuntimeException('Only supply items can receive stock-in lots.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $oldPrice = round((float) ($row['current_price'] ?? 0), 6);
    $lotUnit = round($lotTotalPrice / $lotQty, 6);
    $newOnHand = round($oldOnHand + $lotQty, 2);

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    if ($stockUnit === '') {
        $stockUnit = 'pcs';
    }
    $batchLabel = sprintf(
        'lot: ₱%s/%s%s',
        number_format($lotTotalPrice, 2, '.', ''),
        rtrim(rtrim(number_format($lotQty, 4, '.', ''), '0'), '.'),
        $stockUnit
    );
    if ($note !== null && trim($note) !== '') {
        $batchLabel .= ' — ' . trim($note);
    }
    $batchLabel = substr($batchLabel, 0, 120);

    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        $lotQty,
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$newOnHand, $stockId]);

    $derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);
    $newPrice = $derivedPrice ?? round($lotUnit, 6);

    $oldPriceRounded = round($oldPrice, 2);
    $newPriceRounded = round($newPrice, 2);
    if ($newPriceRounded !== $oldPriceRounded) {
        if (!function_exists('api_costing_apply_price_change')) {
            require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';
        }
        api_costing_apply_price_change($pdo, $stockId, $oldPriceRounded, $newPriceRounded, $userId);
    } else {
        $pdo->prepare('UPDATE stocks SET current_price = ? WHERE id = ?')->execute([$newPriceRounded, $stockId]);
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'current_price' => $newPriceRounded,
        'lot_unit' => round($lotUnit, 6),
        'entry_id' => $entryId,
    ];
}

/**
 * Executive count correction — set absolute on-hand without changing unit cost.
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int|null}
 */
function material_supply_set_on_hand(
    PDO $pdo,
    int $stockId,
    float $onHand,
    int $userId,
    ?string $note = null
): array {
    if ($onHand < 0) {
        throw new RuntimeException('On-hand quantity cannot be negative.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'supply') {
        throw new RuntimeException('Only supply items support count correction.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $newOnHand = round($onHand, 2);
    $entryId = null;

    if ($newOnHand !== $oldOnHand) {
        $delta = round($newOnHand - $oldOnHand, 2);
        $auditNote = $note !== null && trim($note) !== '' ? trim($note) : 'Count correction';
        $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$newOnHand, $stockId]);
        $ins = $pdo->prepare(
            'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $stockId,
            (int) $row['dept_id'],
            $delta,
            date('Y-m-d H:i:s'),
            substr('count_correction: ' . $auditNote, 0, 120),
            $userId,
        ]);
        $entryId = (int) $pdo->lastInsertId();
        require_once __DIR__ . '/inventory_edit_log.php';
        inventory_edit_log_write(
            $pdo,
            $userId,
            'stocks',
            $stockId,
            'on_hand',
            (string) $oldOnHand,
            (string) $newOnHand,
            'set_on_hand'
        );
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'entry_id' => $entryId,
    ];
}

/**
 * Deduct supply on approve of an issue request.
 *
 * @return array{stock_id: int, on_hand: float, entry_id: int}
 */
function material_supply_apply_issue(
    PDO $pdo,
    int $stockId,
    float $amount,
    int $userId,
    string $requesterName,
    string $reason
): array {
    if ($amount <= 0) {
        throw new RuntimeException('Issue quantity must be positive.');
    }
    $requesterName = trim($requesterName);
    if ($requesterName === '') {
        throw new RuntimeException('Requester name is required.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'supply') {
        throw new RuntimeException('Only supply items support issue requests.');
    }
    $current = round((float) ($row['on_hand'] ?? 0), 2);
    $next = round($current - $amount, 2);
    if ($next < 0) {
        throw new RuntimeException('Insufficient on-hand quantity.');
    }
    $label = substr('issue: ' . $requesterName . ' — ' . trim($reason), 0, 120);
    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);
    $ins = $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        (int) $row['dept_id'],
        round(-$amount, 2),
        date('Y-m-d H:i:s'),
        $label,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    return ['stock_id' => $stockId, 'on_hand' => $next, 'entry_id' => $entryId];
}

function material_adjustment_submit(
    PDO $pdo,
    int $stockId,
    string $action,
    float $amount,
    string $reason,
    int $userId,
    ?string $requesterName = null
): int {
    if (!in_array($action, ['add', 'deduct', 'set_on_hand', 'issue'], true)) {
        throw new RuntimeException('Invalid action.');
    }
    if ($action === 'set_on_hand') {
        if ($amount < 0) {
            throw new RuntimeException('Target on-hand cannot be negative.');
        }
    } elseif ($amount <= 0) {
        throw new RuntimeException('Amount must be positive.');
    }
    if (trim($reason) === '') {
        throw new RuntimeException('Reason is required.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    $category = (string) $row['category'];
    if ($category === 'packaging') {
        if ($action !== 'set_on_hand') {
            throw new RuntimeException('Packaging requests must set a target on-hand count.');
        }
    } elseif ($category === 'supply') {
        if (!in_array($action, ['issue', 'set_on_hand'], true)) {
            throw new RuntimeException('Supply requests must issue stock or set on-hand count.');
        }
        if ($action === 'issue') {
            $requesterName = trim((string) $requesterName);
            if ($requesterName === '') {
                throw new RuntimeException('Requester name is required for supply issues.');
            }
        }
    } else {
        throw new RuntimeException('Only packaging and supply items can be requested.');
    }
    $ins = $pdo->prepare(
        'INSERT INTO material_adjustment_requests (stock_id, action, amount, reason, requester_name, requested_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $stockId,
        $action,
        $amount,
        trim($reason),
        $action === 'issue' ? trim((string) $requesterName) : null,
        $userId,
    ]);

    return (int) $pdo->lastInsertId();
}

/** @return list<array<string, mixed>> */
function material_adjustment_list(
    PDO $pdo,
    ?int $requestedBy = null,
    ?string $category = null,
    ?string $action = null,
): array {
    $sql = 'SELECT mar.*, s.name AS stock_name, s.unit, s.category,
                   u.name AS requested_by_name, r.name AS reviewed_by_name
            FROM material_adjustment_requests mar
            INNER JOIN stocks s ON s.id = mar.stock_id
            INNER JOIN users u ON u.id = mar.requested_by
            LEFT JOIN users r ON r.id = mar.reviewed_by';
    $where = [];
    $params = [];
    if ($requestedBy !== null && $requestedBy > 0) {
        $where[] = 'mar.requested_by = ?';
        $params[] = $requestedBy;
    }
    if ($category !== null && trim($category) !== '') {
        $where[] = 's.category = ?';
        $params[] = trim($category);
    }
    if ($action !== null && trim($action) !== '') {
        $where[] = 'mar.action = ?';
        $params[] = trim($action);
    }
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY mar.status = "pending" DESC, mar.created_at DESC LIMIT 500';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['summary'] = material_adjustment_summary($row);
    }

    return $rows;
}

/** @param array<string, mixed> $req */
function material_adjustment_summary(array $req): string
{
    $action = (string) ($req['action'] ?? '');
    $amount = $req['amount'] ?? '';
    $name = (string) ($req['stock_name'] ?? 'stock');
    $unit = (string) ($req['unit'] ?? '');

    if ($action === 'set_on_hand') {
        return 'Set on-hand to ' . $amount . ' ' . $unit . ' — ' . $name;
    }
    if ($action === 'issue') {
        $requester = trim((string) ($req['requester_name'] ?? ''));
        $who = $requester !== '' ? $requester : 'staff';

        return 'Issue ' . $amount . ' ' . $unit . ' to ' . $who . ' — ' . $name;
    }

    return ucfirst($action) . ' ' . $amount . ' ' . $unit . ' — ' . $name;
}

function material_adjustment_approve(PDO $pdo, int $requestId, int $reviewerId): void
{
    $st = $pdo->prepare(
        'SELECT * FROM material_adjustment_requests WHERE id = ? AND status = "pending" LIMIT 1 FOR UPDATE'
    );
    $st->execute([$requestId]);
    $req = $st->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        throw new RuntimeException('Request not found.');
    }
    $stockId = (int) $req['stock_id'];
    $action = (string) $req['action'];
    $amount = (float) $req['amount'];
    $reason = (string) $req['reason'];
    $stock = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($stock === null) {
        throw new RuntimeException('Stock not found.');
    }
    $category = (string) $stock['category'];
    if ($category === 'packaging') {
        if ($action !== 'set_on_hand') {
            throw new RuntimeException('Packaging requests must set on-hand count.');
        }
        $result = material_packaging_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);
    } elseif ($category === 'supply') {
        if ($action === 'issue') {
            $result = material_supply_apply_issue(
                $pdo,
                $stockId,
                $amount,
                $reviewerId,
                (string) ($req['requester_name'] ?? ''),
                $reason
            );
        } elseif ($action === 'set_on_hand') {
            $result = material_supply_set_on_hand($pdo, $stockId, $amount, $reviewerId, $reason);
        } elseif (in_array($action, ['add', 'deduct'], true)) {
            $result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);
        } else {
            throw new RuntimeException('Unsupported supply request action.');
        }
    } else {
        throw new RuntimeException('Unsupported stock category for adjustment.');
    }
    $pdo->prepare(
        'UPDATE material_adjustment_requests
         SET status = "approved", reviewed_by = ?, reviewed_at = NOW(), applied_entry_id = ?
         WHERE id = ?'
    )->execute([$reviewerId, $result['entry_id'] ?? null, $requestId]);
}

function material_adjustment_reject(PDO $pdo, int $requestId, int $reviewerId, string $note): void
{
    if (trim($note) === '') {
        throw new RuntimeException('Rejection note is required.');
    }
    $upd = $pdo->prepare(
        'UPDATE material_adjustment_requests
         SET status = "rejected", rejection_note = ?, reviewed_by = ?, reviewed_at = NOW()
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([trim($note), $reviewerId, $requestId]);
    if ($upd->rowCount() < 1) {
        throw new RuntimeException('Request not found.');
    }
}

function material_adjustment_pending_count(PDO $pdo): int
{
    try {
        $st = $pdo->query(
            'SELECT COUNT(*) FROM material_adjustment_requests
             WHERE status = "pending" AND executive_viewed_at IS NULL'
        );

        return (int) $st->fetchColumn();
    } catch (Throwable) {
        try {
            $st = $pdo->query('SELECT COUNT(*) FROM material_adjustment_requests WHERE status = "pending"');

            return (int) $st->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }
}

function material_adjustment_mark_viewed(PDO $pdo, int $requestId): void
{
    try {
        $pdo->prepare(
            'UPDATE material_adjustment_requests SET executive_viewed_at = NOW()
             WHERE id = ? AND status = "pending" AND executive_viewed_at IS NULL'
        )->execute([$requestId]);
    } catch (Throwable) {
        // Table may not exist pre-migration.
    }
}

/** @return list<array<string, mixed>> */
function material_adjustment_unread_list(PDO $pdo, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    try {
        $st = $pdo->prepare(
            'SELECT mar.id, mar.action, mar.amount, mar.reason, mar.requester_name, mar.created_at, mar.status,
                    s.name AS stock_name, s.unit, s.category,
                    u.name AS requested_by_name
             FROM material_adjustment_requests mar
             INNER JOIN stocks s ON s.id = mar.stock_id
             INNER JOIN users u ON u.id = mar.requested_by
             WHERE mar.status = "pending" AND mar.executive_viewed_at IS NULL
               AND s.category = "supply" AND mar.action = "issue"
             ORDER BY mar.created_at DESC
             LIMIT ' . $limit
        );
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['summary'] = material_adjustment_summary($row);
        }

        return $rows;
    } catch (Throwable) {
        return [];
    }
}

/** @return array<string, mixed> */
function material_raw_snapshot(PDO $pdo): array
{
    $honey = [];
    try {
        $st = $pdo->query(
            'SELECT wh.id, wh.supplier_code, wh.variant, wh.current_qty, wh.initial_qty, wh.arrival_date
             FROM honey_warehouse_rows wh
             ORDER BY wh.arrival_date DESC, wh.id DESC
             LIMIT 200'
        );
        $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        $totalGallons = 0.0;
        foreach ($rows as $r) {
            $q = (float) ($r['current_qty'] ?? 0);
            $totalGallons += $q;
        }
        $honey = [
            'rows' => $rows,
            'total_gallons' => round($totalGallons, 2),
            'unit' => 'gallon',
        ];
    } catch (Throwable) {
        $honey = ['rows' => [], 'total_gallons' => 0.0, 'unit' => 'gallon'];
    }

    $rawStocks = [];
    if (material_inventory_has_columns($pdo)) {
        $names = [
            'paragis' => PT_LEAF_STOCK_NAME,
            'black_rice' => BRC_RAW_STOCK_NAME,
            'cacao' => JAR_CACAO_RAW_STOCK_NAME,
            'honey_comb' => HONEY_COMB_RAW_STOCK_NAME,
        ];
        foreach ($names as $key => $stockName) {
            $st = $pdo->prepare(
                'SELECT s.name, s.unit, d.name AS dept_name,
                        (SELECT COALESCE(SUM(se.qty), 0)
                         FROM stock_entries se
                         INNER JOIN stocks sx ON sx.id = se.stock_id
                         WHERE sx.name = ?) AS entries_sum,
                        (SELECT MAX(se.created_at)
                         FROM stock_entries se
                         INNER JOIN stocks sx ON sx.id = se.stock_id
                         WHERE sx.name = ?) AS last_entry_at
                 FROM stocks s
                 INNER JOIN departments d ON d.id = s.dept_id
                 WHERE s.name = ?
                 ORDER BY s.supplier_id IS NULL DESC, s.id ASC
                 LIMIT 1'
            );
            $st->execute([$stockName, $stockName, $stockName]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $rawStocks[$key] = $row ?: ['name' => $stockName, 'on_hand' => 0, 'unit' => 'bag'];
        }
    }

    return [
        'honey' => $honey,
        'raw_stocks' => $rawStocks,
    ];
}

/** @return array<string, mixed>|null */
function material_inventory_raw_stock_by_slug(PDO $pdo, string $slug): ?array
{
    $map = [
        'paragis' => PT_LEAF_STOCK_NAME,
        'black-rice' => BRC_RAW_STOCK_NAME,
        'cacao' => JAR_CACAO_RAW_STOCK_NAME,
        'honey-comb' => HONEY_COMB_RAW_STOCK_NAME,
    ];
    $name = $map[$slug] ?? null;
    if ($name === null) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, d.name AS dept_name,
                (SELECT COALESCE(SUM(se.qty), 0) FROM stock_entries se WHERE se.stock_id = s.id) AS entries_sum,
                (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
         FROM stocks s
         INNER JOIN departments d ON d.id = s.dept_id
         WHERE s.name = ?
         ORDER BY s.supplier_id IS NULL DESC, s.id ASC
         LIMIT 1'
    );
    $st->execute([$name]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return array<string, mixed>|null */
function material_packaging_lookup_by_barcode(PDO $pdo, string $code): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }
    if (!material_inventory_has_barcode_column($pdo)) {
        if (preg_match('/^PKG(\d+)$/i', $code, $m) === 1) {
            $id = (int) $m[1];
            $row = material_inventory_fetch_stock($pdo, $id);
            if ($row !== null && (string) ($row['category'] ?? '') === 'packaging') {
                $lot = material_packaging_last_lot_qty($pdo, $id);

                return material_inventory_enrich_packaging_row($row, $lot);
            }
        }

        return null;
    }
    $st = $pdo->prepare(
        'SELECT s.*, d.name AS dept_name
         FROM stocks s
         INNER JOIN departments d ON d.id = s.dept_id
         WHERE s.category = \'packaging\' AND UPPER(TRIM(s.barcode)) = ?
         LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    $id = (int) $row['id'];
    if (trim((string) ($row['barcode'] ?? '')) === '') {
        $row['barcode'] = material_packaging_ensure_barcode($pdo, $id);
    }
    $lot = material_packaging_last_lot_qty($pdo, $id);

    return material_inventory_enrich_packaging_row($row, $lot);
}

function material_packaging_label_cell_html(string $code, string $name): string
{
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    try {
        $svg = honey_bottle_code128_svg($code);
    } catch (Throwable) {
        $svg = '<div class="err">barcode lib missing</div>';
    }
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    // Name under barcode (packaging materials requirement).
    return '<div class="label label-product">'
        . '<div class="bc" title="' . $safeCode . '">' . $svg . '</div>'
        . '<div class="product-info" title="' . $safeName . '">' . $safeName . '</div>'
        . '</div>';
}

function material_packaging_print_sheet_html(PDO $pdo): string
{
    require_once __DIR__ . '/sku_barcodes.php';
    $items = material_inventory_list($pdo, 'packaging', true);
    $stream = [];
    foreach ($items as $row) {
        if (preg_match('/^bubble wrap roll - \d+ cm$/i', trim((string) $row['name']))) {
            continue;
        }
        if (preg_match('/^insulation foam roll - /i', trim((string) $row['name']))) {
            continue;
        }
        $id = (int) $row['id'];
        $code = material_packaging_ensure_barcode($pdo, $id);
        $name = (string) $row['name'];
        $stream[] = [
            'slots' => 1,
            'html' => material_packaging_label_cell_html($code, $name),
        ];
    }
    $count = count($stream);

    return sku_barcode_render_packed_label_sheet(
        $stream,
        'Packaging materials barcodes (' . $count . ')',
        $count
    );
}

/**
 * Who can access Packaging Materials for stock-out (and note stock-in = executives).
 *
 * @return array{stock_out: list<array{id:int,name:string,role:string,employee_id:string}>, stock_in_note: string}
 */
function material_packaging_access_roster(PDO $pdo): array
{
    require_once __DIR__ . '/auth_check.php';
    $st = $pdo->query(
        "SELECT u.id, u.name, u.employee_id, u.is_active, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.is_active = 1
         ORDER BY u.name ASC"
    );
    $stockOut = [];
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $role = (string) $row['role_name'];
        if (ims_is_executive($role)) {
            continue;
        }
        $user = [
            'id' => (int) $row['id'],
            'role' => $role,
            'dept_id' => null,
        ];
        if (!material_inventory_role_can_deduct_packaging($user)) {
            continue;
        }
        $stockOut[] = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'role' => $role,
            'employee_id' => trim((string) ($row['employee_id'] ?? '')),
        ];
    }

    return [
        'stock_out' => $stockOut,
        'stock_in_note' => 'Only executives can stock in packaging lots. Assign Stock out packaging (scan) to staff below.',
    ];
}
