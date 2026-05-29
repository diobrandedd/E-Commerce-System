#!/usr/bin/env python3
"""
Waybill Stamper — Finest Wellness PH
Scans each waybill page for a tracking number (text layer first, OCR fallback),
matches it to the Excel order sheet by tracking number,
then stamps the item/s onto the waybill PDF.
"""

import sys, re, json, argparse
from pathlib import Path

import fitz            # PyMuPDF  — PDF read + stamp


def console_safe(text: str) -> str:
    """Avoid UnicodeEncodeError on Windows cp1252 (Apache/CMD)."""
    out = str(text)
    for src, dst in (
        ('\u2192', '->'),
        ('\u2014', '-'),
        ('\u2013', '-'),
        ('\u2026', '...'),
        ('\u2713', '[OK]'),
        ('\u2717', '[X]'),
    ):
        out = out.replace(src, dst)
    enc = getattr(sys.stdout, 'encoding', None) or 'utf-8'
    try:
        return out.encode(enc, errors='replace').decode(enc, errors='replace')
    except (LookupError, TypeError):
        return out.encode('ascii', errors='replace').decode('ascii')
import openpyxl
import pytesseract
from pdf2image import convert_from_path
from PIL import Image

# Poppler path for pdf2image on Windows (Apache often has a minimal PATH).
POPPLER_PATH: str | None = None


def configure_external_tools() -> None:
    """Point pytesseract/pdf2image at common XAMPP Windows install locations."""
    global POPPLER_PATH

    if sys.platform == 'win32':
        for tess in (
            Path(r'C:\Program Files\Tesseract-OCR\tesseract.exe'),
            Path(r'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe'),
        ):
            if tess.is_file():
                pytesseract.pytesseract.tesseract_cmd = str(tess)
                break

        for pop in (
            Path(r'C:\poppler\Library\bin'),
            Path(r'C:\Program Files\poppler\Library\bin'),
            Path(r'C:\poppler-24.08.0\Library\bin'),
            Path(r'C:\poppler-23.11.0-0\Library\bin'),
        ):
            if (pop / 'pdftoppm.exe').is_file():
                POPPLER_PATH = str(pop)
                break

    # Optional override: waybill-stamper/paths.json {"tesseract":"...","poppler":"..."}
    cfg = Path(__file__).resolve().parent / 'paths.json'
    if cfg.is_file():
        try:
            data = json.loads(cfg.read_text(encoding='utf-8'))
            if isinstance(data, dict):
                t = data.get('tesseract')
                if t and Path(str(t)).is_file():
                    pytesseract.pytesseract.tesseract_cmd = str(t)
                p = data.get('poppler')
                if p and (Path(str(p)) / 'pdftoppm.exe').is_file():
                    POPPLER_PATH = str(p)
        except Exception:
            pass


configure_external_tools()


# ─── TRACKING NUMBER PATTERNS ────────────────────────────────────────────────

TN_PATTERNS = [
    r'\b(JT\d{13,16})\b',            # J&T / TikTok
    r'\b(FE[A-Z0-9]{10,14})\b',      # Flash Express
    r'\b(PH\d{10,14}[A-Z0-9]?\w?)\b',# Shopee Express
    r'\b(8201\d{8,10})\b',            # Lazada numeric (820144...)
    r'\b(820\d{9,11})\b',             # Lazada numeric fallback
]

def find_tracking(text: str) -> str | None:
    for pat in TN_PATTERNS:
        m = re.search(pat, text)
        if m:
            return m.group(1).strip()
    return None


# ─── PLATFORM DETECTION ──────────────────────────────────────────────────────

def detect_platform(tn: str | None) -> str:
    if not tn: return 'Unknown'
    if tn.startswith('JT'):  return 'TikTok/J&T'
    if tn.startswith('FE'):  return 'Lazada/Flash'
    if tn.startswith('PH'):  return 'Shopee'
    if tn.startswith('820'): return 'Lazada/J&T'
    return 'Unknown'


# ─── PDF PARSING ─────────────────────────────────────────────────────────────

def extract_text_layer(pdf_path: str, page_idx: int) -> str:
    doc  = fitz.open(pdf_path)
    page = doc[page_idx]
    text = page.get_text()
    doc.close()
    return text


def ocr_page(pdf_path: str, page_num: int) -> str:
    """Render page at 250 DPI and OCR it. page_num is 1-based."""
    kwargs: dict = {'dpi': 250, 'first_page': page_num, 'last_page': page_num}
    if POPPLER_PATH:
        kwargs['poppler_path'] = POPPLER_PATH
    imgs = convert_from_path(pdf_path, **kwargs)
    if imgs:
        return pytesseract.image_to_string(imgs[0])
    return ''


def get_tracking_for_page(pdf_path: str, page_idx: int, log_fn) -> tuple[str | None, str]:
    """
    Returns (tracking_number, method_used).
    Tries text layer first; falls back to OCR.
    """
    text = extract_text_layer(pdf_path, page_idx)
    tn   = find_tracking(text)
    if tn:
        return tn, 'text'

    log_fn(f"  [OCR] Page {page_idx+1} has no text-layer TN — running OCR…")
    try:
        ocr_text = ocr_page(pdf_path, page_idx + 1)
    except pytesseract.TesseractNotFoundError:
        log_fn("  [OCR] Tesseract not found. Install to C:\\Program Files\\Tesseract-OCR\\ (see SETUP.md).")
        return None, 'ocr-unavailable'
    except Exception as exc:
        log_fn(f"  [OCR] Failed: {exc}")
        return None, 'ocr-failed'
    tn = find_tracking(ocr_text)
    return tn, 'ocr'


# ─── EXCEL PARSING ───────────────────────────────────────────────────────────

# ── Column header keyword lists ───────────────────────────────────────────────
# The system scans EVERY row looking for a row that contains these keywords.
# This means it works regardless of which row number the header is on —
# whether it's row 4 (iPYX), row 5, row 6 (sir Jono), or any other row.

TN_COL_NAMES    = ['tracking number', 'tracking no', 'tracking no.', 'tracking', 'waybill']
ITEM_COL_NAMES  = ['item/s', 'items', 'item', 'product', 'products', 'description']
NAME_COL_NAMES  = ['name', 'buyer name', 'customer name', 'recipient']
ORDER_COL_NAMES = ['order no.', 'order no', 'order number', 'order id', 'order']
NOTE_COL_NAMES  = ['note', 'notes', 'remarks']

# Sheets to always skip regardless of content
SKIP_SHEETS = ['template', 'pito-pito', 'inventory', 'return', 'yesterday']


def norm_tn(raw: str) -> str:
    """
    Normalize a tracking number for reliable comparison.
    Handles floats stored as strings (e.g. '820145046679.0' → '820145046679'),
    strips whitespace, uppercases.
    """
    s = str(raw).strip().upper()
    # Remove float suffix (.0) that Excel sometimes adds to numeric tracking numbers
    s = re.sub(r'\.0+$', '', s)
    # Strip all internal whitespace
    s = re.sub(r'\s+', '', s)
    return s


def _is_header_row(cells: list[str]) -> tuple[bool, dict]:
    """
    Given a list of lowercased cell strings from one row,
    check if this row looks like the iPYX/sir Jono header row by detecting
    the presence of known column names. Returns (is_header, col_map).
    """
    found = {}
    for c_idx, cell in enumerate(cells):
        cell = cell.strip()
        if cell in TN_COL_NAMES:    found['tn']    = c_idx
        if cell in ITEM_COL_NAMES:  found['items'] = c_idx
        if cell in NAME_COL_NAMES:  found['name']  = c_idx
        if cell in ORDER_COL_NAMES: found['order'] = c_idx
        if cell in NOTE_COL_NAMES:  found['note']  = c_idx

    # A valid header must have at minimum: items + (tn or name)
    is_header = 'items' in found and ('tn' in found or 'name' in found)
    return is_header, found


def parse_excel(excel_paths: list, log_fn) -> dict:
    """
    Reads ALL sheets in ALL uploaded Excel files.
    For each sheet:
      1. Scans every row (not just the first 10) looking for the header row
         by checking if the row contains the expected column keywords.
      2. Once the header row is found, all non-empty rows below it are data.
      3. Skips blank rows and rows where the TN cell looks like a header keyword.

    Returns dict keyed by normalized tracking number →
      { tn, order_no, name, items, note, source }
    """
    orders = {}

    for path in excel_paths:
        try:
            wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
        except Exception as e:
            log_fn(f"  [ERROR] Cannot open {Path(path).name}: {e}")
            continue

        log_fn(f"\n  File: {Path(path).name}  ({len(wb.sheetnames)} sheets)")

        for sheet_name in wb.sheetnames:
            # Skip known utility / archive sheets
            if any(skip in sheet_name.lower() for skip in SKIP_SHEETS):
                log_fn(f"    Skipping sheet: '{sheet_name}'")
                continue

            ws   = wb[sheet_name]
            rows = list(ws.iter_rows(values_only=True))
            if not rows:
                continue

            # ── STEP 1: Scan every row for the header ──────────────────────
            header_idx = None
            col_map    = {}

            for r_idx, row in enumerate(rows):
                # Lowercase all cells for case-insensitive matching
                cells = [str(c).lower().strip() if c is not None else '' for c in row]
                is_hdr, found = _is_header_row(cells)
                if is_hdr:
                    header_idx = r_idx
                    col_map    = found
                    log_fn(f"    Sheet '{sheet_name}': header found at row {r_idx + 1} → cols: {col_map}")
                    break

            if header_idx is None:
                log_fn(f"    [WARN] No header row found in '{sheet_name}' — skipping")
                continue

            # ── STEP 2: Read data rows below the header ────────────────────
            sheet_count = 0
            for row in rows[header_idx + 1:]:
                # Skip completely empty rows
                if not any(row):
                    continue

                def gcell(key):
                    idx = col_map.get(key)
                    if idx is None or idx >= len(row):
                        return ''
                    v = row[idx]
                    if v is None:
                        return ''
                    # Handle floats from Excel (e.g. 820145046679.0)
                    if isinstance(v, float) and v == int(v):
                        return str(int(v))
                    return str(v).strip()

                raw_tn = gcell('tn')

                # Skip rows with no TN, or where TN cell still looks like a header
                if not raw_tn or raw_tn.lower() in TN_COL_NAMES + ['none', '']:
                    continue

                key = norm_tn(raw_tn)
                if not key:
                    continue

                orders[key] = {
                    'tn':       raw_tn,
                    'order_no': gcell('order'),
                    'name':     gcell('name'),
                    'items':    gcell('items'),
                    'note':     gcell('note'),
                    'source':   f"{Path(path).name} / {sheet_name}",
                }
                sheet_count += 1

            log_fn(f"      → {sheet_count} orders loaded from '{sheet_name}'")

    log_fn(f"\n  Total Excel records across all files: {len(orders)}")
    return orders


# ─── PDF STAMPING ────────────────────────────────────────────────────────────

# Colors — stamp pages use black; summary page uses green/red accents
BLACK         = (0, 0, 0)
WHITE         = (1, 1, 1)
RED_FILL      = (1.00, 0.93, 0.93)
RED_BORDER    = (0.55, 0.05, 0.05)
RED_TEXT      = (0.45, 0.02, 0.02)


def stamp_page(page: fitz.Page, items: str, matched: bool, tn: str | None):
    """
    Stamp a large, bold, clearly visible item label at the bottom of the page.
    Uses white background + thick black border + large black text.
    Sized to be obvious for packers without overwriting any waybill content.
    """
    pw = page.rect.width
    ph = page.rect.height

    # ── Box dimensions — tall enough to be obvious ──────────────────────────
    margin   = 5
    box_h    = 68       # taller box
    rect     = fitz.Rect(margin, ph - box_h - margin,
                         pw - margin, ph - margin)

    if matched:
        # White fill, thick black border
        page.draw_rect(rect, color=BLACK, fill=WHITE, width=2.5)

        # Thin top divider line for visual separation
        page.draw_line(
            (rect.x0, rect.y0 + 20),
            (rect.x1, rect.y0 + 20),
            color=BLACK, width=0.8
        )

        # "ITEMS:" label — small caps header line
        page.insert_text(
            (rect.x0 + 8, rect.y0 + 14),
            "ITEMS:",
            fontsize=9,
            color=BLACK,
            fontname="helv",
        )

        # Main items text — large and bold
        inner = fitz.Rect(rect.x0 + 6, rect.y0 + 24,
                          rect.x1 - 6, rect.y1 - 5)
        rc = page.insert_textbox(
            inner,
            items,
            fontsize=13,
            color=BLACK,
            fontname="hebo",        # Helvetica Bold
            align=fitz.TEXT_ALIGN_LEFT,
        )
        # If text overflows (very long items), fallback to smaller size
        if rc < 0:
            page.draw_rect(inner, color=WHITE, fill=WHITE, width=0)  # clear
            page.insert_textbox(
                inner,
                items,
                fontsize=10,
                color=BLACK,
                fontname="hebo",
                align=fitz.TEXT_ALIGN_LEFT,
            )

    else:
        # Unmatched — red fill, bold red text, still clearly visible
        page.draw_rect(rect, color=RED_BORDER, fill=RED_FILL, width=2.5)
        page.draw_line(
            (rect.x0, rect.y0 + 20),
            (rect.x1, rect.y0 + 20),
            color=RED_BORDER, width=0.8
        )
        page.insert_text(
            (rect.x0 + 8, rect.y0 + 14),
            "NO MATCH FOUND",
            fontsize=9,
            color=RED_TEXT,
            fontname="hebo",
        )
        tn_str = tn or "UNKNOWN"
        inner = fitz.Rect(rect.x0 + 6, rect.y0 + 24,
                          rect.x1 - 6, rect.y1 - 5)
        page.insert_textbox(
            inner,
            f"Tracking: {tn_str}",
            fontsize=11,
            color=RED_TEXT,
            fontname="hebo",
            align=fitz.TEXT_ALIGN_LEFT,
        )


# ─── MAIN ────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--pdfs',   required=True)
    parser.add_argument('--excels', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--log',    default=None)
    args = parser.parse_args()

    if hasattr(sys.stdout, 'reconfigure'):
        try:
            sys.stdout.reconfigure(encoding='utf-8', errors='replace')
        except Exception:
            pass

    log_lines = []
    def log(msg):
        line = str(msg)
        log_lines.append(line)
        print(console_safe(line), flush=True)

    pdf_paths   = json.loads(Path(args.pdfs).read_text())
    excel_paths = json.loads(Path(args.excels).read_text())

    # ── Step 1: Load Excel ──────────────────────────────────────────────────
    log("\n[1/3] Loading Excel order data…")
    orders = parse_excel(excel_paths, log)

    # ── Step 2: Process each PDF ────────────────────────────────────────────
    log("\n[2/3] Processing PDF waybills…")

    matched_list   = []
    unmatched_list = []

    # We'll write to a combined output PDF
    out_doc = fitz.open()

    for pdf_path in pdf_paths:
        log(f"\n  PDF: {Path(pdf_path).name}")
        src_doc = fitz.open(pdf_path)
        total   = len(src_doc)

        for i in range(total):
            log(f"    Page {i+1}/{total}…")

            # Get tracking number
            tn, method = get_tracking_for_page(pdf_path, i, log)
            platform   = detect_platform(tn)
            log(f"      TN={tn} ({method}, {platform})")

            # Match to Excel
            excel_row = None
            if tn:
                key = norm_tn(tn)
                excel_row = orders.get(key)

            # Copy page to output doc
            out_doc.insert_pdf(src_doc, from_page=i, to_page=i)
            out_page = out_doc[-1]

            if excel_row:
                items = excel_row['items'] or '(no item data)'
                stamp_page(out_page, items, matched=True, tn=tn)
                matched_list.append({'page': i+1, 'tn': tn,
                                     'items': items, 'pdf': Path(pdf_path).name})
                log(f"      ✓ MATCHED → {items[:60]}")
            else:
                stamp_page(out_page, '', matched=False, tn=tn)
                unmatched_list.append({'page': i+1, 'tn': tn,
                                       'pdf': Path(pdf_path).name})
                log(f"      ✗ NO MATCH")

        src_doc.close()

    # ── Step 3: Save ────────────────────────────────────────────────────────
    log("\n[3/3] Saving stamped PDF…")
    out_doc.save(args.output, garbage=4, deflate=True)
    out_doc.close()

    # Save log if requested
    if args.log:
        Path(args.log).write_text('\n'.join(log_lines), encoding='utf-8')

    # Machine-readable stats for PHP
    total = len(matched_list) + len(unmatched_list)
    print(f"STAT_TOTAL:{total}")
    print(f"STAT_MATCHED:{len(matched_list)}")
    print(f"STAT_UNMATCHED:{len(unmatched_list)}")
    for u in unmatched_list:
        print(f"UNMATCHED_TN:{u['tn'] or 'UNKNOWN'} (p.{u['page']})")


if __name__ == '__main__':
    main()
