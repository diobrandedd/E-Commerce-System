# PDF → Excel Extractor

Integrated into **Pyx Tracker** under **Waybill → PDF to Excel** (`#/waybill/pdf-to-excel`).

## What it does

Reads each page of a Shopee waybill PDF and builds an **iPYX / sir Jono** Excel (same layout Waybill Stamper expects):

| Col | Header | Source |
|-----|--------|--------|
| A | *(row #)* | 1, 2, 3… per waybill page |
| B | Order No. | Left blank (not on label) |
| C | Name | Receiver on label |
| D | Item/s | Item description section |
| E | Note | Left blank |
| F | *(blank)* | Spacer column — always empty |
| G | Courier | Auto (SPEPH/SPX → Shopee Express, JT → J&T) |
| H | Tracking Number | Barcode text on label |

Header row **4**, data from row **6** (rows 1–3 are title/source notes).

Text layer first; OCR fallback when tracking is missing.

## Requirements

Same as Waybill Stamper, plus:

```
pip install pdfplumber
```

Tesseract and Poppler are needed for OCR fallback pages.

## API (authenticated)

- `POST api/v1/pdf_to_excel.php?action=stash` — one PDF per request
- `POST api/v1/pdf_to_excel.php?action=extract` — `{ pdf_tokens: [] }`
- `POST api/v1/pdf_to_excel.php?action=confirm` — `{ job_token }` after UI review
- `GET api/v1/pdf_to_excel.php?action=download&token=...`

## Files

- `extractor.py` — extraction engine
- `auth_gate.php` — redirects legacy `index.php` to the SPA
