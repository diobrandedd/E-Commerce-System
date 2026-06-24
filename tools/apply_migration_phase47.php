<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();

$col = $pdo->query("SHOW COLUMNS FROM rts_damaged_use_events LIKE 'outcome'")->fetch(PDO::FETCH_ASSOC);
$type = (string) ($col['Type'] ?? '');
if (strpos($type, 'cap_reseal') === false) {
    $pdo->exec(
        "ALTER TABLE `rts_damaged_use_events`
         MODIFY COLUMN `outcome` ENUM('mix_production','scrap','cap_reseal') NOT NULL"
    );
    echo "outcome enum extended with cap_reseal\n";
} else {
    echo "cap_reseal outcome already present\n";
}

$src = $pdo->query("SHOW COLUMNS FROM rts_inventory_rows LIKE 'source_line_item_id'")->fetch(PDO::FETCH_ASSOC);
if (($src['Null'] ?? '') !== 'YES') {
    $pdo->exec('ALTER TABLE `rts_inventory_rows` MODIFY COLUMN `source_line_item_id` INT UNSIGNED NULL DEFAULT NULL');
    echo "source_line_item_id nullable ok\n";
} else {
    echo "source_line_item_id already nullable\n";
}

echo "done\n";
