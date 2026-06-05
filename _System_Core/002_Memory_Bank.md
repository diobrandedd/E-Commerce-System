# Project Change Log & Memory Bank

## 📅 Recent Log Entries

### 2026-06-05 - Partner waybill stamp fixes (Flash NO MATCH + iPYX first sheet only)
- **Symptoms (E2E):** Partner stamp showed **NO MATCH** on Flash/Lazada waybill `P3623KK03MYAD` even though row 1 of iPYX Excel had the order. Error banner displayed `P3623KKO3MYAD` (letter **O** instead of digit **0**). Extra/wrong rows also appeared because the parser scanned **all workbook sheets**.
- **Root causes:**
  1. **OCR 0↔O** — `waybill-stamper/processor.py` OCR/text layer read `0` as `O` on marketplace `P…` parcel IDs; Excel had correct `P3623KK03MYAD`.
  2. **All sheets** — `processor.py` `parse_excel()` iterated every sheet; `online_orders_parse_ipyx_excel_paths()` used `getAllSheets()`. iPYX daily workbook: **only sheet 1** is today's orders; other sheets add noise or overwrite keys.
- **Fixes:**
  - **`norm_tn()` / `ocr_confusables_for_match()`** in `waybill-stamper/processor.py` — for `P[A-Z0-9]+` IDs, map **`O` → `0`** before lookup.
  - **`online_orders_ocr_confusables_for_match()`** in `includes/online_orders_generator.php` — same rule inside **`online_orders_normalize_tracking()`** (duplicate checks + Excel keys stay aligned).
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
  - New **`review`** phase: table of PDF vs final item → **Confirm all & build Excel**.
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
