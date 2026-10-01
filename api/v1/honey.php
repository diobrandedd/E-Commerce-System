<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/honey_inventory.php';
require_once dirname(__DIR__, 2) . '/includes/honey_pricing.php';
require_once dirname(__DIR__, 2) . '/includes/dept_inventory.php';
require_once dirname(__DIR__, 2) . '/includes/dept_production.php';
require_once dirname(__DIR__, 2) . '/includes/dept_pricing.php';
require_once dirname(__DIR__, 2) . '/includes/inventory_edit_log.php';
require_once dirname(__DIR__, 2) . '/includes/honey_warehouse_barcodes.php';

api_dispatch([
    'warehouse_list' => 'api_honey_warehouse_list',
    'warehouse_stock_in' => 'api_honey_warehouse_stock_in',
    'warehouse_stock_out' => 'api_honey_warehouse_stock_out',
    'warehouse_barcode_request_list' => 'api_honey_warehouse_barcode_request_list',
    'warehouse_barcode_request_create' => 'api_honey_warehouse_barcode_request_create',
    'warehouse_barcode_request_cancel' => 'api_honey_warehouse_barcode_request_cancel',
    'warehouse_barcode_generate' => 'api_honey_warehouse_barcode_generate',
    'warehouse_barcode_generate_bulk' => 'api_honey_warehouse_barcode_generate_bulk',
    'warehouse_barcode_print' => 'api_honey_warehouse_barcode_print',
    'warehouse_barcode_lookup' => 'api_honey_warehouse_barcode_lookup',
    'warehouse_barcode_stock_out' => 'api_honey_warehouse_barcode_stock_out',
    'production_list' => 'api_honey_production_list',
    'production_variant_list' => 'api_honey_production_variant_list',
    'production_voided_list' => 'api_honey_production_voided_list',
    'production_void' => 'api_honey_production_void',
    'production_unvoid' => 'api_honey_production_unvoid',
    'production_stock_out' => 'api_honey_production_stock_out',
    'packaging_list' => 'api_honey_packaging_list',
    'packaging_total_inventory' => 'api_honey_packaging_total_inventory',
    'packaging_variant_pools' => 'api_honey_packaging_variant_pools',
    'packaging_stock_out' => 'api_honey_packaging_stock_out',
    'traceability' => 'api_honey_traceability',
    'correction_submit' => 'api_honey_correction_submit',
    'correction_list' => 'api_honey_correction_list',
    'correction_row_options' => 'api_honey_correction_row_options',
    'correction_approve' => 'api_honey_correction_approve',
    'correction_reject' => 'api_honey_correction_reject',
    'correction_pending_count' => 'api_honey_correction_pending_count',
    'executive_adjust' => 'api_honey_executive_adjust',
    'executive_edit' => 'api_honey_executive_edit',
    'correction_unread_count' => 'api_honey_correction_unread_count',
    'correction_unread_list' => 'api_honey_correction_unread_list',
    'correction_mark_viewed' => 'api_honey_correction_mark_viewed',
    'dept_list' => 'api_honey_dept_list',
    'dept_catalog' => 'api_honey_dept_catalog',
    'dept_stock_in' => 'api_honey_dept_stock_in',
    'dept_raw_detail' => 'api_honey_dept_raw_detail',
    'dept_raw_stock_in' => 'api_honey_dept_raw_stock_in',
    'dept_raw_stock_out' => 'api_honey_dept_raw_stock_out',
    'dept_raw_produce' => 'api_honey_dept_raw_produce',
    'dept_pt_use_raw_bags' => 'api_honey_dept_pt_use_raw_bags',
    'dept_pt_make_tea_bags' => 'api_honey_dept_pt_make_tea_bags',
    'dept_pt_pouch' => 'api_honey_dept_pt_pouch',
    'dept_brc_produce' => 'api_honey_dept_brc_produce',
    'dept_jar_produce' => 'api_honey_dept_jar_produce',
    'dept_comb_produce' => 'api_honey_dept_comb_produce',
    'dept_wip_waste' => 'api_honey_dept_wip_waste',
    'dept_production_preview' => 'api_honey_dept_production_preview',
    'dept_production_log' => 'api_honey_dept_production_log',
    'dept_production_daily_record' => 'api_honey_dept_production_daily_record',
    'dept_add_row' => 'api_honey_dept_add_row',
    'supplier_honey_prices' => 'api_honey_supplier_honey_prices',
    'supplier_honey_prices_save' => 'api_honey_supplier_honey_prices_save',
    'supplier_dept_prices' => 'api_honey_supplier_dept_prices',
    'supplier_dept_prices_save' => 'api_honey_supplier_dept_prices_save',
    'warehouse_pending_prices' => 'api_honey_warehouse_pending_prices',
    'warehouse_set_unit_cost' => 'api_honey_warehouse_set_unit_cost',
    'pricing_sync_stocks' => 'api_honey_pricing_sync_stocks',
    'edit_log_list' => 'api_honey_edit_log_list',
    'seed_production_row' => 'api_honey_seed_production_row',
    'seed_packaging_row' => 'api_honey_seed_packaging_row',
    'dept_raw_set_bags' => 'api_honey_dept_raw_set_bags',
    'dept_raw_executive_set' => 'api_honey_dept_raw_executive_set',
]);

function api_honey_require_view(): array
{
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!honey_role_can_view($user) && !honey_role_can_mutate($user)) {
        api_fail('Forbidden.', 403);
    }
    // Operations staff and dept team leaders are read-only and limited to their own
    // department's raw materials + finished products.
    if (in_array($role, ['staff', 'tl'], true)) {
        honey_assert_staff_dept_scope($user);
    }

    return $user;
}

/** Resolve the logged-in user's department key (e.g. honey, paragis, cacao_brc). */
function honey_resolve_user_dept_key(array $user): ?string
{
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    if ($deptId < 1) {
        return null;
    }
    try {
        $st = ims_pdo()->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
        $st->execute([$deptId]);
        $name = $st->fetchColumn();

        return $name !== false ? strtolower((string) $name) : null;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Determine which department a honey.php view request targets, from the
 * `dept`, `slug`, or `product_key` query parameter. Returns null when it
 * cannot be inferred (paramless dept actions handled by the caller).
 */
function honey_request_target_dept_key(): ?string
{
    $dept = strtolower(trim((string) ($_GET['dept'] ?? '')));
    if ($dept !== '') {
        return $dept;
    }
    $slug = strtolower(trim((string) ($_GET['slug'] ?? '')));
    if ($slug !== '') {
        return match ($slug) {
            'paragis' => 'paragis',
            'black-rice', 'cacao', 'cacao-brc', 'black-rice-coffee', 'cacao-tablets' => 'cacao_brc',
            'honey', 'honey-comb' => 'honey',
            default => $slug,
        };
    }
    $productKey = strtolower(trim((string) ($_GET['product_key'] ?? '')));
    if ($productKey !== '') {
        return match ($productKey) {
            'pt' => 'paragis',
            'brc', 'jar_cacao' => 'cacao_brc',
            'honey_comb' => 'honey',
            default => 'honey',
        };
    }

    return null;
}

/**
 * Staff/TL may open a target department when it is their home dept, or when an
 * executive granted the matching line permission (honey.view / dept.*.view).
 */
function honey_staff_may_access_target_dept(array $user, string $targetDept): bool
{
    $targetDept = strtolower(trim($targetDept));
    $userDept = honey_resolve_user_dept_key($user);
    if ($userDept !== null && $userDept === $targetDept) {
        return true;
    }

    return match ($targetDept) {
        'honey' => ims_user_can($user, 'honey.view')
            || ims_user_can($user, 'honey.ops')
            || ims_user_can($user, 'honey.mutate')
            || ims_user_can_dept_line($user, 'honey_comb', 'view'),
        'paragis' => ims_user_can_dept_line($user, 'pt', 'view'),
        'cacao_brc' => ims_user_can_dept_line($user, 'brc', 'view')
            || ims_user_can_dept_line($user, 'jar_cacao', 'view'),
        'csr' => ims_user_can($user, 'honey.view') || ims_user_can($user, 'honey.bottle.generate'),
        default => false,
    };
}

/** 403 unless a dept-scoped ops user (staff / tl) stays within authorized scope. */
function honey_assert_staff_dept_scope(array $user): void
{
    $action = (string) ($_GET['action'] ?? '');
    // Honey warehouse / production / packaging lists — gated by honey.view already.
    // Do not also require home dept = honey (Staff Management may grant honey.* cross-dept).
    $honeyOnly = ['warehouse_list', 'production_list', 'production_variant_list', 'production_voided_list', 'packaging_list', 'packaging_total_inventory', 'packaging_variant_pools', 'traceability'];
    if (in_array($action, $honeyOnly, true)) {
        if (
            !ims_user_can($user, 'honey.view')
            && !ims_user_can($user, 'honey.mutate')
            && !ims_user_can($user, 'honey.ops')
            && !ims_user_can($user, 'honey.bottle.request')
            && !ims_user_can($user, 'honey.bottle.scan')
            && !ims_user_can($user, 'honey.bottle.generate')
        ) {
            api_fail('Forbidden.', 403);
        }

        return;
    }

    $target = honey_request_target_dept_key();
    if ($target === null) {
        return;
    }
    if (!honey_staff_may_access_target_dept($user, $target)) {
        api_fail('Out of scope for your department. Ask an executive to grant access for that line, or open only departments you are assigned to.', 403);
    }
}

function api_honey_require_mutate(): array
{
    $user = api_require_ims_user();
    if (!honey_role_can_mutate($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

/**
 * Honey warehouse / production → packaging / packaging sell / correction submit.
 *
 * @return array<string, mixed>
 */
function api_honey_require_honey_ops(): array
{
    $user = api_require_ims_user();
    if (!honey_role_can_honey_ops($user)) {
        api_fail('Forbidden.', 403);
    }
    // honey.ops permission is enough (including TL assigned to another department).

    return $user;
}

/**
 * Stock-in / Use raw bags / Pouch / Produce for dept raw materials.
 * Team leaders (IMS role `tl`) are allowed only within their assigned department.
 *
 * @param string|null $productKey Catalog key (pt, brc, jar_cacao, …) for TL scope check
 * @return array<string, mixed>
 */
function api_honey_require_dept_produce(?string $productKey = null): array
{
    $user = api_require_ims_user();
    if (!honey_role_can_dept_produce($user, $productKey)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_honey_warehouse_list(): void
{
    api_method_allowed(['GET']);
    $user = api_honey_require_view();
    $role = (string) $user['role'];
    $pdo = ims_pdo();
    $costCol = honey_warehouse_unit_cost_column($pdo);
    $cols = $costCol !== null
        ? "id, supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty,
           {$costCol}, supplier_price_id, cost_override, created_at"
        : 'id, supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty, created_at';
    $st = $pdo->query(
        "SELECT {$cols} FROM honey_warehouse_rows
         ORDER BY (current_qty > 0) DESC, arrival_date DESC, id DESC"
    );
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($costCol !== null) {
        $rows = array_map(
            static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),
            $rows
        );
    }
    api_ok([
        'rows' => $rows,
        'can_view_cost' => honey_pricing_role_can_view_cost($user),
        'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,
    ]);
}

function api_honey_warehouse_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_honey_ops();
    $monthly = !empty($input['monthly_inventory']) || strtolower(trim((string) ($input['log_source'] ?? ''))) === 'monthly_inventory';
    if ($monthly) {
        require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
        api_require_admin_override_password($input);
    }
    $variants = is_array($input['variants'] ?? null) ? $input['variants'] : [];
    try {
        $result = honey_warehouse_stock_in(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['supplier_id'] ?? 0),
            (string) ($input['arrival_date'] ?? date('Y-m-d')),
            $variants
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_warehouse_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_honey_ops();
    try {
        $result = honey_warehouse_stock_out(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['warehouse_row_id'] ?? 0),
            (float) ($input['qty'] ?? 0)
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_warehouse_barcode_request_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    try {
        api_ok([
            'rows' => honey_warehouse_barcode_request_list(
                ims_pdo(),
                isset($_GET['status']) ? (string) $_GET['status'] : null
            ),
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_warehouse_barcode_request_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'honey.warehouse_barcode.request')) {
        api_fail('You do not have permission to request warehouse barcodes.', 403);
    }
    try {
        api_ok([
            'request' => honey_warehouse_barcode_request_create(
                ims_pdo(),
                (int) $user['id'],
                (int) ($input['supplier_id'] ?? 0),
                (string) ($input['arrival_date'] ?? ''),
                (int) ($input['quantity'] ?? 0)
            ),
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_warehouse_barcode_request_cancel(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'honey.warehouse_barcode.request')) {
        api_fail('You do not have permission to cancel warehouse barcode requests.', 403);
    }
    $id = (int) ($input['request_id'] ?? 0);
    $st = ims_pdo()->prepare(
        'UPDATE honey_warehouse_barcode_requests
         SET status = "cancelled"
         WHERE id = ? AND status = "pending"'
    );
    $st->execute([$id]);
    if ($st->rowCount() < 1) {
        api_fail('Only pending warehouse barcode requests can be cancelled.', 422);
    }
    api_ok(['cancelled' => true, 'request_id' => $id]);
}

function api_honey_warehouse_barcode_generate(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'honey.warehouse_barcode.generate')) {
        api_fail('You do not have permission to generate warehouse barcodes.', 403);
    }
    try {
        $request = honey_warehouse_barcode_request_generate(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['request_id'] ?? 0)
        );
        api_ok([
            'request' => $request,
            'print_url' => '/rts/api/v1/honey.php?action=warehouse_barcode_print&request_id='
                . (int) $request['id'],
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_warehouse_barcode_generate_bulk(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'honey.warehouse_barcode.generate')) {
        api_fail('You do not have permission to generate warehouse barcodes.', 403);
    }
    $rawIds = $input['request_ids'] ?? [];
    if (!is_array($rawIds)) {
        api_fail('request_ids must be an array.', 400);
    }
    try {
        api_ok(honey_warehouse_barcode_request_generate_many(
            ims_pdo(),
            (int) $user['id'],
            $rawIds
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_warehouse_barcode_print(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'honey.warehouse_barcode.generate')) {
        api_fail('Forbidden.', 403);
    }
    try {
        $pdo = ims_pdo();
        $multiRaw = trim((string) ($_GET['request_ids'] ?? ''));
        if ($multiRaw !== '') {
            $ids = array_map('intval', preg_split('/\s*,\s*/', $multiRaw) ?: []);
            $html = honey_warehouse_barcode_combined_print_sheet_html($pdo, $ids);
        } else {
            $request = honey_warehouse_barcode_request_fetch($pdo, (int) ($_GET['request_id'] ?? 0));
            if ($request === null) {
                api_fail('Warehouse barcode request not found.', 404);
            }
            if ((string) $request['status'] === 'cancelled') {
                api_fail('Cancelled warehouse barcode requests cannot be printed.', 422);
            }
            $html = honey_warehouse_barcode_print_sheet_html(
                (string) $request['supplier_code'],
                (int) $request['quantity']
            );
        }
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function api_honey_warehouse_barcode_lookup(): void
{
    api_method_allowed(['GET']);
    api_honey_require_honey_ops();
    try {
        api_ok([
            'code' => honey_warehouse_barcode_normalize((string) ($_GET['code'] ?? '')),
            'candidates' => honey_warehouse_barcode_lookup(
                ims_pdo(),
                (string) ($_GET['code'] ?? '')
            ),
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_warehouse_barcode_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_honey_ops();
    try {
        api_ok([
            'stock_out' => honey_warehouse_barcode_stock_out(
                ims_pdo(),
                (int) $user['id'],
                (string) ($input['code'] ?? ''),
                (int) ($input['supplier_id'] ?? 0),
                (string) ($input['arrival_date'] ?? ''),
                (string) ($input['variant'] ?? ''),
                (float) ($input['quantity_gallons'] ?? 0)
            ),
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_production_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $pdo = ims_pdo();
    // One row per production lot — do not join packaging (1 lot → many bottling dates).
    $st = $pdo->query(
        'SELECT pr.id, pr.warehouse_row_id, pr.supplier_code, pr.arrival_date,
                COALESCE(pr.stock_out_date, DATE(pr.transferred_at)) AS stock_out_date,
                pr.daily_group_key, pr.variant, pr.current_qty, pr.initial_qty,
                COALESCE(pr.is_active, 1) AS is_active,
                pr.transferred_at, pr.created_at
         FROM honey_production_rows pr
         ORDER BY pr.transferred_at DESC, pr.id DESC'
    );
    api_ok(['rows' => $st ? $st->fetchAll() : []]);
}

function api_honey_production_variant_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    api_ok(honey_production_variant_summary(ims_pdo()));
}

function api_honey_production_voided_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $variant = trim((string) ($_GET['variant'] ?? ''));
    try {
        api_ok(honey_production_voided_list(ims_pdo(), $variant));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_production_void(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    try {
        $row = honey_production_void_lot(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['production_row_id'] ?? 0)
        );
        api_ok(['lot' => $row]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_production_unvoid(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('honey.executive_edit');
    try {
        $row = honey_production_unvoid_lot(
            ims_pdo(),
            (int) ($input['production_row_id'] ?? 0)
        );
        api_ok(['lot' => $row]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_honey_production_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_honey_ops();
    try {
        $lines = honey_parse_bottle_lines_from_input($input);
        $result = honey_production_stock_out(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['production_row_id'] ?? 0),
            $lines
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_packaging_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $pdo = ims_pdo();
    $bottlingDate = trim((string) ($_GET['bottling_date'] ?? ''));
    $sql = 'SELECT id, production_row_id, supplier_code, batch_no, bottling_date, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon, created_at
         FROM honey_packaging_rows';
    $params = [];
    if ($bottlingDate !== '' && strtolower($bottlingDate) !== 'all') {
        $sql .= ' WHERE bottling_date = ?';
        $params[] = honey_normalize_bottling_date($bottlingDate);
    }
    $sql .= ' ORDER BY bottling_date DESC, id DESC';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    api_ok(['rows' => $st->fetchAll() ?: []]);
}

function api_honey_packaging_total_inventory(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));
}

function api_honey_packaging_variant_pools(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    api_ok(honey_packaging_variant_pools_snapshot(ims_pdo()));
}

function api_honey_packaging_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    // Legacy path; Packaging UI no longer sells — keep gated to exec/PM if still called.
    $user = api_honey_require_mutate();
    try {
        $result = honey_packaging_stock_out_sold(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['packaging_row_id'] ?? 0),
            is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [],
            (string) ($input['sale_type'] ?? 'walk_in')
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_traceability(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $q = trim((string) ($_GET['q'] ?? ''));
    api_ok(honey_traceability(ims_pdo(), $q));
}

function api_honey_correction_submit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_honey_ops();
    $stage = (string) ($input['stage'] ?? '');
    $rowTable = (string) ($input['row_table'] ?? '');
    $rowId = (int) ($input['row_id'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    $correctionType = (string) ($input['correction_type'] ?? 'qty_delta');
    $action = (string) ($input['action'] ?? 'add');
    $amount = (float) ($input['amount'] ?? 0);
    $size = isset($input['size']) ? (string) $input['size'] : null;
    $newValue = isset($input['new_value']) ? trim((string) $input['new_value']) : null;

    if ($reason === '' || $rowId < 1) {
        api_fail('Invalid correction request.', 422);
    }

    if ($correctionType === 'qty_delta' && !in_array($action, ['add', 'deduct'], true)) {
        api_fail('Invalid action.', 422);
    }
    if ($correctionType === 'qty_delta' && $amount <= 0) {
        api_fail('Amount must be positive.', 422);
    }
    if (in_array($correctionType, ['variant', 'set_qty', 'supplier', 'packaging_size'], true) && ($newValue === null || $newValue === '')) {
        api_fail('New value is required for this correction type.', 422);
    }

    $pdo = ims_pdo();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO inventory_correction_requests (
                stage, row_table, row_id, correction_type, new_value, action, amount, size, reason, requested_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $stage,
            $rowTable,
            $rowId,
            $correctionType,
            $newValue,
            $action,
            $amount,
            $size ?: null,
            $reason,
            (int) $user['id'],
        ]);
    } catch (Throwable $e) {
        $ins = $pdo->prepare(
            'INSERT INTO inventory_correction_requests (stage, row_table, row_id, action, amount, size, reason, requested_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$stage, $rowTable, $rowId, $action, $amount, $size ?: null, $reason, (int) $user['id']]);
    }
    api_ok(['id' => (int) $pdo->lastInsertId()]);
}

function api_honey_correction_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_permission('inventory.requests.view', 'inventory.requests.approve');
    $canApprove = ims_user_can($user, 'inventory.requests.approve');
    $canViewAllReadonly = ims_user_can($user, 'inventory.requests.view') && !$canApprove;
    $pdo = ims_pdo();
    $sql = 'SELECT icr.*, u.name AS requested_by_name, r.name AS reviewed_by_name
            FROM inventory_correction_requests icr
            INNER JOIN users u ON u.id = icr.requested_by
            LEFT JOIN users r ON r.id = icr.reviewed_by';
    $params = [];
    if (!$canApprove && !$canViewAllReadonly) {
        $sql .= ' WHERE icr.requested_by = ?';
        $params[] = (int) $user['id'];
    }
    $sql .= ' ORDER BY icr.status = "pending" DESC, icr.created_at DESC LIMIT 500';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$row) {
        honey_correction_enrich($pdo, $row);
    }
    api_ok(['requests' => $rows, 'readonly' => $canViewAllReadonly]);
}

function api_honey_correction_row_options(): void
{
    api_method_allowed(['GET']);
    api_honey_require_honey_ops();
    $stage = (string) ($_GET['stage'] ?? '');
    api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);
}

function api_honey_correction_pending_count(): void
{
    api_method_allowed(['GET']);
    api_honey_correction_unread_count();
}

function api_honey_correction_unread_count(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'inventory.requests.approve') && !ims_user_can($user, 'honey.executive_edit')) {
        api_ok(['count' => 0]);

        return;
    }
    $pdo = ims_pdo();
    $count = honey_correction_unread_count($pdo);
    if (is_file(dirname(__DIR__, 2) . '/includes/material_inventory.php')) {
        require_once dirname(__DIR__, 2) . '/includes/material_inventory.php';
        $count += material_adjustment_pending_count($pdo);
    }
    api_ok(['count' => $count]);
}

function api_honey_correction_unread_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'inventory.requests.approve') && !ims_user_can($user, 'honey.executive_edit')) {
        api_ok(['requests' => []]);

        return;
    }
    $limit = (int) ($_GET['limit'] ?? 10);
    $rows = honey_correction_unread_list(ims_pdo(), $limit);
    api_ok(['requests' => $rows]);
}

function api_honey_correction_mark_viewed(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('honey.executive_edit');
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid request id.', 422);
    }
    honey_correction_mark_viewed(ims_pdo(), $id);
    api_ok(['viewed' => true]);
}

function api_honey_correction_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    $id = (int) ($input['id'] ?? 0);
    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT * FROM inventory_correction_requests WHERE id = ? AND status = "pending" LIMIT 1');
    $st->execute([$id]);
    $req = $st->fetch();
    if (!$req) {
        api_fail('Request not found.', 404);
    }
    $pdo->beginTransaction();
    try {
        honey_apply_correction_request($pdo, $req);
        $pdo->prepare(
            'UPDATE inventory_correction_requests SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        )->execute([(int) $user['id'], $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['approved' => true]);
}

function api_honey_correction_reject(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    $id = (int) ($input['id'] ?? 0);
    $note = trim((string) ($input['rejection_note'] ?? ''));
    if ($note === '') {
        api_fail('Rejection note is required.', 422);
    }
    $upd = ims_pdo()->prepare(
        'UPDATE inventory_correction_requests SET status = "rejected", rejection_note = ?, reviewed_by = ?, reviewed_at = NOW()
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([$note, (int) $user['id'], $id]);
    if ($upd->rowCount() < 1) {
        api_fail('Request not found.', 404);
    }
    api_ok(['rejected' => true]);
}

function api_honey_executive_adjust(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('honey.executive_edit');
    try {
        honey_executive_adjust(
            ims_pdo(),
            (string) ($input['stage'] ?? ''),
            (string) ($input['row_table'] ?? ''),
            (int) ($input['row_id'] ?? 0),
            (float) ($input['new_current'] ?? 0),
            isset($input['size']) ? (string) $input['size'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['adjusted' => true]);
}

function api_honey_executive_edit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    $fields = is_array($input['fields'] ?? null) ? $input['fields'] : $input;
    $logSource = strtolower(trim((string) ($fields['log_source'] ?? $input['log_source'] ?? '')));
    $monthly = !empty($input['monthly_inventory']) || $logSource === 'monthly_inventory';
    if ($monthly) {
        require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
        api_require_admin_override_password($input);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        honey_executive_edit_row(
            $pdo,
            (int) $user['id'],
            (string) ($input['stage'] ?? ''),
            (string) ($input['row_table'] ?? ''),
            (int) ($input['row_id'] ?? 0),
            $fields
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['saved' => true]);
}

function api_honey_dept_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $deptKey = trim((string) ($_GET['dept'] ?? ''));
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    if ($productKey !== '' && !ims_user_can_dept_line($user, $productKey, 'view')) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    $catalog = dept_inventory_catalog_snapshot(
        $pdo,
        $deptKey !== '' ? $deptKey : null,
        $productKey !== '' ? $productKey : null
    );
    $catalog = array_values(array_filter(
        $catalog,
        static fn(array $item): bool => ims_user_can_dept_line($user, (string) $item['key'], 'view')
    ));
    $rows = array_map(static function (array $item): array {
        return [
            'id' => $item['dept_row_id'],
            'dept_id' => $item['dept_id'],
            'dept_name' => $item['dept'],
            'product_key' => $item['key'],
            'product_label' => $item['label'],
            'unit' => $item['unit'],
            'stock_in_unit' => $item['stock_in_unit'],
            'inventory_unit' => $item['inventory_unit'],
            'uses_supplier_pricing' => $item['uses_supplier_pricing'],
            'tea_bags_per_pouch' => $item['tea_bags_per_pouch'],
            'tea_bags_per_bag' => $item['tea_bags_per_bag'],
            'grams_per_pouch' => $item['grams_per_pouch'],
            'grams_per_bag' => $item['grams_per_bag'],
            'pouches_per_bag' => $item['pouches_per_bag'],
            'sell_units_per_bag' => $item['sell_units_per_bag'] ?? $item['pouches_per_bag'],
            'grams_per_sell_unit' => $item['grams_per_sell_unit'] ?? null,
            'current_qty' => $item['current_qty'],
            'initial_qty' => $item['initial_qty'],
            'last_stock_in_at' => $item['last_stock_in_at'],
            'product_id' => $item['product_id'],
            'selling_price' => $item['selling_price'],
        ];
    }, $catalog);
    api_ok(['rows' => $rows]);
}

function api_honey_dept_catalog(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $deptKey = trim((string) ($_GET['dept'] ?? ''));
    $pdo = ims_pdo();
    api_ok(['products' => dept_inventory_catalog_snapshot($pdo, $deptKey !== '' ? $deptKey : null)]);
}

function api_honey_dept_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $qty = (float) ($input['qty'] ?? 0);
    $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : null;
    $unitCost = isset($input['unit_cost']) ? (float) $input['unit_cost'] : null;
    $arrivalDate = trim((string) ($input['arrival_date'] ?? date('Y-m-d')));
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_inventory_stock_in(
            $pdo,
            (int) $user['id'],
            $productKey,
            $qty,
            $arrivalDate,
            $note !== '' ? $note : null,
            $supplierId,
            $unitCost
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_raw_detail(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $productKey = dept_raw_material_slug_to_product_key($slug);
    if ($productKey === null) {
        api_fail('Unknown raw material.', 404);
    }
    if (!ims_user_can_dept_line($user, $productKey, 'view')) {
        api_fail('Forbidden.', 403);
    }
    $snapshot = dept_raw_detail_enriched(ims_pdo(), $slug);
    if ($snapshot === null) {
        api_fail('Unknown raw material.', 404);
    }
    api_ok($snapshot);
}

function api_honey_dept_raw_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $user = api_honey_require_dept_produce($productKey);
    $def = dept_product_by_key($productKey);
    if ($def === null || !($def['uses_supplier_pricing'] ?? false)) {
        api_fail('Invalid product for raw stock-in.', 422);
    }
    $qty = (float) ($input['qty'] ?? 0);
    $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : null;
    $arrivalDate = trim((string) ($input['arrival_date'] ?? date('Y-m-d')));
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_raw_stock_in_bags(
            $pdo,
            (int) $user['id'],
            $productKey,
            $def,
            $qty,
            $arrivalDate,
            $note !== '' ? $note : null,
            $supplierId
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_raw_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $user = api_honey_require_dept_produce($productKey);
    if (!dept_product_key_valid($productKey)) {
        api_fail('Invalid product.', 422);
    }
    $qty = (float) ($input['qty'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_raw_stock_out_bags(
            $pdo,
            (int) $user['id'],
            $productKey,
            $qty,
            $reason !== '' ? $reason : null
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_raw_produce(): void
{
    api_method_allowed(['POST']);
    api_fail('Use dept_pt_use_raw_bags. Paragis Tea, Black Rice Coffee, Jar Cacao, and Honey Comb finished stock-in use dept_pouch_barcodes.php.', 410);
}

function api_honey_dept_pt_use_raw_bags(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_dept_produce('pt');
    $qty = (float) ($input['qty'] ?? 0);
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_pt_use_raw_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_pt_make_tea_bags(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_dept_produce('pt');
    $qty = (float) ($input['qty'] ?? 0);
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_pt_make_tea_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_pt_pouch(): void
{
    api_method_allowed(['POST']);
    api_fail(
        'Pouch button is retired. Use Paragis pouch barcodes (request → generate/print → scan stock-in).',
        410
    );
}

function api_honey_dept_brc_produce(): void
{
    api_method_allowed(['POST']);
    api_fail(
        'Produce button is retired. Use Black Rice Coffee pouch barcodes (request → generate/print → scan stock-in).',
        410
    );
}

function api_honey_dept_jar_produce(): void
{
    api_method_allowed(['POST']);
    api_fail(
        'Produce button is retired. Use Jar Cacao barcodes (request → generate/print → scan stock-in).',
        410
    );
}

function api_honey_dept_comb_produce(): void
{
    api_method_allowed(['POST']);
    api_fail(
        'Produce button is retired. Use Honey Comb jar barcodes on Raw Honey → Packaging (request → generate/print → scan stock-in).',
        410
    );
}

function api_honey_dept_wip_waste(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_dept_produce('pt');
    $qty = (float) ($input['qty'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_wip_waste_tea_bags($pdo, (int) $user['id'], $qty, $reason !== '' ? $reason : null);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_production_preview(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    $action = trim((string) ($_GET['action'] ?? ''));
    $qty = (float) ($_GET['qty'] ?? 0);
    if (!dept_product_key_valid($productKey)) {
        api_fail('Invalid product.', 422);
    }
    if ($action === '' || $qty <= 0) {
        api_fail('action and qty required.', 422);
    }
    try {
        $preview = dept_production_preview_lines(ims_pdo(), $productKey, $action, $qty);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($preview);
}

function api_honey_dept_production_log(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    if (!dept_product_key_valid($productKey)) {
        api_fail('Invalid product.', 422);
    }
    $action = isset($_GET['log_action']) ? trim((string) $_GET['log_action']) : null;
    $date = isset($_GET['date']) ? trim((string) $_GET['date']) : null;
    $groupBy = isset($_GET['group_by']) ? trim((string) $_GET['group_by']) : '';
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 10);
    if ($action === '') {
        $action = null;
    }
    if ($date === '') {
        $date = null;
    }
    if ($groupBy === 'day') {
        if ($action === null) {
            api_fail('log_action required when group_by=day.', 422);
        }
        api_ok(dept_production_daily_totals(ims_pdo(), $productKey, $action, $date, $page, $perPage));
    }
    api_ok(dept_production_list_entries(ims_pdo(), $productKey, $action, $date, $page, $perPage));
}

function api_honey_dept_production_daily_record(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $user = api_honey_require_dept_produce($productKey);
    $qty = (float) ($input['qty'] ?? 0);
    $productionDate = trim((string) ($input['production_date'] ?? ''));
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_production_record_daily(
            $pdo,
            $productKey,
            $qty,
            $productionDate,
            (int) $user['id'],
            $note !== '' ? $note : null
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_dept_add_row(): void
{
    api_method_allowed(['POST']);
    api_fail('Use dept_stock_in with a catalog product_key instead.', 410);
}

function api_honey_supplier_dept_prices(): void
{
    api_method_allowed(['GET']);
    // Supplier Master manage includes inline purchase prices (not only honey.executive_edit).
    api_require_permission('suppliers.manage', 'honey.executive_edit');
    $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    $pdo = ims_pdo();
    $prices = dept_pricing_list_supplier_prices(
        $pdo,
        $supplierId > 0 ? $supplierId : null,
        $productKey !== '' ? $productKey : null
    );

    $latestByKey = [];
    foreach ($prices as $p) {
        $key = (int) $p['supplier_id'] . '|' . (string) $p['product_key'];
        if (!isset($latestByKey[$key])) {
            $latestByKey[$key] = [
                'supplier_id' => (int) $p['supplier_id'],
                'product_key' => (string) $p['product_key'],
                'price_per_unit' => round((float) $p['price_per_unit'], 2),
                'effective_from' => (string) $p['effective_from'],
                'id' => (int) $p['id'],
            ];
        }
    }

    api_ok([
        'prices' => array_values($latestByKey),
        'history' => array_map(static function (array $p): array {
            return [
                'id' => (int) $p['id'],
                'supplier_id' => (int) $p['supplier_id'],
                'product_key' => (string) $p['product_key'],
                'price_per_unit' => round((float) $p['price_per_unit'], 2),
                'effective_from' => (string) $p['effective_from'],
                'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),
            ];
        }, $prices),
    ]);
}

function api_honey_supplier_dept_prices_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('suppliers.manage', 'honey.executive_edit');
    $supplierId = (int) ($input['supplier_id'] ?? 0);
    $effectiveFrom = trim((string) ($input['effective_from'] ?? date('Y-m-d')));
    $products = is_array($input['products'] ?? null) ? $input['products'] : [];

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $saved = dept_pricing_save_supplier_prices($pdo, $supplierId, $products, $effectiveFrom, (int) $user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok(['saved' => $saved]);
}

function api_honey_supplier_honey_prices(): void
{
    api_method_allowed(['GET']);
    api_require_permission('suppliers.manage', 'honey.executive_edit');
    $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
    $pdo = ims_pdo();
    $prices = honey_pricing_list_supplier_prices($pdo, $supplierId > 0 ? $supplierId : null);

    $latestByKey = [];
    foreach ($prices as $p) {
        $key = (int) $p['supplier_id'] . '|' . (string) $p['variant'];
        if (!isset($latestByKey[$key])) {
            $latestByKey[$key] = [
                'supplier_id' => (int) $p['supplier_id'],
                'variant' => (string) $p['variant'],
                'price_per_gallon' => round((float) $p['price_per_gallon'], 2),
                'effective_from' => (string) $p['effective_from'],
                'id' => (int) $p['id'],
            ];
        }
    }

    api_ok([
        'prices' => array_values($latestByKey),
        'history' => array_map(static function (array $p) {
            return [
                'id' => (int) $p['id'],
                'supplier_id' => (int) $p['supplier_id'],
                'variant' => (string) $p['variant'],
                'price_per_gallon' => round((float) $p['price_per_gallon'], 2),
                'effective_from' => (string) $p['effective_from'],
                'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),
            ];
        }, $prices),
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_supplier_honey_prices_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('suppliers.manage', 'honey.executive_edit');
    $supplierId = (int) ($input['supplier_id'] ?? 0);
    $effectiveFrom = trim((string) ($input['effective_from'] ?? date('Y-m-d')));
    $variants = is_array($input['variants'] ?? null) ? $input['variants'] : [];

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $saved = honey_pricing_save_supplier_prices($pdo, $supplierId, $variants, $effectiveFrom, (int) $user['id']);
        $repriced = honey_pricing_reprice_pending_for_supplier($pdo, $supplierId, (int) $user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok([
        'saved' => $saved,
        'repriced_rows' => $repriced,
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_warehouse_pending_prices(): void
{
    api_method_allowed(['GET']);
    api_require_permission('honey.executive_edit');
    $pdo = ims_pdo();
    api_ok([
        'rows' => honey_pricing_pending_warehouse_rows($pdo),
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_warehouse_set_unit_cost(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    $warehouseRowId = (int) ($input['warehouse_row_id'] ?? 0);
    $raw = trim((string) ($input['unit_cost_per_gallon'] ?? ''));
    if ($warehouseRowId < 1) {
        api_fail('Invalid warehouse row.', 422);
    }
    if (!is_numeric($raw) || (float) $raw < 0) {
        api_fail('Unit cost per gallon must be 0 or greater.', 422);
    }

    try {
        $row = honey_pricing_set_warehouse_row_cost(ims_pdo(), $warehouseRowId, (float) $raw, (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok([
        'row' => $row,
        'pending_count' => honey_pricing_pending_count(ims_pdo()),
    ]);
}

function api_honey_pricing_sync_stocks(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    try {
        $synced = honey_pricing_sync_all_variant_stocks(ims_pdo(), (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['synced' => $synced]);
}

function api_honey_edit_log_list(): void
{
    api_method_allowed(['GET']);
    api_require_permission('honey.executive_edit');
    $limit = (int) ($_GET['limit'] ?? 50);
    $offset = (int) ($_GET['offset'] ?? 0);
    $rowTable = isset($_GET['row_table']) ? trim((string) $_GET['row_table']) : null;
    $source = isset($_GET['source']) ? trim((string) $_GET['source']) : null;
    $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : null;
    $dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : null;
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
    $result = inventory_edit_log_list(
        ims_pdo(),
        $limit,
        $offset,
        $rowTable !== '' ? $rowTable : null,
        $source !== '' ? $source : null,
        $dateFrom !== '' ? $dateFrom : null,
        $dateTo !== '' ? $dateTo : null,
        $q !== '' ? $q : null
    );
    api_ok($result);
}

function api_honey_seed_production_row(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_require_admin_override_password($input);
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $row = honey_executive_seed_production_row(
            $pdo,
            (int) $user['id'],
            (int) ($input['warehouse_row_id'] ?? 0),
            (float) ($input['qty'] ?? 0),
            isset($input['variant']) ? (string) $input['variant'] : null
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['row' => $row]);
}

function api_honey_seed_packaging_row(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_require_admin_override_password($input);
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $row = honey_executive_seed_packaging_row(
            $pdo,
            (int) $user['id'],
            (int) ($input['production_row_id'] ?? 0),
            (string) ($input['bottling_date'] ?? date('Y-m-d')),
            is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : []
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['row' => $row]);
}

function api_honey_dept_raw_set_bags(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
    api_require_admin_override_password($input);
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_raw_set_bags_on_hand(
            $pdo,
            (int) $user['id'],
            $productKey,
            (float) ($input['bags'] ?? -1),
            isset($input['note']) ? (string) $input['note'] : null,
            'monthly_inventory'
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

/** Day-to-day executive correction of PT/BRC/Jar Cacao leaf pool (grams). No admin password. */
function api_honey_dept_raw_executive_set(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('honey.executive_edit');
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        if (array_key_exists('grams', $input)) {
            $result = dept_raw_set_grams_on_hand(
                $pdo,
                (int) $user['id'],
                $productKey,
                (float) $input['grams'],
                isset($input['note']) ? (string) $input['note'] : null,
                'executive_edit'
            );
        } else {
            $result = dept_raw_set_bags_on_hand(
                $pdo,
                (int) $user['id'],
                $productKey,
                (float) ($input['bags'] ?? -1),
                isset($input['note']) ? (string) $input['note'] : null,
                'executive_edit'
            );
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

