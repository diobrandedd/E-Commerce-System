<?php

declare(strict_types=1);

/**
 * Silent POS thermal print (Windows).
 *
 * Prefer GDI text via the installed printer driver (same path as Chrome print).
 * Vendor POS-58 drivers often accept RAW ESC/POS into the queue but never move paper.
 */

/**
 * @return array{printer: string, port: string}
 */
function pos_thermal_print_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $printer = 'POS-58';
    $port = '';
    $path = dirname(__DIR__) . '/config/config.php';
    if (is_file($path)) {
        /** @var mixed $all */
        $all = require $path;
        if (is_array($all)) {
            if (!empty($all['pos_thermal_printer']) && is_string($all['pos_thermal_printer'])) {
                $printer = trim($all['pos_thermal_printer']);
            }
            if (!empty($all['pos_thermal_port']) && is_string($all['pos_thermal_port'])) {
                $port = trim($all['pos_thermal_port']);
            }
        }
    }
    $envPrinter = getenv('POS_THERMAL_PRINTER');
    if (is_string($envPrinter) && trim($envPrinter) !== '') {
        $printer = trim($envPrinter);
    }
    $envPort = getenv('POS_THERMAL_PORT');
    if (is_string($envPort) && trim($envPort) !== '') {
        $port = trim($envPort);
    }
    $cfg = ['printer' => $printer !== '' ? $printer : 'POS-58', 'port' => $port];

    return $cfg;
}

/**
 * Print plain UTF-8 receipt text via Windows GDI (silent, uses printer driver).
 *
 * @throws RuntimeException
 */
function pos_thermal_print_text(string $text): void
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = trim($text, "\n");
    if ($text === '') {
        throw new RuntimeException('Empty print job.');
    }
    if (strlen($text) > 200_000) {
        throw new RuntimeException('Print job too large.');
    }
    if (PHP_OS_FAMILY !== 'Windows') {
        throw new RuntimeException('Silent thermal print requires Windows.');
    }

    $cfg = pos_thermal_print_config();
    $name = $cfg['printer'];

    $tmp = tempnam(sys_get_temp_dir(), 'pyxpos');
    if ($tmp === false) {
        throw new RuntimeException('Could not create temp print file.');
    }
    $txt = $tmp . '.txt';
    @unlink($tmp);
    if (file_put_contents($txt, $text . "\n") === false) {
        throw new RuntimeException('Could not write temp print file.');
    }

    $ps1 = dirname(__DIR__) . '/tools/pos_gdi_print.ps1';
    if (!is_file($ps1)) {
        @unlink($txt);
        throw new RuntimeException('Missing tools/pos_gdi_print.ps1');
    }

    $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($ps1)
        . ' -PrinterName ' . escapeshellarg($name)
        . ' -FilePath ' . escapeshellarg($txt);

    $out = [];
    $code = 1;
    exec($cmd . ' 2>&1', $out, $code);
    @unlink($txt);

    if ($code !== 0) {
        throw new RuntimeException(
            'Silent print failed for "' . $name . '". '
            . (trim(implode(' ', $out)) ?: 'GDI print failed.')
        );
    }
}

/**
 * Send raw ESC/POS bytes (COM / share / RAW winspool). Prefer pos_thermal_print_text for POS-58.
 *
 * @throws RuntimeException
 */
function pos_thermal_print_raw(string $bytes): void
{
    if ($bytes === '') {
        throw new RuntimeException('Empty print job.');
    }
    if (strlen($bytes) > 512_000) {
        throw new RuntimeException('Print job too large.');
    }

    $cfg = pos_thermal_print_config();
    $errors = [];

    if ($cfg['port'] !== '') {
        $paths = [$cfg['port']];
        if (!str_starts_with($cfg['port'], '\\\\')) {
            $paths[] = '\\\\.\\' . $cfg['port'];
        }
        foreach ($paths as $path) {
            $fp = @fopen($path, 'wb');
            if ($fp === false) {
                $errors[] = "Could not open port {$path}";
                continue;
            }
            $n = @fwrite($fp, $bytes);
            @fclose($fp);
            if ($n !== false && $n > 0) {
                return;
            }
            $errors[] = "Write failed on {$path}";
        }
    }

    $name = $cfg['printer'];
    foreach (['localhost', '127.0.0.1'] as $host) {
        $share = '\\\\' . $host . '\\' . $name;
        $fp = @fopen($share, 'wb');
        if ($fp === false) {
            $errors[] = "Could not open {$share}";
            continue;
        }
        $n = @fwrite($fp, $bytes);
        @fclose($fp);
        if ($n !== false && $n > 0) {
            return;
        }
        $errors[] = "Write failed on {$share}";
    }

    if (PHP_OS_FAMILY === 'Windows') {
        try {
            pos_thermal_print_raw_winspool($name, $bytes);

            return;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    throw new RuntimeException(
        'Silent RAW print failed for "' . $name . '". '
        . implode(' | ', array_slice($errors, 0, 3))
    );
}

/**
 * @throws RuntimeException
 */
function pos_thermal_print_raw_winspool(string $printerName, string $bytes): void
{
    $tmp = tempnam(sys_get_temp_dir(), 'pyxpos');
    if ($tmp === false) {
        throw new RuntimeException('Could not create temp print file.');
    }
    $bin = $tmp . '.bin';
    @unlink($tmp);
    if (file_put_contents($bin, $bytes) === false) {
        throw new RuntimeException('Could not write temp print file.');
    }

    $ps1 = dirname(__DIR__) . '/tools/pos_raw_print.ps1';
    if (!is_file($ps1)) {
        @unlink($bin);
        throw new RuntimeException('Missing tools/pos_raw_print.ps1');
    }

    $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($ps1)
        . ' -PrinterName ' . escapeshellarg($printerName)
        . ' -FilePath ' . escapeshellarg($bin);

    $out = [];
    $code = 1;
    exec($cmd . ' 2>&1', $out, $code);
    @unlink($bin);

    if ($code !== 0) {
        throw new RuntimeException(trim(implode(' ', $out)) ?: 'Winspool raw print failed.');
    }
}
