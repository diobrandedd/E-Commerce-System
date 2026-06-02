#!/usr/bin/env python3
"""
PDF → Excel Extractor — Finest Wellness PH
Reads each page of a Shopee (SPEPH/JT) waybill PDF,
extracts tracking number, receiver name, item/s, and courier,
then writes one Excel file per PDF in the iPYX format:
  Order No. | Name | Item/s | Note | Courier | Tracking Number
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

import pdfplumber
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

try:
    import pytesseract
    from pdf2image import convert_from_path
    OCR_AVAILABLE = True
except ImportError:
    OCR_AVAILABLE = False

POPPLER_PATH: str | None = None


def console_safe(text: str) -> str:
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


def configure_external_tools() -> None:
    global POPPLER_PATH
    if not OCR_AVAILABLE:
        return

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

    cfg = Path(__file__).resolve().parent.parent / 'waybill-stamper' / 'paths.json'
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


TN_PATTERNS: list[tuple[int, str]] = [
    (10, r'\b(SPEPH\d{12,15})\b'),
    (10, r'\b(SPX[A-Z0-9]{10,15})\b'),
    (20, r'\b(8201\d{8,12})\b'),
    (20, r'\b(820\d{9,12})\b'),
    (30, r'\b(JT\d{13,16})\b'),
    (30, r'\b(FE[A-Z0-9]{10,14})\b'),
    (30, r'\b(PH\d{10,14}[A-Z0-9]?\w?)\b'),
    (40, r'\b(P[A-Z0-9]{11,17})\b'),
]


def _valid_marketplace_parcel_id(tn: str) -> bool:
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


def detect_courier(tn: str | None) -> str:
    if not tn:
        return 'Unknown'
    if tn.startswith('SPEPH') or tn.startswith('SPX') or tn.startswith('PH'):
        return 'Shopee Express'
    if re.match(r'^P[A-Z0-9]{11,17}$', tn, re.I):
        return 'Flash / Marketplace'
    if tn.startswith('JT'):
        return "J&T Express"
    if tn.startswith('FE'):
        return 'Flash Express'
    if tn.startswith('820'):
        return 'Lazada / J&T'
    return 'Unknown'


def missing_fields(rec: dict) -> list[str]:
    missing = []
    if not (rec.get('tracking') or '').strip():
        missing.append('tracking')
    if not (rec.get('name') or '').strip():
        missing.append('name')
    if not (rec.get('items') or '').strip():
        missing.append('items')
    return missing


def row_complete(rec: dict) -> bool:
    return len(missing_fields(rec)) == 0


def extract_from_text(text: str) -> dict:
    lines = [l.strip() for l in text.split('\n') if l.strip()]

    tn = None
    tn_idx = None
    for i, line in enumerate(lines):
        found = find_tracking(line)
        if found:
            tn = found
            tn_idx = i
            break

    name = None
    if tn_idx is not None:
        for j in range(tn_idx + 1, min(tn_idx + 4, len(lines))):
            candidate = lines[j]
            cleaned = re.sub(r'^[A-Z]\s+', '', candidate).strip()
            if cleaned and len(cleaned) > 2 and not re.match(r'^\d+$', cleaned):
                if not re.match(r'^[A-Z]-\d+', cleaned) and 'Sort Code' not in cleaned:
                    name = cleaned
                    break

    items = []
    in_items = False
    for line in lines:
        if 'Item Description' in line:
            in_items = True
            continue
        if in_items:
            if re.match(r'^Weight', line, re.IGNORECASE) or \
               re.match(r'^Delivery', line, re.IGNORECASE):
                break
            if re.match(r'^[A-Z]$', line):
                continue
            if len(line) < 2:
                continue
            items.append(line)

    item_str = ' '.join(items).strip().rstrip('/').strip()

    return {
        'tracking': tn,
        'name': name or '',
        'items': item_str,
        'courier': detect_courier(tn),
    }


def ocr_page(pdf_path: str, page_num: int) -> str:
    if not OCR_AVAILABLE:
        return ''
    try:
        kwargs = {'dpi': 250, 'first_page': page_num, 'last_page': page_num}
        if POPPLER_PATH:
            kwargs['poppler_path'] = POPPLER_PATH
        imgs = convert_from_path(pdf_path, **kwargs)
        return pytesseract.image_to_string(imgs[0]) if imgs else ''
    except Exception:
        return ''


def extract_page(pdf_path: str, page_idx: int) -> dict:
    with pdfplumber.open(pdf_path) as pdf:
        page = pdf.pages[page_idx]
        text = page.extract_text() or ''

    result = extract_from_text(text)

    if not result['tracking']:
        ocr_text = ocr_page(pdf_path, page_idx + 1)
        if ocr_text:
            ocr_result = extract_from_text(ocr_text)
            if ocr_result['tracking']:
                result['tracking'] = ocr_result['tracking']
                result['courier'] = ocr_result['courier']
            if not result['name'] and ocr_result['name']:
                result['name'] = ocr_result['name']
            if not result['items'] and ocr_result['items']:
                result['items'] = ocr_result['items']

    return result


# iPYX / sir Jono layout (matches waybill-stamper header scan):
#   A = row #   B = Order No.   C = Name   D = Item/s   E = Note
#   F = (blank)   G = Courier   H = Tracking Number
# Header row 4, blank row 5, data from row 6.

HEADER_ROW = 4
FIRST_DATA_ROW = 6

# Columns B–H (1-based column index)
COL_SEQ = 1       # A — line number only
COL_ORDER = 2     # B
COL_NAME = 3      # C
COL_ITEMS = 4     # D
COL_NOTE = 5      # E
COL_SPACER = 6    # F — intentionally blank
COL_COURIER = 7   # G
COL_TRACKING = 8  # H

HEADER_LABELS = {
    COL_ORDER: 'Order No.',
    COL_NAME: 'Name',
    COL_ITEMS: 'Item/s',
    COL_NOTE: 'Note',
    COL_COURIER: 'Courier',
    COL_TRACKING: 'Tracking Number',
}

COL_WIDTHS = {
    COL_SEQ: 6,
    COL_ORDER: 18,
    COL_NAME: 28,
    COL_ITEMS: 42,
    COL_NOTE: 14,
    COL_SPACER: 4,
    COL_COURIER: 22,
    COL_TRACKING: 24,
}

HDR_BG = "1a1e28"
HDR_FG = "f5a623"
IPYX_HDR_BG = "4472C4"
IPYX_HDR_FG = "FFFFFF"
ODD_BG = "f8f9fb"
EVEN_BG = "ffffff"
BORDER_C = "dde1e9"


def make_border():
    s = Side(style='thin', color=BORDER_C)
    return Border(left=s, right=s, top=s, bottom=s)


def write_excel(records: list, output_path: str, source_name: str):
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "Today Orders"

    border = make_border()
    banner_font = Font(bold=True, name='Calibri', size=11)
    ipyx_hdr_font = Font(bold=True, name='Calibri', size=11, color=IPYX_HDR_FG)
    ipyx_hdr_fill = PatternFill("solid", fgColor=IPYX_HDR_BG)
    hdr_align = Alignment(horizontal='center', vertical='center')
    row_align = Alignment(vertical='center', wrap_text=True)
    center_align = Alignment(horizontal='center', vertical='center')

    for col_idx, width in COL_WIDTHS.items():
        ws.column_dimensions[get_column_letter(col_idx)].width = width

    ws.cell(row=1, column=COL_ORDER, value='PDF → Excel Orders').font = banner_font
    ws.cell(row=2, column=COL_ORDER, value=f'Source: {source_name}').font = Font(
        name='Calibri', size=10, italic=True,
    )
    ws.cell(row=3, column=COL_ORDER, value='Order No. left blank — not on waybill label').font = Font(
        name='Calibri', size=9, color='666666',
    )

    ws.row_dimensions[HEADER_ROW].height = 22
    for col_idx, label in HEADER_LABELS.items():
        cell = ws.cell(row=HEADER_ROW, column=col_idx, value=label)
        cell.font = ipyx_hdr_font
        cell.fill = ipyx_hdr_fill
        cell.alignment = hdr_align
        cell.border = border

    ws.freeze_panes = f'A{FIRST_DATA_ROW}'

    for seq, rec in enumerate(records, 1):
        r_idx = FIRST_DATA_ROW + seq - 1
        fill = PatternFill("solid", fgColor=ODD_BG if seq % 2 == 0 else EVEN_BG)
        data_cells = {
            COL_SEQ: seq,
            COL_ORDER: '',
            COL_NAME: rec.get('name', ''),
            COL_ITEMS: rec.get('items', ''),
            COL_NOTE: '',
            COL_SPACER: '',
            COL_COURIER: rec.get('courier', ''),
            COL_TRACKING: rec.get('tracking', ''),
        }
        for col_idx, val in data_cells.items():
            cell = ws.cell(row=r_idx, column=col_idx, value=val)
            cell.fill = fill
            cell.border = border
            cell.font = Font(name='Calibri', size=10)
            cell.alignment = center_align if col_idx == COL_SEQ else row_align
        ws.row_dimensions[r_idx].height = 18

    ws2 = wb.create_sheet("Extraction Summary")
    ws2.column_dimensions['A'].width = 32
    ws2.column_dimensions['B'].width = 18

    total = len(records)
    no_tn = sum(1 for r in records if not r.get('tracking'))
    no_name = sum(1 for r in records if not r.get('name'))
    no_items = sum(1 for r in records if not r.get('items'))
    complete = sum(1 for r in records if row_complete(r))

    summary = [
        ('PDF EXTRACTION REPORT', ''),
        ('Source PDF', source_name),
        ('Total pages extracted', total),
        ('Fully complete rows', complete),
        ('Missing tracking number', no_tn),
        ('Missing receiver name', no_name),
        ('Missing item description', no_items),
    ]

    s = Side(style='thin', color=BORDER_C)
    sb = Border(left=s, right=s, top=s, bottom=s)

    for r_idx, (label, val) in enumerate(summary, 1):
        la = ws2.cell(row=r_idx, column=1, value=label)
        vl = ws2.cell(row=r_idx, column=2, value=val)
        if r_idx == 1:
            la.font = Font(bold=True, size=13, name='Calibri', color=HDR_FG)
            la.fill = PatternFill("solid", fgColor=HDR_BG)
            vl.fill = PatternFill("solid", fgColor=HDR_BG)
            ws2.row_dimensions[1].height = 26
        else:
            la.font = Font(name='Calibri', size=11, bold=True)
            vl.font = Font(name='Calibri', size=11)
            la.border = sb
            vl.border = sb

    wb.save(output_path)


def build_report_entry(pdf_path: str, records: list, excel_name: str) -> dict:
    rows = []
    for i, rec in enumerate(records, 1):
        mf = missing_fields(rec)
        rows.append({
            'page': i,
            'tracking': rec.get('tracking') or '',
            'name': rec.get('name') or '',
            'items': rec.get('items') or '',
            'courier': rec.get('courier') or '',
            'complete': row_complete(rec),
            'missing_fields': mf,
        })
    total = len(records)
    complete = sum(1 for r in records if row_complete(r))
    return {
        'pdf': Path(pdf_path).name,
        'excel': excel_name,
        'rows': rows,
        'total': total,
        'complete': complete,
        'missing': total - complete,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--pdfs', required=True, help='JSON list of PDF paths')
    parser.add_argument('--outdir', required=True, help='Output directory')
    parser.add_argument('--report', required=True, help='JSON report output path')
    args = parser.parse_args()

    pdf_paths = json.loads(Path(args.pdfs).read_text(encoding='utf-8'))
    out_dir = Path(args.outdir)
    out_dir.mkdir(parents=True, exist_ok=True)

    all_outputs = []
    full_report = []

    for pdf_path in pdf_paths:
        pdf_name = Path(pdf_path).stem
        print(console_safe(f"\n[PDF] {Path(pdf_path).name}"))

        records = []
        try:
            with pdfplumber.open(pdf_path) as pdf:
                total_pages = len(pdf.pages)
        except Exception as e:
            print(console_safe(f"  [ERROR] Cannot open: {e}"))
            continue

        print(console_safe(f"  Pages: {total_pages}"))

        for i in range(total_pages):
            rec = extract_page(pdf_path, i)
            records.append(rec)

            status = '[OK]' if row_complete(rec) else '[?]'
            print(console_safe(
                f"  P{i + 1:03d} {status} | TN:{rec['tracking'] or 'MISSING':22} | "
                f"{rec['name'] or 'MISSING':25} | {rec['items'][:35] or 'MISSING'}"
            ))

        out_name = f"{pdf_name}_Orders.xlsx"
        out_path = out_dir / out_name
        write_excel(records, str(out_path), Path(pdf_path).name)

        complete = sum(1 for r in records if row_complete(r))
        print(console_safe(f"  -> Excel: {out_name} ({complete}/{total_pages} complete rows)"))

        entry = build_report_entry(pdf_path, records, out_name)
        full_report.append(entry)

        all_outputs.append({
            'pdf': Path(pdf_path).name,
            'excel': out_name,
            'total': total_pages,
            'complete': complete,
            'missing': total_pages - complete,
        })

    Path(args.report).write_text(
        json.dumps(full_report, ensure_ascii=False, indent=2),
        encoding='utf-8',
    )
    print(console_safe(f"\nOUTPUT_JSON:{json.dumps(all_outputs)}"))


if __name__ == '__main__':
    main()
