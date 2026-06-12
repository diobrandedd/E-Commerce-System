---
type: fix
tags: [fix, packaging, inventory, roles, hr, prod_manager, executive]
---

# Fix: Packaging Materials — role split (HR stock-in / PM deduct / exec all)

## Operational model (do not invert)

| Role | Job on Packaging Materials | API / helper |
|------|---------------------------|--------------|
| **HR** | **Stock in lot** when deliveries arrive — total ₱ paid + qty received | `stock_in_lot` · `material_inventory_role_can_stock_in_packaging()` |
| **Production manager** | **Deduct for use** when packaging goes to production — qty + reason | `packaging_deduct` · `material_packaging_deduct_for_use()` · `material_inventory_role_can_deduct_packaging()` |
| **Executive** | **All** of the above + **set on-hand**, **pool yield**, **add catalog**, extended **activity log** (100 rows) | `set_on_hand`, `set_pool_yield`, `catalog_create` · `material_inventory_role_can_set_packaging_on_hand()` |

**HR does not** deduct or set on-hand directly. **Production manager does not** stock in. **Neither** accesses Product Costing (executive only).

## Per-unit vs pool

| Badge | `production_deduct` | On-hand unit | Who deducts |
|-------|---------------------|--------------|-------------|
| **Per-unit** | 1 | pcs, pouch, bottle | PM via **Deduct for use** (+ may still auto-deduct on dept produce for per-unit — see spec) |
| **Pool** | 0 | **whole rolls** | PM deducts **1 roll** when opened; never fractional rolls |

Pool costing uses `pool_yield_qty` (executive edits yield on detail modal).

## SPA pattern

1. **List** — `PackagingMaterialsPage.tsx`: stats (no mixed-unit totals), search, filters, row → detail modal.
2. **Detail** — `PackagingMaterialDetailModal.tsx`: actions from `packaging_detail.permissions` (not hardcoded props alone).
3. **Modals** — `PackagingStockInLotModal`, `PackagingDeductModal`, `PackagingOnHandSetModal`.
4. **Guide** — `PackagingInventoryGuideModal` via header **Guide** button ([[fixes/page-guide-button-modal]]). Role-specific copy; **no `/costing` links** for HR or prod manager.

## Backend checklist (new packaging actions)

1. Add role helper in `includes/material_inventory.php`.
2. Enforce in `api/v1/materials.php` with `api_require_role()` or helper gate.
3. Return `permissions` object from `packaging_detail` so SPA stays in sync.
4. Stock entries: `lot:` stock-in, `use:` deduct, `count_correction:` executive set on-hand.
5. Run `impact.php` + `generate_all.php`.

## Files

| Layer | Path |
|-------|------|
| Roles / deduct | `includes/material_inventory.php` |
| API | `api/v1/materials.php` |
| SPA page | `app/src/app/pages/inventory/PackagingMaterialsPage.tsx` |
| Detail + guide | `PackagingMaterialDetailModal.tsx`, `PackagingInventoryGuideModal.tsx` |
| Client | `app/src/app/lib/materials.ts` |

## Docs

[[005_SPA_UX_Patterns#Role-based inventory permissions]] · [[010_Product_Theory#Who uses it]] · [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
