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

    // IMS CSR team leader / staff — same dept gate as waybill stamper + online orders.
    if (in_array($role, ['tl', 'staff'], true)) {
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

    return in_array($role, ['executive', 'prod_manager'], true);
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
          `status` ENUM("packing","shipped","cancelled","returned") NOT NULL DEFAULT "packing",
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

        $exists = $pdo->prepare('SELECT id, status FROM online_order_parcels WHERE tracking_number = ? LIMIT 1');
        $exists->execute([$tn]);
        $row = $exists->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            $errors[] = 'Tracking already registered: ' . $tn . ' (parcel #' . (int) $row['id'] . ').';

            continue;
        }

        $json = json_encode(['deltas' => $parsed['deltas']], JSON_UNESCAPED_UNICODE);
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
    if ($status !== '' && in_array($status, ['packing', 'shipped', 'cancelled', 'returned'], true)) {
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
         WHERE i.id = ? AND i.voided_at IS NULL LIMIT 1'
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
        $lock = $pdo->prepare('SELECT id, status, items_raw FROM online_order_parcels WHERE id = ? FOR UPDATE');
        $lock->execute([(int) $parcel['id']]);
        $locked = $lock->fetch(PDO::FETCH_ASSOC);
        if ($locked === false || (string) $locked['status'] === 'shipped') {
            $pdo->rollBack();
            $fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);

            return ['parcel' => $fresh ?? $parcel, 'already_shipped' => true];
        }

        honey_parcel_apply_deduct($pdo, (int) $parcel['id'], $userId, (string) $locked['items_raw']);

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
