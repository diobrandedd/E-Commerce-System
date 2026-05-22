<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

/**
 * RTS bridge API.
 *
 * The legacy RTS module is built around `rts_cases` + `rts_line_items`
 * (with case status workflow, claim status, etc.).  Until the SPA
 * achieves full feature parity, this endpoint exposes summarised
 * pool / recent activity data so the SPA `RTSPage` is read-only
 * and links back to the legacy PHP pages for case creation/edits.
 *
 *   GET ?action=pool        -> aggregate balance per product
 *   GET ?action=recent      -> last 50 line items
 *   GET ?action=legacy_urls -> URLs for the legacy PHP screens
 */

api_dispatch([
    'pool' => 'api_rts_pool',
    'recent' => 'api_rts_recent',
    'legacy_urls' => 'api_rts_legacy_urls',
]);

function api_rts_role_allowed(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'staff'], true);
}

function api_rts_pool(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    try {
        $st = $pdo->query(
            'SELECT
                li.product_type,
                li.honey_size,
                li.honey_variant,
                COALESCE(SUM(li.quantity), 0) AS total_qty,
                COUNT(*) AS line_count
             FROM rts_line_items li
             INNER JOIN rts_cases c ON c.id = li.case_id
             WHERE c.voided_at IS NULL
             GROUP BY li.product_type, li.honey_size, li.honey_variant
             ORDER BY li.product_type ASC, li.honey_size ASC, li.honey_variant ASC'
        );
        $rows = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        // Table missing or older schema — return empty pool gracefully.
        $rows = [];
    }
    api_ok(['pool' => $rows]);
}

function api_rts_recent(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    try {
        $st = $pdo->query(
            "SELECT li.id AS line_id, li.case_id, li.product_type, li.honey_size, li.honey_variant,
                    li.combo_product, li.quantity, li.is_bonus, li.created_at,
                    c.tracking_number, c.shop, c.courier_final, c.claim_status,
                    c.date_opened, c.damaged_tag
             FROM rts_line_items li
             INNER JOIN rts_cases c ON c.id = li.case_id
             WHERE c.voided_at IS NULL
             ORDER BY li.created_at DESC, li.id DESC
             LIMIT 50"
        );
        $rows = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        $rows = [];
    }
    api_ok(['items' => $rows]);
}

function api_rts_legacy_urls(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    api_ok([
        'records' => '/rts/records.php',
        'create' => '/rts/record.php',
        'dashboard' => '/rts/dashboard.php',
    ]);
}
