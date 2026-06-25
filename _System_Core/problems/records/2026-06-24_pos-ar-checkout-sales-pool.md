---
date: 2026-06-24
type: problem-record
status: fixed
area: pos-ar-sales
tags: [problem, pos, ar, sales]
---

# Problem: POS could not record AR; Sales pool excluded AR checkouts

## Symptom

Cashiers had no Accounts Receivable path at POS. Executive Sales page only showed cash regular/discount checkouts. AR ledger lacked debtor mobile and executive unpaid toggle.

## Cause

Phase 5 `ar_credits` was IMS-only (`take` action) with no `pos_sales` link. `honey_sales_report` filtered only non-AR cash sales.

## Fix

Migration phase 50: `pos_sales.is_ar`, `ar_credits` debtor + `pos_sale_id`. `honey_pos_ar_checkout` / `dept_pos_ar_checkout` deduct inventory and link ledger. Sales page **AR only** red pill + type badge.

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase50_ar_debtor_pos.sql`, `tools/apply_migration_phase50.php` |
| Backend | `includes/ar_credits.php`, `includes/honey_pos.php`, `includes/dept_pos.php`, `includes/honey_sales_report.php` |
| API | `api/v1/ar.php`, `api/v1/pos.php`, `api/v1/sales.php` |
| SPA | `POSARCheckoutPanel.tsx`, `StaffDebtorCombobox.tsx`, `POSHoneyPage.tsx`, `POSDeptPage.tsx`, `SalesPage.tsx`, `ARCreditsPage.tsx` |
| Spec | `_System_Core/specs/2026-06-24_pos-ar-checkout.md` |

## Deploy

- Run `php tools/apply_migration_phase50.php` on prod
- `cd app && npm run build` + hard refresh Apache `app/dist/`

## Links

- **Spec:** [[specs/2026-06-24_pos-ar-checkout]]

## AI rules

AR checkouts must always create `pos_sales` + `ar_credits` in one transaction; Sales `sale_type=ar` filters `is_ar=1`.
