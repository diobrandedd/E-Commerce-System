 Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | [[specs/2026-08-14_honey-bottle-barcode-inventory]] |
| **Active Plan** | [[plans/active/2026-08-14_honey-bottle-barcode-inventory_plan]] |
| **Status** | `in progress` — Honey bottle barcode inventory (2026-08-14) |

## ðŸš€ Active Feature / Sprint
- **Current Focus:** Manual E2E on dept raw production (Use raw bags â†’ Pouch); run phase 22â€“28 migrations on prod if pending
- **Latest shipped:** [[specs/2026-06-18_costing-parcel-packaging-session]] Â· [[problems/records/2026-06-18_costing-dept-paragis-vs-cacao-brc]] Â· [[problems/records/2026-06-18_costing-hub-parcel-recipes-table]]
- **Agent entry:** [[problems/records/2026-06-12_dept-raw-ux-session]] â€” checklist for next dept raw / PageLayout work

## ðŸ› ï¸ Current Todo List
- [x] **PT v2:** `migration_phase17_pt_supplier_bom.sql`, supplier â‚±/bag, stock-in bagsâ†’pouches (2026-06-05)
- [x] **BRC v2:** supplier â‚±/bag (phase 18); **1 kg BOM** corrected phase 23 (2026-06-11)
- [x] **Jar Cacao BOM:** 220 g/jar, formula 0.22 â€” phase 22 (2026-06-11)
- [x] **Costing UI + custom formula lines** â€” phase 24 (2026-06-11)
- [ ] **Run** phase 33 on prod + `php tools/seed_fulfillment_pack_tiers.php`; stock-in fulfillment SKUs before ship E2E
- [ ] Manual E2E: Paragis â€” stock in bags â†’ Use raw bags â†’ Pouch â†’ verify WIP + finished + packaging deduct
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

