---
date: 2026-09-12
type: problem-record
status: fixed
area: auth-permissions
tags: [problem, honey, permissions, dept]
---

# Problem: Raw Honey “Out of scope for your department” after granting honey permissions

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

Staff/TL opened Raw Honey → Production (or Warehouse) after Honey permissions were turned on in Staff Management, but the page showed a red banner: **Out of scope for your department.** Retry loading failed the same way.

## Cause

→ [[causes/honey-ops-dept-scope-vs-permissions]]

## Fix

→ [[fixes/honey-ops-honor-permissions]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `api/v1/honey.php` (`honey_assert_staff_dept_scope`, `api_honey_require_honey_ops`) |
| Presets | `includes/permissions.php` (`ims_preset_honey_pipeline`) |
| Docs | [[003_Fragile_Edges#IMS role vs SPA role (API gates)]] |

## Deploy

- PHP only + hard refresh (no SPA rebuild required for this fix)
- Existing users keep their current `user_permissions` rows; re-Apply preset only if you want to strip honey keys from non-honey TLs

## Links

- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]
- Related: [[records/2026-08-18_granular-user-permissions]] · [[records/2026-06-21_staff-role-and-account-settings]]
