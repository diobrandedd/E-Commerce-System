<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rts_schema.php';

/**
 * Mobile POS API tokens (Bearer). Website continues to use PHP session + CSRF.
 */
function pos_api_tokens_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `pos_api_tokens` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `token_hash` CHAR(64) NOT NULL,
            `pin_id` INT UNSIGNED NULL,
            `user_id` INT UNSIGNED NULL,
            `label` VARCHAR(160) NOT NULL DEFAULT "",
            `expires_at` DATETIME NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `revoked_at` DATETIME NULL,
            `last_used_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_pos_api_tokens_hash` (`token_hash`),
            KEY `idx_pos_api_tokens_pin` (`pin_id`),
            KEY `idx_pos_api_tokens_user` (`user_id`),
            KEY `idx_pos_api_tokens_expires` (`expires_at`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $done = true;
}

function pos_api_token_hash(string $plain): string
{
    return hash('sha256', $plain);
}

/**
 * @param array{pin_id?: ?int, user_id?: ?int, label?: string} $actor
 * @return array{token: string, expires_at: string}
 */
function pos_api_token_issue(PDO $pdo, array $actor, int $ttlDays = 30): array
{
    pos_api_tokens_ensure_schema($pdo);
    $plain = bin2hex(random_bytes(32));
    $hash = pos_api_token_hash($plain);
    $pinId = isset($actor['pin_id']) ? (int) $actor['pin_id'] : 0;
    $userId = isset($actor['user_id']) ? (int) $actor['user_id'] : 0;
    if ($pinId < 1 && $userId < 1) {
        throw new RuntimeException('Cannot issue token without pin or user.');
    }
    $label = trim((string) ($actor['label'] ?? ''));
    $expires = (new DateTimeImmutable('now'))->modify('+' . max(1, $ttlDays) . ' days');
    $pdo->prepare(
        'INSERT INTO pos_api_tokens (token_hash, pin_id, user_id, label, expires_at)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([
        $hash,
        $pinId > 0 ? $pinId : null,
        $userId > 0 ? $userId : null,
        $label,
        $expires->format('Y-m-d H:i:s'),
    ]);

    return [
        'token' => $plain,
        'expires_at' => $expires->format(DateTimeInterface::ATOM),
    ];
}

function pos_api_token_revoke_plain(PDO $pdo, string $plain): void
{
    if ($plain === '') {
        return;
    }
    pos_api_tokens_ensure_schema($pdo);
    $pdo->prepare(
        'UPDATE pos_api_tokens SET revoked_at = NOW()
         WHERE token_hash = ? AND revoked_at IS NULL'
    )->execute([pos_api_token_hash($plain)]);
}

/**
 * @return array{id:int,pin_id:?int,user_id:?int,label:string}|null
 */
function pos_api_token_find(PDO $pdo, string $plain): ?array
{
    if ($plain === '') {
        return null;
    }
    pos_api_tokens_ensure_schema($pdo);
    $st = $pdo->prepare(
        'SELECT id, pin_id, user_id, label, expires_at, revoked_at
         FROM pos_api_tokens
         WHERE token_hash = ?
         LIMIT 1'
    );
    $st->execute([pos_api_token_hash($plain)]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    if ($row['revoked_at'] !== null && (string) $row['revoked_at'] !== '') {
        return null;
    }
    if (strtotime((string) $row['expires_at']) < time()) {
        return null;
    }
    $pdo->prepare('UPDATE pos_api_tokens SET last_used_at = NOW() WHERE id = ?')
        ->execute([(int) $row['id']]);

    return [
        'id' => (int) $row['id'],
        'pin_id' => $row['pin_id'] !== null ? (int) $row['pin_id'] : null,
        'user_id' => $row['user_id'] !== null ? (int) $row['user_id'] : null,
        'label' => (string) ($row['label'] ?? ''),
    ];
}

function pos_api_bearer_from_request(): string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $k => $v) {
            if (strtolower((string) $k) === 'authorization') {
                $header = (string) $v;
                break;
            }
        }
    }
    if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
        return $m[1];
    }
    // Fallback when Apache strips Authorization.
    $alt = trim((string) ($_SERVER['HTTP_X_POS_TOKEN'] ?? ''));
    if ($alt !== '') {
        return $alt;
    }

    return '';
}

/**
 * Apply a valid Bearer token into the PHP session so existing POS helpers work.
 * Sets $GLOBALS['api_bearer_auth'] = true so CSRF can be skipped.
 */
function pos_api_apply_bearer_auth(): bool
{
    $plain = pos_api_bearer_from_request();
    if ($plain === '') {
        return false;
    }
    try {
        $pdo = ims_pdo();
        $row = pos_api_token_find($pdo, $plain);
    } catch (Throwable) {
        return false;
    }
    if ($row === null) {
        return false;
    }

    ims_session_start();
    if ($row['pin_id'] !== null && $row['pin_id'] > 0) {
        unset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['user_name'],
            $_SESSION['user_email'],
            $_SESSION['employee_id'],
            $_SESSION['role'],
            $_SESSION['dept_id']
        );
        $_SESSION['pos_authenticated'] = true;
        $_SESSION['pos_pin_id'] = $row['pin_id'];
        $_SESSION['pos_label'] = $row['label'] !== '' ? $row['label'] : 'POS';
        $GLOBALS['api_bearer_auth'] = true;
        $GLOBALS['api_bearer_token'] = $plain;

        return true;
    }

    if ($row['user_id'] !== null && $row['user_id'] > 0) {
        $st = $pdo->prepare(
            'SELECT u.id, u.username, u.name, u.email, u.employee_id, u.dept_id, u.is_active, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1'
        );
        $st->execute([$row['user_id']]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if ($user === false || (int) ($user['is_active'] ?? 0) !== 1) {
            return false;
        }
        unset($_SESSION['pos_authenticated'], $_SESSION['pos_pin_id'], $_SESSION['pos_label']);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_email'] = (string) $user['email'];
        $_SESSION['employee_id'] = (string) $user['employee_id'];
        $_SESSION['role'] = (string) $user['role_name'];
        $_SESSION['dept_id'] = isset($user['dept_id']) ? (int) $user['dept_id'] : null;
        $GLOBALS['api_bearer_auth'] = true;
        $GLOBALS['api_bearer_token'] = $plain;

        return true;
    }

    return false;
}

function pos_sales_ensure_refund_columns(PDO $pdo): void
{
    if (!rts_db_has_column($pdo, 'pos_sales', 'refunded_at')) {
        $pdo->exec(
            'ALTER TABLE `pos_sales`
             ADD COLUMN `refunded_at` DATETIME NULL AFTER `sale_date`,
             ADD COLUMN `refund_reason` VARCHAR(255) NULL AFTER `refunded_at`,
             ADD KEY `idx_pos_sales_refunded` (`refunded_at`)'
        );
        rts_db_clear_column_cache();
    }
}
