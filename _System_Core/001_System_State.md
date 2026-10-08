 Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | — |
| **Active Plan** | — |
| **Status** | `shipped` - Packaging report damage |

## ðŸš€ Active Feature / Sprint
- **Current Focus:** Manual E2E on dept raw production (Use raw bags â†’ Pouch); run phase 22â€“28 migrations on prod if pending
- **Latest shipped:** [[plans/archive/2026-10-08_packaging-report-damage_plan]] · [[problems/records/2026-10-08_packaging-report-damage]] · [[plans/archive/2026-09-25_exec-dashboard-stock-ins_plan]] · [[problems/records/2026-09-25_exec-dashboard-stock-ins-trends]] · [[specs/2026-09-18_inventory-check-no-batch-docs]] · [[specs/2026-09-17_honey-packaging-pool-no-lots]] · [[problems/records/2026-09-17_honey-packaging-variant-pools]] · [[specs/2026-09-11_deliveries-photo-only]] · [[specs/2026-09-11_inventory-check-batch-docs]] · [[specs/2026-09-11_inventory-check-scoped-compare]] · [[problems/records/2026-09-11_inventory-check-private-sessions]] · [[specs/2026-09-10_sales-view-amounts]]
- **Agent entry:** [[problems/records/2026-06-12_dept-raw-ux-session]] â€” checklist for next dept raw / PageLayout work

## ðŸ› ï¸ Current Todo List
- [x] **PT v2:** `migration_phase17_pt_supplier_bom.sql`, supplier â‚±/bag, stock-in bagsâ†’pouches (2026-06-05)
- [x] **BRC v2:** supplier â‚±/bag (phase 18); **1 kg BOM** corrected phase 23 (2026-06-11)
- [x] **Jar Cacao BOM:** 220 g/jar, formula 0.22 â€” phase 22 (2026-06-11)
- [x] **Costing UI + custom formula lines** â€” phase 24 (2026-06-11)
- [ ] **Run** phase 33 on prod + `php tools/seed_fulfillment_pack_tiers.php`; stock-in fulfillment SKUs before ship E2E
- [ ] Manual E2E: Paragis — stock in bags → scan pouches → verify bag pool grams drop (overdrawn warn OK; no packaging deduct)
- [ ] Manual E2E: PT (~20.83 pouches/bag) + BRC (10 pouches/bag) + Jar Cacao (~4.55 jars/bag) â†’ costing â†’ custom labor line â†’ parcel â†’ POS
- [ ] Manual E2E: RTS SPA â€” create case, append lines, sync inventory, void/claim, dashboard counts
- [ ] Manual E2E: Shopee SPX **packing list Excel + PDF** â€” import â†’ B2T1 350ml variant pick â†’ master Excel has Order No. + Name + Item/s
- [ ] Manual E2E: CSR finalize batch â†’ parcels in `packing` â†’ **prod_manager** Deliveries scan/photo/ship â†’ packaging totals drop
- [ ] Manual E2E: parcel lookup (CSR TL/staff) and idempotent re-scan of shipped TN
- [ ] Manual E2E: Online Orders â€” VIP/SPX prep download only; PDF+Excel stamper; multi-cycle batch finalize
- [ ] Manual E2E: SPX review edit (type item line); gallon/B2T1/multi-item `[1][2]` packing-list rows
- [x] Online Orders prep vs stamper split + SPX product_info parsing (2026-06-05)
- [ ] Manual E2E: duplicate tracking â€” try same TN across pipelines; confirm modal shows existing parcel/line
- [ ] Re-finalize old batches if parcel lines used preâ€“semicolon-split parser (mixed Paragis + honey)
- [x] Shopee SPX packing list import + Order No./Name master Excel merge (2026-06-05)
- [x] Parcel parser semicolon split + other_items UI (2026-06-05)
- [x] Partner Flash waybill match â€” OCR Oâ†’0 on P-IDs; iPYX Excel first sheet only (2026-06-05)
- [x] Online Parcels CSR TL/staff API access â€” IMS `tl`/`staff` + CSR dept gate (2026-06-05)
- [x] CSR variant picker stock indicators â€” Shopee + VIP; translucent scroll chevrons (2026-06-05)
- [x] Online Parcels packing photo â€” Deliveries â†’ modal; â€œNot yet packedâ€ placeholder (2026-06-05)
- [x] Online Orders duplicate tracking prevention (2026-06-05)
- [x] Online Parcels UI + fulfillment motion/hover (2026-06-05)
- [x] Online Orders Generator + QA (2026-06-04)
- [x] Honey packaging total inventory dashboard
- [x] Inventory connection implementation (2026-06-04)
- [x] CSR variant resolution UX â€” confirm, show completed, edit (2026-06-04)

## âš ï¸ Known Blockers / Open Bugs
- **Ops:** Hard-refresh Apache `app/dist/` after SPA deploy.
- **v2:** Courier-cancel parcel â†’ restock inventory (spec out of scope).

