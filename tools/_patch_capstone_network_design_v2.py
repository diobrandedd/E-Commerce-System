"""Replace Network Design (Figure 3.16) with Visual Paradigm-style layout + gaps filled.

Adds what the supplied VP sketch lacked for Pyx Tracker:
- Internet / ISP cloud
- Firewall / NAT on the uplink
- ESC/POS thermal printer linked to Mobile POS (Bluetooth/Wi-Fi)
- Wired vs wireless legend
- No Visual Paradigm watermark

Replaces only image15.png. Does NOT touch Network Topology (image16.png).
"""

from __future__ import annotations

import math
import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
from docx import Document

SRC = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD_updated.docx")
FALLBACKS = [
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD_netdesign.docx"),
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD_with_POS_diagrams.docx"),
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx"),
]
WORK = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD_net_work.docx")
DESKTOP = Path(r"C:/Users/Admin/Desktop/capstone")
OUT_IMG = DESKTOP / "Pyx_Tracker_Network_Design_v3.png"

BG = "#FFFFFF"
INK = "#1A2A3A"
LINE = "#2F6FAE"
FILL = "#D9ECFA"
FILL2 = "#BFDFF5"
MUTED = "#5A6A7A"
WIRE = "#2F4F6F"
WIFI = "#2F6FAE"
CLOUD_F = "#C5DDF0"

font_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 24)
font_lab = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 12)
font_sm = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)
font_tiny = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 10)


def tw(d, t, f):
    b = d.textbbox((0, 0), t, font=f)
    return b[2] - b[0], b[3] - b[1]


def label(d, cx, y, text, font=font_lab):
    lines = text.split("\n")
    for i, ln in enumerate(lines):
        w, h = tw(d, ln, font)
        d.text((cx - w / 2, y + i * (h + 1)), ln, font=font, fill=INK)


def solid(d, a, b, width=2):
    d.line((*a, *b), fill=WIRE, width=width)


def dashed(d, a, b, width=2, dash=8, gap=5):
    x0, y0 = a
    x1, y1 = b
    length = math.hypot(x1 - x0, y1 - y0) or 1
    dx, dy = (x1 - x0) / length, (y1 - y0) / length
    pos = 0.0
    on = True
    while pos < length:
        seg = dash if on else gap
        end = min(pos + seg, length)
        if on:
            d.line(
                (x0 + dx * pos, y0 + dy * pos, x0 + dx * end, y0 + dy * end),
                fill=WIFI,
                width=width,
            )
        pos = end
        on = not on


# ---- icons (Cisco / VP-like blue) ----

def pc(d, cx, cy, s=1.0):
    # monitor
    d.rounded_rectangle((cx - 22 * s, cy - 28 * s, cx + 10 * s, cy + 2 * s), 3, fill=FILL, outline=LINE, width=2)
    d.rectangle((cx - 18 * s, cy - 24 * s, cx + 6 * s, cy - 2 * s), fill="#F7FBFF", outline=LINE, width=1)
    # tower
    d.rounded_rectangle((cx + 12 * s, cy - 26 * s, cx + 28 * s, cy + 18 * s), 2, fill=FILL, outline=LINE, width=2)
    for k in range(3):
        yy = cy - 16 * s + k * 8 * s
        d.line((cx + 15 * s, yy, cx + 25 * s, yy), fill=LINE, width=1)
    # keyboard
    d.rectangle((cx - 24 * s, cy + 10 * s, cx + 12 * s, cy + 18 * s), fill=FILL2, outline=LINE, width=2)


def server(d, cx, cy, s=1.0):
    d.rounded_rectangle((cx - 28 * s, cy - 36 * s, cx + 28 * s, cy + 36 * s), 4, fill=FILL, outline=LINE, width=2)
    for k in range(5):
        y1 = cy - 28 * s + k * 12 * s
        d.rectangle((cx - 22 * s, y1, cx + 22 * s, y1 + 8 * s), fill=FILL2, outline=LINE, width=1)
        d.ellipse((cx + 14 * s, y1 + 2 * s, cx + 18 * s, y1 + 6 * s), fill="#FFFFFF", outline=LINE, width=1)


def router(d, cx, cy, s=1.0):
    d.rounded_rectangle((cx - 36 * s, cy - 18 * s, cx + 36 * s, cy + 18 * s), 4, fill=FILL, outline=LINE, width=2)
    # ports
    for i in range(6):
        x = cx - 28 * s + i * 7 * s
        d.rectangle((x, cy - 6 * s, x + 5 * s, cy + 8 * s), fill="#FFFFFF", outline=LINE, width=1)
    # 4-way arrow badge
    bx, by = cx + 22 * s, cy
    r = 8 * s
    d.ellipse((bx - r, by - r, bx + r, by + r), fill="#FFFFFF", outline=LINE, width=1)
    for ang in (0, 90, 180, 270):
        a = math.radians(ang)
        d.line((bx, by, bx + r * 0.7 * math.cos(a), by + r * 0.7 * math.sin(a)), fill=LINE, width=2)


def switch(d, cx, cy, s=1.0):
    d.rounded_rectangle((cx - 40 * s, cy - 18 * s, cx + 40 * s, cy + 18 * s), 4, fill=FILL, outline=LINE, width=2)
    for i in range(8):
        x = cx - 34 * s + i * 7 * s
        d.rectangle((x, cy - 6 * s, x + 5 * s, cy + 8 * s), fill="#FFFFFF", outline=LINE, width=1)
    # X / cross badge
    bx, by = cx + 26 * s, cy
    r = 8 * s
    d.ellipse((bx - r, by - r, bx + r, by + r), fill="#FFFFFF", outline=LINE, width=1)
    d.line((bx - 5 * s, by - 5 * s, bx + 5 * s, by + 5 * s), fill=LINE, width=2)
    d.line((bx - 5 * s, by + 5 * s, bx + 5 * s, by - 5 * s), fill=LINE, width=2)


def phone(d, cx, cy, s=1.0):
    d.rounded_rectangle((cx - 14 * s, cy - 28 * s, cx + 14 * s, cy + 28 * s), 4, fill=FILL, outline=LINE, width=2)
    d.rectangle((cx - 10 * s, cy - 22 * s, cx + 10 * s, cy + 16 * s), fill="#F7FBFF", outline=LINE, width=1)
    d.ellipse((cx - 4 * s, cy + 20 * s, cx + 4 * s, cy + 26 * s), outline=LINE, width=2)


def printer(d, cx, cy, s=1.0):
    # thermal printer body
    d.rounded_rectangle((cx - 26 * s, cy - 10 * s, cx + 26 * s, cy + 18 * s), 3, fill=FILL, outline=LINE, width=2)
    d.rectangle((cx - 18 * s, cy - 22 * s, cx + 18 * s, cy - 8 * s), fill=FILL2, outline=LINE, width=2)
    d.line((cx - 14 * s, cy + 2 * s, cx + 14 * s, cy + 2 * s), fill=LINE, width=1)
    d.line((cx - 14 * s, cy + 8 * s, cx + 10 * s, cy + 8 * s), fill=LINE, width=1)


def barcode_scanner(d, cx, cy, s=1.0):
    """Handheld barcode scanner (gun-style) in blue network-icon style."""
    # head / scan window
    d.rounded_rectangle(
        (cx - 22 * s, cy - 22 * s, cx + 18 * s, cy - 2 * s),
        3,
        fill=FILL,
        outline=LINE,
        width=2,
    )
    d.rectangle(
        (cx - 16 * s, cy - 18 * s, cx + 12 * s, cy - 6 * s),
        fill="#F7FBFF",
        outline=LINE,
        width=1,
    )
    # barcode lines in window
    for i, xoff in enumerate((-10, -6, -2, 2, 6)):
        d.line(
            (cx + xoff * s, cy - 16 * s, cx + xoff * s, cy - 8 * s),
            fill=LINE,
            width=2 if i % 2 == 0 else 1,
        )
    # handle
    d.rounded_rectangle(
        (cx - 6 * s, cy - 2 * s, cx + 8 * s, cy + 26 * s),
        3,
        fill=FILL2,
        outline=LINE,
        width=2,
    )
    # trigger
    d.polygon(
        [
            (cx - 6 * s, cy + 4 * s),
            (cx - 16 * s, cy + 10 * s),
            (cx - 6 * s, cy + 14 * s),
        ],
        fill=FILL,
        outline=LINE,
    )


def firewall(d, cx, cy, s=1.0):
    # brick wall
    d.rounded_rectangle((cx - 28 * s, cy - 22 * s, cx + 28 * s, cy + 22 * s), 2, fill=FILL, outline=LINE, width=2)
    rows = 4
    brick_w, brick_h = 14 * s, 10 * s
    for r in range(rows):
        offset = 0 if r % 2 == 0 else brick_w / 2
        y = cy - 18 * s + r * brick_h
        x = cx - 26 * s + offset
        while x < cx + 26 * s - 2:
            d.rectangle((x, y, min(x + brick_w - 1, cx + 26 * s - 2), y + brick_h - 1), outline=LINE, width=1)
            x += brick_w


def cloud(d, cx, cy, s=1.0):
    for ox, oy, r in [(-30, 4, 22), (0, -6, 28), (30, 4, 22), (-12, 14, 18), (12, 14, 18)]:
        d.ellipse(
            (cx + ox * s - r * s, cy + oy * s - r * s, cx + ox * s + r * s, cy + oy * s + r * s),
            fill=CLOUD_F,
            outline=LINE,
            width=2,
        )


def generate(path: Path) -> None:
    W, H = 1400, 900
    img = Image.new("RGB", (W, H), BG)
    d = ImageDraw.Draw(img)

    title = "Network Design for Pyx Tracker"
    w0, _ = tw(d, title, font_title)
    d.text(((W - w0) / 2, 16), title, font=font_title, fill=INK)
    d.text(
        (40, 48),
        "Based on the Visual Paradigm site layout; Internet, Firewall/NAT, and Mobile POS thermal printer added for completeness.",
        font=font_sm,
        fill=MUTED,
    )

    # Coordinates (match supplied VP hierarchy)
    # Top: Admin, Server
    admin = (220, 160)
    srv = (480, 150)
    # Added uplink
    inet = (750, 120)
    fw = (750, 230)
    # Routers / switch
    r1 = (350, 320)
    r2 = (620, 420)
    r3 = (620, 680)
    sw = (220, 680)
    # Endpoints
    pos = (980, 320)
    pos_printer = (1200, 280)
    pos_scanner = (1200, 400)
    mobile = (980, 500)
    csr = (980, 680)

    # Draw icons
    pc(d, *admin, 1.15)
    label(d, admin[0], admin[1] + 40, "Executive / Admin")

    server(d, *srv, 1.05)
    label(d, srv[0], srv[1] + 50, "Application Server\n(PHP / MySQL / API)")

    cloud(d, *inet, 1.0)
    label(d, inet[0], inet[1] + 40, "Internet / ISP")

    firewall(d, *fw, 1.0)
    label(d, fw[0], fw[1] + 36, "Firewall / NAT")

    router(d, *r1, 1.05)
    label(d, r1[0], r1[1] + 32, "Router #1\n(Core / Gateway)")

    router(d, *r2, 1.0)
    label(d, r2[0], r2[1] + 32, "Router #2\n(Wi-Fi Access)")

    switch(d, *sw, 1.05)
    label(d, sw[0], sw[1] + 32, "Switch\n(LAN)")

    router(d, *r3, 1.0)
    label(d, r3[0], r3[1] + 32, "Router #3\n(Office LAN)")

    phone(d, *pos, 1.15)
    label(d, pos[0], pos[1] + 42, "POS Device\n(Android Pyx POS)")

    printer(d, *pos_printer, 1.05)
    label(d, pos_printer[0], pos_printer[1] + 32, "Thermal Printer\n(ESC/POS BT / Wi-Fi)")

    barcode_scanner(d, *pos_scanner, 1.1)
    label(d, pos_scanner[0], pos_scanner[1] + 40, "Barcode Scanner\n(USB / Bluetooth)")

    phone(d, *mobile, 1.15)
    label(d, mobile[0], mobile[1] + 42, "Staff Mobile Phone\n(scan / camera)")

    # CSR dashed box
    d.rounded_rectangle((csr[0] - 70, csr[1] - 55, csr[0] + 70, csr[1] + 55), 6, outline="#7EB6E0", width=2)
    # dashed rectangle effect
    for i in range(0, 140, 8):
        # top/bottom already solid enough; keep light blue frame
        pass
    pc(d, *csr, 1.1)
    label(d, csr[0], csr[1] + 48, "CSR Workstation")

    # --- Wiring (solid) matching VP sketch + additions ---
    # Admin & Server -> Router #1
    solid(d, (admin[0], admin[1] + 30), (admin[0], r1[1] - 20))
    solid(d, (admin[0], r1[1] - 20), (r1[0] - 20, r1[1] - 20))
    solid(d, (r1[0] - 20, r1[1] - 20), (r1[0] - 20, r1[1]))

    solid(d, (srv[0], srv[1] + 40), (srv[0], r1[1] - 40))
    solid(d, (srv[0], r1[1] - 40), (r1[0] + 10, r1[1] - 40))
    solid(d, (r1[0] + 10, r1[1] - 40), (r1[0] + 10, r1[1] - 18))

    # Internet -> Firewall -> Router #1
    solid(d, (inet[0], inet[1] + 30), (fw[0], fw[1] - 30))
    solid(d, (fw[0], fw[1] + 28), (fw[0], fw[1] + 50))
    solid(d, (fw[0], fw[1] + 50), (r1[0] + 50, fw[1] + 50))
    solid(d, (r1[0] + 50, fw[1] + 50), (r1[0] + 50, r1[1] - 10))

    # Router #1 vertical spine to Switch
    solid(d, (r1[0] - 50, r1[1]), (r1[0] - 50, sw[1]))
    solid(d, (r1[0] - 50, sw[1]), (sw[0] + 40, sw[1]))

    # Branch to Router #2
    solid(d, (r1[0] - 50, r2[1]), (r2[0] - 40, r2[1]))

    # Switch -> Router #3 -> CSR
    solid(d, (sw[0] + 45, sw[1]), (r3[0] - 40, r3[1]))
    solid(d, (r3[0] + 40, r3[1]), (csr[0] - 55, csr[1]))

    # Wireless dashed: Router #2 -> POS / Staff mobile
    dashed(d, (r2[0] + 40, r2[1] - 10), (pos[0] - 20, pos[1] + 10))
    dashed(d, (r2[0] + 40, r2[1] + 20), (mobile[0] - 20, mobile[1]))

    # POS peripherals: thermal printer + barcode scanner
    dashed(d, (pos[0] + 20, pos[1] - 8), (pos_printer[0] - 30, pos_printer[1] + 4))
    dashed(d, (pos[0] + 20, pos[1] + 12), (pos_scanner[0] - 28, pos_scanner[1] - 8))

    # Legend
    d.rounded_rectangle((40, H - 110, 560, H - 28), 6, fill="#F7FBFF", outline=LINE, width=1)
    d.text((55, H - 100), "Legend", font=font_lab, fill=INK)
    solid(d, (55, H - 70), (110, H - 70), width=3)
    d.text((120, H - 78), "Solid = wired LAN / uplink", font=font_sm, fill=INK)
    dashed(d, (55, H - 48), (110, H - 48), width=3)
    d.text((120, H - 56), "Dashed = Wi-Fi / Bluetooth (POS print & scanner)", font=font_sm, fill=INK)

    d.text(
        (580, H - 70),
        "Site LAN for Pyx Tracker: executives, CSR, Mobile POS (with scanner/printer), and staff phones reach the application server.",
        font=font_sm,
        fill=MUTED,
    )

    img.save(path)
    print("wrote", path, img.size, path.stat().st_size)


def patch_docx(doc_path: Path, png: Path) -> None:
    png_bytes = png.read_bytes()
    with zipfile.ZipFile(doc_path) as z:
        topo = z.read("word/media/image16.png")
    tmp = doc_path.parent / (doc_path.name + ".nd2.tmpzip")
    with zipfile.ZipFile(doc_path, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            name = info.filename.replace("\\", "/")
            if name == "word/media/image15.png":
                data = png_bytes
                print("replaced image15 Network Design")
            zout.writestr(info, data)
    try:
        tmp.replace(doc_path)
    except PermissionError:
        doc_path.write_bytes(tmp.read_bytes())
        tmp.unlink(missing_ok=True)
    with zipfile.ZipFile(doc_path) as z:
        assert z.read("word/media/image16.png") == topo
        assert z.read("word/media/image15.png") == png_bytes
    print("Topology image16 preserved")


def patch_text(doc_path: Path) -> None:
    doc = Document(str(doc_path))

    def set_text(p, text):
        for r in p.runs:
            r.text = ""
        (p.runs[0] if p.runs else p.add_run()).text = text

    for p in doc.paragraphs:
        t = p.text.strip()
        if t.startswith("A network design outlines") or t.startswith("Figure 3.16 presents the Network Design"):
            set_text(
                p,
                "Figure 3.16 presents the Network Design of Pyx Tracker for the Pyx Food Products site. "
                "Executive/Admin workstations and the application server (PHP, MySQL, API) connect through "
                "Router #1. A Firewall/NAT mediates the Internet/ISP uplink. The LAN switch distributes "
                "traffic to Router #3 for the CSR workstation and to Router #2 for wireless clients. "
                "Dashed links show Wi-Fi access for the Android Mobile POS device and staff mobile phones, "
                "plus Bluetooth/USB or Wi-Fi links from the POS device to an ESC/POS thermal printer and a "
                "barcode scanner used for product lookup at checkout. Solid lines represent wired LAN "
                "connections. The separate Network Topology figure is unchanged.",
            )
        if t.startswith("Solid lines denote wired LAN") or t == "This figure shows the network design.":
            set_text(
                p,
                "Solid lines denote wired LAN or uplink paths; dashed lines denote wireless (Wi-Fi) or "
                "POS peripheral links (thermal printer and barcode scanner).",
            )
    doc.save(str(doc_path))
    print("caption updated")


def main() -> None:
    src = SRC if SRC.exists() else next((p for p in FALLBACKS if p.exists()), None)
    if src is None:
        raise FileNotFoundError("No DOCX source")
    print("SRC", src)
    DESKTOP.mkdir(parents=True, exist_ok=True)
    shutil.copy2(src, WORK)

    generate(OUT_IMG)
    patch_docx(WORK, OUT_IMG)
    patch_text(WORK)

    saved = None
    for name in [
        "Pyx_Tracker_Capstone_SQL_ERD.docx",
        "Pyx_Tracker_Capstone_SQL_ERD_netdesign.docx",
        "Pyx_Tracker_Capstone_SQL_ERD_with_POS_diagrams.docx",
        "Pyx_Tracker_Capstone_SQL_ERD_updated.docx",
    ]:
        dest = DESKTOP / name
        try:
            shutil.copy2(WORK, dest)
            print("saved", dest, dest.stat().st_size)
            saved = dest
        except PermissionError:
            print("locked", name)
    if saved is None:
        saved = DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_network_v2.docx"
        shutil.copy2(WORK, saved)
        print("fallback", saved)

    with zipfile.ZipFile(saved) as z:
        print("design", len(z.read("word/media/image15.png")), "topo", len(z.read("word/media/image16.png")))
    print("DONE", saved)


if __name__ == "__main__":
    main()
