---
title: deliveries_review_nav
source: includes/deliveries_review_nav.php
generated: 2026-06-04T03:07:03+02:00
type: module
function_count: 2
---


# deliveries_review_nav

> Source: `includes/deliveries_review_nav.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_delivery_review_attention_count {#rts_delivery_review_attention_count}

**Tables:** `delivery_batch_items`

**Callers (1):**

- `includes/deliveries_review_nav.php`:25 — `$n = rts_delivery_review_attention_count($pdo);`

## rts_delivery_review_nav_badge_html {#rts_delivery_review_nav_badge_html}

**Callers (5):**

- `deliveries/review.php`:43 — `$deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);`
- `sp_sort/bootstrap.php`:18 — `return rts_delivery_review_nav_badge_html(rts_pdo());`
- `dashboard.php`:25 — `$deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);`
- `record.php`:15 — `$deliveryNavBadge = rts_delivery_review_nav_badge_html(rts_pdo());`
- `records.php`:54 — `$deliveryNavBadge = rts_delivery_review_nav_badge_html($pdo);`

