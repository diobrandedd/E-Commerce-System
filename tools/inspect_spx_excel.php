<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Usage: php inspect_spx_excel.php <xlsx>\n");
    exit(1);
}

$wb = IOFactory::load($path);
$s = $wb->getSheet(0);
echo 'Sheet: ', $s->getTitle(), PHP_EOL;
$maxRow = min(20, (int) $s->getHighestRow());
$maxCol = min(15, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString((string) $s->getHighestColumn()));
for ($r = 1; $r <= $maxRow; $r++) {
    $parts = [];
    for ($c = 1; $c <= $maxCol; $c++) {
        $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
        $v = $s->getCell($coord)->getValue();
        if ($v !== null && trim((string) $v) !== '') {
            $parts[] = chr(64 + $c) . ':' . mb_substr(trim((string) $v), 0, 120);
        }
    }
    if ($parts !== []) {
        echo 'R', $r, ' ', implode(' | ', $parts), PHP_EOL;
    }
}
