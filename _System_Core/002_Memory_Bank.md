# Project Change Log & Memory Bank

> **Timeline index.** New entries link to [[problems/000_Problem_Index|Problem records]] (graph nodes: Problem → Cause → Fix → Files). Say **"save to memory bank"** in Cursor after chat-driven fixes.

## 📅 Recent Log Entries

### 2026-09-24 — Partner sold list: our parcels in their Excel
**Change:** Default direction checks our Online Parcels against their sold list; export our list with Exists. Toggle keeps their→ours. → [[problems/records/2026-09-24_partner-sold-ours-in-theirs]]

### 2026-09-24 — Partner sold list: Exists vs Not found (any status)
**Change:** Green/red = parcel exists for partner (packing counts); Exists column on export; blank-row scan fixed; Order No float + Tracking Number match. → [[problems/records/2026-09-24_partner-sold-exists-not-shipped]]

### 2026-09-18 — Sales Excel Today sheets use day label not week
**Change:** Period Today → tabs `SE 18-shp` / `wlk` and `Period: Today` (was misleading `SE 14-20` week band). → [[fixes/sales-excel-weekly-named-sheets]]

### 2026-09-18 — Sales Excel Today Summary + per-sheet mini breakdown
**Change:** Today/week/month exports use `Summary` with `Period: Today` (not Sep monthly). Each shp/wlk sheet opens with by size/variant/variant×size then detail. → [[fixes/sales-excel-weekly-named-sheets]] · [[problems/records/2026-09-18_sales-excel-today-summary-period]]

### 2026-09-18 — Sales Excel monthly summary = size + variant + variant×size
**Change:** Each `Sep Summary` / `Aug Summary` sheet now mirrors the old header: by bottle/size, by variant, by variant × size for shipped and walk-in (not totals-only). → [[fixes/sales-excel-weekly-named-sheets]] · [[problems/records/2026-09-18_sales-excel-chunked-export]]

### 2026-09-18 — Sales Excel weekly named sheets
**Change:** Export one **monthly** summary per calendar month (`Sep Summary`) + Mon–Sun `SE 14-20-shp` / `wlk` (not one all-time Summary). → [[fixes/sales-excel-weekly-named-sheets]] · [[problems/records/2026-09-18_sales-excel-chunked-export]]

### 2026-09-18 — Sales Excel chunked multi-sheet export
**Change:** Export uses Summary + Shipped/Walk-in sheets of 5k rows each (100k absolute cap) so monthly volume can export. → [[problems/records/2026-09-18_sales-excel-chunked-export]]

### 2026-09-18 — Rebuild finished honey pools from stickers (prod recovery)
**Change:** Phase 86 SQL restores empty POOL `current_*` from `honey_bottle_units` still in packaging; re-links stickers to POOL. SKU packs not included. → [[problems/records/2026-09-17_honey-packaging-variant-pools]]

### 2026-09-18 — Check Inventory: scan → qty (no batch/date)
**Change:** Batch/bottling no longer required. SKU = scan then quantity; unique stickers auto +1; upsert by code (phase85). → [[problems/records/2026-09-18_inventory-check-no-batch-docs]]

### 2026-09-18 — Production gallon max resets when empty
**Change:** Empty/overdrawn production lots clear `initial_qty`; next stock-out starts a fresh “of X gal”. Phase 80 cleanup. → [[problems/records/2026-09-18_honey-production-reset-initial-on-empty]]

### 2026-09-18 — Executive Edit logs only real changes
**Change:** Finished honey `executive_edit` skips unchanged sizes/variant (no more `21→21` noise in Edit Logs). → [[problems/records/2026-09-17_honey-packaging-stocked-in-report]]

### 2026-09-18 — Finished honey Edit Logs modal
**Change:** Products → Honey “Edit logs” opens an in-page modal (packaging executive_edit rows + reason); no longer navigates to Monthly Inventory. → [[problems/records/2026-09-17_honey-packaging-stocked-in-report]]

### 2026-09-18 — Reason-required executive Edit (finished honey)
**Change:** Products → Honey Edit requires a reason (≥5 chars); stored on `inventory_edit_log.reason` (phase84). Edit Logs show Reason. Packaging stocked-in report stays append-only. → [[problems/records/2026-09-17_honey-packaging-stocked-in-report]]

### 2026-09-18 — Executive Edit restored on Products → Honey
**Change:** After Packaging became a stocked-in report, executive Edit for finished bottle pools was missing. Restored Edit on Products → Honey (Light/Amber/Dark POOL `current_*`); Packaging stays report-only. → [[problems/records/2026-09-17_honey-packaging-stocked-in-report]]

### 2026-09-18 — Check Inventory: Complete ≠ stock update
**Change:** Complete only closes the private audit into history. Confirm and update inventory is limited to History → Compare selected for `inventory.check.view_all` (admin password). API `finalize` no longer applies stock from an open session. → [[problems/records/2026-09-11_inventory-check-private-sessions]]

### 2026-09-17 — Packaging stage is stocked-in report
**Change:** Raw Honey → Packaging shows gross scanned-in by date (not live on-hand); Products → Honey remains finished on hand. → [[problems/records/2026-09-17_honey-packaging-stocked-in-report]]

### 2026-09-17 — Honey packaging variant pools + production rows
**Change:** Finished honey on-hand = Light/Amber/Dark POOL rows; stock-in = barcode+qty with FIFO gallon deduct; Packaging cards + Production variant expand. Phase 79 fold. → [[problems/records/2026-09-17_honey-packaging-variant-pools]]

### 2026-09-17 — Confirmed / merged audit history
**Change:** History lists stock-applied compare runs (incl. merges) with mismatch detail; session Comparison run picker. → [[problems/records/2026-09-17_inventory-check-applied-run-history]]

### 2026-09-16 — Sales Excel sheet summaries are channel-only
**Change:** Shipped sheet summary shows only shipped; Walk-in sheet only walk-in (no side-by-side). → [[problems/records/2026-09-16_sales-export-sheet-channel-only]]

### 2026-09-16 — Sales shipped includes dept SKU packs
**Change:** Dept product cards + Excel shipped sheet count unique stickers **and** confirmed SKU pack qty (pt/brc/cacao/comb); honey already did via sales log. → [[problems/records/2026-09-16_sales-dept-shipped-include-sku]]

### 2026-09-16 — Sales export Items lists products
**Change:** Walk-in keeps one row per checkout; **Items** = product names, **Qty** = matching qtys (`Amber 1L; Dark…` / `1; 2`) in UI + Excel. → [[problems/records/2026-09-16_sales-export-items-detail]]

### 2026-09-16 — Honey Packaging shows on-hand (Edit reflects)
**Change:** Packaging table/totals use `current_*`; Edit updates visible inventory; scanned history kept as secondary. → [[problems/records/2026-09-16_honey-packaging-show-on-hand]]

### 2026-09-16 — Executive edit PT/BRC/Cacao raw pool grams
**Change:** Executives set leaf pool remaining grams on raw materials pages (`dept_raw_executive_set`). → [[problems/records/2026-09-16_dept-raw-executive-grams]]

### 2026-09-16 — Honey production lot active + scan filter
**Change:** Executives Edit lot (deactivate + set/adjust gallons); scan Production lot select hides OOS/inactive (`is_active`, phase 83). → [[problems/records/2026-09-16_honey-production-lot-active]]

### 2026-09-16 — Honey production lot refill across days
**Change:** Warehouse→production stock-outs refill one lot per supplier+arrival+variant (not a new row per stock-out day); folded existing AA091226/EJ052526 splits. → [[problems/records/2026-09-16_honey-production-lot-refill]]

### 2026-09-15 — Honey production daily lot merge
**Change:** Manual + scan warehouse stock-out upsert one Production lot per supplier/arrival/variant/day; `production_list` no packaging fan-out; optional consolidate tool. → [[problems/records/2026-09-15_honey-production-lot-merge]]
**Superseded for cross-day:** later days now refill the same lot — see 2026-09-16 entry above.

### 2026-09-15 — Sales Excel export (Shipped + Walk-in)
**Change:** Export button on Sales downloads two-sheet `.xlsx` (pack-scan with RTS resellable + Packed by + walk-in checkouts). Header/page totals include packaging + counted RTS scans (not packaging-only 410). → [[problems/records/2026-09-15_sales-excel-export]]

### 2026-09-15 — Raw stock-in total kilos (PT / BRC / Cacao)
**Change:** Paragis, BRC, Jar Cacao stock-in is total kg (`stock_in_unit=kg`); UI/pricing ₱/kg; Honey Comb unchanged. → [[problems/records/2026-09-15_raw-stock-in-kilos]]

### 2026-09-14 — Remove packaging-page bottle stock-in scanner
**Change:** Dropped Raw Honey Packaging “Scan bottle stock in” button/modal; bottle stock-in stays on Production. → `HoneyPackagingPage.tsx`

### 2026-09-14 — Honey packaging deduct Settings toggle
**Change:** `honey.packaging_deduct` (default on) — turn off to stock-in bottles without deducting empty bottles/stickers from Packaging Materials. → [[problems/records/2026-09-14_honey-packaging-deduct-toggle]]

### 2026-09-12 — Check inventory Confirm updates stock
**Change:** Confirm and update inventory syncs finished stock to audited (+/− variance), voids missing scoped uniques, keeps variance history; admin password required. → [[problems/records/2026-09-12_inventory-check-sync-on-confirm]]

### 2026-09-12 — Monthly inventory + administrator password
**Change:** Restored Monthly inventory (executive); stock overrides require System Settings administrator password. → [[problems/records/2026-09-12_monthly-inventory-admin-password]]

### 2026-09-12 — Hide Monthly inventory (use Check inventory)
**Change:** Removed Monthly inventory from sidebar; `/inventory/monthly-count` redirects to Check inventory; permission key hidden. Page file kept. → `Sidebar.tsx`, `routes.ts`, `includes/permissions.php` — **superseded** by admin-password restore above.

### 2026-09-12 — Permissions trump home department (honey + dept lines)
**Change:** Staff/TL Raw Honey + cross-dept raw access follow Staff Management grants; home dept no longer hard-blocks. → [[problems/records/2026-09-12_honey-out-of-scope-permissions]]

### 2026-09-12 — Packaging scan report uses honey.view
**Change:** Packaging tab scan report/log/options accept `honey.view` (not only `honey.bottle.request`) so “Forbidden.” no longer blocks viewers. → `api/v1/bottle_barcodes.php`, `includes/honey_bottle_barcodes.php`

### 2026-09-12 — Warehouse barcode multi-select print
**Change:** Select multiple supplier barcode requests; one combined A4 sheet with separator banners between codes. → [[problems/records/2026-09-12_warehouse-barcode-multi-print]]

### 2026-09-12 — Warehouse barcode labels show code text
**Change:** Plain 8×20 A4 cells (honey warehouse supplier barcodes) now print the human-readable code under the bars. → [[problems/records/2026-09-12_warehouse-barcode-code-text]]

### 2026-09-11 — Deliveries photo-only (no CSR / no deduct)
**Change:** Add deliveries Photo tab saves TN + before-shipment photo only; CSR parcel optional; no ship deduct. → [[problems/records/2026-09-11_deliveries-photo-only]]

### 2026-09-11 — Inventory check required batch + bottling docs
**Change:** Scan → required batch/date (docs only); compare expandable batch groups; phase 81 unique on code+batch+date. → [[problems/records/2026-09-11_inventory-check-batch-docs]]

### 2026-09-11 — Inventory check private sessions + scoped compare
**Change:** Per-user private audits; compare only counted lines; optional batch; view_all merge history. → [[problems/records/2026-09-11_inventory-check-private-sessions]]

### 2026-09-10 — Sales view amounts permission
**Change:** `sales.view_amounts` gates ₱ on Sales; API strips money when denied; phase 79 continuity grant. → [[problems/records/2026-09-10_sales-view-amounts]]

### 2026-09-10 — Supplier Master prices Forbidden with Manage suppliers
**Change:** Inline honey/dept purchase price APIs accept `suppliers.manage` (or `honey.executive_edit`). → [[problems/records/2026-09-10_supplier-prices-forbidden-manage]]

### 2026-09-10 — Check Inventory scan-only (no batch)
**Change:** Drop required batch on Add-to-list; honey compare by variant+size finished on-hand; 500ms scan cooldown + item success popup. → [[problems/records/2026-09-10_inventory-check-scan-only]]

### 2026-09-10 — Bag-pool deduct Settings toggles
**Change:** Executive can turn PT/BRC/Cacao raw bag deduct on finished stock-in on/off via Settings BOM cards. → [[problems/records/2026-09-10_bag-pool-deduct-toggle]]

### 2026-09-10 — Honey lot gallon progress + production overdraw
**Change:** Per-row gallon progress on Production/Warehouse; bottle scan warns but allows negative production gallons. → [[problems/records/2026-09-10_honey-lot-gallon-progress]]

### 2026-09-10 — Jar Cacao bag pool → jar scan
**Change:** Cacao bag pool + 220 g (settings) jar/SKU deduct; progress strip jar labels; Settings BOM grams/jar + grams/bag. → [[problems/records/2026-09-10_jar-cacao-bag-pool-jar-scan]]

### 2026-09-10 — BRC bag pool → pouch scan
**Change:** Black Rice bag pool + 100 g (settings) pouch/SKU deduct; shared progress strip; Settings BOM grams/pouch + grams/bag. → [[problems/records/2026-09-10_brc-bag-pool-pouch-scan]]

### 2026-09-10 — stock_entries.qty pouch rounding
**Change:** Widen `stock_entries.qty` to DECIMAL(14,6) so Paragis 0.048-bag (48 g) pouch deducts no longer round to 0.05 (50 g). → [[problems/records/2026-09-10_stock-entries-qty-pouch-round]]

### 2026-09-10 — Paragis bag pool → pouch scan
**Change:** Skip WIP; stock bags into a leaf pool; pouch/SKU scans deduct Settings BOM grams (allow negative with warning); bag-pool progress UI + Settings BOM fields. → [[problems/records/2026-09-10_paragis-bag-pool-pouch-scan]]

### 2026-09-10 — SKU stock-in uses today’s bottling lot
**Change:** Non-unique honey SKU stock-in remaps the picked packaging batch to today’s `(batch_no, variant, bottling_date)` row (same as unique stickers). → [[problems/records/2026-09-10_sku-stock-in-today-bottling-date]]

### 2026-09-10 — Packaging lots unique by bottling date
**Change:** Honey packaging uniqueness is now `(batch_no, variant, bottling_date)`; stock-in credits/creates today’s lot; Packaging filters by scan date and bottling date. Run phase77. → [[problems/records/2026-09-10_packaging-bottling-date-unique]]

### 2026-09-10 — Check inventory history detail
**Change:** Audit history rows open a View modal with Counted summary, saved Comparison, and Scan log (plus per-product Log). → [[problems/records/2026-09-10_inventory-check-history-detail]]

### 2026-09-10 — Pack scan editable SKU qty
**Change:** Draft Pack scan sessions can edit absolute quantity on non-unique (SKU) lines via `pack_set_item_qty`; unique stickers remain remove-only. Session card shows summed `total_qty`. → [[problems/records/2026-09-10_pack-scan-editable-sku-qty]]

### 2026-09-09 — Raw materials mobile and guidance fix
**Change:** Paragis now clearly treats barcode stock-in as finished-inventory-only (no raw/WIP/packaging deduct), with scanning primary on phones and secondary tools in an actions modal. Raw Honey Warehouse, Production, and Packaging gained phone cards, semantic actions, accessible dialogs/search, retryable errors, and a fluid reduced-motion-safe stage handoff covered by desktop/mobile Playwright. → [[problems/records/2026-09-09_raw-materials-ui-fix]]

### 2026-09-08 — RTS stock-in review session
**Change:** RTS stock-in now stages continuous scans in an editable owned draft; users can change SKU qty, remove lines, cancel without mutation, or confirm atomically. RTS camera uses presentation-edge repeats with no time cooldown. Run phase 76. → [[problems/records/2026-09-08_rts-stock-in-review-session]]

### 2026-09-05 — Inventory audit Add-to-list UX
**Change:** Check Inventory redesigned — Add-to-list modal (scan/qty/batch), editable session list, Complete → compare preview → Go back or finalize to history. → [[problems/records/2026-09-05_inventory-audit-list-ux]]

### 2026-09-04 — Production bottle scan stock-in + ml/gal setting
**Change:** Production scan stock-in requires a gallon lot, credits packaging, deducts production gallons; Settings `honey.ml_per_gallon` (phase 75 `app_settings`). → [[problems/records/2026-09-04_production-bottle-scan-stock-in]]

### 2026-09-04 — Sales history reprint
**Change:** Executive Sales walk-in history expanded row has **Reprint** — rebuilds receipt from `pos_sales` / `pos_receipt_items` and opens Chrome print (no PDF storage).

### 2026-09-04 — POS silent print via GDI driver
**Change:** Silent POS print uses Windows GDI text (`pos_gdi_print.ps1`) to vendor `POS-58` — same driver path as Chrome. RAW ESC/POS was queuing but not moving paper. → [[problems/records/2026-09-03_pos-mobile-receipt-print]]

### 2026-09-04 — Unique pack FIFO fallback
**Change:** Unique sticker pack/POS deduct tries linked batch then FIFO other packaging rows of same variant+size when the linked aggregate is empty. → [[problems/records/2026-09-04_unique-pack-fifo-fallback]]

### 2026-09-04 — Continuous SKU scan (+1 / typed +N)
**Change:** Pack, packaging stock-in, and inventory check accept repeated SKU scans as +1; optional Quantity + Add for bulk. Session/check backends increment instead of replace. → [[problems/records/2026-09-04_sku-continuous-scan]]

### 2026-09-04 — Pack confirm mixed SKU + unique
**Change:** Pack confirm processes unique stickers before SKU qty so FIFO cannot empty a batch a leftover sticker in the same session still needs. → [[problems/records/2026-09-04_pack-confirm-mixed-sku-unique]]

### 2026-09-03 — POS mobile receipt print
**Change:** Silent POS print — ESC/POS via `pos.php?action=thermal_print` + Winspool (no Chrome dialog). Set `pos_thermal_printer` in config.php. → [[problems/records/2026-09-03_pos-mobile-receipt-print]]

### 2026-09-01 — POS required customer + manual seller
**Change:** Checkout and AR require customer name and manually entered seller (`pos_sales.seller_name`); no walk-in default or session-derived seller. Run `migration_phase74_pos_seller_name.sql`. → [[problems/records/2026-09-01_pos-required-customer-seller-name]]

### 2026-09-01 — SKU hex color barcodes + legacy scan
**Change:** Settings + DB use hex SKU codes (e.g. `F54927` light 350ml); pre-2026 text stickers (`350mlL`, `1LiterD`, …) still scan via alias map. Run `migration_phase73_sku_hex_codes.sql`. → [[problems/records/2026-09-01_sku-hex-barcode-legacy-scan]]

### 2026-08-28 — Honey warehouse supplier/date barcodes
**Change:** Added reusable supplier+arrival-date barcode requests and Code128 printing; warehouse scan-out selects the supplier/date/variant, deducts FIFO gallons, logs source events, and aggregates one daily Production row per supplier/date/variant. → [[problems/records/2026-08-28_honey-warehouse-supplier-barcodes]]

### 2026-08-27 — Barcode Settings selling price sync
**Change:** Editable selling price in Barcode Settings; save updates `products.selling_price` (Product Setup / POS) via `sku_barcode_update_selling_price` on bulk save.

### 2026-08-26 — SKU barcodes (one code per product)
**Change:** Permanent SKU catalog + print N copies; scan→qty stock-in/pack/POS/check; leftover uniques kept. Run phase 69. → [[problems/records/2026-08-26_sku-barcode-inventory]]

### 2026-08-26 — SKU barcode SPA (settings + scan qty)
**Change:** Barcode Settings; generate→print_url; POS/check/pack/RTS SKU qty UX; unique leftovers unchanged. → [[problems/records/2026-08-26_sku-barcode-spa]]

### 2026-08-26 — POS return reduces receipt total
**Change:** Cancelled sold restock reverses pos_receipt line + recalc total; Sales heal for prior returns; returned codes listed separately. → [[problems/records/2026-08-26_pos-return-receipt-total]]

### 2026-08-26 — Sales checkout line barcodes
**Change:** Recent walk-in sales expand shows scanned barcodes per receipt line (returned badge if restocked). → [[problems/records/2026-08-26_sales-checkout-line-barcodes]]

### 2026-08-26 — Cancelled restock POS sold + RTS pool
**Change:** Deliveries Cancelled accepts POS walk-in `sold` barcodes; confirm credits packaging/finished or RTS by unit pool; honey sold reverses sale log qty −1. → [[problems/records/2026-08-26_pack-cancel-restock-pos-sold]]

### 2026-08-25 — RTS barcode inventory cutover
**Change:** Sellable RTS barcode-backed: phase 68 hard reset + sync skip, stock_in_scan, pack/POS RTS consume, Sales RTS counts. → [[problems/records/2026-08-25_rts-barcode-inventory-cutover]]

### 2026-08-25 — RTS barcode inventory cutover SPA
**Change:** RTS inventory stock-in scan + gate Use RTS; Record/labels/POS copy for barcode-backed sellable. → [[problems/records/2026-08-25_rts-barcode-inventory-cutover-spa]]

### 2026-08-25 — RTS label job scan progress UI
**Change:** Recent jobs expandable: scanned/generated ratio + barcode list via `job_codes`. → [[problems/records/2026-08-25_rts-barcode-job-scan-progress]]

### 2026-08-25 — Check Inventory (physical stock take)
**Change:** Inventory → Check inventory: manager opens one shared count per day, any staff scans any finished product (read-only, never mutates stock), then exec/prod manager gets the scanned summary with per-row scan log, compare vs system inventory scoped to counted lines, and dated history. Run phase 67. → [[problems/records/2026-08-25_check-inventory-stock-take]]

### 2026-08-21 — RTS label print clipping
**Change:** Continuous RTS barcode print no longer packs past 17 rows or splits mid-grid; banners start on a fresh row. Fixes stacked/clipped barcodes on page 2. → [[problems/records/2026-08-21_rts-barcode-labels]]

### 2026-08-21 — RTS multi-batch barcode labels
**Change:** RTS → Barcode labels: enter many batch numbers + qtys, generate one job, continuous print with section banners, then stock-in via existing scanners (`rts_label` provenance). Run phase 66. → [[problems/records/2026-08-21_rts-barcode-labels]]

### 2026-08-20 — Pack scan Forbidden despite permission
**Change:** Pack scan API now accepts `deliveries.pack_scan` (same key as sidebar/route). Staff with only Pack scan no longer get 403 on page load. → [[problems/records/2026-08-20_pack-scan-forbidden-permission]]

### 2026-08-19 — Pack scan session barcodes + remove
**Change:** Live pack session product rows expand to show scanned barcodes. Trash removes one draft scan (stock still deducted only on Confirm). → [[problems/records/2026-08-19_pack-scan-session-barcode-remove]]

### 2026-08-18 — Account POS, discounts at checkout, Sales seller log
**Change:** POS is permission `pos.sell` (sidebar). 10%/20% complete the sale immediately with the cashier recorded. Sales Recent walk-in sales shows Sold by / Sold to and filters by product. Run phase 65. → [[problems/records/2026-08-18_pos-account-discount-sales-log]]

### 2026-08-18 — Phone keyboard during camera scan
**Change:** Camera scan no longer opens Android Gboard. Scan fields stay `inputMode=none` unless tapped to type; camera modals blur the wedge input. → [[problems/records/2026-08-18_scan-camera-software-keyboard]]

### 2026-08-18 — Windows toast on barcode requests
**Change:** Open Pyx tab sends a Windows notification when pending honey bottle or pouch/jar barcode requests increase. Turn on via banner or My Account. → [[problems/records/2026-08-18_barcode-request-desktop-notify]]

### 2026-08-18 — Product-line view checkboxes stick after save
**Change:** Unchecking View Paragis / BRC / Cacao / Honey Comb is no longer undone by “View finished products” / “View raw materials”. Those hub keys no longer imply every product line. → [[problems/records/2026-08-18_granular-user-permissions]]

### 2026-08-18 — Permissions actually show in the sidebar
**Change:** Sidebar no longer uses a fixed CSR/staff menu. Granted permission keys appear as nav items (e.g. Leak / damage for CSR TL). Must **Save permissions**, then that user hard-refreshes. → [[problems/records/2026-08-18_granular-user-permissions]]

### 2026-08-18 — Leak/damage camera modal close
**Change:** Camera scanner honors close (unmount + Close button). Leak/damage and unified POS were leaving it mounted. → [[problems/records/2026-08-18_leak-damage-unified-pos-sales-dates]]

### 2026-08-18 — Leak/damage in Inventory sidebar
**Change:** `Leak / damage` is an Inventory nav item (`/inventory/leak-damage`) plus a Honey Packaging shortcut. → [[problems/records/2026-08-18_leak-damage-unified-pos-sales-dates]]

### 2026-08-18 — Sidebar barcode request badges
**Change:** Red count pills on Inventory / Raw Materials for pending bottle and pouch/jar barcode requests (Paragis, BRC, Cacao, Honey Comb). Requests nav badge for PM/HR. → [[problems/records/2026-08-18_sidebar-barcode-request-badges]]

### 2026-08-18 — Granular user permissions
**Change:** Per-user permission checkboxes in Staff Management (collapsible groups, shield icon). Product lines are separate groups: Honey, Paragis, Black Rice Coffee, Jar Cacao, Honey Comb. PHP API + SPA routes/sidebar enforce `can()`/`ims_user_can()`. New accounts default to zero permissions; backfill preserves existing staff. → [[problems/records/2026-08-18_granular-user-permissions]]

### 2026-08-18 — Leak/damage scan, Sales dates, unified POS
**Change:** Unified leak/damage scan for all sticker products (Leaked/Damaged reason, phase 63 log). Sales page adds Pick date + Custom range. POS at `/pos` is scan-first with auto product detect and manual line prices; mixed honey/dept checkout. → [[problems/records/2026-08-18_leak-damage-unified-pos-sales-dates]]

### 2026-08-15 — Day total counters on scan-in reports
**Change:** Selected-day scan reports show a total counter — one scanned total for Paragis / BRC / Cacao / Comb, and a per-variant list (with size breakdown) for liquid honey. Hidden on All dates. API fields `scanned_total` / `totals.by_variant`. → [[problems/records/2026-08-15_daily-scan-in-report]]

### 2026-08-15 — Daily gross scan-to-inventory reports
**Change:** Raw Honey → Packaging and the shared Paragis / BRC / Jar Cacao / Honey Comb barcode panels now default to today's gross scan-in report, with historical date and All dates options. Counts come from barcode `scanned_at` and never decrease after pack, POS, defect, RTS, or other deductions; row drilldowns use the same selected date. No schema change. → [[problems/records/2026-08-15_daily-scan-in-report]]

### 2026-08-15 — Jar Cacao finished-jar defect scan + history
**Change:** On Jar Cacao inventory, **Defect** opens a scan + history modal. In-stock stickers → `damaged` + finished qty −1 + `dept_pouch_defect_log` (phase 62). Otherwise **Not in inventory**. Does not touch RTS pools. → [[problems/records/2026-08-15_cacao-defect-scan]]

### 2026-08-15 — Sales page: Shipped channel + dept product units
**Change:** Pack-scan output is labelled **Shipped** (packed for a parcel) instead of "Online sold", and the headline counts total units. New **By product** cards cover honey plus Paragis / BRC / Jar Cacao / Honey Comb — walk-in from POS receipt lines, shipped from `dept_pouch_units` consumed by a pack session (units only, no dept revenue). Checkout list now includes dept-only receipts. No schema change. → [[problems/records/2026-08-15_sales-shipped-and-dept-products]]

### 2026-08-15 — POS barcode dept + cancel restock + RTS barcodes
**Change:** Dept POS sells by sticker only (reject packed/sold; cart remove). Deliveries Cancelled restocks packed honey/dept barcodes with History (phase 61); tracking cancel API → 410. RTS Record optional product barcodes for honey + dept into sellable/damaged pools. → [[problems/records/2026-08-15_pos-barcode-dept-and-cancel-restock]]

### 2026-08-15 — Mixed pack scan (honey + dept products)
**Change:** Pack scan sessions accept honey bottles mixed with Paragis / BRC / Cacao / Honey Comb stickers in any order. Confirm deducts honey packaging and finished dept stock. New table `honey_bottle_pack_session_dept_items` (phase 60). History gains a product filter. → [[problems/records/2026-08-15_mixed-pack-scan-dept]]

### 2026-08-15 — Dept pouch request scan-in history
**Change:** Tap a Paragis / BRC / Cacao / Honey Comb label **request** row to open a modal of each sticker scanned into finished stock (scanner name + time). Read-only API `request_scan_log` by `request_id`; shared `DeptPouchRequestsPanel` so all four products get it. No schema change. → [[problems/records/2026-08-15_dept-pouch-request-scan-log]]

### 2026-08-15 — Pack scan History (who / when / batch)
**Change:** Deliveries → Pack scan gains a **History** tab (read-only). Lists confirmed sessions with date range + batch code filters; expand for by-batch and per-bottle rows. API `pack_session_list` — SELECT only; existing live pack rows unchanged. → [[problems/records/2026-08-15_pack-scan-history]]

### 2026-08-15 — Honey Comb jar barcodes (Packaging section)
**Change:** Added `honey_comb` to `DEPT_POUCH_BARCODE_KEYS`. Finished jars stock in via request → generate/print → scan (no variant/size; scan = finished +1 only). UI lives below liquid-honey Packaging batches with its own request list. Produce retired (`dept_comb_produce` → 410). Raw honey-comb page keeps bag stock-in/waste and points to Packaging. No schema change. → [[problems/records/2026-08-15_honey-comb-barcode-inventory]]

### 2026-08-15 — Label sheets no longer drop/overlap rows across page breaks
**Change:** Printing a batch over 68 labels lost row 17 of each page and stacked it onto row 1 of the next (two barcodes in one cut box). Both label sheets used `grid-template-rows: repeat(17, 1fr)` inside a box pinned to exactly `287mm`; fractional rows get re-solved when a grid fragments, and the exact height left no rounding slack. Now `grid-auto-rows: 16.8mm` with matching `.label` height — 17 rows = 285.6mm, safely under 287mm. Same 68-per-A4 layout. Affects `honey_bottle_label_sheet_html()` and `dept_pouch_label_sheet_html()`. → [[problems/records/2026-08-15_label-sheet-page-break-overlap]]

### 2026-08-15 — Sticker batch date is the day it was generated
**Change:** `honey_bottle_generate_batch()` stamps `date('Y-m-d')` instead of inheriting the packaging row's `bottling_date`, so labels made today no longer verify as a months-old lot date; the packaging row keeps its own date and past batches are unchanged. Custom batch creation no longer lets a trailing MMDDYY in the code override the Bottling date field — the field wins, and the generator's inline date input stays enabled. Dept pouches still parse MMDDYY from `batch_no` (no date field there). → [[problems/records/2026-08-15_honey-batch-date-generation-day]]

### 2026-08-15 — Custom honey batch reuses an existing row instead of erroring
**Change:** Requesting a second size (or a recount) on a batch you already made no longer fails with "Batch … already exists". `honey_bottle_create_custom_packaging()` returns the matching `(batch_no, variant)` row and flags `reused` so the API/SPA say "added to existing batch". Packaging rows stay size-agnostic — one row covers 1L / 350ml / 250ml / gallon, size lives on the label request. Supplier+date path reuses too rather than silently minting `JR063026-2`. → [[problems/records/2026-08-15_honey-custom-batch-reuse]]

### 2026-08-15 — Universal barcode verify (all accounts)
**Change:** New read-only `/verify-barcode` page + `api/v1/barcode_verify.php` lookup for any logged-in IMS user. Resolves honey bottles (supplier, variant, size, batch/scan dates) or dept pouches/jars (Paragis / Black Rice / Cacao). Sidebar link on all non-POS roles. → [[problems/records/2026-08-15_universal-barcode-verify]]

### 2026-08-15 — Packaging row scan-in history
**Change:** Tap a packaging batch row to open a modal listing each scanned sticker with scanner name + `scanned_at` (API `packaging_scan_log` by `packaging_row_id`). Trace search also matches exact custom `batch_no` (e.g. `JR063026`). → [[problems/records/2026-08-15_packaging-scan-log-modal]]

### 2026-08-15 — Mobile header actions no longer clipped
**Change:** `PageLayout` header is now `flex-wrap` with CSS `order` — below `lg` the actions block goes `w-full` onto its own row (title + bell stay on row one) and returns inline at `lg`. Title/subtitle `truncate` inside `min-w-0`. Raw Honey header labels shorten on phones ("Request", "Scan", "Print"). Applies to every page using `PageLayout`. → [[problems/records/2026-08-15_mobile-header-actions-overflow]]

### 2026-08-15 — Honey batch label unique per variant
**Change:** Same human batch code (e.g. `JR063026`) may exist on dark and amber separately. Migration phase59 replaces global `uq_honey_pk_batch_no` with `uq_honey_pk_batch_variant` `(batch_no, variant)`. `honey_unique_packaging_batch_no()` and custom packaging create only collide within the same variant; sticker codes remain globally unique. Inventory still only moves on scan. → [[problems/records/2026-08-15_honey-batch-label-per-variant]]

### 2026-08-15 — Jar Cacao on dept barcode stock-in
**Change:** Added `jar_cacao` to `DEPT_POUCH_BARCODE_KEYS`. Cacao raw page gets Request / Scan / Generate-print (copy says jars); **Produce** retired (`dept_jar_produce` → 410). Honey Comb still uses Produce. No schema change — phase58 `product_key` already covers it. → [[problems/records/2026-08-15_dept-pouch-barcodes-cacao]]

### 2026-08-15 — Pouch barcodes generalized to dept products (Black Rice Coffee)
**Change:** The Paragis-only pipeline is now per-product. Migration phase58 renames `paragis_pouch_*` → `dept_pouch_*` (rows kept) and adds `product_key` defaulting to `pt`; `includes/dept_pouch_barcodes.php` + `api/v1/dept_pouch_barcodes.php` take a product key, gated by `DEPT_POUCH_BARCODE_KEYS = ['pt','brc']`. Black Rice Coffee gets Request / Scan / Generate-print and loses **Produce** (`dept_brc_produce` now 410); jar cacao and honey comb keep Produce. Codes stay unique across products via the shared `dept_pouch_codes_issued` ledger, and scanning a sticker on the wrong product's panel errors instead of crediting the wrong stock. Scan-in still adds finished pouches with no raw/packaging deduct. SPA renames: `paragisPouchBarcodes.ts` → `deptPouchBarcodes.ts`, `ParagisPouchBarcodePanels.tsx` → `dept/DeptPouchBarcodePanels.tsx`, `canParagisPouchBarcodeOps` → `canDeptPouchBarcodeOps`. → [[problems/records/2026-08-15_dept-pouch-barcodes-black-rice]]

### 2026-08-15 — Staff can request Paragis pouch barcodes
**Change:** SPA was gating pouch barcode buttons on `canMutateDeptRawProduction` (TL+ only). Added `canParagisPouchBarcodeOps` (staff + TL + PM + exec) so staff see Request / Scan / Generate-print; bag stock-in stays TL+. API already allowed `staff` via `paragis_pouch_can_ops`. → [[problems/records/2026-08-15_paragis-pouch-barcode-inventory]]

### 2026-08-15 — Paragis Tea pouch barcodes (replace Pouch, no WIP deduct)
**Change:** Dried Paragis uses request → generate/print → scan-in for finished pouches with custom batch codes (e.g. `JR073026`). Units live in `paragis_pouch_*`; scan bumps `dept_inventory_rows` for `pt` without WIP/packaging deducts. Pouch button removed; `dept_pt_pouch` API returns 410. → [[problems/records/2026-08-15_paragis-pouch-barcode-inventory]]

### 2026-08-14 — Custom packaging batch / supplier for bottle labels
**Change:** Request barcodes and Generate/print can create a packaging batch from a free-text supplier name + bottling date (no production lot). Migration phase56 makes `honey_packaging_rows.production_row_id` nullable. API `create_custom_packaging` builds `supplier_code` like warehouse stock-in. Custom `batch_no` is the supplier code alone (initials + MMDDYY, e.g. `JR063026`) — not the doubled `supplier-bottling` form used by production lots. `honey_bottle_unique_batch_no()` now takes the base code and suffixes `-2`… so re-creating the same supplier+date no longer trips the generic SQLSTATE 23000 → "Duplicate value" handler in `_bootstrap.php`. The batch code can also be **typed directly** (`code` param, 3–32 chars A-Z/0-9/dash); a trailing MMDDYY in the typed code sets the bottling date, and an existing code returns a clear "pick it from the dropdown" error. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Bottle camera scanning moved off html5-qrcode to zxing-wasm
**Change:** New `BarcodeCameraScannerModal` + `lib/barcodeDecoder.ts` drive `getUserMedia` directly and decode with the native `BarcodeDetector` when present, else ZXing compiled to WebAssembly (`zxing-wasm`, `tryHarder`). html5-qrcode reads `formatsToSupport`/`experimentalFeatures` from its **constructor**, not the `start()` config, so those options were silently ignored; worse, its native-detector path is unavailable on iOS Safari, leaving the weak JS ZXing port that never decoded Code 128 stickers. Used by pack scan, stock-in, and honey POS; tracking/QR surfaces keep `TrackingQrScannerModal`. `.htaccess` serves `.wasm` as `application/wasm` so streaming instantiation works. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Scanner: never letterbox the video; resolution goes in videoConstraints
**Change:** Scanner `<video>` must stay `object-cover` — html5-qrcode maps the decode region onto the element box, so `object-contain` letterboxing offsets the scan area off the real frame. Resolution (1920×1080 ideal) must be passed as the config's `videoConstraints`; html5-qrcode's first `start()` arg rejects objects with more than one key, which caused a camera retry loop. Start now falls back to library defaults if constraints are refused. Barcode box is ~4:1 (one label) with a "move closer" hint. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Camera could not read 1D bottle barcodes (QR-shaped scan config)
**Change:** `TrackingQrScannerModal` takes `scanMode="barcode"`; `buildScanConfig` uses a wide qrbox, Code 128 format list, native `BarcodeDetector` when available, and continuous autofocus. The old square 240×240 qrbox cropped the ends off linear barcodes so they never decoded. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Scan success/error beeps across bottle + tracking scanners
**Change:** Shared `playScanBeep` (ok / dup / bad) on honey pack, stock-in, honey POS, deliveries ship/hold/rewaybill/cancel, and RTS tracking + bottle barcode entry. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Continuous bottle camera scanning
**Change:** Camera mode stays open for pack scan, stock-in, and honey POS. Each decode shows animated success/duplicate/failure feedback; in-flight locking plus a 2.5-second same-code cooldown prevents repeated submissions from one camera frame. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Bottle scan: 2D imager + camera fallback + printed code text
**Change:** Three scan surfaces (pack scan, stock-in, honey POS) now accept a 2D-imager wedge (types into the field), a camera scan via shared `TrackingQrScannerModal`, or manual entry. Printed labels show the code text under the barcode for manual fallback. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Bottle label sheet sized for camera scanning (68/A4)
**Change:** Label grid is 4×17 (68/A4), barcode 40mm wide with 5mm quiet zones — ~0.4mm bars. Denser grids (6×20) printed bars too thin for phone cameras. Print at 100% scale. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Bottle codes never reused (issued ledger)
**Change:** `honey_bottle_codes_issued` is append-only; generate claims a code there before printing. Void does not free the sticker. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Random 6-char bottle barcodes (letters + digits)
**Change:** Stickers are unique 6-character codes (letters + digits, no 0/O/1/I/L). Supplier + bottling date stay in DB. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Short 6-digit bottle barcodes (supplier + date in DB)
**Change:** Printed Code128 is a global 6-digit lookup (`000042`), not `batch_no-seq`. Supplier code and bottling date stay on the barcode batch / packaging row. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Bottle label generate: exec + CSR only; sidebar badge
**Change:** Generate/print gated to executive and CSR (not honey staff/PM). Red pending-count badge on Inventory → Production (exec) and Honey labels (CSR). → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — Honey Production hosts bottle request/scan/print (no Bottles tab)
**UX:** Removed Edit/To packaging from Production; request + scan + generate/print are header modals. A4 print 24/page (3×8). Packaging shell from production lot without gallon deduct. → [[problems/records/2026-08-14_honey-bottle-barcode-inventory]]

### 2026-08-14 — [[problems/records/2026-08-14_honey-bottle-barcode-inventory|Honey bottle barcode inventory]]
**Shipped:** Per-bottle Code128 units (`migration_phase54`). Request → generate/print → scan stock-in. Pack scan deducts honey; waybill scan is ship-only. POS/RTS scan bottles. Sales report walk-in vs online from `honey_sales_log`.

### 2026-07-25 — Partner June/July seeds use Excel Tracking Number (col H)
**Redo:** `sql/seed_{ian,joven,jono}_{june|july}_2026_partner_orders.sql` — `tracking_number` = waybill from column H when present; `order_no` = Order No. Jan–May unchanged (no TN column).

### 2026-07-25 — Jono 2026 partner months Mar–Jul historical import
**Loaded + SQL:** Mar–Jul 2026 from `SIR JONO (FWELLNESS) ORDERS.xlsx` (`partner_seller=jono`, Pickup Date → ship_date). Jun/Jul use Tracking Number when present; Excel float-corrupted IDs skipped. Files: `sql/seed_jono_{march|april|may|june|july}_2026_partner_orders.sql` (322 parcels total).

### 2026-07-25 — Joven 2026 partner months Mar–Jul historical import
**Loaded + SQL:** Mar–Jul 2026 from `SIR JOVEN FINEST WELLNESS PH ORDERS.xlsx` (`partner_seller=joven`, Pickup Date → ship_date). March sheet early-April pickups kept on April seed only. Jun/Jul use JT Tracking Number when present. Files: `sql/seed_joven_{march|april|may|june|july}_2026_partner_orders.sql` (856 parcels total).

### 2026-07-25 — Ian 2026 partner months Feb–Jul historical import
**Loaded + SQL:** Feb–Jul 2026 from `FINEST HEALTH ORDERS.xlsx` (Pickup Date → ship_date). Files: `sql/seed_ian_{february|march|april|may|june|july}_2026_partner_orders.sql` (+ January already).

### 2026-07-25 — Ian January 2026 partner iPYX historical import
**Loaded:** `FINEST HEALTH ORDERS.xlsx` sheet `JANUARY 2026` → 6,444 shipped Online parcels (`partner_seller=ian`). `ship_date` = each section **Pickup Date** (not month-end). Tracking = Order No. SQL: `sql/seed_ian_january_2026_partner_orders.sql`.

### 2026-07-24 — [[problems/records/2026-07-22_courier-soa-reconcile|Courier SOA multi-file + mixed courier]]
**Updated:** Upload multiple SOA Excels in one run; merge TNs; J&T and SPX together when both present; per-file highlighted downloads.

### 2026-07-22 — [[problems/records/2026-07-22_courier-soa-reconcile|Courier SOA reconcile]]
**Shipped:** Order management → Courier SOA; J&T/SPX Excel vs shipped parcels by ship_date; missing/extra lists; green/red tracking download.

### 2026-07-22 — [[problems/records/2026-07-22_historical-vip-excel-import|Historical VIP Excel → Online parcels]]
**Shipped:** Order management → Import historical orders; Snappy Excel with real Tracking number (e.g. JT…); preview/commit + conflict compare; shipped + month ship_date; no inventory deduct.

### 2026-07-22 — [[problems/records/2026-07-01_partner-sold-list-reconcile|Partner sold list reconcile]]
**Merged onto `v3.8.0`:** Order management → Partner sold list; upload Excel → match shipped parcels → green/red Order No export. (Brought from `v3.7.11` / `f22699e4` without full branch merge.)

### 2026-07-14 — [[problems/records/2026-07-14_rts-use-and-pos-stock-source|Use RTS + POS stock source]]
**Shipped:** Use RTS replaces Deduct/Sell; Used RTS table with Where used (POS|Packaging); POS/AR checkout asks RTS vs Non-RTS. Run `migration_phase53` on prod. Executives can **Edit** / **Add honey** / **Add product** on RTS inventory (set seeds missing rows). Same Edit/Add on **Damaged RTS**; **Used damaged history** from `rts_damaged_use_events` (`damaged_use_log`).

### 2026-07-02 — [[problems/records/2026-07-02_db-port-based-auto-toggle|DB port-based auto toggle]]

### 2026-06-19 — [[problems/records/2026-06-19_vip-arranger-merger|VIP Excel arranger + merger two-tool workflow]]
**Shipped:** VIP order list → carousel → arranged stash; tracking export merge (Parcel Name ↔ Order Number / Customer Reference) → iPYX.

### 2026-06-19 — [[problems/records/2026-06-19_rts-edit-lines-fk-inventory|RTS case edit + leak line placement FK fix]]

### 2026-06-24 — [[problems/records/2026-06-24_pos-ar-checkout-sales-pool|POS AR checkout + Sales pool]]
**Shipped:** POS honey/dept AR checkout (inventory deduct, debtor picker); `pos_sales.is_ar` + linked `ar_credits`; Sales **AR only** red filter pill; `/ar` debtor/mobile + exec paid/unpaid. Run `migration_phase50` on prod.

### 2026-06-19 — [[problems/records/2026-06-19_waybill-stamper-partner-registry|Waybill stamper partner seller + TikTok + parcels registry]]
**Shipped:** Ian/Jono/Joven on Partner stamp; TikTok pipeline; Online Parcels filters/sort/export with order no. Run `migration_phase48` on prod.

### 2026-06-19 — [[problems/fixes/vip-spx-order-remarks-parser-guards|VIP Paragis qty + CSR confirm]]
**Fixed:** `1 x 2 Paragis Tea` no longer becomes qty 1; dept-only VIP rows require ItemLineBuilder confirm before export.

### 2026-06-19 — [[problems/records/2026-06-19_rts-doc-upload-no-preview|RTS Record multi-attach + 512MB video cap]]
**Shipped:** Camera, record, and gallery feed one thumbnail grid (24 max); parallel uploads; video limit raised to ~512MB; `api/.htaccess` raises PHP upload limits — restart Apache after deploy.

### 2026-06-19 — [[problems/records/2026-06-19_rts-leak-damaged-split|RTS leak damaged vs reusable split + damaged pool]]
**Shipped:** Leak Record RTS uses two line lists (`damaged_lines` + `lines`); sync to sellable/damaged pools; `/rts/damaged` + Use damaged (`mix_production`/`scrap`). Run `migration_phase45` + `migration_phase46` on prod.

### 2026-06-19 — [[problems/records/2026-06-19_rts-swapped-leak-disposition-rollout|RTS swapped/leak rollout + registry media UI]]
**Shipped:** disposition migration + role fix + registry thumbs/modal (`RtsCaseMediaUi`). Run `migration_phase44` on prod.

### 2026-06-19 — [[problems/records/2026-06-19_rts-swapped-leak-disposition|RTS swapped/leak disposition + tracking prefill]]
**Shipped:** `case_disposition` (standard/swapped/leak); swapped excludes sellable sync; tracking prefill from Online Orders/Deliveries; `/rts/swapped` and `/rts/leaks` registries; Record RTS wizard with parcel condition control. Run `sql/migration_phase44_rts_case_disposition.sql` on prod.

### 2026-06-21 — [[problems/records/2026-06-21_spx-packing-list-zip-extension|SPX packing list — PHP zip extension]]
**Fixed:** Shopee packing list upload failed with generic "Cannot open packing list" because XAMPP had `extension=zip` commented out (`ZipArchive` missing). Enabled zip in `php.ini`; clearer error in `shopee_spx_packing_list.php`. Apache restart required for web UI.

### 2026-06-21 — [[problems/records/2026-06-21_staff-role-and-account-settings|Staff role expansion + self-service password page]]
**Shipped:** Non-CSR `staff` additively gains record/view deliveries, online parcels, RTS records, packing-material issue (logged), and read-only own-department raw + finished products (`honey.php` dept-scoped, pricing hidden). New all-users `/account` page changes own password via `auth.php?action=change_password`; sidebar **My Account** for every role. CSR roles untouched; no new DB role. Backend gates use IMS session role (`staff`), not normalized names. **Follow-up:** For packing Forbidden fixed (`materials.php` packaging catalog for issuers); verify parcel tiers fixed (seed + `dept_units` matcher); CSR staff Online Orders Forbidden fixed (`waybill_stamper_user_can_access` accepts IMS `staff` in CSR dept).

### 2026-06-19 — Online Orders practice tour (E2E click-through)
**Shipped:** Single end-to-end practice tour on Online Orders — no batch required, fixture UI per tool, click highlighted control to advance. Quick overview spotlight tour retained. Shared `PracticeTap` + `tourClickToContinue` onAdvance.

### 2026-06-19 — [[problems/records/2026-06-19_pos-dept-checkout-discount|Dept POS checkout + discount + compact cart]]
**Fixed:** Checkout modal never opened (`POSModal` missing `open`). Dept products now support 10%/20% executive discount approval like Honey; cart UI compact and centered.

### 2026-06-19 — POS dept categories "product not found in catalog"
**Fixed:** `POSDeptPage` used `useParams().deptKey` but routes were static (`/pos/paragis`) with no param — product key was always empty. Routes now use `:deptKey`; mapping via `posDeptProductKeyFromRoute()`.

### 2026-06-19 — Packing activity log (For packing)
**Shipped:** Staging, take-outs, and damage on one For packing page (3 tab buttons). Legacy `/log` redirects with `?view=`. Default staging table shows all SKUs.

### 2026-06-19 — [[problems/records/2026-06-19_bubble-staging-piece-display|Bubble staging: piece display + damage in pc]]
**Shipped:** Sized bubble (`13/17/22 cm`) For packing table shows **pc** (via `pool_yield_qty`); damage report accepts pieces; issue modal shows roll→pc hint. Internal ledger stays fractional rolls.

### 2026-06-19 — [[problems/records/2026-06-19_role-aware-dashboard|Role-aware dashboard + executive-only financials]]
**Shipped:** Dashboard branches by role; executive view is compact (`max-w-4xl`): fulfillment KPIs, conditional edit-request banner, display-stock headline, financial alerts (no donut charts / stat row on exec home). PM/HR keep full packaging section.

### 2026-06-19 — Waybill stamp: SPX center border (COD zone)
**Shipped:** Restored stamp border; SPX/Shopee stamps centered on buyer/seller band. **Fix:** [[fixes/waybill-stamp-spx-center-border]] · `waybill-stamper/processor.py`

### 2026-06-19 — [[problems/records/2026-06-19_waybill-stamp-text-only|Waybill stamp text-only (no border)]]
**Shipped:** Removed white/red box + border from PDF item stamp — black/red text only (`waybill-stamper/processor.py`). **Fix:** [[fixes/waybill-stamp-text-only-no-border]] · Python only, re-stamp to apply.

### 2026-06-19 — [[problems/records/2026-06-19_vip-spx-parser-pitfalls|VIP/SPX parser + CSR builder session ⭐]]
**Obsidian hub — read before parser/stamper edits.** Combo honey+dept export drop, false `L` from `1 L HONEY`, **LLA**, **250ml**, J&T `3L HONEY(ALL DARK)1 PC 350ML(DARK)`, collapsible scrollable ItemLineBuilder cards. **Fix:** [[fixes/vip-spx-order-remarks-parser-guards]] · **Causes:** [[causes/vip-spx-size-letter-false-variant]] · [[causes/vip-spx-combo-export-drops-honey]] · [[causes/vip-spx-3l-variant-list-incomplete]] · [[causes/vip-spx-jnt-concatenated-350ml]] · **Parent:** [[records/2026-06-19_vip-spx-order-interpretation]] · **Spec:** [[specs/2026-06-19_vip-spx-order-interpretation]].

### 2026-06-19 — [[problems/records/2026-06-19_vip-spx-order-interpretation|VIP/SPX order interpretation + CSR item builder (initial ship)]]
**Shipped:** J&T/SPX column auto-detect (tracking + Item in Parcel), multi-item parse, ItemLineBuilder on VIP/SPX carousels, per-color stock on 2L/3L cards. **Hotfix:** SPX Sheet1 detect + openpyxl warning JSON parse. **Spec:** [[specs/2026-06-19_vip-spx-order-interpretation]].

### 2026-06-18 — [[problems/records/2026-06-18_honey-comb-production-packaging|Honey Comb: manufacturing BOM + supplier category ENUM]]
**Shipped:** Full dept product `honey_comb`; phase 43 adds `honey_comb` to `suppliers.category` ENUM. **Spec:** [[specs/2026-06-18_honey-comb-production-packaging]].

### 2026-06-18 — Parcel recipes: product COGS + landed total per recipe (products + packaging breakdown in modal/table)

### 2026-06-18 — [[problems/records/2026-06-18_foam-cacao-cut-yields-ui|Foam cut yields editable on insulation foam roll (Jar Cacao)]]

### 2026-06-18 — [[specs/2026-06-18_costing-parcel-packaging-session|MASTER: Costing + parcel packaging session knowledge]]
**Canonical Obsidian doc** for this chat arc: Excel → tier_key map, dept buckets (Paragis vs BRC&Cacao), COGS vs fulfillment layers, all migrations 38–41, errors, design choices, file list, deploy checklist. **Read before any costing/parcel tier work.**

### 2026-06-18 — [[problems/records/2026-06-18_costing-dept-paragis-vs-cacao-brc|Costing: Paragis vs BRC & Cacao dept buckets + combo recipes]]
**Shipped:** UI buckets by `tier_key` not DB `family`; combo PT/BRC/Cacao+honey visible under correct dept filter. **Fix:** [[fixes/costing-dept-paragis-cacao-brc-buckets]] · **Spec:** [[specs/2026-06-18_costing-parcel-packaging-session]].

### 2026-06-18 — [[problems/records/2026-06-18_costing-hub-parcel-recipes-table|Costing hub: products + parcel recipes tables]]
**Shipped:** Products table (dept filter, landed columns, detail modal with COGS / ref. packaging / order calc) + Parcel recipes table with tier breakdown. **Fix:** [[fixes/costing-hub-products-and-parcel-recipes]] · **Spec:** [[specs/2026-06-18_costing-parcel-packaging-session]].

### 2026-06-18 — [[problems/records/2026-06-18_insulation-foam-generic-roll|Insulation foam: generic roll, cut on cacao ship]]
**Shipped:** phase 41 generic `Insulation foam roll` warehouse SKU; ship scan auto-deducts roll fraction from staging using recipe cut size (9×12 / 12.5×12 yields). **Fix:** [[fixes/insulation-foam-generic-roll-ship-cut]].

### 2026-06-18 — [[problems/records/2026-06-18_parcel-tier-bought-cushion-pieces|Parcel tiers: bought liter cushions vs roll columns]]
**Shipped:** phase 40 reactivates `Cushion piece - 1/2/3 liter`; tier seed aligned to Excel "customize" column (honey 1L, dept PT/BRC, cacao jars). **Fix:** [[fixes/parcel-tier-bought-liter-cushion-pieces]] · `migration_phase40_custom_cushion_pieces.sql`.

### 2026-06-18 — [[problems/records/2026-06-18_costing-sync-and-sku-cleanup|Costing sync from prices + legacy SKU cleanup]]
**Shipped:** phase 39 deactivates legacy parcel/cushion SKUs; `sync_all_costing_from_prices.php` syncs supplier + packaging WAC → product baseline. **No unused tables** — all 55 referenced in code.

### 2026-06-18 — [[problems/records/2026-06-18_bubble-wrap-staging-damage|Bubble wrap sizing at issue + staging damage]]
**Shipped:** Generic warehouse `Bubble wrap roll`; TL/PM issue assigns 13/17/22 cm to sized staging; `damaged_qty` + damage report API; TL nav + roles; **parcel costing uses one generic roll WAC ÷ yield per width**. **Spec:** [[specs/2026-06-18_bubble-wrap-staging-damage]] · `migration_phase38_bubble_wrap_staging_damage.sql`.

### 2026-06-18 — [[problems/records/2026-06-18_cancelled-parcel-rewaybill-semantics|Cancelled parcels reusable for re-waybill]]
**Shipped:** Cancel no longer reverts inventory; re-waybill accepts `cancelled` + `on_hold`; ship skips deduct when already committed; Deliveries UI copy aligned. **Spec:** [[specs/2026-06-17_fulfillment-packing-staging]].

### 2026-06-17 — [[problems/records/2026-06-17_cushion-roll-main-divider-lad-piece-foam-roll|Cushion main/divider rolls + LAD piece + foam roll yields]]
**Shipped:** phase 37 cushion/foam migration, `column` + `foam_piece` deduct/costing units, Excel-v2 tier reseed, and HR/executive stock-in yield updates. **Fix:** [[fixes/cushion-roll-main-divider-and-foam-roll-yield]].

### 2026-06-17 — [[problems/records/2026-06-17_fulfillment-packing-staging|Fulfillment packing staging + on-hold re-waybill]]
**Shipped:** Warehouse → for-packing issue layer; ship consumes staging; HR price-only; PM stock-in/issue; Deliveries On hold + Re-waybill; CSR TL view on-hold parcels. **Spec:** [[specs/2026-06-17_fulfillment-packing-staging]] · **Fix:** [[fixes/packaging-inventory-roles]].

### 2026-06-17 — [[problems/records/2026-06-17_fulfillment-packaging-recipes|Fulfillment packaging recipes + ship deduct]]
**Shipped:** Spreadsheet-aligned SKUs, 37 parcel recipes, combo-first matcher, fractional roll deduct on ship. **Fix:** [[fixes/fulfillment-packaging-recipes]] · **Spec:** [[specs/2026-06-17_fulfillment-packaging-recipes]].

### 2026-06-17 — Guides + tours: Staff, Suppliers, Online Orders/Parcels, POS
**Shipped:** `StaffManagementGuideModal`, `SuppliersGuideModal`, `OnlineOrdersGuideModal`, `OnlineParcelsGuideModal`, `POSGuideModal` (category/product); matching `tour/guides/*` modules wired on `/staff`, `/suppliers`, `/waybill/online-orders`, `/fulfillment/online-parcels`, `/pos` (+ honey/dept product screens). **Docs:** [[005_SPA_UX_Patterns#Page guide button (mandatory)]].

### 2026-06-17 — [[problems/fixes/page-interactive-tour|Interactive tours on all guide modals]]
**Shipped:** Start tour on every `*GuideModal` — Costing, Packaging, Supplies, Requests, Paragis, BRC/Cacao, Deliveries review. Shared `GuideTourFooter` + `tour/guides/*` step modules.

### 2026-06-17 — [[problems/fixes/app-feedback-motion|App-wide feedback motion kit + mandatory agent rule]]
**Shipped:** `appFeedbackMotion.tsx` (notices, pressables, hovers); global `Button` tap/hover; `AppModal` close press; `.cursor/rules/feedback-animations.mdc` always-on.

### 2026-06-17 — [[problems/records/2026-06-17_deliveries-review-interactive-tour|Deliveries review interactive tour pilot]]
**Shipped:** `driver.js` page tour — Guide modal + **Start tour** on Deliveries review (spotlight, arrows, step text). **Fix:** [[fixes/page-interactive-tour]].

### 2026-06-16 — [[problems/records/2026-06-16_deliveries-cancel-scan-log|Deliveries cancel scan + inventory restore log]]
**Shipped:** scanner `Mark cancelled` flow for `packing` and `shipped` parcels, shipped-deduct auto-revert, and Deliveries Review cancelled-orders log table. **Fix:** [[fixes/deliveries-cancel-scan-revert-and-log]].

### 2026-06-16 — [[problems/records/2026-06-16_inventory-mobile-consistency|Inventory mobile consistency rollout]]
**Shipped:** same phone pattern on Packaging + Supplies pages — compact `Guide + Actions` header, mobile actions modal, stacked card rows (`sm:hidden`) with desktop tables preserved. **Fix:** [[fixes/inventory-mobile-action-modal-and-cards]].

### 2026-06-16 — [[problems/records/2026-06-16_raw-dept-mobile-layout|Raw dept mobile-first layout]]
**Shipped:** `/inventory/raw-materials/*` phone UX fix — compact header controls + `RawActionsModal`, mobile stacked activity cards, desktop tables preserved. **Rule:** mobile-first mandatory in [[005_SPA_UX_Patterns#Golden rules]]. **Fix:** [[fixes/raw-dept-mobile-first-actions-and-lists]].

### 2026-06-05 — [[problems/records/2026-06-05_parcel-pack-ship-bugs|Parcel pack ship bugs rollup]] ⭐
**Read for agents:** 500 (`$pdo` closure) · `???` labels (PowerShell UTF-8 migration) · TBD tier preview · landed-view + tier modal UX. **Causes:** [[causes/parcel-pack-closure-missing-pdo-use]] · [[causes/sql-migration-powershell-utf8-corruption]] · [[causes/parcel-tier-inactive-preview-500]]. **Fixes:** [[fixes/parcel-pack-pdo-closure-500]] · [[fixes/parcel-pack-ascii-labels-migration]] · [[fixes/parcel-pack-landed-view-modal]]. Run **phase31** migration on Windows.

### 2026-06-05 — [[problems/records/2026-06-05_online-parcel-pack-costing|Parcel pack costing (tier-based)]]
**Shipped:** Product Costing **Parcel packaging** tab — composition tiers (`parcel_pack_tiers`), min/typical/max bubble wrap, Item/s preview, landed cost columns. Manufacturing `product_formula` unchanged. **Fix:** [[fixes/online-parcel-pack-costing]] · **Spec:** [[specs/2026-06-05_online-parcel-pack-costing]] · migration `phase30`.

### 2026-06-12 — [[problems/records/2026-06-12_dept-raw-ux-session|Dept raw UX session rollup]] ⭐
**Start here for agents:** centered `PageLayout` · Paragis **Use raw bags** (`dept_pt_use_raw_bags`) · production **Guide** modals on all raw dept pages (Paragis / BRC / Cacao). **Fixes:** [[fixes/page-layout-centered-content]] · [[fixes/paragis-use-raw-bags]] · [[fixes/dept-raw-material-guides]]. **E2E still open** on [[001_System_State]].

### 2026-06-12 — [[fixes/dept-raw-material-guides|Dept raw material Guide modals]]
**Shipped:** `ParagisProductionGuideModal` + `DeptRawProduceGuideModal` (BRC/Cacao); removed inline workflow/yield panels from `RawDeptMaterialPage`. **Pattern:** [[fixes/page-guide-button-modal]].

### 2026-06-12 — [[fixes/paragis-use-raw-bags|Paragis use raw bags (no tea-bag counting)]]
**Shipped:** `dept_pt_use_raw_bags` — staff enter 1 kg bags opened; WIP derived at 500 tea bags/bag. **Spec:** [[specs/2026-06-12_paragis-production-wip]].

### 2026-06-12 — [[fixes/page-layout-centered-content|Centered page content]]
**Shipped:** `PageLayout` centers header + main in `max-w-6xl mx-auto` (`contentWidth` for wide/full). **Docs:** golden rule #9 in [[005_SPA_UX_Patterns]] · `.cursor/rules/spa-ui-design.mdc`.

### 2026-06-12 — Requests hub collapsible sections
**Shipped:** `RequestsHubSection` accordion on `/inventory/requests` — pending badges, hash auto-expand, animated collapse.

### 2026-06-12 — [[fixes/pyx-scrollbars|Global Pyx scrollbars + sidebar dropdown motion]]
**Shipped:** themed slim scrollbars on all `overflow-y-auto`; fade-in on scroll/hover; `SidebarNavCollapse` animations. **Docs:** [[005_SPA_UX_Patterns#Pyx scrollbars (mandatory)]].

### 2026-06-12 — [[fixes/page-layout-fixed-sidebar|Fixed sidebar shell]]
**Shipped:** `PageLayout` `h-dvh overflow-hidden`; sidebar viewport-fixed; only `<main>` scrolls. **Docs:** [[005_SPA_UX_Patterns#Fixed sidebar shell (mandatory)]].

### 2026-06-12 — Requests page empty (material `request_list` filter bug)
**Fixed:** `request_list` read `$_GET['action']` (API router = `request_list`) as `mar.action` filter → zero rows. Use `request_action` query param instead.

### 2026-06-12 — [[problems/records/2026-06-12_supplies-requests-hub|Supplies requests hub UX]]
**Shipped:** `#supply-requests-section` + review modal · Guides on Supplies + Requests · bell `supply_request` notifications · `supply_detail.permissions`. **Pattern:** [[fixes/supplies-hr-lot-inventory]] · [[005_SPA_UX_Patterns#Requests hub]].

### 2026-06-12 — [[problems/records/2026-06-12_packaging-inventory-role-split|Packaging inventory role split + docs]]
**Shipped:** HR stock-in · PM deduct for use · exec all + logs. **Reusable patterns:** [[fixes/packaging-inventory-roles]] · [[fixes/inventory-role-permissions]] · [[fixes/page-guide-button-modal]] · `.cursor/rules/inventory-roles-and-guides.mdc` · [[005_SPA_UX_Patterns#Role-based inventory permissions]].

### 2026-06-12 — Product Costing executive-only
**Fixed:** HR and production manager blocked from `/costing` route, `costing.php` API, and packaging guide links. **Docs:** [[005_SPA_UX_Patterns#Product Costing page]].

### 2026-06-05 — [[problems/fixes/page-guide-button-modal|Page guide = header button + modal]]
**Pattern:** All page guides use muted border **Guide** button in `PageLayout` actions → scrollable `AppModal` (default closed). Never inline collapsible panels. **Live:** `CostingGuideModal`, `PackagingInventoryGuideModal`. **Docs:** [[005_SPA_UX_Patterns#Page guide button (mandatory)]].

### 2026-06-05 — [[problems/records/2026-06-05_packaging-pool-yield-bootstrap|Packaging pool yield + bootstrap]]
**Shipped:** `production_deduct` + `pool_yield_qty`; full packaging/formula seed + 12 honey SKUs; pool costing; honey bottling bottle/sticker deduct; SPA pool badges. **Spec:** [[specs/2026-06-05_packaging-production-deduct]] · `migration_phase27_packaging_bootstrap.sql`.

### 2026-06-12 — [[problems/records/2026-06-12_pt-make-tea-bags-blocked|PT Make tea bags blocked]]
**Fixed:** Stock-in ≠ tea bags; formula packaging (Yarn/sachet) now maps to make_tea_bags stage; workflow help on raw page. **Cause:** [[causes/pt-stock-in-vs-make-tea-bags]] · **Pattern:** [[fixes/dept-production-wip-bom]].

### 2026-06-12 — [[problems/records/2026-06-12_dept-production-wip-bom|Dept production WIP + BOM deduct]]
**Shipped:** Paragis Make tea bags / Pouch with WIP; formula stock-linked deduct (custom excluded); BRC/Jar single produce; `migration_phase26_dept_wip.sql`. **Pattern:** [[fixes/dept-production-wip-bom]] · [[specs/2026-06-12_paragis-production-wip]].

### 2026-06-12 — [[problems/records/2026-06-12_dept-raw-finished-split|Dept raw vs finished inventory]]
**Shipped:** Raw Materials pages (Paragis / Black Rice / Cacao) — stock in, stock out, Produce; Products pages — finished counts only. **Pattern:** [[fixes/dept-raw-vs-finished-inventory]].

### 2026-06-12 — [[problems/records/2026-06-12_raw-honey-stage-tabs|Raw Honey stage tabs]]
**Fixed:** Raw Honey no longer shows read-only summary only — Warehouse / Production / Packaging tabs + routes under `/inventory/raw-materials/honey/{stage}`; sidebar nested Raw Honey; Products → Honey aliases same paths. **Pattern:** [[fixes/raw-honey-stage-tabs]] · [[005_SPA_UX_Patterns#Raw Honey (three stages)]].

### 2026-06-12 — [[problems/records/2026-06-12_supplies-hr-lot-inventory|Supplies HR lot inventory]]
**Shipped:** org-wide supplies pool; lot stock-in + WAC; HR issue requests with requester name; executive approval on Requests; `migration_phase25_supplies_hr_lot.sql`. **Pattern:** [[fixes/supplies-hr-lot-inventory]] · [[005_SPA_UX_Patterns#Supplies inventory pages]].

### 2026-06-11 — [[problems/records/2026-06-11_costing-session-rollup|Product Costing session rollup]]
**Shipped:** Jar Cacao 220 g BOM (phase 22) · BRC 1 kg BOM (phase 23) · Costing UI grid + Guide modal · custom formula lines (phase 24). **Pattern:** [[fixes/costing-page-layout-and-guide]] · [[005_SPA_UX_Patterns#Product Costing page]].

### 2026-06-11 — [[problems/records/2026-06-11_costing-page-ux-refactor|Costing page UX refactor]]
**Fixed:** removed honey setup + stock prices; grid-aligned rows; viewport scroll; Guide button.

### 2026-06-11 — [[problems/records/2026-06-11_costing-custom-formula-lines|Costing custom formula lines]]
**Added:** Labor/shipping etc. via Custom cost tab; `migration_phase24_formula_custom_lines.sql`.

### 2026-06-11 — [[problems/records/2026-06-11_brc-1kg-bom|BRC 1 kg bag BOM]]
**Fixed:** Supplier ₱/bag = 1 kg; 10 pouches/bag @ 100 g; formula raw **0.1** bag/pouch; `migration_phase23_brc_1kg_bom.sql`.

### 2026-06-11 — [[problems/records/2026-06-11_jar-cacao-220g-bom|Jar Cacao 220 g BOM]]
**Fixed:** 1 kg bag → ~4.55 jars @ 220 g; formula raw **0.22** bag/jar; `migration_phase22_jar_cacao_220g_bom.sql`.

### 2026-06-11 — [[problems/records/2026-06-11_packaging-ui-scroll-normalization|Packaging UI scroll normalization]]
**Shipped:** fixed stats + scrollable table body; capped modal activity scroll; HR Requests top-level in sidebar. **Pattern doc:** [[fixes/packaging-inventory-scroll-and-detail-ux]] · [[005_SPA_UX_Patterns#Packaging and materials inventory pages]].

### 2026-06-11 — [[problems/records/2026-06-11_packaging-materials-ui-on-hand|Packaging Materials UI + on-hand roles]]
**Shipped:** clickable row detail modal; executive `set_on_hand`; HR count requests on Requests; phase 21 migration for `set_on_hand` action.

### 2026-06-11 — [[problems/records/2026-06-11_packaging-wac-legacy-price|Packaging WAC legacy price poison]]
**Fixed:** unit cost now derived from lot purchase entries; pre-lot on-hand valued at average lot unit (fixes set_values baseline skew).

### 2026-06-05 — [[problems/records/2026-06-05_packaging-lot-stock-in|Packaging lot stock-in + WAC]]
**Shipped:** exec/HR stock in purchase lots (total ₱ + qty received) on Packaging Materials (`materials.php stock_in_lot`); WAC unit cost in `current_price`; pool `on_hand` adds qty; Costing helper text for per-unit formula qty.

### 2026-06-05 — [[problems/records/2026-06-05_packaging-price-qty|Packaging materials price + quantity + costing]]
**Shipped:** exec/HR set absolute `current_price` + `on_hand` on Packaging Materials (`materials.php set_values`); Costing formula picker **Packaging materials** optgroup; price changes recalc formula costs.

### 2026-06-05 — [[problems/records/2026-06-05_inventory-arrangement|Inventory arrangement (four-branch IA)]]
**Shipped:** Raw / Packaging / Supplies / Products sidebar; `materials.php` API; `stocks.category` + `on_hand`; prod_manager material requests; split dept product routes. Run `migration_phase20_inventory_arrangement.sql`.

### 2026-06-05 — [[problems/records/2026-06-05_brc-supplier-bom-costing|Black Rice Coffee Supplier Master + BOM]]
**BRC v1:** phase 18 supplier ₱/bag + placeholder 20 kg math. **Corrected 2026-06-11:** 1 kg bag → 10 pouches — see [[problems/records/2026-06-11_brc-1kg-bom]] · `migration_phase23_brc_1kg_bom.sql`.

### 2026-06-05 — [[problems/records/2026-06-05_pt-supplier-bom-costing|Paragis Tea Supplier Master + BOM costing]]
**PT v2:** `supplier_dept_prices` (₱/bag), `Paragis leaf — raw` + `PT pouch packaging` formula (0.0096 bag + 1 pouch per sell unit), stock-in bags→pouches via supplier picker. BRC/Cacao unchanged (1:1 placeholder). Run `migration_phase17_pt_supplier_bom.sql` after phase 16. **Math reference:** [[specs/2026-06-05_paragis-tea-bom-math]] (2500÷24 yield vs 24÷2500 costing).

### 2026-06-05 — [[problems/records/2026-06-05_dept-products-pt-brc-cacao|Dept products PT / BRC / Jar Cacao full rollout]]
**Shipped v1:** catalog `pt`/`brc`/`jar_cacao`, `migration_phase16_dept_products.sql`, `dept_inventory_stock_in` (qty + unit cost on receipt), costing seed + CostingPage dept section, parcel + POS deduct. **Pause / tomorrow:** align pricing with **Supplier Master** like honey (₱/unit list, supplier on stock-in) — categories exist in `suppliers` but no dept price API yet.

### 2026-06-05 — [[problems/records/2026-06-05_online-orders-prep-stamper-split|Online Orders prep vs stamper + SPX follow-ups]]
**4 tools, no chain:** VIP/SPX/Excel = download only → **Back to tools**; user picks **PDF + Excel stamper** separately (`WaybillStamperTool`). Not prep→stamper. **SPX parse:** gallon+variant, `[1][2]` cells, B2T1 350ml, title qty. **UX:** `stash_download`, review type-only edit. README + `003_Fragile_Edges#Online Orders stamp UX` document the rule.

### 2026-06-05 — [[problems/records/2026-06-05_online-orders-two-phase-remake|Online Orders two-phase remake]]
Backend `run_unified_stamp` + prep APIs; replaced three pipeline run steps with tools-page wizard (see prep-stamper-split for final UX).

### 2026-06-05 — [[problems/records/2026-06-05_spx-gallon-variant-qty|SPX gallon qty + variant parse]]
First gallon/B2T1 packing-list fixes; superseded detail in prep-stamper-split record.

### 2026-06-05 — [[problems/records/2026-06-05_rts-spa-followup-ux|RTS SPA follow-up UX]]
Grouped RTS sidebar; sync toast + HR role; records table badges, line_count, compact filters, standard pagination footer.

### 2026-06-05 — [[problems/records/2026-06-05_rts-spa-migration|RTS full SPA migration]]
Migrated record, records list, and dashboard to React; v1 IMS case API + shared handlers; legacy PHP redirects.

### 2026-06-05 — [[problems/records/2026-06-05_unified-requests-hub|Unified Requests hub]]
Merged inventory corrections, edit requests, and POS discount approvals into one Requests hub with redirects.

### 2026-06-05 — [[problems/records/2026-06-05_deliveries-delete-revert-ship|Deliveries delete reverts shipped parcel]]
Deleting last delivery item now reverts parcel state and inventory deduction to allow clean rescan/re-ship.

### 2026-06-05 — [[problems/records/2026-06-05_deliveries-save-latency|Deliveries save latency fix]]
Done/Scan-next latency reduced via eager compressed background photo upload and fewer refresh calls.

### 2026-06-05 — [[problems/records/2026-06-05_deliveries-sidebar-active-state|Deliveries sidebar active-state fix]]
Set exact route matching so Add deliveries and Review batches do not highlight together.

### 2026-06-05 — [[problems/records/2026-06-05_shopee-spx-review-full-list|Shopee SPX review shows full batch]]
Review panel listed only ambiguous rows → fixed to show all pages before export. SPA rebuild.

### 2026-06-05 — [[problems/records/2026-06-05_shopee-spx-tracking-o0|Shopee SPX tracking O→0 in master list]]
`PH…O` normalized to `…0` in DB/master → narrowed OCR confusables to Flash/Lazada P-IDs. PHP + Python only.

### 2026-06-05 - Unified Requests hub (inventory + edit requests + POS discounts)
- **Problem:** Delivery review edit/delete requests lived on a separate **Edit Requests** page (`/edit-requests`); executives expected them under sidebar **Requests** (`/inventory/requests`) alongside inventory corrections. Page was inventory-only — more request types coming.
- **What we did:**
  - **`InventoryRequestsPage`** → unified **Requests** hub with **sectioned tables** (not one inventory-only table):
    1. **Inventory corrections** — honey warehouse/production/packaging (`honey.php` correction_list)
    2. **Edit requests** — deliveries delete/tracking fixes + inventory row edits (`edit_requests.php`) via **`EditRequestsSection`**
    3. **POS discount approvals** — executive only (`pos_discounts.php`)
  - **`EditRequestsSection.tsx`** — reusable table: Category (Deliveries / Inventory / Products), Target, Change, Requester, Reason, Approve/Reject (exec).
  - **`editRequestsApi.ts`** — `listEditRequests`, `approveEditRequest`, `rejectEditRequest`, `formatEditRequestCategory`, `formatEditRequestChange`.
  - **Executive + PM:** `/edit-requests` **redirects** to `/inventory/requests#edit-requests-section`. PM **removed** duplicate sidebar **Edit Requests** link.
  - **Team leader / staff:** still use `/edit-requests` (standalone page — no access to inventory Requests route).
  - **Deliveries review:** PM **My requests** → `#edit-requests-section`.
  - **Notifications:** pending **edit_requests** included in exec bell badge + dropdown (`CorrectionNotificationsContext`); click → `#edit-requests-section`.
- **PM flow:** Review batches → submit delete/edit → track on **Requests → Edit requests**. **Executive:** same page → Approve/Reject (applies `delivery_edit_request_apply`).
- **AI rules:** New approval workflows → add a **section** on Requests hub (or extend `EditRequestsSection` with category), not a new top-level nav page. Reuse `EditRequestsSection` + `edit_requests.php` when staff submits + exec approves.
- **Key files:** `InventoryRequestsPage.tsx`, `EditRequestsSection.tsx`, `EditRequestsPage.tsx`, `editRequestsApi.ts`, `Sidebar.tsx`, `DeliveriesReviewPage.tsx`, `CorrectionNotificationsContext.tsx`, `PageLayout.tsx`
- **Patterns:** [[005_SPA_UX_Patterns#Requests hub (unified approvals)]]
- **Deploy:** `cd app && npm run build` + hard refresh.

### 2026-06-05 - Deliveries delete reverts ship (orphan shipped parcel repair)
- **Symptom:** Executive **deleted** a row in **Review batches**, rescanned same tracking → **"This parcel is already shipped."** (green `shipped` badge); could not re-photograph / re-ship.
- **Root cause:** **`deliveries_delete_item_by_id()`** only removed `delivery_batch_items` + photo file. **`online_order_parcels`** stayed **`shipped`** and packaging stock stayed deducted from prior **`ship_parcel`**.
- **Fix:**
  - **`honey_parcel_revert_deduct()`** — restore qty to `honey_packaging_rows` from `online_parcel_deduct_lines`; delete deduct lines + `honey_sales_log` parcel rows.
  - **`deliveries_revert_parcel_to_packing_if_shipped()`** — when last delivery photo for parcel removed: status → **`packing`**, clear `ship_date` / `shipped_by_user_id` / `deducted_at`.
  - **`deliveries_delete_item_by_id()`** — after DELETE, if **no remaining** `delivery_batch_items` for that tracking/parcel → revert ship + stock.
  - **`deliveries_repair_orphaned_shipped_parcel()`** — on **`parcel_by_tracking`** lookup: if `shipped` but **zero** delivery photos (legacy mistaken delete) → auto-revert to `packing` so rescan works without manual DB fix.
- **Rule:** Deleting delivery photo **does not** unship if **other** active delivery rows still exist for same tracking/parcel.
- **Key files:** `delivery_edit_requests.php`, `honey_parcel_inventory.php`, `api/v1/deliveries.php`
- **Fragile edges:** [[003_Fragile_Edges#Deliveries delete vs parcel ship state]]
- **Deploy:** PHP only — rescan after deploy repairs orphaned rows.

### 2026-06-05 - Deliveries save latency fix (photo upload performance)
- **Symptom:** **Done** / **Scan next** on Add deliveries felt very slow — long **“Saving parcel…”** spinner; PM reported noticeable wait every parcel.
- **Root causes (ranked):**
  1. **Full-resolution phone photos** (often 2–8 MB) uploaded only when user tapped Done — large multipart POST dominates wall time.
  2. **Sequential API chain on one button:** `upload_photo` → `refreshBatch` (GET) → `ship_parcel` → `refreshBatch` again — up to **4 round trips** before UI advanced.
  3. **PHP** read entire upload into RAM via `file_get_contents($tmp)` even when **`client_hint`** (scanned tracking) already made QR/blob classification unnecessary.
  4. **Secondary:** `ship_parcel` → `honey_parcel_apply_deduct` (FIFO packaging updates) — real work but usually smaller than photo upload.
- **Not the cause:** Server-side QR decode on the image — normal flow passes `client_hint` from scan; `deliveries_classify_upload()` returns from hint without `rts_classify_delivery_qr_blob()`.
- **Fix (SPA):**
  - **`compressDeliveryPhoto.ts`** — resize longest edge to **1280px**, JPEG **~0.82**; skip if already small JPEG (<500 KB).
  - **Eager background upload** — on photo capture, compress + `upload_photo` immediately while user reviews parcel; **Done / Scan next** usually only runs **`ship_parcel`** (awaits in-flight upload if user taps early).
  - **Batch count** from upload response **`batch_item_count`** — removed redundant `refreshBatch()` calls during save (page load / date change still refresh once).
  - **UX copy:** “Preparing photo…” → “Saving photo in background…” → “Photo saved — tap Done or Scan next when ready”; saving step shows “Shipping parcel…” when photo already uploaded.
  - **Retake** bumps `uploadGenRef` to cancel stale in-flight uploads.
- **Fix (PHP):** `deliveries_upload.php` — when `client_hint` non-empty, classify from hint only; **do not** `file_get_contents` the tmp file.
- **AI rules — keep in mind for future features:**
  1. **Camera / file uploads in SPA:** always **compress client-side** before `FormData` POST unless file is already tiny.
  2. **Upload timing:** start upload **at capture/selection**, not on final confirm — user review time hides network latency.
  3. **Avoid refresh chains:** use mutation response fields (`batch_item_count`, etc.) instead of extra GETs after every POST.
  4. **Server:** when prior step already supplied structured metadata (tracking hint, IDs), **skip re-reading binaries** for classification/OCR.
  5. **Do not** merge upload+ship into one endpoint unless necessary — eager upload + separate `ship_parcel` preserves idempotency and still feels fast.
  6. Reuse **`compressDeliveryPhoto`** (or same max-edge/JPEG recipe) for any new packing-photo / evidence-photo flows.
- **Key files:** `app/src/app/lib/compressDeliveryPhoto.ts`, `DeliveriesPage.tsx`, `deliveriesApi.ts`, `includes/deliveries_upload.php`
- **Patterns:** [[005_SPA_UX_Patterns#Photo upload performance (SPA + API)]] · [[005_SPA_UX_Patterns#Deliveries (prod_manager)]]
- **Deploy:** PHP + `cd app && npm run build` + hard refresh.

### 2026-06-05 - Deliveries UI polish (sidebar nav, feedback motion, review view modal, hard delete)
- **Sidebar active state:** On `/deliveries/review`, both **Add deliveries** and **Review batches** highlighted — React Router `NavLink` prefix-matches `/deliveries` for child paths.
- **Fix (`Sidebar.tsx`):** `NavLinkChild.end?: boolean`; **Add deliveries** `{ to: "/deliveries", end: true }` so only exact `/deliveries` is active; pass `end={child.end}` to each child `NavLink`. Deliveries group auto-expands when `pathname.startsWith("/deliveries")`.
- **Add deliveries feedback:** `DeliveriesPage` uses fulfillment motion kit (not custom full-screen overlay) — `FulfillmentToast` (Scan next), `FulfillmentSuccessPanel` (Done), `FulfillmentStepSwitch`, `FulfillmentSectionEnter`, `FulfillmentErrorBanner`, `FulfillmentPressable`.
- **Review batches table:** Stays compact; **View** opens `AppModal` with tracking, recipient, items, order, courier, parcel status, packing photo. `review_list` enriched via `deliveries_enrich_review_rows()` (`parcel` object on each row).
- **Hard delete (not void):** `deliveries_delete_item_by_id()` — DELETE row + photo file + pending edit requests; API `delete_item` (alias `void_item`, executive only). If **last** photo for parcel → also **revert ship** + restore packaging stock (see [[002_Memory_Bank#2026-06-05 - Deliveries delete reverts ship (orphan shipped parcel repair)]]).
- **Duplicate tracking:** `deliveries_assert_tracking_not_duplicate()` on upload, resolve, edit-request apply; `DeliveryDuplicateScanModal` on scan conflict.
- **Patterns:** [[005_SPA_UX_Patterns#Sidebar navigation — sibling routes]] · [[005_SPA_UX_Patterns#Deliveries (prod_manager)]]
- **Key files:** `Sidebar.tsx`, `DeliveriesPage.tsx`, `DeliveriesReviewPage.tsx`, `delivery_edit_requests.php`, `deliveries_upload.php`, `api/v1/deliveries.php`
- **Deploy:** `cd app && npm run build` + hard refresh.

### 2026-06-05 - Edit requests submit: Server error on delivery edit/delete
- **Symptom:** Production manager submits edit/delete request on Deliveries review → **Server error.**
- **Root cause:** `api/v1/edit_requests.php` called `api_dispatch()` **before** `const ER_ALLOWED_TABLES` — PHP had not defined the constant yet when `api_er_submit()` ran.
- **Fix:** Move `api_dispatch([...])` to **end of file** (after const + handler functions), same pattern as other v1 APIs.
- **Key file:** `api/v1/edit_requests.php`

### 2026-06-05 - Deliveries upload transaction fix + review photo viewer
- **Symptom:** Add deliveries → photo taken → Done / Scan next → **"There is no active transaction"**; batch count stayed 0.
- **Root cause:** `deliveries_upload_photos()` called `beginTransaction()`, then `online_parcels_get_by_tracking()` → `online_parcels_ensure_schema()` (`CREATE TABLE IF NOT EXISTS`). MySQL DDL **implicitly commits**, ending the txn; final `$pdo->commit()` threw.
- **Fix (`deliveries_upload.php`):** Run `online_parcels_ensure_schema()` **before** `beginTransaction()`; link parcel with lightweight `SELECT id` (no schema DDL inside txn).
- **Review batches:** `review_list` API returns `photo_url`; `DeliveriesReviewPage` thumbnail + View modal; `DeliveryReviewEditModal` shows photo.
- **UX:** Done / Scan next require `photoFile` + preview; retake clears cached `delivery_item_id`.
- **Key files:** `deliveries_upload.php`, `api/v1/deliveries.php`, `DeliveriesPage.tsx`, `DeliveriesReviewPage.tsx`
- **Deploy:** PHP + `cd app && npm run build`

### 2026-06-05 - System-wide AppModal standard (all center dialogs)
- **What we did:** Introduced canonical modal shell `AppModal` (`components/AppModal.tsx`) — dimmed blurred backdrop (`bg-black/50 backdrop-blur-[2px]`) + elevated card panel (`rounded-2xl shadow-2xl ring-1 ring-black/5`) with spring motion and `useReducedMotion()`.
- **Layout parts:** `AppModalHeader`, `AppModalBody`, `AppModalFooter` — header/body/footer pattern used across exec, honey, inventory, deliveries, CSR.
- **Migrated pages:** `CostingPage`, `HoneyProductionPage`, `HoneyWarehousePage`, `HoneyPackagingPage`, `InventoryRequestsPage`, `InventoryPage`, `RTSPage`, `SuppliersPage`, `StaffManagementPage`, `ARCreditsPage`, `DeliveriesPage` (QR scanner).
- **Already on standard:** `CorrectionReviewModal`, `ExecutiveRowEditModal`, `SupplierProfileModal`, `BatchStartModal`, `DeliveryReviewEditModal`, `DeliveryDuplicateScanModal`, `DuplicateTrackingModal`, `OnlineParcelDetailModal`; `FulfillmentModal` re-exports `AppModal`; `ui/dialog` + `ui/alert-dialog` aligned; `POSModal` uses same tokens.
- **Cursor rule:** `.cursor/rules/app-modal.mdc` — agents must use `AppModal` for new center modals; no inline `fixed inset-0` overlays.
- **Docs:** [[005_SPA_UX_Patterns#Modal patterns — AppModal is the standard]]
- **Deploy:** `cd app && npm run build` + hard refresh.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-05 - Shopee SPX Packing List.Standard Local + master Excel merge
- **What we did:** Added a second Shopee SPX import path for Shopee’s **`Packing List.Standard Local_*.xlsx`** plus waybill PDFs. Items come from **`product_info`** blobs (same long-text style as VIP), not PDF OCR alone.
- **UI (`ShopeeSpxRunStep.tsx`):** Extract step toggle — **PDF only** | **Packing list Excel + PDF**. Packing list mode: upload `.xlsx` + PDFs → **Import packing list** → same variant carousel → review → build Excel → stamp.
- **Backend:**
  - `includes/shopee_spx_product_info.php` — parse `Product Name` / `Variation Name` / `Quantity` from `product_info`.
  - `includes/shopee_spx_packing_list.php` — read sheet 0; headers `tracking_number` + `product_info` (+ `order_sn`); optional PDF name merge by normalized tracking via `pdf_to_excel_extract()`.
  - `api/v1/online_orders.php?action=spx_import_packing_list` — `excel_token` + optional `pdf_tokens`.
- **Parsing rules:**
  - `Variation Name:1LITER-Light|Amber|Dark` → `1 Liter Honey LIGHT|AMBER|DARK` (auto, no picker).
  - B2T1 `Light & Amber & Dark,350ml` → `3L-HONEY (LAD) + 350ml` — **LAD set but free 350ml still needs variant** (carousel shows 350ml presets only).
  - User pick **Light 350ml** → final line `3L-HONEY (LAD) + 350ml (L)` via `shopeePresetToResolvedItemsLine()`.
- **Master iPYX / batch lines (Order No. + Name):**
  - Packing list `order_sn` → API row `order_no` → `run_shopee_resolve` → `online_orders_build_ipyx_excel_from_rows()` column **Order No.** (was always blank before).
  - **Name** from PDF extract merged at import by tracking; flows to iPYX **Name** and `csr_online_order_lines.recipient_name`.
  - Column layout unchanged: `Order No. | Name | Item/s | Note | | Courier | Tracking Number`.
- **SPA variant logic (`honeyItemVariant.ts`):** `1LITER-*` auto-resolved; `\bLAD\b` no longer skips resolution when `350ml` lacks `(L|A|D)` suffix; `variantPickTitle()` for B2T1 free-bottle copy.
- **Key files:** `shopee_spx_product_info.php`, `shopee_spx_packing_list.php`, `online_orders_generator.php`, `api/v1/online_orders.php`, `ShopeeSpxRunStep.tsx`, `ItemResolverCarousel.tsx`, `honeyItemVariant.ts`, `onlineOrdersApi.ts`, `pdfToExcelApi.ts` (`order_no` on row type)
- **Deploy:** PHP + `cd app && npm run build` + hard refresh.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Patterns:** [[005_SPA_UX_Patterns#Shopee SPX packing list import]] · [[003_Fragile_Edges#Shopee SPX packing list product_info]]

### 2026-06-05 - Parcel item parser: semicolon split + other_items (mixed orders)
- **Symptom:** Lines like `3 Paragis Tea ; 1 Liter Honey AMBER` parsed as 3× amber liter — honey qty/variant leaked across segments.
- **Fix (`includes/honey_parcel_item_parser.php`):** Split `Item/s` on **`;`** and **`+`**; parse each segment independently. Non-honey products (Paragis, Cacao, BRC, Pito-Pito, Honey Comb) collected in **`other_items`** — noted for packers, no honey deduct.
- **Promote rule (`online_order_parcels.php`):** Allow lines with honey-only, other-only, or mixed — parser errors still block promote.
- **SPA:** `OnlineParcelDetailModal` — **Other products (noted)** section when `other_items` present.
- **Ops:** Existing parcels with wrong `items_parsed_json` need **re-finalize** CSR batch to pick up parser fix.
- **Key files:** `honey_parcel_item_parser.php`, `online_order_parcels.php`, `OnlineParcelDetailModal.tsx`
- **Deploy:** PHP + SPA rebuild if modal changed.

### 2026-06-05 - Deliveries role (Production Manager)
- **Who scans & photographs parcels:** **`prod_manager`** (Production Manager) and **`executive`** — not CSR.
- **Sidebar:** **Deliveries** (truck) → **Add deliveries** `/deliveries` (scan → photo → ship) · **Review batches** `/deliveries/review`.
- **Flow:** CSR finalizes online orders → parcels `packing` → PM Deliveries scan/photo → `ship_parcel` deducts packaging → photo visible on **Online parcels** modal.
- **API gate:** `online_parcels_user_can_ship()` — `executive`, `prod_manager` only.
- **Patterns:** [[005_SPA_UX_Patterns#Deliveries (prod_manager)]]

### 2026-06-05 - Partner waybill stamp fixes (Flash NO MATCH + iPYX first sheet only)
- **Symptoms (E2E):** Partner stamp showed **NO MATCH** on Flash/Lazada waybill `P3623KK03MYAD` even though row 1 of iPYX Excel had the order. Error banner displayed `P3623KKO3MYAD` (letter **O** instead of digit **0**). Extra/wrong rows also appeared because the parser scanned **all workbook sheets**.
- **Root causes:**
  1. **OCR 0↔O** — `waybill-stamper/processor.py` OCR/text layer read `0` as `O` on marketplace `P…` parcel IDs; Excel had correct `P3623KK03MYAD`.
  2. **All sheets** — `processor.py` `parse_excel()` iterated every sheet; `online_orders_parse_ipyx_excel_paths()` used `getAllSheets()`. iPYX daily workbook: **only sheet 1** is today's orders; other sheets add noise or overwrite keys.
- **Fixes:**
  - **`norm_tn()` / `ocr_confusables_for_match()`** in `waybill-stamper/processor.py` — for Flash/Lazada `P[A-Z0-9]{11,17}` IDs, map **`O` → `0`** before lookup (excludes Shopee `PH`/`SPEPH`/`SPX`).
  - **`online_orders_ocr_confusables_for_match()`** in `includes/online_orders_generator.php` — same scoped rule inside **`online_orders_normalize_tracking()`** (duplicate checks + Excel keys stay aligned).
  - **First sheet only** — `processor.py` reads `wb.sheetnames[0]` only; PHP `online_orders_parse_ipyx_excel_paths()` uses `$spreadsheet->getSheet(0)` only.
- **Example:** PDF OCR `P3623KKO3MYAD` + Excel `P3623KK03MYAD` → both normalize to `P3623KK03MYAD` → MATCHED.
- **Key files:** `waybill-stamper/processor.py`, `includes/online_orders_generator.php`
- **Deploy:** PHP + Python only — **no SPA rebuild**. Re-stamp same PDF + Excel to verify.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Fragile edges:** [[003_Fragile_Edges#Partner waybill matching (iPYX Excel + Flash OCR)]]

### 2026-06-05 - Online Parcels API Forbidden for CSR team leader
- **Symptom:** CSR TL opened `/fulfillment/online-parcels` — page loaded but API returned **Forbidden**; empty table + red banner.
- **Root cause:** `online_parcels_user_can_access()` checked SPA role names `csr_tl` / `csr_staff`, but `ims_current_user()` session stores IMS roles **`tl`** / **`staff`**. SPA normalizes via `api_normalize_role()` on login only — API parcel gate did not.
- **Fix:** `includes/online_order_parcels.php` — added `online_parcels_user_is_csr_dept()`; allow `tl` / `staff` when `departments.name = 'csr'` (same pattern as `waybill_stamper_user_can_access()` for team leaders). Also keeps `executive`, `prod_manager`, and normalized `csr_*` names.
- **Key files:** `includes/online_order_parcels.php`, `api/v1/parcels.php`
- **Deploy:** PHP only — refresh page.
- **Fragile edges:** [[003_Fragile_Edges#IMS role vs SPA role (API gates)]]

### 2026-06-05 - CSR variant picker stock indicators (Shopee SPX + VIP + Order Remarks)
- **What we did:** When CSR picks Light/Amber/Dark for a parcel, each preset button now shows **bottled packaging stock** under the label (e.g. `Amber 1L` → `38 left`), matching the Shopee carousel design.
- **Why:** CSRs need on-hand counts at pick time — not just after opening Honey Packaging — to avoid committing variants that are out of stock.
- **Data source:** Same snapshot as Honey Packaging dashboard — `honey_packaging_rows` with non-empty `batch_no` only (warehouse/production excluded).
- **API:** `GET api/v1/online_orders.php?action=packaging_stock` — CSR-accessible read-only wrapper around `honey_packaging_total_inventory_snapshot()` (CSR roles cannot call `honey.php` directly).
- **SPA:**
  - `honeyPackagingStock.ts` — maps presets → variant+size; `enrichShopeePresets` / `enrichVipPresets`.
  - `useHoneyPackagingStock()` — fetches once on carousel mount.
  - `PresetOption.stockLeft` optional field; `VariantPresetButton` second line `{n} left`.
  - Wired in **`ItemResolverCarousel`** (Shopee), **`AmbiguousOrderCarousel`** (VIP + Order Remarks).
  - **2L/3L combo codes** (`LA`, `LAD`, …) show **min** 1L stock among letters in the combo (bottleneck).
- **UX fix — scroll chevrons:** `OrderRemarksScrollButton` in `VariantCardStrip` was opaque and covered stock text. Now **~55% opacity** + translucent `bg-card/45` + light blur; **hover → ~95%** for tap target.
- **Bug fix:** Shopee extract crashed `cn is not defined` — `ItemResolverCarousel.tsx` still used `cn()` for row classes but import was dropped when stock work landed. Restored `import { cn } from "@/app/components/ui/utils"`.
- **Key files:** `api/v1/online_orders.php`, `onlineOrdersApi.ts`, `honeyPackagingStock.ts`, `useHoneyPackagingStock.ts`, `VariantPresetButton` / `OrderRemarksScrollButton` in `orderRemarksMotion.tsx`, `VariantCardStrip.tsx`, `ItemResolverCarousel.tsx`, `AmbiguousOrderCarousel.tsx`, `orderRemarksParse.ts` (`PresetOption.stockLeft`)
- **Deploy:** `cd app && npm run build` + hard refresh.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Patterns:** [[005_SPA_UX_Patterns#CSR variant picker stock indicators]] · [[003_Fragile_Edges#CSR packaging stock for variant pickers]]

### 2026-06-05 - Online Parcels packing photo (Deliveries → detail modal)
- **What we did:** Parcel API responses now include **`packing_photo`** from the latest non-voided **`delivery_batch_items`** row (photo taken during Deliveries scan). **`OnlineParcelDetailModal`** shows the image at the top; if none exists yet, shows **“Not yet packed”** placeholder (dashed border + package icon).
- **Why:** CSR/prod need to see whether a parcel was photographed in Deliveries before ship; photos are not stored on `online_order_parcels` — they live in the deliveries bucket.
- **Backend (`includes/online_order_parcels.php`):**
  - `online_parcels_find_packing_photo()` — prefers `delivery_batch_items.online_parcel_id`, fallback normalized `tracking_number`.
  - `online_parcels_format_packing_photo()` — builds `photo_url` via `rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, …)` with `/rts/` prefix for SPA.
  - `online_parcels_enrich_packing_photo()` — single parcel (get / lookup).
  - `online_parcels_enrich_packing_photos_batch()` — list endpoint (2 queries max, no N+1).
  - `online_parcels_format_row()` always includes `packing_photo: null` unless enriched.
- **API:** `GET api/v1/parcels.php?action=list|get|lookup` — each parcel may include:
  ```json
  "packing_photo": { "delivery_item_id", "photo_filename", "photo_url", "delivery_date" } | null
  ```
- **SPA:**
  - `OnlineParcel` type + `packing_photo` in `parcelsApi.ts`.
  - `OnlineParcelDetailModal.tsx` — **Packing photo** section above recipient/items.
- **Key files:** `includes/online_order_parcels.php`, `includes/rts_storage.php`, `api/v1/parcels.php`, `OnlineParcelDetailModal.tsx`, `parcelsApi.ts`
- **Deploy:** `cd app && npm run build` + hard refresh.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Patterns:** [[005_SPA_UX_Patterns#Online parcel detail (packing photo)]] · [[003_Fragile_Edges#Online order parcels + ship deduct]]

### 2026-06-05 - Online Parcels UI + fulfillment motion & hover system
- **Online Parcels (`/fulfillment/online-parcels`):**
  - Table aligned with system pattern (`bg-card`, uppercase header, `px-4 py-3`, loading/empty states, pagination).
  - Row click + search lookup → **`OnlineParcelDetailModal`** (packing photo or “Not yet packed”, tracking, items raw, parsed deltas, batch, status).
  - **`FulfillmentTableRow`** — stagger reveal + **hover bg + drop shadow**.
  - Toasts on lookup found + refresh; animated error banner.
- **Shared motion kit (`onlineFulfillmentMotion.tsx`):**
  - `FulfillmentToast`, `FulfillmentErrorBanner`, `FulfillmentSectionEnter`, `FulfillmentStepSwitch`, `FulfillmentSuccessPanel`, `FulfillmentModal`, `FulfillmentPickerCard`, `FulfillmentTableRow`, `FulfillmentPressable`, `AnimatedStat`.
  - **Hover tokens:** `fulfillmentHoverCard`, `fulfillmentHoverRow`, `fulfillmentHoverPressablePrimary/Outline` — amber ring + shadow on cards; shadow + bg on rows; lift + shadow on buttons.
  - **`useReducedMotion()`** respected everywhere.
- **Online Orders wired:** step transitions, stamp stats animate, pipeline cards hover, pressable CTAs, duplicate + parcel modals use `FulfillmentModal`.
- **Policy (new):** All future SPA features must ship **feedback animations** + **hover highlight/shadow** on interactive elements — documented in [[005_SPA_UX_Patterns#Feedback animations & hover (mandatory)]] and `.cursor/rules/spa-ui-design.mdc`.
- **Key files:** `onlineFulfillmentMotion.tsx`, `OnlineParcelsPage.tsx`, `OnlineParcelDetailModal.tsx`, `OnlineOrdersGeneratorPage.tsx`, `StampResultPanel.tsx`, `PipelinePicker.tsx`, `DuplicateTrackingModal.tsx`
- **Deploy:** `cd app && npm run build` + hard refresh.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-05 - Online Orders duplicate tracking prevention (Partner + VIP + Shopee SPX)
- **What we did:** Blocked duplicate tracking numbers across all CSR online-order pipelines. On conflict, the SPA opens a **modal** (not a red error banner) showing the tracking number, the **existing record** (recipient, items, courier, pipeline, batch/parcel), and what the user tried to add.
- **Why:** Same TN must not be saved twice in an active/processed batch or after promotion to fulfillment parcels — prevents double packing/shipping.
- **Backend (`includes/online_orders_generator.php`):**
  - `online_orders_normalize_tracking()` — wraps `rts_normalize_tracking_from_qr()` + uppercase/strip spaces.
  - `online_orders_existing_tracking_map()` — indexes `csr_online_order_lines` (batches `active`/`processed`) + `online_order_parcels`.
  - `online_orders_validate_tracking_duplicates()` / `online_orders_assert_no_duplicate_tracking()` — also catches **duplicates within the same upload**.
  - `OnlineOrdersDuplicateTrackingException` with structured `duplicates[]` payload.
  - **`online_orders_lines_append()`** — validates then stores normalized TN.
  - **Partner** — parse Excel **before** stamp; reject before `waybill_stamper_process_uploads()` wastes work.
  - **Shopee** — validate in `run_shopee_resolve` before building Excel.
  - **VIP stamp** — **removed** second `lines_append` (lines saved once at export, like Shopee resolve → stamp).
- **API (`api/v1/online_orders.php`):** `api_online_orders_fail_pipeline()` returns **HTTP 409** + `{ duplicates: [...] }` for `lines_append`, `run_partner`, `run_vip_stamp`, `run_shopee_resolve`. Check `OnlineOrdersDuplicateTrackingException` **before** generic `RuntimeException` (subclass trap).
- **SPA:**
  - `DuplicateTrackingModal.tsx` — fixed `z-[100]` overlay (same pattern as `ExecutiveRowEditModal` / `CorrectionReviewModal`). **Do not use Radix `Dialog` here** — it was the only Dialog consumer and did not render reliably.
  - `useDuplicateTrackingHandler.ts` + `duplicateTrackingUtils.ts` — shared hook; `duplicateConflictsFromApiError()` reads `data.duplicates` regardless of HTTP status.
  - Wired in `PartnerRunStep`, `VipRunStep` (export), `ShopeeSpxRunStep` (resolve). On duplicate: modal only, `setError(null)`.
- **Key files:** `includes/online_orders_generator.php`, `api/v1/online_orders.php`, `DuplicateTrackingModal.tsx`, `useDuplicateTrackingHandler.ts`, `duplicateTrackingUtils.ts`, `PartnerRunStep.tsx`, `VipRunStep.tsx`, `ShopeeSpxRunStep.tsx`, `onlineOrdersTypes.ts`
- **Deploy:** `cd app && npm run build` + hard refresh on ngrok/Apache.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Patterns:** [[005_SPA_UX_Patterns#Duplicate tracking conflict (Online Orders)]] · [[003_Fragile_Edges#Online Orders duplicate tracking]]

### 2026-06-04 - CSR variant resolution UX (VIP + Shopee SPX) — confirm, review, edit
- **What we did:** Standardized ambiguous honey variant flows in Online Orders so users **never auto-commit** a wrong Light/Amber/Dark pick.
- **Shopee SPX (`ItemResolverCarousel` + `ShopeeSpxRunStep`):**
  - Pick preset or custom line → **“Is this correct?”** (`Yes, correct` / `Change`) before saving.
  - New **`review`** phase: **full batch** table (Original vs Final item) → **Confirm all & build Excel** — not only edited/ambiguous rows (fixed 2026-06-05).
  - **`Show completed`** checkbox lists confirmed rows; **Edit item** clears resolution and reopens picker.
  - Resolutions require `confirmed: true` (`shopeeRowIsReadyForExport` in `honeyItemVariant.ts`).
- **VIP (`AmbiguousOrderCarousel` + `VipRunStep`):**
  - Fixed **Show completed** — was hardcoded `showCompleted={false}` and `onShowCompletedChange={() => {}}` (checkbox did nothing).
  - Added **Edit choice** on completed cards via `onClearResolution` (delete from `resolutions` state).
- **Bug fix:** After confirm, app crashed `e.trim is not a function` — `shopeeRowIsReadyForExport` passed whole **`row` object** to `honeyItemNeedsVariantResolution(items)` instead of item string. Added `honeyItemLineText()` for API values that are not strings.
- **Pattern for future CSR pickers:** Reuse `AmbiguousOrderCarousel` / `ItemResolverCarousel` + `orderRemarksMotion` — see [[005_SPA_UX_Patterns#CSR variant resolution (ambiguous honey)]].
- **Key files:** `ItemResolverCarousel.tsx`, `ShopeeItemsReviewPanel.tsx`, `ShopeeSpxRunStep.tsx`, `AmbiguousOrderCarousel.tsx`, `VipRunStep.tsx`, `app/src/app/lib/honeyItemVariant.ts`
- **Deploy:** `cd app && npm run build` + hard refresh on ngrok/Apache.

### 2026-06-04 - Honey packaging total inventory dashboard
- **What we did:** Added **Total inventory (batch bottled)** at top of Honey — Packaging (`/inventory/honey/packaging`). Aggregates **current** bottle counts from `honey_packaging_rows` only (batch numbers exist after bottling). Variants Light / Amber / Dark; sizes 250ML, 350ML, 1L, Gallon. UI matches Sales page: three variant cards, donut chart, per-size grid; **size filter** pills (All / 250ML / 350ML / 1L / Gallon) instead of Sales discount filters.
- **Why:** CSR needs sellable bottled stock at a glance; warehouse gallons and production WIP must not inflate totals.
- **Excluded:** `honey_warehouse_rows`, `honey_production_rows`, packaging rows without `batch_no`, variant `other`.
- **API:** `GET api/v1/honey.php?action=packaging_total_inventory` → `honey_packaging_total_inventory_snapshot()` in `includes/honey_inventory.php` (uses `honey_packaging_variant_inventory_breakdown()`).
- **SPA:** `HoneyVariantPieGrid` supports `mode="inventory"` (Sales keeps default `mode` with Regular/10%/20% filters). `useLiveRefresh` refreshes inventory + batch table together.
- **Key files:** `includes/honey_inventory.php`, `api/v1/honey.php`, `app/src/app/pages/honey/HoneyPackagingPage.tsx`, `app/src/app/components/honey/HoneyVariantPieGrid.tsx`
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-04 - CSR waybill storage moved to external `rts_storage`
- **What we did:** Waybill stamper, PDF→Excel, order remarks, and online orders now use `rts_storage_csr_tool_root()` when `config.php` `storage.root` is set (same pattern as deliveries / SP sort). Buckets: `waybill_stamper/`, `pdf_to_excel/`, `order_remarks/`, `online_orders/` under `C:/xampp/htdocs/rts_storage` (or your configured root).
- **Why:** Stash PDFs/Excel and stamped outputs were filling `rts/storage/` inside the git tree.
- **Key files:** `includes/rts_storage.php`, `includes/waybill_stamper.php`, `includes/pdf_to_excel.php`, `includes/order_remarks.php`, `includes/online_orders_generator.php`, `config/config.sample.php`
- **Ops:** Old files under `rts/storage/waybill-stamper/` etc. can be deleted after cutover; active session stash tokens may need re-upload. Subdirs are auto-created on first use.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-04 - Online Orders Generator: post-ship fixes & QA lessons
- **Context:** E2E on partner / Shopee / VIP pipelines surfaced PHP 500s, upload errors, skipped downloads, and Shopee labels without honey variant on PDF.
- **Fixes applied:**
  | Issue | Symptom | Root cause | Fix |
  |-------|---------|------------|-----|
  | Partner `run_partner` 500 | HTTP 500 after stamp | PhpSpreadsheet 2: `(int) getHighestColumn()` → `columnIndexFromString(0)` TypeError; `getCellByColumnAndRow` removed | `online_orders_coord()` + `getCell()` / `setCellValue()` in `includes/online_orders_generator.php` |
  | Shopee extract upload | `Could not upload "undefined": No files received` | `extractPdfToExcel(tokens)` passed stash strings not `File[]` | `ShopeeSpxRunStep`: `extractPdfToExcel(pdfFiles)` only; waybill stash separate on stamp |
  | Partner stamp download | Jump to “Pipeline step complete” before download | `onComplete` → `setStep("post_run")` immediately after stamp | Parent `stampResult` + `StampResultPanel`; no navigation until user picks “Process another” or “Done — master Excel” |
  | Shopee “1 x 1L HONEY” PDFs | Variant picker skipped | Extractor treated non-empty `items` as complete | `items_need_variant_resolution()` in `pdf-to-excel/extractor.py` → `missing_fields: items_variant`; `app/src/app/lib/honeyItemVariant.ts` + `ItemResolverCarousel` |
  | VIP Step 1 upload | Button only, no drop zone | Hidden `<input>` + button vs `FileZone` | VIP remarks step uses shared `FileZone` (drag/drop) like partner/Shopee |
- **Key files (fixes):** `includes/online_orders_generator.php`, `pdf-to-excel/extractor.py`, `app/src/app/lib/honeyItemVariant.ts`, `app/src/app/lib/normalizePartnerStampResult.ts`, `app/src/app/components/csr/onlineOrders/StampResultPanel.tsx`, `app/src/app/pages/csr/OnlineOrdersGeneratorPage.tsx`, `ShopeeSpxRunStep.tsx`, `PartnerRunStep.tsx`, `VipRunStep.tsx`
- **SPA production:** Rebuild after UI fixes: `cd app && npm run build` — Apache serves `app/dist/`; hard refresh (`Ctrl+F5`) if old bundle cached.
- **DB:** Migration `sql/migration_phase17_csr_online_orders.sql` on `rts_system` (tables: `csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`).
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-04 - Online Orders Generator (CSR batch wizard)
- **What we did:** Unified Waybill Stamper, PDF→Excel, and Order Remarks into `/waybill/online-orders` with batch DB (`csr_online_batches`, `csr_online_batch_runs`, `csr_online_order_lines`), `includes/online_orders_generator.php`, `api/v1/online_orders.php`, SPA wizard (`OnlineOrdersGeneratorPage` + pipeline steps). Extended `waybill_stamper_user_can_access()` for `csr_staff`. Legacy `/waybill/stamper`, `/waybill/pdf-to-excel`, `/waybill/order-remarks` redirect to the hub.
- **Why:** One CSR session for mixed online order types and a single master iPYX print Excel; persist parcel lines for future inventory integration.
- **Key files:** `sql/migration_phase17_csr_online_orders.sql`, `includes/online_orders_generator.php`, `api/v1/online_orders.php`, `app/src/app/pages/csr/OnlineOrdersGeneratorPage.tsx`, `app/src/app/lib/onlineOrdersApi.ts`
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`
- **Impact before shared edit:** `impact.php waybill_stamper_user_can_access`

### 2026-06-04 - AI-directed engineering workflow in Obsidian
- **What we did:** Added `006_AI_Directed_Engineering.md`, `010_Product_Theory.md`, `specs/` + `plans/` templates, Cursor rule `ai-directed-engineering.mdc`, updated `001_System_State` with Active Spec/Plan pointers.
- **Why:** Separate theory (you) → plan (AI, approved) → implement (AI) so work is not lost in chat sessions.

### 2026-06-04 - SPA design system & UX patterns in memory bank
- **What we did:** Added `004_SPA_Design_System.md`, `005_SPA_UX_Patterns.md`, SPA auto-scanners (`generated/spa/`: routes, components, motion, theme), and `.cursor/rules/spa-ui-design.mdc`.
- **Why we did it:** Keep AI-generated UI consistent with existing colors, PageLayout, modals, and feedback animations.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-04 - Automated Memory Bank generators
- **What we did:** Built `tools/memory_bank/` scanners that regenerate `_System_Core/generated/` (API index, include graph, DB table map, per-module caller index, frontend API clients). Added `impact.php` CLI, `003_Fragile_Edges.md`, Cursor rules, and composer `memory-bank` script.
- **Why we did it:** Give AI agents up-to-date dependency maps so code changes do not break unrelated features weeks later.
- **Key files:** `tools/memory_bank/generate_all.php`, `tools/memory_bank/impact.php`, `.cursor/rules/memory-bank-always.mdc`
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### [Today's Date] - System Initialization
- **What we did:** Connected Obsidian to Cursor and built the System Memory Core.
- **Why we did it:** Prevent Cursor from forgetting architectural rules, tech stack differences (React/PHP/Python), and endpoint patterns.
- **Key decisions:** Established `_System_Core` as the single source of truth.

### 2026-06-04 - Inventory connection (parcels, deliveries SPA, ship deduct)
- **What we did:** Closed CSR online orders → production shipping loop. On `batch_finalize`, lines promote to `online_order_parcels` (`packing`). Deliveries moved to SPA (`/deliveries`, `/deliveries/review`) for executive + prod_manager; scan shows Item/s; photo required; **Done / Scan next** runs aggregate packaging deduct (FIFO, no batch pick). Parcel lookup at `/fulfillment/online-parcels` (CSR TL + staff included per approval). `QtyCounter` shows current qty only (no initial/progress).
- **Decisions:** UNIQUE tracking per parcel; reject promote when parser errors; idempotent ship if already `shipped`; legacy `deliveries/*.php` → SPA redirect.
- **API:** `api/v1/parcels.php`, `api/v1/deliveries.php`; extend `batch_finalize` with `parcels_promoted` / `parcels_errors`.
- **Includes:** `honey_parcel_item_parser.php`, `honey_parcel_inventory.php`, `online_order_parcels.php`, `deliveries_upload.php`.
- **Migration:** `sql/migration_phase18_online_order_parcels.sql`
- **Plan:** [[plans/archive/2026-06-04_Inventory-Connection-to-Everything_plan]]

### 2026-06-02 - PDF Extraction Bug Fix (`pdf-to-excel/extractor.py`)
- **What we did:** Fixed tracking extraction reliability for OCR-heavy waybills in `pdf-to-excel/`.
- **Technical changes:**
  - Replaced simple first-match regex scanning with **priority-based matching**: `TN_PATTERNS` now uses `(priority, pattern)` tuples.
  - Added high-confidence numeric patterns for Lazada/J&T first: `8201...` and `820...` (priority 20).
  - Added broader carrier coverage: `FE...` (Flash), plus existing `SPEPH`, `SPX`, `JT`, `PH`, and marketplace `P...`.
  - Implemented `_valid_marketplace_parcel_id()` to reject OCR garbage for `P...` IDs (requires at least 2 digits), preventing false positives from vertical text noise.
  - Updated `find_tracking()` to evaluate all candidate matches and return the **best-priority** valid TN instead of the first regex hit.
  - Expanded `detect_courier()` mappings for clearer downstream labeling: `Flash / Marketplace`, `Flash Express`, `Lazada / J&T`.
- **Why we did it:** OCR pages sometimes surfaced noisy `P...` strings before the real Lazada numeric TN, causing incorrect extraction and downstream no-item matches.
- **Result:** PDF extraction now prefers correct TN candidates on mixed-carrier/OCR pages and reduces false matches from OCR artifacts.

### 2026-06-19 — RTS doc upload no preview on mobile
- **Record:** [[problems/records/2026-06-19_rts-doc-upload-no-preview]] (includes CSR role Forbidden fix on upload/save)

### 2026-06-19 — Online Parcels RTS status sync
- **What we did:** When CSR saves Record RTS, matching `online_order_parcels` row (by normalized tracking) flips to `rts` with `rts_case_id` + `status_before_rts`; void restores prior status. Online Parcels filter/badge for RTS.
- **Migration:** `sql/migration_phase49_online_parcels_rts_status.sql` — run `php tools/apply_migration_phase49.php` per environment.
- **Record:** [[problems/records/2026-06-19_online-parcels-rts-status-sync]]

### 2026-06-24 — RTS documentation monthly folders
- **What we did:** RTS proof uploads now save under `rts_documentation/YYYY-MM/` (same pattern as deliveries); DB stores `YYYY-MM/filename`; legacy flat files still served.
- **Record:** [[problems/records/2026-06-24_rts-documentation-monthly-folders]]

### 2026-06-25 — RTS Records blank media thumbnails
- **What we did:** Fixed SPA-relative `storage_media.php` URLs (`/rts/api/...`), bidirectional file resolve on disk, and `rtsDocUrl()` path encoding so Before ship / Photo / Video load in RTS Records.
- **Record:** [[problems/records/2026-06-24_rts-documentation-monthly-folders]] (updated)

### 2026-06-19 — VIP 3L `1 amber 2dark` → ADD parser
- **What we did:** `normalizeVariantCountText()` splits glued count+color tokens; `1 amber 2dark` resolves to **ADD** instead of wrong **AAA**.
- **Fix note:** [[problems/fixes/vip-spx-order-remarks-parser-guards]] §8

### 2026-06-30 — Bulk honey orders (warehouse B2B)
- **What we did:** Executive Bulk orders tab — supplier-code picks, COGS negotiation, draft → confirm deduct → void.
- **Record:** [[problems/records/2026-06-30_bulk-honey-orders]]

### 2026-07-07 — Courier reports module (summary + pie + filters)
- **What we did:** Added `reports.php?action=courier_summary` with exclusive date range/month/year filters and optional parcel status; replaced Reports chart block with courier summary cards and courier distribution pie tooltip (count + percentage).
- **Record:** [[problems/records/2026-07-07_courier-reports-module]]

### 2026-07-07 — Pipeline reports module (summary + pie + shared filters)
- **What we did:** Added `reports.php?action=pipeline_summary` and a new Pipeline Statistics section below Courier Statistics with summary cards + pie chart (percentage + count tooltip), reusing the same date/status filters and auto-updating on filter changes.
- **Record:** [[problems/records/2026-07-07_pipeline-reports-module]]

### 2026-07-14 — Monthly inventory (executive create/set + edit logs)
- **What we did:** Executive Monthly Inventory page + edit log list; seed honey prod/packaging; raw bag set; RTS set; materials set-on-hand mirrored to `inventory_edit_log`.
- **Record:** [[problems/records/2026-07-14_monthly-inventory-executive]]

### 2026-07-14 — Paragis pouch creation table
- **What we did:** Daily totals on Dried Paragis / Black Rice / Cacao; **Add** date+qty (log-only); renamed Cacao; mobile **Do next** action strip; **dept TL** can Stock in / Pouch / Produce (own dept).
- **Record:** [[problems/fixes/dept-raw-tl-produce]]

### 2026-07-14 — Honey dept TL gallon pipeline
- **What we did:** Honey-dept TL can warehouse stock in/out, production→packaging, packaging sell, and submit corrections (`honey_role_can_honey_ops`); `suppliers.php` list allowed for `tl` so stock-in picker works.
- **Record:** [[problems/fixes/honey-pipeline-tl-ops]]

### 2026-07-14 — Fast tracking search
- **What we did:** Debounced list search + TN-shaped `=`/prefix SQL on Deliveries review, RTS lists, Online parcels; search caps parcels `per_page` at 100.
- **Record:** [[problems/fixes/fast-tracking-search]]

### 2026-08-29 — Honey SKU stock-in custom batch
- **What we did:** Replaced the existing packaging-batch dropdown in the honey SKU scanner with a typed custom batch number; the API creates/reuses the matching custom packaging row without supplier-master validation.
- **Record:** [[problems/records/2026-08-29_honey-sku-custom-batch-stock-in]]

### 2026-08-29 — Honey Packaging Today SKU report
- **What we did:** Packaging scan reports now combine unique sticker scans with quantity-based SKU stock-in events, so current SKU stock-ins appear under Today and in totals.
- **Record:** [[problems/records/2026-08-29_honey-packaging-today-sku-events]]

### 2026-08-29 — Honey Packaging scan-log quantities
- **What we did:** Packaging scan details now show the scanned/inputted quantity, operator, timestamp, and whether each entry came from a SKU quantity or individual sticker scan.
- **Record:** [[problems/records/2026-08-29_honey-packaging-scan-log-quantities]]

### 2026-08-29 — Static SKU cancelled restock
- **What we did:** Cancelled Restock now queues permanent SKU returns with quantity and destination pool, restores aggregate Honey/department/RTS inventory on confirmation, and shows scanner audit details in history without creating fake unit rows.
- **Record:** [[problems/records/2026-08-29_static-sku-cancelled-restock]]

### 2026-08-31 — Static pouch request batch validation
- **What we did:** Static SKU pouch requests no longer require an operator-entered batch code; the backend creates an internal legacy reference while printing continues from Barcode Settings.
- **Record:** [[problems/records/2026-08-31_dept-pouch-request-stale-batch-validation]]

### 2026-08-31 — Barcode sheet requested-item banners
- **What we did:** Barcode sheet banners now identify the requested Honey size/variant or department product instead of generic BATCH A/B text, while supplier/date labels retain their legacy fallback.
- **Record:** [[problems/records/2026-08-31_barcode-sheet-requested-item-banner]]

### 2026-09-15 — Capstone Chapters I–III document
- **What we did:** Created `output/documents/Pyx_Tracker_Capstone_Chapters_I_III.docx` from the supplied title proposal and institutional chapter outline. Included six modules, verified literature, 24 figures, 39 tables, and a dictionary covering 28 principal database tables. Reflected the current photo-only Deliveries and confirmed-session RTS stock-in workflows.
- **Validation:** Updated Word fields and contents; rendered and visually checked the 78-page document; checked source preservation, diagram/table counts, schema dictionary fields, and removal of unrelated sample content. No application code changed.
- **Pending evidence:** Adviser name, actual schedule, physical deployment details, and evaluation results remain explicitly identified for confirmation. Working files and QA evidence are in `output/capstone_work/`.

### 2026-09-16 — Capstone diagram and schedule revision
- **What we did:** Saved a revised capstone DOCX with a top-down six-module FDD, subsystem-boundary UML use cases, and a May–August Gantt chart. Each of the six features has Design and Development, Testing, and Implementation rows; detailed activity allocation is explicitly proposed.
- **Validation:** Updated Word contents and page fields; rendered 81 pages and checked revised figures and page flow. Original delivered DOCX preserved. No application code changed.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_Chapters_I_III_Revised.docx`.

### 2026-09-16 — Consolidated capstone use case diagram
- **What we did:** Replaced the six separate subsystem diagrams with one overall Pyx Tracker use case diagram following the user's new reference: one system boundary, operational actors on both sides, grouped use cases, and include/extend relationships. Renumbered subsequent figures and retained the May–August Gantt and top-down FDD.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_Updated_Use_Case.docx`.

### 2026-09-16 — Five-feature capstone scope and two actors
- **What we did:** Applied the user's explicit removal of POS throughout Chapters I–III. Retained Inventory, Costing/Pricing, Orders/Waybills, Parcel Processing, and RTS. Updated objectives, literature, five-build methodology, May–August Gantt, FDD, IPO, storyboards, schema selection, module list, and tests. The overall use case diagram and matrices use only Admin/Executive and Staff; costing/pricing remains Admin/Executive-only.
- **Validation:** Confirmed five modules and five test scenarios; no POS, cashier, or obsolete six-feature wording in document text. Rendered the 71-page Word document and checked diagram legibility and page flow. No application code or permissions changed.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_Five_Features_Final.docx`.

### 2026-09-16 — Use case direct associations and claim extension
- **What we did:** Replaced shared connector rails with direct actor associations that meet the outer edges of use-case ovals, avoiding crossings through neighboring ovals. Retained two actors and five features. Added optional Update claim status extending Manage RTS case with an explicit Admin/Executive condition, grounded in the existing administrative claim-update action; updated the narrative and RTS matrix.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_Use_Case_Final.docx`. No application code changed.

### 2026-09-17 — FDD-aligned use cases
- **What we did:** Removed numbering from all FDD feature and function boxes. Replaced the use case diagram with the exact twenty named FDD leaf functions, grouped under the five features with Admin/Executive and Staff as the two primary actors. Removed the prior claim-status extension and corresponding diagram-specific text because it is not an FDD function. Preserved existing role-permission matrices, May–August Gantt, and the no-POS scope.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_FDD_Aligned.docx`.

### 2026-09-17 — Main-feature ovals and included FDD subfeatures
- **What we did:** Followed the user's explicit visual convention: two actors connect to main-feature ovals, with twenty dashed include arrows connecting those five features to their exact FDD subfeatures. Retained the unnumbered FDD, no-POS scope, and May–August schedule. The accompanying description identifies the diagram as a feature-level view, rather than a transaction sequence. No unsupported optional extension was added.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_Feature_Subfeature_Use_Case.docx`.

### 2026-09-17 — SQL-derived capstone ERDs
- **What we did:** Refreshed SQL catalog metadata without reading business records. Rebuilt four connected entity-box diagrams for inventory/production, costing, orders/parcels, and returns using actual tables, principal fields, PK/FK labels, and nullability/uniqueness-aware relationship cardinalities. Kept the five-feature scope without POS. Added the costing ERD and renumbered later figures.
- **Evidence:** Enforced foreign keys only; undeclared references are not invented. `rts_inventory_rows.source_line_item_id` is unique, so its child cardinality is zero-or-one. Shared audit links and secondary attributes are omitted for readability and identified as such.
- **Artifact:** `output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx`. No application code or database state changed.
