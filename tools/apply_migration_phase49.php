<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();

function column_type(PDO $pdo, string $table, string $column): string
{
    $st = $pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?');
    $st->execute([$column]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? (string) ($row['Type'] ?? '') : '';
}

function index_exists(PDO $pdo, string $table, string $index): bool
{
    $st = $pdo->prepare('SHOW INDEX FROM `' . str_replace('`', '', $table) . '` WHERE Key_name = ?');
    $st->execute([$index]);

    return (bool) $st->fetch(PDO::FETCH_ASSOC);
}

$status = column_type($pdo, 'online_order_parcels', 'status');
if (strpos($status, 'rts') === false) {
    $pdo->exec(
        "ALTER TABLE `online_order_parcels` MODIFY COLUMN `status`
         ENUM('packing','shipped','on_hold','cancelled','returned','rts') NOT NULL DEFAULT 'packing'"
    );
    echo "online_order_parcels.status + rts\n";
} else {
    echo "online_order_parcels.status already has rts\n";
}

$dce = column_type($pdo, 'delivery_cancel_events', 'previous_status');
if ($dce !== '' && strpos($dce, 'rts') === false) {
    $pdo->exec(
        "ALTER TABLE `delivery_cancel_events` MODIFY COLUMN `previous_status`
         ENUM('packing','shipped','on_hold','cancelled','returned','rts') NOT NULL"
    );
    echo "delivery_cancel_events.previous_status + rts\n";
}

if (column_type($pdo, 'online_order_parcels', 'rts_case_id') === '') {
    $pdo->exec(
        "ALTER TABLE `online_order_parcels`
         ADD COLUMN `rts_case_id` INT UNSIGNED NULL DEFAULT NULL AFTER `deducted_at`,
         ADD COLUMN `status_before_rts` ENUM('packing','shipped','on_hold','cancelled','returned') NULL DEFAULT NULL AFTER `rts_case_id`,
         ADD COLUMN `rts_recorded_at` DATETIME NULL DEFAULT NULL AFTER `status_before_rts`"
    );
    echo "online_order_parcels RTS link columns added\n";
} else {
    echo "online_order_parcels.rts_case_id already exists\n";
}

if (!index_exists($pdo, 'online_order_parcels', 'idx_parcels_rts_case')) {
    $pdo->exec('ALTER TABLE `online_order_parcels` ADD KEY `idx_parcels_rts_case` (`rts_case_id`)');
    echo "idx_parcels_rts_case added\n";
}

echo "done\n";
