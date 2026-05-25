<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const RTS_STORAGE_BUCKET_RTS = 'rts_documentation';
const RTS_STORAGE_BUCKET_DELIVERIES = 'deliveries_documentation';
const RTS_STORAGE_BUCKET_SP_SORT = 'sp_sort';
const RTS_STORAGE_BUCKET_SUPPLIER_IDS = 'supplier_valid_ids';

/** @return array<string, mixed> */
function rts_storage_cfg(): array
{
    static $cfg;
    if ($cfg === null) {
        $cfg = rts_config()['storage'] ?? [];
    }

    return $cfg;
}

function rts_storage_external_enabled(): bool
{
    return trim((string) (rts_storage_cfg()['root'] ?? '')) !== '';
}

/** Absolute filesystem path for all uploads (external root or project assets/). */
function rts_storage_root_path(): string
{
    $root = trim((string) (rts_storage_cfg()['root'] ?? ''));
    if ($root !== '') {
        return rtrim(str_replace('\\', '/', $root), '/');
    }

    return rtrim(str_replace('\\', '/', dirname(__DIR__) . '/assets'), '/');
}

/**
 * Directory for a storage bucket (creates if missing).
 *
 * @param RTS_STORAGE_BUCKET_* $bucket
 */
function rts_storage_dir(string $bucket): string
{
    $allowed = [
        RTS_STORAGE_BUCKET_RTS,
        RTS_STORAGE_BUCKET_DELIVERIES,
        RTS_STORAGE_BUCKET_SP_SORT,
        RTS_STORAGE_BUCKET_SUPPLIER_IDS,
    ];
    if (!in_array($bucket, $allowed, true)) {
        throw new InvalidArgumentException('Unknown storage bucket: ' . $bucket);
    }

    if (rts_storage_external_enabled()) {
        $dir = rts_storage_root_path() . '/' . $bucket;
    } else {
        $dir = dirname(__DIR__) . '/assets/' . $bucket;
    }

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create storage directory: ' . $dir);
    }

    return $dir;
}

/**
 * Month segment for delivery photos from delivery_date (YYYY-MM-DD → YYYY-MM).
 */
function rts_storage_deliveries_month_key(string $deliveryDate): string
{
    if (!preg_match('/^(\d{4}-\d{2})-\d{2}$/', $deliveryDate, $m)) {
        throw new InvalidArgumentException('Invalid delivery_date for month folder');
    }

    return $m[1];
}

/**
 * Absolute directory for a delivery month (creates deliveries_documentation/YYYY-MM/).
 */
function rts_storage_dir_deliveries_month(string $deliveryDate): string
{
    $month = rts_storage_deliveries_month_key($deliveryDate);
    $dir = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $month;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create storage directory: ' . $dir);
    }

    return $dir;
}

/**
 * Validate stored filename/path for a bucket (legacy flat or deliveries YYYY-MM/file).
 *
 * @param RTS_STORAGE_BUCKET_* $bucket
 */
function rts_storage_normalize_stored_filename(string $bucket, string $stored): string
{
    $stored = str_replace('\\', '/', trim($stored));
    if ($stored === '' || str_contains($stored, '..')) {
        return '';
    }

    if ($bucket === RTS_STORAGE_BUCKET_DELIVERIES) {
        if (preg_match('#^\d{4}-\d{2}/[a-zA-Z0-9][a-zA-Z0-9._-]*$#', $stored)) {
            return $stored;
        }
        if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9._-]*$#', $stored)) {
            return $stored;
        }

        return '';
    }

    $safe = basename($stored);

    return ($safe !== '' && $safe === $stored) ? $safe : '';
}

/** URL path from site root, e.g. /rts_storage — empty when using in-app assets/. */
function rts_storage_web_base(): string
{
    if (!rts_storage_external_enabled()) {
        return '';
    }

    $base = trim((string) (rts_storage_cfg()['web_base'] ?? ''));
    if ($base === '') {
        $name = basename(rts_storage_root_path());
        return '/' . $name;
    }

    if (!str_starts_with($base, '/') && !preg_match('#^https?://#i', $base)) {
        $base = '/' . $base;
    }

    return rtrim(str_replace('\\', '/', $base), '/');
}

/**
 * Public URL prefix for a bucket (ends with / or with &= for API fallback).
 *
 * @param string $relativeToApp e.g. '' from app root, '../' from deliveries/
 */
function rts_storage_public_prefix(string $bucket, string $relativeToApp = ''): string
{
    $rel = $relativeToApp !== '' ? rtrim($relativeToApp, '/') . '/' : '';

    // Delivery photos use monthly subfolders; serve via PHP (works with ngrok, no /rts_storage alias).
    if ($bucket === RTS_STORAGE_BUCKET_DELIVERIES) {
        return $rel . 'api/storage_media.php?b=' . rawurlencode($bucket) . '&f=';
    }

    if (rts_storage_external_enabled()) {
        $web = rts_storage_web_base();
        if ($web !== '' && !str_contains($web, '://')) {
            return $web . '/' . $bucket . '/';
        }
        if ($web !== '') {
            return rtrim($web, '/') . '/' . $bucket . '/';
        }

        return $rel . 'api/storage_media.php?b=' . rawurlencode($bucket) . '&f=';
    }

    return $rel . 'assets/' . $bucket . '/';
}

function rts_storage_public_url(string $bucket, string $filename, string $relativeToApp = ''): string
{
    $safe = rts_storage_normalize_stored_filename($bucket, $filename);
    if ($safe === '') {
        return '';
    }

    $encoded = implode('/', array_map('rawurlencode', explode('/', $safe)));
    $prefix = rts_storage_public_prefix($bucket, $relativeToApp);
    if (str_contains($prefix, '&f=')) {
        return $prefix . $encoded;
    }

    return $prefix . $encoded;
}

function rts_storage_resolve_file(string $bucket, string $filename): ?string
{
    $safe = rts_storage_normalize_stored_filename($bucket, $filename);
    if ($safe === '') {
        return null;
    }
    $path = rts_storage_dir($bucket) . '/' . $safe;
    if (is_file($path)) {
        return $path;
    }

    // Legacy rows: flat filename on disk may live under deliveries_documentation/YYYY-MM/.
    if ($bucket === RTS_STORAGE_BUCKET_DELIVERIES && !str_contains($safe, '/')) {
        $matches = glob(rts_storage_dir($bucket) . '/*/' . $safe);
        if (is_array($matches)) {
            foreach ($matches as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }
    }

    return null;
}
