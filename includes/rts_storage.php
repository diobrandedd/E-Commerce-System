<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

const RTS_STORAGE_BUCKET_RTS = 'rts_documentation';
const RTS_STORAGE_BUCKET_DELIVERIES = 'deliveries_documentation';
const RTS_STORAGE_BUCKET_SP_SORT = 'sp_sort';

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
    $allowed = [RTS_STORAGE_BUCKET_RTS, RTS_STORAGE_BUCKET_DELIVERIES, RTS_STORAGE_BUCKET_SP_SORT];
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
    if (rts_storage_external_enabled()) {
        $web = rts_storage_web_base();
        if ($web !== '' && !str_contains($web, '://')) {
            return $web . '/' . $bucket . '/';
        }
        if ($web !== '') {
            return rtrim($web, '/') . '/' . $bucket . '/';
        }

        return 'api/storage_media.php?b=' . rawurlencode($bucket) . '&f=';
    }

    $rel = $relativeToApp !== '' ? rtrim($relativeToApp, '/') . '/' : '';

    return $rel . 'assets/' . $bucket . '/';
}

function rts_storage_public_url(string $bucket, string $filename, string $relativeToApp = ''): string
{
    $safe = basename($filename);
    if ($safe === '' || $safe !== $filename) {
        return '';
    }

    $prefix = rts_storage_public_prefix($bucket, $relativeToApp);
    if (str_contains($prefix, '&f=')) {
        return $prefix . rawurlencode($safe);
    }

    return $prefix . rawurlencode($safe);
}

function rts_storage_resolve_file(string $bucket, string $filename): ?string
{
    $safe = basename($filename);
    if ($safe === '' || $safe !== $filename) {
        return null;
    }
    $path = rts_storage_dir($bucket) . '/' . $safe;

    return is_file($path) ? $path : null;
}
