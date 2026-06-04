<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/deliveries_upload.php';
require_once __DIR__ . '/../../includes/online_order_parcels.php';

function api_deliveries_assert_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Not authenticated.', 401);
    }
    if (!online_parcels_user_can_ship($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_deliveries_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'batch_for_date' => 'api_deliveries_batch_for_date',
    'upload_photo' => 'api_deliveries_upload_photo',
    'parcel_by_tracking' => 'api_deliveries_parcel_by_tracking',
    'ship_parcel' => 'api_deliveries_ship_parcel',
    'review_list' => 'api_deliveries_review_list',
    'void_item' => 'api_deliveries_void_item',
    'resolve_item' => 'api_deliveries_resolve_item',
]);

function api_deliveries_batch_for_date(): void
{
    api_method_allowed(['GET']);
    api_deliveries_assert_access();

    $date = trim((string) ($_GET['delivery_date'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        api_fail('Invalid delivery_date.', 400);
    }

    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT id, delivery_date, submitted_at FROM delivery_batches WHERE delivery_date = ? LIMIT 1');
    $st->execute([$date]);
    $batch = $st->fetch(PDO::FETCH_ASSOC);
    $batchId = $batch !== false ? (int) $batch['id'] : 0;
    $count = 0;
    if ($batchId > 0) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM delivery_batch_items WHERE batch_id = ? AND voided_at IS NULL');
        $c->execute([$batchId]);
        $count = (int) $c->fetchColumn();
    }

    api_ok([
        'batch_id' => $batchId > 0 ? $batchId : null,
        'delivery_date' => $date,
        'item_count' => $count,
    ]);
}

function api_deliveries_upload_photo(): void
{
    api_method_allowed(['POST']);
    api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $deliveryDate = trim((string) ($_POST['delivery_date'] ?? date('Y-m-d')));
    if (!isset($_FILES['photo']) && !isset($_FILES['photos'])) {
        api_fail('photo file required.', 400);
    }

    $fileField = isset($_FILES['photo']) ? $_FILES['photo'] : $_FILES['photos'];
    $files = [
        'name' => is_array($fileField['name']) ? $fileField['name'] : [$fileField['name']],
        'tmp_name' => is_array($fileField['tmp_name']) ? $fileField['tmp_name'] : [$fileField['tmp_name']],
        'error' => is_array($fileField['error']) ? $fileField['error'] : [$fileField['error']],
        'size' => is_array($fileField['size']) ? $fileField['size'] : [$fileField['size']],
    ];

    $hints = null;
    $hint = trim((string) ($_POST['client_hint'] ?? $_POST['tracking_hint'] ?? ''));
    if ($hint !== '') {
        $hints = [$hint];
    }

    $pdo = ims_pdo();
    $submitterId = deliveries_ims_submitter_app_user_id($pdo);

    try {
        $result = deliveries_upload_photos($pdo, $deliveryDate, $files, $hints, $submitterId);
        api_ok($result);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_deliveries_parcel_by_tracking(): void
{
    api_method_allowed(['GET']);
    api_deliveries_assert_access();

    $raw = trim((string) ($_GET['tracking'] ?? $_GET['q'] ?? ''));
    if ($raw === '') {
        api_fail('tracking required.', 400);
    }

    $pdo = ims_pdo();
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($raw);
    if ($tracking === '') {
        api_fail('Invalid tracking.', 400);
    }

    $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    $deliveryItems = [];
    $st = $pdo->prepare(
        'SELECT i.id AS delivery_item_id, i.photo_filename, i.item_status, i.tracking_number, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL AND (i.tracking_number = ? OR i.tracking_number IS NULL OR TRIM(i.tracking_number) = "")
         ORDER BY i.id DESC LIMIT 20'
    );
    $st->execute([$tracking]);
    $deliveryItems = $st->fetchAll(PDO::FETCH_ASSOC);

    api_ok([
        'tracking_normalized' => $tracking,
        'parcel' => $parcel,
        'recent_delivery_items' => $deliveryItems,
    ]);
}

function api_deliveries_ship_parcel(): void
{
    api_method_allowed(['POST']);
    $user = api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $tracking = trim((string) ($input['tracking_number'] ?? $input['tracking'] ?? ''));
    $deliveryItemId = (int) ($input['delivery_item_id'] ?? 0);
    $shipDate = trim((string) ($input['ship_date'] ?? date('Y-m-d')));

    if ($tracking === '') {
        api_fail('tracking_number required.', 400);
    }

    $pdo = ims_pdo();
    try {
        $result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);
        if ($result['already_shipped']) {
            api_ok([
                'parcel' => $result['parcel'],
                'already_shipped' => true,
                'message' => 'Parcel was already shipped; inventory not deducted again.',
            ]);
        } else {
            api_ok(['parcel' => $result['parcel'], 'already_shipped' => false]);
        }
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_deliveries_review_list(): void
{
    api_method_allowed(['GET']);
    api_deliveries_assert_access();

    $q = trim((string) ($_GET['q'] ?? ''));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $voidOnly = isset($_GET['void_only']) && (string) $_GET['void_only'] === '1';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 10);
    $perPage = $perPage === 20 ? 20 : 10;

    $pdo = ims_pdo();
    $where = ['1=1'];
    $params = [];
    if ($voidOnly) {
        $where[] = 'i.voided_at IS NOT NULL';
    }
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'b.delivery_date >= ?';
        $params[] = $from;
    }
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'b.delivery_date <= ?';
        $params[] = $to;
    }
    if ($q !== '') {
        $where[] = '(i.tracking_number LIKE ? OR i.photo_filename LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
    }
    $whereSql = implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    $stc = $pdo->prepare(
        "SELECT COUNT(*) FROM delivery_batch_items i INNER JOIN delivery_batches b ON b.id = i.batch_id WHERE {$whereSql}"
    );
    $stc->execute($params);
    $total = (int) $stc->fetchColumn();

    $st = $pdo->prepare(
        "SELECT i.id AS item_id, i.batch_id, i.photo_filename, i.item_status, i.tracking_number,
                i.voided_at, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE {$whereSql}
         ORDER BY i.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);

    api_ok(['rows' => $st->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
}

function api_deliveries_void_item(): void
{
    api_method_allowed(['POST']);
    api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $itemId = (int) ($input['item_id'] ?? 0);
    if ($itemId < 1) {
        api_fail('item_id required.', 400);
    }

    $pdo = ims_pdo();
    $st = $pdo->prepare('UPDATE delivery_batch_items SET voided_at = NOW() WHERE id = ? AND voided_at IS NULL');
    $st->execute([$itemId]);
    api_ok(['voided' => $st->rowCount() > 0]);
}

function api_deliveries_resolve_item(): void
{
    api_method_allowed(['POST']);
    $user = api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $itemId = (int) ($input['item_id'] ?? 0);
    $tracking = trim((string) ($input['tracking_number'] ?? ''));
    if ($itemId < 1 || $tracking === '') {
        api_fail('item_id and tracking_number required.', 400);
    }

    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        api_fail('Invalid tracking_number.', 400);
    }

    $pdo = ims_pdo();
    $st = $pdo->prepare(
        'UPDATE delivery_batch_items
         SET tracking_number = ?, item_status = "resolved", resolved_at = NOW()
         WHERE id = ? AND voided_at IS NULL'
    );
    $st->execute([$tracking, $itemId]);
    if ($st->rowCount() === 0) {
        api_fail('Item not found or voided.', 404);
    }
    api_ok(['ok' => true]);
}
