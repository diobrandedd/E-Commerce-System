---
date: 2026-11-26
type: problem-record
status: fixed
area: approvals
tags: [problem, hr, approvals, inventory-check, packaging, leaf-pool]
---

# Problem: Administrator password blocked shared override edits

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Check Inventory confirm, executive leaf-pool edits, and Packaging stock-in undo all required a shared administrator password from Settings. That password was awkward to rotate, not auditable as a person, and could not notify anyone when a sensitive edit was requested.

## Cause

→ [[causes/admin-override-password-gate]]

## Fix

→ [[fixes/hr-manager-approvals]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/override_approvals.php`, `includes/mailer.php`, `api/v1/approvals.php` |
| Migration | `sql/migration_phase100_override_approvals.sql` |
| APIs | `inventory_check.php`, `honey.php` (`dept_raw_executive_set`), `bottle_barcodes.php` (`packaging_scan_line_undo`), `settings.php`, `auth.php` |
| SPA | `ApprovalsPage.tsx`, `OverrideConfirmModal.tsx`, `SettingsPage.tsx`, Inventory Check / leaf pool / Packaging undo |
| Removed | `includes/admin_override_password.php`, `AdminOverridePasswordModal.tsx` |
| Related | [[generated/modules/override_approvals]] (after regenerate) |

## Deploy

- Apply **phase 100** on production before deploy (human).
- `composer install` (PHPMailer).
- `cd app && npm run build` + hard refresh; regenerate memory bank.
- Set HR manager + Gmail SMTP in System Settings; send test email.

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
- **Plan:** [[plans/archive/2026-11-26_hr-manager-approvals_plan]]
- **Spec:** [[specs/2026-11-26_hr-manager-approvals]]

## AI rules (optional)

Do not reintroduce `api_require_admin_override_password` for these three actions. Approval must require a logged-in HR manager; never approve from the email link alone. Re-validate payloads on approve; packaging undo must compare `expected_qty`.
