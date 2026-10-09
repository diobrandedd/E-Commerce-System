---
status: approved
created: 2026-10-08
approved_by: chat
---

# Feature Spec: Packaging materials — Report damage

## Problem

Warehouse packaging can be stocked out for use via scan, but damaged materials have no dedicated report path. Staff still need on-hand to drop when packaging is damaged, plus a damaged pool for period totals. Manual “Deduct for use” duplicates scan stock-out.

## Goals

1. **Report damage** (header scan + per-row): qty + required reason; permission `packaging.deduct` (same as Scan stock out).
2. Damage **deducts warehouse on-hand** (stock-out pool via `stock_entries` with `damage:` label) **and** writes a **damaged-pool** log row.
3. List rows show **total reported damaged** for Today / This week / Custom.
4. **Remove Deduct for use** from the packaging detail modal.

## Non-goals

- Finished Leak / damage (`/inventory/leak-damage`)
- Fulfillment packing staging damage
- Changing stock-out history (`use:` only)
- New permission key

## Behavior

1. Scan stock out for use remains unchanged (`use:` entries).
2. Report damage is independent (no FK to a prior stock-out); ops may stock out first then report damage.
3. Insufficient on-hand rejects the damage report.
4. Roll units require whole-number qty (same as stock-out).

## Acceptance

- Assigned stock-out staff can Report damage; others cannot.
- On-hand decreases; damage log + period totals update.
- Deduct for use controls are gone; Scan stock out still works.
