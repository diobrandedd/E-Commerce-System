---
title: rts_products
source: includes/rts_products.php
generated: 2026-06-12T07:27:52+02:00
type: module
function_count: 14
---


# rts_products

> Source: `includes/rts_products.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_product_type_valid {#rts_product_type_valid}

**Callers (1):**

- `includes/rts_products.php`:125 — `if (!rts_product_type_valid($pt)) {`

## rts_product_label {#rts_product_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_honey_size_valid {#rts_honey_size_valid}

**Callers (2):**

- `includes/rts_products.php`:138 — `if (!rts_honey_size_valid($hs)) {`
- `includes/rts_products.php`:165 — `if (!rts_honey_size_valid($chs)) {`

## rts_honey_variant_valid {#rts_honey_variant_valid}

**Callers (2):**

- `includes/rts_products.php`:141 — `if (!rts_honey_variant_valid($hv)) {`
- `includes/rts_products.php`:168 — `if (!rts_honey_variant_valid($chv)) {`

## rts_combo_product_valid {#rts_combo_product_valid}

**Callers (1):**

- `includes/rts_products.php`:150 — `if (!rts_combo_product_valid($cp)) {`

## rts_type_needs_honey {#rts_type_needs_honey}

**Callers (3):**

- `includes/rts_products.php`:135 — `if (rts_type_needs_honey($pt)) {`
- `includes/rts_products.php`:218 — `if (rts_type_needs_honey($pt)) {`
- `includes/rts_products.php`:247 — `if (rts_type_needs_honey($pt)) {`

## rts_type_has_combo {#rts_type_has_combo}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_type_combo_is_honey {#rts_type_combo_is_honey}

**Callers (3):**

- `includes/rts_products.php`:161 — `if (rts_type_combo_is_honey($pt)) {`
- `includes/rts_products.php`:228 — `if (rts_type_combo_is_honey($pt)) {`
- `includes/rts_products.php`:263 — `if (rts_type_combo_is_honey($pt) && !empty($row['combo_honey_size'])) {`

## rts_type_combo_is_other {#rts_type_combo_is_other}

**Callers (3):**

- `includes/rts_products.php`:147 — `if (rts_type_combo_is_other($pt)) {`
- `includes/rts_products.php`:223 — `if (rts_type_combo_is_other($pt)) {`
- `includes/rts_products.php`:256 — `if (rts_type_combo_is_other($pt) && !empty($row['combo_product'])) {`

## rts_validate_line {#rts_validate_line}

**Callers (1):**

- `includes/rts_products.php`:190 — `$err = rts_validate_line($line, $i + 1);`

## rts_validate_lines_v2 {#rts_validate_lines_v2}

**Callers (2):**

- `includes/rts_case_handlers.php`:148 — `$err = rts_validate_lines_v2($linesIn);`
- `includes/rts_case_handlers.php`:359 — `$err = rts_validate_lines_v2($linesIn);`

## rts_normalize_line {#rts_normalize_line}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_line_display {#rts_line_display}

**Callers (1):**

- `includes/labels.php`:33 — `return htmlspecialchars(rts_line_display($row), ENT_QUOTES, 'UTF-8');`

## rts_line_items_schema_v2 {#rts_line_items_schema_v2}

**Tables:** `rts_line_items`

**Callers (4):**

- `includes/rts_schema.php`:163 — `if (rts_line_items_schema_v2($pdo)) {`
- `includes/rts_schema.php`:195 — `if (rts_line_items_schema_v2($pdo, true)) {`
- `includes/rts_schema.php`:234 — `return rts_line_items_schema_v2($pdo, true) ? null : 'Line items upgrade failed.';`
- `dashboard.php`:57 — `if (rts_line_items_schema_v2($pdo)) {`

