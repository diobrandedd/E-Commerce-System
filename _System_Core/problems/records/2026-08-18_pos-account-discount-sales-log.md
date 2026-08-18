---
date: 2026-08-18
type: problem-record
status: fixed
area: pos-sales
tags: [problem]
---

# Problem: Discount request from POS never appeared; walk-in seller not on Sales

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Cashier used POS, tapped 10%/20% for discount approval, and no request showed on Requests. Walk-in sales on the Sales tab did not show which staff sold, and there was no product filter on recent receipts.

## Cause

→ [[causes/pos-pin-role-discount-queue]]

## Fix

→ [[fixes/pos-account-discount-sales-log]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/pos_unified.php`, `includes/honey_sales_report.php`, `includes/permissions.php` |
| API | `api/v1/pos.php`, `api/v1/sales.php` |
| Schema | `sql/migration_phase65_pos_sold_by_user.sql` |
| SPA | `POSUnifiedPage.tsx`, `SalesPage.tsx`, `Sidebar.tsx`, `routes.ts` |
| Spec | [[specs/2026-08-18_pos-account-discount-sales-log]] |

## Deploy

- Run `sql/migration_phase65_pos_sold_by_user.sql` on the target DB (nullable `sold_by_pin_id` + `sold_by_user_id`)
- Grant **Walk-in POS** (`pos.sell`) in Staff permissions for non-executives
- `cd app && npm run build` + hard-refresh Apache `app/dist/`
- Regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#Runtime schema probes]]
- **Patterns:** [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]]
