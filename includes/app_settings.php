<?php

declare(strict_types=1);

/**
 * Key-value application settings (DB-backed).
 */

function app_settings_table_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'app_settings'");
        $ready = $st !== false && $st->fetchColumn() !== false;
    } catch (Throwable $e) {
        $ready = false;
    }

    return $ready;
}

function app_setting_get(PDO $pdo, string $key, ?string $default = null): ?string
{
    $key = trim($key);
    if ($key === '' || !app_settings_table_ready($pdo)) {
        return $default;
    }
    $st = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
    $st->execute([$key]);
    $val = $st->fetchColumn();
    if ($val === false || $val === null) {
        return $default;
    }

    return (string) $val;
}

function app_setting_set(PDO $pdo, string $key, string $value, ?int $userId = null): void
{
    $key = trim($key);
    if ($key === '') {
        throw new RuntimeException('Setting key is required.');
    }
    if (!app_settings_table_ready($pdo)) {
        throw new RuntimeException('app_settings table is missing. Run migration phase 75.');
    }
    $upd = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value, updated_by)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)'
    );
    $upd->execute([$key, $value, $userId]);
}
