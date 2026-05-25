<?php

declare(strict_types=1);

/**
 * Whether an active (non-voided) delivery row already has this tracking number.
 */
function rts_delivery_tracking_is_duplicate(PDO $pdo, string $tracking, ?int $excludeItemId = null): bool
{
    $tracking = trim($tracking);
    if ($tracking === '') {
        return false;
    }

    $sql = 'SELECT COUNT(*) FROM delivery_batch_items
        WHERE voided_at IS NULL
        AND LOWER(TRIM(COALESCE(tracking_number, \'\'))) = LOWER(TRIM(?))';
    $params = [$tracking];
    if ($excludeItemId !== null && $excludeItemId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeItemId;
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);

    return (int) $st->fetchColumn() > 0;
}
