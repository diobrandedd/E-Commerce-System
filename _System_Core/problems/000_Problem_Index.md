# Problem Index

> Chronological hub for **chat-driven fixes** and small optimizations. Each record is a graph node linking **Problem → Cause → Fix → Files**.

## How to use

| You say in Cursor | AI does |
|-------------------|---------|
| "Save to memory bank" | Create `records/YYYY-MM-DD_slug.md` + cause/fix notes + one-line link in [[002_Memory_Bank]] |
| "Implement X, save to memory bank" | Code + problem record + fragile edge if trap |
| Large feature | Still use [[006_AI_Directed_Engineering]] spec → plan → implement |

## Graph shape

```mermaid
flowchart LR
  problem [Problem record]
  cause [Cause note]
  fix [Fix note]
  files [Files / generated modules]
  fragile [003_Fragile_Edges]
  patterns [005_SPA_UX_Patterns]

  problem --> cause
  problem --> fix
  problem --> files
  fix --> fragile
  fix --> patterns
```

## Reusable fix patterns (inventory & UX)

- [[fixes/packaging-inventory-roles]] — HR stock-in / PM deduct / exec all
- [[fixes/inventory-role-permissions]] — PHP helpers + API `permissions` + SPA guides
- [[fixes/page-guide-button-modal]] — Guide header button + modal (not inline panel)
- [[fixes/page-layout-centered-content]] — `PageLayout` centered column (`contentWidth`)
- [[fixes/paragis-use-raw-bags]] — PT production: bags opened → derived WIP (not tea-bag count)
- [[fixes/dept-raw-material-guides]] — Raw dept pages: Paragis / BRC / Cacao guide modals
- [[fixes/online-parcel-pack-costing]] — Tier-based parcel packaging COGS (separate from product_formula)
- [[fixes/costing-hub-products-and-parcel-recipes]] — Costing hub Products + Parcel recipes tables
- [[fixes/costing-dept-paragis-cacao-brc-buckets]] — Paragis vs BRC&Cacao UI buckets; combo tier visibility
- [[fixes/parcel-tier-bought-liter-cushion-pieces]] — Bought 1L/2L/3L cushion pieces per Excel customize column
- [[fixes/insulation-foam-generic-roll-ship-cut]] — Generic foam roll; cut size at ship from recipe
- [[fixes/parcel-pack-ascii-labels-migration]] — Fix `???` tier labels after Windows SQL import
- [[fixes/raw-dept-mobile-first-actions-and-lists]] — Raw materials page is phone-first: compact header actions + mobile activity cards
- [[fixes/inventory-mobile-action-modal-and-cards]] — Packaging/Supplies mobile consistency: actions modal + stacked card rows
- [[fixes/deliveries-cancel-scan-revert-and-log]] — Cancel scanner path with shipped-inventory revert + cancellation audit table
- [[fixes/vip-spx-order-remarks-parser-guards]] — VIP/SPX: strip size before L/A/D, combo export, 3L LLA, 250ml, J&T concatenated 350ml
- [[fixes/waybill-stamp-text-only-no-border]] — PDF item stamp: text only, no box

## Records (newest first)

- [[records/2026-08-15_universal-barcode-verify]] — Verify barcode page + API for all IMS roles
- [[records/2026-08-15_packaging-scan-log-modal]] — Packaging row click shows who/when scanned stickers in
- [[records/2026-08-15_mobile-header-actions-overflow]] — PageLayout header actions wrap to own row on mobile
- [[records/2026-08-15_honey-batch-label-per-variant]] — honey `batch_no` unique per variant; stickers stay global-unique
- [[records/2026-08-15_dept-pouch-barcodes-cacao]] — Jar Cacao on dept barcode stock-in; Produce retired
- [[records/2026-08-15_dept-pouch-barcodes-black-rice]] — pouch barcodes generalized to `dept_pouch_*`; Black Rice replaces Produce
- [[records/2026-08-15_paragis-pouch-barcode-inventory]] — Paragis pouch barcodes replace Pouch; scan-in no WIP deduct
- [[records/2026-08-14_honey-bottle-barcode-inventory]] — per-bottle barcodes; pack scan deducts; ship is confirm-only
- [[records/2026-06-19_waybill-stamp-text-only]] — stamp text-only, no border box
- [[records/2026-06-19_vip-spx-parser-pitfalls]] — **⭐** VIP/SPX parser + builder session hub
- [[records/2026-06-19_vip-spx-order-interpretation]] — VIP/SPX column detect + ItemLineBuilder initial ship
- [[records/2026-06-18_costing-hub-parcel-recipes-table]] — Costing hub products + parcel recipes tables
- [[records/2026-06-18_insulation-foam-generic-roll]] — generic insulation foam roll + ship-time cut
- [[records/2026-06-18_parcel-tier-bought-cushion-pieces]] — bought liter cushions vs roll columns
- [[records/2026-06-18_costing-sync-and-sku-cleanup]] — phase 39 legacy SKU cleanup + price sync tool
- [[records/2026-06-18_bubble-wrap-staging-damage]] — generic bubble roll + staging damage
- [[records/2026-06-18_cancelled-parcel-rewaybill-semantics]] — cancelled parcels reusable for re-waybill
- [[records/2026-06-16_inventory-mobile-consistency]] — packaging/supplies mobile header + row consistency
- [[records/2026-06-16_deliveries-cancel-scan-log]] — deliveries cancel scanner + inventory restore + log table
- [[records/2026-06-16_raw-dept-mobile-layout]] — raw materials page phone layout and action overflow fix
- [[records/2026-06-05_parcel-pack-ship-bugs]] — **rollup:** 500 closure, UTF-8 ???, TBD preview, modal UX
- [[records/2026-06-05_online-parcel-pack-costing]] — Phase 1 parcel pack tier costing
- [[records/2026-06-12_paragis-use-raw-bags]]
- [[records/2026-06-12_packaging-inventory-role-split]]
- [[records/2026-06-12_pt-make-tea-bags-blocked]]
- [[records/2026-06-12_dept-production-wip-bom]]
- [[records/2026-06-12_dept-raw-finished-split]]
- [[records/2026-06-12_raw-honey-stage-tabs]]
- [[records/2026-06-12_supplies-hr-lot-inventory]]
- [[records/2026-06-05_dept-products-pt-brc-cacao]]
- [[records/2026-06-05_online-orders-prep-stamper-split]]
- [[records/2026-06-05_online-orders-two-phase-remake]]
- [[records/2026-06-05_spx-gallon-variant-qty]]
- [[records/2026-06-05_rts-spa-followup-ux]]
- [[records/2026-06-05_rts-spa-migration]]
- [[records/2026-06-05_edit-requests-submit-server-error]]
- [[records/2026-06-05_deliveries-upload-transaction]]
- [[records/2026-06-05_app-modal-standard]]
- [[records/2026-06-05_shopee-spx-packing-list-import]]
- [[records/2026-06-05_parcel-item-parser-semicolon]]
- [[records/2026-06-05_partner-waybill-flash-no-match]]
- [[records/2026-06-05_online-parcels-csr-forbidden]]
- [[records/2026-06-05_csr-variant-picker-stock]]
- [[records/2026-06-05_online-parcels-packing-photo]]
- [[records/2026-06-05_online-parcels-ui-motion]]
- [[records/2026-06-05_duplicate-tracking-prevention]]
- [[records/2026-06-05_unified-requests-hub]]
- [[records/2026-06-05_deliveries-delete-revert-ship]]
- [[records/2026-06-05_deliveries-save-latency]]
- [[records/2026-06-05_deliveries-sidebar-active-state]]
- [[records/2026-06-05_shopee-spx-review-full-list]]
- [[records/2026-06-05_shopee-spx-tracking-o0]]
- [[records/2026-06-04_csr-variant-resolution-ux]]
- [[records/2026-06-04_honey-packaging-inventory-dashboard]]
- [[records/2026-06-04_csr-waybill-external-storage]]
- [[records/2026-06-04_online-orders-post-ship-qa]]
- [[records/2026-06-04_online-orders-generator]]
- [[records/2026-06-04_inventory-connection]]
- [[records/2026-06-02_pdf-extraction-tracking]]

## Templates

- [[TEMPLATE_Problem_Record]]
- [[TEMPLATE_Cause]]
- [[TEMPLATE_Fix]]

## See also

- [[002_Memory_Bank]] — timeline index (links here)
- [[003_Fragile_Edges]] — promoted traps
- [[006_AI_Directed_Engineering]] — full feature workflow
