"""Use Case Diagram aligned to approved features + Figure 3.4 FDD modules/subfunctions.

Six modules (same as FDD / proposal features):
1. Integrated Inventory Management
2. Product Costing and Pricing
3. Multi-Platform Orders and Waybills
4. Parcel Processing and Inventory Sync
5. Return-to-Sender Management
6. Point of Sale (Web + Mobile POS)

Actors: Executive, HR Personnel, Staff (no Customer).
"""

from __future__ import annotations

import math
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

DESKTOP = Path(r"C:\Users\Admin\Desktop\capstone")
SRC_DOC = DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx"
OUT_PNG = DESKTOP / "Pyx_Tracker_Use_Case_Diagram.png"
WORK_PNG = Path(r"C:\xampp\htdocs\rts\output\documents\_use_case_pyx_tracker.png")
OUT_DOCS = [
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_usecase.docx",
    Path(r"C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_SQL_ERD.docx"),
]

BG = "#FFFFFF"
INK = "#1A1A1A"
MUTED = "#555555"
BORDER = "#222222"
DASH = "#444444"
BAND = "#F3F6FA"

font_boundary = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 15)
font_actor = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_uc = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)
font_uc_b = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)
font_stereo = ImageFont.truetype(r"C:/Windows/Fonts/ariali.ttf", 9)
font_note = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)
font_band = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)


def text_size(draw, text, font):
    b = draw.textbbox((0, 0), text, font=font)
    return b[2] - b[0], b[3] - b[1]


def wrap_lines(text: str, max_chars: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    cur = ""
    for w in words:
        trial = (cur + " " + w).strip()
        if len(trial) <= max_chars:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines or [text]


def draw_actor(draw, cx, cy, label: str):
    r = 12
    draw.ellipse((cx - r, cy - 52, cx + r, cy - 52 + 2 * r), outline=INK, width=2)
    draw.line((cx, cy - 52 + 2 * r, cx, cy + 4), fill=INK, width=2)
    draw.line((cx - 24, cy - 18, cx + 24, cy - 18), fill=INK, width=2)
    draw.line((cx, cy + 4, cx - 18, cy + 40), fill=INK, width=2)
    draw.line((cx, cy + 4, cx + 18, cy + 40), fill=INK, width=2)
    for i, line in enumerate(label.split("\n")):
        tw, _ = text_size(draw, line, font_actor)
        draw.text((cx - tw / 2, cy + 46 + i * 15), line, font=font_actor, fill=INK)
    return (cx, cy - 8)


def draw_oval(draw, cx, cy, label: str, bold: bool = False, max_chars: int = 18):
    lines = wrap_lines(label, max_chars)
    font = font_uc_b if bold else font_uc
    max_w = 0
    total_h = 0
    line_sizes = []
    for line in lines:
        tw, th = text_size(draw, line, font)
        line_sizes.append((tw, th))
        max_w = max(max_w, tw)
        total_h += th + 1
    pad_x, pad_y = 14, 8
    rw = max(58, max_w / 2 + pad_x)
    rh = max(18, total_h / 2 + pad_y)
    box = (cx - rw, cy - rh, cx + rw, cy + rh)
    draw.ellipse(box, fill=BG, outline=BORDER, width=2)
    y = cy - total_h / 2
    for (tw, th), line in zip(line_sizes, lines):
        draw.text((cx - tw / 2, y), line, font=font, fill=INK)
        y += th + 1
    return box


def oval_edge(box, toward):
    x1, y1, x2, y2 = box
    cx, cy = (x1 + x2) / 2, (y1 + y2) / 2
    rx, ry = (x2 - x1) / 2, (y2 - y1) / 2
    dx, dy = toward[0] - cx, toward[1] - cy
    if dx == 0 and dy == 0:
        return (cx + rx, cy)
    scale = 1.0 / math.sqrt((dx * dx) / (rx * rx) + (dy * dy) / (ry * ry))
    return (cx + dx * scale, cy + dy * scale)


def polyline(draw, pts, fill=INK, width=1):
    cleaned = [pts[0]]
    for p in pts[1:]:
        if math.hypot(p[0] - cleaned[-1][0], p[1] - cleaned[-1][1]) > 1.5:
            cleaned.append(p)
    for i in range(len(cleaned) - 1):
        draw.line(
            (cleaned[i][0], cleaned[i][1], cleaned[i + 1][0], cleaned[i + 1][1]),
            fill=fill,
            width=width,
        )


def draw_assoc_ortho(draw, p0, box, channel_x: float):
    cy = (box[1] + box[3]) / 2
    p1 = oval_edge(box, (channel_x, cy))
    ax, ay = p0
    polyline(draw, [(ax, ay), (channel_x, ay), (channel_x, cy), p1])


def dash_line(draw, p0, p1, dash=6, gap=4):
    x0, y0 = p0
    x1, y1 = p1
    length = math.hypot(x1 - x0, y1 - y0) or 1
    dx, dy = (x1 - x0) / length, (y1 - y0) / length
    pos = 0.0
    on = True
    while pos < length:
        seg = dash if on else gap
        end = min(pos + seg, length)
        if on:
            draw.line(
                (x0 + dx * pos, y0 + dy * pos, x0 + dx * end, y0 + dy * end),
                fill=DASH,
                width=1,
            )
        pos = end
        on = not on


def arrow_head(draw, tip, tail):
    ang = math.atan2(tip[1] - tail[1], tip[0] - tail[0])
    size = 8
    for delta in (-0.4, 0.4):
        a = ang + math.pi + delta
        draw.line(
            (tip[0], tip[1], tip[0] + size * math.cos(a), tip[1] + size * math.sin(a)),
            fill=DASH,
            width=1,
        )


def draw_dependency(draw, box_from, box_to, stereotype: str, label_nudge=(0, -7)):
    c_from = ((box_from[0] + box_from[2]) / 2, (box_from[1] + box_from[3]) / 2)
    c_to = ((box_to[0] + box_to[2]) / 2, (box_to[1] + box_to[3]) / 2)
    p0 = oval_edge(box_from, c_to)
    p1 = oval_edge(box_to, c_from)
    dash_line(draw, p0, p1)
    arrow_head(draw, p1, p0)
    mx = (p0[0] + p1[0]) / 2 + label_nudge[0]
    my = (p0[1] + p1[1]) / 2 + label_nudge[1]
    label = f"<<{stereotype}>>"
    tw, th = text_size(draw, label, font_stereo)
    draw.rectangle((mx - tw / 2 - 2, my - th / 2 - 1, mx + tw / 2 + 2, my + th / 2 + 1), fill=BG)
    draw.text((mx - tw / 2, my - th / 2), label, font=font_stereo, fill=INK)


# FDD modules → subfunctions (exact FDD wording)
MODULES = [
    {
        "key": "inv",
        "title": "Integrated Inventory\nManagement",
        "subs": [
            ("inv_recv", "Receive materials"),
            ("inv_prod", "Track production"),
            ("inv_fin", "Confirm finished stock"),
            ("inv_mov", "Review movements"),
        ],
    },
    {
        "key": "cost",
        "title": "Product Costing\nand Pricing",
        "subs": [
            ("cost_form", "Maintain formulas"),
            ("cost_calc", "Calculate costs"),
            ("cost_rec", "Define parcel recipes"),
            ("cost_price", "Maintain prices"),
        ],
    },
    {
        "key": "ord",
        "title": "Multi-Platform Orders\nand Waybills",
        "subs": [
            ("ord_prep", "Prepare order files"),
            ("ord_val", "Validate order data"),
            ("ord_stamp", "Stamp waybills"),
            ("ord_batch", "Finalize batches"),
        ],
    },
    {
        "key": "par",
        "title": "Parcel Processing and\nInventory Sync",
        "subs": [
            ("par_scan", "Record packing scans"),
            ("par_mov", "Confirm movements"),
            ("par_photo", "Save shipment photos"),
            ("par_hist", "Review parcel history"),
        ],
    },
    {
        "key": "rts",
        "title": "Return-to-Sender\nManagement",
        "subs": [
            ("rts_case", "Record return case"),
            ("rts_cls", "Classify condition"),
            ("rts_rev", "Review stock-in"),
            ("rts_sell", "Confirm sellable stock"),
        ],
    },
    {
        "key": "pos",
        "title": "Point of Sale\n(Web + Mobile POS)",
        "subs": [
            ("pos_auth", "Authenticate POS staff"),
            ("pos_sell", "Barcode sell / AR checkout"),
            ("pos_print", "Print ESC/POS receipt"),
            ("pos_hist", "History, reprint, refund"),
        ],
    },
]


def generate(path: Path) -> None:
    W, H = 2500, 1680
    img = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(img)

    bx1, by1, bx2, by2 = 240, 36, 2260, 1560
    draw.rectangle((bx1, by1, bx2, by2), outline=BORDER, width=3)
    btitle = "Pyx Tracker: Web-based Enterprise Operations Management System"
    btw, _ = text_size(draw, btitle, font_boundary)
    draw.text((bx1 + (bx2 - bx1 - btw) / 2, by1 + 10), btitle, font=font_boundary, fill=INK)
    draw.text(
        (bx1 + 16, by1 + 32),
        "Aligned to Figure 3.4 FDD modules and approved proposal features",
        font=font_note,
        fill=MUTED,
    )

    exec_pt = draw_actor(draw, 95, 380, "Executive")
    hr_pt = draw_actor(draw, 95, 900, "HR\nPersonnel")
    staff_pt = draw_actor(draw, 2405, 720, "Staff")
    for i, line in enumerate("(CSR / Production /\nPOS / Delivery /\nMobile POS)".split("\n")):
        tw, _ = text_size(draw, line, font_note)
        draw.text((2405 - tw / 2, 820 + i * 13), line, font=font_note, fill=MUTED)

    left_ch = bx1 + 32
    right_ch = bx2 - 32

    boxes: dict[str, tuple] = {}

    def uc(key, x, y, label, bold=False, mc=17):
        boxes[key] = draw_oval(draw, x, y, label, bold=bold, max_chars=mc)

    # Cross-cutting access (proposal users; supports all FDD modules)
    uc("users", 420, 120, "Manage Users\nand Access", bold=True)
    uc("roles", 680, 120, "Assign Roles /\nPermissions")
    uc("dash", 940, 120, "Monitor Dashboards\nand Reports", bold=True)

    # Six FDD modules in two rows of three
    # Row 1 y≈280–620; Row 2 y≈780–1180
    col_xs = [520, 1120, 1720]  # primary module centers
    row_ys = [340, 900]
    sub_dx = 280  # subfunctions to the right of each module

    includes: list[tuple[str, str]] = [("users", "roles")]
    staff_keys: list[str] = []
    exec_mod_keys: list[str] = []

    for mi, mod in enumerate(MODULES):
        row, col = divmod(mi, 3)
        mx = col_xs[col]
        my = row_ys[row]
        # light band behind module group
        band = (mx - 200, my - 90, mx + sub_dx + 160, my + 280)
        draw.rounded_rectangle(band, radius=12, outline="#D0D7E2", width=1)

        uc(mod["key"], mx, my, mod["title"], bold=True, mc=20)
        exec_mod_keys.append(mod["key"])

        for si, (sk, slabel) in enumerate(mod["subs"]):
            sx = mx + sub_dx
            sy = my - 90 + si * 70
            uc(sk, sx, sy, slabel, bold=False, mc=22)
            includes.append((mod["key"], sk))
            staff_keys.append(sk)

    # POS authenticate is also extend target for sell (optional detail)
    extends = [
        ("pos_hist", "pos_sell"),  # history/reprint/refund extends checkout
        ("inv_mov", "dash"),  # movement review feeds dashboards
        ("par_hist", "dash"),
        ("rts_rev", "dash"),
    ]

    # Associations
    for key in ("users", "dash", *exec_mod_keys):
        draw_assoc_ortho(draw, exec_pt, boxes[key], left_ch)
    draw_assoc_ortho(draw, hr_pt, boxes["users"], left_ch)

    for key in staff_keys:
        draw_assoc_ortho(draw, staff_pt, boxes[key], right_ch)

    for a, b in includes:
        draw_dependency(draw, boxes[a], boxes[b], "include")
    for a, b in extends:
        draw_dependency(draw, boxes[a], boxes[b], "extend", label_nudge=(0, 9))

    # Legend
    lx, ly = 40, 1600
    draw.text((lx, ly), "Legend:", font=font_note, fill=INK)
    draw.line((lx + 60, ly + 8, lx + 100, ly + 8), fill=INK, width=1)
    draw.text((lx + 108, ly), "Association", font=font_note, fill=INK)
    dash_line(draw, (lx + 220, ly + 8), (lx + 260, ly + 8))
    arrow_head(draw, (lx + 260, ly + 8), (lx + 220, ly + 8))
    draw.text((lx + 268, ly), "<<include>> / <<extend>>", font=font_note, fill=INK)
    draw.text(
        (lx + 480, ly),
        "Use cases = FDD modules + subfunctions  |  Actors: Executive, HR, Staff",
        font=font_note,
        fill=MUTED,
    )

    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG", optimize=True)
    print(f"saved {path} ({path.stat().st_size} bytes)")


def patch_docx(png: Path) -> None:
    png_bytes = png.read_bytes()
    src = SRC_DOC if SRC_DOC.exists() else DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_usecase.docx"
    with zipfile.ZipFile(src, "r") as zin:
        items = list(zin.infolist())
        blob = {i.filename: zin.read(i.filename) for i in items}
    blob["word/media/image7.png"] = png_bytes

    try:
        from lxml import etree

        NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
        root = etree.fromstring(blob["word/document.xml"])
        new_desc = (
            "Figure 3.5 presents the overall Use Case Diagram of Pyx Tracker aligned with the "
            "approved proposal features and Figure 3.4 Functional Decomposition Diagram. The six "
            "primary use cases match the FDD modules: Integrated Inventory Management, Product "
            "Costing and Pricing, Multi-Platform Orders and Waybills, Parcel Processing and "
            "Inventory Sync, Return-to-Sender Management, and Point of Sale (Web + Mobile POS). "
            "Each module <<include>>s its FDD subfunctions. Actors are Executive, HR Personnel, "
            "and Staff; Manage Users and Access and Monitor Dashboards and Reports support "
            "role-based operation across modules."
        )
        for p in root.xpath("//w:p", namespaces=NS):
            t = "".join(p.xpath(".//w:t/text()", namespaces=NS))
            if "Figure 3.5" in t and (
                "presents" in t.lower()
                or "separates" in t.lower()
                or "aligned" in t.lower()
                or "overall Use Case" in t
            ):
                texts = p.xpath(".//w:t", namespaces=NS)
                if texts:
                    texts[0].text = new_desc
                    for extra in texts[1:]:
                        extra.text = ""
                break
        blob["word/document.xml"] = etree.tostring(
            root, xml_declaration=True, encoding="UTF-8", standalone=True
        )
    except Exception as e:
        print("caption skip:", e)

    for path in OUT_DOCS:
        tmp = path.with_suffix(".tmp.docx")
        try:
            with zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED) as zout:
                for item in items:
                    zout.writestr(item, blob[item.filename])
            tmp.replace(path)
            print(f"OK {path.name}")
        except PermissionError as e:
            if tmp.exists():
                tmp.unlink(missing_ok=True)
            print(f"LOCKED {path.name}: {e}")


if __name__ == "__main__":
    generate(OUT_PNG)
    generate(WORK_PNG)
    patch_docx(OUT_PNG)
