<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/online_order_parcels.php';

function api_parcels_assert_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Not authenticated.', 401);
    }
    if (!online_parcels_user_can_access($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_parcels_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'list' => 'api_parcels_list',
    'get' => 'api_parcels_get',
    'lookup' => 'api_parcels_lookup',
    'parse_preview' => 'api_parcels_parse_preview',
]);

function api_parcels_list(): void
{
    api_method_allowed(['GET']);
    api_parcels_assert_access();

    $q = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 25);

    $pdo = ims_pdo();
    online_parcels_ensure_schema($pdo);
    $result = online_parcels_list($pdo, $q, $status, $page, $perPage);
    api_ok($result);
}

function api_parcels_get(): void
{
    api_method_allowed(['GET']);
    api_parcels_assert_access();

    $id = (int) ($_GET['id'] ?? 0);
    $tracking = trim((string) ($_GET['tracking'] ?? ''));
    $pdo = ims_pdo();
    online_parcels_ensure_schema($pdo);

    $parcel = null;
    if ($id > 0) {
        $parcel = online_parcels_get_by_id($pdo, $id);
    } elseif ($tracking !== '') {
        $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    } else {
        api_fail('id or tracking required.', 400);
    }

    if ($parcel === null) {
        api_fail('Parcel not found.', 404);
    }
    api_ok(['parcel' => $parcel]);
}

function api_parcels_lookup(): void
{
    api_method_allowed(['GET']);
    api_parcels_assert_access();

    $raw = trim((string) ($_GET['q'] ?? $_GET['tracking'] ?? ''));
    if ($raw === '') {
        api_fail('q required.', 400);
    }

    $pdo = ims_pdo();
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($raw);
    if ($tracking === '') {
        api_fail('Could not read tracking from scan.', 400);
    }
    $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    if ($parcel === null) {
        api_fail('No parcel found for this tracking number.', 404);
    }
    api_ok(['parcel' => $parcel, 'tracking_normalized' => $tracking]);
}

function api_parcels_parse_preview(): void
{
    api_method_allowed(['POST']);
    $user = api_parcels_assert_access();
    api_parcels_require_csrf();
    if (!in_array((string) ($user['role'] ?? ''), ['executive', 'prod_manager'], true)) {
        api_fail('Forbidden.', 403);
    }

    $input = api_input();
    $items = trim((string) ($input['items'] ?? ''));
    if ($items === '') {
        api_fail('items required.', 400);
    }

    require_once __DIR__ . '/../../includes/honey_parcel_item_parser.php';
    $parsed = honey_parcel_parse_items_string($items);
    api_ok($parsed);
}
