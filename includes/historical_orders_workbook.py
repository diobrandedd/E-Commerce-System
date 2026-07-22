#!/usr/bin/env python3
"""Parse Snappy/Webcake shipped-order Excel (Tracking number column) for historical import."""

from __future__ import annotations

import json
import re
import sys
import warnings
from decimal import Decimal, InvalidOperation
from typing import Any

import openpyxl

warnings.filterwarnings("ignore", category=UserWarning, module="openpyxl")

REQUIRED = {
    "tracking": ["tracking number", "tracking no", "tracking no.", "waybill", "waybill number"],
    "order_id": ["order id", "order no", "order no.", "order number"],
    "recipient": ["recipient name", "customer", "receiver"],
    "product": ["product name", "product"],
}


def cell_str(value: Any) -> str:
    if value is None:
        return ""
    return str(value).strip()


def normalize_header(text: str) -> str:
    return re.sub(r"\s+", " ", cell_str(text).lower().replace("(*)", ""))


def normalize_id(value: Any) -> str:
    if value is None:
        return ""
    if isinstance(value, bool):
        return ""
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        if value != value:
            return ""
        try:
            return str(int(Decimal(str(value)).to_integral_value()))
        except (InvalidOperation, OverflowError, ValueError):
            return cell_str(value)
    text = cell_str(value)
    if re.match(r"^\d+\.0$", text):
        text = text[:-2]
    return text


def normalize_qty(value: Any) -> int:
    if value is None or value == "":
        return 1
    try:
        q = int(Decimal(str(value)))
        return q if q > 0 else 1
    except (InvalidOperation, OverflowError, ValueError):
        return 1


def find_cols(header_cells: list[str]) -> dict[str, int]:
    norms = [normalize_header(h) for h in header_cells]
    found: dict[str, int] = {}
    for key, aliases in REQUIRED.items():
        for i, norm in enumerate(norms):
            if not norm:
                continue
            if any(norm == a or a in norm for a in aliases):
                found[key] = i
                break
    # Prefer Recipient name over Customer when both exist
    for i, norm in enumerate(norms):
        if norm == "recipient name":
            found["recipient"] = i
            break
    for i, norm in enumerate(norms):
        if norm in ("quantity", "qty"):
            found["quantity"] = i
            break
    for i, norm in enumerate(norms):
        if norm == "status":
            found["status"] = i
            break
    return found


def pick_sheet(wb: openpyxl.Workbook):
    if wb.active is not None:
        return wb.active
    return wb[wb.sheetnames[0]]


def parse_workbook(path: str) -> dict[str, Any]:
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = pick_sheet(wb)
    rows_iter = ws.iter_rows(values_only=True)
    try:
        header = next(rows_iter)
    except StopIteration:
        raise ValueError("Workbook is empty.")
    header_cells = [cell_str(c) for c in header]
    cols = find_cols(header_cells)
    missing = [k for k in ("tracking", "order_id", "recipient", "product") if k not in cols]
    if missing:
        raise ValueError(
            "Not a Snappy shipped-orders Excel. Need columns: Tracking number, Order ID, "
            "Recipient/Customer, Product name. Missing: " + ", ".join(missing)
        )

    orders: list[dict[str, Any]] = []
    excel_row = 1
    for row in rows_iter:
        excel_row += 1
        if row is None:
            continue
        cells = list(row)
        def get(key: str) -> Any:
            idx = cols.get(key)
            if idx is None or idx >= len(cells):
                return None
            return cells[idx]

        tn = re.sub(r"\s+", "", cell_str(get("tracking")))
        order_no = normalize_id(get("order_id"))
        recipient = cell_str(get("recipient"))
        product = cell_str(get("product")).rstrip(":").strip()
        qty = normalize_qty(get("quantity")) if "quantity" in cols else 1
        status = cell_str(get("status")) if "status" in cols else ""

        if tn == "" and order_no == "" and recipient == "" and product == "":
            continue

        items = f"{qty} x {product}" if product else ""
        orders.append(
            {
                "excel_row": excel_row,
                "tracking_number": tn,
                "order_no": order_no,
                "recipient_name": recipient,
                "product_name": product,
                "quantity": qty,
                "items_raw": items,
                "sheet_status": status,
            }
        )

    return {
        "format": "snappy_shipped",
        "sheet": ws.title,
        "total_rows": len(orders),
        "orders": orders,
    }


def main() -> None:
    if len(sys.argv) < 3 or sys.argv[1] != "parse":
        print(json.dumps({"error": "Usage: historical_orders_workbook.py parse <xlsx>"}))
        sys.exit(1)
    path = sys.argv[2]
    try:
        print(json.dumps(parse_workbook(path), ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
