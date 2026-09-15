---
title: qr_decode
source: includes/qr_decode.php
generated: 2026-09-15T10:58:46+02:00
type: module
function_count: 13
---


# qr_decode

> Source: `includes/qr_decode.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_qr_decode_blob_once {#rts_qr_decode_blob_once}

**Callers (4):**

- `includes/qr_decode.php`:46 — `$a = rts_qr_decode_blob_once($blob, false);`
- `includes/qr_decode.php`:53 — `$b = rts_qr_decode_blob_once($blob, true);`
- `includes/qr_decode.php`:276 — `$a = rts_qr_decode_blob_once($blob, false);`
- `includes/qr_decode.php`:277 — `$b = rts_qr_decode_blob_once($blob, true);`

## rts_qr_decode_blob_best_single {#rts_qr_decode_blob_best_single}

**Callers (1):**

- `includes/qr_decode.php`:319 — `$decoded = rts_qr_decode_blob_best_single($candidateBlob);`

## rts_qr_gd_clone_image {#rts_qr_gd_clone_image}

**Callers (2):**

- `includes/qr_decode.php`:196 — `$f1 = rts_qr_gd_clone_image($src);`
- `includes/qr_decode.php`:204 — `$f2 = rts_qr_gd_clone_image($src);`

## rts_qr_gd_to_png_blob {#rts_qr_gd_to_png_blob}

**Callers (4):**

- `includes/qr_decode.php`:199 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f1));`
- `includes/qr_decode.php`:208 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f2));`
- `includes/qr_decode.php`:216 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($scaled));`
- `includes/qr_decode.php`:227 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($rot));`

## rts_qr_gd_apply_decode_filters {#rts_qr_gd_apply_decode_filters}

**Callers (4):**

- `includes/qr_decode.php`:198 — `rts_qr_gd_apply_decode_filters($f1);`
- `includes/qr_decode.php`:206 — `rts_qr_gd_apply_decode_filters($f2);`
- `includes/qr_decode.php`:215 — `rts_qr_gd_apply_decode_filters($scaled);`
- `includes/qr_decode.php`:226 — `rts_qr_gd_apply_decode_filters($rot);`

## rts_qr_gd_sharpen {#rts_qr_gd_sharpen}

**Callers (1):**

- `includes/qr_decode.php`:207 — `rts_qr_gd_sharpen($f2);`

## rts_qr_gd_scale_if_small {#rts_qr_gd_scale_if_small}

**Callers (1):**

- `includes/qr_decode.php`:213 — `$scaled = rts_qr_gd_scale_if_small($src);`

## rts_qr_add_variant_blob {#rts_qr_add_variant_blob}

**Callers (5):**

- `includes/qr_decode.php`:184 — `rts_qr_add_variant_blob($variants, $seen, $blob);`
- `includes/qr_decode.php`:199 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f1));`
- `includes/qr_decode.php`:208 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($f2));`
- `includes/qr_decode.php`:216 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($scaled));`
- `includes/qr_decode.php`:227 — `rts_qr_add_variant_blob($variants, $seen, rts_qr_gd_to_png_blob($rot));`

## rts_qr_build_decode_variants {#rts_qr_build_decode_variants}

**Callers (1):**

- `includes/qr_decode.php`:317 — `$variants = rts_qr_build_decode_variants($blob);`

## rts_qr_decodes_differ {#rts_qr_decodes_differ}

**Callers (1):**

- `includes/qr_decode.php`:296 — `if ($okA && $okB && rts_qr_decodes_differ($a['text'], $b['text'])) {`

## rts_normalize_tracking_from_qr {#rts_normalize_tracking_from_qr}

**Callers (7):**

- `includes/deliveries_upload.php`:198 — `$ht = rts_normalize_tracking_from_qr($hintRaw);`
- `includes/deliveries_upload.php`:210 — `$ht = rts_normalize_tracking_from_qr($hintRaw);`
- `includes/online_order_parcels.php`:269 — `$tn = rts_normalize_tracking_from_qr($tn);`
- `includes/online_order_parcels.php`:329 — `return rts_normalize_tracking_from_qr($raw);`
- `includes/online_orders_generator.php`:356 — `$t = rts_normalize_tracking_from_qr($raw);`
- `includes/qr_decode.php`:280 — `$na = $okA ? rts_normalize_tracking_from_qr($a['text']) : '';`
- `includes/qr_decode.php`:281 — `$nb = $okB ? rts_normalize_tracking_from_qr($b['text']) : '';`

## rts_classify_delivery_qr_blob {#rts_classify_delivery_qr_blob}

**Callers (1):**

- `includes/deliveries_upload.php`:208 — `$class = rts_classify_delivery_qr_blob($blob);`

## rts_qr_decode_image_upload_best {#rts_qr_decode_image_upload_best}

**Callers (2):**

- `api/scan_qr_image.php`:85 — `$text = rts_qr_decode_image_upload_best($blob);`
- `api/scan_qr_image_public.php`:72 — `$text = rts_qr_decode_image_upload_best($blob);`

