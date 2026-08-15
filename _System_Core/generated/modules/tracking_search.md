---
title: tracking_search
source: includes/tracking_search.php
generated: 2026-08-15T08:14:53+02:00
type: module
function_count: 4
---


# tracking_search

> Source: `includes/tracking_search.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## tracking_search_is_tn_shaped {#tracking_search_is_tn_shaped}

**Callers (4):**

- `api/v1/deliveries.php`:327 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/online_order_parcels.php`:750 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/rts_case_handlers.php`:732 — `if (tracking_search_is_tn_shaped($q)) {`
- `includes/rts_case_handlers.php`:848 — `if (tracking_search_is_tn_shaped($q)) {`

## tracking_search_escape_like {#tracking_search_escape_like}

**Callers (2):**

- `includes/tracking_search.php`:38 — `[$q, tracking_search_escape_like($q) . '%'],`
- `includes/tracking_search.php`:47 — `return '%' . tracking_search_escape_like(trim($q)) . '%';`

## tracking_search_tn_column_sql {#tracking_search_tn_column_sql}

**Callers (5):**

- `api/v1/deliveries.php`:328 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('i.tracking_number', $q);`
- `includes/online_order_parcels.php`:752 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('tracking_number', $q);`
- `includes/online_order_parcels.php`:753 — `[$onoSql, $onoParams] = tracking_search_tn_column_sql('order_no', $q);`
- `includes/rts_case_handlers.php`:733 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('c.tracking_number', $q);`
- `includes/rts_case_handlers.php`:849 — `[$tnSql, $tnParams] = tracking_search_tn_column_sql('c.tracking_number', $q);`

## tracking_search_contains_like {#tracking_search_contains_like}

**Callers (4):**

- `api/v1/deliveries.php`:334 — `$like = tracking_search_contains_like($q);`
- `includes/online_order_parcels.php`:760 — `$like = tracking_search_contains_like($q);`
- `includes/rts_case_handlers.php`:740 — `$params[] = tracking_search_contains_like($q);`
- `includes/rts_case_handlers.php`:856 — `$params[] = tracking_search_contains_like($q);`

