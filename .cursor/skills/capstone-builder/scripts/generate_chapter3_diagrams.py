"""Generate Pyx Tracker Chapter III diagrams as publication-ready PNG files."""

from __future__ import annotations

import sys
import textwrap
from datetime import date
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


WIDTH = 2000
BG = "#F7F9FC"
INK = "#14213D"
MUTED = "#52627A"
BORDER = "#AAB7C8"
BLUE = "#2667FF"
TEAL = "#0E9F8A"
ORANGE = "#F59E0B"
RED = "#E45756"
PURPLE = "#7C3AED"


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    filename = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / filename), size)


def wrapped(draw: ImageDraw.ImageDraw, text: str, box: tuple[int, int, int, int], size=27, bold=False,
            fill=INK, spacing=7, align="left") -> None:
    x1, y1, x2, _ = box
    average = max(size * 0.52, 1)
    width = max(int((x2 - x1) / average), 8)
    lines: list[str] = []
    for paragraph in text.split("\n"):
        lines.extend(textwrap.wrap(paragraph, width=width, break_long_words=False) or [""])
    draw.multiline_text((x1, y1), "\n".join(lines), font=font(size, bold), fill=fill, spacing=spacing, align=align)


def title(draw: ImageDraw.ImageDraw, heading: str, subtitle: str) -> None:
    draw.text((80, 55), heading, font=font(52, True), fill=INK)
    draw.text((82, 122), subtitle, font=font(25), fill=MUTED)
    draw.rounded_rectangle((80, 170, 360, 182), radius=6, fill=BLUE)


def card(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], heading: str, body: str, color: str) -> None:
    x1, y1, x2, y2 = box
    draw.rounded_rectangle(box, radius=18, fill="white", outline=BORDER, width=3)
    draw.rounded_rectangle((x1, y1, x2, y1 + 58), radius=18, fill=color)
    draw.rectangle((x1, y1 + 38, x2, y1 + 58), fill=color)
    draw.text((x1 + 22, y1 + 14), heading, font=font(27, True), fill="white")
    wrapped(draw, body, (x1 + 23, y1 + 82, x2 - 18, y2 - 18), size=23, fill=INK, spacing=9)


def business_model_canvas(path: Path) -> None:
    image = Image.new("RGB", (WIDTH, 1300), BG)
    draw = ImageDraw.Draw(image)
    title(draw, "Pyx Tracker Business Model Canvas", "Internal enterprise operations platform for Pyx Food Products")

    gap = 20
    x = [70, 390, 710, 1090, 1410, 1930]
    top, mid, bottom = 220, 735, 970

    card(draw, (x[0], top, x[1] - gap, bottom - gap), "Key Partners",
         "• Pyx Food Products management\n• Product and material suppliers\n• Shopee SPX and VIP channels\n"
         "• Courier providers\n• IT and maintenance support", RED)
    card(draw, (x[1], top, x[2] - gap, mid - gap), "Key Activities",
         "• Inventory and production\n• Order and waybill processing\n• Packing and deliveries\n"
         "• POS and credit sales\n• RTS and claims\n• Costing and reporting", ORANGE)
    card(draw, (x[1], mid, x[2] - gap, bottom - gap), "Key Resources",
         "• Personnel and process knowledge\n• Inventory and supplier data\n"
         "• Database and file storage\n• Server and network\n• Scanners and cameras", PURPLE)
    card(draw, (x[2], top, x[3] - gap, bottom - gap), "Value Proposition",
         "One role-based platform that connects production, inventory, fulfillment, sales, costing, and returns. "
         "Reduces repeated encoding, strengthens traceability, and gives management timely operational records.", BLUE)
    card(draw, (x[3], top, x[4] - gap, mid - gap), "User Relationships",
         "• Role-specific interfaces\n• Guided workflows\n• Approval requests\n• Audit histories\n"
         "• Barcode-assisted transactions\n• Operational support and training", TEAL)
    card(draw, (x[3], mid, x[4] - gap, bottom - gap), "Channels",
         "• Desktop web browser\n• Mobile browser\n• LAN/Wi-Fi access\n• Optional secured HTTPS access\n"
         "• Printed barcodes and receipts", "#27AE60")
    card(draw, (x[4], top, x[5] - gap, bottom - gap), "User Segments",
         "• Executives\n• HR personnel\n• Production managers\n• Team leaders and staff\n"
         "• CSR personnel\n• POS cashiers\n• Indirect beneficiaries: customers", RED)
    card(draw, (70, bottom, 990, 1220), "Cost Structure",
         "Server and network operation • scanners and client devices • file storage and backup • "
         "software maintenance • OCR/document-processing dependencies • user training", "#2D9CDB")
    card(draw, (1010, bottom, 1930, 1220), "Operational Value / Benefits",
         "Faster record retrieval • synchronized stock movements • fewer duplicate entries • accountable parcel evidence • "
         "traceable costs and prices • structured returns • improved management visibility", TEAL)

    draw.text((70, 1255), "Figure source: approved proposal and verified Pyx Tracker repository modules.",
              font=font(20), fill=MUTED)
    image.save(path, quality=95)


def gantt_chart(path: Path) -> None:
    image = Image.new("RGB", (WIDTH, 1250), BG)
    draw = ImageDraw.Draw(image)
    title(draw, "Pyx Tracker Development Timeline", "Evidence-based schedule derived from repository commits, May–September 2026")

    tasks = [
        ("Initial requirements, RTS, and UI foundation", date(2026, 5, 18), date(2026, 5, 29), RED),
        ("Inventory, suppliers, POS, and costing", date(2026, 5, 22), date(2026, 6, 18), ORANGE),
        ("Waybill, online orders, and deliveries", date(2026, 5, 29), date(2026, 6, 24), BLUE),
        ("RTS SPA, claims, and inventory integration", date(2026, 6, 5), date(2026, 7, 14), PURPLE),
        ("Reports, reconciliation, and historical data", date(2026, 6, 30), date(2026, 7, 25), TEAL),
        ("Barcode inventory and pack sessions", date(2026, 8, 14), date(2026, 8, 31), RED),
        ("Permissions, stock checks, and RTS review", date(2026, 8, 18), date(2026, 9, 8), BLUE),
        ("Verification and capstone documentation", date(2026, 9, 1), date(2026, 9, 9), TEAL),
    ]
    start, end = date(2026, 5, 1), date(2026, 9, 30)
    label_left, chart_left, chart_right = 70, 760, 1930
    top, row_h = 260, 100
    total_days = (end - start).days

    draw.rounded_rectangle((label_left, 215, chart_right, top + row_h * len(tasks) + 65), radius=16,
                           fill="white", outline=BORDER, width=3)
    draw.rectangle((label_left, 215, chart_right, 300), fill="#E8EEF7")
    draw.text((95, 239), "DEVELOPMENT ACTIVITY", font=font(26, True), fill=INK)

    months = [(5, "May"), (6, "June"), (7, "July"), (8, "August"), (9, "September")]
    for month, label in months:
        month_start = date(2026, month, 1)
        next_month = date(2026, month + 1, 1) if month < 9 else date(2026, 10, 1)
        x1 = chart_left + int(((month_start - start).days / total_days) * (chart_right - chart_left))
        x2 = chart_left + int(((next_month - start).days / total_days) * (chart_right - chart_left))
        draw.line((x1, 215, x1, top + row_h * len(tasks) + 65), fill=BORDER, width=2)
        draw.text(((x1 + x2) // 2, 255), label, anchor="mm", font=font(23, True), fill=INK)
    draw.line((chart_right, 215, chart_right, top + row_h * len(tasks) + 65), fill=BORDER, width=2)

    for index, (task, task_start, task_end, color) in enumerate(tasks):
        y1 = 315 + index * row_h
        y2 = y1 + 64
        draw.line((label_left, y1 + 78, chart_right, y1 + 78), fill="#D8E0EA", width=1)
        label = f"{task}\n{task_start:%b %d}–{task_end:%b %d}"
        wrapped(draw, label, (95, y1 + 3, chart_left - 30, y2 + 20), size=21, bold=True, fill=INK, spacing=4)
        x1 = chart_left + int(((task_start - start).days / total_days) * (chart_right - chart_left))
        x2 = chart_left + int(((task_end - start).days / total_days) * (chart_right - chart_left))
        draw.rounded_rectangle((x1, y1 + 8, max(x2, x1 + 16), y2), radius=12, fill=color)
        if x2 - x1 >= 170:
            draw.text(((x1 + x2) // 2, (y1 + 8 + y2) // 2),
                      f"{task_start:%b %d}–{task_end:%b %d}", anchor="mm", font=font(17, True), fill="white")

    draw.text((70, 1155), "Timeline evidence: Git commit dates. Bars summarize documented implementation activity, not a proposed future schedule.",
              font=font(20), fill=MUTED)
    image.save(path, quality=95)


def node(draw: ImageDraw.ImageDraw, center: tuple[int, int], label: str, width: int, height: int,
         color: str, size=22) -> tuple[int, int, int, int]:
    cx, cy = center
    box = (cx - width // 2, cy - height // 2, cx + width // 2, cy + height // 2)
    draw.rounded_rectangle(box, radius=14, fill="white", outline=color, width=4)
    lines = textwrap.wrap(label, width=max(int(width / (size * 0.55)), 8))
    draw.multiline_text((cx, cy), "\n".join(lines), anchor="mm", align="center",
                        font=font(size, True), fill=INK, spacing=4)
    return box


def functional_decomposition(path: Path) -> None:
    image = Image.new("RGB", (WIDTH, 1710), BG)
    draw = ImageDraw.Draw(image)
    title(draw, "Functional Decomposition Diagram", "Complete implemented feature set of Pyx Tracker")

    root_center = (1000, 245)
    node(draw, root_center, "PYX TRACKER", 430, 90, BLUE, 30)

    groups = [
        ("ACCESS & ADMINISTRATION", [
            "Authentication & Role-Based Permissions",
            "Dashboard & Notifications",
            "Staff & Department Management",
            "Supplier Management",
            "Settings, Requests & Approvals",
        ]),
        ("INVENTORY & PRODUCTION", [
            "Honey Warehouse, Production & Packaging",
            "Department Raw, WIP & Finished Production",
            "Packaging Materials & Supplies",
            "SKU & Unique Barcode Management",
            "Inventory Check, Variance & Leak/Damage",
        ]),
        ("COSTING & PRICING", [
            "Supplier Honey & Department Prices",
            "Product Formula, COGS & Selling Price",
            "Parcel Packaging Tiers & Recipes",
            "Landed Cost & Margin Preview",
            "Bulk Honey Order Costing",
        ]),
        ("ORDERS & FULFILLMENT", [
            "VIP & Shopee SPX Order Preparation",
            "PDF-to-Excel & Waybill Stamping",
            "Online Parcel Registry",
            "Product Pack Scan & Deliveries",
            "Packing Staging, Hold, Cancel & Re-waybill",
        ]),
        ("SALES & FINANCE", [
            "Unified Barcode Point of Sale",
            "Receipts & Thermal Printing",
            "Discount Approval",
            "Accounts Receivable Credits",
            "Sales History & Analytics",
        ]),
        ("RTS, REPORTS & AUDIT", [
            "RTS Cases, Evidence & Claims",
            "RTS Sellable/Damaged Inventory Pools",
            "RTS Stock-in Review, Labels & Use Events",
            "Operational Reports & Reconciliation",
            "Historical Import, Corrections & Audit Logs",
        ]),
    ]
    group_centers = [
        (360, 500), (1000, 500), (1640, 500),
        (360, 1110), (1000, 1110), (1640, 1110),
    ]
    root_bottom = root_center[1] + 45
    top_rail_y = 350
    bottom_rail_y = 950
    draw.line((1000, root_bottom, 1000, bottom_rail_y), fill=BORDER, width=4)
    draw.line((360, top_rail_y, 1640, top_rail_y), fill=BORDER, width=4)
    draw.line((360, bottom_rail_y, 1640, bottom_rail_y), fill=BORDER, width=4)

    for index, ((gx, gy), (group, children)) in enumerate(zip(group_centers, groups)):
        rail_y = top_rail_y if index < 3 else bottom_rail_y
        draw.line((gx, rail_y, gx, gy - 56), fill=BORDER, width=4)
        group_box = (gx - 280, gy - 56, gx + 280, gy + 500)
        draw.rounded_rectangle(group_box, radius=18, fill="white", outline=BLUE, width=4)
        draw.rounded_rectangle((group_box[0], group_box[1], group_box[2], group_box[1] + 78),
                               radius=18, fill=BLUE)
        draw.rectangle((group_box[0], group_box[1] + 55, group_box[2], group_box[1] + 78), fill=BLUE)
        draw.text((gx, group_box[1] + 39), group, anchor="mm", font=font(23, True), fill="white")
        for child_index, child in enumerate(children):
            y1 = group_box[1] + 98 + child_index * 84
            child_box = (group_box[0] + 20, y1, group_box[2] - 20, y1 + 65)
            color = [RED, ORANGE, TEAL, PURPLE, "#2D9CDB"][child_index]
            draw.rounded_rectangle(child_box, radius=12, fill="#FBFCFE", outline=color, width=3)
            draw.multiline_text(
                (gx, y1 + 32),
                "\n".join(textwrap.wrap(child, width=38)),
                anchor="mm",
                align="center",
                font=font(19, True),
                fill=INK,
                spacing=2,
            )

    draw.text((70, 1660), "Figure source: approved scope, SPA routes, APIs, and verified implemented repository modules.",
              font=font(20), fill=MUTED)
    image.save(path, quality=95)


def actor(draw: ImageDraw.ImageDraw, center: tuple[int, int], label: str) -> tuple[int, int]:
    x, y = center
    draw.ellipse((x - 18, y - 70, x + 18, y - 34), outline=INK, width=4)
    draw.line((x, y - 34, x, y + 30), fill=INK, width=4)
    draw.line((x - 35, y - 5, x + 35, y - 5), fill=INK, width=4)
    draw.line((x, y + 30, x - 32, y + 78), fill=INK, width=4)
    draw.line((x, y + 30, x + 32, y + 78), fill=INK, width=4)
    draw.multiline_text((x, y + 95), label, anchor="ma", align="center", font=font(21, True), fill=INK)
    return center


def use_case(draw: ImageDraw.ImageDraw, center: tuple[int, int], label: str, color: str) -> tuple[int, int]:
    x, y = center
    box = (x - 205, y - 46, x + 205, y + 46)
    draw.ellipse(box, fill="white", outline=color, width=4)
    draw.multiline_text((x, y), "\n".join(textwrap.wrap(label, 28)), anchor="mm", align="center",
                        font=font(21, True), fill=INK, spacing=3)
    return center


def use_case_diagram(path: Path) -> None:
    image = Image.new("RGB", (WIDTH, 1450), BG)
    draw = ImageDraw.Draw(image)
    title(draw, "Use Case Diagram", "Primary actors and major Pyx Tracker interactions")

    boundary = (430, 220, 1570, 1360)
    draw.rounded_rectangle(boundary, radius=25, fill="white", outline=INK, width=4)
    draw.text((1000, 255), "PYX TRACKER SYSTEM BOUNDARY", anchor="mm", font=font(28, True), fill=INK)

    cases = [
        ((760, 390), "Manage users, permissions, suppliers, and settings", BLUE),
        ((1240, 390), "Monitor dashboards, reports, and costing", PURPLE),
        ((760, 600), "Manage inventory and production", ORANGE),
        ((1240, 600), "Prepare orders and stamp waybills", BLUE),
        ((760, 810), "Pack, document, and ship parcels", TEAL),
        ((1240, 810), "Process POS sales and AR", RED),
        ((760, 1020), "Record RTS cases and claims", PURPLE),
        ((1240, 1020), "Review RTS stock-in and inventory pools", ORANGE),
        ((1000, 1220), "Reconcile records and audit activity", TEAL),
    ]
    case_centers = [use_case(draw, *item) for item in cases]

    actors = {
        "Executive": actor(draw, (200, 420), "Executive"),
        "HR": actor(draw, (200, 770), "HR Personnel"),
        "Production": actor(draw, (200, 1120), "Production\nManager / Staff"),
        "CSR": actor(draw, (1800, 420), "CSR TL / Staff"),
        "POS": actor(draw, (1800, 770), "POS Cashier"),
        "RTS": actor(draw, (1800, 1120), "RTS-Authorized\nUser"),
    }

    links = {
        "Executive": [0, 1, 2, 4, 5, 6, 7, 8],
        "HR": [0, 2, 8],
        "Production": [2, 4, 6, 7, 8],
        "CSR": [3, 6, 8],
        "POS": [5, 8],
        "RTS": [6, 7, 8],
    }
    for actor_name, indexes in links.items():
        ax, ay = actors[actor_name]
        edge_x = boundary[0] if ax < WIDTH // 2 else boundary[2]
        for index in indexes:
            cx, cy = case_centers[index]
            target_x = cx - 205 if ax < WIDTH // 2 else cx + 205
            draw.line((ax + (45 if ax < WIDTH // 2 else -45), ay, edge_x, cy, target_x, cy),
                      fill="#8A98AA", width=2)

    draw.text((70, 1390), "Associations indicate authorized interaction; exact actions remain subject to granular permissions.",
              font=font(20), fill=MUTED)
    image.save(path, quality=95)


def main() -> None:
    output = Path(sys.argv[1] if len(sys.argv) > 1 else r"C:\Users\Admin\Desktop\capstone\assets")
    output.mkdir(parents=True, exist_ok=True)
    business_model_canvas(output / "business_model_canvas.png")
    gantt_chart(output / "gantt_chart.png")
    functional_decomposition(output / "functional_decomposition.png")
    use_case_diagram(output / "use_case_diagram.png")
    print(f"Generated Chapter III diagrams in {output}")


if __name__ == "__main__":
    main()
