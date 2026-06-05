# Fragile Edges (Manual)

> Curated traps that regex scanners **cannot** reliably detect. Read this before editing shared infrastructure.

See also: [[000_System_Architecture]] · [[generated/110_Include_Graph]] · [[generated/120_Database_Tables]]

## Dual auth systems

| System | Session helper | User table | Used by |
|--------|----------------|------------|---------|
| **IMS (SPA)** | `ims_current_user()` in `includes/auth_check.php` | `users` | `api/v1/*`, React SPA |
| **Legacy RTS** | `rts_auth_user()` in `includes/auth.php` | `app_users` | `record.php`, `api/case.php`, `api/delivery_*.php`, legacy pages |

Do not assume a session from one system works on endpoints built for the other.

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

**Two tables, one workflow:** `delivery_batch_items` (packing photo) and `online_order_parcels` (CSR parcel, `packing` → `shipped` on `ship_parcel`).

- Deleting only the **delivery row** without reverting parcel left **`shipped`** + deducted stock — rescan showed *"already shipped"*.
- **Now:** `deliveries_delete_item_by_id()` reverts ship + `honey_parcel_revert_deduct()` when **no** active delivery photos remain for that parcel/tracking.
- **Orphan repair:** `parcel_by_tracking` calls `deliveries_repair_orphaned_shipped_parcel()` — `shipped` with zero photos → auto `packing` (fixes rows deleted before this fix).
- **Do not** delete `delivery_batch_items` without considering `online_parcel_deduct_lines` if the parcel was shipped.
- If **other** delivery photos still exist for same tracking, delete does **not** unship.

## Runtime schema probes

Code adapts SQL when columns are missing. Changing schema without updating probes causes silent behavior changes:

- `rts_db_has_column()` — `includes/rts_schema.php`
- `rts_cases_void_sql()` — void column handling
- `honey_warehouse_has_pricing_columns()` / `honey_warehouse_unit_cost_column()` — pricing columns
- `honey_pos_column_exists()` — POS schema variants

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
- **B2T1 trap:** `Light & Amber & Dark,350ml` → line `3L-HONEY (LAD) + 350ml`. **`LAD` alone must not mark row complete** — free 350ml still needs `(L|A|D)` suffix. PHP: `shopee_spx_items_need_variant_resolution()`; SPA: `honeyItemNeedsVariantResolution()` + `shopeePresetToResolvedItemsLine()` on confirm.
- **Master Excel:** `order_sn` → `order_no` through `run_shopee_resolve` → column B **Order No.**; PDF name → column C **Name**. PDF-only Shopee path still leaves Order No. blank.
- **Tracking match for names:** `shopee_spx_merge_pdf_names()` uses `online_orders_normalize_tracking()` — same chain as duplicate checks / partner stamp.

## IMS role vs SPA role (API gates)

- **Session stores IMS roles:** `tl`, `staff`, `executive`, `prod_manager` — from `roles.name` at login (`includes/auth_check.php`).
- **SPA displays normalized roles:** `api_normalize_role()` in `api/v1/_bootstrap.php` maps `tl`+CSR dept → `csr_tl`, `staff`+CSR → `csr_staff`. Route guards and Sidebar use normalized names.
- **Trap:** API permission checks that only list `csr_tl` / `csr_staff` will **403** for real CSR users whose session still has `tl` / `staff`.
- **Pattern:** Mirror `waybill_stamper_user_can_access()` — accept normalized names **or** `tl`/`staff` with `online_parcels_user_is_csr_dept()` / dept lookup `departments.name = 'csr'`.
- **Fixed 2026-06-05:** `online_parcels_user_can_access()` in `includes/online_order_parcels.php`.

## Partner waybill matching (iPYX Excel + Flash OCR)

- **Excel scope:** iPYX partner workbook **first sheet only** (`Today's Order` daily list). Do not scan all sheets — `processor.py` and `online_orders_parse_ipyx_excel_paths()` both use sheet index 0.
- **Stamp matching:** `waybill_stamper_process_uploads()` → `processor.py` builds `orders` dict keyed by `norm_tn()`; each PDF page TN looked up same way.
- **Flash/Lazada P-IDs:** OCR often reads digit **0** as letter **O** (e.g. `P3623KK03MYAD` → `P3623KKO3MYAD`). Both Python `ocr_confusables_for_match()` and PHP `online_orders_ocr_confusables_for_match()` map **`O` → `0`** on `^P[A-Z0-9]{11,17}$` before compare — **not** on Shopee express prefixes (`PH`, `SPEPH`, `SPX`), which legitimately end in letter **O** (e.g. `PH26239152010O`).
- **Normalization chain:** `online_orders_normalize_tracking()` applies OCR fix — keeps duplicate-tracking checks and Excel parse aligned with stamper.
- **Standalone stamper:** Same `processor.py` rules apply to legacy `api/v1/waybill.php?action=process` and Online Orders Partner step.

## CSR carousel “Show completed” trap

`AmbiguousOrderCarousel` and `ItemResolverCarousel` require **parent-controlled** `showCompleted` + `onShowCompletedChange`. Wiring `showCompleted={false}` and `onShowCompletedChange={() => {}}` makes the checkbox appear but do nothing (VIP bug 2026-06-04).

## CSR packaging stock for variant pickers

- **CSR cannot** call `api/v1/honey.php?action=packaging_total_inventory` — `honey_role_can_view` excludes `csr_tl` / `csr_staff`. Use **`online_orders.php?action=packaging_stock`** instead (same snapshot, `online_orders_user_can_access` gate).
- **Counts** = `honey_packaging_rows.current_*` where `batch_no` is set — same scope as packaging dashboard; not warehouse gallons or WIP production.
- **2L/3L presets** display min 1L stock across combo letters — do not sum; packers are limited by the scarcest bottle in the combo.
- **`ItemResolverCarousel` + `cn`:** Stock work once dropped `cn` import while row `className={cn(...)}` remained → Shopee extract white-screen. Keep both imports when editing carousel files.

## Online order parcels + ship deduct

- **`online_order_parcels`** — created on `batch_finalize` from `csr_online_order_lines`; UNIQUE `tracking_number`; parser must pass before insert.
- **Ship** — `api/v1/deliveries.php` `ship_parcel` requires `delivery_item_id` (photo uploaded first); uses `honey_pos_deduct_packaging_aggregate()` via `honey_parcel_apply_deduct()`; idempotent if already `shipped`.
- **Nested transactions trap:** `online_parcels_ship()` wraps ship + deduct in one transaction. `honey_parcel_apply_deduct()` must use **`$ownsTransaction = !$pdo->inTransaction()`** (same as `honey_pos_checkout`) — inner `commit()` otherwise ends the outer transaction and `ship_parcel` fails with **“There is no active transaction”**.
- **Packing photo in parcel UI** — not a column on `online_order_parcels`. Enriched at read time from `delivery_batch_items.photo_filename` (`online_parcels_find_packing_photo` / batch enrich on list). URL via `rts_storage_public_url` → `api/storage_media.php?b=deliveries_documentation`. Modal shows **Not yet packed** when null. Keep link order: `online_parcel_id` first, then tracking fallback.
- **CSR access** — `online_parcels_user_can_access()` must accept IMS `tl`/`staff` in CSR dept, not only SPA labels `csr_tl`/`csr_staff` — see [[003_Fragile_Edges#IMS role vs SPA role (API gates)]].
- **Parser** — `includes/honey_parcel_item_parser.php` must stay aligned with `orderRemarksParse.ts` / Shopee variant rules. **Semicolon/`+` split** per segment — do not apply one honey variant/qty to the whole line when Paragis/Cacao/etc. precede honey. Non-honey → `other_items` only (no deduct).
- **Deliveries auth** — SPA uses IMS (`executive`, `prod_manager` only); legacy `api/delivery_bulk.php` still uses `app_users` submitter id via `deliveries_upload.php`.

## Honey “total inventory” scope

`packaging_total_inventory` counts **`honey_packaging_rows.current_*`** where `batch_no` is non-empty. Do not sum warehouse or production for this dashboard — those stages use gallons, not per-bottle SKUs. Sales charts use `honey_sales_log`; inventory charts use packaging current columns.

## Online Orders duplicate tracking

- **When it runs:** Before any insert via `online_orders_lines_append()` and pre-flight in `run_partner_stamp` / `run_shopee_resolve`. VIP lines saved once at **export** (`lines_append` in `VipRunStep`); **VIP stamp does not append again** (2026-06-05 fix — previously double-saved same TN).
- **Normalize:** Always compare via `online_orders_normalize_tracking()` — includes OCR **O→0** on Flash `P…` IDs (2026-06-05). Same helper as parcel promote (`rts_normalize_tracking_from_qr` + uppercase/strip). Phone-based VIP TNs (e.g. `+639…`) match parcel UNIQUE keys.
- **Sources checked:** (1) `csr_online_order_lines` in non-cancelled batches, (2) `online_order_parcels`, (3) duplicate rows in the current upload payload.
- **API:** `OnlineOrdersDuplicateTrackingException` → 409 + `data.duplicates`. Frontend must parse `e.data.duplicates`, not only `e.message`.
- **Do not** catch `RuntimeException` before `OnlineOrdersDuplicateTrackingException` in pipeline handlers — duplicates would downgrade to 400 without structured payload.

## Online Orders stamp UX (SPA)

After any pipeline stamp, **`OnlineOrdersGeneratorPage` holds `stampResult`** and renders `StampResultPanel` until the user explicitly continues. Do not call `setStep("post_run")` on stamp API success — only on “Done — master Excel” or “Process another pipeline”.

## Edit-request table whitelist

`api/v1/edit_requests.php` defines `ER_ALLOWED_TABLES`. New editable tables must be added there or executive edit approvals will fail.

## POS discount → honey POS

`includes/pos_discount_requests.php` depends on `includes/honey_pos.php` for catalog and receipt payload. POS discount schema or checkout changes must stay aligned.

## RTS mirror → honey deduct

`rts_inventory_deduct_sell()` in `includes/rts_inventory_sync.php` bridges RTS pool rows into honey packaging deduct when `category=honey`. Changing category values or honey deduct signatures breaks SPA sell flow.

## CSR waybill file storage

When `config.php` sets `storage.root` (e.g. `C:/xampp/htdocs/rts_storage`), stashes and outputs for waybill stamper, PDF→Excel, order remarks, and online orders live under:

- `waybill_stamper/{uploads,outputs,stash}`
- `pdf_to_excel/{stash,outputs,work}`
- `order_remarks/{stash,outputs}`
- `online_orders/{outputs,work}`

Resolved via `rts_storage_csr_tool_root()` in `includes/rts_storage.php`. If `storage.root` is empty, legacy paths remain under `rts/storage/`. Downloads still use PHP session tokens (not public URLs).

## Before you edit shared code

1. Run `php tools/memory_bank/impact.php <function_name>` (Windows: `c:\xampp\php\php.exe tools/memory_bank/impact.php <function_name>`)
2. Read the matching module note under [[generated/130_Module_Index]]
3. After merge, regenerate: `php tools/memory_bank/generate_all.php` and log impact in [[002_Memory_Bank]]
