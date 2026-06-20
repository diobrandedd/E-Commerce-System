<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/pos_discount_requests.php';

api_dispatch([
    'list' => 'api_pos_discounts_list',
    'approve' => 'api_pos_discounts_approve',
    'decline' => 'api_pos_discounts_decline',
]);

function api_pos_discounts_require_exec(): array
{
    return api_require_role(['executive']);
}

function api_pos_discounts_list(): void
{
    api_method_allowed(['GET']);
    api_pos_discounts_require_exec();

    $status = trim((string) ($_GET['status'] ?? 'pending'));
    $limit = (int) ($_GET['limit'] ?? 100);
    try {
        $payload = pos_discount_request_list(ims_pdo(), $status, $limit);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($payload);
}

function api_pos_discounts_approve(): void
{
    api_method_allowed(['POST']);
    $user = api_pos_discounts_require_exec();
    $input = api_input();
    api_require_csrf($input);

    $id = (int) ($input['id'] ?? 0);
    try {
        $payload = pos_discount_request_approve(ims_pdo(), $id, (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok($payload);
}

function api_pos_discounts_decline(): void
{
    api_method_allowed(['POST']);
    $user = api_pos_discounts_require_exec();
    $input = api_input();
    api_require_csrf($input);

    $id = (int) ($input['id'] ?? 0);
    $note = trim((string) ($input['note'] ?? ''));
    try {
        pos_discount_request_decline(ims_pdo(), $id, (int) $user['id'], $note !== '' ? $note : null);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok(['declined' => true]);
}
