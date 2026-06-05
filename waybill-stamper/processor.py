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

# (priority, pattern) — lower priority number wins when multiple matches exist (e.g. OCR).
TN_PATTERNS: list[tuple[int, str]] = [
    (10, r'\b(SPEPH\d{12,15})\b'),          # Shopee Express PH barcode
    (10, r'\b(SPX[A-Z0-9]{10,15})\b'),      # Shopee Express alt
    (20, r'\b(8201\d{8,12})\b'),            # Lazada / J&T numeric
    (20, r'\b(820\d{9,12})\b'),             # Lazada numeric (e.g. 820145410774)
    (30, r'\b(JT\d{13,16})\b'),            # J&T / TikTok
    (30, r'\b(FE[A-Z0-9]{10,14})\b'),      # Flash carrier barcode
    (30, r'\b(PH\d{10,14}[A-Z0-9]?\w?)\b'),# Shopee legacy PH…
    (40, r'\b(P[A-Z0-9]{11,17})\b'),        # Marketplace parcel ID (P0432KFQNC1AC)
]


def _valid_marketplace_parcel_id(tn: str) -> bool:
    """Reject OCR garbage like PLLOLPSVLOZ8 (letters + one digit on vertical text)."""
    if not re.match(r'^P[A-Z0-9]{11,17}$', tn, re.IGNORECASE):
        return True
    return sum(1 for c in tn if c.isdigit()) >= 2


def find_tracking(text: str) -> str | None:
    if not text:
        return None
    best_prio = 999
    best_tn: str | None = None
    for prio, pat in TN_PATTERNS:
        for m in re.finditer(pat, text, re.IGNORECASE):
            tn = m.group(1).strip().upper()
            if not _valid_marketplace_parcel_id(tn):
                continue
            if prio < best_prio:
                best_prio = prio
                best_tn = tn
    return best_tn


# ─── PLATFORM DETECTION ──────────────────────────────────────────────────────

def detect_platform(tn: str | None) -> str:
    if not tn: return 'Unknown'
    if tn.startswith('SPEPH') or tn.startswith('SPX'): return 'Shopee Express'
    if re.match(r'^P[A-Z0-9]{11,17}$', tn): return 'Marketplace/Flash'
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
SKIP_SHEETS = [
    'template', 'pito-pito', 'inventory', 'return', 'yesterday',
    'extraction summary',
]


def norm_tn(raw: str) -> str:
    """
    Normalize a tracking number for reliable comparison.
    Handles floats stored as strings (e.g. '820145046679.0' → '820145046679'),
    strips whitespace, uppercases, and fixes common OCR confusions (O/0 on Flash P-IDs).
    """
    s = str(raw).strip().upper()
    # Remove float suffix (.0) that Excel sometimes adds to numeric tracking numbers
    s = re.sub(r'\.0+$', '', s)
    # Strip all internal whitespace
    s = re.sub(r'\s+', '', s)
    return ocr_confusables_for_match(s)


def ocr_confusables_for_match(s: str) -> str:
    """
    Map OCR-prone letters to digits for waybill ↔ Excel matching.
    Flash/Lazada marketplace parcel IDs (P…) often read letter O as digit 0.
    Never apply to Shopee express prefixes (PH, SPEPH, SPX).
    """
    if re.match(r'^(PH|SPEPH|SPX)', s):
        return s
    if re.match(r'^P[A-Z0-9]{11,17}$', s) and 'O' in s:
        return s.replace('O', '0')
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
    Reads the **first sheet only** in each uploaded Excel file (iPYX daily order sheet).
    For each sheet:
      1. Scans every row looking for the header row by column keywords.
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

        log_fn(f"\n  File: {Path(path).name}  (first sheet only)")

        if not wb.sheetnames:
            continue

        sheet_name = wb.sheetnames[0]
        log_fn(f"    Using sheet: '{sheet_name}'")

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
                log_fn(f"    Header found at row {r_idx + 1} → cols: {col_map}")
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


def _stamp_layout(
    page: fitz.Page,
    tn: str | None,
    line: str | None = None,
) -> tuple[fitz.Rect, int, int]:
    """
    Item stamp on the bottom-right; bottom-left kept clear for SPX/Shopee QR only.
    Returns (rect, font_size, font_size_small).
    """
    pw = page.rect.width
    ph = page.rect.height
    margin = 4

    tn_up = (tn or '').upper()
    # Only SPEPH/SPX use the narrow layout (QR bottom-left). Flash/Lazada P… IDs use full width.
    is_shopee = tn_up.startswith('SPEPH') or tn_up.startswith('SPX')
    if not is_shopee and tn:
        is_shopee = detect_platform(tn) == 'Shopee Express'

    left_reserve = pw * (0.42 if is_shopee else 0.22)
    long_line = bool(line and len(line) > 45)
    box_h = 20 if is_shopee else 24
    if long_line:
        box_h = 28 if is_shopee else 34

    font = 9 if is_shopee else 10
    font_small = 7 if is_shopee else 8

    x0 = max(left_reserve + margin, left_reserve)
    rect = fitz.Rect(x0, ph - box_h - margin, pw - margin, ph - margin)
    return rect, font, font_small


def _draw_stamp_text(
    page: fitz.Page,
    rect: fitz.Rect,
    line: str,
    font: int,
    font_small: int,
    color: tuple,
) -> None:
    """Fit stamp text; shrink font and grow box upward if the line is long."""
    inner = fitz.Rect(rect.x0 + 4, rect.y0 + 2, rect.x1 - 4, rect.y1 - 2)
    min_fs = 5

    for attempt in range(3):
        for fs in range(font if attempt == 0 else font_small, min_fs - 1, -1):
            rc = page.insert_textbox(
                inner,
                line,
                fontsize=fs,
                color=color,
                fontname="hebo",
                align=fitz.TEXT_ALIGN_LEFT,
            )
            if rc >= 0:
                return
        # Grow box upward for wrapped lines (up to ~2 extra lines)
        grow = 12
        if inner.y0 - grow < rect.y0 - 24:
            break
        inner = fitz.Rect(inner.x0, inner.y0 - grow, inner.x1, inner.y1)

    # Last resort: truncate so something is always visible
    label = 'ITEMS: ' if line.upper().startswith('ITEMS:') else ''
    body = line[len(label):] if label else line
    max_body = max(12, int((inner.width / 4.5)))
    short = f"{label}{body[:max_body]}…" if len(body) > max_body else line
    page.insert_textbox(
        inner,
        short,
        fontsize=min_fs,
        color=color,
        fontname="hebo",
        align=fitz.TEXT_ALIGN_LEFT,
    )


def stamp_page(page: fitz.Page, items: str, matched: bool, tn: str | None):
    """
    Item stamp at the bottom-right (e.g. ITEMS: 1 x 1L HONEY w/SF).
    Bottom-left stays clear for carrier QR codes on SPX/Shopee labels only.
    """
    if matched:
        line = f"ITEMS: {items}"
    else:
        tn_str = tn or "UNKNOWN"
        line = f"NO MATCH: {tn_str}"

    rect, font, font_small = _stamp_layout(page, tn, line)

    if matched:
        page.draw_rect(rect, color=BLACK, fill=WHITE, width=1.2)
        _draw_stamp_text(page, rect, line, font, font_small, BLACK)
    else:
        page.draw_rect(rect, color=RED_BORDER, fill=RED_FILL, width=1.2)
        _draw_stamp_text(page, rect, line, font, font_small, RED_TEXT)


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
