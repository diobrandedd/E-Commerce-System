<?php

declare(strict_types=1);

/** Max RTS proof files per case (photos + videos). */
const RTS_ATTACHMENTS_MAX = 24;

/** Max single image upload (bytes). */
const RTS_DOC_IMAGE_MAX_BYTES = 12 * 1024 * 1024;

/** Max single video upload (bytes) — long phone recordings; PHP ini must allow this too. */
const RTS_DOC_VIDEO_MAX_BYTES = 512 * 1024 * 1024;

function rts_attachment_filename_valid(string $name): bool
{
    return preg_match('/^[A-Za-z0-9._-]{1,190}$/', $name) === 1;
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
