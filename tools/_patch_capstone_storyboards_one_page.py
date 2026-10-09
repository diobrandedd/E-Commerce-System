"""One-screen storyboards in Coconut-sample layout:
  Heading
  [Screenshot]  [orange description box]
  Figure caption

Separated Executive vs Staff. Patches Desktop capstone DOCX.
"""

from __future__ import annotations

import zipfile
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
from lxml import etree

ASSETS = Path(r"C:\Users\Admin\.cursor\projects\c-xampp-htdocs-rts\assets")
DESKTOP = Path(r"C:\Users\Admin\Desktop\capstone")
WORK = Path(r"C:\xampp\htdocs\rts\output\documents")
SB_DIR = DESKTOP / "storyboards"

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

# Pages to embed as Fig 3.6–3.10 (one image each)
DOC_PAGES = [
    {
        "key": "login_web",
        "heading": "Executive - Login Page",
        "box_title": "Executive Module - Login Page",
        "box_body": (
            "The Executive opens Pyx Tracker Management System and signs in with an assigned "
            "username and password. Successful authentication routes the Executive to the role-based "
            "dashboard with full management navigation."
        ),
        "caption": "Figure 3.6: Executive Login Page of Pyx Tracker",
        "media": "word/media/image8.png",
    },
    {
        "key": "honey_wh",
        "heading": "Executive - Inventory (Raw Honey Warehouse)",
        "box_title": "Executive Module - Inventory Warehouse",
        "box_body": (
            "With an EXECUTIVE badge, the user manages Raw Honey warehouse stock-in and stock-out "
            "to production, reviews supplier lots, quantities, and availability, and can edit or "
            "stock out selected rows."
        ),
        "caption": "Figure 3.7: Executive Inventory Warehouse of Pyx Tracker",
        "media": "word/media/image9.png",
    },
    {
        "key": "costing",
        "heading": "Executive - Product Costing",
        "box_title": "Executive Module - Product Costing",
        "box_body": (
            "The Executive reviews manufacturing COGS, parcel packaging cost, landed cost, sell "
            "price, and margin by product department and size. This costing hub is for Executive "
            "pricing decisions and is not used by Staff cashiers."
        ),
        "caption": "Figure 3.8: Executive Product Costing of Pyx Tracker",
        "media": "word/media/image10.png",
    },
    {
        "key": "orders",
        "heading": "Executive - Online Orders",
        "box_title": "Executive Module - Online Orders",
        "box_body": (
            "The Executive prepares multi-platform order Excel files using VIP and Shopee tools, "
            "validates workbooks, then stamps waybill PDFs into the active batch for fulfillment."
        ),
        "caption": "Figure 3.9: Executive Online Orders of Pyx Tracker",
        "media": "word/media/image11.png",
    },
    {
        "key": "pos_login",
        "heading": "Staff - POS Login Page",
        "box_title": "Staff Module - POS Login",
        "box_body": (
            "Staff open the Pyx POS staff terminal and enter an Employee ID assigned in Settings. "
            "Only authorized executives and staff can open the POS app for walk-in sales."
        ),
        "caption": "Figure 3.10: Staff POS Login Page of Pyx Tracker",
        "media": "word/media/image12.png",
    },
]

# Extra pages saved to Desktop/storyboards (not all embedded — keep figure sequence stable)
EXTRA_PAGES = [
    {
        "key": "honey_prod",
        "heading": "Executive - Inventory (Raw Honey Production)",
        "box_title": "Executive Module - Honey Production",
        "box_body": (
            "The Executive scans bottles into packaging, reviews production gallons by honey "
            "variant, and monitors FIFO deduction across supplier lots."
        ),
        "caption": "Figure 3.6.2: Executive Honey Production of Pyx Tracker",
    },
    {
        "key": "pack_scan",
        "heading": "Executive - Deliveries Pack Scan",
        "box_title": "Executive Module - Pack Scan",
        "box_body": (
            "Before wrapping, the Executive or warehouse staff scans product barcodes to deduct "
            "stock from the selected pool and confirm the pack session."
        ),
        "caption": "Figure 3.9.2: Pack Scan of Pyx Tracker",
    },
    {
        "key": "record_rts",
        "heading": "Executive - Record RTS",
        "box_title": "Executive Module - Record RTS",
        "box_body": (
            "The Executive starts a return case by scanning or typing the courier tracking number, "
            "then continues to classify condition and restore sellable stock."
        ),
        "caption": "Figure 3.9.3: Record RTS of Pyx Tracker",
    },
    {
        "key": "pos_checkout",
        "heading": "Staff - POS Checkout",
        "box_title": "Staff Module - POS Checkout",
        "box_body": (
            "After login, Staff build a sale on Checkout by scanning products into the cart, "
            "optionally assigning Sold to, then completing cash or AR credit payment."
        ),
        "caption": "Figure 3.10.2: Staff POS Checkout of Pyx Tracker",
    },
    {
        "key": "pos_scan",
        "heading": "Staff - Scan Product",
        "box_title": "Staff Module - Scan Product",
        "box_body": (
            "Staff point the scanner or type a barcode/SKU, then tap Add to place the item in "
            "the cart for checkout."
        ),
        "caption": "Figure 3.10.3: Staff Scan Product of Pyx Tracker",
    },
]

BG = "#FFFFFF"
INK = "#1A1A1A"
MUTED = "#444444"
BOX_BG = "#F6E8D0"
BOX_BORDER = "#E0C090"
FRAME = "#CCCCCC"

font_h = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 26)
font_box_t = ImageFont.truetype(r"C:/Windows/Fonts/arialbd.ttf", 15)
font_box = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 13)
font_cap = ImageFont.truetype(r"C:/Windows/Fonts/arial.ttf", 12)


def find_shot(key: str) -> Path:
    name = SHOTS[key]
    hits = list(ASSETS.glob(f"*{name}"))
    if not hits:
        raise FileNotFoundError(name)
    return hits[0]


def wrap(draw, text, font, max_w):
    words = text.split()
    lines, cur = [], ""
    for w in words:
        trial = (cur + " " + w).strip()
        if draw.textlength(trial, font=font) <= max_w:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def make_page(spec: dict, out: Path) -> Path:
    """One storyboard page: heading + screenshot + side description + caption."""
    W, H = 1400, 820
    canvas = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(canvas)

    # Heading
    draw.text((40, 24), spec["heading"], font=font_h, fill=INK)

    # Screenshot area (left)
    shot = Image.open(find_shot(spec["key"])).convert("RGB")
    max_sw, max_sh = 820, 560
    shot.thumbnail((max_sw, max_sh), Image.Resampling.LANCZOS)
    sx, sy = 40, 70
    # frame
    draw.rectangle(
        (sx - 2, sy - 2, sx + shot.width + 2, sy + shot.height + 2),
        outline=FRAME,
        width=2,
    )
    canvas.paste(shot, (sx, sy))

    # Orange description box (right)
    bx1, by1 = 900, 70
    bx2, by2 = 1360, 70 + max(shot.height, 420)
    draw.rounded_rectangle((bx1, by1, bx2, by2), radius=6, fill=BOX_BG, outline=BOX_BORDER, width=2)
    draw.text((bx1 + 18, by1 + 18), spec["box_title"], font=font_box_t, fill=INK)
    body_lines = wrap(draw, spec["box_body"], font_box, bx2 - bx1 - 36)
    y = by1 + 52
    for line in body_lines:
        draw.text((bx1 + 18, y), line, font=font_box, fill=MUTED)
        y += 20

    # Figure caption centered under screenshot
    cap = spec["caption"]
    cw = draw.textlength(cap, font=font_cap)
    cap_y = sy + shot.height + 18
    draw.text(((sx + shot.width) / 2 + sx / 2 - cw / 2, cap_y), cap, font=font_cap, fill=INK)
    # simpler center under left panel:
    draw.rectangle((sx, cap_y, sx + shot.width, cap_y + 22), fill=BG)  # clear
    draw.text((sx + (shot.width - cw) / 2, cap_y), cap, font=font_cap, fill=INK)

    out.parent.mkdir(parents=True, exist_ok=True)
    canvas.save(out, "PNG", optimize=True)
    print(f"saved {out.name} ({out.stat().st_size})")
    return out


def set_para_text(p, text: str, ns) -> None:
    texts = p.xpath(".//w:t", namespaces=ns)
    if not texts:
        return
    texts[0].text = text
    for extra in texts[1:]:
        extra.text = ""


def patch_docx(pages: list[dict]) -> None:
    src_candidates = [
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_storyboards.docx",
        DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
        WORK / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    ]
    src = next(p for p in src_candidates if p.exists())
    with zipfile.ZipFile(src, "r") as zin:
        items = list(zin.infolist())
        blob = {i.filename: zin.read(i.filename) for i in items}

    for page in pages:
        blob[page["media"]] = page["path"].read_bytes()

    NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
    root = etree.fromstring(blob["word/document.xml"])

    intro = (
        "The following storyboards present one screen per figure in the institutional sample "
        "format (screen title, interface image, and module description). Admin/Executive screens "
        "use the Pyx Tracker web system; Staff screens use the Pyx POS terminal."
    )
    caption_map = {
        "Figure 3.6": pages[0]["caption"].replace("Figure 3.6:", "Figure 3.6 "),
        "Figure 3.7": pages[1]["caption"].replace("Figure 3.7:", "Figure 3.7 "),
        "Figure 3.8": pages[2]["caption"].replace("Figure 3.8:", "Figure 3.8 "),
        "Figure 3.9": pages[3]["caption"].replace("Figure 3.9:", "Figure 3.9 "),
        "Figure 3.10": pages[4]["caption"].replace("Figure 3.10:", "Figure 3.10 "),
    }
    # Word-style captions without extra colon quirks
    word_captions = [
        "Figure 3.6  Executive Login Page of Pyx Tracker",
        "Figure 3.7  Executive Inventory Warehouse of Pyx Tracker",
        "Figure 3.8  Executive Product Costing of Pyx Tracker",
        "Figure 3.9  Executive Online Orders of Pyx Tracker",
        "Figure 3.10  Staff POS Login Page of Pyx Tracker",
    ]
    bodies = [
        pages[0]["box_body"],
        pages[1]["box_body"],
        pages[2]["box_body"],
        pages[3]["box_body"],
        pages[4]["box_body"],
    ]

    # Walk storyboard section and rewrite captions + bodies in order
    fig_i = 0
    for p in root.xpath("//w:p", namespaces=NS):
        t = "".join(p.xpath(".//w:t/text()", namespaces=NS))
        if t.startswith("The following storyboards") or t.startswith("The following labeled"):
            set_para_text(p, intro, NS)
        if t.startswith("Figure 3.6") or t.startswith("Figure 3.7") or t.startswith("Figure 3.8") or t.startswith("Figure 3.9") or t.startswith("Figure 3.10"):
            if fig_i < len(word_captions) and "Database" not in t:
                # only caption lines (short) vs body (long)
                if len(t) < 120 or "Storyboard" in t or "Login" in t or "Inventory" in t or "Costing" in t or "Orders" in t or "Return" in t or "POS" in t or "of Pyx Tracker" in t:
                    if not t.startswith("Figure 3.6 shows") and not t.startswith("Figure 3.7 shows") and not t.startswith("Figure 3.8 shows") and not t.startswith("Figure 3.9 shows") and not t.startswith("Figure 3.10 shows"):
                        set_para_text(p, word_captions[fig_i], NS)
                        fig_i += 1
        for j, prefix in enumerate(
            (
                "Figure 3.6 shows",
                "Figure 3.7 shows",
                "Figure 3.8 shows",
                "Figure 3.9 shows",
                "Figure 3.10 shows",
            )
        ):
            if t.startswith(prefix):
                set_para_text(p, bodies[j], NS)

    blob["word/document.xml"] = etree.tostring(
        root, xml_declaration=True, encoding="UTF-8", standalone=True
    )

    outs = [
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD.docx",
        DESKTOP / "Pyx_Tracker_Capstone_SQL_ERD_storyboards.docx",
        DESKTOP / "Pyx_Tracker_Capstone_CURRENT.docx",
        WORK / "Pyx_Tracker_Capstone_SQL_ERD.docx",
    ]
    for path in outs:
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


def main() -> None:
    SB_DIR.mkdir(parents=True, exist_ok=True)
    embedded = []
    for spec in DOC_PAGES:
        path = SB_DIR / f"{spec['heading'].replace(' ', '_').replace('-', '')}.png"
        # safer filename
        safe = (
            spec["heading"]
            .replace(" ", "_")
            .replace("-", "_")
            .replace("(", "")
            .replace(")", "")
            + ".png"
        )
        path = SB_DIR / safe
        make_page(spec, path)
        spec = {**spec, "path": path}
        embedded.append(spec)

    for spec in EXTRA_PAGES:
        safe = (
            spec["heading"]
            .replace(" ", "_")
            .replace("-", "_")
            .replace("(", "")
            .replace(")", "")
            + ".png"
        )
        make_page(spec, SB_DIR / safe)

    patch_docx(embedded)
    print("storyboard dir:", SB_DIR)


if __name__ == "__main__":
    main()
