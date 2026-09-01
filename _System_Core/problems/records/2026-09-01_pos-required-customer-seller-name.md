---
date: 2026-09-01
type: problem-record
status: fixed
area: pos
tags: [problem, pos]
---

# Problem: POS checkout allowed walk-in default and inferred seller from session

## Symptom

Checkout modal accepted empty customer name (defaulted to "Walk-in") and did not capture who sold the order — seller was implied from the logged-in POS pin/user.

## Cause

Buyer name normalization defaulted empty strings to "Walk-in". No `seller_name` column or API field; inserts used `sold_by_pin_id` / `sold_by_user_id` only.

## Fix

Require non-empty `buyer_name` and manual `seller_name` on all POS checkout and AR endpoints. Add `pos_sales.seller_name` (migration phase 74). Shared helpers `pos_normalize_buyer_name`, `pos_normalize_seller_name`, `pos_sales_seller_insert`. SPA checkout modals use `POSCheckoutIdentityFields` with validation gating pay buttons.

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase74_pos_seller_name.sql` |
| Helpers | `includes/pos_sales_helpers.php` |
| API | `api/v1/pos.php` |
| POS includes | `includes/pos_unified.php`, `includes/honey_pos.php`, `includes/dept_pos.php` |
| Reports | `includes/honey_sales_report.php` |
| SPA fields | `app/src/app/components/pos/POSCheckoutIdentityFields.tsx` |
| SPA pages | `POSUnifiedPage.tsx`, `POSHoneyPage.tsx`, `POSDeptPage.tsx`, `POSPage.tsx` |
| AR panel | `app/src/app/components/pos/POSARCheckoutPanel.tsx` |

## Deploy

- Run `migration_phase74_pos_seller_name.sql` on DB
- `cd app && npm run build` + hard refresh

## Links

- **Fragile edges:** [[003_Fragile_Edges#POS]]

## AI rules

Do not default buyer to "Walk-in" or auto-fill seller from session — both must be explicit checkout inputs; audit columns remain for terminal identity only.
