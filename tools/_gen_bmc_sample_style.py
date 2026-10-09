"""Generate Pyx Tracker Business Model Canvas in S.I.G.N.-sample sticky-note style."""
from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

OUT_PNG = Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Business_Model_Canvas.png")
OUT_WORK = Path(r"C:\xampp\htdocs\rts\output\documents\_bmc_pyx_tracker.png")

# Sample palette
GRID = "#4A90C8"
HEADER = "#333333"
TITLE = "#1A1A1A"
BG = "#FFFFFF"
BLUE = "#5BA3D9"
NAVY = "#3D5A80"
RED = "#E85A6B"
ICON = "#8A8A8A"

W, H = 2000, 1320


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    name = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / name), size)


def wrap_lines(text: str, f: ImageFont.ImageFont, max_w: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    cur = ""
    for word in words:
        trial = f"{cur} {word}".strip()
        if f.getlength(trial) <= max_w:
            cur = trial
        else:
            if cur:
                lines.append(cur)
            cur = word
    if cur:
        lines.append(cur)
    return lines or [""]


def sticky(
    draw: ImageDraw.ImageDraw,
    box: tuple[int, int, int, int],
    text: str,
    fill: str,
    size: int = 22,
) -> None:
    x1, y1, x2, y2 = box
    draw.rounded_rectangle(box, radius=10, fill=fill)
    f = font(size, True)
    pad = 12
    lines = wrap_lines(text, f, max(40, x2 - x1 - 2 * pad))
    # vertical center
    line_h = size + 4
    total_h = line_h * len(lines)
    ty = y1 + max(pad, (y2 - y1 - total_h) // 2)
    for line in lines:
        tw = f.getlength(line)
        tx = x1 + (x2 - x1 - tw) / 2
        draw.text((tx, ty), line, font=f, fill="white")
        ty += line_h


def section_header(
    draw: ImageDraw.ImageDraw,
    x: int,
    y: int,
    label: str,
    icon: str,
) -> None:
    # simple geometric icon
    ix, iy = x + 8, y + 4
    if icon == "partners":
        draw.ellipse((ix, iy, ix + 14, iy + 14), outline=ICON, width=2)
        draw.ellipse((ix + 10, iy + 4, ix + 24, iy + 18), outline=ICON, width=2)
    elif icon == "activities":
        draw.rectangle((ix + 2, iy + 2, ix + 20, iy + 16), outline=ICON, width=2)
        draw.line((ix + 6, iy + 8, ix + 16, iy + 8), fill=ICON, width=2)
    elif icon == "resources":
        draw.polygon([(ix + 11, iy), (ix + 22, iy + 9), (ix + 11, iy + 18), (ix, iy + 9)], outline=ICON)
    elif icon == "value":
        draw.polygon([(ix + 11, iy), (ix + 22, iy + 9), (ix + 11, iy + 18), (ix, iy + 9)], outline=ICON, width=2)
    elif icon == "rel":
        draw.ellipse((ix, iy + 2, ix + 12, iy + 14), outline=ICON, width=2)
        draw.ellipse((ix + 10, iy + 2, ix + 22, iy + 14), outline=ICON, width=2)
    elif icon == "channels":
        draw.polygon([(ix, iy + 8), (ix + 10, iy), (ix + 10, iy + 5), (ix + 22, iy + 5),
                      (ix + 22, iy + 11), (ix + 10, iy + 11), (ix + 10, iy + 16)], outline=ICON)
    elif icon == "segments":
        draw.ellipse((ix + 5, iy, ix + 17, iy + 12), outline=ICON, width=2)
        draw.arc((ix, iy + 8, ix + 22, iy + 20), 200, 340, fill=ICON, width=2)
    elif icon == "cost":
        draw.ellipse((ix + 2, iy, ix + 20, iy + 18), outline=ICON, width=2)
        draw.text((ix + 7, iy + 1), "$", font=font(14, True), fill=ICON)
    elif icon == "revenue":
        draw.rectangle((ix + 4, iy + 2, ix + 18, iy + 16), outline=ICON, width=2)
        draw.line((ix + 4, iy + 8, ix + 18, iy + 8), fill=ICON, width=2)

    draw.text((x + 34, y), label, font=font(22, True), fill=HEADER)


def stack_stickies(
    draw: ImageDraw.ImageDraw,
    area: tuple[int, int, int, int],
    items: list[tuple[str, str]],
    gap: int = 10,
) -> None:
    x1, y1, x2, y2 = area
    n = len(items)
    if n == 0:
        return
    usable = y2 - y1 - gap * (n - 1)
    h = usable // n
    y = y1
    for text, color in items:
        sticky(draw, (x1, y, x2, y + h), text, color, size=20 if h < 70 else 22)
        y += h + gap


def row_stickies(
    draw: ImageDraw.ImageDraw,
    area: tuple[int, int, int, int],
    items: list[tuple[str, str]],
    gap: int = 12,
) -> None:
    x1, y1, x2, y2 = area
    n = len(items)
    usable = x2 - x1 - gap * (n - 1)
    w = usable // n
    x = x1
    for text, color in items:
        sticky(draw, (x, y1, x + w, y2), text, color, size=20)
        x += w + gap


def draw_bmc(path: Path) -> None:
    img = Image.new("RGB", (W, H), BG)
    draw = ImageDraw.Draw(img)

    # Title
    title = "Business Model Canvas"
    tf = font(44, True)
    tw = tf.getlength(title)
    draw.text(((W - tw) / 2, 28), title, font=tf, fill=TITLE)

    # Outer grid
    left, right = 40, W - 40
    top, bottom = 100, H - 40
    mid_y = 880  # split upper / lower
    # column x boundaries (5 upper columns)
    # proportions similar to classic BMC: KP | KA/KR | VP | CR/CH | CS
    xs = [left, 340, 640, 1100, 1460, right]
    # horizontal split for KA/KR and CR/CH
    split_y = 490

    # Draw grid lines
    draw.rectangle((left, top, right, bottom), outline=GRID, width=3)
    # verticals
    for x in xs[1:-1]:
        draw.line((x, top, x, mid_y), fill=GRID, width=3)
    # mid horizontal full
    draw.line((left, mid_y, right, mid_y), fill=GRID, width=3)
    # KA/KR split
    draw.line((xs[1], split_y, xs[2], split_y), fill=GRID, width=3)
    # CR/CH split
    draw.line((xs[3], split_y, xs[4], split_y), fill=GRID, width=3)
    # Cost / Revenue vertical
    mid_x = (left + right) // 2
    draw.line((mid_x, mid_y, mid_x, bottom), fill=GRID, width=3)

    pad = 14
    header_h = 36

    # --- Key Partners ---
    section_header(draw, xs[0] + 8, top + 10, "Key Partners", "partners")
    stack_stickies(
        draw,
        (xs[0] + pad, top + header_h + 16, xs[1] - pad, mid_y - pad),
        [
            ("Product & packaging suppliers", BLUE),
            ("Shopee SPX / VIP channels", BLUE),
            ("Courier providers", BLUE),
            ("IT support", BLUE),
        ],
    )

    # --- Key Activities ---
    section_header(draw, xs[1] + 8, top + 10, "Key Activities", "activities")
    stack_stickies(
        draw,
        (xs[1] + pad, top + header_h + 16, xs[2] - pad, split_y - pad),
        [
            ("Research and System Development", BLUE),
            ("Inventory, orders & fulfillment", BLUE),
            ("System Maintenance", BLUE),
            ("User Testing and Feedback", BLUE),
        ],
    )

    # --- Key Resources ---
    section_header(draw, xs[1] + 8, split_y + 10, "Key Resources", "resources")
    stack_stickies(
        draw,
        (xs[1] + pad, split_y + header_h + 16, xs[2] - pad, mid_y - pad),
        [
            ("Human Resources", BLUE),
            ("Financial Resources", BLUE),
            ("Technology Resources", BLUE),
        ],
    )

    # --- Value Propositions ---
    section_header(draw, xs[2] + 8, top + 10, "Value Propositions", "value")
    stack_stickies(
        draw,
        (xs[2] + pad, top + header_h + 16, xs[3] - pad, mid_y - pad),
        [
            ("Real-time inventory synchronization", NAVY),
            ("Multi-platform order & waybill processing", RED),
            ("Parcel scan, deduction & shipment proof", NAVY),
            ("Product costing & pricing calculator", RED),
            ("Integrated POS and RTS management", NAVY),
        ],
        gap=8,
    )

    # --- Customer Relationships ---
    section_header(draw, xs[3] + 8, top + 10, "Customer Relationships", "rel")
    stack_stickies(
        draw,
        (xs[3] + pad, top + header_h + 16, xs[4] - pad, split_y - pad),
        [
            ("Assisted Self-Service", BLUE),
            ("Role-based guided workflows", BLUE),
        ],
    )

    # --- Channels ---
    section_header(draw, xs[3] + 8, split_y + 10, "Channels", "channels")
    stack_stickies(
        draw,
        (xs[3] + pad, split_y + header_h + 16, xs[4] - pad, mid_y - pad),
        [
            ("Web Application (Browser)", BLUE),
            ("Walk-in POS / Mobile POS", BLUE),
        ],
    )

    # --- Customer Segments ---
    section_header(draw, xs[4] + 8, top + 10, "Customer Segments", "segments")
    stack_stickies(
        draw,
        (xs[4] + pad, top + header_h + 16, xs[5] - pad, mid_y - pad),
        [
            ("Online and walk-in customers", NAVY),
            ("Executives and HR personnel", RED),
            ("Staff / CSR / Cashiers", NAVY),
        ],
    )

    # --- Cost Structure ---
    section_header(draw, left + 8, mid_y + 12, "Cost Structure", "cost")
    row_stickies(
        draw,
        (left + pad, mid_y + header_h + 28, mid_x - pad, bottom - pad),
        [
            ("Development costs (Software)", BLUE),
            ("Internet / network", BLUE),
            ("Devices & scanners", BLUE),
        ],
    )

    # --- Revenue Streams ---
    section_header(draw, mid_x + 8, mid_y + 12, "Revenue Streams", "revenue")
    row_stickies(
        draw,
        (mid_x + pad, mid_y + header_h + 28, right - pad, bottom - pad),
        [
            ("Online product sales", BLUE),
            ("Walk-in retail sales", BLUE),
            ("Collection of receivables", BLUE),
        ],
    )

    path.parent.mkdir(parents=True, exist_ok=True)
    img.save(path, "PNG", optimize=True)
    print(f"saved {path} ({path.stat().st_size} bytes)")


if __name__ == "__main__":
    draw_bmc(OUT_PNG)
    draw_bmc(OUT_WORK)
