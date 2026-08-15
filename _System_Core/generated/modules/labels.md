---
title: labels
source: includes/labels.php
generated: 2026-08-15T03:29:16+02:00
type: module
function_count: 3
---


# labels

> Source: `includes/labels.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_products.php`

## rts_courier_label {#rts_courier_label}

**Callers (8):**

- `sp_sort/batches.php`:99 — `<option value="<?= $c ?>"<?= $filterCourier === $c ? ' selected' : '' ?>><?= htmlspecialchars(rts_courier_label($c), ENT_QUOTES, 'UTF-8') ?></option>`
- `sp_sort/batches.php`:175 — `<td><?= htmlspecialchars(rts_courier_label($courier), ENT_QUOTES, 'UTF-8') ?></td>`
- `dashboard.php`:172 — `'label' => rts_courier_label($courierCode),`
- `dashboard.php`:461 — `<td><?= htmlspecialchars(rts_courier_label($cc), ENT_QUOTES, 'UTF-8') ?></td>`
- `dashboard.php`:583 — `<td><?= htmlspecialchars(rts_courier_label((string) $r['courier_final']), ENT_QUOTES, 'UTF-8') ?></td>`
- `records.php`:698 — `'courierFinal' => rts_courier_label((string) $c['courier_final']),`
- `records.php`:699 — `'courierDetected' => rts_courier_label((string) $c['courier_detected']),`
- `records.php`:741 — `<td><?= htmlspecialchars(rts_courier_label((string) $c['courier_final']), ENT_QUOTES, 'UTF-8') ?></td>`

## rts_shop_label {#rts_shop_label}

**Callers (6):**

- `sp_sort/batches.php`:108 — `<option value="<?= $s ?>"<?= $filterShop === $s ? ' selected' : '' ?>><?= htmlspecialchars(rts_shop_label($s), ENT_QUOTES, 'UTF-8') ?></option>`
- `sp_sort/batches.php`:174 — `<td><?= htmlspecialchars(rts_shop_label((string) $r['shop']), ENT_QUOTES, 'UTF-8') ?></td>`
- `dashboard.php`:150 — `'label' => rts_shop_label($shopCode),`
- `dashboard.php`:478 — `<td><?= htmlspecialchars(rts_shop_label($ss), ENT_QUOTES, 'UTF-8') ?></td>`
- `dashboard.php`:582 — `<td><?= htmlspecialchars(rts_shop_label((string) $r['shop']), ENT_QUOTES, 'UTF-8') ?></td>`
- `records.php`:668 — `$shopHtml = rts_shop_label((string) $c['shop']);`

## rts_line_summary {#rts_line_summary}

**Callers (2):**

- `records.php`:674 — `$linesPlain[] = html_entity_decode((string) rts_line_summary($L), ENT_QUOTES | ENT_HTML5, 'UTF-8');`
- `records.php`:761 — `<li><?= rts_line_summary($L) ?></li>`

