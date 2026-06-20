---
type: fix
tags: [fix, pdf-to-excel, tracking]
---

# Fix: Priority-based tracking patterns + marketplace ID validation

## What we changed

Priority tuples for TN patterns; high-confidence `8201…` first; `_valid_marketplace_parcel_id()` rejects OCR garbage P-IDs; best-priority valid match wins.

## Related problems

- [[records/2026-06-02_pdf-extraction-tracking]]
