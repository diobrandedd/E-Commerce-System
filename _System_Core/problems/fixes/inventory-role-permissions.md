---
type: fix
tags: [fix, inventory, roles, permissions, spa, api]
---

# Fix: Inventory role permissions — one source in PHP, mirror in SPA

## Rule

**Backend helpers in `material_inventory.php` are canonical.** SPA shows buttons from API `permissions` objects (detail endpoints) or route guards — never invent role logic only in React.

## Inventory surfaces

| Surface | HR | Production manager | Executive |
|---------|----|--------------------|-----------|
| **Packaging Materials** | Stock in lot | Deduct for use | All + set on-hand + pool yield + catalog + full log |
| **Supplies** | Stock in lot; issue/count **requests** | — | Approve requests; set on-hand |
| **Product Costing** | — | — | **Only role** (route + `costing.php` API) |
| **Raw / dept production** | View (where routed) | Produce, BOM preview | All |

## SPA conventions

1. **Page guides** — header **Guide** → `*GuideModal` ([[fixes/page-guide-button-modal]]). Role-specific sections inside modal.
2. **No misleading links** — do not link HR/PM to pages they cannot access (e.g. `/costing`).
3. **Detail modals** — primary actions in modal footer area, not inline in table rows.
4. **Activity log** — executive gets extended history where implemented (packaging: 100 vs 25).

## When adding a new inventory page

1. Define role helpers in `material_inventory.php` (or domain include).
2. Gate API actions; return `permissions` on detail/list if UI needs it.
3. Add route guard in `app/src/app/routes.ts` + sidebar in `Sidebar.tsx`.
4. Add role section to `*GuideModal` if page has a guide.
5. Document in [[005_SPA_UX_Patterns]] and [[010_Product_Theory]].

## Docs

[[fixes/packaging-inventory-roles]] · [[fixes/supplies-hr-lot-inventory]] · [[fixes/page-guide-button-modal]]
