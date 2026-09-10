---
title: honey_volume
source: includes/honey_volume.php
generated: 2026-09-10T10:08:58+02:00
type: module
function_count: 10
---


# honey_volume

> Source: `includes/honey_volume.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`

## honey_price_per_liter_from_gallon {#honey_price_per_liter_from_gallon}

**Callers (1):**

- `includes/honey_pricing.php`:144 — `$pricePerLiter = round(honey_price_per_liter_from_gallon((float) $priceRow['price_per_gallon']), 6);`

## honey_ml_per_gallon {#honey_ml_per_gallon}

**Callers (5):**

- `api/v1/settings.php`:199 — `'ml_per_gallon' => honey_ml_per_gallon($pdo),`
- `includes/honey_inventory.php`:513 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:720 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:822 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/rts_damaged_use.php`:313 — `$volumeGallons = honey_lines_to_gallons($deductLines, honey_ml_per_gallon($pdo));`

## honey_ml_per_gallon_save {#honey_ml_per_gallon_save}

**Callers (1):**

- `api/v1/settings.php`:217 — `$saved = honey_ml_per_gallon_save(ims_pdo(), $ml, (int) ($user['id'] ?? 0) ?: null);`

## honey_normalize_bottle_size {#honey_normalize_bottle_size}

**Callers (9):**

- `api/v1/_costing_helpers.php`:143 — `$norm = honey_normalize_bottle_size((string) $size);`
- `includes/catalog_bootstrap.php`:27 — `$norm = honey_normalize_bottle_size($size);`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_inventory.php`:705 — `$size = honey_normalize_bottle_size($size);`
- `includes/honey_inventory.php`:821 — `$size = honey_normalize_bottle_size($size);`
- `includes/honey_volume.php`:78 — `return match (honey_normalize_bottle_size($size)) {`
- `includes/honey_volume.php`:98 — `$size = honey_normalize_bottle_size((string) ($line['size'] ?? ''));`
- `includes/honey_volume.php`:168 — `'size' => honey_normalize_bottle_size((string) ($line['size'] ?? '')),`
- `includes/honey_volume.php`:184 — `'size' => honey_normalize_bottle_size((string) $size),`

## honey_bottle_size_ml {#honey_bottle_size_ml}

**Callers (3):**

- `api/v1/_costing_helpers.php`:145 — `return honey_bottle_size_ml($norm) / 1000.0;`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_volume.php`:123 — `$total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''), $mlPerGallon);`

## honey_aggregate_bottle_lines {#honey_aggregate_bottle_lines}

**Callers (3):**

- `includes/honey_inventory.php`:512 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`
- `includes/honey_inventory.php`:719 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`
- `includes/honey_inventory.php`:838 — `honey_aggregate_bottle_lines([['size' => $size, 'qty' => 1]]),`

## honey_lines_total_ml {#honey_lines_total_ml}

**Callers (1):**

- `includes/honey_volume.php`:147 — `return honey_ml_to_gallons(honey_lines_total_ml($lines, $mlPerGallon), $mlPerGallon);`

## honey_ml_to_gallons {#honey_ml_to_gallons}

**Callers (1):**

- `includes/honey_volume.php`:147 — `return honey_ml_to_gallons(honey_lines_total_ml($lines, $mlPerGallon), $mlPerGallon);`

## honey_lines_to_gallons {#honey_lines_to_gallons}

**Callers (4):**

- `includes/honey_inventory.php`:514 — `$usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);`
- `includes/honey_inventory.php`:721 — `$usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);`
- `includes/honey_inventory.php`:823 — `$usedGallons = honey_lines_to_gallons([['size' => $size, 'qty' => 1]], $mlPerGallon);`
- `includes/rts_damaged_use.php`:313 — `$volumeGallons = honey_lines_to_gallons($deductLines, honey_ml_per_gallon($pdo));`

## honey_parse_bottle_lines_from_input {#honey_parse_bottle_lines_from_input}

**Callers (1):**

- `api/v1/honey.php`:464 — `$lines = honey_parse_bottle_lines_from_input($input);`

