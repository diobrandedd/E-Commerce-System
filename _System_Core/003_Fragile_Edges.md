# Fragile Edges (Manual)

> Curated traps that regex scanners **cannot** reliably detect. Read this before editing shared infrastructure.

See also: [[000_System_Architecture]] · [[generated/110_Include_Graph]] · [[generated/120_Database_Tables]]

## Dual auth systems

| System | Session helper | User table | Used by |
|--------|----------------|------------|---------|
| **IMS (SPA)** | `ims_current_user()` in `includes/auth_check.php` | `users` | `api/v1/*`, React SPA |
| **Legacy RTS** | `rts_auth_user()` in `includes/auth.php` | `app_users` | `api/case.php` (thin wrapper), `api/delivery_*.php` if still called outside SPA |

Do not assume a session from one system works on endpoints built for the other.

**RTS SPA cutover (2026-06-05):** Record / records list / dashboard are React routes (`/rts/record`, `/rts/records`, `/rts/dashboard`). SPA writes use **`api/v1/rts_cases.php`** (IMS + CSRF). Shared logic lives in `includes/rts_case_handlers.php`; `api/case.php` delegates there for any remaining legacy callers. Legacy PHP pages `record.php`, `records.php`, `dashboard.php` redirect to SPA — do not add new SPA features that POST to `api/case.php`.

## `db.php` side effect

Every include of `includes/db.php` also loads `includes/forwarding.php` and may call `rts_forwarding_maybe_redirect()`. CLI scripts and tests can behave differently on localhost vs public/ngrok URLs.

## Reverse include: includes → api/v1

`includes/honey_pricing.php` requires `api/v1/_costing_helpers.php`, which boots `_bootstrap.php` (JSON headers, session). **Do not add new includes → api/v1 requires.** See [[generated/110_Include_Graph#Risky edges (includes → api/v1)]].

## Honey module circular dependency

`honey_inventory.php` ↔ `honey_pricing.php` is broken with lazy load via `honey_inventory_pricing()`. Do not add top-level cross-requires between these files.

## SPA photo uploads (mobile camera)

Full-resolution phone photos (multi‑MB) uploaded on a **Confirm** button are the #1 perceived latency bug in warehouse flows.

- **Always** compress client-side before POST (`compressDeliveryPhoto.ts` — 1280px / JPEG 0.82).
- **Upload at capture**, not on Done — see [[005_SPA_UX_Patterns#Photo upload performance (SPA + API)]].
- **`deliveries_upload.php`:** when `client_hint` is set (scan already gave tracking), do not `file_get_contents` the tmp file — classify from hint only.
- Chaining `upload` → `refreshBatch` → `ship` → `refreshBatch` on one tap multiplies wait — use response `batch_item_count` instead of extra GETs.

## Deliveries delete vs parcel ship state

**Two tables, one workflow (updated 2026-09-11):** `delivery_batch_items` holds the **before-shipment photo** (documentary). CSR `online_order_parcels` is optional for the Photo tab — SPA no longer requires a parcel to save a photo and does **not** call `ship_parcel` / deduct inventory from Add deliveries Photo. RTS before-shipment thumbs resolve by **tracking** on `delivery_batch_items`. On hold / Re-waybill remain parcel-based.

- Deleting only the **delivery row** without reverting parcel left **`shipped`** + deducted stock — rescan showed *"already shipped"*.
- **Now:** `deliveries_delete_item_by_id()` reverts ship + `honey_parcel_revert_deduct()` when **no** active delivery photos remain for that parcel/tracking.
- **Orphan repair:** `parcel_by_tracking` calls `deliveries_repair_orphaned_shipped_parcel()` — `shipped` with zero photos → auto `packing` (fixes rows deleted before this fix).
- **Do not** delete `delivery_batch_items` without considering `online_parcel_deduct_lines` if the parcel was shipped.
- If **other** delivery photos still exist for same tracking, delete does **not** unship.
- **Cancellation scan path:** `api/v1/deliveries.php?action=cancel_parcel_scan` allows `packing` + `shipped` parcels. For `shipped`, it must run `honey_parcel_revert_deduct()` before status change to `cancelled`; otherwise stock stays deducted while parcel is cancelled.
- **Audit requirement:** every scanner cancellation writes `delivery_cancel_events` (previous status, inventory reverted flag, actor). Do not set `online_order_parcels.status = 'cancelled'` directly without the event insert.

## Runtime schema probes

Code adapts SQL when columns are missing. Changing schema without updating probes causes silent behavior changes:

- `rts_db_has_column()` — `includes/rts_schema.php`
- `rts_cases_void_sql()` — void column handling
- `honey_warehouse_has_pricing_columns()` / `honey_warehouse_unit_cost_column()` — pricing columns
- `honey_pos_column_exists()` — POS schema variants
- **Phase 65:** `pos_sales.sold_by_pin_id` is **nullable**. Account POS (`pos.sell`) writes `sold_by_user_id` instead. Do not INSERT PIN-only after this migration for IMS cashiers. Probe `sold_by_user_id` before joining `users` on the Sales list.

After any migration, grep for these helpers and update callers.

## Shared access via waybill

`pdf_to_excel.php` and `order_remarks.php` reuse `waybill_stamper_user_can_access()` / `waybill_stamper_require_access()` from `includes/waybill_stamper.php`. `online_orders_generator.php` delegates to the same helper (includes **`csr_staff`**). Changing waybill access rules affects PDF→Excel, order remarks, and Online Orders unintentionally.

## PhpSpreadsheet 2.x (`online_orders_generator.php`)

Project uses `phpoffice/phpspreadsheet` **^2.0**. Do **not** use removed APIs:

- `getCellByColumnAndRow` / `setCellValueByColumnAndRow` / `getStyleByColumnAndRow`
- `(int) $sheet->getHighestColumn()` — column letters must stay strings; use `Coordinate::columnIndexFromString()`

Use `online_orders_coord($col, $row)` → `$sheet->getCell(...)` / `setCellValue(...)`. Match pattern in `includes/sp_sort_service.php` (`getCell('B4')`).

## Shopee / marketplace PDF item lines

Labels often show size only (e.g. `1 x 1L HONEY w/SF`) with **no Light/Amber/Dark**. Extractor marks these incomplete via `items_variant` in `missing_fields`. SPA must resolve in Shopee SPX carousel (`honeyItemNeedsVariantResolution` in `app/src/app/lib/honeyItemVariant.ts`) before `run_shopee_resolve` — do not treat non-empty `items` as packer-ready.

**SPA confirm + edit:** Resolutions need `confirmed: true` before export. Use `honeyItemLineText()` before any `.trim()` on item fields — API/JSON may send non-strings.

**Review trap:** `ShopeeItemsReviewPanel` must **not** filter to `shopeeRowNeedsItemResolution()` only — users need the **full batch** visible before **Confirm all & build Excel** (fixed 2026-06-05).

**Common bug:** Calling `honeyItemNeedsVariantResolution(row)` instead of `honeyItemNeedsVariantResolution(resolution.items)` → runtime `trim is not a function` because `row` is an object.

## Shopee SPX packing list `product_info`

- **Format:** Shopee export `Packing List.Standard Local_*.xlsx` — sheet 0, columns **`tracking_number`**, **`product_info`**, **`order_sn`** (not the same layout as partner iPYX).
- **Import API:** `online_orders.php?action=spx_import_packing_list` — stash packing list as `excel` token + optional PDF tokens for **names only** (items stay from Excel).
- **Auto-resolve:** `1LITER-Light|Amber|Dark` in `Variation Name` → packer line `1 Liter Honey …` — do not send to carousel.
- **Gallon auto-resolve:** `Product Name` contains `N Gallon` + `Variation Name` is `Light|Amber|Dark` → `{N} Gallon Honey {VARIANT}` using **N from product title**, not Shopee `Quantity:` (e.g. `1 Gallon …` + `Quantity: 2` → `1 Gallon Honey AMBER`). Same for `1 Liter` / `2 Liter` in title vs `Quantity`.
- **Prep Excel download:** `waybill.php?action=stash_download&token=` serves stashed prep workbook without releasing token — offer on SPX/VIP ready screens and on **PDF + Excel stamper** before `run_unified_stamp`.
- **Review edit:** Auto-resolved packing-list rows are not in `needsWork` — `ItemResolverCarousel` must inject `focusPage` row; review edit is **type-only** (no variant presets).
- **Multi-item cell:** One `product_info` cell may list `[1] Product Name:… [2] Product Name:…` — same tracking row, multiple line items. `shopee_spx_split_product_info_blocks()` parses each block; merged packer text uses ` ; ` (matches `honey_parcel_item_parser`).
- **B2T1 trap (two promos):** (1) **1L B2T1** — product title has `1 Liter`, variation `Light & Amber & Dark,350ml` → `3L-HONEY (LAD) + 350ml`; free 350ml still needs `(L|A|D)` suffix via carousel. (2) **350ml B2T1** — product title is `[BUY 2 TAKE 1] 350mL…` (no liter) → `3× 350ml (LAD)` complete, no 3L. Do not use `/LAD/i` alone on variation to infer 3L when product is 350-only.
- **Master Excel:** `order_sn` → `order_no` through `run_shopee_resolve` → column B **Order No.**; PDF name → column C **Name**. PDF-only Shopee path still leaves Order No. blank.
- **Tracking match for names:** `shopee_spx_merge_pdf_names()` uses `online_orders_normalize_tracking()` — same chain as duplicate checks / partner stamp.

## IMS role vs SPA role (API gates)

- **Session stores IMS roles:** `tl`, `staff`, `executive`, `prod_manager` — from `roles.name` at login (`includes/auth_check.php`).
- **SPA displays normalized roles:** `api_normalize_role()` in `api/v1/_bootstrap.php` maps `tl`+CSR dept → `csr_tl`, `staff`+CSR → `csr_staff`. Route guards and Sidebar use normalized names.
- **Trap:** API permission checks that only list `csr_tl` / `csr_staff` will **403** for real CSR users whose session still has `tl` / `staff`.
- **Pattern:** Mirror `waybill_stamper_user_can_access()` — accept normalized names **or** `tl`/`staff` with `online_parcels_user_is_csr_dept()` / dept lookup `departments.name = 'csr'`.
- **Fixed 2026-06-05:** `online_parcels_user_can_access()` in `includes/online_order_parcels.php`.
- **Honey Packaging “Forbidden.” vs dept (2026-09-12):** Packaging scan report was gated on `honey.bottle.request` while the page only needs `honey.view`. That 403 is a **permission key** mismatch (message `Forbidden.`), not department scope (`Out of scope for your department.`). Report/log/options now accept `honey.view` / `honey.ops` / bottle request or scan.
- **Permissions trump home dept (2026-09-12):** Staff/TL Raw Honey lists need `honey.view`/`ops`, not `dept_id=honey`. Cross-dept raw (`paragis` / `cacao_brc` / comb) allowed when home matches **or** the matching `dept.*.view` / honey grant is checked. See [[problems/records/2026-09-12_honey-out-of-scope-permissions]].

## Partner waybill matching (iPYX Excel + Flash OCR)

- **Excel scope:** iPYX partner workbook **first sheet only** (`Today's Order` daily list). Do not scan all sheets — `processor.py` and `online_orders_parse_ipyx_excel_paths()` both use sheet index 0.
- **Stamp matching:** `waybill_stamper_process_uploads()` → `processor.py` builds `orders` dict keyed by `norm_tn()`; each PDF page TN looked up same way.
- **Flash/Lazada P-IDs:** OCR often reads digit **0** as letter **O** (e.g. `P3623KK03MYAD` → `P3623KKO3MYAD`). Both Python `ocr_confusables_for_match()` and PHP `online_orders_ocr_confusables_for_match()` map **`O` → `0`** on `^P[A-Z0-9]{11,17}$` before compare — **not** on Shopee express prefixes (`PH`, `SPEPH`, `SPX`), which legitimately end in letter **O** (e.g. `PH26239152010O`).
- **Normalization chain:** `online_orders_normalize_tracking()` applies OCR fix — keeps duplicate-tracking checks and Excel parse aligned with stamper.
- **Standalone stamper:** Same `processor.py` rules apply to legacy `api/v1/waybill.php?action=process` and Online Orders Partner step.

## VIP / SPX order remarks parser (CSR)

**Read:** [[fixes/vip-spx-order-remarks-parser-guards]] · [[records/2026-06-19_vip-spx-parser-pitfalls]]

- **Size vs variant:** `1 L HONEY` is bottle size — **not** Light (`L`). Use `stripHoneySizeTokens()` before L/A/D detection ([[causes/vip-spx-size-letter-false-variant]]).
- **Honey + dept combos:** `1 x 1 L HONEY: 1 JAR CACAO` must export **both** lines. Do not filter incomplete honey from `composePackerLine`; block export until ItemLineBuilder save on multi-item rows ([[causes/vip-spx-combo-export-drops-honey]]).
- **3L codes:** Keep `VALID_3L` in TS **and** `HONEY_PARCEL_VALID_3L` in PHP in sync — includes **LLA** (2 light + 1 amber), not only LAA ([[causes/vip-spx-3l-variant-list-incomplete]]).
- **250ml / 350ml:** Both are sellable bulk sizes; detect 250 before 350; same L/A/D/LAD rules.
- **J&T concatenated Item Name:** `3L HONEY(ALL DARK)1 PC 350ML(DARK)` has **no separator** before trailing 350ml — split on `\d+ PC 350ML` after main honey block ([[causes/vip-spx-jnt-concatenated-350ml]]).
- **Parser parity:** `itemLineBuilder.ts` + `orderRemarksParse.ts` + `honey_parcel_item_parser.php` must stay aligned (semicolon/`+`/`:` split, promo counts, dept `product_key`).
- **Set B / free 350ml:** Main 3L variant pick is separate from free 350ml pick — `hasFree350Promo()` + `needsFree350Pick()`.

## Waybill item stamp (PDF)

- **Bordered bottom-right (2026-06-19):** `stamp_page()` — white fill + black border (match), red (no match). **All carriers** including SPX: bottom-right; SPX reserves ~42% left width for QR.
- **Fix:** [[fixes/waybill-stamp-bottom-right-border]]
- **OCR O→0** on Flash `P…` IDs still applies via `norm_tn()` — see partner stamp record below.

## CSR carousel “Show completed” trap

`AmbiguousOrderCarousel` and `ItemResolverCarousel` require **parent-controlled** `showCompleted` + `onShowCompletedChange`. Wiring `showCompleted={false}` and `onShowCompletedChange={() => {}}` makes the checkbox appear but do nothing (VIP bug 2026-06-04).

## CSR packaging stock for variant pickers

- **CSR cannot** call `api/v1/honey.php?action=packaging_total_inventory` — `honey_role_can_view` excludes `csr_tl` / `csr_staff`. Use **`online_orders.php?action=packaging_stock`** instead (same snapshot, `online_orders_user_can_access` gate).
- **Counts** = `honey_packaging_rows.current_*` where `batch_no` is set — same scope as packaging dashboard; not warehouse gallons or WIP production.
- **2L/3L presets** display min 1L stock across combo letters — do not sum; packers are limited by the scarcest bottle in the combo.
- **`ItemResolverCarousel` + `cn`:** Stock work once dropped `cn` import while row `className={cn(...)}` remained → Shopee extract white-screen. Keep both imports when editing carousel files.

## Online order parcels + ship deduct

- **`online_order_parcels`** — created on `batch_finalize` from `csr_online_order_lines`; UNIQUE `tracking_number`; parser must pass before insert.
- **Ship** — `api/v1/deliveries.php` `ship_parcel` requires `delivery_item_id` (photo uploaded first); **phase 54:** parse `items_raw` only (`honey_parcel_assert_items_ok`). Honey **and** dept finished stock (Paragis / BRC / Cacao / Comb) are deducted at pack scan (`honey_bottle_pack_session_confirm` + `honey_bottle_pack_session_dept_items`). Fulfillment packaging is **not** deducted at ship. Keep `honey_parcel_apply_deduct()` / `honey_parcel_revert_deduct()` for legacy parcels. Idempotent if already `shipped`.
- **Pack cancel restock** — Deliveries → Cancelled uses product barcode sessions (`pack_cancel_restock_*`, phase 61) to restore `packed` units to `in_stock` + finished qty (unique leftovers). SKU pack lines restock via aggregate credit when implemented; tracking `cancel_parcel_scan` returns **410**. Sealed-box re-waybill remains Hold / Re-waybill (no restock).
- **POS** — Single scan-first terminal at `/pos`: `unified_lookup` resolves **SKU barcodes first** (`product_sku_barcodes`), then leftover unique stickers; SKU lines send `lookup_kind=sku` + `qty` + `sku_id` (no `unit_id`); unique stickers still require `in_stock` unit. Manual `list_unit_price` per line; mixed cart via `pos_unified_checkout`.
- **SKU barcodes (phase 69)** — One permanent short code per product (Barcode Settings). Codes must be globally unique and must not collide with leftover unique stickers. Print = N identical Code128 copies. Stock-in / pack / inventory check: scan → enter qty. New SKU stock-in credits aggregates only — **do not** insert `honey_bottle_units` / `dept_pouch_units`. Lookup order: SKU → unique honey → unique dept.
- **Pack scan draft qty** — `pack_set_item_qty` may change absolute quantity only on SKU session lines (`sku_id` set, no unique `unit_id`). Unique stickers stay one-per-row (remove only). Session `total_qty` is the sum of line quantities for the “In this session” card.
- **Inventory check** — Each user has a **private** open session (`opened_by`). Multiple open sessions may exist the same day. SKU lines upsert per `(event_id, code, batch_no, bottling_date)`; unique leftovers still one row per code. **Batch + bottling date are required documentation** (not used for matching). Compare emits **only audited > 0** keys by product/size; plus expandable `batches[]` docs. **Confirm and update inventory** (admin password) freezes variance history then sets finished honey/dept aggregates to audited and voids in-scope unique stickers not in the audit (SKU-only keys: qty only). Scanning itself never mutates stock. Without `inventory.check.view_all`, history is own sessions only; view_all can merge-select closed history and apply once. → [[specs/2026-09-12_inventory-check-sync-on-confirm]] · [[specs/2026-09-11_inventory-check-batch-docs]] · [[specs/2026-09-11_inventory-check-scoped-compare]].
- **Sales report** — `sale_type = 'parcel'` rows are pack-scan output, reported as **Shipped** (packed for a parcel), never as register revenue. Period filter supports **Pick date** (single day) and **Custom range** via `period=custom` + `from`/`to`. Dept walk-in units come from POS receipt lines via `products.dept_product_key` (so RTS-pool dept sells count); dept shipped units come from `dept_pouch_units` with `consumed_ref_type = 'pack_session'` **plus** SKU pack deduct sale-log qty. Never double-count unique unit rows and SKU qty for the same channel. **Main totals / channel share include packaging + counted RTS scans** (skip bare RTS FIFO mirror rows that duplicate sticker logs). Peso fields require `sales.view_amounts` (page still opens with `sales.view`); API strips money when denied. See [[specs/2026-09-10_sales-view-amounts]]. **Excel export** (`honey_sold_export`): two sheets Shipped + Walk-in; RTS resellable + Packed by columns; header totals match sheet rows — [[fixes/sales-excel-two-sheet-export]].
- **Leak / damage scan** — Unified page `/inventory/leak-damage`: staff pick **Leaked** or **Damaged**, scan any honey or dept sticker → unit `damaged`, finished aggregate −1, row in `finished_unit_leak_damage_log` (phase 63). Non-`in_stock` → **Not in inventory**. Does **not** write RTS pools. Legacy `dept_pouch_defect_scan` delegates here.
- **Daily scan-in reports** — gross historical stock-in is barcode `scanned_at IS NOT NULL`, never `honey_packaging_rows.current_*`, `dept_inventory_rows.current_qty`, or current unit status. Honey groups by packaging row + size; dept groups by request for `pt` / `brc` / `jar_cacao` / `honey_comb`. Do not restore `barcode.status = "scanned"` filters in history queries: a later void/status transition must not erase that the unit was scanned into the system.
- **RTS Record** — Tracking QR/manual first; optional product barcodes (`bottle_codes`) → honey + dept RTS sellable/damaged pools. Not finished packaging stock.
- **Nested transactions trap:** `online_parcels_ship()` wraps ship + deduct in one transaction. `honey_parcel_apply_deduct()` must use **`$ownsTransaction = !$pdo->inTransaction()`** (same as `honey_pos_checkout`) — inner `commit()` otherwise ends the outer transaction and `ship_parcel` fails with **“There is no active transaction”**.
- **Packing photo in parcel UI** — not a column on `online_order_parcels`. Enriched at read time from `delivery_batch_items.photo_filename` (`online_parcels_find_packing_photo` / batch enrich on list). URL via `rts_storage_public_url` → `api/storage_media.php?b=deliveries_documentation`. Modal shows **Not yet packed** when null. Keep link order: `online_parcel_id` first, then tracking fallback.
- **CSR access** — `online_parcels_user_can_access()` must accept IMS `tl`/`staff` in CSR dept, not only SPA labels `csr_tl`/`csr_staff` — see [[003_Fragile_Edges#IMS role vs SPA role (API gates)]].
- **Parser** — `includes/honey_parcel_item_parser.php` must stay aligned with `orderRemarksParse.ts` / Shopee variant rules. **Semicolon/`+` split** per segment — do not apply one honey variant/qty to the whole line when Paragis/Cacao/etc. precede honey. Catalog dept lines → `other_items` with `product_key` (`pt`, `brc`, `jar_cacao`); unknown lines (Pito-Pito, Honey Comb) remain note-only.
- **Ship deduct** — `honey_parcel_apply_deduct()` deducts honey **finished goods** packaging rows, `dept_inventory_rows`, and **fulfillment packaging** (`parcel_fulfillment_apply_deduct` → `online_parcel_fulfillment_deduct_lines`). **Fulfillment packaging** (bubble, cushion, pouches, etc.) consumes **for-packing staging** (`fulfillment_packing_balances.consumed_qty`) after phase 34 — PM must **issue** warehouse stock to staging first; ship does **not** touch warehouse `on_hand` for fulfillment SKUs. Revert on courier cancel decreases staging `consumed_qty` (not warehouse). **On hold + re-waybill:** `hold_parcel` never reverts; `rewaybill` links new TN; re-ship idempotent via deduct lines / `deducted_at`. Opened returns → RTS (separate). Fail-hard if staging insufficient. Recipes from `parcel_pack_costing.php`. Run `migration_phase33` + `migration_phase34` + tier seed before ship. **Sized bubble UI:** balances/damage display in **pc** (`pool_yield_qty` on sized SKUs); DB still stores fractional **rolls** — do not change consume math without updating ship deduct.
- **Parcel pack costing** — `parcel_pack_tiers` keyed by order composition (`honey_parcel_parse_items_string` → **one** matching tier by `sort_order`; **not** additive sum of family tiers). Combo tiers use `family: honey` in DB but UI buckets by `tier_key` — see [[specs/2026-06-18_costing-parcel-packaging-session#Ops departments vs DB family enum]]. Bubble/foam costing: generic warehouse roll WAC ÷ sized `pool_yield_qty`. **Foam:** executive sets **9"×12"** and **12.5"×12"** cuts/roll on generic `Insulation foam roll` detail (not single yield field) — [[fixes/foam-cacao-cut-yields-on-generic-roll]]. Reference landed addon = solo tier per product (`parcel_pack_reference_addon_for_product`). **UI:** Costing hub Products + Parcel recipes modes — [[specs/2026-06-18_costing-parcel-packaging-session]]. Tier editor: `ParcelPackTiersModal`.
- **Parcel pack migrations (Windows)** — SQL seed strings must be **ASCII-only** (`Honey - 1x 1L`, not em dash / ×). Pipe via `Get-Content -Encoding UTF8 -Raw | mysql`. If labels show `???`, run `migration_phase31_parcel_pack_ascii_fix.sql` — [[causes/sql-migration-powershell-utf8-corruption]].
- **`parcel_pack_fetch_tier_lines`** — `array_map` closure must `use ($pdo)` or parcel API returns 500 — [[causes/parcel-pack-closure-missing-pdo-use]].
- **TBD tier preview** — `parcel_pack_preview_tier_by_key()` returns warning + ₱0; do not `activeOnly` throw — [[causes/parcel-tier-inactive-preview-500]].
- **Deliveries auth** — SPA uses IMS (`executive`, `prod_manager` only); legacy `api/delivery_bulk.php` still uses `app_users` submitter id via `deliveries_upload.php`.

## Dept catalog products (PT / BRC / Cacao)

- **Catalog** — single source: `includes/dept_products.php` (`pt`, `brc`, `jar_cacao`). Parser, dashboard, stock-in, costing seed, parcel deduct, and POS must use the same keys.
- **Paragis Tea BOM** — 1 kg leaf pool unit → 500 tea bags @ 2 g → **500÷24** ≈ 20.83 pouches. Costing leaf qty **24÷500 = 0.048** kg/pouch — [[specs/2026-06-05_paragis-tea-bom-math]].
- **PT production WIP** — Raw **kg** on leaf `stock_entries` (`stock_in_unit=kg`); **tea bags** on `dept_wip_rows`; **pouches** on `dept_inventory_rows`. **Use raw bags** / **Pouch** deduct stock-linked formula lines only (custom lines excluded). See [[specs/2026-06-12_paragis-production-wip]] · [[fixes/paragis-use-raw-bags]]. Run `migration_phase26_dept_wip.sql`.
- **PT barcode stock-in deducts the bag pool (2026-09-10)** — Paragis unique/SKU pouch scans credit finished inventory **and** deduct leaf from raw kg (`grams_per_tea_bag × tea_bags_per_pouch`, Settings `pt.*`) when `pt.bag_pool_deduct` is on (default). Insufficient pool **warns** but still stocks in (pool may go negative). Packaging materials are not deducted. WIP Use raw bags / tea-bag produce are hidden. See [[specs/2026-09-10_paragis-bag-pool-pouch-scan]] · [[specs/2026-09-10_bag-pool-deduct-toggle]].
- **`stock_entries.qty` precision (phase 78)** — Must stay **≥6 decimal places** (`DECIMAL(14,6)`). Paragis pouch deduct is **0.048 kg** (48 g / 1000). `DECIMAL(12,2)` rounded that to **0.05** (= 50 g per scan). Do not narrow this column.
- **PT / BRC / Jar Cacao raw stock-in (2026-09-15)** — Ops enter **total kilos received** (`stock_in_unit=kg`). Pool API still uses `bags_on_hand` / `dept_raw_stock_in` field names; **1 unit = 1 kg**. Scan deduct unchanged (grams ÷ `grams_per_bag`, default 1000). Honey Comb stays bag stock-in. → [[problems/records/2026-09-15_raw-stock-in-kilos]]
- **PT raw stock-in** — `dept_raw_stock_in_bags()` records supplier kg (legacy name). `Use raw bags` may track WIP separately; neither is a prerequisite for finished pouch barcode stock-in.
- **Black Rice Coffee BOM** — 1 kg → **1,000÷100 = 10** pouches (defaults). Settings `brc.grams_per_pouch` + `brc.grams_per_bag` (grams per kg of leaf) override scan deduct. Costing formula still uses leaf qty **0.1** kg/pouch + packaging unless separately changed. See [[specs/2026-06-05_brc-bom-math]] · [[specs/2026-09-10_brc-bag-pool-pouch-scan]].
- **BRC barcode stock-in deducts the bag pool (2026-09-10)** — Unique/SKU pouch scans credit finished inventory **and** deduct leaf (`grams_per_pouch` / `grams_per_bag`) when `brc.bag_pool_deduct` is on (default). Short pool **warns** but allows negative. Packaging not deducted on scan. Inventorial Produce is hidden. See [[specs/2026-09-10_brc-bag-pool-pouch-scan]] · [[specs/2026-09-10_bag-pool-deduct-toggle]].
- **Jar Cacao BOM** — 1 kg → **~4.55** sellable jars (`JAR_CACAO_GRAMS_PER_JAR = 220`). Settings `jar_cacao.grams_per_jar` + `jar_cacao.grams_per_bag` override scan deduct. Formula raw qty **0.22** kg/jar + packaging unless separately changed. See [[specs/2026-06-05_jar-cacao-bom-math]] · [[specs/2026-09-10_jar-cacao-bag-pool-jar-scan]].
- **Jar Cacao barcode stock-in deducts the bag pool (2026-09-10)** — Unique/SKU jar scans credit finished inventory **and** deduct leaf (`grams_per_jar` / `grams_per_bag`) when `jar_cacao.bag_pool_deduct` is on (default). Short pool **warns** but allows negative. Packaging not deducted on scan. Inventorial Produce is hidden. See [[specs/2026-09-10_jar-cacao-bag-pool-jar-scan]] · [[specs/2026-09-10_bag-pool-deduct-toggle]].
- **Bag-pool deduct toggles (2026-09-10)** — Settings keys `pt.bag_pool_deduct`, `brc.bag_pool_deduct`, `jar_cacao.bag_pool_deduct` (default **on**). When off, finished stock-in still credits inventory but skips `dept_raw_deduct_bags_allow_negative`. Bag pool strip stays visible with an off note. Do not restore inventorial Produce when off.
- **Dept pricing API** — `supplier_dept_prices` + `SupplierDeptPricesInline` for `paragis` suppliers (PT only today); mirror honey pattern in `includes/dept_pricing.php`.
- **On-hand** — one row per `(dept_id, product_key)`; create-on-first-stock-in.
- **Costing** — seeded 1:1 formula to `* — unit` stock rows; executive sets `selling_price` before POS sell. **Custom formula lines** (`line_label` + fixed ₱, `stock_id` NULL) for labor/shipping — `migration_phase24_formula_custom_lines.sql`; SPA Add formula → Custom cost.

## Product Costing (SPA)

- **Access** — **executive only** (`routes.ts`, `costing.php`, sidebar). HR and prod manager must not reach costing UI or API.
- **Page** — `CostingPage.tsx`: **Products** vs **Parcel recipes** toggle; dept chips Honey / Paragis Tea / BRC & Cacao; Guide → `CostingGuideModal`. Full session knowledge: [[specs/2026-06-18_costing-parcel-packaging-session]].
- **Row grid** — `costingRowGridClass` / `CostingProductMetrics`; column headers per section. Avoid `flex-1` label + right metrics (causes zoom-out layout).
- **Formula add** — stock-linked lines use dept auto-qty + packaging WAC; **Custom cost** for labor/shipping (`api_costing_formula_current_unit_sql` includes NULL `stock_id` lines).
- **Rollup** — [[problems/records/2026-06-11_costing-session-rollup]] (phase 22–24 migrations + UI).
- **Combo SKUs** (PT w/ Honey, etc.) — still out of scope; honey deduct only until combo spec ships.

## Inventory arrangement (material on-hand)

- **Monthly inventory (executive)** — `/inventory/monthly-count` (`inventory.monthly_count`) sets absolute counted stock across honey stages, materials, raw bags, dept, RTS. Mutations require the **administrator override password** from System Settings (`security.admin_override_password_hash` via `includes/admin_override_password.php`). Check inventory Confirm also uses that password for audit-driven finished-stock sync. → [[fixes/monthly-inventory-admin-password]] · [[specs/2026-09-12_inventory-check-sync-on-confirm]]
- **Categories** — `stocks.category`: `raw_material`, `packaging`, `supply`, `costing_only`. Packaging/supplies use **`stocks.on_hand`** (authoritative for those items).
- **Raw Honey gallons** — still **`honey_warehouse_rows`** / `honey_production_rows` / `honey_packaging_rows` stage tables, not `stocks.on_hand`. **SPA:** Raw Materials → **Raw Honey** has in-page tabs (Warehouse / Production / Packaging) at `/inventory/raw-materials/honey/{stage}`; Products → Honey links to the same routes. Do not resurrect `RawHoneySummaryPage` read-only snapshot as the primary entry. See [[fixes/raw-honey-stage-tabs]].
- **Honey production gallon overdraw (2026-09-10)** — Bottle/SKU scan from Production **warns** but **allows** `honey_production_rows.current_qty` to go **negative** (same ops pattern as dept bag pools). Warehouse stock-out still blocks insufficient qty. Lot progress UI uses `current_qty` / `initial_qty` per row. See [[specs/2026-09-10_honey-lot-gallon-progress]].
- **Dept sellables** — still **`dept_inventory_rows`**. Product routes split PT / BRC / Jar Cacao (`/inventory/products/*`).
- **Material requests** — `material_adjustment_requests` + `api/v1/materials.php`; prod_manager submits, executive approves on Requests page. Badge count includes pending material requests in `correction_unread_count`.
- **Packaging production deduct** — `stocks.production_deduct`: `1` = per-unit (auto-deduct on dept produce / honey bottling); `0` = pool-only (manual on-hand, **yield-based costing** via `pool_yield_qty`, never auto-deduct). Spec: [[specs/2026-06-05_packaging-production-deduct]] · `migration_phase27_packaging_bootstrap.sql`.
- **Pool costing math** — line unit cost = `current_price / pool_yield_qty`; line cost = `qty_per_unit ×` that (e.g. 24 tea bags/pouch ÷ 10,000 yield × roll WAC). Executive edits yield on Packaging Materials.
- **Honey bottling packaging** — deducts size-specific `Honey bottle w/ cap - {size}` (ASCII hyphen) + `Honey sticker` from `stocks.on_hand` when Settings `honey.packaging_deduct` is **on** (default). When **off**, bottle stock-in / bottling still credits finished honey and production gallons but skips packaging materials deduct (same idea as bag-pool toggles). Induction/cap seal stay pool-only. Do not use em-dash `—` in SKU names (Windows pipe/import → `???` duplicates). Run `migration_phase28_honey_bottle_dedup.sql` if duplicates appear.
- **Packaging lot stock-in** — executive and HR record purchase lots on Packaging Materials via `materials.php?action=stock_in_lot`: **total price paid** + **quantity received** → unit cost = total ÷ qty; `on_hand` **adds** lot qty (pool). Unit cost is **derived from lot purchase entries** (legacy `set_values` prices must not poison WAC). Price changes call `api_costing_apply_price_change`. **`on_hand` is operational inventory only**; formula qty is `qty_per_unit` per sellable unit.
- **Packaging roles** — **HR** stock-in lot (`stock_in_lot`). **Production manager** deduct for use (`packaging_deduct`). **Executive** all actions + `set_on_hand` + pool yield + extended activity log. Legacy HR `set_on_hand` requests on Requests still exist in API but packaging UI no longer uses them.
- **Supplies pool** — org-wide catalog (`category=supply`, canonical `dept_id` only in DB). **HR + executive** lot stock-in (`stock_in_lot`); WAC via `material_pool_unit_cost_from_lots`. **HR** submits `issue` (requires `requester_name`) or `set_on_hand` on Requests; executive approves → `material_supply_apply_issue` writes `issue: {name} — {reason}`. Run `migration_phase25_supplies_hr_lot.sql`. No prod_manager supply requests. Spec: [[specs/2026-06-12_supplies-hr-lot-inventory]] · [[fixes/supplies-hr-lot-inventory]].
- **Packaging / Supplies SPA** — long lists: scroll **table body only** (stats + headers fixed); detail modal scrolls **activity region only**. Pattern: [[005_SPA_UX_Patterns#Packaging and materials inventory pages]] · [[005_SPA_UX_Patterns#Supplies inventory pages]].
- **Packaging in costing** — `category = packaging` stocks appear in Add formula line under **Packaging materials** optgroup. Canonical price edit for those rows is the Packaging Materials page, not a second path on Costing → Stocks.

## Honey “total inventory” scope

`packaging_total_inventory` counts **`honey_packaging_rows.current_*`** where `batch_no` is non-empty. Do not sum warehouse or production for this dashboard — those stages use gallons, not per-bottle SKUs. Sales charts use `honey_sales_log`; inventory charts use packaging current columns.

## Honey packaging `batch_no` uniqueness

`honey_packaging_rows` uniqueness is **`(batch_no, variant, bottling_date)`** (phase77), not `(batch_no, variant)` alone. The same label (e.g. `JR063026`) may exist on dark and amber, and on different bottling dates. Stock-in (unique stickers **and** SKU) credits/creates the lot for **today’s** bottling date — the picked `packaging_row_id` supplies batch/variant/supplier only; qty lands on today’s dated row via `honey_ensure_packaging_row_for_stock_in`. One production gallon lot may have multiple packaging rows across days (same `production_row_id` + `batch_no`). Sticker codes in `honey_bottle_codes_issued` stay globally unique. Do not restore a global unique on `batch_no` or one-row-per-production (`uq_honey_pk_production_row` dropped in phase77). Use `honey_unique_packaging_batch_no($pdo, $base, $variant, $bottlingDate)` and `honey_ensure_packaging_row_for_stock_in(...)`.

## Online Orders duplicate tracking

- **When it runs:** Before any insert via `online_orders_lines_append()` inside **`online_orders_run_unified_stamp()`** (and manual `lines_append` API). Prep tools (`tool_vip_prepare`, `tool_spx_prepare`, `tool_excel_register`) **do not** append lines — single append point at unified stamp avoids VIP/SPX double-save.
- **Normalize:** Always compare via `online_orders_normalize_tracking()` — includes OCR **O→0** on Flash `P…` IDs (2026-06-05). Same helper as parcel promote (`rts_normalize_tracking_from_qr` + uppercase/strip). Phone-based VIP TNs (e.g. `+639…`) match parcel UNIQUE keys.
- **Sources checked:** (1) `csr_online_order_lines` in non-cancelled batches, (2) `online_order_parcels`, (3) duplicate rows in the current upload payload.
- **API:** `OnlineOrdersDuplicateTrackingException` → 409 + `data.duplicates`. Frontend must parse `e.data.duplicates`, not only `e.message`.
- **Do not** catch `RuntimeException` before `OnlineOrdersDuplicateTrackingException` in pipeline handlers — duplicates would downgrade to 400 without structured payload.

## Online Orders stamp UX (SPA)

**Four tools, no prep → stamper chain.** Tools page cards are independent — VIP / SPX / validate Excel end with **Back to tools** (download only). User must **return to tools** and pick **PDF + Excel stamper** themselves. Do not reintroduce `UnifiedStampStep`, per-pipeline run steps, or “Continue to stamp” CTAs on prep screens.

| Card | Ends with |
|------|-----------|
| VIP Excel arranger | Download iPYX → Back to tools |
| SPX name merge | Download iPYX → Back to tools |
| Validate existing Excel | Download / validate → Back to tools |
| PDF + Excel stamper | `run_unified_stamp` → `stamp_result` |

After stamp: `StampResultPanel` → **Back to tools** or **Done — master Excel**. Stamper is `WaybillStamperTool.tsx` only — not embedded in prep tools.

## Edit-request table whitelist

`api/v1/edit_requests.php` defines `ER_ALLOWED_TABLES`. New editable tables must be added there or executive edit approvals will fail.

## POS discount → honey POS

`includes/pos_discount_requests.php` depends on `includes/honey_pos.php` for catalog and receipt payload. POS discount schema or checkout changes must stay aligned.

## RTS mirror → honey deduct

`rts_inventory_deduct_sell()` in `includes/rts_inventory_sync.php` bridges RTS pool rows into honey packaging deduct when `category=honey`. Changing category values or honey deduct signatures breaks SPA sell flow.

## RTS stock-in review session

RTS sellable stock-in is not an immediate scan mutation. `rts_stock_in_sessions` + items hold the user's draft; only `rts_stock_in_session_confirm()` may move unique units and credit `rts_inventory_rows`, inside one transaction. Cancel must never alter inventory. SKU scan request keys make retries idempotent.

The RTS camera uses `duplicateMode="presentationEdge"`: one decode while visible, then rearm after no-code frames. Do not remove all suppression—camera decoding runs every 120 ms and would multiply a stationary SKU.

## CSR waybill file storage

When `config.php` sets `storage.root` (e.g. `C:/xampp/htdocs/rts_storage`), stashes and outputs for waybill stamper, PDF→Excel, order remarks, and online orders live under:

- `waybill_stamper/{uploads,outputs,stash}`
- `pdf_to_excel/{stash,outputs,work}`
- `order_remarks/{stash,outputs}`
- `online_orders/{outputs,work}`

Resolved via `rts_storage_csr_tool_root()` in `includes/rts_storage.php`. If `storage.root` is empty, legacy paths remain under `rts/storage/`. Downloads still use PHP session tokens (not public URLs).

## Mobile-first SPA traps

- This system is used on warehouse and production phones; do not validate UI only on desktop.
- `PageLayout` header is one row — too many page `actions` buttons will crush title/subtitle on narrow widths. Use compact mobile actions + modal sheet pattern.
- Dense tables (activity logs, stock entries) must have a mobile stacked-card fallback; horizontal 4-column tables are unreadable on 360px screens.
- After mobile-sensitive edits, smoke test at ~360x800 in browser responsive mode before ship.

## Desktop barcode request notifications

Windows toasts use the **browser Notification API** from the open SPA tab (poll every 15s). They do **not** fire if Pyx is fully closed.

- Requires **https** or **localhost**. Plain `http://192.168.x.x` is often blocked.
- User must click **Allow** on the browser prompt (or My Account toggle). `Notification.requestPermission()` needs a click in Chromium.
- Preference is local (`pyx.desktopBarcodeAlerts`); turning the toggle off does not revoke site permission.
- Clicking the toast dispatches `pyx:navigate` — `PageLayout` must listen and `navigate()`.

## Scan fields vs phone software keyboard

Stock-in / pack / POS scan boxes stay focused for USB/Bluetooth **keyboard-wedge** scanners. On Android Chrome, focusing a text input opens Gboard — including **behind** a camera modal, and again after each decode when the page called `.focus()`.

- Camera modals must `blur()` the active field and park focus on a non-input sink (`useDismissSoftwareKeyboard`).
- Scan inputs use `useScanWedgeField`: `inputMode="none"` unless the user taps to type; do **not** refocus while the camera is open.

## Before you edit shared code

1. Run `php tools/memory_bank/impact.php <function_name>` (Windows: `c:\xampp\php\php.exe tools/memory_bank/impact.php <function_name>`)
2. Read the matching module note under [[generated/130_Module_Index]]
3. After merge, regenerate: `php tools/memory_bank/generate_all.php` and log impact in [[002_Memory_Bank]]
