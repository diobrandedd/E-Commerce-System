---
type: cause
tags: [cause, pdf-to-excel, tracking]
---

# Cause: PDF tracking extraction preferred noisy P… OCR over real numeric TN

## What went wrong

First-match regex on OCR pages surfaced garbage `P…` strings before Lazada numeric `8201…` tracking. No priority ranking or marketplace ID validation.

## Where in code

- `pdf-to-excel/extractor.py` — `find_tracking()`, `TN_PATTERNS`

## Related problems

- [[records/2026-06-02_pdf-extraction-tracking]]
