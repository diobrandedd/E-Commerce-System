<?php

declare(strict_types=1);

/**
 * Best-effort courier guess from tracking string / QR payload.
 * Staff can override in the UI.
 */
function rts_detect_courier(string $raw): string
{
    $t = strtoupper(trim($raw));
    if ($t === '') {
        return 'unknown';
    }
    // SPX (Shopee etc.)
    if (strpos($t, 'SPX') !== false) {
        return 'spx';
    }
    // Flash Express common patterns
    if (preg_match('/\bFL[A-Z0-9]{8,}\b/', $t) || strpos($t, 'FLASH') !== false) {
        return 'flash';
    }
    if (strlen($t) >= 10 && substr($t, 0, 2) === 'FL') {
        return 'flash';
    }
    // J&T Philippines often has JT prefix or numeric-only waybills
    if (preg_match('/^JT[A-Z0-9]{8,}$/', $t)) {
        return 'jnt';
    }
    if (preg_match('/^\d{10,14}$/', $t)) {
        return 'jnt';
    }
    return 'unknown';
}

/**
 * Bucket free-text parcel/courier labels into jnt|spx|flash (same rules as reports).
 */
function rts_courier_label_bucket(string $courier): ?string
{
    $c = strtoupper(trim($courier));
    if ($c === '') {
        return null;
    }
    if (str_contains($c, 'SPX')) {
        return 'spx';
    }
    if (str_contains($c, 'FLASH')) {
        return 'flash';
    }
    if (
        str_contains($c, 'J&T') ||
        str_contains($c, 'JNT') ||
        str_contains($c, 'J&T EXPRESS')
    ) {
        return 'jnt';
    }

    return null;
}
