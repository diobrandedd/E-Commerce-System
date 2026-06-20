---
status: approved
created: 2026-06-18
area: fulfillment-packaging
tags: [bubble-wrap, staging, damage, packaging]
---

# Bubble wrap — unsized warehouse, sized staging, damage reporting

Supplements [[specs/2026-06-17_fulfillment-packing-staging]] and [[specs/2026-06-17_fulfillment-packaging-recipes]].

## Problem (ops reality)

Bubble wrap **rolls arrive and are stocked without a fixed width** in warehouse Packaging Materials. Width is chosen **when TL/PM takes rolls to the packing area**. Parcel recipes and costing still need **13 / 17 / 22 cm** (per packaging spreadsheet).

Today the system has three sized warehouse SKUs (`Bubble wrap roll - 13/17/22 cm`), which implies size is known at stock-in — **wrong for warehouse**.

## Target model

| Layer | Bubble wrap behavior |
|-------|---------------------|
| **Warehouse** (`stocks.on_hand`) | One SKU: **Bubble wrap roll** (no width). HR price + PM stock-in here only. |
| **For packing staging** | Three sized balances: **13 / 17 / 22 cm** (existing SKUs, yields unchanged). Sized SKUs have **0 warehouse on_hand** — staging only. |
| **Issue to packing** | Taking bubble rolls opens a **size assignment form**: rolls qty + width (13/17/22 cm). Deduct **generic** warehouse; credit **sized** staging `issued_qty`. |
| **Ship deduct** | Unchanged — consumes sized staging per parcel recipe (`bubble_piece` → roll fraction via `pool_yield_qty`). |
| **Damaged wrap** | On staging row: report qty + note → increases `damaged_qty`, reduces **remaining** (`issued - consumed - damaged`). Audit log. |
| **Leftover for tomorrow** | **No action** — remaining stays on counter; next issue **adds** to `issued_qty`. Ship deduct tomorrow as normal. |

## Roles

| Action | prod_manager | team_leader (`tl`) | csr_tl | executive |
|--------|--------------|--------------------|--------|-----------|
| Stock-in generic bubble | yes | no | no | yes |
| Issue bubble (size form) | yes | **yes** | **yes** | yes |
| Report staging damage | yes | **yes** | **yes** | yes |
| Other fulfillment issue (cushion, pouch, …) | yes | **yes** | **yes** | yes |

**All TL roles** — dept team leader (`tl` → SPA `team_leader`) and CSR team leader (`csr_tl`). Staff (`staff` / `csr_staff`) do not issue or report damage unless promoted.

Helper: `material_inventory_role_is_tl($role)` → true for `tl`, `csr_tl` (and executive/prod_manager use existing issue gates).

## Bubble issue form (required fields)

When issue line is generic bubble warehouse SKU:

1. **Number of rolls** (qty, roll unit)
2. **Width for packing** — radio/select: **13 cm**, **17 cm**, **22 cm**
3. Optional note (existing issue note)

Backend maps width → sized staging `stock_id` via fixed name map. Issue line log stores both `warehouse_stock_id` (generic) and `staging_stock_id` (sized).

## Damage form

On **For packing** staging table (sized bubble rows + optionally all fulfillment SKUs later):

1. **Qty** (rolls or roll-fraction per SKU unit — rolls for bubble)
2. **Note** (required, max 255)
3. Submit → `damaged_qty += qty`; validate `qty <= remaining`

Damaged qty is **not** ship consumption and **not** returned to warehouse.

## Data changes (phase 38)

1. New warehouse SKU `Bubble wrap roll` (generic, `roll`, yields N/A or 0).
2. Sized `Bubble wrap roll - {13,17,22} cm` — keep for recipes; **not** warehouse stock-in targets.
3. `fulfillment_packing_balances.damaged_qty` DECIMAL default 0.
4. `fulfillment_packing_damage_events` — audit (stock_id, qty, note, reported_by, created_at).
5. `fulfillment_packing_issue_lines` — optional `source_stock_id`, `bubble_width_cm` for bubble split issues (audit).
6. **Migration script**: sum existing warehouse `on_hand` from three sized bubble SKUs → generic SKU; zero sized warehouse on_hand.

## UI

- **Packaging Materials** — generic bubble in list; sized bubble hidden from warehouse filter (or shown as staging-only badge).
- **For packing** — issue modal: detecting generic bubble triggers size sub-form; staging table shows **Damaged** column + **Report damage** action; remaining = issued − consumed − damaged.

## Out of scope

- Auto-detect roll width from physical label OCR.
- Returning damaged wrap to warehouse.
- Changing parcel tier recipes (still 13/17/22 cm from spreadsheet).

## Costing

- **One warehouse price:** HR stock-in on generic `Bubble wrap roll` only (WAC per roll).
- **Parcel pack costing:** sized recipe lines (`bubble_piece` on `Bubble wrap roll - {13,17,22} cm`) use **generic roll WAC ÷ `pool_yield_qty`** — no separate prices on sized SKUs.
- Sized stocks remain for recipes, staging deduct, and yield only.

## Links

- [[specs/2026-06-17_fulfillment-packaging-recipes]]
- [[fixes/packaging-inventory-roles]]
