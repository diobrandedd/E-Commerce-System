<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/shopee_spx_packing_list.php';

$path = $argv[1] ?? 'C:/Users/Admin/Downloads/Packing List.Standard Local_1 (2).xlsx';
echo "File: $path\n";
echo 'exists: ' . (is_file($path) ? 'yes' : 'no') . "\n";
if (!is_file($path)) {
    exit(1);
}
echo 'size: ' . filesize($path) . "\n";

try {
    $rows = shopee_spx_packing_list_parse_paths([$path]);
    echo 'parsed rows: ' . count($rows) . "\n";
    echo json_encode(array_slice($rows, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . "\n";
    exit(1);
}
