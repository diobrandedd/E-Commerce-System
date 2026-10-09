"""Generate Pyx Tracker DATABASE DESIGN (physical) figure in institutional sample style.

Replaces only Figure 3.11 media (image13.png). Does NOT touch the ERD (image14.png).
"""

from __future__ import annotations

import math
import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SRC_DOC = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx")
FALLBACK_DOC = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx")
OUT_IMG = Path(r"C:/xampp/htdocs/rts/output/documents/_db_design_pyx_tracker.png")
DESKTOP_DIR = Path(r"C:/Users/Admin/Desktop/capstone")
DESKTOP_DOC = DESKTOP_DIR / "Pyx_Tracker_Capstone_SQL_ERD.docx"
DESKTOP_PNG = DESKTOP_DIR / "Pyx_Tracker_Database_Design.png"

# Sample Data Modeler palette
BG = "#FFFFFF"
INK = "#1A1A1A"
HEADER = "#4A90D9"
HEADER_TEXT = "#FFFFFF"
ATTR_BG = "#FFF59D"  # yellow attribute body
KEYS_BG = "#FFFDE7"
BORDER = "#333333"
LINE = "#555555"
MUTED = "#555555"
P_BADGE = "#F5C542"
F_BADGE = "#90CAF9"

font_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 28)
font_sub = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 14)
font_hdr = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_attr = ImageFont.truetype(r"C:/Windows/Fonts/consola.ttf", 11)
font_attr_b = ImageFont.truetype(r"C:/Windows/Fonts/consolab.ttf", 11)
font_keys_hdr = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)
font_keys = ImageFont.truetype(r"C:/Windows/Fonts/consola.ttf", 10)
font_lane = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 12)
font_rel = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 10)

# Principal physical tables from live rts_system (same module set as ERD).
# cols: (name, type_display, marker)  marker in {"P","F","PF",""}
# keys: list of constraint/index labels shown in Keys section
TABLES = {
    "ROLES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("name", "VARCHAR(32)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": ["ROLES_PK (id)", "uq_roles_name (name)"],
    },
    "DEPARTMENTS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("name", "VARCHAR(32)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": ["DEPARTMENTS_PK (id)", "uq_departments_name (name)"],
    },
    "USERS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("username", "VARCHAR(64)", ""),
            ("name", "VARCHAR(120)", ""),
            ("email", "VARCHAR(160)", ""),
            ("employee_id", "VARCHAR(24)", ""),
            ("password_hash", "VARCHAR(255)", ""),
            ("role_id", "INT UNSIGNED", "F"),
            ("dept_id", "INT UNSIGNED", "F"),
            ("is_active", "TINYINT(1)", ""),
            ("created_at", "DATETIME", ""),
            ("updated_at", "DATETIME", ""),
        ],
        "keys": [
            "USERS_PK (id)",
            "fk_users_role (role_id)",
            "fk_users_dept (dept_id)",
            "uq_users_username (username)",
            "idx_users_role (role_id)",
        ],
    },
    "SUPPLIERS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("first_name", "VARCHAR(80)", ""),
            ("last_name", "VARCHAR(80)", ""),
            ("contact_number", "VARCHAR(32)", ""),
            ("address", "VARCHAR(255)", ""),
            ("category", "ENUM(...)", ""),
            ("is_active", "TINYINT(1)", ""),
            ("created_by", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "SUPPLIERS_PK (id)",
            "fk_suppliers_created_by (created_by)",
            "idx_suppliers_category_active",
        ],
    },
    "STOCKS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("name", "VARCHAR(160)", ""),
            ("dept_id", "INT UNSIGNED", "F"),
            ("supplier_id", "INT UNSIGNED", "F"),
            ("unit", "VARCHAR(32)", ""),
            ("category", "VARCHAR(64)", ""),
            ("on_hand", "DECIMAL(14,6)", ""),
            ("current_price", "DECIMAL(12,2)", ""),
            ("created_by", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "STOCKS_PK (id)",
            "fk_stocks_dept (dept_id)",
            "fk_stocks_supplier (supplier_id)",
            "uq_stocks_dept_name_supplier",
        ],
    },
    "STOCK_ENTRIES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("stock_id", "INT UNSIGNED", "F"),
            ("dept_id", "INT UNSIGNED", "F"),
            ("qty", "DECIMAL(14,6)", ""),
            ("batching_date", "DATETIME", ""),
            ("supplier_batch_no", "VARCHAR(120)", ""),
            ("entered_by", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "STOCK_ENTRIES_PK (id)",
            "fk_stock_entries_stock (stock_id)",
            "fk_stock_entries_dept (dept_id)",
            "idx_stock_entries_stock",
        ],
    },
    "PRODUCTS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("name", "VARCHAR(160)", ""),
            ("dept_product_key", "VARCHAR(32)", ""),
            ("dept_id", "INT UNSIGNED", "F"),
            ("size", "ENUM(...)", ""),
            ("variant", "ENUM(...)", ""),
            ("selling_price", "DECIMAL(12,2)", ""),
            ("baseline_cost", "DECIMAL(12,2)", ""),
            ("created_by", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "PRODUCTS_PK (id)",
            "fk_products_dept (dept_id)",
            "uq_products_dept_product_key",
        ],
    },
    "PRODUCT_FORMULA": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("product_id", "INT UNSIGNED", "F"),
            ("stock_id", "INT UNSIGNED", "F"),
            ("line_label", "VARCHAR(160)", ""),
            ("qty_per_unit", "DECIMAL(16,8)", ""),
            ("price_at_creation", "DECIMAL(12,2)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "PRODUCT_FORMULA_PK (id)",
            "fk_product_formula_product (product_id)",
            "idx_product_formula_stock",
        ],
    },
    "HONEY_WAREHOUSE_ROWS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("supplier_id", "INT UNSIGNED", "F"),
            ("supplier_code", "VARCHAR(32)", ""),
            ("arrival_date", "DATE", ""),
            ("variant", "ENUM(...)", ""),
            ("current_qty", "DECIMAL(12,2)", ""),
            ("initial_qty", "DECIMAL(12,2)", ""),
            ("created_by", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "HONEY_WH_PK (id)",
            "fk_honey_wh_supplier (supplier_id)",
            "idx_honey_wh_arrival",
        ],
    },
    "HONEY_PRODUCTION_ROWS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("warehouse_row_id", "INT UNSIGNED", "F"),
            ("supplier_code", "VARCHAR(32)", ""),
            ("stock_out_date", "DATE", ""),
            ("variant", "ENUM(...)", ""),
            ("current_qty", "DECIMAL(12,2)", ""),
            ("initial_qty", "DECIMAL(12,2)", ""),
            ("is_active", "TINYINT(1)", ""),
            ("created_by", "INT UNSIGNED", "F"),
        ],
        "keys": [
            "HONEY_PR_PK (id)",
            "fk_honey_pr_wh (warehouse_row_id)",
            "uq_honey_production_daily_group",
        ],
    },
    "HONEY_PACKAGING_ROWS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("production_row_id", "INT UNSIGNED", "F"),
            ("batch_no", "VARCHAR(48)", ""),
            ("bottling_date", "DATE", ""),
            ("variant", "ENUM(...)", ""),
            ("current_liter", "DECIMAL(12,2)", ""),
            ("current_250ml", "DECIMAL(12,2)", ""),
            ("current_350ml", "DECIMAL(12,2)", ""),
            ("current_gallon", "DECIMAL(12,2)", ""),
            ("created_by", "INT UNSIGNED", "F"),
        ],
        "keys": [
            "HONEY_PK_PK (id)",
            "fk_honey_pk_prod (production_row_id)",
            "uq_honey_pk_batch_variant_date",
        ],
    },
    "POS_SALES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("receipt_no", "VARCHAR(32)", ""),
            ("buyer_name", "VARCHAR(160)", ""),
            ("seller_name", "VARCHAR(160)", ""),
            ("total_amount", "DECIMAL(12,2)", ""),
            ("sold_by_user_id", "INT UNSIGNED", "F"),
            ("sale_date", "DATETIME", ""),
            ("is_ar", "TINYINT(1)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "POS_SALES_PK (id)",
            "fk_pos_sales_sold_by_user",
            "uq_pos_sales_receipt_no",
        ],
    },
    "POS_RECEIPT_ITEMS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("sale_id", "INT UNSIGNED", "F"),
            ("product_id", "INT UNSIGNED", "F"),
            ("qty", "DECIMAL(12,2)", ""),
            ("unit_price", "DECIMAL(12,2)", ""),
            ("line_total", "DECIMAL(12,2)", ""),
            ("variant", "VARCHAR(32)", ""),
            ("size", "VARCHAR(16)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "POS_RECEIPT_ITEMS_PK (id)",
            "fk_pos_receipt_items_sale (sale_id)",
            "fk_pos_receipt_items_product",
        ],
    },
    "CSR_ONLINE_BATCHES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("user_id", "INT UNSIGNED", "F"),
            ("status", "ENUM(...)", ""),
            ("created_at", "DATETIME", ""),
            ("completed_at", "DATETIME", ""),
            ("master_filename", "VARCHAR(255)", ""),
        ],
        "keys": [
            "CSR_ONLINE_BATCHES_PK (id)",
            "idx_csr_online_batches_user_status",
        ],
    },
    "CSR_ONLINE_ORDER_LINES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("batch_id", "INT UNSIGNED", "F"),
            ("order_no", "VARCHAR(64)", ""),
            ("recipient_name", "VARCHAR(255)", ""),
            ("tracking_number", "VARCHAR(96)", ""),
            ("courier", "VARCHAR(191)", ""),
            ("source_pipeline", "ENUM(...)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "CSR_ONLINE_ORDER_LINES_PK (id)",
            "fk_csr_online_order_lines_batch",
            "idx_csr_online_order_lines_tracking",
        ],
    },
    "ONLINE_ORDER_PARCELS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("csr_batch_id", "INT UNSIGNED", "F"),
            ("csr_line_id", "INT UNSIGNED", "F"),
            ("tracking_number", "VARCHAR(96)", ""),
            ("status", "ENUM(...)", ""),
            ("ship_date", "DATE", ""),
            ("rts_case_id", "INT UNSIGNED", "F"),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "ONLINE_ORDER_PARCELS_PK (id)",
            "fk_online_parcels_csr_batch",
            "fk_online_parcels_csr_line",
            "uq_online_order_parcels_tracking",
        ],
    },
    "DELIVERY_BATCHES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("submitted_by_user_id", "INT UNSIGNED", "F"),
            ("delivery_date", "DATE", ""),
            ("submitted_at", "DATETIME", ""),
        ],
        "keys": [
            "DELIVERY_BATCHES_PK (id)",
            "fk_batch_user (submitted_by_user_id)",
            "idx_delivery_date",
        ],
    },
    "DELIVERY_BATCH_ITEMS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("batch_id", "INT UNSIGNED", "F"),
            ("tracking_number", "VARCHAR(96)", ""),
            ("item_status", "ENUM(...)", ""),
            ("online_parcel_id", "INT UNSIGNED", "F"),
            ("photo_filename", "VARCHAR(190)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "DELIVERY_BATCH_ITEMS_PK (id)",
            "fk_item_batch (batch_id)",
            "fk_delivery_items_online_parcel",
        ],
    },
    "RTS_CASES": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("tracking_number", "VARCHAR(96)", ""),
            ("courier_final", "VARCHAR(16)", ""),
            ("shop", "VARCHAR(24)", ""),
            ("date_opened", "DATE", ""),
            ("claim_status", "ENUM(...)", ""),
            ("case_disposition", "ENUM(...)", ""),
            ("voided_at", "DATETIME", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "RTS_CASES_PK (id)",
            "uq_tracking (tracking_number)",
            "idx_claim_status",
            "idx_shop",
        ],
    },
    "RTS_LINE_ITEMS": {
        "cols": [
            ("id", "INT UNSIGNED", "P"),
            ("case_id", "INT UNSIGNED", "F"),
            ("product_type", "VARCHAR(32)", ""),
            ("honey_size", "VARCHAR(16)", ""),
            ("honey_variant", "VARCHAR(16)", ""),
            ("quantity", "DECIMAL(10,2)", ""),
            ("line_role", "VARCHAR(32)", ""),
            ("created_at", "DATETIME", ""),
        ],
        "keys": [
            "RTS_LINE_ITEMS_PK (id)",
            "fk_line_case (case_id)",
            "idx_case (case_id)",
        ],
    },
}

BOX_W = 292
ROW_H = 15
HDR_H = 26
KEYS_HDR_H = 16
PAD = 6

LAYOUT = {
    "ROLES": (60, 200),
    "USERS": (60, 430),
    "DEPARTMENTS": (60, 980),
    "SUPPLIERS": (420, 200),
    "STOCKS": (420, 560),
    "STOCK_ENTRIES": (420, 1040),
    "PRODUCTS": (820, 200),
    "PRODUCT_FORMULA": (820, 680),
    "POS_SALES": (820, 1100),
    "POS_RECEIPT_ITEMS": (820, 1540),
    "HONEY_WAREHOUSE_ROWS": (1260, 200),
    "HONEY_PRODUCTION_ROWS": (1260, 640),
    "HONEY_PACKAGING_ROWS": (1260, 1120),
    "CSR_ONLINE_BATCHES": (1700, 200),
    "CSR_ONLINE_ORDER_LINES": (1700, 520),
    "ONLINE_ORDER_PARCELS": (1700, 920),
    "RTS_CASES": (1700, 1380),
    "RTS_LINE_ITEMS": (1700, 1860),
    "DELIVERY_BATCHES": (2160, 200),
    "DELIVERY_BATCH_ITEMS": (2160, 470),
}

LANES = [
    (60, "Identity"),
    (420, "Inventory"),
    (820, "Products / POS"),
    (1260, "Honey"),
    (1700, "Online / RTS"),
    (2160, "Delivery"),
]

# (one, many, route_kind)
RELS = [
    ("ROLES", "USERS", "down"),
    ("DEPARTMENTS", "USERS", "up"),
    ("SUPPLIERS", "STOCKS", "down"),
    ("STOCKS", "STOCK_ENTRIES", "down"),
    ("PRODUCTS", "PRODUCT_FORMULA", "down"),
    ("POS_SALES", "POS_RECEIPT_ITEMS", "down"),
    ("HONEY_WAREHOUSE_ROWS", "HONEY_PRODUCTION_ROWS", "down"),
    ("HONEY_PRODUCTION_ROWS", "HONEY_PACKAGING_ROWS", "down"),
    ("CSR_ONLINE_BATCHES", "CSR_ONLINE_ORDER_LINES", "down"),
    ("CSR_ONLINE_ORDER_LINES", "ONLINE_ORDER_PARCELS", "down"),
    ("ONLINE_ORDER_PARCELS", "RTS_CASES", "down"),
    ("RTS_CASES", "RTS_LINE_ITEMS", "down"),
    ("DELIVERY_BATCHES", "DELIVERY_BATCH_ITEMS", "down"),
    ("SUPPLIERS", "HONEY_WAREHOUSE_ROWS", "right"),
    ("STOCKS", "PRODUCT_FORMULA", "right"),
    ("USERS", "CSR_ONLINE_BATCHES", "top_bus"),
    ("USERS", "DELIVERY_BATCHES", "top_bus"),
    ("USERS", "POS_SALES", "corridor"),
    ("DEPARTMENTS", "STOCKS", "right"),
    ("DEPARTMENTS", "PRODUCTS", "top_from_dept"),
    ("DEPARTMENTS", "STOCK_ENTRIES", "right"),
    ("PRODUCTS", "POS_RECEIPT_ITEMS", "right_bypass"),
    ("CSR_ONLINE_BATCHES", "ONLINE_ORDER_PARCELS", "right_bypass"),
    ("ONLINE_ORDER_PARCELS", "DELIVERY_BATCH_ITEMS", "right"),
]


def measure_box(data: dict) -> tuple[int, int]:
    n_cols = len(data["cols"])
    n_keys = len(data["keys"])
    h = HDR_H + n_cols * ROW_H + 4 + KEYS_HDR_H + n_keys * 13 + PAD
    return BOX_W, h


def anchor(box, side: str, t: float = 0.5):
    x1, y1, x2, y2 = box
    t = max(0.15, min(0.85, t))
    if side == "L":
        return (x1, y1 + (y2 - y1) * t)
    if side == "R":
        return (x2, y1 + (y2 - y1) * t)
    if side == "T":
        return (x1 + (x2 - x1) * t, y1)
    return (x1 + (x2 - x1) * t, y2)


def draw_poly(draw, pts, width=2):
    for i in range(len(pts) - 1):
        draw.line((pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1]), fill=LINE, width=width)


def draw_crows(draw, tip, prev):
    tx, ty = tip
    ox, oy = prev
    ang = math.atan2(ty - oy, tx - ox)
    for delta in (-0.5, 0.0, 0.5):
        a = ang + delta
        draw.line((tx, ty, tx - 10 * math.cos(a), ty - 10 * math.sin(a)), fill=LINE, width=2)


def draw_one(draw, tip, nxt):
    tx, ty = tip
    ox, oy = nxt
    ang = math.atan2(oy - ty, ox - tx)
    px, py = -math.sin(ang) * 6, math.cos(ang) * 6
    mx = tx + 9 * math.cos(ang)
    my = ty + 9 * math.sin(ang)
    draw.line((mx - px, my - py, mx + px, my + py), fill=LINE, width=2)


def route(boxes, a, b, kind, slot=0.5, top_y=155):
    ba, bb = boxes[a], boxes[b]
    if kind == "down":
        return [anchor(ba, "B"), anchor(bb, "T")]
    if kind == "up":
        return [anchor(ba, "T"), anchor(bb, "B")]
    if kind == "right":
        p0, p1 = anchor(ba, "R", slot), anchor(bb, "L", slot)
        mx = (p0[0] + p1[0]) / 2
        return [p0, (mx, p0[1]), (mx, p1[1]), p1]
    if kind == "top_bus":
        p0 = anchor(ba, "T", slot)
        p1 = anchor(bb, "T", 0.35)
        return [p0, (p0[0], top_y), (p1[0], top_y), p1]
    if kind == "top_from_dept":
        p0 = anchor(ba, "T", 0.7)
        p1 = anchor(bb, "T", 0.65)
        ty = top_y + 16
        return [p0, (p0[0], ty), (p1[0], ty), p1]
    if kind == "corridor":
        p0 = anchor(ba, "R", 0.75)
        p1 = anchor(bb, "L", 0.4)
        corridor = ba[2] + 30
        return [p0, (corridor, p0[1]), (corridor, p1[1]), p1]
    if kind == "right_bypass":
        p0 = anchor(ba, "R", slot)
        same_col = abs((ba[0] + ba[2]) / 2 - (bb[0] + bb[2]) / 2) < 20
        if same_col:
            gutter = ba[2] + 36
            p1 = anchor(bb, "R", slot)
            return [p0, (gutter, p0[1]), (gutter, p1[1]), p1]
        p1 = anchor(bb, "L", slot)
        mx = (p0[0] + p1[0]) / 2
        return [p0, (mx, p0[1]), (mx, p1[1]), p1]
    p0, p1 = anchor(ba, "R", slot), anchor(bb, "L", slot)
    mx = (p0[0] + p1[0]) / 2
    return [p0, (mx, p0[1]), (mx, p1[1]), p1]


def draw_badge(draw, x, y, letter: str):
    fill = P_BADGE if letter == "P" else F_BADGE
    if letter == "PF":
        # dual badge
        draw.ellipse((x, y, x + 12, y + 12), fill=P_BADGE, outline=BORDER)
        draw.text((x + 2, y - 1), "P", font=font_keys_hdr, fill=INK)
        draw.ellipse((x + 14, y, x + 26, y + 12), fill=F_BADGE, outline=BORDER)
        draw.text((x + 17, y - 1), "F", font=font_keys_hdr, fill=INK)
        return 28
    draw.ellipse((x, y, x + 12, y + 12), fill=fill, outline=BORDER)
    draw.text((x + 3, y - 1), letter, font=font_keys_hdr, fill=INK)
    return 14


def draw_table(draw, name: str, data: dict, box):
    x1, y1, x2, y2 = box
    # outer
    draw.rectangle((x1, y1, x2, y2), fill=ATTR_BG, outline=BORDER, width=2)
    # header
    draw.rectangle((x1, y1, x2, y1 + HDR_H), fill=HEADER, outline=BORDER, width=2)
    draw.text((x1 + 8, y1 + 5), name, font=font_hdr, fill=HEADER_TEXT)

    y = y1 + HDR_H + 2
    for col, typ, marker in data["cols"]:
        x = x1 + 6
        if marker:
            w = draw_badge(draw, x, y + 1, marker)
            x += w + 3
        else:
            x += 4
        label = f"{col}: {typ}"
        font = font_attr_b if marker in ("P", "PF") else font_attr
        draw.text((x, y), label, font=font, fill=INK)
        y += ROW_H

    # Keys band
    draw.line((x1, y + 1, x2, y + 1), fill=BORDER, width=1)
    draw.rectangle((x1 + 1, y + 2, x2 - 1, y2 - 1), fill=KEYS_BG, outline=KEYS_BG)
    draw.text((x1 + 8, y + 4), "Keys", font=font_keys_hdr, fill=INK)
    y += KEYS_HDR_H
    for k in data["keys"]:
        draw.text((x1 + 8, y), k, font=font_keys, fill=INK)
        y += 13


def generate(path: Path) -> None:
    boxes: dict[str, tuple[float, float, float, float]] = {}
    for name, data in TABLES.items():
        x, y = LAYOUT[name]
        bw, bh = measure_box(data)
        boxes[name] = (x, y, x + bw, y + bh)

    w = int(max(b[2] for b in boxes.values()) + 70)
    h = int(max(b[3] for b in boxes.values()) + 70)
    img = Image.new("RGB", (w, h), BG)
    draw = ImageDraw.Draw(img)

    title = "Database Design for Pyx Tracker"
    tb = draw.textbbox((0, 0), title, font=font_title)
    draw.text(((w - (tb[2] - tb[0])) / 2, 18), title, font=font_title, fill=INK)
    draw.text(
        (60, 56),
        "Physical schema from live rts_system (MySQL).  (P) = primary key  |  (F) = foreign key  |  Crow's-foot = 1 : N",
        font=font_sub,
        fill=MUTED,
    )
    draw.text(
        (60, 76),
        "Attribute panels list column types; Keys panels list PRIMARY / FOREIGN / UNIQUE / INDEX names. Distinct from the conceptual ERD (Figure 3.12).",
        font=font_sub,
        fill=MUTED,
    )
    for x, label in LANES:
        draw.text((x, 110), label, font=font_lane, fill=HEADER)

    counts: dict[str, int] = {}
    seen: dict[str, int] = {}
    for a, _b, _k in RELS:
        counts[a] = counts.get(a, 0) + 1
    slots: dict[tuple[str, str], float] = {}
    for a, b, _k in RELS:
        i = seen.get(a, 0)
        seen[a] = i + 1
        n = counts[a]
        slots[(a, b)] = 0.5 if n == 1 else 0.25 + 0.5 * i / max(n - 1, 1)

    for a, b, kind in RELS:
        pts = route(boxes, a, b, kind, slots[(a, b)])
        draw_poly(draw, pts)
        draw_one(draw, pts[0], pts[1])
        draw_crows(draw, pts[-1], pts[-2])

    for name, data in TABLES.items():
        draw_table(draw, name, data, boxes[name])

    draw.text(
        (60, h - 32),
        "Source: INFORMATION_SCHEMA / live rts_system. ENUM(...) shortened for diagram readability; full values appear in the Data Dictionary.",
        font=font_sub,
        fill=MUTED,
    )
    img.save(path)
    print(f"wrote {path} {img.size} {path.stat().st_size} bytes")


def patch_db_design_only(doc_path: Path, png: Path) -> None:
    data_png = png.read_bytes()
    tmp = doc_path.parent / (doc_path.name + ".dbdesign.tmpzip")
    replaced = 0
    with zipfile.ZipFile(doc_path, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            name = info.filename.replace("\\", "/")
            if name == "word/media/image13.png":
                data = data_png
                replaced += 1
                print("replaced Database Design media:", name)
            elif name == "word/media/image14.png":
                print("preserved ERD media:", name, len(data), "bytes")
            zout.writestr(info, data)
    if replaced != 1:
        raise RuntimeError(f"Expected 1 image13.png replacement, got {replaced}")
    try:
        tmp.replace(doc_path)
    except PermissionError:
        blob = tmp.read_bytes()
        tmp.unlink(missing_ok=True)
        doc_path.write_bytes(blob)
    print(f"updated {doc_path} ({doc_path.stat().st_size} bytes)")


def update_caption(doc_path: Path) -> None:
    from docx import Document

    d = Document(str(doc_path))
    for p in d.paragraphs:
        t = p.text.strip()
        if t.startswith("Figure 3.11"):
            new = "Figure 3.11  Database Design of Pyx Tracker"
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = new
            else:
                p.add_run(new)
        if t.startswith("The shared relational database contains identity"):
            new = (
                "Figure 3.11 presents the physical Database Design of Pyx Tracker derived from the live "
                "rts_system MySQL schema. Each entity box lists typed columns with (P) primary-key and "
                "(F) foreign-key markers, followed by a Keys panel naming PRIMARY, FOREIGN, UNIQUE, and "
                "INDEX constraints. Functional-area columns group identity, inventory, products/POS, honey "
                "production, online/RTS, and delivery tables. This physical design is separate from the "
                "conceptual Entity Relationship Diagram in Figure 3.12."
            )
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = new
            else:
                p.add_run(new)
    d.save(str(doc_path))
    print("updated Figure 3.11 caption/description")


def main() -> None:
    src = SRC_DOC if SRC_DOC.exists() else FALLBACK_DOC
    if not src.exists():
        raise FileNotFoundError(src)

    DESKTOP_DIR.mkdir(parents=True, exist_ok=True)
    # Work on a copy so we never leave desktop half-written
    work = FALLBACK_DOC
    shutil.copy2(src, work)

    # Remember ERD bytes before patch to prove they are unchanged
    with zipfile.ZipFile(work) as z:
        erd_before = z.read("word/media/image14.png")

    generate(OUT_IMG)
    patch_db_design_only(work, OUT_IMG)
    update_caption(work)

    with zipfile.ZipFile(work) as z:
        erd_after = z.read("word/media/image14.png")
        db_after = z.read("word/media/image13.png")
    if erd_before != erd_after:
        raise RuntimeError("ERD image14.png changed — aborting")
    if db_after != OUT_IMG.read_bytes():
        raise RuntimeError("Database Design image13.png was not updated correctly")

    shutil.copy2(work, DESKTOP_DOC)
    shutil.copy2(OUT_IMG, DESKTOP_PNG)
    print(f"saved {DESKTOP_DOC} ({DESKTOP_DOC.stat().st_size} bytes)")
    print(f"saved {DESKTOP_PNG} ({DESKTOP_PNG.stat().st_size} bytes)")
    print("ERD unchanged: OK")
    print("DONE")


if __name__ == "__main__":
    main()
