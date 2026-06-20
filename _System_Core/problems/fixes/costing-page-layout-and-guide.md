---
type: fix
tags: [fix, costing, spa, ux]
---

# Fix: Product Costing — grid rows, guide modal, custom formula lines

## What we changed (2026-06-11 session)

1. **Removed** inline **Honey costing setup** and **Stock prices** panels from `CostingPage` (clutter + horizontal overflow).
2. **Added** header **Guide** button → `CostingGuideModal` (honey / PT / BRC / Jar Cacao math + workflow + custom costs).
3. **Row layout** — shared CSS grid so metric columns align (no `flex-1` gap between name and ₱ columns).
4. **Products list** scrolls inside `max-h-[calc(100dvh-9rem)]`; `PageLayout` main uses `min-w-0 overflow-x-hidden`.
5. **Custom formula lines** — Add formula → **Custom cost** tab (labor, shipping, etc.).

## Dept BOM corrections (same session)

| Product | Was | Now | Migration |
|---------|-----|-----|-----------|
| Jar Cacao | 1 bag = 1 jar | 1 kg → ~4.55 jars @ 220 g; formula **0.22** | `migration_phase22_jar_cacao_220g_bom.sql` |
| Black Rice Coffee | 20 kg bag | 1 kg → 10 pouches @ 100 g; formula **0.1** | `migration_phase23_brc_1kg_bom.sql` |

Constants: `includes/dept_products.php` + `app/src/app/lib/deptProducts.ts` (must stay in sync).

## Implementation patterns

### Shared row grid

Reference: `app/src/app/components/costing/costingRowLayout.ts`, `CostingProductMetrics.tsx`

```tsx
// Honey rows (color bar column)
<div className={costingHoneyRowGridClass}>
  <div className="w-1 ..." /> {/* size accent */}
  <div>{/* label + badges */}</div>
  <CostingProductMetrics ... chevron />
</div>

// Dept / other rows
<div className={costingRowGridClass}>
  <div>{/* product name */}</div>
  <CostingProductMetrics ... />
</div>
```

- **Column headers:** `CostingRowColumnHeaders` once per section (honey variant open, dept card).
- **Do not** use `flex-1` on the label column beside metrics — causes huge whitespace on wide screens.
- Desktop grid: `minmax(6rem,1fr) | 4.25rem | 4.25rem | margin | baseline | chevron`.

### Page structure

Reference: `CostingPage.tsx`

```tsx
<PageLayout actions={<Guide + Refresh />}>
  <div className="flex flex-col min-h-0 min-w-0">
    {/* Products header + New Product */}
    <div className="max-h-[calc(100dvh-9rem)] overflow-y-auto overscroll-contain">
      <CostingHoneyVariantGroups />
      {/* dept card with CostingRowColumnHeaders */}
    </div>
  </div>
  <CostingGuideModal />
</PageLayout>
```

### Guide modal

`CostingGuideModal.tsx` — scrollable `AppModalBody`; math from `deptProducts.ts` + `honeyPricing.ts` (single source, not duplicated prose).

### Add formula line

| Mode | Fields | API |
|------|--------|-----|
| **From stock** | stock picker + qty | `stock_id`, `qty_per_unit` |
| **Custom cost** | name, ₱/unit, qty | `is_custom`, `line_label`, `unit_price`, `qty_per_unit` |

Custom rows: `product_formula.stock_id` NULL, `line_label` set, `price_at_creation` = fixed unit ₱. Current cost uses `price_at_creation` (not stock WAC).

Migration: `migration_phase24_formula_custom_lines.sql`.

## Files

| Role | Path |
|------|------|
| Page | `app/src/app/pages/exec/CostingPage.tsx` |
| Grid / metrics | `app/src/app/components/costing/CostingProductMetrics.tsx`, `costingRowLayout.ts` |
| Honey groups | `app/src/app/components/costing/CostingHoneyVariantGroups.tsx` |
| Guide | `app/src/app/components/costing/CostingGuideModal.tsx` |
| API | `api/v1/costing.php`, `api/v1/_costing_helpers.php` |
| BOM constants | `includes/dept_products.php`, `app/src/app/lib/deptProducts.ts` |

## Deploy checklist (prod)

1. `migration_phase22_jar_cacao_220g_bom.sql`
2. `migration_phase23_brc_1kg_bom.sql`
3. `migration_phase24_formula_custom_lines.sql`
4. `cd app && npm run build`

## Links

- [[problems/records/2026-06-11_costing-session-rollup]]
- [[specs/2026-06-05_jar-cacao-bom-math]]
- [[specs/2026-06-05_brc-bom-math]]
- [[005_SPA_UX_Patterns#Product Costing page]]
