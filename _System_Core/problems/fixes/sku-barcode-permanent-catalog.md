---
date: 2026-08-26
area: inventory
---

# Fix: Permanent SKU barcode catalog

Store one short Code128 value per honey variant×size and dept product in `product_sku_barcodes`. Print N copies. Scan flows: SKU → enter qty (aggregate credit/debit); leftover unique 6-char stickers still resolve as qty 1. Never auto-overwrite barcode from selling price.

**2026-09:** Canonical codes are 6-char hex colors (e.g. `F54927` light 350ml). Pre-2026 text codes (`350mlL`, `1LiterD`, …) still scan via `sku_barcode_legacy_aliases()` in `includes/sku_barcodes.php`. Migration: `sql/migration_phase73_sku_hex_codes.sql`.
