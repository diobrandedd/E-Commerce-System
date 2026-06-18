---
status: shipped
created: 2026-06-18
area: honey-comb, costing, production
tags: [honey_comb, production-packaging, jar, sticker, dept-product]
---

# Honey Comb — production packaging (manufacturing BOM)

> **Two layers:** Manufacturing (this spec) vs parcel ship materials ([[specs/2026-06-18_costing-parcel-packaging-session]] Excel row 13 → `honey_comb_solo`).

## Product

| Field | Value |
|-------|--------|
| `dept_product_key` | `honey_comb` |
| Department | `honey` |
| Sell unit | `pcs` (1 retail jar) |
| Costing product | **Honey Comb** (single SKU, no variant matrix) |

## Manufacturing BOM (per jar)

| Stock | Qty | Notes |
|-------|-----|--------|
| `Honey comb — raw` | 0.5 bag | 1 kg bag → **500 g** per jar (default yield; change `HONEY_COMB_GRAMS_PER_JAR` if ops differs) |
| `Honey comb jar packaging` | 1 pcs | Jar **includes cap** — not `Jar Cacao jar packaging` |
| `Honey comb sticker` | 1 pcs | Applied at pack time |

## Parcel layer (unchanged)

Excel row 13 `HONEY COMB` → `honey_comb_solo`: cushion piece, cardboard half, bubble 17+13cm. Deducted at **ship**, not in product formula.

**Landed reference** = manufacturing COGS + `honey_comb_solo` typical pack.

## Ops flows

1. **Supplier Master** — category `honey_comb`, ₱/bag on raw comb.
2. **Raw Materials** — `/inventory/raw-materials/honey-comb` — stock in bags, **Produce** jars.
3. **Finished** — `/inventory/products/honey-comb` — on-hand sellable jars.
4. **Costing** — Honey filter → Honey Comb section; formula + parcel reference.
5. **POS** — `/pos/honey-comb` walk-in checkout.
6. **Online** — RTS `jar_honey_comb`; parcel parser `product_key: honey_comb` → ship deduct.

## Migration

`sql/migration_phase42_honey_comb_product.sql`  
`sql/migration_phase43_suppliers_honey_comb_category.sql` — extend `suppliers.category` ENUM

## Constants (PHP + TS)

`HONEY_COMB_GRAMS_PER_BAG = 1000`, `HONEY_COMB_GRAMS_PER_JAR = 500` in `dept_products.php` / `deptProducts.ts`.
