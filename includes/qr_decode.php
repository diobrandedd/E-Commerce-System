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
 * Non-invert first, then invert.
 */
function rts_qr_decode_blob_best_single(string $blob): ?string
{
    $a = rts_qr_decode_blob_once($blob, false);
    if ($a['type'] === 'ok') {
        $t = trim((string) $a['text']);
        if ($t !== '') {
            return $t;
        }
    }
    $b = rts_qr_decode_blob_once($blob, true);
    if ($b['type'] === 'ok') {
        $t = trim((string) $b['text']);
        if ($t !== '') {
            return $t;
        }
    }

    return null;
}

/**
 * @param resource|\GdImage $img
 */
function rts_qr_gd_clone_image($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w < 1 || $h < 1) {
        return null;
    }
    $clone = imagecreatetruecolor($w, $h);
    if ($clone === false) {
        return null;
    }
    imagecopy($clone, $img, 0, 0, 0, 0, $w, $h);

    return $clone;
}

/**
 * @param resource|\GdImage $img
 */
function rts_qr_gd_to_png_blob($img): ?string
{
    ob_start();
    $ok = imagepng($img, null, 6);
    $bin = ob_get_clean();
    if (!$ok || !is_string($bin) || $bin === '') {
        return null;
    }

    return $bin;
}

/**
 * @param resource|\GdImage $img
 */
function rts_qr_gd_apply_decode_filters($img): void
{
    @imagefilter($img, IMG_FILTER_GRAYSCALE);
    @imagefilter($img, IMG_FILTER_CONTRAST, -40);
    @imagefilter($img, IMG_FILTER_BRIGHTNESS, 10);
}

/**
 * @param resource|\GdImage $img
 */
function rts_qr_gd_sharpen($img): void
{
    $matrix = [
        [-1, -1, -1],
        [-1, 16, -1],
        [-1, -1, -1],
    ];
    @imageconvolution($img, $matrix, 8, 0);
}

/**
 * @param resource|\GdImage $img
 * @return resource|\GdImage|null
 */
function rts_qr_gd_scale_if_small($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w < 1 || $h < 1) {
        return null;
    }
    $minSide = min($w, $h);
    if ($minSide >= 900) {
        return null;
    }
    $factor = min(2.2, 900 / $minSide);
    $nw = (int) max(1, round($w * $factor));
    $nh = (int) max(1, round($h * $factor));
    if (function_exists('imagescale')) {
        $scaled = @imagescale($img, $nw, $nh);
        if ($scaled !== false) {
            return $scaled;
        }
    }
    $scaled = imagecreatetruecolor($nw, $nh);
    if ($scaled === false) {
        return null;
    }
    imagecopyresampled($scaled, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

    return $scaled;
}

/**
 * @param array<int, string> $out
 * @param array<string, bool> $seen
 */
function rts_qr_add_variant_blob(array &$out, array &$seen, ?string $blob): void
{
    if (!is_string($blob) || $blob === '') {
        return;
    }
    // Guard memory/cpu blowups from huge generated variants.
    if (strlen($blob) > 16 * 1024 * 1024) {
        return;
    }
    $key = md5($blob);
    if (isset($seen[$key])) {
        return;
    }
    $seen[$key] = true;
    $out[] = $blob;
}

/**
 * Build enhanced image variants for better QR detection.
 *
 * @return array<int, string>
 */
function rts_qr_build_decode_variants(string $blob): array
{
    $variants = [];
    $seen = [];
    rts_qr_add_variant_blob($variants, $seen, $blob);

    if (!extension_loaded('gd')) {
        return $variants;
    }

    $src = @imagecreatefromstring($blob);
    if ($src === false) {
        return $variants;
    }

    // Pass 1: grayscale + contrast.
    $f1 = rts_qr_gd_clone_image($src);
    if ($f1 !== null) {
        rts_qr_gd_apply_decode_filters($f1);
        rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f1));
        imagedestroy($f1);
    }

    // Pass 2: sharpened after filters.
    $f2 = rts_qr_gd_clone_image($src);
    if ($f2 !== null) {
        rts_qr_gd_apply_decode_filters($f2);
        rts_qr_gd_sharpen($f2);
        rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f2));
        imagedestroy($f2);
    }

    // Pass 3: upscale small images before filtering.
    $scaled = rts_qr_gd_scale_if_small($src);
    if ($scaled !== null) {
        rts_qr_gd_apply_decode_filters($scaled);
        rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($scaled));
        imagedestroy($scaled);
    }

    // Pass 4/5: rotated variants, useful when EXIF orientation is off.
    foreach ([90, 270] as $deg) {
        $rot = @imagerotate($src, $deg, 0);
        if ($rot === false) {
            continue;
        }
        rts_qr_gd_apply_decode_filters($rot);
        rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($rot));
        imagedestroy($rot);
    }

    imagedestroy($src);

    return $variants;
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
 * Best-effort single QR string from image bytes.
 * Tries original image first, then enhanced variants (filter/sharpen/scale/rotate).
 */
function rts_qr_decode_image_upload_best(string $blob): ?string
{
    $variants = rts_qr_build_decode_variants($blob);
    foreach ($variants as $candidateBlob) {
        $decoded = rts_qr_decode_blob_best_single($candidateBlob);
        if ($decoded !== null) {
            return $decoded;
        }
    }

    return null;
}
