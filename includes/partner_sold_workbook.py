#!/usr/bin/env python3
"""Partner seller sold-list scan + Order No highlight export."""

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
EXCLUDED_REMARK_RE = re.compile(r"\b(canceled|cancelled|failed\s+delivery)\b", re.I)


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


def find_header_row(ws: openpyxl.worksheet.worksheet.Worksheet, max_scan: int = 20) -> tuple[int, int, int | None]:
    order_col: int | None = None
    remark_col: int | None = None
    header_row = 1
    max_row = min(max_scan, ws.max_row or max_scan)
    max_col = min(ws.max_column or 1, 30)
    for row in range(1, max_row + 1):
        for col in range(1, max_col + 1):
            norm = normalize_header(cell_str(ws.cell(row=row, column=col).value))
            if not norm:
                continue
            if order_col is None and any(norm == alias or alias in norm for alias in ORDER_HEADER_ALIASES):
                order_col = col
                header_row = row
            if remark_col is None and any(norm == alias or alias in norm for alias in REMARK_HEADER_ALIASES):
                remark_col = col
                header_row = row
        if order_col is not None:
            break
    if order_col is None:
        raise ValueError("Could not find Order No column in workbook.")
    return header_row, order_col, remark_col


def pick_sheet(wb: openpyxl.Workbook) -> openpyxl.worksheet.worksheet.Worksheet:
    if wb.active is not None:
        return wb.active
    return wb[wb.sheetnames[0]]


def scan_workbook(path: str) -> dict[str, Any]:
    wb = openpyxl.load_workbook(path, data_only=True)
    ws = pick_sheet(wb)
    header_row, order_col, remark_col = find_header_row(ws)
    rows: list[dict[str, Any]] = []
    last_order: str | None = None
    max_row = ws.max_row or header_row
    for row in range(header_row + 1, max_row + 1):
        raw_order = ws.cell(row=row, column=order_col).value
        order_no = normalize_order_no(raw_order)
        inherited = False
        if order_no is None and last_order is not None:
            order_no = last_order
            inherited = True
        remark = ""
        if remark_col is not None:
            remark = cell_str(ws.cell(row=row, column=remark_col).value)
        excluded = bool(remark and EXCLUDED_REMARK_RE.search(remark))
        if order_no is not None:
            last_order = order_no
        if not order_no and not remark and raw_order in (None, ""):
            continue
        rows.append(
            {
                "row": row,
                "order_no": order_no,
                "order_no_raw": cell_str(raw_order),
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
        "rows": rows,
    }


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
    green = PatternFill(start_color="C6EFCE", end_color="C6EFCE", fill_type="solid")
    red = PatternFill(start_color="FFC7CE", end_color="FFC7CE", fill_type="solid")

    for row_key, status in row_matches.items():
        row = int(row_key)
        if row <= header_row:
            continue
        cell = ws.cell(row=row, column=order_col)
        if status == "matched":
            cell.fill = green
        elif status == "missing":
            cell.fill = red

    wb.save(dst_path)
    return {"ok": True, "output": dst_path}


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
