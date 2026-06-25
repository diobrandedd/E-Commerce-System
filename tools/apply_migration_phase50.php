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

if (column_type($pdo, 'pos_sales', 'is_ar') === '') {
    $pdo->exec(
        'ALTER TABLE `pos_sales`
         ADD COLUMN `is_ar` TINYINT(1) NOT NULL DEFAULT 0 AFTER `total_amount`,
         ADD KEY `idx_pos_sales_is_ar` (`is_ar`)'
    );
    echo "pos_sales.is_ar added\n";
} else {
    echo "pos_sales.is_ar already exists\n";
}

if (column_type($pdo, 'ar_credits', 'debtor_name') === '') {
    $pdo->exec(
        "ALTER TABLE `ar_credits`
         MODIFY COLUMN `user_id` INT UNSIGNED NULL,
         ADD COLUMN `debtor_user_id` INT UNSIGNED NULL AFTER `user_id`,
         ADD COLUMN `debtor_name` VARCHAR(160) NOT NULL DEFAULT '' AFTER `debtor_user_id`,
         ADD COLUMN `debtor_mobile` VARCHAR(32) NULL AFTER `debtor_name`,
         ADD COLUMN `pos_sale_id` INT UNSIGNED NULL AFTER `debtor_mobile`,
         ADD COLUMN `source` ENUM('ims_take','pos') NOT NULL DEFAULT 'ims_take' AFTER `pos_sale_id`,
         ADD COLUMN `recorded_by_pin_id` INT UNSIGNED NULL AFTER `source`"
    );
    echo "ar_credits debtor columns added\n";
} else {
    echo "ar_credits debtor columns already exist\n";
}

if (!index_exists($pdo, 'ar_credits', 'idx_ar_credits_debtor_user')) {
    $pdo->exec('ALTER TABLE `ar_credits` ADD KEY `idx_ar_credits_debtor_user` (`debtor_user_id`)');
    echo "idx_ar_credits_debtor_user added\n";
}

if (!index_exists($pdo, 'ar_credits', 'idx_ar_credits_pos_sale')) {
    $pdo->exec('ALTER TABLE `ar_credits` ADD KEY `idx_ar_credits_pos_sale` (`pos_sale_id`)');
    echo "idx_ar_credits_pos_sale added\n";
}

// FKs — best effort (may already exist)
try {
    $pdo->exec(
        'ALTER TABLE `ar_credits`
         ADD CONSTRAINT `fk_ar_credits_debtor_user` FOREIGN KEY (`debtor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL'
    );
    echo "fk_ar_credits_debtor_user added\n";
} catch (Throwable) {
    echo "fk_ar_credits_debtor_user skip\n";
}

try {
    $pdo->exec(
        'ALTER TABLE `ar_credits`
         ADD CONSTRAINT `fk_ar_credits_pos_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE SET NULL'
    );
    echo "fk_ar_credits_pos_sale added\n";
} catch (Throwable) {
    echo "fk_ar_credits_pos_sale skip\n";
}

try {
    $pdo->exec(
        'ALTER TABLE `ar_credits`
         ADD CONSTRAINT `fk_ar_credits_recorded_pin` FOREIGN KEY (`recorded_by_pin_id`) REFERENCES `pos_pins` (`id`) ON DELETE SET NULL'
    );
    echo "fk_ar_credits_recorded_pin added\n";
} catch (Throwable) {
    echo "fk_ar_credits_recorded_pin skip\n";
}

$pdo->exec(
    'UPDATE `ar_credits` ar
     INNER JOIN `users` u ON u.id = ar.user_id
     SET ar.debtor_user_id = ar.user_id,
         ar.debtor_name = u.name,
         ar.source = "ims_take"
     WHERE ar.debtor_name = "" OR ar.debtor_name IS NULL'
);
echo "ar_credits backfill done\n";

echo "done\n";
