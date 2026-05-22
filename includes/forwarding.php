<?php

declare(strict_types=1);

/**
 * Central ngrok / public HTTPS forwarding configuration.
 *
 * Edit config/forwarding.php when switching ngrok accounts.
 */

/** @return array<string, mixed> */
function rts_forwarding_config(): array
{
    static $cfg;
    if ($cfg !== null) {
        return $cfg;
    }

    $path = dirname(__DIR__) . '/config/forwarding.php';
    if (!is_file($path)) {
        $path = dirname(__DIR__) . '/config/forwarding.sample.php';
    }
    $cfg = is_file($path) ? require $path : [];
    if (!is_array($cfg)) {
        $cfg = [];
    }

    return $cfg;
}

function rts_forwarding_enabled(): bool
{
    return (bool) (rts_forwarding_config()['enabled'] ?? false);
}

/** Parsed public origin, e.g. https://xxx.ngrok-free.dev (no trailing slash). */
function rts_forwarding_public_url(): string
{
    $raw = trim((string) (rts_forwarding_config()['public_url'] ?? ''));
    if ($raw === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $raw)) {
        $raw = 'https://' . $raw;
    }
    $parts = parse_url($raw);
    if ($parts === false || empty($parts['host'])) {
        return '';
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
    $host = (string) $parts['host'];
    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

    return $scheme . '://' . $host . $port;
}

function rts_forwarding_host(): string
{
    $url = rts_forwarding_public_url();
    if ($url === '') {
        return '';
    }
    $parts = parse_url($url);

    return (string) ($parts['host'] ?? '');
}

function rts_forwarding_ngrok_skip_warning(): bool
{
    if (!rts_forwarding_enabled()) {
        return false;
    }

    return (bool) (rts_forwarding_config()['ngrok_skip_browser_warning'] ?? true);
}

/** @return array<string, string> Headers for fetch() when using ngrok free tier. */
function rts_forwarding_fetch_headers(): array
{
    if (!rts_forwarding_ngrok_skip_warning()) {
        return [];
    }

    return ['ngrok-skip-browser-warning' => '1'];
}

function rts_forwarding_fetch_headers_json(): string
{
    return json_encode(rts_forwarding_fetch_headers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
}

function rts_forwarding_current_host(): string
{
    return strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
}

function rts_forwarding_current_is_public_host(): bool
{
    $want = strtolower(rts_forwarding_host());
    $have = rts_forwarding_current_host();

    return $want !== '' && $have !== '' && $want === $have;
}

function rts_forwarding_is_loopback_host(string $host): bool
{
    $host = strtolower(trim($host));
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return true;
    }

    return str_starts_with($host, '127.');
}

function rts_forwarding_should_redirect_request(): bool
{
    if (PHP_SAPI === 'cli') {
        return false;
    }
    if (!rts_forwarding_enabled()) {
        return false;
    }
    if (rts_forwarding_public_url() === '') {
        return false;
    }
    if (rts_forwarding_current_is_public_host()) {
        return false;
    }

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    // JSON API must not 302 to ngrok (breaks fetch / CORS when SPA is on LAN IP).
    if (preg_match('#/(api/|api/v1/)#i', $uri)) {
        return false;
    }

    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'application/json')) {
        return false;
    }

    $host = rts_forwarding_current_host();
    if ($host === '') {
        return false;
    }

    if (
        (bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true)
        && rts_forwarding_is_loopback_host($host)
    ) {
        return false;
    }

    return true;
}

/**
 * Server-side redirect to the configured public URL (keeps path + query).
 * Call early on pages that must run over HTTPS (camera / QR).
 */
function rts_forwarding_maybe_redirect(): void
{
    if (!rts_forwarding_should_redirect_request()) {
        return;
    }

    $base = rts_forwarding_public_url();
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }

    header('Location: ' . $base . $uri, true, 302);
    exit;
}

/**
 * Inline script fallback (same behavior as legacy NGROK_HOST blocks).
 * Prefer rts_forwarding_maybe_redirect() in PHP; this covers edge cases before PHP runs.
 */
function rts_forwarding_head_script(): string
{
    if (!rts_forwarding_enabled()) {
        return '';
    }
    $host = rts_forwarding_host();
    if ($host === '') {
        return '';
    }
    $allowLocal = (bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true);
    $hostJs = json_encode($host, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $allowLocalJs = $allowLocal ? 'true' : 'false';

    return '<script>(function(){'
        . 'var NGROK_HOST=' . $hostJs . ';'
        . 'var ALLOW_LOCALHOST=' . $allowLocalJs . ';'
        . 'var h=location.hostname.toLowerCase();'
        . 'if(ALLOW_LOCALHOST&&(h==="localhost"||h==="127.0.0.1"||h.indexOf("127.")===0))return;'
        . 'if(location.protocol==="https:"&&h===NGROK_HOST)return;'
        . 'location.replace("https://"+NGROK_HOST+location.pathname+location.search+location.hash);'
        . '})();</script>';
}

/**
 * Absolute URL under /rts/ (uses forwarding public URL when enabled).
 */
function rts_app_public_url(string $path = ''): string
{
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $appRoot = '/rts';
    if ($path === '/' || $path === '') {
        $fullPath = $appRoot . '/';
    } elseif (str_starts_with($path, '/rts/')) {
        $fullPath = $path;
    } elseif (str_starts_with($path, '/rts')) {
        $fullPath = $path;
    } else {
        $fullPath = $appRoot . $path;
    }

    if (rts_forwarding_enabled() && rts_forwarding_public_url() !== '') {
        return rts_forwarding_public_url() . $fullPath;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return $scheme . '://' . $host . $fullPath;
}

/** Payload for API / SPA consumers. */
function rts_forwarding_api_payload(): array
{
    return [
        'enabled' => rts_forwarding_enabled(),
        'public_url' => rts_forwarding_public_url(),
        'host' => rts_forwarding_host(),
        'label' => (string) (rts_forwarding_config()['label'] ?? ''),
        'allow_localhost_without_redirect' => (bool) (rts_forwarding_config()['allow_localhost_without_redirect'] ?? true),
        'fetch_headers' => rts_forwarding_fetch_headers(),
    ];
}
