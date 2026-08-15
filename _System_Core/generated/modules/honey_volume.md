---
title: honey_volume
source: includes/honey_volume.php
generated: 2026-08-15T08:14:36+02:00
type: module
function_count: 8
---


# honey_volume

> Source: `includes/honey_volume.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## honey_price_per_liter_from_gallon {#honey_price_per_liter_from_gallon}

**Callers (1):**

- `includes/honey_pricing.php`:144 — `$pricePerLiter = round(honey_price_per_liter_from_gallon((float) $priceRow['price_per_gallon']), 6);`

## honey_normalize_bottle_size {#honey_normalize_bottle_size}

**Callers (7):**

- `api/v1/_costing_helpers.php`:143 — `$norm = honey_normalize_bottle_size((string) $size);`
- `includes/catalog_bootstrap.php`:27 — `$norm = honey_normalize_bottle_size($size);`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_volume.php`:33 — `return match (honey_normalize_bottle_size($size)) {`
- `includes/honey_volume.php`:53 — `$size = honey_normalize_bottle_size((string) ($line['size'] ?? ''));`
- `includes/honey_volume.php`:115 — `'size' => honey_normalize_bottle_size((string) ($line['size'] ?? '')),`
- `includes/honey_volume.php`:131 — `'size' => honey_normalize_bottle_size((string) $size),`

## honey_bottle_size_ml {#honey_bottle_size_ml}

**Callers (3):**

- `api/v1/_costing_helpers.php`:145 — `return honey_bottle_size_ml($norm) / 1000.0;`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_volume.php`:76 — `$total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''));`

## honey_aggregate_bottle_lines {#honey_aggregate_bottle_lines}

**Callers (1):**

- `includes/honey_inventory.php`:395 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`

## honey_lines_total_ml {#honey_lines_total_ml}

**Callers (1):**

- `includes/honey_volume.php`:94 — `return honey_ml_to_gallons(honey_lines_total_ml($lines));`

## honey_ml_to_gallons {#honey_ml_to_gallons}

**Callers (1):**

- `includes/honey_volume.php`:94 — `return honey_ml_to_gallons(honey_lines_total_ml($lines));`

## honey_lines_to_gallons {#honey_lines_to_gallons}

**Callers (2):**

- `includes/honey_inventory.php`:396 — `$usedGallons = honey_lines_to_gallons($lines);`
- `includes/rts_damaged_use.php`:313 — `$volumeGallons = honey_lines_to_gallons($deductLines);`

## honey_parse_bottle_lines_from_input {#honey_parse_bottle_lines_from_input}

**Callers (1):**

- `api/v1/honey.php`:312 — `$lines = honey_parse_bottle_lines_from_input($input);`

