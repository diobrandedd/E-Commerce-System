---
title: tracking_search
source: includes/tracking_search.php
generated: 2026-08-29T11:19:50+02:00
type: module
function_count: 4
---


# tracking_search

> Source: `includes/tracking_search.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## tracking_search_is_tn_shaped {#tracking_search_is_tn_shaped}

**Callers (4):**

- `api/v1/deliveries.php`:300 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/online_order_parcels.php`:749 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/rts_case_handlers.php`:753 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/rts_case_handlers.php`:869 — `if (tracking_search_is_tn_shaped($q)) {`

## tracking_search_escape_like {#tracking_search_escape_like}

**Callers (2):**

- `includes/tracking_search.php`:38 — `[$q, tracking_search_escape_like($q) . '%'],`
- `includes/tracking_search.php`:47 — `return '%' . tracking_search_escape_like(trim($q)) . '%';`

## tracking_search_tn_column_sql {#tracking_search_tn_column_sql}

**Callers (5):**

- `api/v1/deliveries.php`:301 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('i.tracking_number', $q);`
- `includes/online_order_parcels.php`:751 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('tracking_number', $q);`
- `includes/online_order_parcels.php`:752 — `[$onoSql, $onoParams] = tracking_search_tn_column_sql('order_no', $q);`
- `includes/rts_case_handlers.php`:754 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('c.tracking_number', $q);`
- `includes/rts_case_handlers.php`:870 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('c.tracking_number', $q);`

## tracking_search_contains_like {#tracking_search_contains_like}

**Callers (4):**

- `api/v1/deliveries.php`:307 — `$like = tracking_search_contains_like($q);`
- `includes/online_order_parcels.php`:759 — `$like = tracking_search_contains_like($q);`
- `includes/rts_case_handlers.php`:761 — `$params[] = tracking_search_contains_like($q);`
- `includes/rts_case_handlers.php`:877 — `$params[] = tracking_search_contains_like($q);`

