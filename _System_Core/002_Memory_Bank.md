# Project Change Log & Memory Bank

## 📅 Recent Log Entries

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
