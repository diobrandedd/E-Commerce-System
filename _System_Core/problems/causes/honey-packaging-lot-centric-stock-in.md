---
date: 2026-09-17
type: cause
area: honey-packaging
tags: [cause]
---

# Cause: Lot-centric packaging stock-in

Finished honey stock-in required a `production_row_id` (and earlier batch/bottling UX), and live `current_*` lived on dated packaging lot rows. That forced lot pickers and a sprawling Packaging table even though packing/POS only need on-hand by variant and size.
