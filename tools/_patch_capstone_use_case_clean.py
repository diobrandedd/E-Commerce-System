"""Clean Pyx Tracker Use Case Diagram with Staff vs Customer access separation."""

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

font_boundary = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 15)
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
    line_sizes = []
    for line in lines:
        tw, th = text_size(draw, line, font)
        line_sizes.append((tw, th))
        max_w = max(max_w, tw)
        total_h += th + 1
    pad_x, pad_y = 16, 9
    rw = max(62, max_w / 2 + pad_x)
    rh = max(20, total_h / 2 + pad_y)
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


def draw_dependency(draw, box_from, box_to, stereotype: str, label_nudge=(0, -8)):
    c_from = ((box_from[0] + box_from[2]) / 2, (box_from[1] + box_from[3]) / 2)
    c_to = ((box_to[0] + box_to[2]) / 2, (box_to[1] + box_to[3]) / 2)
    dx = abs(c_to[0] - c_from[0])
    dy = abs(c_to[1] - c_from[1])
    if dx > 120 and dy > 80:
        mid_x = (c_from[0] + c_to[0]) / 2
        p0 = oval_edge(box_from, (mid_x, c_from[1]))
        p1 = oval_edge(box_to, (mid_x, c_to[1]))
        for a, b in ((p0, (mid_x, c_from[1])), ((mid_x, c_from[1]), (mid_x, c_to[1])), ((mid_x, c_to[1]), p1)):
            dash_line(draw, a, b)
        arrow_head(draw, p1, (mid_x, c_to[1]))
        mx = mid_x + label_nudge[0]
        my = (c_from[1] + c_to[1]) / 2 + label_nudge[1]
    else:
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


def generate(path: Path) -> None:
    W, H = 2300, 1740
    img = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(img)

    bx1, by1, bx2, by2 = 250, 40, 2050, 1610
    draw.rectangle((bx1, by1, bx2, by2), outline=BORDER, width=3)
    btitle = "Pyx Tracker: Web-based Enterprise Operations Management System"
    btw, _ = text_size(draw, btitle, font_boundary)
    draw.text((bx1 + (bx2 - bx1 - btw) / 2, by1 + 12), btitle, font=font_boundary, fill=INK)

    # --- Actors: Executive, HR, Staff (system users only) ---
    exec_pt = draw_actor(draw, 100, 340, "Executive")
    hr_pt = draw_actor(draw, 100, 920, "HR\nPersonnel")

    staff_pt = draw_actor(draw, 2200, 620, "Staff")
    for i, line in enumerate(
        "(CSR / Production /\nPOS / Delivery /\nMobile POS)\n\nAccesses system:\ninventory · orders\nparcel · POS · RTS".split("\n")
    ):
        tw, _ = text_size(draw, line, font_note)
        draw.text((2200 - tw / 2, 720 + i * 13), line, font=font_note, fill=MUTED)

    left_channel = bx1 + 36
    staff_channel = bx2 - 36

    boxes: dict[str, tuple] = {}

    def uc(key, x, y, label, bold=False, mc=18):
        boxes[key] = draw_oval(draw, x, y, label, bold=bold, max_chars=mc)

    # LEFT — Executive / HR
    uc("users", 460, 140, "Manage Users\nand Access", bold=True)
    uc("roles", 780, 140, "Assign Roles /\nMobile POS Staff")
    uc("dash", 460, 250, "Monitor Dashboards\nand Reports", bold=True)
    uc("approve", 780, 250, "Approve Inventory\nCorrections")
    uc("costing", 460, 370, "Product Costing\nand Pricing", bold=True)
    uc("formulas", 780, 370, "Maintain Formulas\nand Prices")
    uc("inventory", 460, 490, "Integrated Inventory\nManagement", bold=True, mc=20)
    uc("orders", 460, 630, "Multi-Platform Order\nProcessing", bold=True, mc=20)
    uc("parcel", 460, 770, "Parcel Processing &\nInventory Sync", bold=True, mc=20)
    uc("pos", 460, 950, "Point of Sale\nSystem", bold=True)
    uc("rts", 460, 1260, "Return-to-Sender\nManagement", bold=True)
    uc("review_rts", 780, 1260, "Review RTS Claims\nand Dashboard")

    # MID — staff internals only
    uc("inv_mid", 1100, 490, "Stock & Production\nWorkflows")
    uc("ord_mid", 1100, 630, "Order Prep &\nWaybill Tools")
    uc("par_mid", 1100, 770, "Scan / Photo /\nShip Update")
    uc("auth", 1100, 950, "Authenticate POS /\nStaff / Bearer Token")
    uc("cart", 1400, 890, "Scan Barcode\nand Build Cart")
    uc("checkout", 1400, 1010, "Cash or AR\nCredit Checkout")
    uc("receipt", 1400, 1130, "Print ESC/POS\nThermal Receipt")
    uc("printer", 1100, 1130, "Configure Printer\n(BT / USB / Wi-Fi)")

    # RIGHT — Staff system access only
    draw.text((1530, 1186), "Staff system access (logged-in users)", font=font_note, fill=MUTED)
    draw.line((1520, 1205, bx2 - 16, 1205), fill="#999999", width=1)

    uc("stockin", 1780, 410, "Receive Materials\nand Stock-In")
    uc("honey", 1780, 510, "Track Honey /\nDept. Production")
    uc("prepare", 1780, 590, "Prepare VIP /\nShopee Orders")
    uc("waybill", 1780, 680, "Stamp Waybills /\nFinalize Batches")
    uc("scan", 1780, 770, "Scan Tracking /\nCapture Photos")
    uc("ship", 1780, 860, "Update Parcel Status\nto Shipped")
    uc("web_pos", 1780, 950, "Use Web POS\nCheckout")
    uc("mob_pos", 1780, 1040, "Use Mobile POS\n(Android)", bold=True)
    uc("history", 1780, 1130, "Sales History /\nReprint / Refund")
    uc("rts_case", 1500, 1320, "Record RTS Case\nand Sync Pools")
    uc("rts_stock", 1780, 1320, "Confirm Sellable\nStock Restoration")

    # Associations by access
    for key in ("users", "dash", "approve", "review_rts", "costing", "inventory", "orders", "parcel", "pos", "rts"):
        draw_assoc_ortho(draw, exec_pt, boxes[key], left_channel)
    draw_assoc_ortho(draw, hr_pt, boxes["users"], left_channel)

    for key in (
        "stockin",
        "honey",
        "prepare",
        "waybill",
        "scan",
        "ship",
        "web_pos",
        "mob_pos",
        "history",
        "rts_case",
        "rts_stock",
    ):
        draw_assoc_ortho(draw, staff_pt, boxes[key], staff_channel)

    includes = [
        ("users", "roles"),
        ("costing", "formulas"),
        ("inventory", "inv_mid"),
        ("inv_mid", "stockin"),
        ("inv_mid", "honey"),
        ("orders", "ord_mid"),
        ("ord_mid", "prepare"),
        ("ord_mid", "waybill"),
        ("parcel", "par_mid"),
        ("par_mid", "scan"),
        ("par_mid", "ship"),
        ("pos", "web_pos"),
        ("pos", "mob_pos"),
        ("web_pos", "auth"),
        ("mob_pos", "auth"),
        ("auth", "cart"),
        ("auth", "checkout"),
        ("cart", "receipt"),
        ("rts", "rts_case"),
        ("rts", "rts_stock"),
    ]
    for a, b in includes:
        draw_dependency(draw, boxes[a], boxes[b], "include")

    extends = [
        ("checkout", "history"),
        ("printer", "receipt"),
        ("review_rts", "rts"),
        ("approve", "stockin"),
    ]
    for a, b in extends:
        draw_dependency(draw, boxes[a], boxes[b], "extend", label_nudge=(0, 10))

    lx, ly = 40, 1650
    draw.text((lx, ly), "Legend:", font=font_note, fill=INK)
    draw.line((lx + 60, ly + 8, lx + 100, ly + 8), fill=INK, width=1)
    draw.text((lx + 108, ly), "Association", font=font_note, fill=INK)
    dash_line(draw, (lx + 220, ly + 8), (lx + 260, ly + 8))
    arrow_head(draw, (lx + 260, ly + 8), (lx + 220, ly + 8))
    draw.text((lx + 268, ly), "<<include>> / <<extend>>", font=font_note, fill=INK)
    draw.text(
        (lx + 480, ly),
        "Actors: Executive, HR Personnel, Staff (system users)",
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
    media = "word/media/image7.png"
    blob[media] = png_bytes

    # Refresh figure body text if present
    try:
        from lxml import etree

        NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
        W = NS["w"]
        root = etree.fromstring(blob["word/document.xml"])
        new_desc = (
            "Figure 3.5 presents the overall Use Case Diagram of Pyx Tracker. "
            "Actors are Executive, HR Personnel, and Staff (CSR, production, POS, delivery, "
            "and Mobile POS). Executive and HR handle users, dashboards, costing, and oversight. "
            "Staff perform logged-in inventory, order, parcel, POS, and RTS operations. "
            "Customer is omitted as an actor because customers do not log in to Pyx Tracker; "
            "they are external beneficiaries of fulfillment and retail sales."
        )
        for p in root.xpath("//w:p", namespaces=NS):
            t = "".join(p.xpath(".//w:t/text()", namespaces=NS))
            if (
                ("Figure 3.5" in t and ("presents" in t.lower() or "separates" in t.lower()))
                or t.strip().startswith("Figure 3.5 presents")
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
