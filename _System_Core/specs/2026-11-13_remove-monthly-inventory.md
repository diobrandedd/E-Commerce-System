---
date: 2026-11-13
status: approved
area: inventory
---

# Spec: Remove Monthly inventory

## Goal

Remove the executive Monthly inventory absolute-override page and `inventory.monthly_count` permission. Inventory audit continues via System Logs (and the packaging finished-honey Edit logs modal). Administrator password remains for Check inventory confirm and leaf-pool edits.

## Out of scope

- Products executive Edit / Supplies set-on-hand
- Backfilling old `inventory_edit_log` into `system_activity_log`
