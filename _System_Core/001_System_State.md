# Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | [[specs/Inventory Arrangement change]] |
| **Active Plan** | [[plans/archive/2026-06-05_packaging-price-qty_plan]] (shipped) |
| **Status** | `shipped` — packaging price/qty + costing formula link; manual E2E recommended |

## 🚀 Active Feature / Sprint
- **Current Focus:** Manual E2E packaging set-values (exec/HR) → costing formula line with packaging stock
- **Latest shipped:** Packaging price + qty — [[problems/records/2026-06-05_packaging-price-qty]]

## 🛠️ Current Todo List
- [x] **PT v2:** `migration_phase17_pt_supplier_bom.sql`, supplier ₱/bag, stock-in bags→pouches (2026-06-05)
- [x] **BRC v2:** `migration_phase18_brc_supplier_bom.sql`, black_rice supplier ₱/bag, 20 kg → 200 pouches (2026-06-05)
- [ ] **Run** phase 16–18 migrations on prod if not done
- [ ] Manual E2E: PT (1 bag → ~104 pouches) + BRC (1 bag → 200 pouches) → costing → parcel → POS
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
