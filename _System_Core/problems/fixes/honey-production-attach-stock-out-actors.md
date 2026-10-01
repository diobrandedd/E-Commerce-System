---
date: 2026-10-01
type: fix
area: honey-production
tags: [fix]
---

# Fix: Attach latest WH→production mover on lot payloads

`honey_production_lots_attach_stock_out_actors` loads the latest `honey_warehouse_stock_out_events` row per `production_row_id` (MAX id), joins `users` for display name, falls back to production row `created_by` + `transferred_at`/`created_at`. Wired into `honey_production_variant_summary` and `honey_production_voided_list`. SPA shows “Moved by Name · when” on expanded lots and Voided rows.

→ Problem: [[records/2026-10-01_honey-production-stock-out-actor]]
→ Fragile: [[003_Fragile_Edges#Honey production]]
