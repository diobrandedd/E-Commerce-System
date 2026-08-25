<?php

declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db.php';

/**
 * Granular permission catalog — single source of truth for keys, labels, and role presets.
 *
 * @return array<string, array{label: string, group: string, description: string, hidden?: bool}>
 */
function ims_permission_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = [
        'barcode.verify' => ['label' => 'Verify barcode', 'group' => 'General', 'description' => 'Look up and verify product barcodes.'],

        'inventory.entries.view' => ['label' => 'View inventory entries', 'group' => 'Inventory', 'description' => 'View stock entries and counts.'],
        'inventory.entries.add' => ['label' => 'Add stock entries', 'group' => 'Inventory', 'description' => 'Create new stock-in entries.'],
        'inventory.daily_count' => ['label' => 'Daily count', 'group' => 'Inventory', 'description' => 'Run daily inventory counts.'],
        'inventory.monthly_count' => ['label' => 'Monthly count', 'group' => 'Inventory', 'description' => 'Executive monthly inventory count.'],
        'inventory.requests.view' => ['label' => 'View inventory requests', 'group' => 'Inventory', 'description' => 'View packaging and supply requests queue.'],
        'inventory.requests.approve' => ['label' => 'Approve inventory requests', 'group' => 'Inventory', 'description' => 'Approve or reject inventory requests.'],
        'inventory.edit_requests.submit' => ['label' => 'Submit edit requests', 'group' => 'Inventory', 'description' => 'Submit inventory correction requests.'],
        'inventory.edit_requests.approve' => ['label' => 'Approve edit requests', 'group' => 'Inventory', 'description' => 'Approve or reject inventory edit requests.'],
        'inventory.raw.view' => ['label' => 'View raw materials', 'group' => 'Inventory', 'description' => 'View the raw-materials hub. Does not grant Paragis / BRC / Cacao / Comb.'],
        'inventory.raw.mutate' => ['label' => 'Edit raw materials', 'group' => 'Inventory', 'description' => 'Stock in and adjust raw materials.'],
        'inventory.products.view' => ['label' => 'View finished products', 'group' => 'Inventory', 'description' => 'View Honey finished stock and the products hub. Does not grant Paragis / BRC / Cacao / Comb.'],
        'inventory.leak_damage.view' => ['label' => 'View leak/damage log', 'group' => 'Inventory', 'description' => 'View finished-unit leak and damage records.'],
        'inventory.leak_damage.record' => ['label' => 'Record leak/damage', 'group' => 'Inventory', 'description' => 'Scan and record leak or damage on finished units.'],
        'inventory.check.scan' => ['label' => 'Check inventory - scan', 'group' => 'Inventory', 'description' => 'Scan finished products into the daily physical count.'],
        'inventory.check.view_all' => ['label' => 'Check inventory - full report', 'group' => 'Inventory', 'description' => 'See the full count summary, comparison, and history across all product lines.'],
        'inventory.check.manage' => ['label' => 'Check inventory - open/close count', 'group' => 'Inventory', 'description' => 'Open and close the daily physical count event.'],

        'packaging.view' => ['label' => 'View packaging materials', 'group' => 'Packaging & supplies', 'description' => 'View packaging inventory and activity.'],
        'packaging.catalog' => ['label' => 'Edit packaging catalog', 'group' => 'Packaging & supplies', 'description' => 'Create and edit packaging SKUs.'],
        'packaging.adjust' => ['label' => 'Adjust packaging', 'group' => 'Packaging & supplies', 'description' => 'Manual packaging quantity adjustments.'],
        'packaging.stock_in' => ['label' => 'Stock in packaging', 'group' => 'Packaging & supplies', 'description' => 'Receive packaging lots.'],
        'packaging.deduct' => ['label' => 'Deduct packaging', 'group' => 'Packaging & supplies', 'description' => 'Deduct packaging for production use.'],
        'packaging.set_on_hand' => ['label' => 'Set packaging on-hand', 'group' => 'Packaging & supplies', 'description' => 'Override packaging on-hand counts.'],
        'packaging.edit_price' => ['label' => 'Edit packaging price', 'group' => 'Packaging & supplies', 'description' => 'Edit packaging unit costs.'],
        'packaging.request' => ['label' => 'Request packaging', 'group' => 'Packaging & supplies', 'description' => 'Submit packaging stock requests.'],
        'supplies.view' => ['label' => 'View supplies', 'group' => 'Packaging & supplies', 'description' => 'View office and supply inventory.'],
        'supplies.stock_in' => ['label' => 'Stock in supplies', 'group' => 'Packaging & supplies', 'description' => 'Receive supply lots.'],
        'supplies.issue' => ['label' => 'Issue supplies', 'group' => 'Packaging & supplies', 'description' => 'Issue supplies to departments.'],
        'supplies.catalog' => ['label' => 'Manage supply catalog', 'group' => 'Packaging & supplies', 'description' => 'Create and edit supply SKUs.'],
        'supplies.request' => ['label' => 'Request supplies', 'group' => 'Packaging & supplies', 'description' => 'Submit supply issue requests.'],

        'honey.view' => ['label' => 'View honey inventory', 'group' => 'Honey', 'description' => 'View honey warehouse and pipeline.'],
        'honey.mutate' => ['label' => 'Edit honey inventory', 'group' => 'Honey', 'description' => 'Stock in, adjust, and mutate honey inventory.'],
        'honey.ops' => ['label' => 'Honey pipeline ops', 'group' => 'Honey', 'description' => 'Run honey warehouse, production, and packaging pipeline.'],
        'honey.executive_edit' => ['label' => 'Honey executive edit', 'group' => 'Honey', 'description' => 'Direct executive edits on honey records.'],
        'honey.pricing.view_cost' => ['label' => 'View honey costs', 'group' => 'Honey', 'description' => 'View supplier costs and pricing data.'],
        'honey.bottle.request' => ['label' => 'Request bottle labels', 'group' => 'Honey', 'description' => 'Request honey bottle barcode labels.'],
        'honey.bottle.generate' => ['label' => 'Generate bottle labels', 'group' => 'Honey', 'description' => 'Generate and print honey bottle labels (CSR).'],
        'honey.bottle.scan' => ['label' => 'Scan bottle barcodes', 'group' => 'Honey', 'description' => 'Scan honey bottle barcodes in production.'],

        'dept.paragis.view' => ['label' => 'View Paragis', 'group' => 'Paragis', 'description' => 'View Dried Paragis raw and Paragis Tea finished inventory.'],
        'dept.paragis.produce' => ['label' => 'Produce Paragis', 'group' => 'Paragis', 'description' => 'Stock in bags, use raw bags, and make tea bags.'],
        'dept.paragis.barcodes' => ['label' => 'Paragis pouch barcodes', 'group' => 'Paragis', 'description' => 'Request, print, and scan Paragis pouch barcodes.'],

        'dept.brc.view' => ['label' => 'View Black Rice Coffee', 'group' => 'Black Rice Coffee', 'description' => 'View Black Rice raw and finished pouch inventory.'],
        'dept.brc.produce' => ['label' => 'Produce Black Rice Coffee', 'group' => 'Black Rice Coffee', 'description' => 'Stock in bags and produce BRC pouches.'],
        'dept.brc.barcodes' => ['label' => 'BRC pouch barcodes', 'group' => 'Black Rice Coffee', 'description' => 'Request, print, and scan BRC pouch barcodes.'],

        'dept.cacao.view' => ['label' => 'View Jar Cacao', 'group' => 'Jar Cacao', 'description' => 'View cacao raw and finished jar inventory.'],
        'dept.cacao.produce' => ['label' => 'Produce Jar Cacao', 'group' => 'Jar Cacao', 'description' => 'Stock in bags and produce cacao jars.'],
        'dept.cacao.barcodes' => ['label' => 'Cacao jar barcodes', 'group' => 'Jar Cacao', 'description' => 'Request, print, and scan cacao jar barcodes.'],

        'dept.comb.view' => ['label' => 'View Honey Comb', 'group' => 'Honey Comb', 'description' => 'View Honey Comb raw and finished jar inventory.'],
        'dept.comb.produce' => ['label' => 'Produce Honey Comb', 'group' => 'Honey Comb', 'description' => 'Stock in comb bags and produce comb jars.'],
        'dept.comb.barcodes' => ['label' => 'Honey Comb jar barcodes', 'group' => 'Honey Comb', 'description' => 'Request, print, and scan Honey Comb jar barcodes.'],

        // Legacy catch-alls — kept for existing grants; hidden from the Staff permissions modal.
        'honey.dept_produce' => ['label' => 'Dept production (legacy)', 'group' => 'Honey', 'description' => 'Legacy: all dept production lines.', 'hidden' => true],
        'dept.pouch_barcodes' => ['label' => 'Dept pouch barcodes (legacy)', 'group' => 'Production', 'description' => 'Legacy: all dept pouch/jar barcodes.', 'hidden' => true],

        'packing.materials.view' => ['label' => 'View packing materials', 'group' => 'Packing & deliveries', 'description' => 'View fulfillment packing materials staging.'],
        'packing.materials.issue' => ['label' => 'Issue packing materials', 'group' => 'Packing & deliveries', 'description' => 'Issue packing materials to parcels.'],
        'packing.on_hold.view' => ['label' => 'View on-hold parcels', 'group' => 'Packing & deliveries', 'description' => 'View parcels on hold in packing.'],

        'deliveries.view' => ['label' => 'View deliveries', 'group' => 'Packing & deliveries', 'description' => 'View delivery batches and scan queue.'],
        'deliveries.pack_scan' => ['label' => 'Pack scan', 'group' => 'Packing & deliveries', 'description' => 'Scan parcels and attach packing photos.'],
        'deliveries.ship' => ['label' => 'Ship parcels', 'group' => 'Packing & deliveries', 'description' => 'Mark parcels as shipped.'],
        'deliveries.review' => ['label' => 'Review deliveries', 'group' => 'Packing & deliveries', 'description' => 'Review delivery photos and batches.'],
        'deliveries.cancel_restock' => ['label' => 'Cancel & restock', 'group' => 'Packing & deliveries', 'description' => 'Cancel shipped parcels and restock inventory.'],
        'deliveries.delete' => ['label' => 'Delete delivery items', 'group' => 'Packing & deliveries', 'description' => 'Delete delivery batch items (executive).'],

        'csr.online_orders' => ['label' => 'Online orders generator', 'group' => 'CSR & online', 'description' => 'Access waybill stamper and online orders tools.'],
        'csr.parcels.view' => ['label' => 'View online parcels', 'group' => 'CSR & online', 'description' => 'View and search online order parcels.'],
        'csr.parcels.edit_items' => ['label' => 'Edit parcel items', 'group' => 'CSR & online', 'description' => 'Edit line items on online parcels.'],
        'csr.reconcile.partner_sold' => ['label' => 'Partner sold reconcile', 'group' => 'CSR & online', 'description' => 'Reconcile partner-sold orders.'],
        'csr.reconcile.courier_soa' => ['label' => 'Courier SOA reconcile', 'group' => 'CSR & online', 'description' => 'Reconcile courier statements of account.'],
        'csr.historical_import' => ['label' => 'Historical orders import', 'group' => 'CSR & online', 'description' => 'Import historical order data.'],

        'rts.view' => ['label' => 'View RTS', 'group' => 'RTS', 'description' => 'View RTS pool, records, and dashboard.'],
        'rts.record' => ['label' => 'Record RTS cases', 'group' => 'RTS', 'description' => 'Create and edit RTS case records.'],
        'rts.admin' => ['label' => 'RTS admin', 'group' => 'RTS', 'description' => 'RTS administrative actions.'],
        'rts.damaged.use' => ['label' => 'Use damaged RTS stock', 'group' => 'RTS', 'description' => 'Use damaged inventory from RTS pool.'],
        'rts.inventory.set' => ['label' => 'Set RTS inventory', 'group' => 'RTS', 'description' => 'Executive set honey/other RTS inventory levels.'],
        'rts.sync.run' => ['label' => 'Run RTS sync', 'group' => 'RTS', 'description' => 'Run RTS inventory sync jobs.'],
        'rts.barcode_labels' => ['label' => 'RTS barcode labels', 'group' => 'RTS', 'description' => 'Generate and print barcodes for unlabeled RTS units (batch generate).'],

        'sales.view' => ['label' => 'View sales', 'group' => 'Sales & AR', 'description' => 'View sales reports and summaries.'],
        'sales.bulk_orders' => ['label' => 'Bulk honey orders', 'group' => 'Sales & AR', 'description' => 'Manage bulk honey order batches.'],
        'pos.sell' => ['label' => 'Walk-in POS', 'group' => 'Sales & AR', 'description' => 'Open the POS terminal and complete walk-in sales, including 10%/20% discounts.'],
        'pos.discounts.approve' => ['label' => 'Approve POS discounts', 'group' => 'Sales & AR', 'description' => 'Approve leftover POS discount requests. New sales apply 10%/20% at checkout.'],
        'ar.take' => ['label' => 'Take AR credits', 'group' => 'Sales & AR', 'description' => 'Record staff AR credit purchases.'],
        'ar.view_all' => ['label' => 'View all AR credits', 'group' => 'Sales & AR', 'description' => 'View all staff AR credit balances.'],
        'ar.mark_paid' => ['label' => 'Mark AR paid/unpaid', 'group' => 'Sales & AR', 'description' => 'Mark AR credits as paid or unpaid.'],

        'reports.view' => ['label' => 'View reports', 'group' => 'Executive', 'description' => 'Access executive reports.'],
        'costing.view' => ['label' => 'View product costing', 'group' => 'Executive', 'description' => 'View product costing and formulas.'],
        'costing.manage' => ['label' => 'Manage product costing', 'group' => 'Executive', 'description' => 'Edit costing formulas and products.'],
        'suppliers.view' => ['label' => 'View suppliers', 'group' => 'Executive', 'description' => 'View supplier master list.'],
        'suppliers.manage' => ['label' => 'Manage suppliers', 'group' => 'Executive', 'description' => 'Create and edit suppliers.'],
        'settings.manage' => ['label' => 'System settings', 'group' => 'Executive', 'description' => 'Manage POS pins and system settings.'],
        'staff.manage' => ['label' => 'Staff management', 'group' => 'Executive', 'description' => 'Create users and manage permissions.'],
    ];

    return $catalog;
}

/** @return list<string> */
function ims_all_permission_keys(): array
{
    return array_keys(ims_permission_catalog());
}

/**
 * Product-line slug used in permission keys (pt → paragis, jar_cacao → cacao).
 *
 * @return array<string, string> product_key => line slug
 */
function ims_dept_line_map(): array
{
    return [
        'pt' => 'paragis',
        'brc' => 'brc',
        'jar_cacao' => 'cacao',
        'honey_comb' => 'comb',
    ];
}

function ims_dept_permission_key(string $productKey, string $action): ?string
{
    $line = ims_dept_line_map()[strtolower(trim($productKey))] ?? null;
    if ($line === null || !in_array($action, ['view', 'produce', 'barcodes'], true)) {
        return null;
    }

    return 'dept.' . $line . '.' . $action;
}

/**
 * @param list<string> $lines paragis|brc|cacao|comb
 * @param list<string> $actions view|produce|barcodes
 * @return list<string>
 */
function ims_dept_line_permission_keys(array $lines, array $actions): array
{
    $out = [];
    foreach ($lines as $line) {
        foreach ($actions as $action) {
            $out[] = 'dept.' . $line . '.' . $action;
        }
    }

    return $out;
}

/** Lines a role preset should grant for this department assignment. */
function ims_preset_dept_lines(?string $deptKey): array
{
    return match ($deptKey) {
        'paragis' => ['paragis'],
        'cacao_brc' => ['brc', 'cacao'],
        'honey' => ['comb'],
        'csr' => [],
        default => ['paragis', 'brc', 'cacao', 'comb'],
    };
}

/** @return list<string> */
function ims_permission_preset_for_role(string $role, ?string $deptKey = null): array
{
    $deptKey = $deptKey !== null ? strtolower(trim($deptKey)) : null;
    $isCsr = $deptKey === 'csr';
    $deptLines = ims_preset_dept_lines($deptKey);

    if ($role === 'executive') {
        return ims_all_permission_keys();
    }

    $base = match ($role) {
        'prod_manager' => array_merge([
            'barcode.verify',
            'inventory.entries.view', 'inventory.daily_count', 'inventory.edit_requests.submit',
            'inventory.raw.view', 'inventory.products.view', 'inventory.leak_damage.view', 'inventory.leak_damage.record',
            'inventory.check.scan', 'inventory.check.view_all', 'inventory.check.manage',
            'packaging.view', 'packaging.stock_in', 'packaging.deduct',
            'supplies.view',
            'honey.view', 'honey.mutate', 'honey.ops', 'honey.pricing.view_cost',
            'honey.bottle.request', 'honey.bottle.scan',
        ], ims_dept_line_permission_keys(['paragis', 'brc', 'cacao', 'comb'], ['view', 'produce', 'barcodes']), [
            'packing.materials.view', 'packing.materials.issue', 'packing.on_hold.view',
            'deliveries.view', 'deliveries.pack_scan', 'deliveries.ship', 'deliveries.review', 'deliveries.cancel_restock',
            'csr.parcels.view',
            'csr.reconcile.partner_sold', 'csr.reconcile.courier_soa', 'csr.historical_import',
            'rts.view', 'rts.record', 'rts.damaged.use', 'rts.sync.run', 'rts.admin', 'rts.barcode_labels',
            'sales.view', 'reports.view',
            'ar.take', 'ar.view_all',
            'suppliers.view',
        ]),
        'hr' => [
            'barcode.verify',
            'inventory.entries.view', 'inventory.requests.view',
            'inventory.check.scan',
            'packaging.view', 'packaging.stock_in', 'packaging.request', 'packaging.set_on_hand',
            'supplies.view', 'supplies.stock_in', 'supplies.issue', 'supplies.catalog', 'supplies.request',
            'honey.view',
            'packing.on_hold.view',
            'rts.view',
            'ar.take',
        ],
        'tl' => array_merge([
            'barcode.verify',
            'inventory.raw.view', 'inventory.products.view', 'inventory.leak_damage.view', 'inventory.leak_damage.record',
            'inventory.check.scan',
            'honey.view', 'honey.ops',
            'honey.bottle.request', 'honey.bottle.scan',
            'suppliers.view',
            'packing.materials.view', 'packing.materials.issue',
            'rts.view', 'rts.record', 'rts.damaged.use', 'rts.barcode_labels',
            'ar.take',
        ], ims_dept_line_permission_keys($deptLines, ['view', 'produce', 'barcodes']), $isCsr ? [
            'csr.online_orders', 'csr.parcels.view', 'csr.parcels.edit_items',
            'csr.reconcile.partner_sold', 'csr.reconcile.courier_soa', 'csr.historical_import',
            'honey.bottle.generate',
            'packing.on_hold.view',
        ] : []),
        'staff' => array_merge([
            'barcode.verify',
            'inventory.raw.view', 'inventory.products.view', 'inventory.leak_damage.view', 'inventory.leak_damage.record',
            'inventory.check.scan',
            'honey.view',
            'honey.bottle.request', 'honey.bottle.scan',
            'packing.materials.view', 'packing.materials.issue',
            'deliveries.view', 'deliveries.pack_scan', 'deliveries.ship', 'deliveries.review', 'deliveries.cancel_restock',
            'rts.view',
            'ar.take',
        ], ims_dept_line_permission_keys($deptLines, ['view', 'barcodes']), $isCsr ? [
            'csr.online_orders', 'csr.parcels.view', 'csr.parcels.edit_items',
            'csr.reconcile.partner_sold', 'csr.reconcile.courier_soa', 'csr.historical_import',
            'honey.bottle.generate',
            'packing.on_hold.view',
        ] : []),
        default => [],
    };

    return array_values(array_unique(array_filter(
        $base,
        static fn(string $k): bool => isset(ims_permission_catalog()[$k])
    )));
}

/** @var array<int, list<string>> */
$GLOBALS['_ims_user_perm_cache'] = [];

/** @return list<string> */
function ims_user_permissions(int $userId): array
{
    if ($userId < 1) {
        return [];
    }
    if (isset($GLOBALS['_ims_user_perm_cache'][$userId])) {
        return $GLOBALS['_ims_user_perm_cache'][$userId];
    }

    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare('SELECT permission_key FROM user_permissions WHERE user_id = ? ORDER BY permission_key ASC');
        $st->execute([$userId]);
        $keys = array_map(
            static fn(array $r): string => (string) $r['permission_key'],
            $st->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    } catch (Throwable) {
        $keys = [];
    }

    $GLOBALS['_ims_user_perm_cache'][$userId] = $keys;

    return $keys;
}

function ims_user_permissions_clear_cache(?int $userId = null): void
{
    if ($userId === null) {
        $GLOBALS['_ims_user_perm_cache'] = [];
        return;
    }
    unset($GLOBALS['_ims_user_perm_cache'][$userId]);
}

/** @param array{id:int,role:string,dept_id:?int} $user */
function ims_user_can(array $user, string $key): bool
{
    if (($user['role'] ?? '') === IMS_ROLE_EXECUTIVE) {
        return true;
    }
    if (!isset(ims_permission_catalog()[$key])) {
        return false;
    }
    $granted = ims_user_permissions((int) $user['id']);
    if (in_array($key, $granted, true)) {
        return true;
    }
    if (preg_match('/^dept\.(paragis|brc|cacao|comb)\.(view|produce|barcodes)$/', $key, $m) !== 1) {
        return false;
    }
    $action = $m[2];
    if ($action === 'produce' && in_array('honey.dept_produce', $granted, true)) {
        return true;
    }
    if ($action === 'barcodes' && in_array('dept.pouch_barcodes', $granted, true)) {
        return true;
    }

    return false;
}

function ims_user_can_dept_line(array $user, string $productKey, string $action): bool
{
    $key = ims_dept_permission_key($productKey, $action);

    return $key !== null && ims_user_can($user, $key);
}

/** Expand legacy catch-all grants so the permissions modal shows per-line checkboxes. */
function ims_user_permissions_expanded(int $userId): array
{
    $keys = ims_user_permissions($userId);
    $set = array_fill_keys($keys, true);
    $lines = ['paragis', 'brc', 'cacao', 'comb'];
    if (isset($set['honey.dept_produce'])) {
        foreach ($lines as $line) {
            $set['dept.' . $line . '.produce'] = true;
        }
    }
    if (isset($set['dept.pouch_barcodes'])) {
        foreach ($lines as $line) {
            $set['dept.' . $line . '.barcodes'] = true;
        }
    }
    $catalog = ims_permission_catalog();

    return array_values(array_filter(
        array_keys($set),
        static fn(string $k): bool => isset($catalog[$k]) && empty($catalog[$k]['hidden'])
    ));
}

/** @return list<array{key: string, label: string, group: string, description: string}> */
function ims_permission_catalog_list(): array
{
    $out = [];
    foreach (ims_permission_catalog() as $key => $meta) {
        $out[] = [
            'key' => $key,
            'label' => $meta['label'],
            'group' => $meta['group'],
            'description' => $meta['description'],
        ];
    }

    return $out;
}

/** @return list<array{group: string, permissions: list<array{key: string, label: string, description: string}>}> */
function ims_permission_catalog_grouped(): array
{
    $groups = [];
    foreach (ims_permission_catalog() as $key => $meta) {
        if (!empty($meta['hidden'])) {
            continue;
        }
        $g = $meta['group'];
        if (!isset($groups[$g])) {
            $groups[$g] = [];
        }
        $groups[$g][] = [
            'key' => $key,
            'label' => $meta['label'],
            'description' => $meta['description'],
        ];
    }

    $out = [];
    foreach ($groups as $group => $permissions) {
        if ($permissions === []) {
            continue;
        }
        $out[] = ['group' => $group, 'permissions' => $permissions];
    }

    return $out;
}

/** @param list<string> $keys */
function ims_user_permissions_set(int $userId, array $keys, ?int $grantedBy = null): void
{
    $catalog = ims_permission_catalog();
    $valid = [];
    foreach ($keys as $key) {
        $key = trim((string) $key);
        if ($key !== '' && isset($catalog[$key])) {
            $valid[$key] = true;
        }
    }
    $validKeys = array_keys($valid);

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM user_permissions WHERE user_id = ?');
        $del->execute([$userId]);
        if ($validKeys !== []) {
            $ins = $pdo->prepare(
                'INSERT INTO user_permissions (user_id, permission_key, granted_by) VALUES (?, ?, ?)'
            );
            foreach ($validKeys as $key) {
                $ins->execute([$userId, $key, $grantedBy]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    ims_user_permissions_clear_cache($userId);
}

function ims_user_permission_count(int $userId): int
{
    return count(ims_user_permissions($userId));
}

/** Resolve department key for a user row. */
function ims_user_dept_key(array $user): ?string
{
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    if ($deptId < 1) {
        return null;
    }
    try {
        $st = ims_pdo()->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
        $st->execute([$deptId]);
        $name = (string) ($st->fetchColumn() ?: '');

        return $name !== '' ? strtolower($name) : null;
    } catch (Throwable) {
        return null;
    }
}

function ims_user_is_csr_dept(array $user): bool
{
    return ims_user_dept_key($user) === 'csr';
}
