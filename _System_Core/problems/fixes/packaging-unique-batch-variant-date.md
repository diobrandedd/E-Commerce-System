---
date: 2026-09-10
type: fix
area: honey-packaging
---

# Fix: Unique packaging lots by bottling date

1. Migration phase77: unique `(batch_no, variant, bottling_date)`; drop production one-row unique.
2. Helpers: `honey_fetch_packaging_by_batch_variant_date`, `honey_ensure_packaging_row_for_stock_in`; uniqueness allocator scoped to date.
3. Stock-in / ensure / custom create use today (or picked date). Unique sticker **and** SKU honey stock-in both remap to today’s lot via `honey_ensure_packaging_row_for_stock_in` (picked row = batch identity only).
4. Packaging SPA: Scan date + Bottling date filters; lot labels include date.
