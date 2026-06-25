<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/ar_credits.php';

/**
 * Accounts-Receivable (employee product credits) API.
 *
 *   GET  ?action=list          -> own credits for staff; all for executive
 *   GET  ?action=products      -> sellable products for take modal
 *   GET  ?action=staff_picker  -> active staff for POS debtor combobox
 *   POST ?action=take          -> staff takes a product on credit (IMS)
 *   POST ?action=mark_paid     -> executive marks paid
 *   POST ?action=mark_unpaid   -> executive marks unpaid
 */

api_dispatch([
    'list' => 'api_ar_list',
    'take' => 'api_ar_take',
    'mark_paid' => 'api_ar_mark_paid',
    'mark_unpaid' => 'api_ar_mark_unpaid',
    'products' => 'api_ar_products',
    'staff_picker' => 'api_ar_staff_picker',
]);

function api_ar_products(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    try {
        api_ok(['products' => ar_credits_sellable_products(ims_pdo())]);
    } catch (Throwable) {
        api_ok(['products' => [], 'missing_migration' => true]);
    }
}

function api_ar_staff_picker(): void
{
    api_method_allowed(['GET']);
    api_pos_require_session_or_ims();
    try {
        api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);
    } catch (Throwable) {
        api_ok(['staff' => [], 'missing_migration' => true]);
    }
}

function api_pos_require_session_or_ims(): void
{
    if (ims_pos_authenticated()) {
        return;
    }
    api_require_ims_user();
}

function api_ar_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    try {
        api_ok(['credits' => ar_credits_list(ims_pdo(), $user)]);
    } catch (Throwable) {
        api_ok(['credits' => [], 'missing_migration' => true]);
    }
}

function api_ar_take(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!in_array($role, ar_credits_staff_roles(), true)) {
        api_fail('Forbidden.', 403);
    }

    $productId = (int) ($input['product_id'] ?? 0);
    $qty = (float) ($input['qty'] ?? 0);
    if ($productId < 1 || $qty <= 0) {
        api_fail('Invalid product or quantity.', 422);
    }

    try {
        $result = ar_credits_take_for_user(ims_pdo(), (int) $user['id'], $productId, $qty);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($result);
}

function api_ar_mark_paid(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid id.', 422);
    }

    try {
        ar_credits_mark_paid(ims_pdo(), $id, (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }

    api_ok(['paid' => true]);
}

function api_ar_mark_unpaid(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid id.', 422);
    }

    try {
        ar_credits_mark_unpaid(ims_pdo(), $id);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }

    api_ok(['unpaid' => true]);
}
