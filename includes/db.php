<?php

declare(strict_types=1);

function rts_config(): array
{
    static $cfg;
    if ($cfg === null) {
        $path = dirname(__DIR__) . '/config/config.php';
        $cfg = file_exists($path) ? require $path : [];
    }
    return $cfg;
}

function rts_pdo(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = rts_config()['db'] ?? [];
    $host = $c['host'] ?? '127.0.0.1';
    $port = (int) ($c['port'] ?? 3306);
    $requestPort = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $prodDbName = 'rts_system';
    $devDbName = 'rts_development';
    $name = $requestPort === '8080' ? $devDbName : $prodDbName;
    $user = $c['user'] ?? 'root';
    $pass = $c['pass'] ?? '';
    $charset = $c['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

/**
 * Inventory modules share the same database connection as RTS.
 * Keep this alias so new modules can depend on a neutral name.
 */
function ims_pdo(): PDO
{
    return rts_pdo();
}

require_once __DIR__ . '/forwarding.php';
rts_forwarding_maybe_redirect();
