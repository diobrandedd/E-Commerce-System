---
date: 2026-06-12
type: fix
area: raw-honey-inventory
tags: [honey, spa, ux, raw-materials, tabs]
---

# Fix: Raw Honey — Warehouse / Production / Packaging tabs

## Problem

**Raw Materials → Raw Honey** showed `RawHoneySummaryPage`: a read-only warehouse gallon table with a link to “Stock in (Products → Warehouse)”. Users expected the same **three-stage honey workflow** (Warehouse, Production, Packaging) inside Raw Honey.

The full operational pages already existed under **Products → Honey**, so honey was split across two nav paths with no in-page tabs on the raw-materials entry.

## What we changed

1. **`HoneyStageTabs`** — in-page tabs: Warehouse | Production | Packaging on all three stage pages.
2. **Canonical routes** (under Raw Materials):
   - `/inventory/raw-materials/honey/warehouse`
   - `/inventory/raw-materials/honey/production`
   - `/inventory/raw-materials/honey/packaging`
   - `/inventory/raw-materials/honey` → redirect to **warehouse**
3. **Sidebar** — **Raw Honey** is a nested group (Warehouse / Production / Packaging). **Products → Honey** uses the same paths.
4. **Redirects** — `/inventory/products/honey/*` and legacy `/inventory/honey/*` → raw-materials paths.
5. **Page titles** — stage pages use title **Raw Honey** + stage-specific subtitle (replacing “Honey — Warehouse” etc.).

## Files

| Role | Path |
|------|------|
| Tabs | `app/src/app/components/honey/HoneyStageTabs.tsx` |
| Pages | `HoneyWarehousePage.tsx`, `HoneyProductionPage.tsx`, `HoneyPackagingPage.tsx` |
| Routes | `app/src/app/routes.ts` |
| Nav | `app/src/app/components/Sidebar.tsx` |
| Orphan | `RawHoneySummaryPage.tsx` — no longer routed; safe to delete in a cleanup pass |

## Pattern

Tabs sit **below** `PageLayout` header, above stage content:

```tsx
<PageLayout title="Raw Honey" subtitle="Warehouse — …">
  <div className="space-y-4">
    <HoneyStageTabs />
    {/* stage table / actions */}
  </div>
</PageLayout>
```

## Finished honey (Products)

**Total inventory (batch bottled)** — variant/size pie grid — on **Products → Honey** (`HoneyProductInventoryPage`, `HoneyBottledInventoryPanel`). Raw Materials → **Packaging** keeps batch rows, sell, and traceability only.

## Links

- [[005_SPA_UX_Patterns#Raw Honey (three stages)]]
- [[003_Fragile_Edges#Inventory arrangement]]
