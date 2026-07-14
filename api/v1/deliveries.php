<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/deliveries_upload.php';
require_once __DIR__ . '/../../includes/delivery_edit_requests.php';
require_once __DIR__ . '/../../includes/online_order_parcels.php';
require_once __DIR__ . '/../../includes/tracking_search.php';

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

function api_deliveries_assert_executive(): array
{
    $user = api_deliveries_assert_access();
    if ((string) ($user['role'] ?? '') !== 'executive') {
        api_fail('Only executives can void or resolve delivery items directly. Submit an edit request instead.', 403);
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
    'cancel_parcel_scan' => 'api_deliveries_cancel_parcel_scan',
    'cancel_log_list' => 'api_deliveries_cancel_log_list',
    'hold_parcel' => 'api_deliveries_hold_parcel',
    'rewaybill' => 'api_deliveries_rewaybill',
    'review_list' => 'api_deliveries_review_list',
    'void_item' => 'api_deliveries_delete_item',
    'delete_item' => 'api_deliveries_delete_item',
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
    if ($parcel !== null) {
        $parcel = deliveries_repair_orphaned_shipped_parcel($pdo, $parcel);
    }
    $parcelId = $parcel !== null ? (int) ($parcel['id'] ?? 0) : null;
    $existingRecordings = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);

    api_ok([
        'tracking_normalized' => $tracking,
        'parcel' => $parcel,
        'existing_recordings' => $existingRecordings,
        'already_recorded' => $existingRecordings !== [],
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

function api_deliveries_cancel_parcel_scan(): void
{
    api_method_allowed(['POST']);
    $user = api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $tracking = trim((string) ($input['tracking_number'] ?? $input['tracking'] ?? ''));
    $source = trim((string) ($input['cancel_source'] ?? 'scanner'));
    $noteRaw = isset($input['note']) ? trim((string) $input['note']) : '';
    $note = $noteRaw !== '' ? $noteRaw : null;

    if ($tracking === '') {
        api_fail('tracking_number required.', 400);
    }

    $pdo = ims_pdo();
    try {
        $result = online_parcels_cancel($pdo, (int) $user['id'], $tracking, $source, $note);
        $message = $result['already_cancelled']
            ? 'Parcel already cancelled.'
            : 'Courier cancelled — sealed box kept for re-waybill (inventory unchanged).';
        api_ok([
            'parcel' => $result['parcel'],
            'already_cancelled' => (bool) ($result['already_cancelled'] ?? false),
            'previous_status' => (string) ($result['previous_status'] ?? ''),
            'inventory_reverted' => (bool) ($result['inventory_reverted'] ?? false),
            'cancel_event_id' => isset($result['event_id']) ? (int) $result['event_id'] : null,
            'message' => $message,
        ]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_deliveries_cancel_log_list(): void
{
    api_method_allowed(['GET']);
    api_deliveries_assert_access();

    $q = trim((string) ($_GET['q'] ?? ''));
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 10)));

    $pdo = ims_pdo();
    $result = online_parcels_cancel_log_list($pdo, $q, $from, $to, $page, $perPage);

    api_ok([
        'rows' => $result['rows'],
        'total' => (int) $result['total'],
        'page' => $page,
        'per_page' => $perPage,
    ]);
}

function api_deliveries_hold_parcel(): void
{
    api_method_allowed(['POST']);
    $user = api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $tracking = trim((string) ($input['tracking_number'] ?? $input['tracking'] ?? ''));
    $noteRaw = isset($input['note']) ? trim((string) $input['note']) : '';
    $note = $noteRaw !== '' ? $noteRaw : null;

    if ($tracking === '') {
        api_fail('tracking_number required.', 400);
    }

    $pdo = ims_pdo();
    try {
        $result = online_parcels_hold($pdo, (int) $user['id'], $tracking, $note);
        $message = $result['already_on_hold']
            ? 'Parcel is already on hold.'
            : 'Parcel placed on hold for re-waybill. Inventory unchanged.';
        api_ok([
            'parcel' => $result['parcel'],
            'already_on_hold' => (bool) ($result['already_on_hold'] ?? false),
            'previous_status' => (string) ($result['previous_status'] ?? ''),
            'hold_event_id' => isset($result['event_id']) ? (int) $result['event_id'] : null,
            'message' => $message,
        ]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_deliveries_rewaybill(): void
{
    api_method_allowed(['POST']);
    $user = api_deliveries_assert_access();
    api_deliveries_require_csrf();

    $input = api_input();
    $oldTracking = trim((string) ($input['old_tracking'] ?? $input['old_tracking_number'] ?? ''));
    $newTracking = trim((string) ($input['new_tracking'] ?? $input['new_tracking_number'] ?? ''));

    if ($oldTracking === '' || $newTracking === '') {
        api_fail('old_tracking and new_tracking required.', 400);
    }

    $pdo = ims_pdo();
    try {
        $result = online_parcels_rewaybill($pdo, (int) $user['id'], $oldTracking, $newTracking);
        api_ok([
            'parcel' => $result['parcel'],
            'history_id' => (int) ($result['history_id'] ?? 0),
            'message' => 'Re-waybill linked. Parcel is back in packing — ship on the new tracking number.',
        ]);
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
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = (int) ($_GET['per_page'] ?? 10);
    $perPage = $perPage === 20 ? 20 : 10;

    $pdo = ims_pdo();
    $where = ['i.voided_at IS NULL'];
    $params = [];
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'b.delivery_date >= ?';
        $params[] = $from;
    }
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'b.delivery_date <= ?';
        $params[] = $to;
    }
    if ($q !== '') {
        if (tracking_search_is_tn_shaped($q)) {
            [$tnSql, $tnParams] = tracking_search_tn_column_sql('i.tracking_number', $q);
            $where[] = $tnSql;
            foreach ($tnParams as $p) {
                $params[] = $p;
            }
        } else {
            $like = tracking_search_contains_like($q);
            $hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');
            if ($hasParcelLink) {
                $where[] = '(i.tracking_number LIKE ? OR i.photo_filename LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM online_order_parcels p
                        WHERE (p.id = i.online_parcel_id OR p.tracking_number = i.tracking_number)
                          AND (p.recipient_name LIKE ? OR p.items_raw LIKE ? OR p.tracking_number LIKE ? OR p.order_no LIKE ?)
                    ))';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            } else {
                $where[] = '(i.tracking_number LIKE ? OR i.photo_filename LIKE ?
                    OR EXISTS (
                        SELECT 1 FROM online_order_parcels p
                        WHERE p.tracking_number = i.tracking_number
                          AND (p.recipient_name LIKE ? OR p.items_raw LIKE ? OR p.tracking_number LIKE ? OR p.order_no LIKE ?)
                    ))';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
        }
    }
    $whereSql = implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    $stc = $pdo->prepare(
        "SELECT COUNT(*) FROM delivery_batch_items i INNER JOIN delivery_batches b ON b.id = i.batch_id WHERE {$whereSql}"
    );
    $stc->execute($params);
    $total = (int) $stc->fetchColumn();

    $parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')
        ? 'i.online_parcel_id,'
        : '';
    $st = $pdo->prepare(
        "SELECT i.id AS item_id, i.batch_id, i.photo_filename, i.item_status, i.tracking_number,
                {$parcelCol} i.voided_at, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE {$whereSql}
         ORDER BY i.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $photo = online_parcels_format_packing_photo([
            'delivery_item_id' => (int) ($row['item_id'] ?? 0),
            'photo_filename' => (string) ($row['photo_filename'] ?? ''),
            'delivery_date' => (string) ($row['delivery_date'] ?? ''),
        ]);
        $row['photo_url'] = $photo['photo_url'] ?? '';
    }
    unset($row);
    $rows = deliveries_enrich_review_rows($pdo, $rows);

    api_ok(['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
}

function api_deliveries_delete_item(): void
{
    api_method_allowed(['POST']);
    api_deliveries_assert_executive();
    api_deliveries_require_csrf();

    $input = api_input();
    $itemId = (int) ($input['item_id'] ?? 0);
    if ($itemId < 1) {
        api_fail('item_id required.', 400);
    }

    $pdo = ims_pdo();
    try {
        api_ok(['deleted' => deliveries_delete_item_by_id($pdo, $itemId)]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_deliveries_resolve_item(): void
{
    api_method_allowed(['POST']);
    api_deliveries_assert_executive();
    api_deliveries_require_csrf();

    $input = api_input();
    $itemId = (int) ($input['item_id'] ?? 0);
    $tracking = trim((string) ($input['tracking_number'] ?? ''));
    if ($itemId < 1 || $tracking === '') {
        api_fail('item_id and tracking_number required.', 400);
    }

    $pdo = ims_pdo();
    try {
        deliveries_resolve_item_by_id($pdo, $itemId, $tracking);
        api_ok(['ok' => true]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}
