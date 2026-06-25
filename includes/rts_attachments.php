<?php

declare(strict_types=1);

require_once __DIR__ . '/rts_storage.php';

/** Max RTS proof files per case (photos + videos). */
const RTS_ATTACHMENTS_MAX = 24;

/** Max single image upload (bytes). */
const RTS_DOC_IMAGE_MAX_BYTES = 12 * 1024 * 1024;

/** Max single video upload (bytes) — long phone recordings; PHP ini must allow this too. */
const RTS_DOC_VIDEO_MAX_BYTES = 512 * 1024 * 1024;

function rts_attachment_filename_valid(string $name): bool
{
    if (preg_match('/^[A-Za-z0-9._-]{1,190}$/', $name) === 1) {
        return true;
    }

    return preg_match('/^\d{4}-\d{2}\/[A-Za-z0-9._-]{1,190}$/', $name) === 1;
}

/**
 * Human-readable damaged flag for listings / DB column.
 *
 * @param string|null $reason Enum slug or empty/null when not damaged
 */
function rts_damaged_tag(?string $reason): string
{
    if ($reason === null || $reason === '') {
        return 'no';
    }
    $labels = [
        'leak' => 'Leak',
        'item_replaced' => 'Item replaced',
        'packaging_replaced' => 'Packaging replaced',
    ];
    $human = $labels[$reason] ?? $reason;

    return 'yes - ' . $human;
}

/**
 * Decode stored JSON + legacy single photo column into a normalized list.
 *
 * @return list<array{kind:string,file:string}>
 */
function rts_attachments_decode(?string $json, ?string $legacyPhoto): array
{
    $out = [];
    if ($json !== null && $json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $kind = isset($row['kind']) ? (string) $row['kind'] : '';
                $file = isset($row['file']) ? (string) $row['file'] : '';
                if (($kind === 'image' || $kind === 'video') && rts_attachment_filename_valid($file)) {
                    $out[] = ['kind' => $kind, 'file' => $file];
                }
            }
        }
    }
    if ($out === []) {
        $lp = $legacyPhoto !== null ? trim((string) $legacyPhoto) : '';
        if ($lp !== '' && rts_attachment_filename_valid($lp)) {
            $out[] = ['kind' => 'image', 'file' => $lp];
        }
    }

    return $out;
}

/**
 * First image filename (for legacy documentation_photo column).
 */
function rts_attachments_first_image(array $attachments): ?string
{
    foreach ($attachments as $row) {
        if (($row['kind'] ?? '') === 'image') {
            return $row['file'] ?? null;
        }
    }

    return null;
}

/**
 * Normalize JSON-decoded POST payload attachment rows.
 *
 * @param mixed $input
 * @return array{list: list<array{kind:string,file:string}>, error: string|null}
 */
function rts_attachments_normalize_request($input): array
{
    if ($input === null) {
        return ['list' => [], 'error' => null];
    }
    if (!is_array($input)) {
        return ['list' => [], 'error' => 'documentation_attachments must be an array'];
    }
    if (count($input) > RTS_ATTACHMENTS_MAX) {
        return ['list' => [], 'error' => 'Too many documentation files (max ' . RTS_ATTACHMENTS_MAX . ')'];
    }
    $list = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            return ['list' => [], 'error' => 'Invalid documentation_attachments entry'];
        }
        $kind = isset($row['kind']) ? trim((string) $row['kind']) : '';
        $file = isset($row['file']) ? trim((string) $row['file']) : '';
        if ($kind !== 'image' && $kind !== 'video') {
            return ['list' => [], 'error' => 'Invalid attachment kind'];
        }
        if (!rts_attachment_filename_valid($file)) {
            return ['list' => [], 'error' => 'Invalid attachment filename'];
        }
        $list[] = ['kind' => $kind, 'file' => $file];
    }

    return ['list' => $list, 'error' => null];
}

/**
 * Save uploaded RTS documentation into rts_documentation/YYYY-MM/ (same layout as deliveries).
 *
 * @return array{file_name: string, file_url: string, media_kind: string}
 */
function rts_documentation_save_upload(string $tmp, int $size, ?string $originalFilename = null, ?string $recordDate = null): array
{
    if ($tmp === '' || $size < 1) {
        throw new RuntimeException('Empty upload');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $imageMax = RTS_DOC_IMAGE_MAX_BYTES;
    $videoMax = RTS_DOC_VIDEO_MAX_BYTES;
    $kind = '';
    $ext = '';
    $maxForType = $imageMax;

    switch ($mime) {
        case 'image/jpeg':
            $ext = 'jpg';
            $kind = 'image';
            break;
        case 'image/png':
            $ext = 'png';
            $kind = 'image';
            break;
        case 'image/webp':
            $ext = 'webp';
            $kind = 'image';
            break;
        case 'image/gif':
            $ext = 'gif';
            $kind = 'image';
            break;
        case 'image/heic':
        case 'image/heif':
            $ext = 'heic';
            $kind = 'image';
            break;
        case 'video/mp4':
            $ext = 'mp4';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'video/webm':
            $ext = 'webm';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'video/quicktime':
            $ext = 'mov';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'video/3gpp':
        case 'video/3gp':
            $ext = '3gp';
            $kind = 'video';
            $maxForType = $videoMax;
            break;
        case 'application/octet-stream':
            $orig = strtolower((string) ($originalFilename ?? ''));
            if (preg_match('/\.(mp4|mov|webm|3gp)$/i', $orig, $m)) {
                $ext = strtolower($m[1]);
                $kind = 'video';
                $maxForType = $videoMax;
                break;
            }
            // fall through
        default:
            throw new RuntimeException('Unsupported type (use JPG/PNG/WebP/GIF or MP4/WebM/MOV)');
    }

    if ($size > $maxForType) {
        $mb = (int) round($maxForType / (1024 * 1024));
        throw new RuntimeException(
            'File too large (max about ' . $mb . 'MB for ' . ($kind === 'video' ? 'video' : 'images') . ')'
        );
    }

    $monthKey = rts_storage_rts_month_key($recordDate);
    $targetDir = rts_storage_dir_rts_month($recordDate);
    $prefix = $kind === 'video' ? 'rtsvid_' : 'rtsdoc_';
    $baseName = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $storedName = $monthKey . '/' . $baseName;
    $targetPath = $targetDir . '/' . $baseName;

    if (!move_uploaded_file($tmp, $targetPath)) {
        throw new RuntimeException('Could not save uploaded file');
    }

    return [
        'file_name' => $storedName,
        'file_url' => rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_RTS, $storedName),
        'media_kind' => $kind,
    ];
}
