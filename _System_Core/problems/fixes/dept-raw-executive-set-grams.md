---
date: 2026-09-16
type: fix
status: applied
area: dept-raw
tags: [fix]
---

# Fix: Executive leaf pool set by grams

1. `dept_raw_set_grams_on_hand` — convert grams ÷ `grams_per_bag` → kg, call `dept_raw_set_bags_on_hand` with log source `executive_edit`.
2. API `dept_raw_executive_set` — `honey.executive_edit`, no admin password; accepts `grams` (or `bags` kg).
3. SPA: **Edit pool (grams)** on Paragis / BRC / Cacao raw pages via `ExecutiveRawPoolEditModal`.
