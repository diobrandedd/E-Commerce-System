---
date: 2026-11-11
type: cause
status: documented
area: system-logs
tags: [cause]
---

# Cause: Audit trails were per-domain only

Each domain wrote its own table (`inventory_edit_log`, `delivery_cancel_events`, POS sales, etc.) with no central feed or sidebar entry named Logs.
