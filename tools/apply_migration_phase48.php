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

$runPipeline = column_type($pdo, 'csr_online_batch_runs', 'pipeline');
if (strpos($runPipeline, 'tiktok') === false) {
    $pdo->exec("ALTER TABLE `csr_online_batch_runs` MODIFY COLUMN `pipeline` ENUM('partner','vip','shopee_spx','tiktok') NOT NULL");
    echo "csr_online_batch_runs.pipeline + tiktok\n";
} else {
    echo "csr_online_batch_runs.pipeline already has tiktok\n";
}

foreach (['csr_online_order_lines', 'online_order_parcels'] as $table) {
    $pipe = column_type($pdo, $table, 'source_pipeline');
    if (strpos($pipe, 'tiktok') === false) {
        $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `source_pipeline` ENUM('partner','vip','shopee_spx','tiktok') NOT NULL");
        echo "{$table}.source_pipeline + tiktok\n";
    }

    $courier = column_type($pdo, $table, 'courier');
    if (!preg_match('/varchar\((19[0-9]|2[0-9]{2})\)/i', $courier)) {
        $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `courier` VARCHAR(191) NOT NULL DEFAULT ''");
        echo "{$table}.courier widened\n";
    }

    if (column_type($pdo, $table, 'partner_seller') === '') {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `partner_seller` ENUM('ian','jono','joven') NULL DEFAULT NULL AFTER `source_pipeline`");
        echo "{$table}.partner_seller added\n";
    }
}

$indexes = [
    'csr_online_order_lines' => [
        'idx_csr_lines_partner_seller' => 'ADD KEY `idx_csr_lines_partner_seller` (`partner_seller`)',
        'idx_csr_lines_pipeline_created' => 'ADD KEY `idx_csr_lines_pipeline_created` (`source_pipeline`, `created_at`)',
        'idx_csr_lines_courier' => 'ADD KEY `idx_csr_lines_courier` (`courier`(64))',
    ],
    'online_order_parcels' => [
        'idx_parcels_partner_seller' => 'ADD KEY `idx_parcels_partner_seller` (`partner_seller`)',
        'idx_parcels_pipeline_created' => 'ADD KEY `idx_parcels_pipeline_created` (`source_pipeline`, `created_at`)',
        'idx_parcels_courier' => 'ADD KEY `idx_parcels_courier` (`courier`(64))',
    ],
];

foreach ($indexes as $table => $defs) {
    foreach ($defs as $name => $sql) {
        if (!index_exists($pdo, $table, $name)) {
            $pdo->exec("ALTER TABLE `{$table}` {$sql}");
            echo "{$table} index {$name}\n";
        }
    }
}

echo "done\n";
