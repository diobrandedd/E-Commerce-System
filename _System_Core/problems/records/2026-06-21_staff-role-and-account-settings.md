---
date: 2026-06-21
type: problem-record
status: fixed
area: auth-roles
tags: [problem, roles, staff, deliveries, inventory, account]
---

# Problem: Staff users had no operational access; no self-service password change

> Expanded the non-CSR `staff` role and added an all-users account page.

## Symptom

Non-CSR `staff` could only reach Stock Input / Daily Count / Edit Requests / AR. The business needed staff to also: record + view deliveries, view online parcels, view RTS records, issue (fetch) packing materials with accountability, and see their own department's raw materials + finished products (read-only). Separately, no user could change their own password from the SPA — `/settings` was executive-only.

## Cause

The `staff` role was intentionally narrow; deliveries/parcels/packing/inventory gates excluded it, and the only Settings page was the executive system-settings page.

## Fix

Additive expansion of the existing `staff` role (no new DB role, no `api_normalize_role` change) + a new `/account` page using the already-open `auth.php?action=change_password`.

- **Deliveries (record + view):** `online_parcels_user_can_ship()` now includes `staff`; void/delete/resolve stay executive-only (`api_deliveries_assert_executive`).
- **Online parcels:** `online_parcels_user_can_access()` allows `staff` unconditionally (non-CSR).
- **Packing materials (fetch, logged):** `material_inventory_role_can_issue_packing()` includes `staff`; issues already record `issued_by_name`.
- **RTS records:** backend `api_rts_role_allowed` already allowed `staff`; added `staff` to SPA `rts`, `rts/records`, `rts/dashboard` guards.
- **Inventory (own-dept, read-only):** `honey_role_can_view()` includes `staff`; new `honey_assert_staff_dept_scope()` in `honey.php` restricts staff to their own department (honey-stage actions require honey dept; dept/slug/product_key params must match). Pricing stays hidden (`honey_pricing_role_can_view_cost` excludes staff). `materials.php` untouched so packaging/supplies stay exec/PM/HR only.
- **SPA:** `invViewGuard` (= invRoles + staff) on honey-stage + `raw-materials/:slug` + products pages; cross-dept aggregate hubs (`raw-materials`, `cacao-brc`) kept exec/PM/HR. Sidebar `staff` nav expanded; per-dept inventory children; **My Account** link for all roles.
- **Account page:** `AccountSettingsPage.tsx` + `lib/account.ts` `changePassword()`; route `/account` (any authenticated user).

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/online_order_parcels.php` (`online_parcels_user_can_access`, `online_parcels_user_can_ship`) |
| Backend | `includes/fulfillment_packing.php` (`material_inventory_role_can_issue_packing`) |
| Backend | `includes/honey_inventory.php` (`honey_role_can_view`) |
| Backend | `api/v1/honey.php` (`honey_assert_staff_dept_scope` + dept helpers in `api_honey_require_view`) |
| SPA | `app/src/app/routes.ts` (`invViewGuard`, `/account`, staff guards) |
| SPA | `app/src/app/components/Sidebar.tsx` (staff nav + My Account) |
| SPA | `app/src/app/pages/AccountSettingsPage.tsx`, `app/src/app/lib/account.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank.

## Links

- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]] — gates use IMS session role (`staff`), not normalized SPA names.
- **Patterns:** [[005_SPA_UX_Patterns#Role-based inventory permissions (mandatory)]]

## AI rules (optional)

When widening a role, gate backend on the raw IMS session role (`staff`/`tl`), keep mutations on their own executive/PM checks, and dept-scope reads at the view entry point. Do not add `staff` to full `material_inventory_role_can_view` — use packaging-only catalog gate in `api_materials_catalog_list` instead.

## Follow-up fixes (2026-06-21)

- **For packing Forbidden:** `PackingMaterialsStagingPage` loads `listMaterials("packaging")` via `materials.php`; staff were blocked by `api_materials_require_view`. Fixed: allow `category=packaging` when `material_inventory_role_can_issue_packing`; strip pricing fields for staff.
- **Verify parcel tiers:** Legacy phase-30 `dept_1_2_units` tier matched all shapes (dead `dept_units_min/max` rule). Fixed: `dept_units` range in `parcel_pack_tier_matches`; `verify.ps1` runs `seed_fulfillment_pack_tiers.php` before regression test.
