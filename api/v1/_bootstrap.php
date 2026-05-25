<?php

declare(strict_types=1);

/**
 * Shared bootstrap for /rts/api/v1/*.php JSON endpoints.
 *
 * Responsibilities:
 *  - Send JSON headers and same-origin CORS hints
 *  - Start IMS session via the existing helper
 *  - Dispatch the requested action
 *  - Provide CSRF helpers reused by SPA + legacy PHP pages
 *  - Provide consistent {ok, data, error} JSON output
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    ims_session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_ok($data = null): void
{
    api_json(['ok' => true, 'data' => $data, 'error' => null]);
}

function api_fail(string $error, int $status = 400, $data = null): void
{
    api_json(['ok' => false, 'data' => $data, 'error' => $error], $status);
}

function api_method_allowed(array $methods): void
{
    $m = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($m, $methods, true)) {
        api_fail('Method not allowed.', 405);
    }
}

function api_action(): string
{
    $a = trim((string) ($_REQUEST['action'] ?? ''));
    return $a === '' ? 'index' : $a;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '' && (str_starts_with(trim($raw), '{') || str_starts_with(trim($raw), '['))) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}

function api_csrf_token(): string
{
    if (!isset($_SESSION['ims_csrf']) || !is_string($_SESSION['ims_csrf']) || $_SESSION['ims_csrf'] === '') {
        $_SESSION['ims_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['ims_csrf'];
}

function api_require_csrf(array $input): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET') {
        return;
    }
    $token = (string) ($input['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = api_csrf_token();
    if (!hash_equals($expected, $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_require_ims_user(): array
{
    $u = ims_current_user();
    if ($u === null) {
        api_fail('Not authenticated.', 401);
    }
    return $u;
}

function api_require_role(array $allowed): array
{
    $u = api_require_ims_user();
    if (!in_array((string) $u['role'], $allowed, true)) {
        api_fail('Forbidden.', 403);
    }
    return $u;
}

/**
 * Normalize an IMS PHP role to the SPA Sidebar's `Role` type.
 *  - `tl`           -> `team_leader` (or `csr_tl` when dept is CSR)
 *  - `staff`        -> `staff`       (or `csr_staff` when dept is CSR)
 *  - `prod_manager` -> `prod_manager`
 *  - `executive`    -> `executive`
 */
function api_normalize_role(string $phpRole, ?string $deptName): string
{
    $isCsr = $deptName === 'csr';
    return match ($phpRole) {
        'executive' => 'executive',
        'prod_manager' => 'prod_manager',
        'hr' => 'hr',
        'tl' => $isCsr ? 'csr_tl' : 'team_leader',
        'staff' => $isCsr ? 'csr_staff' : 'staff',
        default => $phpRole,
    };
}

/**
 * Resolve a department row by id and return its display name + canonical key.
 */
function api_dept_lookup(?int $deptId): array
{
    if ($deptId === null || $deptId < 1) {
        return ['id' => null, 'key' => 'all', 'name' => 'All departments'];
    }
    try {
        $pdo = ims_pdo();
        $st = $pdo->prepare('SELECT id, name FROM departments WHERE id = ? LIMIT 1');
        $st->execute([$deptId]);
        $row = $st->fetch();
        if (!$row) {
            return ['id' => null, 'key' => 'all', 'name' => 'All departments'];
        }
        return [
            'id' => (int) $row['id'],
            'key' => (string) $row['name'],
            'name' => api_dept_label((string) $row['name']),
        ];
    } catch (Throwable) {
        return ['id' => null, 'key' => 'all', 'name' => 'All departments'];
    }
}

function api_dept_label(string $key): string
{
    return match ($key) {
        'honey' => 'Honey',
        'paragis' => 'Paragis',
        'cacao_brc' => 'Cacao / Black Rice Coffee',
        'csr' => 'Customer Service',
        default => ucfirst(str_replace('_', ' ', $key)),
    };
}

/**
 * Dispatcher helper. Pass a map of action => callable($input, $user|null).
 */
function api_dispatch(array $actions): void
{
    $action = api_action();
    if (!isset($actions[$action])) {
        api_fail('Unknown action: ' . $action, 404);
    }
    try {
        $actions[$action]();
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            api_fail('Duplicate value (unique constraint).', 409);
        }
        api_fail('Database error.', 500);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    } catch (Throwable $e) {
        api_fail('Server error.', 500);
    }
}
