# Granular user permissions

## Problem

Staff could only be assigned coarse roles (executive, prod_manager, hr, tl, staff). There was no way in Staff Management to pick exactly which capabilities each person has (scanning, packing, CSR tools, inventory, etc.). New accounts inherited full role presets with no opt-in flow.

## Cause

Authorization was hardcoded in ~15 PHP `*_role_can_*` helpers, `api_require_role()` calls, SPA route role arrays, and a large role `switch` in `Sidebar.tsx`. No `user_permissions` table existed.

## Fix

- Added `user_permissions` table and [`includes/permissions.php`](../includes/permissions.php) catalog with role presets for backfill.
- `ims_user_can()` / `api_require_permission()` gate API actions; executives bypass.
- `auth.php` `me` returns `permissions[]`; SPA `AuthContext.can()` and route/sidebar filtering.
- Staff Management: **Permissions** modal (shield icon) with **collapsible permission groups**, search, preset apply, clear all.
- New users are created with **zero** permissions; executive is prompted to grant access.
- Phase 2: remaining SPA pages and APIs migrated from `user.role` checks to `can()` / `ims_user_can()` (dashboard, costing, materials, honey corrections, inventory pages, deliveries, RTS, notifications).
- Unchecking Paragis / Cacao / Comb **view** now sticks. `inventory.products.view` / `inventory.raw.view` no longer force those line-view boxes back on.
- `tools/gen_permission_keys.php` generates `app/src/app/lib/permissionKeys.ts` from PHP catalog.

## Files

- `sql/migration_phase64_user_permissions.sql`
- `includes/permissions.php`
- `tools/backfill_user_permissions.php`
- `api/v1/_bootstrap.php`, `api/v1/auth.php`, `api/v1/staff.php`, domain APIs
- `app/src/app/lib/permissions.ts`, `context/AuthContext.tsx`, `components/RequireAuth.tsx`, `components/Sidebar.tsx`, `routes.ts`
- `app/src/app/components/exec/StaffPermissionsModal.tsx`, `pages/exec/StaffManagementPage.tsx`
- `app/src/app/lib/permissionKeys.ts`, `tools/gen_permission_keys.php`
- `api/v1/dashboard.php`, `api/v1/costing.php`, `api/v1/honey.php`, `api/v1/materials.php`, `api/v1/stocks.php`, `api/v1/edit_requests.php`

## Production rollout

1. Run migration (safe anytime).
2. Off-hours: `php tools/backfill_user_permissions.php` then deploy PHP + `app/dist`.
3. Smoke-test one user per role before shift.
