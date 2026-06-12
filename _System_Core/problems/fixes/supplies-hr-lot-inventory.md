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
- HR submits **issue** requests with **requester name**; executive approves on **Requests** (`#supply-requests-section`).
- On approve: `on_hand` − qty; audit `issue: {name} — {reason}`.
- New `material_adjustment_requests.action = issue` + `requester_name` column (`migration_phase25_supplies_hr_lot.sql`).
- Removed prod_manager supply add/deduct path.
- HR can create supply catalog items (same as executive for supplies only).
- **Requests hub (2026-06-12):** dedicated supply table; `SupplyRequestReviewModal` (approve / deny with note); bell notifications via `request_unread_list` + `request_mark_viewed`; `SuppliesInventoryGuideModal` + `InventoryRequestsGuideModal`.

## SPA

| Component | Role |
|-----------|------|
| `SuppliesMaterialsPage.tsx` | List + stats + Guide + header Issue request |
| `SuppliesInventoryGuideModal.tsx` | Role-specific supplies workflow |
| `SupplyMaterialDetailModal.tsx` | Detail + API `permissions` actions |
| `SupplyStockInLotModal.tsx` | Lot stock-in |
| `SupplyIssueRequestModal.tsx` | HR issue for staff |
| `SupplyRequestReviewModal.tsx` | Executive review on Requests |
| `InventoryRequestsGuideModal.tsx` | Requests hub role guide |
| `SupplyOnHandSetModal` | Executive set on-hand |

## API

- `supply_detail` (+ `permissions`), `stock_in_lot` (supply category), `set_on_hand` (supply)
- `request_submit`: supply → HR/exec; `issue` \| `set_on_hand`
- `request_unread_list`, `request_mark_viewed` (executive notifications)
- `request_list?category=supply&request_action=issue` (optional server filter; not `action` — that is the API router param)

## Links

- [[specs/2026-06-12_supplies-hr-lot-inventory]]
- [[005_SPA_UX_Patterns#Supplies inventory pages]]
