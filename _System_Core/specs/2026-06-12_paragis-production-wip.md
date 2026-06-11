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

1. **Make tea bags** — input: tea bag count. Deduct stock-linked formula lines at **make_tea_bags** stage (leaf ÷ 24 per tea bag; tea sachet ÷ 24 per tea bag). Credit WIP. Custom formula lines never deduct.
2. **Pouch** — input: pouch count. Deduct `PT pouch packaging` from formula; WIP −24×pouches; finished +pouches.
3. **WIP waste** — −tea bags WIP only.
4. **Warehouse waste** — −sealed bags (FIFO).

## Packaging SKUs & formula stages

| Formula stock | Production stage | Qty scale (formula is per pouch) |
|---------------|------------------|----------------------------------|
| `Paragis leaf — raw` | Make tea bags | `qty_per_unit ÷ 24 × tea_bags` |
| Tea-bag packaging (`PT tea bag sachet`, `Paragis Tea Bag Yarn`, any packaging except pouch) | Make tea bags | `qty_per_unit ÷ 24 × tea_bags` |
| `PT pouch packaging` | Pouch | `qty_per_unit × pouches` |
| Custom lines (`stock_id` NULL) | — | never deduct |

Avoid duplicate tea-bag packaging lines on the same formula (e.g. sachet **and** yarn).

## BRC / Jar

Single **produce** step at sell unit; all stock-linked formula lines × qty; no WIP table.
