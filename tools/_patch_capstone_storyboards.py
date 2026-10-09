"""Build Executive vs Staff storyboards from user screenshots and patch the DOCX."""

from __future__ import annotations

import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
from lxml import etree

ASSETS = Path(r"C:\Users\Admin\.cursor\projects\c-xampp-htdocs-rts\assets")
DESKTOP = Path(r"C:\Users\Admin\Desktop\capstone")
WORK = Path(r"C:\xampp\htdocs\rts\output\documents")

# Map of screenshot filenames (suffix match)
SHOTS = {
    "login_web": "image__1_-d5a0701e-a077-46a7-9161-244a84689d3a.png",
    "honey_wh": "image__2_-9c5ee872-4387-4c02-b00b-c5cc5a50fd53.png",
    "honey_prod": "image__3_-cce2d042-471c-4d77-bead-425915130a86.png",
    "paragis": "image__5_-5f00d93c-d4ed-4234-b3ef-ba919a1611db.png",
    "black_rice": "image__6_-7b4e6d6c-337c-40e3-8249-6b206fcc3b21.png",
    "costing": "48a882f2-037a-4a63-868e-9ebe182a77c0-77c27837-3118-482a-b342-2b84983970d9.png",
    "cacao": "55cecc0d-7bbf-48f0-8e5c-d3ec674e762f-fcdb66a4-f60b-4bb5-bc45-f92d2872ad79.png",
    "orders": "image__7_-206f49a3-0df5-4751-8daf-dce44a9f4d11.png",
    "deliveries_review": "image__8_-cff9070d-4935-4eb1-aae9-840b72f131b6.png",
    "pack_scan": "image__11_-bec089ff-e9b4-4d8b-b49e-e62d4e85eaee.png",
    "add_deliveries": "image__10_-8bd93406-8dea-4783-8625-c789c8b7bc08.png",
    "record_rts": "image__9_-8c043717-5624-4a1e-8ed8-19df8879de36.png",
    "rts_inv": "image__12_-a1399f38-3a50-4040-88f2-9f0608197432.png",
    "pos_login": "image__13_-f30e48e7-8aa4-4b8d-a612-31b52839e48b.png",
    "pos_checkout": "39-5be61cf4-9d0a-4f09-b3b6-4e8d0afd8d40.png",
    "pos_scan": "40-3dff173d-a9c4-4cdd-967b-7f55f5b52f77.png",
}

INK = "#1A1A1A"
MUTED = "#555555"
BG = "#F5F2EA"
CARD = "#FFFFFF"
ACCENT = "#B8860B"
BORDER = "#D0C8B8"

font_title = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 28)
font_step = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 16)
font_cap = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 13)
font_note = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 12)


def find_shot(key: str) -> Path:
    name = SHOTS[key]
    matches = list(ASSETS.glob(f"*{name}"))
    if not matches:
        raise FileNotFoundError(name)
    return matches[0]


def load_fit(path: Path, max_w: int, max_h: int) -> Image.Image:
    im = Image.open(path).convert("RGB")
    im.thumbnail((max_w, max_h), Image.Resampling.LANCZOS)
    return im


def panel_board(
    out: Path,
    title: str,
    subtitle: str,
    panels: list[tuple[str, str, str]],
    cols: int = 3,
) -> None:
    """panels: (shot_key, step_label, caption)"""
    pad = 24
    gap = 18
    header_h = 90
    label_h = 52
    cell_w, cell_h = 520, 340
    rows = (len(panels) + cols - 1) // cols
    W = pad * 2 + cols * cell_w + (cols - 1) * gap
    H = header_h + pad + rows * (cell_h + label_h + gap) + 40

    canvas = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(canvas)
    draw.text((pad, 22), title, font=font_title, fill=INK)
    draw.text((pad, 56), subtitle, font=font_note, fill=MUTED)

    for i, (key, step, cap) in enumerate(panels):
        r, c = divmod(i, cols)
        x = pad + c * (cell_w + gap)
        y = header_h + r * (cell_h + label_h + gap)
        # card
        draw.rounded_rectangle((x, y, x + cell_w, y + cell_h + label_h - 8), radius=12, fill=CARD, outline=BORDER, width=2)
        # step badge
        draw.rounded_rectangle((x + 10, y + 10, x + 120, y + 36), radius=8, fill=ACCENT)
        draw.text((x + 18, y + 14), step, font=font_step, fill="white")
        # image
        shot = load_fit(find_shot(key), cell_w - 24, cell_h - 50)
        ix = x + (cell_w - shot.width) // 2
        iy = y + 44
        canvas.paste(shot, (ix, iy))
        # caption
        draw.text((x + 12, y + cell_h + 4), cap, font=font_cap, fill=MUTED)

    out.parent.mkdir(parents=True, exist_ok=True)
    canvas.save(out, "PNG", optimize=True)
    print(f"saved {out} ({out.stat().st_size})")


def build_all() -> dict[str, Path]:
    outs = {}
    # Fig 3.6 — Executive login + inventory
    p = DESKTOP / "Storyboard_Executive_Inventory.png"
    panel_board(
        p,
        "Admin / Executive Storyboard — Login & Inventory",
        "Role badge EXECUTIVE. Full inventory navigation across raw materials, packaging, supplies, and products.",
        [
            ("login_web", "Step 1", "Sign in to Pyx Tracker Management System"),
            ("honey_wh", "Step 2", "Raw Honey — Warehouse stock-in / stock-out"),
            ("honey_prod", "Step 3", "Raw Honey — Production scan & gallons by variant"),
            ("paragis", "Step 4", "Dried Paragis — leaf pool & finished pouches"),
            ("black_rice", "Step 5", "Black Rice — pool, pouches, production activity"),
            ("cacao", "Step 6", "Cacao — jar pool, scans, costing stock"),
        ],
        cols=3,
    )
    outs["inv"] = p

    # Fig 3.7 — Executive costing
    p = DESKTOP / "Storyboard_Executive_Costing.png"
    panel_board(
        p,
        "Admin / Executive Storyboard — Product Costing",
        "Executive-only costing: manufacturing COGS, parcel packaging tiers, sell price, and margin.",
        [
            ("login_web", "Step 1", "Sign in as Executive"),
            ("costing", "Step 2", "Open Product Costing (Products / Parcel packaging)"),
            ("costing", "Step 3", "Review COGS, pack cost, landed, sell price, margin"),
        ],
        cols=3,
    )
    outs["cost"] = p

    # Fig 3.8 — Executive orders & deliveries
    p = DESKTOP / "Storyboard_Executive_Orders_Deliveries.png"
    panel_board(
        p,
        "Admin / Executive Storyboard — Orders & Deliveries",
        "Online order prep/stamp tools, pack scan, add deliveries, and batch review.",
        [
            ("orders", "Step 1", "Online Orders — prep Excel & stamp into batch"),
            ("pack_scan", "Step 2", "Deliveries — Pack scan (deduct before wrap)"),
            ("add_deliveries", "Step 3", "Deliveries — scan tracking & before-ship photo"),
            ("deliveries_review", "Step 4", "Deliveries review — photo & parcel status"),
        ],
        cols=2,
    )
    outs["orders"] = p

    # Fig 3.9 — Executive RTS
    p = DESKTOP / "Storyboard_Executive_RTS.png"
    panel_board(
        p,
        "Admin / Executive Storyboard — Return-to-Sender",
        "Record RTS by tracking, then confirm sellable RTS inventory (Executive oversight).",
        [
            ("record_rts", "Step 1", "Record RTS — scan/enter tracking number"),
            ("rts_inv", "Step 2", "RTS inventory — sellable on-hand & sync"),
            ("rts_inv", "Step 3", "Review honey RTS / other products / used RTS"),
        ],
        cols=3,
    )
    outs["rts"] = p

    # Fig 3.10 — Staff POS
    p = DESKTOP / "Storyboard_Staff_POS.png"
    panel_board(
        p,
        "Staff Storyboard — Point of Sale (Pyx POS)",
        "Staff terminal: authenticate with Employee ID, scan products, build cart, checkout.",
        [
            ("pos_login", "Step 1", "Pyx POS staff terminal — Enter Employee ID"),
            ("pos_checkout", "Step 2", "Checkout — empty cart / Scan add product"),
            ("pos_scan", "Step 3", "Scan product — barcode/SKU then Add"),
        ],
        cols=3,
    )
    outs["staff"] = p

    # Also copy to work folder
    for k, path in outs.items():
        dest = WORK / path.name
        Image.open(path).save(dest)
    return outs


def set_para_text(p, text: str, ns) -> None:
    texts = p.xpath(".//w:t", namespaces=ns)
    if not texts:
        return
    texts[0].text = text
    for extra in texts[1:]:
        extra.text = ""


def patch_docx(outs: dict[str, Path]) -> None:
    src_candidates = [
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
        DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
        WORK / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    ]
    src = next(p for p in src_candidates if p.exists())

    media_map = {
        "word/media/image8.png": outs["inv"],   # Fig 3.6
        "word/media/image9.png": outs["orders"],  # will renumber captions
        "word/media/image10.png": outs["cost"],
        "word/media/image11.png": outs["rts"],
        "word/media/image12.png": outs["staff"],
    }
    # Reorder for logical figure sequence:
    # 3.6 Executive Inventory, 3.7 Executive Costing, 3.8 Executive Orders,
    # 3.9 Executive RTS, 3.10 Staff POS
    media_map = {
        "word/media/image8.png": outs["inv"],
        "word/media/image9.png": outs["cost"],
        "word/media/image10.png": outs["orders"],
        "word/media/image11.png": outs["rts"],
        "word/media/image12.png": outs["staff"],
    }

    with zipfile.ZipFile(src, "r") as zin:
        items = list(zin.infolist())
        blob = {i.filename: zin.read(i.filename) for i in items}

    for media, path in media_map.items():
        blob[media] = path.read_bytes()

    NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
    root = etree.fromstring(blob["word/document.xml"])
    replacements = [
        (
            "The following labeled wireframes",
            "The following storyboards use production screenshots from Pyx Tracker and Pyx POS. "
            "They are separated by role: Admin/Executive (web management system with EXECUTIVE badge) "
            "and Staff (operational POS terminal and day-to-day scan workflows). ",
        ),
        ("Figure 3.6  Inventory Storyboard", "Figure 3.6  Admin/Executive Storyboard — Login and Inventory"),
        (
            "The user selects the category, product, and lot",
            "Figure 3.6 shows the Executive sign-in screen and inventory flows for Raw Honey "
            "(Warehouse and Production), Dried Paragis, Black Rice, and Cacao, including stock-in, "
            "production scans, leaf/jar pools, and finished-goods records.",
        ),
        ("Figure 3.7  Online Orders Storyboard", "Figure 3.7  Admin/Executive Storyboard — Product Costing"),
        (
            "Staff select a source-specific preparation tool",
            "Figure 3.7 shows the Executive Product Costing screen: manufacturing COGS, packaging "
            "tiers, landed cost, sell price, and margin by department and size. Staff do not use "
            "this costing hub.",
        ),
        ("Figure 3.8  Product Costing Storyboard", "Figure 3.8  Admin/Executive Storyboard — Online Orders and Deliveries"),
        (
            "Admin/Executive selects a product or parcel recipe",
            "Figure 3.8 shows Executive order and delivery workflows: Online Orders prep and "
            "stamper tools, Pack scan, Add deliveries with before-shipment photo, and Deliveries review.",
        ),
        ("Figure 3.9  Parcel Documentation Storyboard", "Figure 3.9  Admin/Executive Storyboard — Return-to-Sender"),
        (
            "The operator enters the tracking number, captures or uploads the image",
            "Figure 3.9 shows Executive RTS recording by tracking number and RTS inventory "
            "confirmation for sellable and related stock pools.",
        ),
        ("Figure 3.10  Return-to-Sender Storyboard", "Figure 3.10  Staff Storyboard — Point of Sale (Pyx POS)"),
        (
            "The operator records the case and condition, then reviews a separate stock-in session",
            "Figure 3.10 shows the Staff POS terminal: Employee ID sign-in, Checkout with Scan/add "
            "product, and the Scan product barcode/SKU step used by cashiers and Mobile POS staff.",
        ),
    ]

    for p in root.xpath("//w:p", namespaces=NS):
        t = "".join(p.xpath(".//w:t/text()", namespaces=NS))
        for old, new in replacements:
            if t.startswith(old) or t == old or (old in t and len(old) > 40 and t.startswith(old[:40])):
                set_para_text(p, new, NS)
                break
            if t.strip() == old.strip():
                set_para_text(p, new, NS)
                break

    # Also match caption-only exact
    for p in root.xpath("//w:p", namespaces=NS):
        t = "".join(p.xpath(".//w:t/text()", namespaces=NS)).strip()
        exact = {
            "Figure 3.6  Inventory Storyboard": "Figure 3.6  Admin/Executive Storyboard — Login and Inventory",
            "Figure 3.7  Online Orders Storyboard": "Figure 3.7  Admin/Executive Storyboard — Product Costing",
            "Figure 3.8  Product Costing Storyboard": "Figure 3.8  Admin/Executive Storyboard — Online Orders and Deliveries",
            "Figure 3.9  Parcel Documentation Storyboard": "Figure 3.9  Admin/Executive Storyboard — Return-to-Sender",
            "Figure 3.10  Return-to-Sender Storyboard": "Figure 3.10  Staff Storyboard — Point of Sale (Pyx POS)",
        }
        if t in exact:
            set_para_text(p, exact[t], NS)

    blob["word/document.xml"] = etree.tostring(
        root, xml_declaration=True, encoding="UTF-8", standalone=True
    )

    out_paths = [
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_storyboards.docx",
        DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
        WORK / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    ]
    for path in out_paths:
        tmp = path.with_suffix(".tmp.docx")
        try:
            with zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED) as zout:
                for item in items:
                    zout.writestr(item, blob[item.filename])
            tmp.replace(path)
            print(f"OK {path}")
        except PermissionError as e:
            if tmp.exists():
                tmp.unlink(missing_ok=True)
            print(f"LOCKED {path.name}: {e}")


if __name__ == "__main__":
    outs = build_all()
    patch_docx(outs)
