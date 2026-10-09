# 📦 Waybill Stamper — Setup Guide
## Finest Wellness PH | XAMPP Windows

---

## WHAT IT DOES
- Upload PDF waybills (TikTok, Lazada, Shopee) + iPYX Excel order sheets
- System scans each waybill page for its tracking number
  → Text layer first (fast), OCR fallback for image-only pages
- Matches tracking number to Excel col H (Tracking Number)
- Stamps the Item/s (col D) directly onto the waybill PDF
- Downloads a new stamped PDF ready to use

---

## REQUIREMENTS

### 1. Python 3 (with PATH)
https://www.python.org/downloads/
✅ Check "Add Python to PATH" during install

### 2. Python libraries — open Command Prompt and run:
```
pip install pdfplumber openpyxl PyMuPDF pytesseract pdf2image
```

`pdfplumber` is required for **PDF → Excel** (Waybill sidebar). PyMuPDF is used by **Waybill Stamper**.

### 3. Tesseract OCR (for Lazada/Shopee image-only pages)
Download: https://github.com/UB-Mannheim/tesseract/wiki
→ Install to: `C:\Program Files\Tesseract-OCR\` (default — the app auto-detects this path)
→ "Add to PATH" is optional (Apache does not always inherit your user PATH)

If you install elsewhere, copy `paths.sample.json` to `paths.json` and set full paths.

Verify: `tesseract --version` in CMD, or check that this file exists:
`C:\Program Files\Tesseract-OCR\tesseract.exe`

### 4. Poppler for Windows (needed by pdf2image)
Download: https://github.com/oschwartz10612/poppler-windows/releases
→ Extract to: C:\poppler\
→ Add C:\poppler\Library\bin to your Windows PATH

### Verify everything works — run in CMD:
```
python --version
tesseract --version
python -c "import fitz, openpyxl, pytesseract, pdf2image; print('All OK')"
```

**Multiple Pythons on Windows:** the app prefers `python` (with `openpyxl` installed) over `py -3`. If you recently installed Python 3.14+, `pip install …` may have gone into one interpreter while Apache still saw another. Always install with the same launcher you verified above, e.g. `python -m pip install …` (or `py -3.12 -m pip install …`).

---

## PHP upload limits (Pyx Tracker SPA)

The app uploads **one file at a time** (then processes), so you are not limited by a single 40 MB `post_max_size` on the whole batch. Each file can be up to ~60 MB.

If a **single PDF** is huge, raise in `C:\xampp\php\php.ini` then restart Apache:

```
upload_max_filesize = 64M
post_max_size = 68M
max_execution_time = 300
```

(`api/v1/.htaccess` also sets these for the API folder when Apache allows it.)

---

## INSTALLATION

1. Copy the `waybill-stamper` folder to:
   ```
   C:\xampp\htdocs\waybill-stamper\
   ```

2. Make sure Apache is running in XAMPP Control Panel

3. Open browser → http://localhost/waybill-stamper/

---

## PDF → EXCEL (no worker Excel)

When workers send **PDF waybills only**, use **Waybill → PDF to Excel** in Pyx Tracker:

1. Upload Shopee waybill PDF(s)
2. Review extracted rows (tracking, name, item/s per page)
3. Confirm and download the generated iPYX Excel (one file per PDF)
4. Upload the **same PDF(s)** plus the Excel in **Waybill Stamper** below

Legacy standalone URL `pdf-to-excel/` redirects to the SPA when logged in.

---

## HOW TO USE (Waybill Stamper)

1. Drop your **PDF waybills** (one or multiple) into the left zone
2. Drop your **iPYX Excel order sheet(s)** into the right zone
3. Click **⚡ Stamp Waybills**
4. Wait — OCR pages take ~2–3 seconds each
5. Download the stamped PDF

**Stamp placement:** One line at the bottom-right (e.g. `ITEMS: 1 x 1L HONEY w/SF`) so the **bottom-left QR** on SPX/Shopee labels stays visible.

---

## EXCEL FORMAT EXPECTED (iPYX / sir Jono)
- Row 4 = blue header row
- Row 5 = blank separator
- Data from row 6 onward
- Col A = line number (1, 2, 3…)
- Col B = Order No.
- Col C = Name
- Col D = Item/s  ← stamped onto the waybill
- Col E = Note
- Col F = blank spacer (no header text)
- Col G = Courier
- Col H = Tracking Number  ← matched against PDF tracking numbers

**PDF → Excel** writes this layout automatically so Stamper can read the file as-is.

The system reads ALL sheets automatically (Today Orders, YesterdayPendings, etc.)
and skips utility sheets (Template, Pito-Pito, Inventory).

---

## SUPPORTED PLATFORMS & TRACKING FORMATS
| Platform  | Tracking Format     | Extraction Method |
|-----------|---------------------|-------------------|
| TikTok    | JT001848...         | Text layer ⚡     |
| Lazada    | 820144974112        | OCR 🔍            |
| Shopee    | SPEPH… / SPX… / PH… | Text layer ⚡     |
| Flash / marketplace | P0432KFQNC1AC (parcel ID) or FE… | Text layer ⚡ |
| Lazada/J&T | 820… / JT…         | Text / OCR        |

---

## TROUBLESHOOTING

**shell_exec disabled**
- Open C:\xampp\php\php.ini
- Find: disable_functions
- Remove shell_exec from the list
- Restart Apache

**OCR not working / wrong text**
- Make sure Tesseract is in PATH
- Try: tesseract --version in CMD

**pdf2image fails**
- Make sure Poppler bin folder is in Windows PATH

**Slow processing**
- OCR pages take ~2–3s each — a 100-page batch with 70 OCR pages ≈ 3–4 minutes
- Normal! The progress bar shows each step

---

## FILE STRUCTURE
```
waybill-stamper/
├── index.php       ← Web interface
├── processor.py    ← PDF + Excel engine
├── uploads/        ← Temp (auto-cleaned)
├── outputs/        ← Stamped PDFs (auto-deleted after download)
└── SETUP.md        ← This file
```
