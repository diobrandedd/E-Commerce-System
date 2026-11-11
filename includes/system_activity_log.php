<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/** @var list<string> */
const SYSTEM_ACTIVITY_CATEGORIES = [
    'product_setup',
    'rts',
    'deliveries',
    'order_management',
    'pos',
    'sales',
    'inventory',
    'staff_settings',
];

/**
 * Append one system activity row. Soft-fails so audit never breaks stock/POS.
 *
 * @param array{
 *   entity_type?: ?string,
 *   entity_id?: int|string|null,
 *   old_value?: ?string,
 *   new_value?: ?string,
 *   meta?: array<string, mixed>|null,
 *   actor_user_id?: int|null
 * } $opts
 */
function system_activity_log_write(
    PDO $pdo,
    string $category,
    string $action,
    string $summary,
    array $opts = []
): void {
    try {
        $category = trim($category);
        $action = trim($action);
        $summary = trim($summary);
        if ($category === '' || $action === '' || $summary === '') {
            return;
        }
        if (!in_array($category, SYSTEM_ACTIVITY_CATEGORIES, true)) {
            $category = 'inventory';
        }
        if (function_exists('mb_substr')) {
            $summary = mb_substr($summary, 0, 500);
            $action = mb_substr($action, 0, 64);
        } else {
            $summary = substr($summary, 0, 500);
            $action = substr($action, 0, 64);
        }

        $entityType = isset($opts['entity_type']) ? trim((string) $opts['entity_type']) : null;
        if ($entityType === '') {
            $entityType = null;
        }
        $entityId = null;
        if (isset($opts['entity_id']) && $opts['entity_id'] !== null && $opts['entity_id'] !== '') {
            $entityId = (string) $opts['entity_id'];
            if (function_exists('mb_substr')) {
                $entityId = mb_substr($entityId, 0, 64);
            } else {
                $entityId = substr($entityId, 0, 64);
            }
        }

        $old = isset($opts['old_value']) ? ($opts['old_value'] !== null ? (string) $opts['old_value'] : null) : null;
        $new = isset($opts['new_value']) ? ($opts['new_value'] !== null ? (string) $opts['new_value'] : null) : null;
        $metaJson = null;
        if (isset($opts['meta']) && is_array($opts['meta']) && $opts['meta'] !== []) {
            $encoded = json_encode($opts['meta'], JSON_UNESCAPED_UNICODE);
            $metaJson = $encoded !== false ? $encoded : null;
        }
        $actor = isset($opts['actor_user_id']) ? (int) $opts['actor_user_id'] : null;
        if ($actor !== null && $actor < 1) {
            $actor = null;
        }

        $pdo->prepare(
            'INSERT INTO system_activity_log
                (category, action, summary, entity_type, entity_id, old_value, new_value, meta_json, actor_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $category,
            $action,
            $summary,
            $entityType,
            $entityId,
            $old,
            $new,
            $metaJson,
            $actor,
        ]);
    } catch (Throwable $e) {
        error_log('system_activity_log_write: ' . $e->getMessage());
    }
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int}
 */
function system_activity_log_list(
    PDO $pdo,
    int $page = 1,
    int $perPage = 20,
    ?string $category = null,
    ?string $q = null,
    ?string $dateFrom = null,
    ?string $dateTo = null
): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];

    if ($category !== null && $category !== '' && in_array($category, SYSTEM_ACTIVITY_CATEGORIES, true)) {
        $where[] = 'l.category = ?';
        $params[] = $category;
    }
    if ($dateFrom !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    $q = $q !== null ? trim($q) : '';
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(l.summary LIKE ? OR l.action LIKE ? OR l.entity_type LIKE ? OR l.entity_id LIKE ?
            OR l.old_value LIKE ? OR l.new_value LIKE ? OR u.name LIKE ? OR u.username LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
    }

    $sqlWhere = implode(' AND ', $where);

    $countSt = $pdo->prepare(
        "SELECT COUNT(*) FROM system_activity_log l
         LEFT JOIN users u ON u.id = l.actor_user_id
         WHERE {$sqlWhere}"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $st = $pdo->prepare(
        "SELECT l.id, l.category, l.action, l.summary, l.entity_type, l.entity_id,
                l.old_value, l.new_value, l.meta_json, l.actor_user_id, l.created_at,
                u.name AS actor_name, u.username AS actor_username
         FROM system_activity_log l
         LEFT JOIN users u ON u.id = l.actor_user_id
         WHERE {$sqlWhere}
         ORDER BY l.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $meta = null;
        if (!empty($row['meta_json'])) {
            $decoded = json_decode((string) $row['meta_json'], true);
            $meta = is_array($decoded) ? $decoded : null;
        }
        $rows[] = [
            'id' => (int) $row['id'],
            'category' => (string) $row['category'],
            'action' => (string) $row['action'],
            'summary' => (string) $row['summary'],
            'entity_type' => $row['entity_type'] !== null ? (string) $row['entity_type'] : null,
            'entity_id' => $row['entity_id'] !== null ? (string) $row['entity_id'] : null,
            'old_value' => $row['old_value'] !== null ? (string) $row['old_value'] : null,
            'new_value' => $row['new_value'] !== null ? (string) $row['new_value'] : null,
            'meta' => $meta,
            'actor_user_id' => $row['actor_user_id'] !== null ? (int) $row['actor_user_id'] : null,
            'actor_name' => $row['actor_name'] !== null ? (string) $row['actor_name'] : null,
            'actor_username' => $row['actor_username'] !== null ? (string) $row['actor_username'] : null,
            'created_at' => (string) $row['created_at'],
        ];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}
