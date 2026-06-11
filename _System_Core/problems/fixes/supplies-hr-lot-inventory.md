---
date: 2026-06-12
type: fix
area: supplies-inventory
tags: [supplies, hr, lot, wac, issue]
---

# Fix: Supplies HR lot inventory

## What changed

- Org-wide supplies catalog (no dept picker); canonical `dept_id` in DB only.
- `SuppliesMaterialsPage` mirrors packaging UX: stats, scrollable table, detail modal.
- HR + executive **stock-in lot** → WAC `current_price` per pc (shared `material_pool_unit_cost_from_lots`).
- HR submits **issue** requests with **requester name**; executive approves on Requests.
- On approve: `on_hand` − qty; audit `issue: {name} — {reason}`.
- New `material_adjustment_requests.action = issue` + `requester_name` column (`migration_phase25_supplies_hr_lot.sql`).
- Removed prod_manager supply add/deduct path.
- HR can create supply catalog items (same as executive for supplies only).

## SPA

| Component | Role |
|-----------|------|
| `SuppliesMaterialsPage.tsx` | List + stats |
| `SupplyMaterialDetailModal.tsx` | Detail + actions |
| `SupplyStockInLotModal.tsx` | Lot stock-in |
| `SupplyIssueRequestModal.tsx` | HR issue for staff |
| `SupplyOnHandSetModal` / `SupplyOnHandRequestModal` | Exec / HR count |

## API

- `supply_detail`, `stock_in_lot` (supply category), `set_on_hand` (supply)
- `request_submit`: supply → HR only; `issue` \| `set_on_hand`

## Links

- [[specs/2026-06-12_supplies-hr-lot-inventory]]
- [[005_SPA_UX_Patterns#Supplies inventory pages]]
