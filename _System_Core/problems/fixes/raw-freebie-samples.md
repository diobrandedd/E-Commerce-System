---
date: 2026-10-08
type: fix
---

# Fix: Freebie grams + pool + modal

1. Settings: `*.freebie_grams` on PT/BRC/Cacao BOM (default 5 g).
2. Tables: `dept_raw_freebie_balances`, `dept_raw_freebie_log`.
3. Create: FIFO leaf deduct + `on_hand += qty`. Give out: `on_hand -= qty`.
4. SPA: Freebies card beside Finished; **+ Freebies** modal Create / Give out / History.
