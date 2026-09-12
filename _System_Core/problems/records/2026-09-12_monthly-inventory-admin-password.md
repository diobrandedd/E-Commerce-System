---
date: 2026-09-12
type: problem-record
status: fixed
area: inventory
tags: [problem, monthly-inventory, security]
---

# Problem: Monthly inventory absolute overrides needed admin password

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Monthly inventory can rewrite absolute on-hand / lot quantities. After briefly hiding the module in favor of Check inventory, ops needed it back under executive permission, with an extra administrator password (changeable in System Settings) before any stock-level override.

## Cause

→ [[causes/monthly-inventory-absolute-override]]

## Fix

→ [[fixes/monthly-inventory-admin-password]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/admin_override_password.php` |
| API | `api/v1/settings.php`, `honey.php`, `materials.php`, `rts.php` |
| SPA | `SettingsPage.tsx`, `MonthlyInventoryPage.tsx`, `AdminOverridePasswordModal.tsx` |
| Related | [[generated/modules/app_settings]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank
- Executive must **set** administrator password in System Settings before first Monthly override

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
