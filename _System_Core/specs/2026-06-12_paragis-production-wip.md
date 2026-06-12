---
status: approved
created: 2026-06-12
area: dept-paragis-production
tags: [paragis, pt, wip, production, bom]
---

# Paragis production WIP — tea bags + pouches

## Units

| Stage | Unit | Storage |
|-------|------|---------|
| Warehouse | supplier bag (1 kg) | `stock_entries` on `Paragis leaf — raw` |
| WIP | tea bag (2 g leaf) | `dept_wip_rows` (`wip_unit = tea_bag`) |
| Finished | pouch (24 tea bags) | `dept_inventory_rows` (`product_key = pt`) |

## Actions

1. **Make tea bags** — input: tea bag count. Deduct **leaf only** (`production_deduct = 1`). Yarn / paper roll are pool-only (costing via yield; no deduct). Credit WIP.
2. **Pouch** — input: pouch count. Deduct `PT pouch packaging`; WIP −24×pouches; finished +pouches.
3. **WIP waste** — −tea bags WIP only.
4. **Warehouse waste** — −sealed bags (FIFO).

## Packaging SKUs & formula stages

| Formula stock | Production deduct | Costing qty (per pouch) |
|---------------|-------------------|-------------------------|
| `Paragis leaf — raw` | Make tea bags: `÷ 24 × tea_bags` | 0.0096 bag |
| `PT pouch packaging` | Pouch: `× pouches` | 1 |
| `Paragis Tea Bag Yarn` | No — pool | 24 tea bags (÷ yield for ₱) |
| `PT tea bag paper roll` | No — pool | 24 tea bags (÷ yield for ₱) |
| Custom lines (`stock_id` NULL) | Never | frozen custom price |

See [[specs/2026-06-05_packaging-production-deduct]]. Do not use `PT tea bag sachet` on formula when paper roll is canonical.

## BRC / Jar

Single **produce** step at sell unit; all stock-linked formula lines × qty; no WIP table.
