"""Build star topology figure with user-supplied icons; patch DOCX media in place."""

from __future__ import annotations

import shutil
import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ASSETS = Path(
    r"C:/Users/Admin/.cursor/projects/c-xampp-htdocs-rts/assets"
)
# Attachment order matches user numbering 1–5
ICON_FILES = {
    "app_server": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-a95ef318-edef-4c76-a546-2d6e727bc9d6.png",
    "mobile": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-58211f65-cc43-4953-b9f0-7c53bc55eed7.png",
    "staff_ws": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-ca41de92-4028-4dbd-92ed-3335586114aa.png",
    "admin_ws": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-06d1e19a-b3a5-4c8b-ac47-8405cc284175.png",
    "router": ASSETS
    / "c__Users_Admin_AppData_Roaming_Cursor_User_workspaceStorage_c7c434809b0e3f6b99f4071ead395cf1_images_image-4539b8ca-1aaa-4455-88b8-82934bf69d44.png",
}

OUT = Path(r"C:/xampp/htdocs/rts/output/documents/_star_topology_pyx.png")
DOCS = [
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new"),
]

# rId23 -> media/image16.png is Figure 3.17
MEDIA_NAME = "word/media/image16.png"


def load_icon(path: Path, max_side: int = 140) -> Image.Image:
    im = Image.open(path).convert("RGBA")
    w, h = im.size
    px = im.load()
    # Flood-fill near-black background from corners to transparency (phone icon).
    from collections import deque

    visited = [[False] * w for _ in range(h)]
    q = deque([(0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)])
    while q:
        x, y = q.popleft()
        if x < 0 or y < 0 or x >= w or y >= h or visited[y][x]:
            continue
        r, g, b, _a = px[x, y]
        if r + g + b > 50:
            continue
        visited[y][x] = True
        px[x, y] = (0, 0, 0, 0)
        q.extend([(x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1)])

    im.thumbnail((max_side, max_side), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (max_side, max_side), (255, 255, 255, 0))
    ox = (max_side - im.size[0]) // 2
    oy = (max_side - im.size[1]) // 2
    canvas.paste(im, (ox, oy), im)
    return canvas


def draw_node(base: Image.Image, icon: Image.Image, cx: int, cy: int, label: str, font, box_w=220, box_h=210):
    draw = ImageDraw.Draw(base)
    x0, y0 = cx - box_w // 2, cy - box_h // 2
    x1, y1 = cx + box_w // 2, cy + box_h // 2
    draw.rounded_rectangle((x0, y0, x1, y1), radius=10, outline=(30, 30, 30), width=2, fill=(255, 255, 255))
    # icon
    ix = cx - icon.size[0] // 2
    iy = y0 + 16
    base.paste(icon, (ix, iy), icon)
    # label under icon
    bbox = draw.textbbox((0, 0), label, font=font)
    tw = bbox[2] - bbox[0]
    # wrap long labels
    lines = label.split("\n") if "\n" in label else [label]
    if len(label) > 22 and "\n" not in label:
        # split at space near middle
        parts = label.split(" ")
        mid = len(parts) // 2
        lines = [" ".join(parts[:mid]), " ".join(parts[mid:])]
    total_h = 0
    sizes = []
    for line in lines:
        b = draw.textbbox((0, 0), line, font=font)
        sizes.append((b[2] - b[0], b[3] - b[1]))
        total_h += b[3] - b[1] + 2
    ty = y1 - total_h - 14
    for line, (lw, lh) in zip(lines, sizes):
        draw.text((cx - lw // 2, ty), line, font=font, fill=(20, 20, 20))
        ty += lh + 2
    return (cx, cy, x0, y0, x1, y1)


def main() -> None:
    for k, p in ICON_FILES.items():
        if not p.exists():
            raise FileNotFoundError(p)

    icons = {
        "app_server": load_icon(ICON_FILES["app_server"], 130),
        "mobile": load_icon(ICON_FILES["mobile"], 110),
        "staff_ws": load_icon(ICON_FILES["staff_ws"], 130),
        "admin_ws": load_icon(ICON_FILES["admin_ws"], 130),
        "router": load_icon(ICON_FILES["router"], 120),
    }

    W, H = 1100, 900
    img = Image.new("RGB", (W, H), (255, 255, 255))
    draw = ImageDraw.Draw(img)
    title_f = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 28)
    label_f = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 14)
    note_f = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 13)

    draw.text((40, 28), "Network Topology", font=title_f, fill=(20, 20, 20))

    # positions: star
    center = (W // 2, H // 2 - 10)
    nodes = {
        "admin": (220, 200, icons["admin_ws"], "Admin/Executive\nworkstation"),
        "staff": (880, 200, icons["staff_ws"], "Staff workstation"),
        "mobile": (220, 680, icons["mobile"], "Staff mobile phone"),
        "server": (880, 680, icons["app_server"], "Application server"),
        "router": (center[0], center[1], icons["router"], "Router / access point"),
    }

    # Draw lines first (center to corners)
    for key in ("admin", "staff", "mobile", "server"):
        x, y, _, _ = nodes[key]
        draw.line((center[0], center[1], x, y), fill=(60, 60, 60), width=2)

    # Draw nodes
    for key, (x, y, icon, label) in nodes.items():
        bw, bh = (240, 220) if key != "router" else (250, 230)
        draw_node(img, icon, x, y, label, label_f, box_w=bw, box_h=bh)

    # notes under center
    note1 = "Logical local access topology"
    note2 = "Remote hosting requires an internet connection."
    for i, note in enumerate((note1, note2)):
        b = draw.textbbox((0, 0), note, font=note_f)
        tw = b[2] - b[0]
        draw.text((W // 2 - tw // 2, center[1] + 130 + i * 20), note, font=note_f, fill=(70, 70, 70))

    img.save(OUT)
    print("wrote", OUT, img.size, OUT.stat().st_size)

    png = OUT.read_bytes()
    for doc in DOCS:
        if not doc.exists():
            print("skip", doc)
            continue
        tmp = doc.parent / (doc.name + ".tmp_topo")
        try:
            with zipfile.ZipFile(doc, "r") as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
                for info in zin.infolist():
                    data = zin.read(info.filename)
                    name = info.filename.replace("\\", "/")
                    if name == MEDIA_NAME:
                        data = png
                        print("replaced", MEDIA_NAME, "in", doc.name)
                    zout.writestr(info, data)
            try:
                tmp.replace(doc)
                print("saved", doc, doc.stat().st_size)
            except PermissionError:
                try:
                    doc.write_bytes(tmp.read_bytes())
                    print("saved write_bytes", doc)
                except PermissionError as e:
                    print("LOCKED", doc, e)
                tmp.unlink(missing_ok=True)
        except Exception as e:
            print("ERR", doc, e)
            Path(tmp).unlink(missing_ok=True)

    # Prefer syncing Desktop from a successfully updated source
    desk = DOCS[1]
    for src in (DOCS[0], DOCS[2]):
        if src.exists():
            try:
                with zipfile.ZipFile(src) as z:
                    if z.getinfo(MEDIA_NAME).file_size == len(png):
                        shutil.copy2(src, desk)
                        print("desktop synced from", src.name)
                        break
            except Exception:
                pass


if __name__ == "__main__":
    main()
