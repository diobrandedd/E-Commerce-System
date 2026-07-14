---
type: fix
tags: [fix, inventory, roles, dept-raw, tl]
---

# Fix: Dept TL can Stock in / Pouch / Produce

## Rule

IMS role `tl` (SPA `team_leader`) may run **dept raw** production mutations for their assigned department only:

- Stock in raw bags
- Use raw bags / Pouch (Paragis)
- Produce (BRC / Jar Cacao / Honey Comb)
- Related waste + manual daily production log on those pages

They do **not** get finished-goods `honey_role_can_mutate` (exec + prod_manager). Honey **gallon pipeline** for honey-dept TL is separate: [[fixes/honey-pipeline-tl-ops]].

## Enforcement

- PHP: `honey_role_can_dept_produce()` + `api_honey_require_dept_produce($productKey)` with dept scope for `tl`
- SPA: `canMutateDeptRawProduction()` on `RawDeptMaterialPage` only
