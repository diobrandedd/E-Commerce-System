---
date: 2026-09-15
type: fix
area: sales
---

# Fix: Two-sheet Sales Excel export

## Behavior

1. `GET sales.php?action=honey_sold_export` streams PhpSpreadsheet `.xlsx`.
2. Sheet **Shipped**: pack-scan / parcel lines + **RTS resellable** Yes/No (`source=rts` or dept `pool=rts_sellable`).
3. Sheet **Walk-in**: all matching POS checkouts (cap 5,000) + RTS flag from sales log; Total only with `sales.view_amounts`.
4. Period + channel + product + sale_type + receipt filters; channel empties the non-matching sheet.
5. SPA **Export Excel** above the Recent walk-in table via `apiDownload`.
6. Always `require_once vendor/autoload.php` before PhpSpreadsheet (API bootstrap does not load Composer).

## Do not

- Count bare RTS FIFO `honey_sales_log` rows that duplicate bottle-unit pack logs (no `bottle_unit_id` / `pack_session_id` / `online_parcel_id` / `qty_other`).
- Put money columns in the export when the user lacks `sales.view_amounts`.
