<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/honey_parcel_inventory.php';
require_once __DIR__ . '/qr_decode.php';
require_once __DIR__ . '/rts_schema.php';

function online_parcels_user_can_access(?array $user): bool
{
    if ($user === null) {
        return false;
    }
    $role = (string) ($user['role'] ?? '');

    return in_array($role, ['executive', 'prod_manager', 'csr_tl', 'csr_staff'], true);
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

    return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null);
}

/**
 * @return array<string, mixed>|null
 */
function online_parcels_get_by_id(PDO $pdo, int $id): ?array
{
    online_parcels_ensure_schema($pdo);
    $st = $pdo->prepare('SELECT * FROM online_order_parcels WHERE id = ? LIMIT 1');
    $st->execute([$id]);

    return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null);
}

/**
 * @param array<string, mixed>|false|null $row
 * @return array<string, mixed>|null
 */
function online_parcels_format_row($row): ?array
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

    return [
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
    ];
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

    return ['rows' => $rows, 'total' => $total];
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
