"""Regenerate Pyx Tracker ERD in sample academic style and patch the capstone DOCX.

Visual conventions match the institutional sample ERD:
- Entity boxes with blue headers and light body fill
- # primary key, * attributes (FK noted in attribute text)
- Crow's-foot 1:N connectors with bidirectional verb labels
"""

from __future__ import annotations

import math
import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SRC_DOC = Path(r"C:/Users/Admin/Downloads/Pyx_Tracker_Capstone_SQL_ERD.docx")
WORK_DOC = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx")
OUT_IMG = Path(r"C:/xampp/htdocs/rts/output/documents/_erd_pyx_tracker.png")
DESKTOP_DIR = Path(r"C:/Users/Admin/Desktop/capstone")
DESKTOP_DOC = DESKTOP_DIR / "Pyx_Tracker_Capstone_SQL_ERD.docx"

# Sample-style palette (blue headers, light entity bodies)
BG = "#FFFFFF"
INK = "#1F2A37"
HEADER = "#2F80ED"
HEADER_TEXT = "#FFFFFF"
BODY_FILL = "#F7FBFF"
BORDER = "#2F80ED"
MUTED = "#5B6B7C"
LINE = "#4A5568"
DASHED = "#7A8699"

font_reg = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 15)
font_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 32)
font_sub = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 16)
font_hdr = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 15)
font_attr = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 13)
font_attr_b = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_lane = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_rel = ImageFont.truetype(r"C:/Windows/Fonts/ariali.ttf", 11)

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

# (one_side, many_side, route_kind, from_verb, to_verb, dashed)
# from_verb sits near the one-side; to_verb sits near the many-side (sample style).
RELS = [
    ("ROLES", "USERS", "down", "Assigns", "Assigned to", False),
    ("DEPARTMENTS", "USERS", "up", "Employs", "Belongs to", False),
    ("SUPPLIERS", "STOCKS", "down", "Supplies", "Sourced from", False),
    ("STOCKS", "STOCK_ENTRIES", "down", "Records", "Logged under", False),
    ("PRODUCTS", "PRODUCT_FORMULA", "down", "Defines", "Composes", False),
    ("POS_SALES", "POS_RECEIPT_ITEMS", "down", "Contains", "Listed in", False),
    ("HONEY_WAREHOUSE_ROWS", "HONEY_PRODUCTION_ROWS", "down", "Feeds", "Drawn from", False),
    ("HONEY_PRODUCTION_ROWS", "HONEY_PACKAGING_ROWS", "down", "Bottles into", "Packed from", False),
    ("CSR_ONLINE_BATCHES", "CSR_ONLINE_ORDER_LINES", "down", "Groups", "Belongs to", False),
    ("CSR_ONLINE_ORDER_LINES", "ONLINE_ORDER_PARCELS", "down", "Ships as", "Created from", False),
    ("ONLINE_ORDER_PARCELS", "RTS_CASES", "down", "May return as", "Linked to", True),
    ("RTS_CASES", "RTS_LINE_ITEMS", "down", "Lists", "Itemized in", False),
    ("DELIVERY_BATCHES", "DELIVERY_BATCH_ITEMS", "down", "Contains", "Packed in", False),
    ("SUPPLIERS", "HONEY_WAREHOUSE_ROWS", "right", "Delivers", "Received from", False),
    ("STOCKS", "PRODUCT_FORMULA", "right", "Used in", "Requires", True),
    ("USERS", "CSR_ONLINE_BATCHES", "top_bus", "Prepares", "Prepared by", False),
    ("USERS", "DELIVERY_BATCHES", "top_bus", "Submits", "Submitted by", False),
    ("USERS", "POS_SALES", "corridor", "Records", "Sold by", False),
    ("DEPARTMENTS", "STOCKS", "right", "Owns", "Assigned to", False),
    ("DEPARTMENTS", "PRODUCTS", "top_from_dept", "Catalogs", "Belongs to", False),
    ("DEPARTMENTS", "STOCK_ENTRIES", "right", "Tracks", "Recorded under", False),
    ("PRODUCTS", "POS_RECEIPT_ITEMS", "right_bypass", "Sold as", "Sold from", False),
    ("CSR_ONLINE_BATCHES", "ONLINE_ORDER_PARCELS", "right_bypass", "Creates", "Issued from", True),
    ("ONLINE_ORDER_PARCELS", "DELIVERY_BATCH_ITEMS", "right", "Delivered in", "Scans", False),
]


def measure_box(data: dict) -> tuple[int, int]:
    # header + pk line + attrs + padding (sample #/* density)
    return BOX_W, 34 + 20 + len(data["attrs"]) * 18 + 12


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


def draw_poly(draw, pts, width=2, dashed=False):
    if not dashed:
        for i in range(len(pts) - 1):
            draw.line((pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1]), fill=LINE, width=width)
        return
    # approximate dashed segments
    for i in range(len(pts) - 1):
        x0, y0 = pts[i]
        x1, y1 = pts[i + 1]
        length = math.hypot(x1 - x0, y1 - y0) or 1
        dx, dy = (x1 - x0) / length, (y1 - y0) / length
        pos = 0.0
        on = True
        dash, gap = 8.0, 6.0
        while pos < length:
            seg = dash if on else gap
            end = min(pos + seg, length)
            if on:
                sx, sy = x0 + dx * pos, y0 + dy * pos
                ex, ey = x0 + dx * end, y0 + dy * end
                draw.line((sx, sy, ex, ey), fill=DASHED, width=width)
            pos = end
            on = not on


def point_along(pts, t: float):
    """Point at fraction t along total polyline length (0..1)."""
    segs = []
    total = 0.0
    for i in range(len(pts) - 1):
        a, b = pts[i], pts[i + 1]
        d = math.hypot(b[0] - a[0], b[1] - a[1])
        segs.append((a, b, d))
        total += d
    if total <= 0:
        return pts[0]
    target = total * max(0.0, min(1.0, t))
    acc = 0.0
    for a, b, d in segs:
        if acc + d >= target:
            u = 0 if d == 0 else (target - acc) / d
            return (a[0] + (b[0] - a[0]) * u, a[1] + (b[1] - a[1]) * u)
        acc += d
    return pts[-1]


def draw_rel_label(draw, pts, text: str, t: float):
    if not text:
        return
    cx, cy = point_along(pts, t)
    label = f" {text} "
    bbox = draw.textbbox((0, 0), label, font=font_rel)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    pad_x, pad_y = 3, 1
    x0 = cx - tw / 2 - pad_x
    y0 = cy - th / 2 - pad_y - 8
    x1 = cx + tw / 2 + pad_x
    y1 = cy + th / 2 + pad_y - 8
    draw.rectangle((x0, y0, x1, y1), fill=BG)
    draw.text((cx - tw / 2, cy - th / 2 - 9), label, font=font_rel, fill=INK)


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

    title = "Entity Relationship Diagram for Pyx Tracker"
    title_bbox = draw.textbbox((0, 0), title, font=font_title)
    title_w = title_bbox[2] - title_bbox[0]
    draw.text(((w - title_w) / 2, 22), title, font=font_title, fill=INK)
    draw.text(
        (82, 68),
        "Web-based Enterprise Operations Management System  |  Crow's-foot = 1 : N  |  # = PK, * = attribute",
        font=font_sub,
        fill=MUTED,
    )
    draw.text(
        (82, 92),
        "Derived from live rts_system schema / SQL migrations. Bidirectional relationship verbs follow the institutional ERD sample style.",
        font=font_sub,
        fill=MUTED,
    )
    for x, label in LANES:
        draw.text((x, 128), label, font=font_lane, fill=HEADER)

    counts: dict[str, int] = {}
    seen: dict[str, int] = {}
    for a, _b, _k, _f, _t, _d in RELS:
        counts[a] = counts.get(a, 0) + 1
    slots: dict[tuple[str, str], float] = {}
    for a, b, _k, _f, _t, _d in RELS:
        i = seen.get(a, 0)
        seen[a] = i + 1
        n = counts[a]
        slots[(a, b)] = 0.5 if n == 1 else 0.25 + 0.5 * i / max(n - 1, 1)

    for a, b, kind, from_verb, to_verb, dashed in RELS:
        pts = route(boxes, a, b, kind, slots[(a, b)])
        draw_poly(draw, pts, dashed=dashed)
        draw_one(draw, pts[0], pts[1])
        draw_crows(draw, pts[-1], pts[-2])
        draw_rel_label(draw, pts, from_verb, 0.22)
        draw_rel_label(draw, pts, to_verb, 0.78)

    for name, data in ENTITIES.items():
        x1, y1, x2, y2 = boxes[name]
        draw.rounded_rectangle((x1, y1, x2, y2), radius=8, fill=BODY_FILL, outline=BORDER, width=2)
        draw.rounded_rectangle((x1, y1, x2, y1 + 30), radius=8, fill=HEADER, outline=BORDER, width=2)
        # square off bottom of header band
        draw.rectangle((x1, y1 + 16, x2, y1 + 30), fill=HEADER, outline=HEADER)
        draw.line((x1, y1 + 30, x2, y1 + 30), fill=BORDER, width=1)
        draw.text((x1 + 10, y1 + 6), name, font=font_hdr, fill=HEADER_TEXT)

        y = y1 + 38
        # sample style: # pk
        draw.text((x1 + 10, y), f"# {data['pk']}", font=font_attr_b, fill=INK)
        y += 18
        draw.line((x1 + 8, y - 2, x2 - 8, y - 2), fill="#D0D7E2", width=1)
        for attr in data["attrs"]:
            draw.text((x1 + 10, y), f"* {attr}", font=font_attr, fill=INK)
            y += 18

    draw.text(
        (80, h - 36),
        "Source: live rts_system schema. Solid = required association; dashed = optional / conditional link.",
        font=font_reg,
        fill=MUTED,
    )
    img.save(path)
    print(f"wrote {path} {img.size} {path.stat().st_size} bytes")


def patch_docx(doc_path: Path, erd_png: Path) -> None:
    if not doc_path.exists():
        raise FileNotFoundError(doc_path)
    png = erd_png.read_bytes()
    tmp = doc_path.parent / (doc_path.name + ".tmpzip")
    replaced = 0
    with zipfile.ZipFile(doc_path, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            name = info.filename.replace("\\", "/")
            # Only replace the ERD media slot (Figure 3.12), not Network Design images.
            if name == "word/media/image14.png":
                data = png
                replaced += 1
                print("replaced", name)
            zout.writestr(info, data)
    if replaced != 1:
        raise RuntimeError(f"Expected to replace image14.png once, got {replaced}")
    try:
        tmp.replace(doc_path)
    except PermissionError:
        data = tmp.read_bytes()
        tmp.unlink(missing_ok=True)
        doc_path.write_bytes(data)
    print(f"updated {doc_path} ({doc_path.stat().st_size} bytes)")


def main() -> None:
    if not SRC_DOC.exists():
        raise FileNotFoundError(SRC_DOC)

    DESKTOP_DIR.mkdir(parents=True, exist_ok=True)
    WORK_DOC.parent.mkdir(parents=True, exist_ok=True)

    # Start from the user-provided Downloads manuscript
    shutil.copy2(SRC_DOC, WORK_DOC)

    generate_erd(OUT_IMG)
    patch_docx(WORK_DOC, OUT_IMG)

    # Update ERD caption/description to mention sample-style notation
    from docx import Document

    d = Document(str(WORK_DOC))
    for p in d.paragraphs:
        t = p.text.strip()
        if t.startswith("Figure 3.12  Entity Relationship Diagram"):
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = "Figure 3.12  Entity Relationship Diagram of Pyx Tracker"
            else:
                p.add_run("Figure 3.12  Entity Relationship Diagram of Pyx Tracker")
        if t.startswith("Figure 3.12 presents the single Entity Relationship Diagram"):
            new = (
                "Figure 3.12 presents the single Entity Relationship Diagram of Pyx Tracker derived from the "
                "project SQL schema (full_schema.sql, applied migrations, and live rts_system metadata). "
                "Entity boxes follow the institutional sample style: primary keys are marked with # and "
                "attributes with *. Crow's-foot connectors show one-to-many relationships, with bidirectional "
                "verb labels on each end. Solid lines mark required associations; dashed lines mark optional "
                "or conditional links among inventory, honey production, products, POS, online orders, "
                "deliveries, and RTS cases."
            )
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = new
            else:
                p.add_run(new)
    d.save(str(WORK_DOC))

    shutil.copy2(WORK_DOC, DESKTOP_DOC)
    print(f"mirrored {DESKTOP_DOC} ({DESKTOP_DOC.stat().st_size} bytes)")

    assert DESKTOP_DOC.exists() and DESKTOP_DOC.stat().st_size > 0
    assert OUT_IMG.exists() and OUT_IMG.stat().st_size > 0
    print("DONE")


if __name__ == "__main__":
    main()
