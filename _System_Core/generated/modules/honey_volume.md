---
title: honey_volume
source: includes/honey_volume.php
generated: 2026-06-05T10:39:42+02:00
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

**Callers (5):**

- `api/v1/_costing_helpers.php`:68 — `$norm = honey_normalize_bottle_size((string) $size);`
- `includes/honey_volume.php`:33 — `return match (honey_normalize_bottle_size($size)) {`
- `includes/honey_volume.php`:53 — `$size = honey_normalize_bottle_size((string) ($line['size'] ?? ''));`
- `includes/honey_volume.php`:115 — `'size' => honey_normalize_bottle_size((string) ($line['size'] ?? '')),`
- `includes/honey_volume.php`:131 — `'size' => honey_normalize_bottle_size((string) $size),`

## honey_bottle_size_ml {#honey_bottle_size_ml}

**Callers (2):**

- `api/v1/_costing_helpers.php`:70 — `return honey_bottle_size_ml($norm) / 1000.0;`
- `includes/honey_volume.php`:76 — `$total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''));`

## honey_aggregate_bottle_lines {#honey_aggregate_bottle_lines}

**Callers (1):**

- `includes/honey_inventory.php`:284 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`

## honey_lines_total_ml {#honey_lines_total_ml}

**Callers (1):**

- `includes/honey_volume.php`:94 — `return honey_ml_to_gallons(honey_lines_total_ml($lines));`

## honey_ml_to_gallons {#honey_ml_to_gallons}

**Callers (1):**

- `includes/honey_volume.php`:94 — `return honey_ml_to_gallons(honey_lines_total_ml($lines));`

## honey_lines_to_gallons {#honey_lines_to_gallons}

**Callers (1):**

- `includes/honey_inventory.php`:285 — `$usedGallons = honey_lines_to_gallons($lines);`

## honey_parse_bottle_lines_from_input {#honey_parse_bottle_lines_from_input}

**Callers (1):**

- `api/v1/honey.php`:150 — `$lines = honey_parse_bottle_lines_from_input($input);`

