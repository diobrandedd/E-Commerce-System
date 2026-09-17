"""Regenerate a clean single-page ERD and replace media in the existing DOCX in place."""

from __future__ import annotations

import math
import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

DOC = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new")
OUT_IMG = Path(r"C:/xampp/htdocs/rts/output/documents/_erd_pyx_tracker.png")
DESKTOP = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx")

BG = "#FFFFFF"
INK = "#1A1A1A"
HEADER = "#2F80ED"
HEADER_TEXT = "#FFFFFF"
BORDER = "#333333"
MUTED = "#555555"
LINE = "#5A5A5A"
PK_BG = "#FFF3B0"

font_reg = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 16)
font_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 34)
font_sub = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 18)
font_hdr = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 17)
font_attr = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 13)
font_attr_b = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_pk = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 10)
font_lane = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 14)
font_rel = ImageFont.truetype(r"C:/Windows/Fonts/ariali.ttf", 12)  # italic relationship comments

ENTITIES = {
    "ROLES": {"pk": "id", "attrs": ["name", "created_at"]},
    "DEPARTMENTS": {"pk": "id", "attrs": ["name", "created_at"]},
    "USERS": {
        "pk": "id",
        "attrs": [
            "username",
            "name",
            "email",
            "employee_id",
            "password_hash",
            "role_id (FK)",
            "dept_id (FK)",
            "is_active",
            "created_at",
            "updated_at",
        ],
    },
    "SUPPLIERS": {
        "pk": "id",
        "attrs": [
            "first_name",
            "last_name",
            "contact_number",
            "address",
            "category",
            "is_active",
            "created_by (FK)",
            "created_at",
        ],
    },
    "STOCKS": {
        "pk": "id",
        "attrs": [
            "name",
            "dept_id (FK)",
            "supplier_id (FK)",
            "unit",
            "category",
            "on_hand",
            "current_price",
            "created_by (FK)",
            "created_at",
        ],
    },
    "STOCK_ENTRIES": {
        "pk": "id",
        "attrs": [
            "stock_id (FK)",
            "dept_id (FK)",
            "qty",
            "batching_date",
            "supplier_batch_no",
            "entered_by (FK)",
            "created_at",
        ],
    },
    "PRODUCTS": {
        "pk": "id",
        "attrs": [
            "name",
            "dept_product_key",
            "dept_id (FK)",
            "size",
            "variant",
            "selling_price",
            "baseline_cost",
            "created_by (FK)",
            "created_at",
        ],
    },
    "PRODUCT_FORMULA": {
        "pk": "id",
        "attrs": [
            "product_id (FK)",
            "stock_id (FK)",
            "line_label",
            "qty_per_unit",
            "price_at_creation",
            "created_at",
        ],
    },
    "HONEY_WAREHOUSE_ROWS": {
        "pk": "id",
        "attrs": [
            "supplier_id (FK)",
            "supplier_code",
            "arrival_date",
            "variant",
            "current_qty",
            "initial_qty",
            "created_by (FK)",
            "created_at",
        ],
    },
    "HONEY_PRODUCTION_ROWS": {
        "pk": "id",
        "attrs": [
            "warehouse_row_id (FK)",
            "supplier_code",
            "stock_out_date",
            "variant",
            "current_qty",
            "initial_qty",
            "is_active",
            "created_by (FK)",
        ],
    },
    "HONEY_PACKAGING_ROWS": {
        "pk": "id",
        "attrs": [
            "production_row_id (FK)",
            "batch_no",
            "bottling_date",
            "variant",
            "current_liter",
            "current_250ml",
            "current_350ml",
            "current_gallon",
            "created_by (FK)",
        ],
    },
    "POS_SALES": {
        "pk": "id",
        "attrs": [
            "receipt_no",
            "buyer_name",
            "seller_name",
            "total_amount",
            "sold_by_user_id (FK)",
            "sale_date",
            "is_ar",
            "created_at",
        ],
    },
    "POS_RECEIPT_ITEMS": {
        "pk": "id",
        "attrs": [
            "sale_id (FK)",
            "product_id (FK)",
            "qty",
            "unit_price",
            "line_total",
            "variant",
            "size",
            "created_at",
        ],
    },
    "CSR_ONLINE_BATCHES": {
        "pk": "id",
        "attrs": ["user_id (FK)", "status", "created_at", "completed_at", "master_filename"],
    },
    "CSR_ONLINE_ORDER_LINES": {
        "pk": "id",
        "attrs": [
            "batch_id (FK)",
            "order_no",
            "recipient_name",
            "tracking_number",
            "courier",
            "source_pipeline",
            "created_at",
        ],
    },
    "ONLINE_ORDER_PARCELS": {
        "pk": "id",
        "attrs": [
            "csr_batch_id (FK)",
            "csr_line_id (FK)",
            "tracking_number",
            "status",
            "ship_date",
            "rts_case_id (FK)",
            "created_at",
        ],
    },
    "DELIVERY_BATCHES": {
        "pk": "id",
        "attrs": ["submitted_by_user_id (FK)", "delivery_date", "submitted_at"],
    },
    "DELIVERY_BATCH_ITEMS": {
        "pk": "id",
        "attrs": [
            "batch_id (FK)",
            "tracking_number",
            "item_status",
            "online_parcel_id (FK)",
            "photo_filename",
            "created_at",
        ],
    },
    "RTS_CASES": {
        "pk": "id",
        "attrs": [
            "tracking_number",
            "courier_final",
            "shop",
            "date_opened",
            "claim_status",
            "case_disposition",
            "voided_at",
            "created_at",
        ],
    },
    "RTS_LINE_ITEMS": {
        "pk": "id",
        "attrs": [
            "case_id (FK)",
            "product_type",
            "honey_size",
            "honey_variant",
            "quantity",
            "line_role",
            "created_at",
        ],
    },
}

BOX_W = 300
LAYOUT = {
    "ROLES": (80, 210),
    "USERS": (80, 430),
    "DEPARTMENTS": (80, 980),
    "SUPPLIERS": (500, 210),
    "STOCKS": (500, 560),
    "STOCK_ENTRIES": (500, 1040),
    "PRODUCTS": (960, 210),
    "PRODUCT_FORMULA": (960, 680),
    "POS_SALES": (960, 1100),
    "POS_RECEIPT_ITEMS": (960, 1540),
    "HONEY_WAREHOUSE_ROWS": (1440, 210),
    "HONEY_PRODUCTION_ROWS": (1440, 640),
    "HONEY_PACKAGING_ROWS": (1440, 1120),
    "CSR_ONLINE_BATCHES": (1920, 210),
    "CSR_ONLINE_ORDER_LINES": (1920, 520),
    "ONLINE_ORDER_PARCELS": (1920, 920),
    "RTS_CASES": (1920, 1380),
    "RTS_LINE_ITEMS": (1920, 1860),
    "DELIVERY_BATCHES": (2420, 210),
    "DELIVERY_BATCH_ITEMS": (2420, 470),
}

LANES = [
    (80, "Identity"),
    (500, "Inventory"),
    (960, "Products / POS"),
    (1440, "Honey"),
    (1920, "Online / RTS"),
    (2420, "Delivery"),
]

# (one_side, many_side, route_kind, relationship comment) — comment style: "...has a..."
RELS = [
    ("ROLES", "USERS", "down", "assigns"),
    ("DEPARTMENTS", "USERS", "up", "employs"),
    ("SUPPLIERS", "STOCKS", "down", "supplies"),
    ("STOCKS", "STOCK_ENTRIES", "down", "has a"),
    ("PRODUCTS", "PRODUCT_FORMULA", "down", "has a"),
    ("POS_SALES", "POS_RECEIPT_ITEMS", "down", "contains"),
    ("HONEY_WAREHOUSE_ROWS", "HONEY_PRODUCTION_ROWS", "down", "feeds"),
    ("HONEY_PRODUCTION_ROWS", "HONEY_PACKAGING_ROWS", "down", "bottles into"),
    ("CSR_ONLINE_BATCHES", "CSR_ONLINE_ORDER_LINES", "down", "has a"),
    ("CSR_ONLINE_ORDER_LINES", "ONLINE_ORDER_PARCELS", "down", "ships as"),
    ("ONLINE_ORDER_PARCELS", "RTS_CASES", "down", "may return as"),
    ("RTS_CASES", "RTS_LINE_ITEMS", "down", "lists"),
    ("DELIVERY_BATCHES", "DELIVERY_BATCH_ITEMS", "down", "has a"),
    ("SUPPLIERS", "HONEY_WAREHOUSE_ROWS", "right", "delivers"),
    ("STOCKS", "PRODUCT_FORMULA", "right", "used in"),
    ("USERS", "CSR_ONLINE_BATCHES", "top_bus", "prepares"),
    ("USERS", "DELIVERY_BATCHES", "top_bus", "submits"),
    ("USERS", "POS_SALES", "corridor", "records"),
    ("DEPARTMENTS", "STOCKS", "right", "owns"),
    ("DEPARTMENTS", "PRODUCTS", "top_from_dept", "catalogs"),
    ("DEPARTMENTS", "STOCK_ENTRIES", "right", "tracks"),
    ("PRODUCTS", "POS_RECEIPT_ITEMS", "right_bypass", "sold as"),
    ("CSR_ONLINE_BATCHES", "ONLINE_ORDER_PARCELS", "right_bypass", "creates"),
    ("ONLINE_ORDER_PARCELS", "DELIVERY_BATCH_ITEMS", "right", "delivered in"),
]


def measure_box(data: dict) -> tuple[int, int]:
    return BOX_W, 34 + 24 + len(data["attrs"]) * 18 + 10


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


def midpoint_of_polyline(pts):
    """Point near the visual middle of the connector for the relationship comment."""
    if len(pts) == 2:
        return ((pts[0][0] + pts[1][0]) / 2, (pts[0][1] + pts[1][1]) / 2)
    # Prefer the middle segment of elbow routes
    mid_i = len(pts) // 2 - 1
    a, b = pts[mid_i], pts[mid_i + 1]
    return ((a[0] + b[0]) / 2, (a[1] + b[1]) / 2)


def draw_rel_comment(draw, pts, comment: str):
    """Draw italic relationship comment on the line, e.g. ----has a----> style."""
    if not comment:
        return
    cx, cy = midpoint_of_polyline(pts)
    label = f" {comment} "
    bbox = draw.textbbox((0, 0), label, font=font_rel)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    pad_x, pad_y = 3, 1
    x0 = cx - tw / 2 - pad_x
    y0 = cy - th / 2 - pad_y
    x1 = cx + tw / 2 + pad_x
    y1 = cy + th / 2 + pad_y
    draw.rectangle((x0, y0, x1, y1), fill=BG)
    draw.text((cx - tw / 2, cy - th / 2 - 1), label, font=font_rel, fill=INK)


def draw_crows(draw, tip, prev):
    tx, ty = tip
    ox, oy = prev
    ang = math.atan2(ty - oy, tx - ox)
    for delta in (-0.5, 0.0, 0.5):
        a = ang + delta
        draw.line(
            (tx, ty, tx - 11 * math.cos(a), ty - 11 * math.sin(a)),
            fill=LINE,
            width=2,
        )


def draw_one(draw, tip, nxt):
    tx, ty = tip
    ox, oy = nxt
    ang = math.atan2(oy - ty, ox - tx)
    px, py = -math.sin(ang) * 7, math.cos(ang) * 7
    mx = tx + 10 * math.cos(ang)
    my = ty + 10 * math.sin(ang)
    draw.line((mx - px, my - py, mx + px, my + py), fill=LINE, width=2)


def route(boxes, a, b, kind, slot=0.5, top_y=168):
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
        ty = top_y + 18
        return [p0, (p0[0], ty), (p1[0], ty), p1]
    if kind == "corridor":
        p0 = anchor(ba, "R", 0.75)
        p1 = anchor(bb, "L", 0.4)
        corridor = ba[2] + 35
        return [p0, (corridor, p0[1]), (corridor, p1[1]), p1]
    if kind == "right_bypass":
        p0 = anchor(ba, "R", slot)
        same_col = abs((ba[0] + ba[2]) / 2 - (bb[0] + bb[2]) / 2) < 20
        if same_col:
            gutter = ba[2] + 40
            p1 = anchor(bb, "R", slot)
            return [p0, (gutter, p0[1]), (gutter, p1[1]), p1]
        p1 = anchor(bb, "L", slot)
        mx = (p0[0] + p1[0]) / 2
        return [p0, (mx, p0[1]), (mx, p1[1]), p1]
    p0, p1 = anchor(ba, "R", slot), anchor(bb, "L", slot)
    mx = (p0[0] + p1[0]) / 2
    return [p0, (mx, p0[1]), (mx, p1[1]), p1]


def generate_erd(path: Path) -> None:
    boxes: dict[str, tuple[float, float, float, float]] = {}
    for name, data in ENTITIES.items():
        x, y = LAYOUT[name]
        bw, bh = measure_box(data)
        boxes[name] = (x, y, x + bw, y + bh)

    w = int(max(b[2] for b in boxes.values()) + 90)
    h = int(max(b[3] for b in boxes.values()) + 80)
    img = Image.new("RGB", (w, h), BG)
    draw = ImageDraw.Draw(img)

    draw.text((80, 24), "Entity Relationship Diagram", font=font_title, fill=INK)
    draw.text(
        (82, 66),
        "Figure 3.12: Pyx Tracker — Web-based Enterprise Operations Management System",
        font=font_sub,
        fill=MUTED,
    )
    draw.text(
        (82, 92),
        "Single schema ERD (live rts_system). Crow's-foot = 1 : N. Italic line comments name each relationship (e.g. has a).",
        font=font_sub,
        fill=MUTED,
    )
    for x, label in LANES:
        draw.text((x, 130), label, font=font_lane, fill=HEADER)

    counts: dict[str, int] = {}
    seen: dict[str, int] = {}
    for a, _b, _k, _c in RELS:
        counts[a] = counts.get(a, 0) + 1
    slots: dict[tuple[str, str], float] = {}
    for a, b, _k, _c in RELS:
        i = seen.get(a, 0)
        seen[a] = i + 1
        n = counts[a]
        slots[(a, b)] = 0.5 if n == 1 else 0.25 + 0.5 * i / max(n - 1, 1)

    for a, b, kind, comment in RELS:
        pts = route(boxes, a, b, kind, slots[(a, b)])
        draw_poly(draw, pts)
        draw_one(draw, pts[0], pts[1])
        draw_crows(draw, pts[-1], pts[-2])
        draw_rel_comment(draw, pts, comment)

    for name, data in ENTITIES.items():
        x1, y1, x2, y2 = boxes[name]
        draw.rectangle((x1, y1, x2, y2), fill="white", outline=BORDER, width=2)
        draw.rectangle((x1, y1, x2, y1 + 30), fill=HEADER, outline=BORDER, width=2)
        draw.text((x1 + 8, y1 + 6), name, font=font_hdr, fill=HEADER_TEXT)
        y = y1 + 36
        draw.rounded_rectangle((x1 + 7, y, x1 + 26, y + 15), radius=2, fill=PK_BG, outline="#D4A017")
        draw.text((x1 + 10, y), "PK", font=font_pk, fill="#7A5A00")
        draw.text((x1 + 30, y - 1), data["pk"], font=font_attr_b, fill=INK)
        y += 20
        draw.line((x1 + 5, y - 3, x2 - 5, y - 3), fill="#CCCCCC", width=1)
        for attr in data["attrs"]:
            draw.text((x1 + 10, y), attr, font=font_attr, fill=INK)
            y += 18

    draw.text(
        (80, h - 36),
        "Source: live rts_system schema / SQL migrations. One diagram — relationship comments follow [entity] ----verb----> [entity] style.",
        font=font_reg,
        fill=MUTED,
    )
    img.save(path)
    print(f"wrote {path} {img.size} {path.stat().st_size} bytes")


def patch_docx_inplace(doc_path: Path, erd_png: Path) -> None:
    if not doc_path.exists():
        raise FileNotFoundError(doc_path)
    png = erd_png.read_bytes()
    tmp = doc_path.parent / (doc_path.name + ".tmpzip")
    with zipfile.ZipFile(doc_path, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            name = info.filename.replace("\\", "/")
            if name in {
                "word/media/image14.png",
                "word/media/image15.png",
                "word/media/image16.png",
                "word/media/image17.png",
            }:
                data = png
                print("replaced", name)
            zout.writestr(info, data)
    # Replace in place; if locked, write bytes directly after unlink attempt
    try:
        tmp.replace(doc_path)
    except PermissionError:
        # File may be open — overwrite via read/write on a short retry
        data = tmp.read_bytes()
        tmp.unlink(missing_ok=True)
        doc_path.write_bytes(data)
    print(f"updated in place {doc_path} ({doc_path.stat().st_size} bytes)")


def main() -> None:
    generate_erd(OUT_IMG)
    patch_docx_inplace(DOC, OUT_IMG)
    DESKTOP.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(DOC, DESKTOP)
    print(f"mirrored {DESKTOP} ({DESKTOP.stat().st_size} bytes)")
    assert DOC.exists() and DOC.stat().st_size > 0
    assert DESKTOP.exists() and DESKTOP.stat().st_size > 0
    print("DONE")


if __name__ == "__main__":
    main()
