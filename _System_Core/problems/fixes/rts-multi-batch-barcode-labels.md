---
date: 2026-08-21
type: fix
tags: [rts, barcodes]
---

# Fix: RTS multi-batch barcode labels

- Permission `rts.barcode_labels`; route `/rts/barcode-labels`
- Job tables + honey/dept `source=rts_label` + `rts_label_job_id`
- `rts_barcode_label_job_create` generates all lines in one transaction
- Continuous print: banner (4 slots) + labels pack across the same A4 grid
- After stick: existing honey/dept stock-in (aggregates increment)
