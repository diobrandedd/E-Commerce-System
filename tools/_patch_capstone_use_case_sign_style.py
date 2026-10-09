"""Pyx Tracker Use Case — S.I.G.N. sample layout rules:

- Short solid associations; no shared vertical bus through ovals
- Parallel horizontal <<include>> fans (lines do not cross)
- Left = Executive/HR oversight; Right = Staff operations
- Content = FDD modules + subfunctions (approved features)
- Ovals never sit on unrelated lines
"""

from __future__ import annotations

import math
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

DESKTOP = Path(r"C:\Users\Admin\Desktop\capstone")
SRC_CANDIDATES = [
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_fdd_usecase.docx",
    DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    Path(r"C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_SQL_ERD.docx"),
]
OUT_PNG = DESKTOP / "Pyx_Tracker_Use_Case_Diagram.png"
WORK_PNG = Path(r"C:\xampp\htdocs\rts\output\documents\_use_case_pyx_tracker.png")
OUT_DOCS = [
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_usecase.docx",
    DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_fdd_usecase.docx",
    DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
    Path(r"C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_SQL_ERD.docx"),
]

BG = "#FFFFFF"
INK = "#1A1A1A"
MUTED = "#555555"
BORDER = "#222222"
DASH = "#444444"

font_boundary = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 16)
font_actor = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 13)
font_uc = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)
font_uc_b = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)
font_stereo = ImageFont.truetype(r"C:/Windows/Fonts/ariali.ttf", 9)
font_note = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)


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
    sizes = []
    for line in lines:
        tw, th = text_size(draw, line, font)
        sizes.append((tw, th))
        max_w = max(max_w, tw)
        total_h += th + 1
    rw = max(56, max_w / 2 + 14)
    rh = max(17, total_h / 2 + 8)
    box = (cx - rw, cy - rh, cx + rw, cy + rh)
    draw.ellipse(box, fill=BG, outline=BORDER, width=2)
    y = cy - total_h / 2
    for (tw, th), line in zip(sizes, lines):
        draw.text((cx - tw / 2, y), line, font=font, fill=INK)
        y += th + 1
    return box


def oval_edge(box, toward):
    x1, y1, x2, y2 = box
    cx, cy = (x1 + x2) / 2, (y1 + y2) / 2
    rx, ry = (x2 - x1) / 2, (y2 - y1) / 2
    dx, dy = toward[0] - cx, toward[1] - cy
    if abs(dx) < 1e-6 and abs(dy) < 1e-6:
        return (cx + rx, cy)
    scale = 1.0 / math.sqrt((dx * dx) / (rx * rx) + (dy * dy) / (ry * ry))
    return (cx + dx * scale, cy + dy * scale)


def draw_assoc(draw, p0, box):
    """Straight association to oval rim — short fans only."""
    p1 = oval_edge(box, p0)
    draw.line((p0[0], p0[1], p1[0], p1[1]), fill=INK, width=1)


def draw_assoc_margin(draw, p0, box, channel_x: float):
    """Orthogonal association in an empty side margin (no ovals in the channel)."""
    cy = (box[1] + box[3]) / 2
    p1 = oval_edge(box, (channel_x, cy))
    ax, ay = p0
    pts = [(ax, ay), (channel_x, ay), (channel_x, cy), p1]
    for i in range(len(pts) - 1):
        draw.line((pts[i][0], pts[i][1], pts[i + 1][0], pts[i + 1][1]), fill=INK, width=1)


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


def draw_include(draw, box_from, box_to):
    """<<include>>: dashed arrow from base to included; prefer near-horizontal."""
    c_from = ((box_from[0] + box_from[2]) / 2, (box_from[1] + box_from[3]) / 2)
    c_to = ((box_to[0] + box_to[2]) / 2, (box_to[1] + box_to[3]) / 2)
    p0 = oval_edge(box_from, c_to)
    p1 = oval_edge(box_to, c_from)
    dash_line(draw, p0, p1)
    arrow_head(draw, p1, p0)
    mx, my = (p0[0] + p1[0]) / 2, (p0[1] + p1[1]) / 2 - 7
    lab = "<<include>>"
    tw, th = text_size(draw, lab, font_stereo)
    draw.rectangle((mx - tw / 2 - 2, my - th / 2 - 1, mx + tw / 2 + 2, my + th / 2 + 1), fill=BG)
    draw.text((mx - tw / 2, my - th / 2), lab, font=font_stereo, fill=INK)


def draw_extend(draw, box_from, box_to):
    """<<extend>>: dashed arrow from extending to base (often horizontal bridge)."""
    c_from = ((box_from[0] + box_from[2]) / 2, (box_from[1] + box_from[3]) / 2)
    c_to = ((box_to[0] + box_to[2]) / 2, (box_to[1] + box_to[3]) / 2)
    p0 = oval_edge(box_from, c_to)
    p1 = oval_edge(box_to, c_from)
    dash_line(draw, p0, p1)
    arrow_head(draw, p1, p0)
    mx, my = (p0[0] + p1[0]) / 2, (p0[1] + p1[1]) / 2 + 8
    lab = "<<extend>>"
    tw, th = text_size(draw, lab, font_stereo)
    draw.rectangle((mx - tw / 2 - 2, my - th / 2 - 1, mx + tw / 2 + 2, my + th / 2 + 1), fill=BG)
    draw.text((mx - tw / 2, my - th / 2), lab, font=font_stereo, fill=INK)


def generate(path: Path) -> None:
    W, H = 2300, 1980
    img = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(img)

    bx1, by1, bx2, by2 = 210, 40, 2090, 1880
    draw.rectangle((bx1, by1, bx2, by2), outline=BORDER, width=3)
    title = "Pyx Tracker: Web-based Enterprise Operations Management System"
    tw, _ = text_size(draw, title, font_boundary)
    draw.text((bx1 + (bx2 - bx1 - tw) / 2, by1 + 12), title, font=font_boundary, fill=INK)

    # Actors outside — S.I.G.N. style left/right
    exec_pt = draw_actor(draw, 90, 420, "Executive")
    hr_pt = draw_actor(draw, 90, 1180, "HR\nPersonnel")
    staff_pt = draw_actor(draw, 2210, 900, "Staff")
    for i, line in enumerate("(CSR / Production /\nPOS / Delivery /\nMobile POS)".split("\n")):
        tw, _ = text_size(draw, line, font_note)
        draw.text((2210 - tw / 2, 1000 + i * 13), line, font=font_note, fill=MUTED)

    boxes: dict[str, tuple] = {}

    def uc(key, x, y, label, bold=False, mc=18):
        boxes[key] = draw_oval(draw, x, y, label, bold=bold, max_chars=mc)

    # ============================================================
    # S.I.G.N. geometry:
    #   Left:  Exec — primary — includes (→ center)
    #   Right: includes (← center) — primary — Staff
    #   Extends are short horizontal bridges in clear gaps
    # ============================================================

    # --- Band 1: Access (left only) ---
    uc("users", 400, 150, "Manage Users\nand Access", bold=True)
    uc("roles", 720, 150, "Assign Roles /\nPermissions")

    # --- Band 2: Inventory (extend corridor y=280 above includes) ---
    uc("inv_over", 400, 280, "Oversee Inventory\nOperations", bold=True)
    uc("inv_recv", 1180, 360, "Receive materials")
    uc("inv_prod", 1180, 420, "Track production")
    uc("inv_fin", 1180, 480, "Confirm finished stock")
    uc("inv_mov", 1180, 540, "Review movements")
    uc("inv", 1580, 280, "Integrated Inventory\nManagement", bold=True, mc=20)

    # --- Band 3: Costing (left) ---
    uc("cost", 400, 620, "Product Costing\nand Pricing", bold=True)
    uc("cost_form", 760, 560, "Maintain formulas")
    uc("cost_calc", 760, 620, "Calculate costs")
    uc("cost_rec", 760, 680, "Define parcel recipes")
    uc("cost_price", 1020, 620, "Maintain prices")

    # --- Band 4: Orders (right) ---
    uc("ord_prep", 1180, 640, "Prepare order files")
    uc("ord_val", 1180, 700, "Validate order data")
    uc("ord_stamp", 1180, 760, "Stamp waybills")
    uc("ord_batch", 1180, 820, "Finalize batches")
    uc("ord", 1580, 730, "Multi-Platform Orders\nand Waybills", bold=True, mc=20)

    # --- Band 5: Parcel (right) ---
    uc("par_scan", 1180, 900, "Record packing scans")
    uc("par_mov", 1180, 960, "Confirm movements")
    uc("par_photo", 1180, 1020, "Save shipment photos")
    uc("par_hist", 1180, 1080, "Review parcel history")
    uc("par", 1580, 990, "Parcel Processing and\nInventory Sync", bold=True, mc=20)

    # --- Band 6: Dashboards (left) ---
    uc("dash", 400, 900, "Monitor Dashboards\nand Reports", bold=True)

    # --- Band 7: RTS — extend at y=1220; includes below ---
    uc("rts_rev", 400, 1220, "Review RTS Claims\nand Dashboard", bold=True)
    uc("rts", 1580, 1220, "Return-to-Sender\nManagement", bold=True)
    uc("rts_case", 1180, 1320, "Record return case")
    uc("rts_cls", 1180, 1380, "Classify condition")
    uc("rts_stock", 1180, 1440, "Review stock-in")
    uc("rts_sell", 1180, 1500, "Confirm sellable stock")

    # --- Band 8: POS (right) ---
    uc("pos_auth", 1180, 1600, "Authenticate POS staff")
    uc("pos_sell", 1180, 1660, "Barcode sell /\nAR checkout")
    uc("pos_print", 1180, 1720, "Print ESC/POS receipt")
    uc("pos_hist", 1180, 1780, "History, reprint,\nrefund")
    uc("pos", 1580, 1660, "Point of Sale\n(Web + Mobile POS)", bold=True)

    # Includes — short, parallel, same band
    draw_include(draw, boxes["users"], boxes["roles"])
    for k in ("cost_form", "cost_calc", "cost_rec", "cost_price"):
        draw_include(draw, boxes["cost"], boxes[k])
    for k in ("inv_recv", "inv_prod", "inv_fin", "inv_mov"):
        draw_include(draw, boxes["inv"], boxes[k])
    for k in ("ord_prep", "ord_val", "ord_stamp", "ord_batch"):
        draw_include(draw, boxes["ord"], boxes[k])
    for k in ("par_scan", "par_mov", "par_photo", "par_hist"):
        draw_include(draw, boxes["par"], boxes[k])
    for k in ("rts_case", "rts_cls", "rts_stock", "rts_sell"):
        draw_include(draw, boxes["rts"], boxes[k])
    for k in ("pos_auth", "pos_sell", "pos_print", "pos_hist"):
        draw_include(draw, boxes["pos"], boxes[k])

    # Short horizontal extends in clear gaps (S.I.G.N. bridges)
    draw_extend(draw, boxes["inv_over"], boxes["inv"])
    draw_extend(draw, boxes["rts_rev"], boxes["rts"])
    draw_extend(draw, boxes["pos_hist"], boxes["pos_sell"])

    # Associations in empty side margins only (S.I.G.N. clean fans)
    left_margin = 270   # between actors and left primaries
    right_margin = 1850  # between right primaries and Staff
    for key in ("users", "inv_over", "cost", "dash", "rts_rev"):
        draw_assoc_margin(draw, exec_pt, boxes[key], left_margin)
    draw_assoc_margin(draw, hr_pt, boxes["users"], left_margin)
    for key in ("inv", "ord", "par", "rts", "pos"):
        draw_assoc_margin(draw, staff_pt, boxes[key], right_margin)

    # Legend
    lx, ly = 40, 1910
    draw.text((lx, ly), "Legend:", font=font_note, fill=INK)
    draw.line((lx + 70, ly + 8, lx + 110, ly + 8), fill=INK, width=1)
    draw.text((lx + 118, ly), "Association", font=font_note, fill=INK)
    dash_line(draw, (lx + 240, ly + 8), (lx + 280, ly + 8))
    arrow_head(draw, (lx + 280, ly + 8), (lx + 240, ly + 8))
    draw.text((lx + 288, ly), "<<include>> / <<extend>>", font=font_note, fill=INK)
    draw.text(
        (lx + 520, ly),
        "Layout: S.I.G.N. style (no line-through-oval)  |  Content: FDD modules + features",
        font=font_note,
        fill=MUTED,
    )

    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG", optimize=True)
    print(f"saved {path} ({path.stat().st_size} bytes)")


def patch_docx(png: Path) -> None:
    png_bytes = png.read_bytes()
    src = next((p for p in SRC_CANDIDATES if p.exists()), None)
    if src is None:
        raise SystemExit("No source DOCX found")
    with zipfile.ZipFile(src, "r") as zin:
        items = list(zin.infolist())
        blob = {i.filename: zin.read(i.filename) for i in items}
    blob["word/media/image7.png"] = png_bytes

    try:
        from lxml import etree

        NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
        root = etree.fromstring(blob["word/document.xml"])
        new_desc = (
            "Figure 3.5 presents the overall Use Case Diagram of Pyx Tracker using the same "
            "clean UML layout as the institutional sample: actors outside the boundary, short "
            "association lines that do not cross through ovals, and <<include>> / <<extend>> "
            "dependencies. Use cases follow the approved features and Figure 3.4 FDD modules—"
            "Integrated Inventory Management, Product Costing and Pricing, Multi-Platform Orders "
            "and Waybills, Parcel Processing and Inventory Sync, Return-to-Sender Management, and "
            "Point of Sale (Web + Mobile POS)—with Executive and HR on oversight and Staff on "
            "operations."
        )
        for p in root.xpath("//w:p", namespaces=NS):
            t = "".join(p.xpath(".//w:t/text()", namespaces=NS))
            if "Figure 3.5" in t and any(
                k in t.lower() for k in ("presents", "aligned", "separates", "overall")
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
