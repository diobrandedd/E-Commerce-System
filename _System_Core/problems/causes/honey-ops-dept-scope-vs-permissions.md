---
date: 2026-09-12
type: cause
area: auth-permissions
tags: [cause, honey, permissions, dept]
---

# Cause: Honey pipeline required home dept honey despite honey.* grants

`honey_assert_staff_dept_scope()` blocked `warehouse_list` / `production_list` / `packaging_list` unless the staff/TL `dept_id` resolved to `honey` or `csr`. Separately, `api_honey_require_honey_ops()` blocked TL mutations unless dept was `honey`.

Staff Management can grant `honey.view` / `honey.ops` to accounts whose home department is Paragis or Cacao. The SPA route guard only checks permissions, so those users opened Raw Honey Production and got **Out of scope for your department.**
