#!/usr/bin/env python3
"""Partner seller sold-list scan + Order No highlight / Exists column export."""

from __future__ import annotations

import json
import re
import sys
import warnings
from decimal import Decimal, InvalidOperation
from typing import Any

import openpyxl
from openpyxl.styles import PatternFill

warnings.filterwarnings("ignore", category=UserWarning, module="openpyxl")

ORDER_HEADER_ALIASES = ["order no", "order no.", "order number", "order #"]
REMARK_HEADER_ALIASES = ["remark", "remarks", "note", "notes"]
TRACKING_HEADER_ALIASES = [
    "tracking number",
    "tracking no",
    "tracking no.",
    "tracking #",
    "waybill",
    "tn",
]
ORDER_DATE_ALIASES = ["order date", "date"]
ITEM_ALIASES = ["item name", "item", "product", "product name"]
QTY_ALIASES = ["quantity", "qty", "qty."]
CUSTOMER_ALIASES = ["customer", "recipient", "buyer", "name"]
EXCLUDED_REMARK_RE = re.compile(r"\b(canceled|cancelled|failed\s+delivery)\b", re.I)
EMPTY_STREAK_STOP = 25


def cell_str(value: Any) -> str:
    if value is None:
        return ""
    return str(value).strip()


def normalize_header(text: str) -> str:
    return re.sub(r"\s+", " ", cell_str(text).lower().replace("(*)", ""))


def normalize_order_no(value: Any) -> str | None:
    if value is None:
        return None
    if isinstance(value, bool):
        return None
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        if value != value:  # NaN
            return None
        if abs(value) >= 1e15 or float(value).is_integer():
            try:
                return str(int(Decimal(str(value)).to_integral_value()))
            except (InvalidOperation, OverflowError, ValueError):
                pass
        text = format(value, ".0f") if float(value).is_integer() else str(value)
        return text.strip() or None
    text = cell_str(value)
    if not text:
        return None
    if re.match(r"^\d+\.0$", text):
        text = text[:-2]
    if re.match(r"^\d+\.?\d*[eE][+]?\d+$", text):
        try:
            return str(int(Decimal(text).to_integral_value()))
        except (InvalidOperation, OverflowError, ValueError):
            pass
    return text


def normalize_tracking(value: Any) -> str | None:
    text = cell_str(value)
    if not text:
        return None
    if re.match(r"^\d+\.0$", text):
        text = text[:-2]
    return text or None


def find_header_map(ws: openpyxl.worksheet.worksheet.Worksheet, max_scan: int = 20) -> dict[str, Any]:
    order_col: int | None = None
    remark_col: int | None = None
    tracking_col: int | None = None
    order_date_col: int | None = None
    item_col: int | None = None
    qty_col: int | None = None
    customer_col: int | None = None
    header_row = 1
    max_row = min(max_scan, ws.max_row or max_scan)
    max_col = min(ws.max_column or 1, 40)
    for row in range(1, max_row + 1):
        for col in range(1, max_col + 1):
            norm = normalize_header(cell_str(ws.cell(row=row, column=col).value))
            if not norm:
                continue
            if order_col is None and any(norm == alias or alias in norm for alias in ORDER_HEADER_ALIASES):
                # Prefer exact-ish Order No over "Sold Platform"
                if "order" in norm and ("no" in norm or "#" in norm or "number" in norm):
                    order_col = col
                    header_row = row
            if remark_col is None and any(norm == alias or alias in norm for alias in REMARK_HEADER_ALIASES):
                remark_col = col
                header_row = row
            if tracking_col is None and any(norm == alias or alias in norm for alias in TRACKING_HEADER_ALIASES):
                tracking_col = col
                header_row = row
            if order_date_col is None and any(norm == alias for alias in ORDER_DATE_ALIASES):
                order_date_col = col
            if item_col is None and any(norm == alias for alias in ITEM_ALIASES):
                item_col = col
            if qty_col is None and any(norm == alias for alias in QTY_ALIASES):
                qty_col = col
            if customer_col is None and any(norm == alias for alias in CUSTOMER_ALIASES):
                customer_col = col
        if order_col is not None:
            break
    if order_col is None:
        raise ValueError("Could not find Order No column in workbook.")
    return {
        "header_row": header_row,
        "order_col": order_col,
        "remark_col": remark_col,
        "tracking_col": tracking_col,
        "order_date_col": order_date_col,
        "item_col": item_col,
        "qty_col": qty_col,
        "customer_col": customer_col,
    }


def pick_sheet(wb: openpyxl.Workbook) -> openpyxl.worksheet.worksheet.Worksheet:
    if wb.active is not None:
        return wb.active
    return wb[wb.sheetnames[0]]


def row_has_continuation(ws: openpyxl.worksheet.worksheet.Worksheet, row: int, cols: dict[str, Any]) -> bool:
    for key in ("item_col", "qty_col", "customer_col"):
        col = cols.get(key)
        if col is None:
            continue
        if cell_str(ws.cell(row=row, column=int(col)).value):
            return True
    return False


def scan_workbook(path: str) -> dict[str, Any]:
    wb = openpyxl.load_workbook(path, data_only=True)
    ws = pick_sheet(wb)
    cols = find_header_map(ws)
    header_row = int(cols["header_row"])
    order_col = int(cols["order_col"])
    remark_col = cols.get("remark_col")
    tracking_col = cols.get("tracking_col")
    order_date_col = cols.get("order_date_col")
    rows: list[dict[str, Any]] = []
    last_order: str | None = None
    empty_streak = 0
    max_row = ws.max_row or header_row
    skipped_blank = 0
    for row in range(header_row + 1, max_row + 1):
        raw_order = ws.cell(row=row, column=order_col).value
        order_no = normalize_order_no(raw_order)
        raw_date = ws.cell(row=row, column=int(order_date_col)).value if order_date_col else None
        has_date = raw_date not in (None, "")
        continuation = row_has_continuation(ws, row, cols)
        inherited = False

        if order_no is None and last_order is not None and not has_date and continuation:
            order_no = last_order
            inherited = True

        remark = ""
        if remark_col is not None:
            remark = cell_str(ws.cell(row=row, column=int(remark_col)).value)
        tracking = None
        if tracking_col is not None:
            tracking = normalize_tracking(ws.cell(row=row, column=int(tracking_col)).value)

        excluded = bool(remark and EXCLUDED_REMARK_RE.search(remark))

        if order_no is None and tracking is None and not remark and raw_order in (None, "") and not continuation:
            empty_streak += 1
            skipped_blank += 1
            if empty_streak >= EMPTY_STREAK_STOP and last_order is not None:
                break
            continue

        empty_streak = 0
        if order_no is not None and not inherited:
            last_order = order_no

        rows.append(
            {
                "row": row,
                "order_no": order_no,
                "order_no_raw": cell_str(raw_order),
                "tracking_number": tracking,
                "remark": remark,
                "inherited_order": inherited,
                "excluded": excluded,
            }
        )
    return {
        "sheet": ws.title,
        "header_row": header_row,
        "order_col": order_col,
        "remark_col": remark_col,
        "tracking_col": tracking_col,
        "skipped_blank_rows": skipped_blank,
        "rows": rows,
    }


def resolve_exists_col(ws: openpyxl.worksheet.worksheet.Worksheet, header_row: int) -> int:
    max_col = ws.max_column or 1
    # Prefer first empty header cell to the right of column A within a reasonable span
    scan_to = max(max_col + 1, 12)
    for col in range(1, scan_to + 1):
        if not cell_str(ws.cell(row=header_row, column=col).value):
            # Avoid planting inside FAILED DELIVERY side lists (often col N+)
            # Use the first empty among cols after the main sold-list block if possible.
            if col >= 12:
                return col
    return max_col + 1


def highlight_workbook(src_path: str, dst_path: str, matches_path: str) -> dict[str, Any]:
    with open(matches_path, encoding="utf-8") as fh:
        payload = json.load(fh)
    row_matches: dict[str, str] = {}
    for entry in payload.get("rows", []):
        if not isinstance(entry, dict):
            continue
        row = int(entry.get("row", 0))
        status = str(entry.get("status", "")).lower()
        if row > 0 and status:
            row_matches[str(row)] = status

    wb = openpyxl.load_workbook(src_path)
    ws = pick_sheet(wb)
    header_row = int(payload.get("header_row", 1))
    order_col = int(payload.get("order_col", 2))
    exists_col = int(payload.get("exists_col") or 0)
    if exists_col <= 0:
        exists_col = resolve_exists_col(ws, header_row)

    green = PatternFill(start_color="C6EFCE", end_color="C6EFCE", fill_type="solid")
    red = PatternFill(start_color="FFC7CE", end_color="FFC7CE", fill_type="solid")

    header_cell = ws.cell(row=header_row, column=exists_col)
    if not cell_str(header_cell.value):
        header_cell.value = "Exists"

    for row_key, status in row_matches.items():
        row = int(row_key)
        if row <= header_row:
            continue
        cell = ws.cell(row=row, column=order_col)
        exists_cell = ws.cell(row=row, column=exists_col)
        if status == "matched":
            cell.fill = green
            exists_cell.value = "Exists"
            exists_cell.fill = green
        elif status == "missing":
            cell.fill = red
            exists_cell.value = "Not found"
            exists_cell.fill = red

    wb.save(dst_path)
    return {"ok": True, "output": dst_path, "exists_col": exists_col}


def main() -> None:
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: partner_sold_workbook.py <scan|highlight> <workbook> [args...]"}))
        sys.exit(1)
    mode = sys.argv[1].lower()
    path = sys.argv[2]
    try:
        if mode == "scan":
            result = scan_workbook(path)
        elif mode == "highlight":
            if len(sys.argv) < 5:
                raise ValueError("highlight requires destination and matches JSON paths.")
            result = highlight_workbook(path, sys.argv[3], sys.argv[4])
        else:
            raise ValueError("Unknown mode: " + mode)
        print(json.dumps(result, ensure_ascii=False))
    except Exception as exc:
        print(json.dumps({"error": str(exc)}, ensure_ascii=False))
        sys.exit(1)


if __name__ == "__main__":
    main()
