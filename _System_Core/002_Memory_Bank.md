# Project Change Log & Memory Bank

> **Timeline index.** New entries link to [[problems/000_Problem_Index|Problem records]] (graph nodes: Problem → Cause → Fix → Files). Say **"save to memory bank"** in Cursor after chat-driven fixes.

## 📅 Recent Log Entries

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
