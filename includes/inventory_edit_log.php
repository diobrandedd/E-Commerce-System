<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function inventory_edit_log_has_source_column(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM inventory_edit_log LIKE 'source'");
        $cache = $st !== false && (bool) $st->fetch();
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

/**
 * Append one inventory edit audit row.
 * Soft-fails if the log table is missing (pre-migration).
 */
function inventory_edit_log_write(
    PDO $pdo,
    int $userId,
    string $rowTable,
    int $rowId,
    string $field,
    ?string $old,
    ?string $new,
    ?string $source = null
): void {
    try {
        if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {
            $pdo->prepare(
                'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$rowTable, $rowId, $field, $old, $new, $userId, $source]);

            return;
        }
        $pdo->prepare(
            'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$rowTable, $rowId, $field, $old, $new, $userId]);
    } catch (Throwable) {
        // Log table may not exist before migration.
    }
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int}
 */
function inventory_edit_log_list(
    PDO $pdo,
    int $limit = 50,
    int $offset = 0,
    ?string $rowTable = null,
    ?string $source = null,
    ?string $dateFrom = null,
    ?string $dateTo = null,
    ?string $q = null
): array {
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);
    $where = ['1=1'];
    $params = [];

    if ($rowTable !== null && $rowTable !== '') {
        $where[] = 'l.row_table = ?';
        $params[] = $rowTable;
    }
    if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {
        $where[] = 'l.source = ?';
        $params[] = $source;
    }
    if ($dateFrom !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    if ($q !== null && trim($q) !== '') {
        $like = '%' . trim($q) . '%';
        $where[] = '(l.field_name LIKE ? OR l.old_value LIKE ? OR l.new_value LIKE ? OR CAST(l.row_id AS CHAR) LIKE ? OR u.name LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);
    $hasSource = inventory_edit_log_has_source_column($pdo);
    $sourceCol = $hasSource ? 'l.source' : 'NULL AS source';

    $countSt = $pdo->prepare(
        "SELECT COUNT(*) FROM inventory_edit_log l
         LEFT JOIN users u ON u.id = l.edited_by
         WHERE {$whereSql}"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $st = $pdo->prepare(
        "SELECT l.id, l.row_table, l.row_id, l.field_name, l.old_value, l.new_value,
                l.edited_by, {$sourceCol}, l.created_at, u.name AS edited_by_name
         FROM inventory_edit_log l
         LEFT JOIN users u ON u.id = l.edited_by
         WHERE {$whereSql}
         ORDER BY l.created_at DESC, l.id DESC
         LIMIT {$limit} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'rows' => array_map(static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'row_table' => (string) $r['row_table'],
                'row_id' => (int) $r['row_id'],
                'field_name' => (string) $r['field_name'],
                'old_value' => $r['old_value'],
                'new_value' => $r['new_value'],
                'edited_by' => (int) $r['edited_by'],
                'edited_by_name' => (string) ($r['edited_by_name'] ?? ''),
                'source' => $r['source'] ?? null,
                'created_at' => (string) $r['created_at'],
            ];
        }, $rows),
        'total' => $total,
    ];
}
