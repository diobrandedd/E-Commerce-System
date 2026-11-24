<?php

declare(strict_types=1);

/**
 * Local smoke test for SPA + api/v1 endpoints.
 * Run: php tools/spa_smoke_test.php
 * Or open: http://localhost/rts/tools/spa_smoke_test.php (localhost only)
 */

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        http_response_code(403);
        echo 'localhost only';
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';

function out(string $line): void
{
    echo $line . ($GLOBALS['isCli'] ? PHP_EOL : "\n");
}

function pass(string $label): void
{
    out('[PASS] ' . $label);
}

function fail(string $label, string $detail = ''): void
{
    out('[FAIL] ' . $label . ($detail !== '' ? ' — ' . $detail : ''));
}

function api_call(string $endpoint, string $action, ?array $post = null, ?string $cookieFile = null): array
{
    $url = 'http://127.0.0.1/rts/api/v1/' . ltrim($endpoint, '/') . '?action=' . rawurlencode($action);
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
        $headers[] = 'Content-Type: application/json';
        if (isset($post['_csrf_header'])) {
            $headers[] = 'X-CSRF-Token: ' . $post['_csrf_header'];
            unset($post['_csrf_header']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post, JSON_UNESCAPED_UNICODE));
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookieFile !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($body) ? json_decode($body, true) : null;
    return ['http' => $code, 'json' => is_array($json) ? $json : null, 'raw' => $body];
}

out('=== Pyx Tracker SPA smoke test ===');
out('');

// 1) Database
try {
    $pdo = ims_pdo();
    pass('Database connection (' . (rts_config()['db']['name'] ?? '?') . ')');
    foreach (['users', 'pos_pins', 'pos_sales', 'ar_credits', 'products', 'stock_entries'] as $table) {
        $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        if ($st && $st->fetch()) {
            pass("Table exists: {$table}");
        } else {
            fail("Table missing: {$table}");
        }
    }
} catch (Throwable $e) {
    fail('Database', $e->getMessage());
}

// 2) SPA build
$distAssets = glob(dirname(__DIR__) . '/app/dist/assets/*.{js,css}', GLOB_BRACE);
if ($distAssets && count($distAssets) > 0) {
    pass('SPA built (' . count($distAssets) . ' asset files in app/dist/assets/)');
} else {
    fail('SPA not built', 'Run: cd rts/app && npm install && npm run build:prod');
}

// 3) API (needs curl extension)
if (!function_exists('curl_init')) {
    fail('curl extension', 'Enable php_curl in XAMPP to test API over HTTP');
    exit($isCli ? 1 : 0);
}

$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pyx_spa_smoke_' . getmypid() . '.txt';
@unlink($cookieFile);

$me = api_call('auth.php', 'me', null, $cookieFile);
if (($me['http'] ?? 0) === 200 && ($me['json']['ok'] ?? false)) {
    pass('GET auth.php?action=me');
    $csrf = (string) ($me['json']['data']['csrf'] ?? '');
} else {
    fail('GET auth.php?action=me', (string) ($me['raw'] ?? ''));
    $csrf = '';
}

// Login — bootstrap default matches legacy app_users hash (admin123); override via PYX_TEST_* env
$testUser = getenv('PYX_TEST_USER') ?: 'admin';
$testPass = getenv('PYX_TEST_PASS') ?: 'admin123';

$login = api_call('auth.php', 'login', ['username' => $testUser, 'password' => $testPass], $cookieFile);
if (($login['http'] ?? 0) === 200 && ($login['json']['ok'] ?? false) && !empty($login['json']['data']['user'])) {
    $role = (string) ($login['json']['data']['user']['role'] ?? '');
    pass("Login as {$testUser} (role: {$role})");
    $csrf = (string) ($login['json']['data']['csrf'] ?? $csrf);
} else {
    fail("Login as {$testUser}", (string) ($login['json']['error'] ?? $login['raw'] ?? ''));
    out('');
    out('Tip: set PYX_TEST_USER and PYX_TEST_PASS env vars, or use your executive username + employee ID.');
    @unlink($cookieFile);
    exit($isCli ? 1 : 0);
}

$authedGets = [
    'dashboard.php?action=index' => 'dashboard',
    'ar.php?action=list' => 'AR credits list',
    'reports.php?action=stock_movement&period=monthly' => 'reports stock_movement',
    'reports.php?action=dept_summary' => 'reports dept_summary',
];
foreach ($authedGets as $path => $label) {
    $ch = curl_init('http://127.0.0.1/rts/api/v1/' . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = is_string($body) ? json_decode($body, true) : null;
    if ($code === 200 && ($json['ok'] ?? false)) {
        pass($label);
    } else {
        fail($label, (string) ($json['error'] ?? substr((string) $body, 0, 120)));
    }
}

// POS session (optional — set PYX_TEST_PIN)
$posPin = getenv('PYX_TEST_PIN') ?: '';
if ($posPin !== '') {
    @unlink($cookieFile);
    api_call('auth.php', 'me', null, $cookieFile);
    $posLogin = api_call('auth.php', 'pos_login', ['pin' => $posPin], $cookieFile);
    if (($posLogin['http'] ?? 0) === 200 && ($posLogin['json']['ok'] ?? false)) {
        pass('POS login');
        $products = api_call('pos.php', 'products', null, $cookieFile);
        if (($products['http'] ?? 0) === 200 && ($products['json']['ok'] ?? false)) {
            $count = count($products['json']['data']['products'] ?? []);
            pass("POS products list ({$count} sellable)");
        } else {
            fail('POS products', (string) ($products['json']['error'] ?? ''));
        }
    } else {
        fail('POS login', (string) ($posLogin['json']['error'] ?? ''));
    }
} else {
    out('[SKIP] POS login — set PYX_TEST_PIN env var to test');
}

@unlink($cookieFile);

out('');
out('=== Browser URLs ===');
out('SPA (after build): http://localhost/rts/app/dist/');
out('SPA dev:           npm run dev in rts/app → http://localhost:5173');
out('Legacy login:      http://localhost/rts/login.php');
out('This script:       http://localhost/rts/tools/spa_smoke_test.php');

exit(0);
