---
status: shipped
spec: "[[specs/2026-09-11_inventory-check-scoped-compare]]"
created: 2026-09-11
approved_by: chat 2026-09-11
---

# Implementation Plan: Inventory check private sessions + scoped compare

> Spec: [[specs/2026-09-11_inventory-check-scoped-compare]]

## Summary

Per-user private audits; optional batch; compare counted keys only; view_all merge history.

## Files

| File | Action |
|------|--------|
| `sql/migration_phase80_inventory_check_private_sessions.sql` | drop date-only unique |
| `includes/inventory_check.php` | current/open/list/compare |
| `api/v1/inventory_check.php` | gates + merge actions |
| `InventoryCheckPage.tsx` | batch + history select UI |
