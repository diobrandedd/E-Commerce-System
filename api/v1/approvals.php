<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/override_approvals.php';

api_dispatch([
    'list' => 'api_approvals_list',
    'get' => 'api_approvals_get',
    'approve' => 'api_approvals_approve',
    'reject' => 'api_approvals_reject',
    'pending_count' => 'api_approvals_pending_count',
    'unread_list' => 'api_approvals_unread_list',
    'mark_viewed' => 'api_approvals_mark_viewed',
]);

function api_approvals_require_user(): array
{
    return api_require_ims_user();
}

function api_approvals_require_hr(): array
{
    $user = api_approvals_require_user();
    if (!override_approval_is_hr_manager($user)) {
        api_fail('Only the HR manager can do this.', 403);
    }

    return $user;
}

function api_approvals_list(): void
{
    api_method_allowed(['GET']);
    $user = api_approvals_require_user();
    $status = trim((string) ($_GET['status'] ?? 'all'));
    $rows = override_approval_list(ims_pdo(), $user, $status !== '' ? $status : 'all');
    $hr = override_approval_hr_manager_public(ims_pdo());
    api_ok([
        'rows' => $rows,
        'is_hr_manager' => override_approval_is_hr_manager($user),
        'hr_manager' => $hr,
    ]);
}

function api_approvals_get(): void
{
    api_method_allowed(['GET']);
    $user = api_approvals_require_user();
    $id = (int) ($_GET['id'] ?? 0);
    $row = override_approval_get(ims_pdo(), $id);
    if ($row === null) {
        api_fail('Not found.', 404);
    }
    $isHr = override_approval_is_hr_manager($user);
    if (!$isHr && (int) $row['requested_by'] !== (int) $user['id']) {
        api_fail('Forbidden.', 403);
    }
    api_ok(['request' => $row, 'is_hr_manager' => $isHr]);
}

function api_approvals_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_approvals_require_hr();
    $id = (int) ($input['id'] ?? 0);
    try {
        $request = override_approval_approve(ims_pdo(), $id, $user);
        api_ok(['request' => $request, 'applied' => true]);
    } catch (Throwable $e) {
        $req = override_approval_get(ims_pdo(), $id);
        if ($req !== null && ($req['status'] ?? '') === 'failed') {
            api_fail($e->getMessage(), 422, ['request' => $req]);
        }
        api_fail($e->getMessage(), 422);
    }
}

function api_approvals_reject(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_approvals_require_hr();
    $id = (int) ($input['id'] ?? 0);
    $note = (string) ($input['rejection_note'] ?? $input['note'] ?? '');
    try {
        $request = override_approval_reject(ims_pdo(), $id, $user, $note);
        api_ok(['request' => $request]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_approvals_pending_count(): void
{
    api_method_allowed(['GET']);
    api_approvals_require_hr();
    api_ok(['count' => override_approval_pending_count(ims_pdo())]);
}

function api_approvals_unread_list(): void
{
    api_method_allowed(['GET']);
    api_approvals_require_hr();
    api_ok(['rows' => override_approval_unread_list(ims_pdo())]);
}

function api_approvals_mark_viewed(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_approvals_require_hr();
    $id = isset($input['id']) ? (int) $input['id'] : null;
    override_approval_mark_viewed(ims_pdo(), $id && $id > 0 ? $id : null);
    api_ok(['ok' => true]);
}
