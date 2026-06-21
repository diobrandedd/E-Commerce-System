<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/honey_parcel_inventory.php';
require_once __DIR__ . '/qr_decode.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/rts_storage.php';

function online_parcels_user_is_csr_dept(array $user): bool
{
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    if ($deptId < 1) {
        return false;
    }
    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
        $st->execute([$deptId]);
        $name = (string) ($st->fetchColumn() ?: '');

        return strtolower($name) === 'csr';
    } catch (Throwable) {
        return false;
    }
}

function online_parcels_user_can_access(?array $user): bool
{
    if ($user === null) {
        return false;
    }
    $role = (string) ($user['role'] ?? '');

    if (in_array($role, ['executive', 'prod_manager'], true)) {
        return true;
    }

    // SPA-normalized names (session normally stores IMS `tl` / `staff` instead).
    if (in_array($role, ['csr_tl', 'csr_staff'], true)) {
        return true;
    }

    // Operations staff (non-CSR) get parcel visibility as part of the staff role.
    if ($role === 'staff') {
        return true;
    }

    // IMS CSR team leader — same dept gate as waybill stamper + online orders.
    if ($role === 'tl') {
        return online_parcels_user_is_csr_dept($user);
    }

    return false;
}

function online_parcels_user_can_ship(?array $user): bool
{
    if ($user === null) {
        return false;
    }
    $role = (string) ($user['role'] ?? '');

    // Staff record + ship deliveries; executive-only actions (void/delete/resolve)
    // are still gated separately in deliveries.php.
    return in_array($role, ['executive', 'prod_manager', 'staff'], true);
}

function online_parcels_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `online_order_parcels` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `csr_batch_id` INT UNSIGNED NULL DEFAULT NULL,
          `csr_line_id` INT UNSIGNED NULL DEFAULT NULL,
          `tracking_number` VARCHAR(96) NOT NULL,
          `items_raw` TEXT NOT NULL,
          `items_parsed_json` JSON NULL,
          `recipient_name` VARCHAR(255) NOT NULL DEFAULT "",
          `courier` VARCHAR(64) NOT NULL DEFAULT "",
          `order_no` VARCHAR(64) NULL DEFAULT NULL,
          `source_pipeline` ENUM("partner","vip","shopee_spx") NOT NULL,
          `status` ENUM("packing","shipped","on_hold","cancelled","returned") NOT NULL DEFAULT "packing",
          `ship_date` DATE NULL DEFAULT NULL,
          `return_date` DATE NULL DEFAULT NULL,
          `shipped_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
          `deducted_at` DATETIME NULL DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_online_order_parcels_tracking` (`tracking_number`),
          KEY `idx_online_order_parcels_status` (`status`),
          KEY `idx_online_order_parcels_batch` (`csr_batch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `online_parcel_deduct_lines` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `parcel_id` INT UNSIGNED NOT NULL,
          `packaging_row_id` INT UNSIGNED NOT NULL,
          `variant` VARCHAR(32) NOT NULL,
          `size` ENUM("liter","250ml","350ml","gallon") NOT NULL,
          `qty` DECIMAL(12,2) NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_online_parcel_deduct_parcel` (`parcel_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `online_parcel_dept_deduct_lines` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `parcel_id` INT UNSIGNED NOT NULL,
          `dept_row_id` INT UNSIGNED NOT NULL,
          `product_key` VARCHAR(32) NOT NULL,
          `qty` DECIMAL(12,2) NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_opddl_parcel` (`parcel_id`),
          KEY `idx_opddl_dept_row` (`dept_row_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `delivery_cancel_events` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `online_parcel_id` INT UNSIGNED NOT NULL,
          `tracking_number` VARCHAR(96) NOT NULL,
          `previous_status` ENUM("packing","shipped","on_hold","cancelled","returned") NOT NULL,
          `inventory_reverted` TINYINT(1) NOT NULL DEFAULT 0,
          `cancel_source` VARCHAR(32) NOT NULL DEFAULT "scanner",
          `note` VARCHAR(255) NULL DEFAULT NULL,
          `cancelled_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_dce_parcel` (`online_parcel_id`),
          KEY `idx_dce_tracking` (`tracking_number`),
          KEY `idx_dce_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

/**
 * @return array{promoted: int, skipped: int, errors: list<string>}
 */
function online_parcels_promote_from_batch(PDO $pdo, int $batchId): array
{
    online_parcels_ensure_schema($pdo);

    $st = $pdo->prepare(
        'SELECT id, order_no, recipient_name, items, courier, tracking_number, source_pipeline
         FROM csr_online_order_lines WHERE batch_id = ? ORDER BY id ASC'
    );
    $st->execute([$batchId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC);

    $promoted = 0;
    $skipped = 0;
    $errors = [];

    $ins = $pdo->prepare(
        'INSERT INTO online_order_parcels (
            csr_batch_id, csr_line_id, tracking_number, items_raw, items_parsed_json,
            recipient_name, courier, order_no, source_pipeline, status
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "packing")'
    );

    foreach ($lines as $line) {
        $tn = trim((string) ($line['tracking_number'] ?? ''));
        if ($tn === '') {
            $skipped++;
            $errors[] = 'Skipped line #' . (int) $line['id'] . ': missing tracking number.';

            continue;
        }
        $tn = rts_normalize_tracking_from_qr($tn);
        if ($tn === '') {
            $skipped++;
            $errors[] = 'Skipped line #' . (int) $line['id'] . ': invalid tracking number.';

            continue;
        }

        $itemsRaw = trim((string) ($line['items'] ?? ''));
        $parsed = honey_parcel_parse_items_string($itemsRaw);
        if ($parsed['errors'] !== []) {
            $errors[] = 'Line ' . $tn . ': ' . implode(' ', $parsed['errors']);

            continue;
        }
        if ($parsed['deltas'] === [] && ($parsed['other_items'] ?? []) === []) {
            $errors[] = 'Line ' . $tn . ': no recognizable items.';

            continue;
        }

        $exists = $pdo->prepare('SELECT id, status FROM online_order_parcels WHERE tracking_number = ? LIMIT 1');
        $exists->execute([$tn]);
        $row = $exists->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $errors[] = 'Tracking already registered: ' . $tn . ' (parcel #' . (int) $row['id'] . ').';

            continue;
        }

        $json = json_encode(
            ['deltas' => $parsed['deltas'], 'other_items' => $parsed['other_items'] ?? []],
            JSON_UNESCAPED_UNICODE
        );
        $ins->execute([
            $batchId,
            (int) $line['id'],
            $tn,
            $itemsRaw,
            $json,
            (string) ($line['recipient_name'] ?? ''),
            (string) ($line['courier'] ?? ''),
            $line['order_no'] !== null && $line['order_no'] !== '' ? (string) $line['order_no'] : null,
            (string) ($line['source_pipeline'] ?? 'partner'),
        ]);
        $promoted++;
    }

    return ['promoted' => $promoted, 'skipped' => $skipped, 'errors' => $errors];
}

function online_parcels_normalize_tracking(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    return rts_normalize_tracking_from_qr($raw);
}

/**
 * @return array<string, mixed>|null
 */
function online_parcels_get_by_tracking(PDO $pdo, string $tracking): ?array
{
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        return null;
    }
    $st = $pdo->prepare('SELECT * FROM online_order_parcels WHERE tracking_number = ? LIMIT 1');
    $st->execute([$tracking]);

    return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);
}

/**
 * @return array<string, mixed>|null
 */
function online_parcels_get_by_id(PDO $pdo, int $id): ?array
{
    online_parcels_ensure_schema($pdo);
    $st = $pdo->prepare('SELECT * FROM online_order_parcels WHERE id = ? LIMIT 1');
    $st->execute([$id]);

    return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);
}

/**
 * Latest non-voided deliveries photo linked to this parcel (by online_parcel_id or tracking).
 *
 * @return array{delivery_item_id: int, photo_filename: string, photo_url: string, delivery_date: string}|null
 */
function online_parcels_find_packing_photo(PDO $pdo, int $parcelId, string $tracking): ?array
{
    $hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');

    if ($hasLink && $parcelId > 0) {
        $st = $pdo->prepare(
            'SELECT i.id AS delivery_item_id, i.photo_filename, b.delivery_date
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL
               AND i.online_parcel_id = ?
               AND TRIM(COALESCE(i.photo_filename, "")) != ""
             ORDER BY i.id DESC
             LIMIT 1'
        );
        $st->execute([$parcelId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            return online_parcels_format_packing_photo($row);
        }
    }

    if ($tracking === '') {
        return null;
    }

    $st = $pdo->prepare(
        'SELECT i.id AS delivery_item_id, i.photo_filename, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL
           AND TRIM(COALESCE(i.photo_filename, "")) != ""
           AND TRIM(COALESCE(i.tracking_number, "")) = ?
         ORDER BY i.id DESC
         LIMIT 1'
    );
    $st->execute([$tracking]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return null;
    }

    return online_parcels_format_packing_photo($row);
}

/**
 * @param array<string, mixed> $row
 * @return array{delivery_item_id: int, photo_filename: string, photo_url: string, delivery_date: string}|null
 */
function online_parcels_format_packing_photo(array $row): ?array
{
    $fn = trim((string) ($row['photo_filename'] ?? ''));
    if ($fn === '') {
        return null;
    }
    $url = rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn);
    if ($url !== '' && !str_starts_with($url, '/') && !preg_match('#^https?://#i', $url)) {
        $url = '/rts/' . ltrim($url, '/');
    }

    return [
        'delivery_item_id' => (int) ($row['delivery_item_id'] ?? 0),
        'photo_filename' => $fn,
        'photo_url' => $url,
        'delivery_date' => (string) ($row['delivery_date'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $parcel
 * @return array<string, mixed>
 */
function online_parcels_enrich_packing_photo(PDO $pdo, array $parcel): array
{
    $parcel['packing_photo'] = online_parcels_find_packing_photo(
        $pdo,
        (int) ($parcel['id'] ?? 0),
        (string) ($parcel['tracking_number'] ?? '')
    );

    return $parcel;
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function online_parcels_enrich_packing_photos_batch(PDO $pdo, array $rows): array
{
    if ($rows === []) {
        return [];
    }

    $hasLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');
    $byParcelId = [];
    $byTracking = [];

    if ($hasLink) {
        $parcelIds = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $parcelIds[$id] = true;
            }
        }
        if ($parcelIds !== []) {
            $placeholders = implode(',', array_fill(0, count($parcelIds), '?'));
            $st = $pdo->prepare(
                "SELECT i.id AS delivery_item_id, i.photo_filename, b.delivery_date, i.online_parcel_id
                 FROM delivery_batch_items i
                 INNER JOIN delivery_batches b ON b.id = i.batch_id
                 WHERE i.voided_at IS NULL
                   AND TRIM(COALESCE(i.photo_filename, '')) != ''
                   AND i.online_parcel_id IN ({$placeholders})
                 ORDER BY i.id DESC"
            );
            $st->execute(array_keys($parcelIds));
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $photoRow) {
                $pid = (int) ($photoRow['online_parcel_id'] ?? 0);
                if ($pid > 0 && !isset($byParcelId[$pid])) {
                    $formatted = online_parcels_format_packing_photo($photoRow);
                    if ($formatted !== null) {
                        $byParcelId[$pid] = $formatted;
                    }
                }
            }
        }
    }

    $missingTrackings = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0 && isset($byParcelId[$id])) {
            continue;
        }
        $tn = trim((string) ($row['tracking_number'] ?? ''));
        if ($tn !== '') {
            $missingTrackings[$tn] = true;
        }
    }

    if ($missingTrackings !== []) {
        $placeholders = implode(',', array_fill(0, count($missingTrackings), '?'));
        $st = $pdo->prepare(
            "SELECT i.id AS delivery_item_id, i.photo_filename, b.delivery_date, i.tracking_number
             FROM delivery_batch_items i
             INNER JOIN delivery_batches b ON b.id = i.batch_id
             WHERE i.voided_at IS NULL
               AND TRIM(COALESCE(i.photo_filename, '')) != ''
               AND TRIM(COALESCE(i.tracking_number, '')) IN ({$placeholders})
             ORDER BY i.id DESC"
        );
        $st->execute(array_keys($missingTrackings));
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $photoRow) {
            $tn = trim((string) ($photoRow['tracking_number'] ?? ''));
            if ($tn !== '' && !isset($byTracking[$tn])) {
                $formatted = online_parcels_format_packing_photo($photoRow);
                if ($formatted !== null) {
                    $byTracking[$tn] = $formatted;
                }
            }
        }
    }

    foreach ($rows as &$row) {
        $id = (int) ($row['id'] ?? 0);
        $tn = trim((string) ($row['tracking_number'] ?? ''));
        $row['packing_photo'] = $byParcelId[$id] ?? ($tn !== '' ? ($byTracking[$tn] ?? null) : null);
    }
    unset($row);

    return $rows;
}

/**
 * @param array<string, mixed>|false|null $row
 * @return array<string, mixed>|null
 */
function online_parcels_format_row($row, ?PDO $pdo = null): ?array
{
    if (!is_array($row)) {
        return null;
    }
    $parsed = null;
    if (!empty($row['items_parsed_json'])) {
        $decoded = json_decode((string) $row['items_parsed_json'], true);
        if (is_array($decoded)) {
            $parsed = $decoded;
        }
    }

    $formatted = [
        'id' => (int) $row['id'],
        'csr_batch_id' => $row['csr_batch_id'] !== null ? (int) $row['csr_batch_id'] : null,
        'csr_line_id' => $row['csr_line_id'] !== null ? (int) $row['csr_line_id'] : null,
        'tracking_number' => (string) $row['tracking_number'],
        'items_raw' => (string) $row['items_raw'],
        'items_parsed' => $parsed,
        'recipient_name' => (string) $row['recipient_name'],
        'courier' => (string) $row['courier'],
        'order_no' => $row['order_no'] !== null ? (string) $row['order_no'] : null,
        'source_pipeline' => (string) $row['source_pipeline'],
        'status' => (string) $row['status'],
        'ship_date' => $row['ship_date'] !== null ? (string) $row['ship_date'] : null,
        'return_date' => $row['return_date'] !== null ? (string) $row['return_date'] : null,
        'deducted_at' => $row['deducted_at'] !== null ? (string) $row['deducted_at'] : null,
        'created_at' => (string) $row['created_at'],
        'packing_photo' => null,
    ];

    if ($pdo !== null) {
        return online_parcels_enrich_packing_photo($pdo, $formatted);
    }

    return $formatted;
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int}
 */
function online_parcels_list(PDO $pdo, string $q, string $status, int $page, int $perPage): array
{
    online_parcels_ensure_schema($pdo);
    $page = max(1, $page);
    $perPage = min(100, max(10, $perPage));
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(tracking_number LIKE ? OR recipient_name LIKE ? OR items_raw LIKE ? OR order_no LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($status !== '' && in_array($status, ['packing', 'shipped', 'on_hold', 'cancelled', 'returned'], true)) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $whereSql = implode(' AND ', $where);

    $stc = $pdo->prepare("SELECT COUNT(*) FROM online_order_parcels WHERE {$whereSql}");
    $stc->execute($params);
    $total = (int) $stc->fetchColumn();

    $st = $pdo->prepare(
        "SELECT * FROM online_order_parcels WHERE {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $formatted = online_parcels_format_row($r);
        if ($formatted !== null) {
            $rows[] = $formatted;
        }
    }

    return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];
}

/**
 * Ship parcel: requires prior delivery photo (delivery_item_id) for this tracking today.
 *
 * @return array{parcel: array<string, mixed>, already_shipped: bool}
 */
function online_parcels_ship(
    PDO $pdo,
    int $userId,
    string $tracking,
    int $deliveryItemId,
    string $shipDate
): array {
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        throw new RuntimeException('Invalid tracking number.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shipDate)) {
        throw new RuntimeException('Invalid ship_date.');
    }
    if ($deliveryItemId < 1) {
        throw new RuntimeException('A delivery photo is required before shipping (delivery_item_id).');
    }

    $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    if ($parcel === null) {
        throw new RuntimeException('No online order parcel found for this tracking number.');
    }

    if ($parcel['status'] === 'shipped') {
        return ['parcel' => $parcel, 'already_shipped' => true];
    }
    if ($parcel['status'] !== 'packing') {
        throw new RuntimeException('Parcel cannot be shipped (status: ' . $parcel['status'] . ').');
    }

    $stItem = $pdo->prepare(
        'SELECT i.id, i.photo_filename, i.tracking_number, i.online_parcel_id, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.id = ? LIMIT 1'
    );
    $stItem->execute([$deliveryItemId]);
    $item = $stItem->fetch(PDO::FETCH_ASSOC);
    if ($item === false) {
        throw new RuntimeException('Delivery photo row not found.');
    }
    $itemTn = trim((string) ($item['tracking_number'] ?? ''));
    if ($itemTn !== '' && $itemTn !== $tracking) {
        throw new RuntimeException('Delivery photo tracking does not match parcel tracking.');
    }
    if (trim((string) ($item['photo_filename'] ?? '')) === '') {
        throw new RuntimeException('Delivery photo is missing on this row.');
    }

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT id, status, items_raw, deducted_at FROM online_order_parcels WHERE id = ? FOR UPDATE');
        $lock->execute([(int) $parcel['id']]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if ($locked === false || (string) $locked['status'] === 'shipped') {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

            return ['parcel' => $fresh ?? $parcel, 'already_shipped' => true];
        }

        $parcelId = (int) $locked['id'];
        $alreadyDeducted = ($locked['deducted_at'] ?? null) !== null
            || parcel_fulfillment_already_deducted($pdo, $parcelId);
        if (!$alreadyDeducted) {
            $stHoney = $pdo->prepare('SELECT 1 FROM online_parcel_deduct_lines WHERE parcel_id = ? LIMIT 1');
            $stHoney->execute([$parcelId]);
            $alreadyDeducted = (bool) $stHoney->fetchColumn();
        }
        if (!$alreadyDeducted) {
            honey_parcel_apply_deduct($pdo, $parcelId, $userId, (string) $locked['items_raw']);
        }

        $upd = $pdo->prepare(
            'UPDATE online_order_parcels
             SET status = "shipped", ship_date = ?, shipped_by_user_id = ?, deducted_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([$shipDate, $userId, (int) $parcel['id']]);

        if (rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id')) {
            $pdo->prepare('UPDATE delivery_batch_items SET online_parcel_id = ? WHERE id = ?')
                ->execute([(int) $parcel['id'], $deliveryItemId]);
            if ($itemTn === '') {
                $pdo->prepare(
                    'UPDATE delivery_batch_items SET tracking_number = ?, item_status = "resolved" WHERE id = ?'
                )->execute([$tracking, $deliveryItemId]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

    return ['parcel' => $fresh ?? $parcel, 'already_shipped' => false];
}

/**
 * @return array{
 *   parcel: array<string,mixed>,
 *   already_cancelled: bool,
 *   previous_status: string,
 *   inventory_reverted: bool,
 *   event_id: int|null
 * }
 */
function online_parcels_cancel(
    PDO $pdo,
    int $userId,
    string $tracking,
    string $cancelSource = 'scanner',
    ?string $note = null
): array {
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        throw new RuntimeException('Invalid tracking number.');
    }

    $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    if ($parcel === null) {
        throw new RuntimeException('No online order parcel found for this tracking number.');
    }

    $source = trim($cancelSource);
    if ($source === '') {
        $source = 'scanner';
    }
    $note = $note !== null ? trim($note) : null;
    if ($note === '') {
        $note = null;
    }
    if ($note !== null && function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 255);
    } elseif ($note !== null) {
        $note = substr($note, 0, 255);
    }

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare(
            'SELECT id, tracking_number, status, items_raw
             FROM online_order_parcels
             WHERE id = ?
             FOR UPDATE'
        );
        $lock->execute([(int) $parcel['id']]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if ($locked === false) {
            throw new RuntimeException('Parcel not found.');
        }

        $previousStatus = (string) ($locked['status'] ?? '');
        if ($previousStatus === 'cancelled') {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

            return [
                'parcel' => $fresh ?? $parcel,
                'already_cancelled' => true,
                'previous_status' => 'cancelled',
                'inventory_reverted' => false,
                'event_id' => null,
            ];
        }

        if (!in_array($previousStatus, ['packing', 'shipped'], true)) {
            throw new RuntimeException('Parcel cannot be cancelled (status: ' . $previousStatus . ').');
        }

        // Courier cancel — sealed box is reused via re-waybill; do not restore inventory.
        $inventoryReverted = false;

        $upd = $pdo->prepare(
            'UPDATE online_order_parcels
             SET status = "cancelled",
                 ship_date = NULL,
                 shipped_by_user_id = NULL,
                 return_date = NULL
             WHERE id = ?'
        );
        $upd->execute([(int) $locked['id']]);

        $insEvent = $pdo->prepare(
            'INSERT INTO delivery_cancel_events
                (online_parcel_id, tracking_number, previous_status, inventory_reverted, cancel_source, note, cancelled_by_user_id)
             VALUES
                (?, ?, ?, ?, ?, ?, ?)'
        );
        $insEvent->execute([
            (int) $locked['id'],
            (string) $locked['tracking_number'],
            $previousStatus,
            $inventoryReverted ? 1 : 0,
            $source,
            $note,
            $userId > 0 ? $userId : null,
        ]);
        $eventId = (int) $pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

    return [
        'parcel' => $fresh ?? $parcel,
        'already_cancelled' => false,
        'previous_status' => isset($previousStatus) ? $previousStatus : (string) ($parcel['status'] ?? ''),
        'inventory_reverted' => $inventoryReverted ?? false,
        'event_id' => $eventId ?? null,
    ];
}

/**
 * Mark parcel on hold for re-waybill — no inventory revert.
 *
 * @return array{parcel: array<string,mixed>, already_on_hold: bool, previous_status: string, event_id: int|null}
 */
function online_parcels_hold(
    PDO $pdo,
    int $userId,
    string $tracking,
    ?string $note = null
): array {
    online_parcels_ensure_schema($pdo);
    $tracking = online_parcels_normalize_tracking($tracking);
    if ($tracking === '') {
        throw new RuntimeException('Invalid tracking number.');
    }

    $parcel = online_parcels_get_by_tracking($pdo, $tracking);
    if ($parcel === null) {
        throw new RuntimeException('No online order parcel found for this tracking number.');
    }

    $note = $note !== null ? trim($note) : null;
    if ($note === '') {
        $note = null;
    }
    if ($note !== null && function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 255);
    } elseif ($note !== null) {
        $note = substr($note, 0, 255);
    }

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare(
            'SELECT id, tracking_number, status, deducted_at FROM online_order_parcels WHERE id = ? FOR UPDATE'
        );
        $lock->execute([(int) $parcel['id']]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if ($locked === false) {
            throw new RuntimeException('Parcel not found.');
        }

        $previousStatus = (string) ($locked['status'] ?? '');
        if ($previousStatus === 'on_hold') {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

            return [
                'parcel' => $fresh ?? $parcel,
                'already_on_hold' => true,
                'previous_status' => 'on_hold',
                'event_id' => null,
            ];
        }

        if (!in_array($previousStatus, ['packing', 'shipped'], true)) {
            throw new RuntimeException('Parcel cannot be placed on hold (status: ' . $previousStatus . ').');
        }

        $upd = $pdo->prepare('UPDATE online_order_parcels SET status = "on_hold" WHERE id = ?');
        $upd->execute([(int) $locked['id']]);

        $eventId = null;
        try {
            $ins = $pdo->prepare(
                'INSERT INTO online_parcel_hold_events
                    (online_parcel_id, tracking_number, previous_status, note, held_by_user_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $ins->execute([
                (int) $locked['id'],
                (string) $locked['tracking_number'],
                $previousStatus,
                $note,
                $userId > 0 ? $userId : null,
            ]);
            $eventId = (int) $pdo->lastInsertId();
        } catch (Throwable) {
            // Table may not exist before migration.
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

    return [
        'parcel' => $fresh ?? $parcel,
        'already_on_hold' => false,
        'previous_status' => $previousStatus ?? '',
        'event_id' => $eventId,
    ];
}

/**
 * Link a new waybill to an on-hold or courier-cancelled parcel (same sealed box).
 *
 * @return array{parcel: array<string,mixed>, history_id: int}
 */
function online_parcels_rewaybill(
    PDO $pdo,
    int $userId,
    string $oldTracking,
    string $newTracking
): array {
    online_parcels_ensure_schema($pdo);
    $oldTracking = online_parcels_normalize_tracking($oldTracking);
    $newTracking = online_parcels_normalize_tracking($newTracking);
    if ($oldTracking === '' || $newTracking === '') {
        throw new RuntimeException('Invalid tracking number.');
    }
    if ($oldTracking === $newTracking) {
        throw new RuntimeException('New tracking must differ from the old tracking.');
    }

    $parcel = online_parcels_get_by_tracking($pdo, $oldTracking);
    if ($parcel === null) {
        throw new RuntimeException('No parcel found for old tracking number.');
    }

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare(
            'SELECT id, status, tracking_number, csr_batch_id, csr_line_id FROM online_order_parcels WHERE id = ? FOR UPDATE'
        );
        $lock->execute([(int) $parcel['id']]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if ($locked === false) {
            throw new RuntimeException('Parcel not found.');
        }
        if ((string) ($locked['status'] ?? '') !== 'on_hold' && (string) ($locked['status'] ?? '') !== 'cancelled') {
            throw new RuntimeException('Parcel must be on hold or cancelled (courier cancel) before re-waybill.');
        }

        $dup = $pdo->prepare('SELECT id, status, csr_line_id, csr_batch_id FROM online_order_parcels WHERE tracking_number = ? LIMIT 1 FOR UPDATE');
        $dup->execute([$newTracking]);
        $dupRow = $dup->fetch(PDO::FETCH_ASSOC);
        $newLineId = null;
        $newBatchId = null;

        if ($dupRow !== false && (int) $dupRow['id'] !== (int) $locked['id']) {
            $dupStatus = (string) ($dupRow['status'] ?? '');
            if ($dupStatus !== 'packing') {
                throw new RuntimeException('New tracking is already used by another parcel.');
            }
            $newLineId = $dupRow['csr_line_id'] !== null ? (int) $dupRow['csr_line_id'] : null;
            $newBatchId = $dupRow['csr_batch_id'] !== null ? (int) $dupRow['csr_batch_id'] : null;
            $pdo->prepare('DELETE FROM online_order_parcels WHERE id = ?')->execute([(int) $dupRow['id']]);
        } else {
            $lineSt = $pdo->prepare(
                'SELECT id, batch_id FROM csr_online_order_lines WHERE tracking_number = ? ORDER BY id DESC LIMIT 1'
            );
            $lineSt->execute([$newTracking]);
            $csrLine = $lineSt->fetch(PDO::FETCH_ASSOC);
            if ($csrLine !== false) {
                $newLineId = (int) $csrLine['id'];
                $newBatchId = (int) $csrLine['batch_id'];
            }
        }

        $historyId = 0;
        try {
            $hist = $pdo->prepare(
                'INSERT INTO online_parcel_tracking_history
                    (parcel_id, old_tracking, new_tracking, changed_by)
                 VALUES (?, ?, ?, ?)'
            );
            $hist->execute([
                (int) $locked['id'],
                (string) $locked['tracking_number'],
                $newTracking,
                $userId > 0 ? $userId : null,
            ]);
            $historyId = (int) $pdo->lastInsertId();
        } catch (Throwable) {
            throw new RuntimeException('Tracking history table missing. Run migration phase 34.');
        }

        $upd = $pdo->prepare(
            'UPDATE online_order_parcels
             SET tracking_number = ?, status = "packing", csr_line_id = COALESCE(?, csr_line_id), csr_batch_id = COALESCE(?, csr_batch_id)
             WHERE id = ?'
        );
        $upd->execute([
            $newTracking,
            $newLineId,
            $newBatchId,
            (int) $locked['id'],
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

    return [
        'parcel' => $fresh ?? $parcel,
        'history_id' => $historyId,
    ];
}

/**
 * @return array{rows: list<array<string,mixed>>, total: int}
 */
function online_parcels_cancel_log_list(PDO $pdo, string $q, string $from, string $to, int $page, int $perPage): array
{
    online_parcels_ensure_schema($pdo);
    $page = max(1, $page);
    $perPage = min(100, max(10, $perPage));
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'DATE(e.created_at) >= ?';
        $params[] = $from;
    }
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'DATE(e.created_at) <= ?';
        $params[] = $to;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(e.tracking_number LIKE ? OR p.recipient_name LIKE ? OR p.items_raw LIKE ? OR p.order_no LIKE ? OR u.name LIKE ? OR u.username LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = implode(' AND ', $where);
    $countSql = "SELECT COUNT(*)
                 FROM delivery_cancel_events e
                 LEFT JOIN online_order_parcels p ON p.id = e.online_parcel_id
                 LEFT JOIN users u ON u.id = e.cancelled_by_user_id
                 WHERE {$whereSql}";
    $stc = $pdo->prepare($countSql);
    $stc->execute($params);
    $total = (int) $stc->fetchColumn();

    $sql = "SELECT e.id, e.online_parcel_id, e.tracking_number, e.previous_status, e.inventory_reverted,
                   e.cancel_source, e.note, e.cancelled_by_user_id, e.created_at,
                   p.recipient_name, p.items_raw, p.order_no, p.courier,
                   u.name AS cancelled_by_name, u.username AS cancelled_by_username
            FROM delivery_cancel_events e
            LEFT JOIN online_order_parcels p ON p.id = e.online_parcel_id
            LEFT JOIN users u ON u.id = e.cancelled_by_user_id
            WHERE {$whereSql}
            ORDER BY e.id DESC
            LIMIT {$perPage} OFFSET {$offset}";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'id' => (int) ($row['id'] ?? 0),
            'online_parcel_id' => (int) ($row['online_parcel_id'] ?? 0),
            'tracking_number' => (string) ($row['tracking_number'] ?? ''),
            'previous_status' => (string) ($row['previous_status'] ?? ''),
            'inventory_reverted' => ((int) ($row['inventory_reverted'] ?? 0)) === 1,
            'cancel_source' => (string) ($row['cancel_source'] ?? ''),
            'note' => $row['note'] !== null ? (string) $row['note'] : null,
            'cancelled_by_user_id' => $row['cancelled_by_user_id'] !== null ? (int) $row['cancelled_by_user_id'] : null,
            'cancelled_by_name' => $row['cancelled_by_name'] !== null ? (string) $row['cancelled_by_name'] : null,
            'cancelled_by_username' => $row['cancelled_by_username'] !== null ? (string) $row['cancelled_by_username'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'recipient_name' => $row['recipient_name'] !== null ? (string) $row['recipient_name'] : null,
            'items_raw' => $row['items_raw'] !== null ? (string) $row['items_raw'] : null,
            'order_no' => $row['order_no'] !== null ? (string) $row['order_no'] : null,
            'courier' => $row['courier'] !== null ? (string) $row['courier'] : null,
        ];
    }

    return ['rows' => $rows, 'total' => $total];
}
