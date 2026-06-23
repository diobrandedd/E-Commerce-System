<?php

declare(strict_types=1);

require_once __DIR__ . '/courier_detect.php';
require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/online_order_parcels.php';
require_once __DIR__ . '/rts_products.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/rts_storage.php';

/** @return list<string> */
function rts_case_allowed_dispositions(): array
{
    return ['standard', 'swapped', 'leak'];
}

function rts_case_disposition_label(string $disposition): string
{
    return match ($disposition) {
        'swapped' => 'Swapped',
        'leak' => 'Leak',
        default => 'Standard',
    };
}

/**
 * Map Online Orders source_pipeline to RTS shop slug, or null when CSR must pick.
 */
function rts_pipeline_to_shop(?string $pipeline): ?string
{
    $p = strtolower(trim((string) $pipeline));

    return match ($p) {
        'vip' => 'vip',
        'shopee_spx', 'shopee' => 'shopee',
        default => null,
    };
}

/**
 * Normalize parcel / CSR courier string to RTS courier_final when confident.
 */
function rts_parcel_courier_to_final(?string $courier, string $tracking): ?string
{
    $c = strtolower(trim((string) $courier));
    if ($c !== '') {
        if (str_contains($c, 'spx') || str_contains($c, 'shopee')) {
            return 'spx';
        }
        if (str_contains($c, 'jnt') || str_contains($c, 'j&t')) {
            return 'jnt';
        }
        if (str_contains($c, 'flash')) {
            return 'flash';
        }
    }
    $detected = rts_detect_courier($tracking);
    if ($detected !== 'unknown' && in_array($detected, rts_case_allowed_couriers(), true)) {
        return $detected;
    }

    return null;
}

/**
 * Map dept product_key from parcel parser to RTS product_type.
 */
function rts_dept_key_to_product_type(string $key): ?string
{
    return match ($key) {
        'pt' => 'pouch_pt',
        'brc' => 'pouch_brc',
        'jar_cacao' => 'jar_cacao',
        'honey_comb' => 'jar_honey_comb',
        default => null,
    };
}

/**
 * Best-effort: parcel items_raw → RTS line suggestions.
 *
 * @return array{lines: list<array<string, mixed>>, warnings: list<string>}
 */
function rts_items_raw_to_line_suggestions(string $itemsRaw): array
{
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    $lines = [];
    $warnings = array_values($parsed['errors'] ?? []);

    foreach ($parsed['deltas'] as $d) {
        $variant = strtolower((string) ($d['variant'] ?? ''));
        if (!rts_honey_variant_valid($variant)) {
            $warnings[] = 'Unmapped honey variant: ' . ($d['variant'] ?? '');
            continue;
        }
        $size = strtolower((string) ($d['size'] ?? ''));
        $size = match ($size) {
            '1l', '1 l', 'liter', 'litre' => 'liter',
            default => $size,
        };
        if (!rts_honey_size_valid($size)) {
            $warnings[] = 'Unmapped honey size: ' . ($d['size'] ?? '');
            continue;
        }
        $qty = (float) ($d['qty'] ?? 0);
        if ($qty < 0.01) {
            continue;
        }
        $lines[] = [
            'product_type' => 'honey',
            'honey_size' => $size,
            'honey_variant' => $variant,
            'combo_product' => null,
            'combo_quantity' => null,
            'combo_honey_size' => null,
            'combo_honey_variant' => null,
            'quantity' => round($qty, 2),
            'is_bonus' => false,
        ];
    }

    foreach ($parsed['other_items'] as $item) {
        $qty = (float) ($item['qty'] ?? 0);
        if ($qty < 0.01) {
            continue;
        }
        $key = isset($item['product_key']) ? (string) $item['product_key'] : '';
        $pt = $key !== '' ? rts_dept_key_to_product_type($key) : null;
        if ($pt === null) {
            $warnings[] = 'Could not map line: ' . (string) ($item['raw'] ?? $item['label'] ?? 'unknown');
            continue;
        }
        $lines[] = [
            'product_type' => $pt,
            'honey_size' => null,
            'honey_variant' => null,
            'combo_product' => null,
            'combo_quantity' => null,
            'combo_honey_size' => null,
            'combo_honey_variant' => null,
            'quantity' => round($qty, 2),
            'is_bonus' => false,
        ];
    }

    return ['lines' => $lines, 'warnings' => array_values(array_unique($warnings))];
}

/**
 * @return array{match: bool, item_id: ?int, photo_filename: ?string, delivery_date: ?string, file_url: ?string}
 */
function rts_prefill_delivery_lookup(PDO $pdo, string $tracking): array
{
    $tracking = trim($tracking);
    if ($tracking === '') {
        return ['match' => false, 'item_id' => null, 'photo_filename' => null, 'delivery_date' => null, 'file_url' => null];
    }
    $st = $pdo->prepare(
        'SELECT i.id AS item_id, i.photo_filename, b.delivery_date
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL
           AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
           AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
         ORDER BY b.submitted_at DESC, i.id DESC
         LIMIT 1'
    );
    $st->execute([$tracking]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['match' => false, 'item_id' => null, 'photo_filename' => null, 'delivery_date' => null, 'file_url' => null];
    }
    $fn = (string) $row['photo_filename'];

    return [
        'match' => true,
        'item_id' => (int) $row['item_id'],
        'photo_filename' => $fn,
        'delivery_date' => (string) $row['delivery_date'],
        'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn),
    ];
}

/**
 * @return array<string, mixed>
 */
function rts_case_handler_tracking_prefill(PDO $pdo, string $tracking): array
{
    $normalized = online_parcels_normalize_tracking(trim($tracking));
    if ($normalized === '') {
        throw new RuntimeException('Missing tracking');
    }

    $found = ['parcel' => false, 'delivery' => false, 'csr_line' => false];
    $suggested = [
        'shop' => null,
        'courier_final' => null,
        'date_delivered' => null,
        'lines' => [],
        'lines_parse_warnings' => [],
        'recipient_name' => null,
        'order_no' => null,
    ];
    $fieldSources = [];
    $manualRequired = ['shop' => true, 'courier' => true, 'lines' => true];

    $parcel = online_parcels_get_by_tracking($pdo, $normalized);
    $itemsRaw = '';
    if ($parcel !== null) {
        $found['parcel'] = true;
        $itemsRaw = (string) ($parcel['items_raw'] ?? '');
        $pipeline = (string) ($parcel['source_pipeline'] ?? '');
        $shop = rts_pipeline_to_shop($pipeline);
        if ($shop !== null) {
            $suggested['shop'] = $shop;
            $fieldSources['shop'] = 'parcel_pipeline';
            $manualRequired['shop'] = false;
        }
        $courier = rts_parcel_courier_to_final((string) ($parcel['courier'] ?? ''), $normalized);
        if ($courier !== null) {
            $suggested['courier_final'] = $courier;
            $fieldSources['courier'] = 'parcel';
            $manualRequired['courier'] = false;
        }
        $suggested['recipient_name'] = $parcel['recipient_name'] ?? null;
        $suggested['order_no'] = $parcel['order_no'] ?? null;
    }

    if ($itemsRaw === '') {
        $csrSt = $pdo->prepare(
            'SELECT l.items, l.courier, l.source_pipeline, l.recipient_name, l.order_no
             FROM csr_online_order_lines l
             INNER JOIN csr_online_batches b ON b.id = l.batch_id
             WHERE b.status IN ("active", "processed")
               AND LOWER(TRIM(l.tracking_number)) = LOWER(TRIM(?))
             ORDER BY l.id DESC
             LIMIT 1'
        );
        $csrSt->execute([$normalized]);
        $csrRow = $csrSt->fetch(PDO::FETCH_ASSOC);
        if (is_array($csrRow)) {
            $found['csr_line'] = true;
            $itemsRaw = (string) ($csrRow['items'] ?? '');
            if ($suggested['shop'] === null) {
                $shop = rts_pipeline_to_shop((string) ($csrRow['source_pipeline'] ?? ''));
                if ($shop !== null) {
                    $suggested['shop'] = $shop;
                    $fieldSources['shop'] = 'csr_pipeline';
                    $manualRequired['shop'] = false;
                }
            }
            if ($suggested['courier_final'] === null) {
                $courier = rts_parcel_courier_to_final((string) ($csrRow['courier'] ?? ''), $normalized);
                if ($courier !== null) {
                    $suggested['courier_final'] = $courier;
                    $fieldSources['courier'] = 'csr_line';
                    $manualRequired['courier'] = false;
                }
            }
            if ($suggested['recipient_name'] === null) {
                $suggested['recipient_name'] = $csrRow['recipient_name'] ?? null;
            }
            if ($suggested['order_no'] === null) {
                $suggested['order_no'] = $csrRow['order_no'] ?? null;
            }
        }
    }

    if ($itemsRaw !== '') {
        $mapped = rts_items_raw_to_line_suggestions($itemsRaw);
        $suggested['lines'] = $mapped['lines'];
        $suggested['lines_parse_warnings'] = $mapped['warnings'];
        if ($mapped['lines'] !== []) {
            $manualRequired['lines'] = false;
            $fieldSources['lines'] = 'items_raw';
        }
    }

    $delivery = rts_prefill_delivery_lookup($pdo, $normalized);
    $beforeShipment = ['available' => false, 'download_url' => null];
    if ($delivery['match']) {
        $found['delivery'] = true;
        if (!empty($delivery['delivery_date'])) {
            $suggested['date_delivered'] = (string) $delivery['delivery_date'];
            $fieldSources['date_delivered'] = 'deliveries';
        }
        if (trim((string) ($delivery['photo_filename'] ?? '')) !== '') {
            $beforeShipment = [
                'available' => true,
                'download_url' => $delivery['file_url'],
            ];
        }
    }

    $existingCase = rts_cases_fetch_by_tracking($pdo, $normalized);

    return [
        'tracking_normalized' => $normalized,
        'found' => $found,
        'suggested' => $suggested,
        'before_shipment' => $beforeShipment,
        'field_sources' => $fieldSources,
        'manual_required' => $manualRequired,
        'existing_rts_case' => $existingCase !== null,
        'detected_courier' => rts_detect_courier($normalized),
    ];
}
