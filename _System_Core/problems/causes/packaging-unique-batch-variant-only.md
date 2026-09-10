---
date: 2026-09-10
type: cause
area: honey-packaging
---

# Cause: Packaging unique on batch+variant only

`honey_packaging_rows` unique key was `(batch_no, variant)` (phase59). Find/create paths looked up without `bottling_date`, so stock-in reused the first row and never stamped a new lot date. Phase9 also enforced one packaging row per `production_row_id`.
