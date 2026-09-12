<?php

declare(strict_types=1);

require_once __DIR__ . '/app_settings.php';

/** DB key for password_hash of the executive stock-override password. */
const ADMIN_OVERRIDE_PASSWORD_SETTING_KEY = 'security.admin_override_password_hash';

function admin_override_password_configured(PDO $pdo): bool
{
    $hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);

    return $hash !== null && $hash !== '';
}

/**
 * Verify plain password against the configured administrator override password.
 */
function admin_override_password_verify(PDO $pdo, string $plain): bool
{
    $hash = app_setting_get($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, null);
    if ($hash === null || $hash === '') {
        return false;
    }

    return password_verify($plain, $hash);
}

/**
 * Set or replace the administrator override password (min 6 chars).
 * When a password is already configured, $currentPlain must match it.
 */
function admin_override_password_set(PDO $pdo, string $newPlain, ?string $currentPlain, ?int $userId): void
{
    $newPlain = (string) $newPlain;
    if (strlen($newPlain) < 6) {
        throw new RuntimeException('Administrator password must be at least 6 characters.');
    }
    if (admin_override_password_configured($pdo)) {
        if ($currentPlain === null || $currentPlain === '' || !admin_override_password_verify($pdo, $currentPlain)) {
            throw new RuntimeException('Current administrator password is incorrect.');
        }
    }
    $hash = password_hash($newPlain, PASSWORD_DEFAULT);
    if ($hash === false) {
        throw new RuntimeException('Could not store administrator password.');
    }
    app_setting_set($pdo, ADMIN_OVERRIDE_PASSWORD_SETTING_KEY, $hash, $userId);
}

/**
 * Require admin_password from an API input array (monthly inventory / stock overrides).
 * Call only from api/v1 handlers (api_fail must exist).
 *
 * @param array<string, mixed> $input
 */
function api_require_admin_override_password(array $input): void
{
    $pdo = ims_pdo();
    if (!admin_override_password_configured($pdo)) {
        api_fail(
            'Administrator password is not set. An executive must set it under System Settings before overriding stock.',
            403
        );
    }
    $plain = trim((string) ($input['admin_password'] ?? $input['administrator_password'] ?? ''));
    if ($plain === '') {
        api_fail('Administrator password is required to override stock levels.', 403);
    }
    if (!admin_override_password_verify($pdo, $plain)) {
        api_fail('Invalid administrator password.', 403);
    }
}
