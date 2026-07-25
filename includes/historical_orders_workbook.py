#!/usr/bin/env python3
"""Parse historical order Excels: Snappy (Tracking number) or partner iPYX (Order No./Name/Item/s)."""

from __future__ import annotations

import json
import re
import sys
import warnings
from decimal import Decimal, InvalidOperation
from typing import Any

import openpyxl

warnings.filterwarnings("ignore", category=UserWarning, module="openpyxl")

SNAPPY_REQUIRED = {
    "tracking": ["tracking number", "tracking no", "tracking no.", "waybill", "waybill number"],
    "order_id": ["order id", "order no", "order no.", "order number"],
    "recipient": ["recipient name", "customer", "receiver"],
    "product": ["product name", "product"],
}

IPYX_ORDER_ALIASES = ["order no.", "order no", "order number", "order #"]
IPYX_NAME_ALIASES = ["name", "customer", "receiver", "recipient name"]
IPYX_ITEMS_ALIASES = ["item/s", "items", "item", "remarks"]
IPYX_COURIER_ALIASES = ["courier"]


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


def find_cols(header_cells: list[str], required: dict[str, list[str]]) -> dict[str, int]:
    norms = [normalize_header(h) for h in header_cells]
    found: dict[str, int] = {}
    for key, aliases in required.items():
        for i, norm in enumerate(norms):
            if not norm:
                continue
            if any(norm == a or a in norm for a in aliases):
                found[key] = i
                break
    return found


def pick_sheet(wb: openpyxl.Workbook, sheet_name: str | None = None):
    if sheet_name:
        wanted = sheet_name.strip().lower()
        for name in wb.sheetnames:
            if name.strip().lower() == wanted:
                return wb[name]
        # soft match contains
        for name in wb.sheetnames:
            if wanted in name.strip().lower() or name.strip().lower() in wanted:
                return wb[name]
        raise ValueError(
            f"Sheet not found: {sheet_name}. Available: " + ", ".join(wb.sheetnames)
        )
    if wb.active is not None:
        return wb.active
    return wb[wb.sheetnames[0]]


def is_ipyx_header(cells: list[Any]) -> dict[str, int] | None:
    norms = [normalize_header(cell_str(c)) for c in cells]
    found: dict[str, int] = {}
    for i, norm in enumerate(norms):
        if not norm:
            continue
        if "order_no" not in found and any(norm == a or norm.startswith(a) for a in IPYX_ORDER_ALIASES):
            found["order_no"] = i
        if "name" not in found and any(norm == a for a in IPYX_NAME_ALIASES):
            found["name"] = i
        if "items" not in found and any(norm == a or a in norm for a in IPYX_ITEMS_ALIASES):
            found["items"] = i
        if "courier" not in found and any(norm == a for a in IPYX_COURIER_ALIASES):
            found["courier"] = i
    if "order_no" in found and "name" in found and "items" in found:
        return found
    return None


def is_section_noise(order_no: str, name: str) -> bool:
    low = order_no.lower()
    if low in ("ipyx", "-", "order no.", "order no", "order number"):
        return True
    if low.startswith("order date") or low.startswith("pickup date"):
        return True
    if name.lower() in ("name", "customer"):
        return True
    return False


def parse_pickup_date_from_cells(cells: list[Any], default_year: int | None = None) -> str | None:
    """Return YYYY-MM-DD from a 'Pickup Date: Jan 2, 2026' / 'Pickup Date: Mar 1' cell."""
    from datetime import datetime

    for cell in cells:
        text = cell_str(cell)
        if not text:
            continue
        m = re.search(r"pickup\s*date\s*:\s*(.+)$", text, flags=re.I)
        if not m:
            continue
        raw = re.sub(r"\s+", " ", m.group(1).strip())
        # Normalize "Feb28" / "Mar3" style fragments if any slip in
        raw = re.sub(r"([A-Za-z])(\d)", r"\1 \2", raw)
        formats_with_year = (
            "%b %d, %Y",
            "%B %d, %Y",
            "%b %d %Y",
            "%B %d %Y",
            "%Y-%m-%d",
            "%m/%d/%Y",
        )
        for fmt in formats_with_year:
            try:
                return datetime.strptime(raw, fmt).strftime("%Y-%m-%d")
            except ValueError:
                continue
        if default_year is not None:
            for fmt in ("%b %d", "%B %d", "%m/%d"):
                try:
                    # Avoid ambiguous no-year strptime (Python 3.15+); inject year explicitly.
                    dt = datetime.strptime(f"{raw} {default_year}", f"{fmt} %Y")
                    return dt.strftime("%Y-%m-%d")
                except ValueError:
                    continue
    return None


def year_hint_from_sheet(sheet_title: str) -> int | None:
    m = re.search(r"(20\d{2})", sheet_title)
    if m:
        return int(m.group(1))
    return None


def parse_partner_ipyx(ws, sheet_title: str) -> dict[str, Any]:
    orders: list[dict[str, Any]] = []
    cols: dict[str, int] | None = None
    current_ship_date: str | None = None
    default_year = year_hint_from_sheet(sheet_title)
    excel_row = 0
    for row in ws.iter_rows(values_only=True):
        excel_row += 1
        if row is None:
            continue
        cells = list(row)

        pickup = parse_pickup_date_from_cells(cells, default_year)
        if pickup is not None:
            current_ship_date = pickup
            continue

        header = is_ipyx_header(cells)
        if header is not None:
            cols = header
            continue
        if cols is None:
            continue

        def get(key: str) -> Any:
            idx = cols.get(key) if cols else None
            if idx is None or idx >= len(cells):
                return None
            return cells[idx]

        order_no = normalize_id(get("order_no"))
        recipient = cell_str(get("name"))
        items = cell_str(get("items")).strip()
        courier = cell_str(get("courier")) if "courier" in cols else ""

        if order_no == "" and recipient == "" and items == "":
            continue
        if is_section_noise(order_no, recipient):
            continue
        if order_no == "" or recipient == "" or items == "":
            continue

        orders.append(
            {
                "excel_row": excel_row,
                "tracking_number": "",  # partner list has order no, not waybill
                "order_no": order_no,
                "recipient_name": recipient,
                "product_name": items,
                "quantity": 1,
                "items_raw": items,
                "courier": courier,
                "ship_date": current_ship_date,
                "sheet_status": "",
            }
        )

    if orders == []:
        raise ValueError(
            "No partner iPYX order rows found. Expected repeating headers: Order No., Name, Item/s."
        )

    return {
        "format": "partner_ipyx",
        "sheet": sheet_title,
        "total_rows": len(orders),
        "orders": orders,
        "available_sheets": [],
    }


def parse_snappy(ws, sheet_title: str) -> dict[str, Any]:
    rows_iter = ws.iter_rows(values_only=True)
    try:
        header = next(rows_iter)
    except StopIteration:
        raise ValueError("Workbook is empty.")
    header_cells = [cell_str(c) for c in header]
    cols = find_cols(header_cells, SNAPPY_REQUIRED)
    # Prefer Recipient name over Customer
    norms = [normalize_header(h) for h in header_cells]
    for i, norm in enumerate(norms):
        if norm == "recipient name":
            cols["recipient"] = i
            break
    for i, norm in enumerate(norms):
        if norm in ("quantity", "qty"):
            cols["quantity"] = i
            break
    for i, norm in enumerate(norms):
        if norm == "status":
            cols["status"] = i
            break

    missing = [k for k in ("tracking", "order_id", "recipient", "product") if k not in cols]
    if missing:
        raise ValueError("snappy_missing:" + ",".join(missing))

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
                "courier": "",
                "sheet_status": status,
            }
        )

    return {
        "format": "snappy_shipped",
        "sheet": sheet_title,
        "total_rows": len(orders),
        "orders": orders,
        "available_sheets": [],
    }


def parse_workbook(path: str, sheet_name: str | None = None) -> dict[str, Any]:
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    available = list(wb.sheetnames)
    ws = pick_sheet(wb, sheet_name)
    title = ws.title

    # Try snappy first only when first row looks like snappy headers.
    first_rows: list[list[Any]] = []
    for i, row in enumerate(ws.iter_rows(values_only=True)):
        first_rows.append(list(row) if row else [])
        if i >= 8:
            break

    # reopen for full parse (read_only iterator consumed)
    wb.close()
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = pick_sheet(wb, sheet_name)

    snappy_look = False
    if first_rows:
        cols = find_cols([cell_str(c) for c in first_rows[0]], SNAPPY_REQUIRED)
        if "tracking" in cols and "product" in cols:
            snappy_look = True

    if snappy_look:
        try:
            data = parse_snappy(ws, title)
            data["available_sheets"] = available
            return data
        except ValueError as e:
            if not str(e).startswith("snappy_missing:"):
                raise

    # Partner iPYX: scan for Order No. / Name / Item/s headers anywhere in sheet.
    wb.close()
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    ws = pick_sheet(wb, sheet_name)
    data = parse_partner_ipyx(ws, title)
    data["available_sheets"] = available
    return data


def main() -> None:
    if len(sys.argv) < 3 or sys.argv[1] != "parse":
        print(
            json.dumps(
                {
                    "error": "Usage: historical_orders_workbook.py parse <xlsx> [sheet_name]"
                }
            )
        )
        sys.exit(1)
    path = sys.argv[2]
    sheet = sys.argv[3] if len(sys.argv) > 3 else None
    try:
        print(json.dumps(parse_workbook(path, sheet), ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)


if __name__ == "__main__":
    main()
