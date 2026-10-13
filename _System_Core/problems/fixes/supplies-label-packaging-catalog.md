---
type: fix
tags: [fix]
---

# Fix: Supplies nav = packaging catalog; no department on Add item

## What we changed

- Sidebar shows only **Supplies** → `/inventory/supplies` (PackagingMaterialsPage)
- Old `/inventory/packaging-materials` redirects to `/inventory/supplies`
- Old `SuppliesMaterialsPage` route removed from router
- Add item no longer shows department; server assigns canonical dept
- User-facing copy updated to Supplies

## Verify

1. Inventory nav has one Supplies entry (no Packaging Materials).
2. Open Supplies — packaging catalog, scan stock-out still works.
3. Executive Add item — name + unit only; create succeeds.
4. Old `/inventory/packaging-materials` URL redirects to Supplies.

## Related problems

- [[records/2026-10-09_supplies-replaces-packaging-page]]
