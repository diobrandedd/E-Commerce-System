---
status: approved
created: 2026-10-06
---

# Feature Spec: Packaging stock-out history

## Problem

Warehouse packaging stock-outs (scan / manual deduct) are written to `stock_entries` with `use:` labels and `entered_by`, but there is no cross-item history page to audit who stocked out what and when. Per-row activity in the detail modal is too narrow for ops review.

## Goals

1. History page listing all packaging materials **scan/manual stock-outs** (`use:` entries).
2. Columns: when, item, qty + unit, reason, who.
3. Filters: search (item / reason / actor), date range, person (`user_id`).
4. Permission: `packaging.view` only (not scan-only `packaging.deduct`).
5. Link from Packaging Materials (History action + sidebar sibling).

## Non-goals

- Revert/void stock-outs
- CSV export
- Fulfillment staging issue/consume history
- Stock-in history on this page

## Acceptance

- Users with `packaging.view` open `/inventory/packaging-materials/history` and see paginated stock-outs newest first.
- Scan-only users cannot open the history route.
- Each row shows absolute qty, stripped reason (no `use:` prefix), and actor name.
- Search and date filters narrow results correctly.
