<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();

$hasLineRole = $pdo->query("SHOW COLUMNS FROM rts_line_items LIKE 'line_role'")->fetch();
if (!$hasLineRole) {
    $sql = file_get_contents(dirname(__DIR__) . '/sql/migration_phase45_rts_line_role.sql');
    if ($sql === false) {
        fwrite(STDERR, "Could not read migration_phase45\n");
        exit(1);
    }
    $pdo->exec($sql);
    echo "migration_phase45 applied\n";
} else {
    echo "line_role already exists\n";
}

$hasPool = $pdo->query("SHOW COLUMNS FROM rts_inventory_rows LIKE 'inventory_pool'")->fetch();
if (!$hasPool) {
    $sql = file_get_contents(dirname(__DIR__) . '/sql/migration_phase46_rts_damaged_pool.sql');
    if ($sql === false) {
        fwrite(STDERR, "Could not read migration_phase46\n");
        exit(1);
    }
    $pdo->exec($sql);
    echo "migration_phase46 applied\n";
} else {
    echo "inventory_pool already exists\n";
}

$hasEvents = $pdo->query("SHOW TABLES LIKE 'rts_damaged_use_events'")->fetch();
if (!$hasEvents) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `rts_damaged_use_events` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `rts_inventory_row_id` INT UNSIGNED NOT NULL,
          `outcome` ENUM('mix_production','scrap') NOT NULL,
          `variant` VARCHAR(16) NULL DEFAULT NULL,
          `volume_gallons` DECIMAL(12,4) NULL DEFAULT NULL,
          `size_qtys_json` JSON NULL DEFAULT NULL,
          `qty_other` DECIMAL(12,2) NULL DEFAULT NULL,
          `notes` VARCHAR(512) NULL DEFAULT NULL,
          `production_row_id` INT UNSIGNED NULL DEFAULT NULL,
          `performed_by` INT UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_rts_damaged_use_row` (`rts_inventory_row_id`),
          KEY `idx_rts_damaged_use_created` (`created_at`),
          CONSTRAINT `fk_rts_damaged_use_row` FOREIGN KEY (`rts_inventory_row_id`) REFERENCES `rts_inventory_rows` (`id`) ON DELETE RESTRICT,
          CONSTRAINT `fk_rts_damaged_use_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "rts_damaged_use_events created\n";
}

echo "done\n";
