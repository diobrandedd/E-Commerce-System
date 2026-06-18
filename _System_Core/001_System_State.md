# Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | [[specs/2026-06-18_costing-parcel-packaging-session]] (costing hub + parcel dept buckets) · [[specs/2026-06-17_fulfillment-packing-staging]] (staging) |
| **Active Plan** | — |
| **Status** | `shipped` — costing parcel hub + dept buckets (2026-06-18) |

## 🚀 Active Feature / Sprint
- **Current Focus:** Manual E2E on dept raw production (Use raw bags → Pouch); run phase 22–28 migrations on prod if pending
- **Latest shipped:** [[specs/2026-06-18_costing-parcel-packaging-session]] · [[problems/records/2026-06-18_costing-dept-paragis-vs-cacao-brc]] · [[problems/records/2026-06-18_costing-hub-parcel-recipes-table]]
- **Agent entry:** [[problems/records/2026-06-12_dept-raw-ux-session]] — checklist for next dept raw / PageLayout work

## 🛠️ Current Todo List
- [x] **PT v2:** `migration_phase17_pt_supplier_bom.sql`, supplier ₱/bag, stock-in bags→pouches (2026-06-05)
- [x] **BRC v2:** supplier ₱/bag (phase 18); **1 kg BOM** corrected phase 23 (2026-06-11)
- [x] **Jar Cacao BOM:** 220 g/jar, formula 0.22 — phase 22 (2026-06-11)
- [x] **Costing UI + custom formula lines** — phase 24 (2026-06-11)
- [ ] **Run** phase 33 on prod + `php tools/seed_fulfillment_pack_tiers.php`; stock-in fulfillment SKUs before ship E2E
- [ ] Manual E2E: Paragis — stock in bags → Use raw bags → Pouch → verify WIP + finished + packaging deduct
- [ ] Manual E2E: PT (~20.83 pouches/bag) + BRC (10 pouches/bag) + Jar Cacao (~4.55 jars/bag) → costing → custom labor line → parcel → POS
- [ ] Manual E2E: RTS SPA — create case, append lines, sync inventory, void/claim, dashboard counts
- [ ] Manual E2E: Shopee SPX **packing list Excel + PDF** — import → B2T1 350ml variant pick → master Excel has Order No. + Name + Item/s
- [ ] Manual E2E: CSR finalize batch → parcels in `packing` → **prod_manager** Deliveries scan/photo/ship → packaging totals drop
- [ ] Manual E2E: parcel lookup (CSR TL/staff) and idempotent re-scan of shipped TN
- [ ] Manual E2E: Online Orders — VIP/SPX prep download only; PDF+Excel stamper; multi-cycle batch finalize
- [ ] Manual E2E: SPX review edit (type item line); gallon/B2T1/multi-item `[1][2]` packing-list rows
- [x] Online Orders prep vs stamper split + SPX product_info parsing (2026-06-05)
- [ ] Manual E2E: duplicate tracking — try same TN across pipelines; confirm modal shows existing parcel/line
- [ ] Re-finalize old batches if parcel lines used pre–semicolon-split parser (mixed Paragis + honey)
- [x] Shopee SPX packing list import + Order No./Name master Excel merge (2026-06-05)
- [x] Parcel parser semicolon split + other_items UI (2026-06-05)
- [x] Partner Flash waybill match — OCR O→0 on P-IDs; iPYX Excel first sheet only (2026-06-05)
- [x] Online Parcels CSR TL/staff API access — IMS `tl`/`staff` + CSR dept gate (2026-06-05)
- [x] CSR variant picker stock indicators — Shopee + VIP; translucent scroll chevrons (2026-06-05)
- [x] Online Parcels packing photo — Deliveries → modal; “Not yet packed” placeholder (2026-06-05)
- [x] Online Orders duplicate tracking prevention (2026-06-05)
- [x] Online Parcels UI + fulfillment motion/hover (2026-06-05)
- [x] Online Orders Generator + QA (2026-06-04)
- [x] Honey packaging total inventory dashboard
- [x] Inventory connection implementation (2026-06-04)
- [x] CSR variant resolution UX — confirm, show completed, edit (2026-06-04)

## ⚠️ Known Blockers / Open Bugs
- **Ops:** Hard-refresh Apache `app/dist/` after SPA deploy.
- **v2:** Courier-cancel parcel → restock inventory (spec out of scope).
