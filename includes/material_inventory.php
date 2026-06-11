<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_products.php';

const MATERIAL_CATEGORIES = ['raw_material', 'packaging', 'supply', 'costing_only'];

function material_inventory_role_can_view(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'hr'], true);
}

function material_inventory_role_can_catalog(string $role): bool
{
    return $role === 'executive';
}

function material_inventory_role_can_adjust(string $role): bool
{
    return $role === 'executive';
}

function material_inventory_role_can_request(string $role): bool
{
    return $role === 'prod_manager' || $role === 'hr';
}

function material_inventory_role_can_request_packaging(string $role): bool
{
    return $role === 'hr';
}

function material_inventory_role_can_set_packaging(string $role): bool
{
    return $role === 'executive' || $role === 'hr';
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
    $sql = 'SELECT s.id, s.name, s.dept_id, s.unit, s.category, s.on_hand, s.is_active,
                   s.current_price, s.supplier_id, s.supplier_name, s.supplier_code,
                   d.name AS dept_name,
                   (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
            FROM stocks s
            INNER JOIN departments d ON d.id = s.dept_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY s.category ASC, s.name ASC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['on_hand'] = round((float) ($row['on_hand'] ?? 0), 2);
        $row['current_price'] = round((float) ($row['current_price'] ?? 0), 2);
        $row['can_adjust'] = (string) $row['category'] === 'supply';
        $row['can_set_packaging'] = (string) $row['category'] === 'packaging';
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
    if ($name === '' || $deptId < 1) {
        throw new RuntimeException('Name and department are required.');
    }
    $dup = $pdo->prepare(
        'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND category = ? AND (supplier_id IS NULL) LIMIT 1'
    );
    $dup->execute([$deptId, $name, $category]);
    if ($dup->fetch()) {
        throw new RuntimeException('A stock with this name already exists in this department.');
    }
    $ins = $pdo->prepare(
        'INSERT INTO stocks (name, dept_id, unit, category, on_hand, is_active, current_price, created_by)
         VALUES (?, ?, ?, ?, 0, 1, 0, ?)'
    );
    $ins->execute([$name, $deptId, $unit, $category, $userId]);
    $id = (int) $pdo->lastInsertId();
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
 * Parse `lot: ₱500.00/100pcs` batch labels written by stock-in.
 *
 * @return array{total: float, qty: float}|null
 */
function material_packaging_parse_lot_batch_label(string $label): ?array
{
    if (!preg_match('/^lot:\s*[^\d]*([\d,]+\.?\d*)\s*\/\s*([\d,]+\.?\d*)\s*pcs/iu', $label, $m)) {
        return null;
    }

    return [
        'total' => (float) str_replace(',', '', $m[1]),
        'qty' => (float) str_replace(',', '', $m[2]),
    ];
}

/**
 * Derive pool unit cost from lot purchase entries. Pre-lot on-hand (e.g. legacy set_values)
 * is valued at the average lot purchase unit so bad manual prices do not poison WAC.
 */
function material_packaging_pool_unit_cost_from_lots(PDO $pdo, int $stockId, float $poolOnHand): ?float
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
        $parsed = material_packaging_parse_lot_batch_label((string) $row['supplier_batch_no']);
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
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials can receive stock-in lots.');
    }
    if (!(int) ($row['is_active'] ?? 1)) {
        throw new RuntimeException('Stock item is inactive.');
    }

    $oldOnHand = round((float) ($row['on_hand'] ?? 0), 2);
    $oldPrice = round((float) ($row['current_price'] ?? 0), 6);
    $lotUnit = round($lotTotalPrice / $lotQty, 6);
    $newOnHand = round($oldOnHand + $lotQty, 2);

    $batchLabel = sprintf(
        'lot: ₱%s/%spcs',
        number_format($lotTotalPrice, 2, '.', ''),
        rtrim(rtrim(number_format($lotQty, 4, '.', ''), '0'), '.')
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

    $derivedPrice = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $newOnHand);
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
    }

    return [
        'stock_id' => $stockId,
        'on_hand' => $newOnHand,
        'entry_id' => $entryId,
    ];
}

/** @return list<array<string, mixed>> */
function material_packaging_recent_entries(PDO $pdo, int $stockId, int $limit = 25): array
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
        $parsed = material_packaging_parse_lot_batch_label($label);
        $row['entry_kind'] = str_starts_with($label, 'lot:')
            ? 'lot'
            : (str_starts_with($label, 'count_correction:') ? 'count_correction' : 'other');
        if ($parsed !== null) {
            $row['lot_total_price'] = $parsed['total'];
            $row['lot_qty'] = $parsed['qty'];
            $row['lot_unit'] = round($parsed['total'] / $parsed['qty'], 6);
        }
    }

    return $rows;
}

function material_adjustment_submit(
    PDO $pdo,
    int $stockId,
    string $action,
    float $amount,
    string $reason,
    int $userId
): int {
    if (!in_array($action, ['add', 'deduct', 'set_on_hand'], true)) {
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
        if (!in_array($action, ['add', 'deduct'], true)) {
            throw new RuntimeException('Supply requests must add or deduct quantity.');
        }
    } else {
        throw new RuntimeException('Only packaging and supply items can be requested.');
    }
    $ins = $pdo->prepare(
        'INSERT INTO material_adjustment_requests (stock_id, action, amount, reason, requested_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$stockId, $action, $amount, trim($reason), $userId]);

    return (int) $pdo->lastInsertId();
}

/** @return list<array<string, mixed>> */
function material_adjustment_list(PDO $pdo, ?int $requestedBy = null): array
{
    $sql = 'SELECT mar.*, s.name AS stock_name, s.unit, s.category,
                   u.name AS requested_by_name, r.name AS reviewed_by_name
            FROM material_adjustment_requests mar
            INNER JOIN stocks s ON s.id = mar.stock_id
            INNER JOIN users u ON u.id = mar.requested_by
            LEFT JOIN users r ON r.id = mar.reviewed_by';
    $params = [];
    if ($requestedBy !== null && $requestedBy > 0) {
        $sql .= ' WHERE mar.requested_by = ?';
        $params[] = $requestedBy;
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
        if (!in_array($action, ['add', 'deduct'], true)) {
            throw new RuntimeException('Supply requests must add or deduct.');
        }
        $result = material_inventory_adjust($pdo, $stockId, $action, $amount, $reviewerId, $reason);
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
        ];
        foreach ($names as $key => $stockName) {
            $st = $pdo->prepare(
                'SELECT s.id, s.name, s.unit, s.on_hand, s.category, d.name AS dept_name,
                        (SELECT COALESCE(SUM(se.qty), 0) FROM stock_entries se WHERE se.stock_id = s.id) AS entries_sum,
                        (SELECT MAX(se.created_at) FROM stock_entries se WHERE se.stock_id = s.id) AS last_entry_at
                 FROM stocks s
                 INNER JOIN departments d ON d.id = s.dept_id
                 WHERE s.name = ?
                 ORDER BY s.supplier_id IS NULL DESC, s.id ASC
                 LIMIT 1'
            );
            $st->execute([$stockName]);
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
