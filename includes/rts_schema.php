<?php

declare(strict_types=1);

require_once __DIR__ . '/rts_products.php';

/** Whether a column exists on a table (cached per request). */
/** @return array<string, bool> */
function &rts_db_column_cache(): array
{
    static $cache = [];

    return $cache;
}

function rts_db_clear_column_cache(): void
{
    $cache = &rts_db_column_cache();
    foreach (array_keys($cache) as $key) {
        unset($cache[$key]);
    }
}

function rts_db_has_column(PDO $pdo, string $table, string $column): bool
{
    $cache = &rts_db_column_cache();
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $safeTable = str_replace('`', '', $table);
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $safeTable . '` LIKE ' . $pdo->quote($column));
        $cache[$key] = $stmt !== false && $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (Throwable) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

/** SQL fragment: active (non-voided) cases, or always true if voided_at is missing. */
function rts_cases_void_sql(PDO $pdo, string $alias = ''): string
{
    $prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';

    return rts_db_has_column($pdo, 'rts_cases', 'voided_at')
        ? $prefix . 'voided_at IS NULL'
        : '1=1';
}

/** @return list<string> Backtick-quoted column names present on rts_cases. */
function rts_cases_select_columns(PDO $pdo): array
{
    $wanted = [
        'id',
        'tracking_number',
        'courier_detected',
        'courier_final',
        'shop',
        'vip_subsource',
        'date_delivered',
        'date_opened',
        'documentation_photo',
        'documentation_attachments',
        'damaged_reason',
        'damaged_tag',
        'case_disposition',
        'delivery_match_item_id',
        'before_shipment_photo_filename',
        'claim_status',
        'claim_status_note',
        'created_at',
        'updated_at',
        'voided_at',
    ];
    $cols = [];
    foreach ($wanted as $col) {
        if (rts_db_has_column($pdo, 'rts_cases', $col)) {
            $cols[] = '`' . $col . '`';
        }
    }
    if ($cols === []) {
        foreach (['id', 'tracking_number', 'courier_detected', 'courier_final', 'shop', 'date_delivered', 'date_opened'] as $col) {
            if (rts_db_has_column($pdo, 'rts_cases', $col)) {
                $cols[] = '`' . $col . '`';
            }
        }
    }

    return $cols;
}

/** @return array<string, mixed>|null */
function rts_cases_fetch_by_tracking(PDO $pdo, string $tracking): ?array
{
    $select = rts_cases_select_columns($pdo);
    if ($select === []) {
        return null;
    }
    $sql = 'SELECT ' . implode(', ', $select) . ' FROM rts_cases WHERE tracking_number = ? LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute([$tracking]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row !== false ? $row : null;
}

function rts_v1_pack_to_honey_size(string $pack): string
{
    $p = strtolower(trim($pack));

    return match ($p) {
        '250', '250ml', '250 ml' => '250ml',
        '350', '350ml', '350 ml' => '350ml',
        'liter', '1l', '1 l' => 'liter',
        'gallon' => 'gallon',
        default => $p !== '' ? $p : '250ml',
    };
}

/** Map legacy line row to v2 API shape for the record UI. */
function rts_line_item_row_to_api(array $row): array
{
    if (isset($row['product_type']) && (string) $row['product_type'] !== '') {
        return $row;
    }

    $kind = (string) ($row['kind'] ?? '');
    if ($kind === 'honey') {
        return array_merge($row, [
            'product_type' => 'honey',
            'honey_size' => rts_v1_pack_to_honey_size((string) ($row['pack_size'] ?? '')),
            'honey_variant' => (string) ($row['variant'] ?? ''),
            'combo_product' => null,
            'combo_quantity' => null,
            'combo_honey_size' => null,
            'combo_honey_variant' => null,
        ]);
    }

    $nh = (string) ($row['non_honey_product'] ?? '');
    $map = [
        'paragis_tea' => 'pouch_pt',
        'black_rice_tea' => 'pouch_brc',
        'cacao' => 'jar_cacao',
        'honey_comb' => 'jar_honey_comb',
        'pito_pito' => 'pouch_pt',
    ];

    return array_merge($row, [
        'product_type' => $map[$nh] ?? 'pouch_pt',
        'honey_size' => null,
        'honey_variant' => null,
        'combo_product' => null,
        'combo_quantity' => null,
        'combo_honey_size' => null,
        'combo_honey_variant' => null,
    ]);
}

/** @return list<array<string, mixed>> */
function rts_line_items_fetch_for_case(PDO $pdo, int $caseId): array
{
    if (rts_line_items_schema_v2($pdo)) {
        $st = $pdo->prepare(
            'SELECT id, product_type, honey_size, honey_variant, combo_product, combo_quantity,
                    combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id, created_at
             FROM rts_line_items WHERE case_id = ? ORDER BY id ASC'
        );
        $st->execute([$caseId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $rows;
    }

    $cols = ['id', 'quantity', 'created_at'];
    foreach (['kind', 'variant', 'pack_size', 'non_honey_product', 'is_bonus', 'bonus_parent_id'] as $col) {
        if (rts_db_has_column($pdo, 'rts_line_items', $col)) {
            $cols[] = $col;
        }
    }
    $quoted = array_map(static fn (string $c): string => '`' . $c . '`', $cols);
    $sql = 'SELECT ' . implode(', ', $quoted) . ' FROM rts_line_items WHERE case_id = ? ORDER BY id ASC';
    $st = $pdo->prepare($sql);
    $st->execute([$caseId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map('rts_line_item_row_to_api', $rows);
}

/**
 * Upgrade empty legacy line-items table to v2. Returns error message or null on success.
 */
function rts_line_items_ensure_v2_schema(PDO $pdo): ?string
{
    if (rts_line_items_schema_v2($pdo, true)) {
        return null;
    }
    try {
        $cnt = (int) $pdo->query('SELECT COUNT(*) FROM rts_line_items')->fetchColumn();
    } catch (Throwable $e) {
        return 'Could not read line items table.';
    }
    if ($cnt > 0) {
        return 'Product lines need a database upgrade. In phpMyAdmin run sql/migration_rts_line_items_v2.sql (this removes old line-item rows).';
    }
    try {
        $pdo->exec('DROP TABLE IF EXISTS `rts_line_items`');
        $pdo->exec(
            'CREATE TABLE `rts_line_items` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `case_id` INT UNSIGNED NOT NULL,
              `product_type` VARCHAR(32) NOT NULL,
              `honey_size` VARCHAR(16) NULL,
              `honey_variant` VARCHAR(16) NULL,
              `combo_product` VARCHAR(32) NULL,
              `combo_quantity` DECIMAL(10,2) NULL,
              `combo_honey_size` VARCHAR(16) NULL,
              `combo_honey_variant` VARCHAR(16) NULL,
              `quantity` DECIMAL(10,2) NOT NULL,
              `is_bonus` TINYINT(1) NOT NULL DEFAULT 0,
              `bonus_parent_id` INT UNSIGNED NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_case` (`case_id`),
              KEY `idx_bonus_parent` (`bonus_parent_id`),
              CONSTRAINT `fk_line_case` FOREIGN KEY (`case_id`) REFERENCES `rts_cases` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        rts_db_clear_column_cache();
    } catch (Throwable $e) {
        return 'Could not upgrade line items table. Run sql/migration_rts_line_items_v2.sql in phpMyAdmin.';
    }

    return rts_line_items_schema_v2($pdo, true) ? null : 'Line items upgrade failed.';
}

/**
 * @param array<string, mixed> $row
 */
function rts_cases_insert_row(PDO $pdo, array $row): int
{
    $allowed = [
        'tracking_number',
        'courier_detected',
        'courier_final',
        'shop',
        'vip_subsource',
        'date_delivered',
        'date_opened',
        'documentation_photo',
        'documentation_attachments',
        'damaged_reason',
        'damaged_tag',
        'case_disposition',
        'delivery_match_item_id',
        'before_shipment_photo_filename',
    ];
    $cols = [];
    $placeholders = [];
    $params = [];
    foreach ($allowed as $col) {
        if (!array_key_exists($col, $row) || !rts_db_has_column($pdo, 'rts_cases', $col)) {
            continue;
        }
        $cols[] = '`' . $col . '`';
        $placeholders[] = '?';
        $params[] = $row[$col];
    }
    if ($cols === []) {
        throw new RuntimeException('No writable columns on rts_cases');
    }
    $sql = 'INSERT INTO rts_cases (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $pdo->prepare($sql)->execute($params);

    return (int) $pdo->lastInsertId();
}

/**
 * @param array<string, mixed> $row
 */
function rts_cases_update_row(PDO $pdo, int $caseId, array $row): void
{
    $allowed = [
        'tracking_number',
        'courier_detected',
        'courier_final',
        'shop',
        'vip_subsource',
        'date_delivered',
        'date_opened',
        'documentation_photo',
        'documentation_attachments',
        'damaged_reason',
        'damaged_tag',
        'case_disposition',
        'delivery_match_item_id',
        'before_shipment_photo_filename',
        'updated_at',
    ];
    $sets = [];
    $params = [];
    foreach ($allowed as $col) {
        if ($col === 'updated_at') {
            if (rts_db_has_column($pdo, 'rts_cases', 'updated_at')) {
                $sets[] = '`updated_at` = CURRENT_TIMESTAMP';
            }
            continue;
        }
        if (!array_key_exists($col, $row) || !rts_db_has_column($pdo, 'rts_cases', $col)) {
            continue;
        }
        $sets[] = '`' . $col . '` = ?';
        $params[] = $row[$col];
    }
    if ($sets === []) {
        return;
    }
    $params[] = $caseId;
    $sql = 'UPDATE rts_cases SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($params);
}

/** @param list<array<string, mixed>> $lines Normalized v2 lines from rts_normalize_line */
function rts_line_items_insert_batch(PDO $pdo, int $caseId, array $lines): void
{
    $schemaErr = rts_line_items_ensure_v2_schema($pdo);
    if ($schemaErr !== null) {
        throw new RuntimeException($schemaErr);
    }
    $li = $pdo->prepare(
        'INSERT INTO rts_line_items (case_id, product_type, honey_size, honey_variant, combo_product, combo_quantity, combo_honey_size, combo_honey_variant, quantity, is_bonus, bonus_parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    );
    $insertedIds = [];
    foreach ($lines as $idx => $L) {
        $parentId = null;
        if (!empty($L['is_bonus']) && isset($L['_bonus_parent_idx'])) {
            $parentId = $insertedIds[(int) $L['_bonus_parent_idx']] ?? null;
        }
        $li->execute([
            $caseId,
            $L['product_type'],
            $L['honey_size'],
            $L['honey_variant'],
            $L['combo_product'],
            $L['combo_quantity'],
            $L['combo_honey_size'],
            $L['combo_honey_variant'],
            $L['quantity'],
            $L['is_bonus'],
            $parentId,
        ]);
        $insertedIds[$idx] = (int) $pdo->lastInsertId();
    }
}

/** Active-case lookup by tracking (respects voided_at when present). */
function rts_cases_find_active_id_by_tracking(PDO $pdo, string $tracking): ?int
{
    $void = rts_cases_void_sql($pdo);
    $st = $pdo->prepare("SELECT id FROM rts_cases WHERE tracking_number = ? AND {$void} LIMIT 1");
    $st->execute([$tracking]);
    $id = $st->fetchColumn();

    return $id !== false ? (int) $id : null;
}
