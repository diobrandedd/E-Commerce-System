<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$path = $argv[1] ?? '';
try {
    $ss = PhpOffice\PhpSpreadsheet\IOFactory::load($path);
    echo "OK\n";
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    if ($e->getPrevious()) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . "\n";
    }
}
