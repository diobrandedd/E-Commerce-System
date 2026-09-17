"""Replace ONLY Network Design (image15). Never modify Network Topology (image16)."""

from __future__ import annotations

import math
import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ASSETS = Path(r"C:/Users/Admin/.cursor/projects/c-xampp-htdocs-rts/assets")
ICONS = {
    "server": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-a95ef318-edef-4c76-a546-2d6e727bc9d6.png",
    "mobile": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-58211f65-cc43-4953-b9f0-7c53bc55eed7.png",
    "staff": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-ca41de92-4028-4dbd-92ed-3335586114aa.png",
    "admin": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-06d1e19a-b3a5-4c8b-ac47-8405cc284175.png",
    "router": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-4539b8ca-1aaa-4455-88b8-82934bf69d44.png",
}

OUT = Path(r"C:/xampp/htdocs/rts/output/documents/_network_design_pyx.png")
TOPO_LOCK = Path(r"C:/xampp/htdocs/rts/output/documents/_topo16_lock.bin")
DESIGN_MEDIA = "word/media/image15.png"
TOPO_MEDIA = "word/media/image16.png"

DOCS = [
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx"),
]


def fit_icon(path: Path, size: int = 72, phone: bool = False, crop_bottom: float = 0.0) -> Image.Image:
    im = Image.open(path).convert("RGBA")
    w, h = im.size
    if crop_bottom:
        im = im.crop((0, 0, w, int(h * (1 - crop_bottom))))
    if phone:
        tile = Image.new("RGBA", (size, size), (255, 255, 255, 255))
        im.thumbnail((size - 6, size - 6), Image.Resampling.LANCZOS)
        tile.paste(im, ((size - im.size[0]) // 2, (size - im.size[1]) // 2), im)
        return tile
    im.thumbnail((size, size), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), (255, 255, 255, 0))
    canvas.paste(im, ((size - im.size[0]) // 2, (size - im.size[1]) // 2), im)
    return canvas


def block_arrow(draw, x0, y0, x1, y1, color=(170, 170, 170)):
    ang = math.atan2(y1 - y0, x1 - x0)
    length = math.hypot(x1 - x0, y1 - y0) or 1
    ux, uy = (x1 - x0) / length, (y1 - y0) / length
    px, py = -uy, ux
    body = max(length - 20, 1)
    half, tip = 8, 16
    pts = [
        (x0 + px * half, y0 + py * half),
        (x0 + ux * body + px * half, y0 + uy * body + py * half),
        (x0 + ux * body + px * tip, y0 + uy * body + py * tip),
        (x1, y1),
        (x0 + ux * body - px * tip, y0 + uy * body - py * tip),
        (x0 + ux * body - px * half, y0 + uy * body - py * half),
        (x0 - px * half, y0 - py * half),
    ]
    draw.polygon(pts, fill=color)


def text_c(draw, cx, y, text, font, fill=(25, 25, 25)):
    lines = text.split("\n")
    yy = y
    for line in lines:
        b = draw.textbbox((0, 0), line, font=font)
        draw.text((cx - (b[2] - b[0]) / 2, yy), line, font=font, fill=fill)
        yy += b[3] - b[1] + 1


def draw_padlock(draw, cx, cy):
    # blue padlock like sample
    draw.rounded_rectangle((cx - 28, cy - 5, cx + 28, cy + 40), radius=6, fill=(40, 110, 210), outline=(20, 70, 150), width=2)
    draw.arc((cx - 20, cy - 38, cx + 20, cy), start=180, end=0, fill=(40, 110, 210), width=6)
    draw.ellipse((cx - 6, cy + 10, cx + 6, cy + 22), fill=(255, 220, 80))


def draw_document(draw, cx, cy):
    draw.rectangle((cx - 22, cy - 30, cx + 22, cy + 30), fill=(250, 250, 250), outline=(40, 40, 40), width=2)
    draw.polygon([(cx + 8, cy - 30), (cx + 22, cy - 30), (cx + 22, cy - 16)], fill=(210, 210, 210), outline=(40, 40, 40))
    for i in range(4):
        y = cy - 12 + i * 10
        draw.line((cx - 14, y, cx + 10, y), fill=(80, 80, 80), width=2)
    # pen
    draw.line((cx + 10, cy + 8, cx + 34, cy - 16), fill=(30, 90, 180), width=4)
    draw.polygon([(cx + 34, cy - 16), (cx + 40, cy - 10), (cx + 28, cy - 4)], fill=(30, 90, 180))


def draw_data_blocks(draw, cx, cy):
    for i, col in enumerate([(160, 160, 170), (140, 140, 155), (120, 120, 140)]):
        x = cx - 35 + i * 18
        y = cy - 25 + i * 6
        draw.rounded_rectangle((x, y, x + 40, y + 50), radius=4, fill=col, outline=(60, 60, 70), width=2)


def make_diagram() -> Path:
    admin = fit_icon(ICONS["admin"], 70)
    staff = fit_icon(ICONS["staff"], 70, crop_bottom=0.08)
    mobile = fit_icon(ICONS["mobile"], 70, phone=True)
    server = fit_icon(ICONS["server"], 78)
    router = fit_icon(ICONS["router"], 78)

    W, H = 1500, 1050
    img = Image.new("RGB", (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    f_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 28)
    f_lab = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)
    f_small = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 11)
    f_cloud = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 26)
    f_oval = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 11)

    draw.text((36, 18), "Network Design", font=f_title, fill=(20, 20, 20))

    # ===== TOP LINEAR FLOW (sample-like) =====
    top = 120
    # 1 Admin
    x1 = 90
    img.paste(admin, (x1 - 35, top), admin)
    text_c(draw, x1, top + 78, "ADMIN /\nEXECUTIVE", f_lab)
    block_arrow(draw, x1 + 50, top + 35, 175, top + 35)

    # 2 Staff workstation
    x2 = 230
    img.paste(staff, (x2 - 35, top), staff)
    text_c(draw, x2, top + 78, "STAFF\nWORKSTATION", f_lab)
    # branch down to mobile (like facial recognition branch)
    block_arrow(draw, x2, top + 100, x2, 250, color=(160, 160, 160))
    img.paste(mobile, (x2 - 35, 255), mobile)
    text_c(draw, x2, 335, "STAFF MOBILE\nPHONE\n(camera / scan)", f_lab)
    block_arrow(draw, x2 + 50, top + 35, 320, top + 35)

    # 3 Operations / cast equivalent
    x3 = 380
    draw.rounded_rectangle((x3 - 48, top - 5, x3 + 48, top + 70), radius=8, outline=(40, 40, 40), width=2, fill=(255, 248, 230))
    draw.ellipse((x3 - 18, top + 8, x3 + 18, top + 44), outline=(40, 90, 180), width=3)
    draw.rectangle((x3 - 22, top + 40, x3 + 22, top + 58), fill=(40, 90, 180))
    text_c(draw, x3, top + 78, "USE SYSTEM\n(orders / POS /\ninventory / RTS)", f_lab)
    block_arrow(draw, x3 + 55, top + 35, 470, top + 35)

    # 4 DATA / application server
    x4 = 545
    draw_data_blocks(draw, x4, top + 25)
    img.paste(server, (x4 + 25, top - 5), server)
    text_c(draw, x4 + 20, top + 78, "APPLICATION\nSERVER / DATA", f_lab)
    block_arrow(draw, x4 + 95, top + 35, 700, top + 35)

    # 5 SESSION box (like HASH)
    x5 = 780
    draw.rectangle((x5 - 70, top + 5, x5 + 70, top + 55), outline=(30, 30, 30), width=2, fill=(255, 255, 255))
    draw.text((x5 - 62, top + 18), "sess_a9f3...php", font=f_small, fill=(40, 40, 40))
    text_c(draw, x5, top - 18, "SESSION / API", f_lab)
    block_arrow(draw, x5 + 78, top + 35, 920, top + 35)

    # 6 ENCRYPTION / auth
    x6 = 980
    draw_padlock(draw, x6, top + 5)
    text_c(draw, x6, top + 78, "AUTH /\nENCRYPTION", f_lab)
    block_arrow(draw, x6 + 45, top + 35, 1095, top + 35)

    # 7 SIGNED / saved record
    x7 = 1160
    draw_document(draw, x7, top + 25)
    text_c(draw, x7, top + 78, "SAVED\nRECORD", f_lab)

    # big arrow into NETWORK cloud
    block_arrow(draw, x7, top + 110, 750, 390, color=(150, 150, 150))

    # ===== CENTER NETWORK CLOUD =====
    cx, cy = 750, 480
    overlay = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    od = ImageDraw.Draw(overlay)
    fill = (150, 200, 240, 230)
    line = (60, 120, 180, 255)
    for e in [
        (cx - 210, cy - 35, cx - 30, cy + 95),
        (cx - 120, cy - 95, cx + 70, cy + 55),
        (cx - 30, cy - 45, cx + 210, cy + 100),
        (cx - 140, cy - 5, cx + 140, cy + 115),
    ]:
        od.ellipse(e, fill=fill, outline=line, width=3)
    img = Image.alpha_composite(img.convert("RGBA"), overlay).convert("RGB")
    draw = ImageDraw.Draw(img)
    img.paste(router, (cx - 39, cy - 50), router)
    text_c(draw, cx, cy + 45, "NETWORK", f_cloud, fill=(20, 55, 110))
    text_c(draw, cx, cy + 78, "Router / Wi-Fi access point", f_small, fill=(40, 40, 40))

    # ===== BOTTOM THREE BRANCHES (sample style) =====
    endpoints = [
        (280, admin, "ADMIN /\nEXECUTIVE", "USE SYSTEM"),
        (750, staff, "STAFF\nWORKSTATION", "USE SYSTEM"),
        (1220, mobile, "STAFF\nMOBILE PHONE", "USE SYSTEM"),
    ]
    for ex, icon, lab, oval in endpoints:
        # arrow from cloud
        block_arrow(draw, cx + (ex - cx) * 0.15, cy + 120, ex, 640, color=(160, 160, 160))
        # signed document
        draw_document(draw, ex, 680)
        text_c(draw, ex - 70, 655, "[SESSION]", f_lab, fill=(0, 90, 150))
        text_c(draw, ex + 55, 700, "[BROWSER]", f_lab, fill=(0, 90, 150))
        block_arrow(draw, ex, 720, ex, 780, color=(150, 150, 150))
        # endpoint user
        img.paste(icon, (ex - 45, 785), icon)
        text_c(draw, ex - 55, 870, lab, f_lab)
        # oval action like COUNT VOTE
        draw.ellipse((ex + 25, 845, ex + 145, 885), fill=(255, 240, 200), outline=(40, 40, 40), width=2)
        text_c(draw, ex + 85, 855, oval, f_oval)

    # footer note
    draw.text(
        (36, H - 36),
        "This figure shows the network design.",
        font=f_small,
        fill=(60, 60, 60),
    )

    img.save(OUT)
    print("wrote", OUT, img.size, OUT.stat().st_size)
    return OUT


def patch_design_only(png_bytes: bytes, topo_lock: bytes) -> None:
    for doc in DOCS:
        if not doc.exists():
            continue
        tmp = doc.parent / (doc.name + ".tmp_design_only")
        with zipfile.ZipFile(doc, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
            for info in zin.infolist():
                data = zin.read(info.filename)
                name = info.filename.replace("\\", "/")
                if name == DESIGN_MEDIA:
                    data = png_bytes
                elif name == TOPO_MEDIA:
                    # force preserve locked topology bytes
                    data = topo_lock
                zout.writestr(info, data)
        wrote = False
        try:
            tmp.replace(doc)
            wrote = True
            print("saved", doc.name)
        except PermissionError:
            try:
                doc.write_bytes(tmp.read_bytes())
                wrote = True
                print("saved write", doc.name)
            except PermissionError:
                print("LOCKED (skipped)", doc.name)
            tmp.unlink(missing_ok=True)

        if not wrote:
            continue

        with zipfile.ZipFile(doc, "r") as z:
            got = z.read(TOPO_MEDIA)
            ok = got == topo_lock
            print("  topology preserved:", ok, "design size:", z.getinfo(DESIGN_MEDIA).file_size)
            if not ok:
                raise RuntimeError(f"Topology image changed in {doc}")


def update_design_text_only(doc_path: Path) -> None:
    from docx import Document

    d = Document(str(doc_path))
    changed = False
    for p in d.paragraphs:
        t = p.text.strip()
        # stop if we somehow hit topology heading — still only edit design paras
        if t.startswith("Figure 3.16"):
            msg = "Figure 3.16  Network Design of Pyx Tracker"
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = msg
            else:
                p.add_run(msg)
            changed = True
        if t.startswith("A network design outlines") or t.startswith("Browser clients connect"):
            msg = (
                "A network design outlines the architecture that supports communication between various devices. "
                "The network diagram below depicts a Wireless LAN (WLAN) configuration where multiple devices "
                "connect to a central Wi-Fi router. In this design, the router serves as the hub, providing "
                "wireless internet access to all connected devices. These devices, including Admin/Executive "
                "and Staff workstations and Staff mobile phones, establish connections to the application server, "
                "forming the core communication layer within the network and ensuring uninterrupted access for users."
            )
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = msg
            else:
                p.add_run(msg)
            changed = True
        if t.startswith("This figure shows the logical network design") or t.startswith(
            "The diagram does not assert"
        ):
            msg = "This figure shows the network design."
            for r in p.runs:
                r.text = ""
            if p.runs:
                p.runs[0].text = msg
            else:
                p.add_run(msg)
            changed = True
    if not changed:
        return
    tmp = doc_path.parent / (doc_path.name + ".tmp_design_txt")
    d.save(str(tmp))
    try:
        # re-assert topology bytes after docx save (python-docx may not touch media, but be safe)
        topo = TOPO_LOCK.read_bytes()
        out2 = doc_path.parent / (doc_path.name + ".tmp_design_txt2")
        with zipfile.ZipFile(tmp, "r") as zin, zipfile.ZipFile(out2, "w", zipfile.ZIP_DEFLATED) as zout:
            for info in zin.infolist():
                data = zin.read(info.filename)
                name = info.filename.replace("\\", "/")
                if name == TOPO_MEDIA:
                    data = topo
                elif name == DESIGN_MEDIA:
                    data = OUT.read_bytes()
                zout.writestr(info, data)
        try:
            out2.replace(doc_path)
        except PermissionError:
            doc_path.write_bytes(out2.read_bytes())
        tmp.unlink(missing_ok=True)
        out2.unlink(missing_ok=True)
        print("text updated", doc_path.name)
    except Exception as e:
        print("text update fail", doc_path.name, e)


def main() -> None:
    if not TOPO_LOCK.exists():
        # create lock from desktop if missing
        desk = DOCS[0]
        with zipfile.ZipFile(desk) as z:
            TOPO_LOCK.write_bytes(z.read(TOPO_MEDIA))
    topo_lock = TOPO_LOCK.read_bytes()
    make_diagram()
    png = OUT.read_bytes()
    patch_design_only(png, topo_lock)
    for doc in DOCS:
        if doc.exists():
            try:
                update_design_text_only(doc)
            except Exception as e:
                print("caption err", doc, e)
    # final verify all
    for doc in DOCS:
        if not doc.exists():
            continue
        try:
            with zipfile.ZipFile(doc) as z:
                assert z.read(TOPO_MEDIA) == topo_lock
                assert z.read(DESIGN_MEDIA) == png
            print("VERIFIED", doc.name)
        except Exception as e:
            print("VERIFY FAIL", doc.name, e)


if __name__ == "__main__":
    main()
