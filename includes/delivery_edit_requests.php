<?php

declare(strict_types=1);

require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/honey_parcel_inventory.php';
require_once __DIR__ . '/rts_storage.php';

/**
 * @return list<array<string, mixed>>
 */
function deliveries_find_existing_recordings(PDO $pdo, string $tracking, ?int $parcelId = null): array
{
    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '' && ($parcelId === null || $parcelId < 1)) {
        return [];
    }

    $hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');

    if ($hasParcelLink && $parcelId !== null && $parcelId > 0) {
        $st = $pdo->prepare(
            'SELECT i.id AS delivery_item_id, i.photo_filename, i.item_status, i.tracking_number, b.delivery_date
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL AND (i.tracking_number = ? OR i.online_parcel_id = ?)
             ORDER BY i.id DESC'
        );
        $st->execute([$tracking, $parcelId]);
    } elseif ($tracking !== '') {
        $st = $pdo->prepare(
            'SELECT i.id AS delivery_item_id, i.photo_filename, i.item_status, i.tracking_number, b.delivery_date
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL AND i.tracking_number = ?
             ORDER BY i.id DESC'
        );
        $st->execute([$tracking]);
    } elseif ($hasParcelLink && $parcelId !== null && $parcelId > 0) {
        $st = $pdo->prepare(
            'SELECT i.id AS delivery_item_id, i.photo_filename, i.item_status, i.tracking_number, b.delivery_date
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL AND i.online_parcel_id = ?
             ORDER BY i.id DESC'
        );
        $st->execute([$parcelId]);
    } else {
        return [];
    }

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @throws RuntimeException when another active delivery row already uses this tracking/parcel.
 */
function deliveries_assert_tracking_not_duplicate(
    PDO $pdo,
    string $tracking,
    int $excludeItemId = 0,
    ?int $parcelId = null
): void {
    $existing = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);
    foreach ($existing as $rec) {
        $id = (int) ($rec['delivery_item_id'] ?? 0);
        if ($excludeItemId > 0 && $id === $excludeItemId) {
            continue;
        }
        $tn = trim((string) ($rec['tracking_number'] ?? ''));
        throw new RuntimeException(
            $tn !== ''
                ? "Tracking {$tn} is already recorded in Deliveries (item #{$id})."
                : "This parcel already has a delivery photo on file (item #{$id})."
        );
    }
}

function deliveries_get_batch_item_row(PDO $pdo, int $itemId): array
{
    $parcelCol = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')
        ? 'i.online_parcel_id,'
        : '';
    $st = $pdo->prepare(
        "SELECT i.id, i.batch_id, i.photo_filename, i.tracking_number, i.item_status, i.voided_at,
                {$parcelCol} b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.id = ? LIMIT 1"
    );
    $st->execute([$itemId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Delivery item not found.');
    }
    if ($row['voided_at'] !== null && (string) $row['voided_at'] !== '') {
        throw new RuntimeException('Delivery item not found.');
    }

    return $row;
}

function deliveries_delete_item_photo_file(string $photoFilename): void
{
    $safe = rts_storage_normalize_stored_filename(RTS_STORAGE_BUCKET_DELIVERIES, $photoFilename);
    if ($safe === '') {
        return;
    }
    $path = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $safe;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Resolve linked online_order_parcels.id from a delivery_batch_items row.
 */
function deliveries_parcel_id_from_item(PDO $pdo, array $row): ?int
{
    if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {
        $pid = (int) ($row['online_parcel_id'] ?? 0);
        if ($pid > 0) {
            return $pid;
        }
    }
    $tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));
    if ($tracking === '') {
        return null;
    }
    $parcel = online_parcels_get_by_tracking($pdo, $tracking);

    return $parcel !== null ? (int) ($parcel['id'] ?? 0) : null;
}

/**
 * Undo ship + packaging deduct when the last delivery photo for a parcel is removed.
 */
function deliveries_revert_parcel_to_packing_if_shipped(PDO $pdo, int $parcelId): void
{
    if ($parcelId < 1) {
        return;
    }

    online_parcels_ensure_schema($pdo);
    $st = $pdo->prepare('SELECT id, status FROM online_order_parcels WHERE id = ? FOR UPDATE');
    $st->execute([$parcelId]);
    $locked = $st->fetch(PDO::FETCH_ASSOC);
    if ($locked === false || (string) ($locked['status'] ?? '') !== 'shipped') {
        return;
    }

    honey_parcel_revert_deduct($pdo, $parcelId);
    $pdo->prepare(
        'UPDATE online_order_parcels
         SET status = "packing", ship_date = NULL, shipped_by_user_id = NULL, deducted_at = NULL
         WHERE id = ?'
    )->execute([$parcelId]);
}

/**
 * Shipped parcel with no delivery photos left — repair after mistaken delete (legacy rows).
 *
 * @param array<string, mixed>|null $parcel
 * @return array<string, mixed>|null
 */
function deliveries_repair_orphaned_shipped_parcel(PDO $pdo, ?array $parcel): ?array
{
    if ($parcel === null || (string) ($parcel['status'] ?? '') !== 'shipped') {
        return $parcel;
    }

    $parcelId = (int) ($parcel['id'] ?? 0);
    $tracking = (string) ($parcel['tracking_number'] ?? '');
    if ($parcelId < 1) {
        return $parcel;
    }

    if (deliveries_find_existing_recordings($pdo, $tracking, $parcelId) !== []) {
        return $parcel;
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        deliveries_revert_parcel_to_packing_if_shipped($pdo, $parcelId);
        if ($ownsTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return online_parcels_get_by_id($pdo, $parcelId) ?? $parcel;
}

/**
 * Permanently remove a mistaken/duplicate delivery row and its photo file.
 * When this was the last photo for a shipped parcel, revert parcel to packing and restore stock.
 */
function deliveries_delete_item_by_id(PDO $pdo, int $itemId): bool
{
    $row = deliveries_get_batch_item_row($pdo, $itemId);
    $photoFn = trim((string) ($row['photo_filename'] ?? ''));
    $parcelId = deliveries_parcel_id_from_item($pdo, $row);
    $tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'DELETE FROM edit_requests WHERE table_name = "delivery_batch_items" AND record_id = ? AND status = "pending"'
        )->execute([$itemId]);

        $st = $pdo->prepare('DELETE FROM delivery_batch_items WHERE id = ?');
        $st->execute([$itemId]);
        if ($st->rowCount() === 0) {
            $pdo->rollBack();

            return false;
        }

        if ($parcelId !== null && $parcelId > 0) {
            $remaining = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);
            if ($remaining === []) {
                deliveries_revert_parcel_to_packing_if_shipped($pdo, $parcelId);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    if ($photoFn !== '') {
        deliveries_delete_item_photo_file($photoFn);
    }

    return true;
}

/** @deprecated Use deliveries_delete_item_by_id — kept for legacy callers. */
function deliveries_void_item_by_id(PDO $pdo, int $itemId): bool
{
    return deliveries_delete_item_by_id($pdo, $itemId);
}

function deliveries_resolve_item_by_id(PDO $pdo, int $itemId, string $tracking): bool
{
    $row = deliveries_get_batch_item_row($pdo, $itemId);

    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        throw new RuntimeException('Invalid tracking number.');
    }

    $parcelId = null;
    if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {
        $pid = (int) ($row['online_parcel_id'] ?? 0);
        if ($pid > 0) {
            $parcelId = $pid;
        }
    }
    deliveries_assert_tracking_not_duplicate($pdo, $tracking, $itemId, $parcelId);

    $st = $pdo->prepare(
        'UPDATE delivery_batch_items
         SET tracking_number = ?, item_status = "resolved", resolved_at = NOW()
         WHERE id = ? AND voided_at IS NULL'
    );
    $st->execute([$tracking, $itemId]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Item not found.');
    }

    return true;
}

/**
 * @return array{old_value: string, new_value: string}
 */
function delivery_edit_request_normalize_values(string $field, string $oldValue, string $newValue): array
{
    if ($field === 'void' || $field === 'delete') {
        return [
            'old_value' => $oldValue !== '' ? $oldValue : 'active',
            'new_value' => 'deleted',
        ];
    }
    if ($field === 'tracking_number') {
        $normalized = online_parcels_normalize_tracking($newValue);
        if ($normalized === '') {
            throw new RuntimeException('Invalid tracking number.');
        }

        return [
            'old_value' => $oldValue,
            'new_value' => $normalized,
        ];
    }

    throw new RuntimeException('Unsupported delivery change.');
}

function delivery_edit_request_assert_no_duplicate_pending(PDO $pdo, int $itemId, string $field): void
{
    $st = $pdo->prepare(
        'SELECT id FROM edit_requests
         WHERE table_name = "delivery_batch_items" AND record_id = ? AND field_name = ? AND status = "pending"
         LIMIT 1'
    );
    $st->execute([$itemId, $field]);
    if ($st->fetchColumn() !== false) {
        throw new RuntimeException('A pending request already exists for this item.');
    }
}

/**
 * @return array<string, mixed>
 */
function delivery_edit_request_validate_submit(PDO $pdo, int $itemId, string $field, string $newValue): array
{
    if (!in_array($field, ['void', 'delete', 'tracking_number'], true)) {
        throw new RuntimeException('Invalid delivery change type.');
    }

    $row = deliveries_get_batch_item_row($pdo, $itemId);

    if ($field === 'void' || $field === 'delete') {
        $st = $pdo->prepare(
            'SELECT id FROM edit_requests
             WHERE table_name = "delivery_batch_items" AND record_id = ? AND field_name IN ("void", "delete")
               AND status = "pending"
             LIMIT 1'
        );
        $st->execute([$itemId]);
        if ($st->fetchColumn() !== false) {
            throw new RuntimeException('A pending delete request already exists for this item.');
        }
    } else {
        delivery_edit_request_assert_no_duplicate_pending($pdo, $itemId, $field);
    }

    $currentTn = trim((string) ($row['tracking_number'] ?? ''));
    if ($field === 'void' || $field === 'delete') {
        delivery_edit_request_normalize_values($field, $currentTn !== '' ? $currentTn : (string) $row['item_status'], '');

        return $row;
    }

    if ($newValue === '') {
        throw new RuntimeException('Tracking number is required.');
    }

    $normalized = online_parcels_normalize_tracking($newValue);
    $parcelId = null;
    if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {
        $pid = (int) ($row['online_parcel_id'] ?? 0);
        if ($pid > 0) {
            $parcelId = $pid;
        }
    }
    deliveries_assert_tracking_not_duplicate($pdo, $normalized, $itemId, $parcelId);
    delivery_edit_request_normalize_values($field, $currentTn, $newValue);

    return $row;
}

/**
 * Apply an approved edit_requests row for delivery_batch_items.
 *
 * @param array<string, mixed> $req
 */
function delivery_edit_request_apply(PDO $pdo, array $req): void
{
    $itemId = (int) ($req['record_id'] ?? 0);
    $field = (string) ($req['field_name'] ?? '');
    if ($itemId < 1) {
        throw new RuntimeException('Invalid delivery item.');
    }

    if ($field === 'void' || $field === 'delete') {
        if (!deliveries_delete_item_by_id($pdo, $itemId)) {
            throw new RuntimeException('Item not found.');
        }

        return;
    }

    if ($field === 'tracking_number') {
        deliveries_resolve_item_by_id($pdo, $itemId, (string) ($req['new_value'] ?? ''));

        return;
    }

    throw new RuntimeException('Unsupported delivery change.');
}

/**
 * Attach online-order parcel details (name, items, status) to delivery review rows.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function deliveries_enrich_review_rows(PDO $pdo, array $rows): array
{
    if ($rows === []) {
        return [];
    }

    online_parcels_ensure_schema($pdo);
    $hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');

    $parcelIds = [];
    $trackings = [];
    foreach ($rows as $row) {
        if ($hasLink) {
            $pid = (int) ($row['online_parcel_id'] ?? 0);
            if ($pid > 0) {
                $parcelIds[$pid] = true;
            }
        }
        $tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));
        if ($tn !== '') {
            $trackings[$tn] = true;
        }
    }

    $byId = [];
    $byTracking = [];

    if ($parcelIds !== []) {
        $ids = array_keys($parcelIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT * FROM online_order_parcels WHERE id IN ({$placeholders})");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $raw) {
            $parcel = online_parcels_format_row($raw, null);
            if ($parcel === null) {
                continue;
            }
            $byId[(int) $parcel['id']] = $parcel;
            $byTracking[(string) $parcel['tracking_number']] = $parcel;
        }
    }

    $missingTn = [];
    foreach (array_keys($trackings) as $tn) {
        if (!isset($byTracking[$tn])) {
            $missingTn[] = $tn;
        }
    }
    if ($missingTn !== []) {
        $placeholders = implode(',', array_fill(0, count($missingTn), '?'));
        $st = $pdo->prepare("SELECT * FROM online_order_parcels WHERE tracking_number IN ({$placeholders})");
        $st->execute($missingTn);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $raw) {
            $parcel = online_parcels_format_row($raw, null);
            if ($parcel === null) {
                continue;
            }
            $byId[(int) $parcel['id']] = $parcel;
            $byTracking[(string) $parcel['tracking_number']] = $parcel;
        }
    }

    $out = [];
    foreach ($rows as $row) {
        $parcel = null;
        if ($hasLink) {
            $pid = (int) ($row['online_parcel_id'] ?? 0);
            if ($pid > 0) {
                $parcel = $byId[$pid] ?? null;
            }
        }
        if ($parcel === null) {
            $tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));
            if ($tn !== '') {
                $parcel = $byTracking[$tn] ?? null;
            }
        }

        $row['parcel'] = $parcel !== null ? [
            'id' => (int) $parcel['id'],
            'tracking_number' => (string) $parcel['tracking_number'],
            'recipient_name' => (string) $parcel['recipient_name'],
            'items_raw' => (string) $parcel['items_raw'],
            'order_no' => $parcel['order_no'] !== null ? (string) $parcel['order_no'] : null,
            'courier' => (string) $parcel['courier'],
            'status' => (string) $parcel['status'],
        ] : null;
        $out[] = $row;
    }

    return $out;
}
