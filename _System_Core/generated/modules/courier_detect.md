---
title: courier_detect
source: includes/courier_detect.php
generated: 2026-09-18T11:24:23+02:00
type: module
function_count: 2
---


# courier_detect

> Source: `includes/courier_detect.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_detect_courier {#rts_detect_courier}

**Callers (5):**

- `includes/rts_case_handlers.php`:179 — `'detected_courier' => rts_detect_courier($tracking),`
- `includes/rts_case_handlers.php`:202 — `'detected_courier' => rts_detect_courier($tracking),`
- `includes/rts_case_handlers.php`:455 — `'courier_detected' => rts_detect_courier($tracking),`
- `includes/rts_prefill.php`:59 — `$detected = rts_detect_courier($tracking);`
- `includes/rts_prefill.php`:307 — `'detected_courier' => rts_detect_courier($normalized),`

## rts_courier_label_bucket {#rts_courier_label_bucket}

**Callers (2):**

- `api/v1/reports.php`:371 — `return rts_courier_label_bucket($courier);`
- `includes/courier_soa_reconcile.php`:354 — `$bucket = rts_courier_label_bucket((string) ($row['courier'] ?? ''));`

