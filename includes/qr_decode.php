<?php

declare(strict_types=1);

/**
 * Decode first QR from image bytes using chillerlan/php-qrcode (optional invert pass).
 *
 * @return array{type:'ok', text:string}|array{type:'empty'}|array{type:'error'}
 */
function rts_qr_decode_blob_once(string $blob, bool $invert): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_readable($autoload)) {
        return ['type' => 'error'];
    }
    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        return ['type' => 'error'];
    }
    require_once $autoload;

    $options = new chillerlan\QRCode\QROptions();
    $options->readerIncreaseContrast = true;
    $options->readerGrayscale = true;
    $options->readerInvertColors = $invert;

    try {
        $qr = new chillerlan\QRCode\QRCode($options);
        $result = $qr->readFromBlob($blob);
        $text = trim((string) $result);
        if ($text === '') {
            return ['type' => 'empty'];
        }
        return ['type' => 'ok', 'text' => $text];
    } catch (chillerlan\QRCode\Decoder\QRCodeDecoderException) {
        return ['type' => 'empty'];
    } catch (Throwable) {
        return ['type' => 'error'];
    }
}

/**
 * Compare two decoded strings (case-insensitive trim) for ambiguity detection.
 */
function rts_qr_decodes_differ(string $a, string $b): bool
{
    return strcasecmp(trim($a), trim($b)) !== 0;
}

/**
 * Normalize tracking from QR payload (trim; strip common URL noise).
 */
function rts_normalize_tracking_from_qr(string $raw): string
{
    $t = trim($raw);
    if ($t === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $t)) {
        $path = parse_url($t, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            $seg = basename($path);
            if ($seg !== '' && $seg !== '/') {
                $t = urldecode($seg);
            }
        }
        $q = parse_url($t, PHP_URL_QUERY);
        if (is_string($q) && preg_match('/(?:^|&)tracking=([^&]+)/i', $q, $m)) {
            $t = urldecode($m[1]);
        }
    }
    return trim($t);
}

/**
 * Delivery bulk / RTS-aligned: prefer non-invert decode (same as scan_qr_image.php), then invert.
 *
 * @return array{status:string, tracking:?string, raw:?string}
 */
function rts_classify_delivery_qr_blob(string $blob): array
{
    $a = rts_qr_decode_blob_once($blob, false);
    $b = rts_qr_decode_blob_once($blob, true);
    $okA = ($a['type'] === 'ok');
    $okB = ($b['type'] === 'ok');
    $na = $okA ? rts_normalize_tracking_from_qr($a['text']) : '';
    $nb = $okB ? rts_normalize_tracking_from_qr($b['text']) : '';

    if ($na !== '') {
        if ($nb !== '' && strcasecmp($na, $nb) !== 0) {
            $raw = json_encode(['invert_off' => $a['text'], 'invert_on' => $b['text']], JSON_UNESCAPED_UNICODE);
            $raw = $raw !== false ? mb_substr($raw, 0, 500) : null;

            return ['status' => 'ambiguous', 'tracking' => null, 'raw' => $raw];
        }

        return ['status' => 'decoded_ok', 'tracking' => $na, 'raw' => mb_substr($a['text'], 0, 500)];
    }
    if ($nb !== '') {
        return ['status' => 'decoded_ok', 'tracking' => $nb, 'raw' => mb_substr($b['text'], 0, 500)];
    }
    if ($okA && $okB && rts_qr_decodes_differ($a['text'], $b['text'])) {
        $raw = json_encode(['invert_off' => $a['text'], 'invert_on' => $b['text']], JSON_UNESCAPED_UNICODE);
        $raw = $raw !== false ? mb_substr($raw, 0, 500) : null;

        return ['status' => 'ambiguous', 'tracking' => null, 'raw' => $raw];
    }
    if ($okA || $okB) {
        $raw = $okA ? mb_substr($a['text'], 0, 500) : mb_substr($b['text'], 0, 500);

        return ['status' => 'needs_manual', 'tracking' => null, 'raw' => $raw];
    }

    return ['status' => 'needs_manual', 'tracking' => null, 'raw' => null];
}

/**
 * Best-effort single QR string from image bytes (non-invert first, then invert) — aligns with RTS image scan.
 */
function rts_qr_decode_image_upload_best(string $blob): ?string
{
    $a = rts_qr_decode_blob_once($blob, false);
    if ($a['type'] === 'ok') {
        $t = trim($a['text']);
        if ($t !== '') {
            return $t;
        }
    }
    $b = rts_qr_decode_blob_once($blob, true);
    if ($b['type'] === 'ok') {
        $t = trim($b['text']);
        if ($t !== '') {
            return $t;
        }
    }

    return null;
}
