# Current System State

> **AI-directed engineering:** point agents at your active spec + plan. Workflow: [[006_AI_Directed_Engineering]]

## Active initiative

| Field | Link |
|-------|------|
| **Active Spec** | _(none — last completed: [[specs/Inventory Connection to Everything]])_ |
| **Active Plan** | _(archived: [[plans/archive/2026-06-04_Inventory-Connection-to-Everything_plan]])_ |
| **Status** | `done` |

## 🚀 Active Feature / Sprint
- **Current Focus:** Manual E2E + ops verification
- **Latest shipped:** Inventory connection — parcels registry, deliveries SPA, ship-time honey deduct — see [[002_Memory_Bank]]

## 🛠️ Current Todo List
- [ ] Manual E2E: CSR finalize batch → parcels in `packing` → prod deliveries scan/photo/ship → packaging totals drop
- [ ] Manual E2E: parcel lookup (CSR TL/staff) and idempotent re-scan of shipped TN
- [x] Online Orders Generator + QA (2026-06-04)
- [x] Honey packaging total inventory dashboard
- [x] Inventory connection implementation (2026-06-04)

## ⚠️ Known Blockers / Open Bugs
- **Ops:** Hard-refresh Apache `app/dist/` after SPA deploy.
- **v2:** Courier-cancel parcel → restock inventory (spec out of scope).
