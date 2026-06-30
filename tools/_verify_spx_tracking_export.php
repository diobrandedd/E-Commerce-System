<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/order_remarks.php';

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Usage: php _verify_spx_tracking_export.php <xlsx>\n");
    exit(1);
}

$result = order_remarks_parse_tracking_map($path, 'spx');
echo 'profile: ', $result['profileId'], PHP_EOL;
echo 'refs: ', count($result['refs']), PHP_EOL;
echo json_encode(array_slice($result['refs'], 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;
