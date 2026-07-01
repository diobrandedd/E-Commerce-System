#!/usr/bin/env python3
"""Generate SQL seed for partner sold-list reconcile testing."""

from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
WORKBOOK_PY = ROOT / "includes" / "partner_sold_workbook.py"
DEFAULT_XLSX = Path(r"c:\Users\Admin\Documents\partnerseller sold list.xlsx")
OUT_SQL = ROOT / "sql" / "seed_partner_sold_reconcile_test_ian.sql"


def main() -> None:
    xlsx = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_XLSX
    if not xlsx.is_file():
        raise SystemExit(f"Workbook not found: {xlsx}")

    scan = subprocess.check_output(
        [sys.executable, str(WORKBOOK_PY), "scan", str(xlsx)],
        text=True,
    )
    data = json.loads(scan)
    seen: set[str] = set()
    orders: list[str] = []
    for row in data.get("rows", []):
        if row.get("excluded"):
            continue
        on = row.get("order_no")
        if not on or on in seen:
            continue
        seen.add(on)
        orders.append(on)

    values: list[str] = []
    for i, on in enumerate(orders, start=1):
        tn = f"TEST-IAN-SOLD-{i:05d}"
        on_esc = str(on).replace("'", "''")
        pipeline = "tiktok" if on.isdigit() and len(on) > 12 else "partner"
        values.append(
            f"  ('{tn}', '1 Jar Cacao (test seed)', 'Test Receiver {i}', "
            f"'Test Courier', '{on_esc}', '{pipeline}', 'ian', 'shipped', '2026-04-01')"
        )

    lines = [
        "-- Seed shipped Online Parcels for partner sold-list reconcile test (Ian / April 2026).",
        "-- Run: mysql -u root rts_system < sql/seed_partner_sold_reconcile_test_ian.sql",
        "-- Cleanup:",
        "--   DELETE FROM online_order_parcels WHERE tracking_number LIKE 'TEST-IAN-SOLD-%';",
        "",
        "SET NAMES utf8mb4;",
        "",
        "DELETE FROM online_order_parcels WHERE tracking_number LIKE 'TEST-IAN-SOLD-%';",
        "",
        "INSERT INTO online_order_parcels (",
        "  tracking_number, items_raw, recipient_name, courier, order_no,",
        "  source_pipeline, partner_seller, status, ship_date",
        ") VALUES",
        ",\n".join(values) + ";",
        "",
        f"-- Inserted {len(orders)} rows (unique non-excluded Order No from workbook).",
    ]
    OUT_SQL.write_text("\n".join(lines), encoding="utf-8")
    print(f"Wrote {OUT_SQL} ({len(orders)} orders)")


if __name__ == "__main__":
    main()
