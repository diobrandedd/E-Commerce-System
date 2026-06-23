<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = ims_pdo();
$has = $pdo->query("SHOW COLUMNS FROM rts_cases LIKE 'case_disposition'")->fetch();
if ($has) {
    echo "case_disposition already exists.\n";
    exit(0);
}

$pdo->exec(
    "ALTER TABLE `rts_cases`
      ADD COLUMN `case_disposition` ENUM('standard','swapped','leak') NOT NULL DEFAULT 'standard'
        COMMENT 'standard=normal return; swapped=wrong contents; leak=damaged sellable'
        AFTER `damaged_tag`,
      ADD COLUMN `before_shipment_photo_filename` VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Denormalized copy from delivery_batch_items.photo_filename at save time'
        AFTER `delivery_match_item_id`,
      ADD KEY `idx_rts_case_disposition_opened` (`case_disposition`, `date_opened`)"
);
echo "ALTER ok\n";

$pdo->exec(
    "UPDATE `rts_cases`
     SET `case_disposition` = CASE
       WHEN `damaged_reason` = 'leak' THEN 'leak'
       WHEN `damaged_reason` IN ('item_replaced', 'packaging_replaced') THEN 'swapped'
       ELSE 'standard'
     END
     WHERE `case_disposition` = 'standard'"
);
echo "Backfill case_disposition ok\n";

$pdo->exec(
    "UPDATE `rts_cases` c
     INNER JOIN `delivery_batch_items` i ON i.id = c.delivery_match_item_id
     SET c.before_shipment_photo_filename = NULLIF(TRIM(i.photo_filename), '')
     WHERE c.delivery_match_item_id IS NOT NULL
       AND (c.before_shipment_photo_filename IS NULL OR c.before_shipment_photo_filename = '')"
);
echo "Backfill before_shipment ok\n";

require_once dirname(__DIR__) . '/includes/rts_schema.php';
rts_db_clear_column_cache();
echo "Done.\n";
