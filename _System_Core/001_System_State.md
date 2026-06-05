# Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | _(none — last completed: [[specs/2026-06-05_RTS-SPA-Migration]])_ |
| **Active Plan** | _(archived: [[plans/archive/2026-06-05_rts_spa_migration_plan]])_ |
| **Status** | `done` |

## 🚀 Active Feature / Sprint
- **Current Focus:** Manual E2E (RTS SPA migration + inventory connection + CSR pipelines)
- **Latest shipped:** RTS full SPA migration (record, records, dashboard via `api/v1/rts_cases.php`) — see [[002_Memory_Bank]]

## 🛠️ Current Todo List
- [ ] Manual E2E: RTS SPA — create case, append lines, sync inventory, void/claim, dashboard counts
- [ ] Manual E2E: Shopee SPX **packing list Excel + PDF** — import → B2T1 350ml variant pick → master Excel has Order No. + Name + Item/s
- [ ] Manual E2E: CSR finalize batch → parcels in `packing` → **prod_manager** Deliveries scan/photo/ship → packaging totals drop
- [ ] Manual E2E: parcel lookup (CSR TL/staff) and idempotent re-scan of shipped TN
- [ ] Manual E2E: Shopee SPX PDF-only confirm → review → stamp; VIP show completed + edit choice
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
