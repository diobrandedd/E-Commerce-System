---
date: 2026-09-12
type: problem-record
status: fixed
area: auth-permissions
tags: [problem, honey, permissions, dept]
---

# Problem: Out of scope despite Staff Management honey / dept grants

## Symptom

Staff/TL with honey (or Paragis / BRC / cacao) permissions still saw **Out of scope for your department.** on Raw Honey Production and could not open other departments’ raw pages unless their home `dept_id` matched.

## Cause

`honey_assert_staff_dept_scope()` and `api_honey_require_honey_ops()` required home department = honey (or exact dept match), ignoring granular `user_permissions`. An earlier fix in this area had been reverted in `api/v1/honey.php`.

## Fix

- Honey pipeline lists: allow when `honey.view` / `honey.ops` / `honey.mutate` is granted (no home-dept honey gate).
- Cross-dept targets (`dept` / `slug` / `product_key`): allow home dept **or** matching `dept.*.view` / honey view grants via `honey_staff_may_access_target_dept()`.
- TL honey ops: `honey.ops` alone is enough.
- Role presets: honey pipeline keys only auto-included for home dept honey/csr.

## Files

| Role | Path |
|------|------|
| Primary | `api/v1/honey.php` |
| Presets | `includes/permissions.php` |

## Deploy

- PHP only; user must **log out and back in** (or hard refresh) after permission changes
