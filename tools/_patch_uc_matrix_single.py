"""Consolidate Use Case Matrix into a single table. Save to Desktop/capstone."""

from __future__ import annotations

import shutil
from pathlib import Path

from docx import Document
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Pt, RGBColor

SRC_CANDIDATES = [
    Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new"),
]
DESK = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx")
MAIN = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx")
NEW = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new")

AUTHOR = "Cayunda, Klint Russel R.; Delos Santos, Brent R.; Fin, Noli III M."

# Columns: Use Case | UC01 | UC02 | UC03 | UC04 | UC05
HEADERS = [
    "Use Case",
    "UC01 Inventory stock movement",
    "UC02 Product and parcel costing",
    "UC03 Order preparation and finalization",
    "UC04 Before-shipment documentation",
    "UC05 RTS case and sellable stock-in",
]

ROWS = [
    (
        "Author",
        AUTHOR,
        AUTHOR,
        AUTHOR,
        AUTHOR,
        AUTHOR,
    ),
    (
        "Primary Actor",
        "Admin/Executive and Staff",
        "Admin/Executive",
        "Admin/Executive and Staff",
        "Admin/Executive and Staff",
        "Admin/Executive and Staff",
    ),
    (
        "Trigger",
        "Material receipt or finished-stock operation",
        "Review or update of product costs",
        "Receipt of supported order files",
        "Parcel presented for photo recording",
        "Returned parcel received",
    ),
    (
        "Preconditions",
        "Valid product, stock unit, permission and source record.",
        "Configured material prices, units and formulas.",
        "Supported source files and an active processing batch.",
        "Readable or manually entered tracking identifier and an image.",
        "Tracking or case information, item condition and readable product codes.",
    ),
    (
        "Basic Flow",
        "1. Select category and product.\n2. Enter or scan the movement.\n3. Review quantity and source.\n4. Confirm the allowed operation.",
        "1. Select product or tier.\n2. Review quantities, prices and yield.\n3. Maintain formula lines.\n4. Review calculated cost and price.",
        "1. Choose preparation tool.\n2. Upload and review data.\n3. Run separate PDF/Excel stamper.\n4. Download results and finalize batch.",
        "1. Scan or type tracking.\n2. Capture or upload photo.\n3. Review and save.\n4. Continue to next record.",
        "1. Record case and condition.\n2. Open stock-in session.\n3. Scan received sellable goods.\n4. Review and confirm session.",
    ),
    (
        "Postcondition",
        "The relevant balance and movement record reflect the confirmed quantity.",
        "The cost is reproducible from the stored inputs.",
        "Prepared outputs and finalized order/parcel records are available.",
        "Photo is retrievable by tracking number; stock is unchanged.",
        "Case history is retained; confirmed sellable quantity is credited once.",
    ),
    (
        "Exceptions",
        "Reject invalid input; preserve balances when an unconfirmed action is cancelled.",
        "Deny non-executive access; identify missing configuration before relying on the result.",
        "Report duplicates, unmatched records or missing fields; do not describe partial preparation as finalization.",
        "Provide duplicate feedback and request valid image/tracking input. A CSR parcel is not required.",
        "Case save alone adds no sellable stock; discarding a draft leaves inventory unchanged.",
    ),
]


def set_cell_shading(cell, hex_color: str) -> None:
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), hex_color)
    shd.set(qn("w:val"), "clear")
    tcPr.append(shd)


def set_cell_text(cell, text: str, *, bold: bool = False, white: bool = False, size: int = 9) -> None:
    # clear paragraphs
    for p in cell.paragraphs[1:]:
        p._element.getparent().remove(p._element)
    p = cell.paragraphs[0]
    for r in p.runs:
        r.text = ""
    if p.runs:
        run = p.runs[0]
        run.text = text
    else:
        run = p.add_run(text)
    run.bold = bold
    run.font.size = Pt(size)
    run.font.name = "Times New Roman"
    run._element.rPr.rFonts.set(qn("w:ascii"), "Times New Roman")
    run._element.rPr.rFonts.set(qn("w:hAnsi"), "Times New Roman")
    if white:
        run.font.color.rgb = RGBColor(255, 255, 255)


def build_tbl():
    tbl = OxmlElement("w:tbl")
    tblPr = OxmlElement("w:tblPr")
    tblW = OxmlElement("w:tblW")
    tblW.set(qn("w:w"), "5000")
    tblW.set(qn("w:type"), "pct")
    tblPr.append(tblW)
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        el = OxmlElement(f"w:{edge}")
        el.set(qn("w:val"), "single")
        el.set(qn("w:sz"), "4")
        el.set(qn("w:space"), "0")
        el.set(qn("w:color"), "000000")
        borders.append(el)
    tblPr.append(borders)
    tbl.append(tblPr)

    grid = OxmlElement("w:tblGrid")
    widths = ["1200", "760", "760", "760", "760", "760"]
    for w in widths:
        gc = OxmlElement("w:gridCol")
        gc.set(qn("w:w"), w)
        grid.append(gc)
    tbl.append(grid)
    return tbl


def add_row(tbl, cells: list[str], *, header: bool = False, alt: bool = False) -> None:
    tr = OxmlElement("w:tr")
    fill = "2F80ED" if header else ("D6EAF8" if alt else "FFFFFF")
    for i, text in enumerate(cells):
        tc = OxmlElement("w:tc")
        tcPr = OxmlElement("w:tcPr")
        tcW = OxmlElement("w:tcW")
        tcW.set(qn("w:w"), "1200" if i == 0 else "760")
        tcW.set(qn("w:type"), "dxa")
        tcPr.append(tcW)
        shd = OxmlElement("w:shd")
        shd.set(qn("w:fill"), fill)
        shd.set(qn("w:val"), "clear")
        tcPr.append(shd)
        tc.append(tcPr)
        p = OxmlElement("w:p")
        r = OxmlElement("w:r")
        rPr = OxmlElement("w:rPr")
        rf = OxmlElement("w:rFonts")
        rf.set(qn("w:ascii"), "Times New Roman")
        rf.set(qn("w:hAnsi"), "Times New Roman")
        rPr.append(rf)
        sz = OxmlElement("w:sz")
        sz.set(qn("w:val"), "16" if not header else "16")  # 8pt
        rPr.append(sz)
        if header or i == 0:
            rPr.append(OxmlElement("w:b"))
        if header:
            color = OxmlElement("w:color")
            color.set(qn("w:val"), "FFFFFF")
            rPr.append(color)
        r.append(rPr)
        t = OxmlElement("w:t")
        t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
        t.text = text
        r.append(t)
        p.append(r)
        tc.append(p)
        tr.append(tc)
    tbl.append(tr)


def set_para(p, text: str) -> None:
    for r in p.runs:
        r.text = ""
    if p.runs:
        p.runs[0].text = text
    else:
        p.add_run(text)


def main() -> None:
    src = next(p for p in SRC_CANDIDATES if p.exists())
    d = Document(str(src))
    print("opened", src)

    # First 5 tables are UC matrices
    first_tbl = d.tables[0]._tbl
    parent = first_tbl.getparent()
    insert_at = parent.index(first_tbl)

    # Remove tables 0..4 (UC matrices) — remove from last to first by element
    uc_tbls = [d.tables[i]._tbl for i in range(5)]
    for t in uc_tbls:
        parent = t.getparent()
        parent.remove(t)

    # Build combined matrix
    new_tbl = build_tbl()
    add_row(new_tbl, HEADERS, header=True)
    for i, row in enumerate(ROWS):
        add_row(new_tbl, list(row), alt=(i % 2 == 1))

    # Insert at original position of first UC table
    # After removals, insert_at may still be valid if we captured index before remove
    # Re-find: insert after "Use Case Matrix" heading paragraph
    body = d.element.body
    # find paragraph Use Case Matrix then insert table after following caption para
    inserted = False
    for p in d.paragraphs:
        if p.text.strip() == "Use Case Matrix":
            # next non-empty is often Table 3.1 caption
            el = p._element
            # look ahead for Table 3.1 caption
            nxt = el.getnext()
            while nxt is not None and nxt.tag != qn("w:p"):
                nxt = nxt.getnext()
            # skip empty paragraphs until caption or insert after heading
            target = el
            probe = el.getnext()
            while probe is not None:
                if probe.tag == qn("w:p"):
                    texts = [t.text or "" for t in probe.findall(".//" + qn("w:t"))]
                    joined = "".join(texts).strip()
                    if joined.startswith("Table 3.1"):
                        target = probe
                        break
                    if joined.startswith("Table 3.2") or joined.startswith("Storyboard"):
                        break
                probe = probe.getnext()
            target.addnext(new_tbl)
            inserted = True
            break
    if not inserted:
        body.append(new_tbl)

    # Captions: keep one Table 3.1; remove 3.2-3.5 UC matrix captions
    for p in d.paragraphs:
        t = p.text.strip()
        if t.startswith("Table 3.1  Use Case Matrix"):
            set_para(
                p,
                "Table 3.1  Use Case Matrix of Pyx Tracker: Web-based Enterprise Operations Management System.",
            )
        if t.startswith("Table 3.2  Use Case Matrix") or t.startswith("Table 3.3  Use Case Matrix") or t.startswith(
            "Table 3.4  Use Case Matrix"
        ) or t.startswith("Table 3.5  Use Case Matrix"):
            # clear caption paragraphs (leave empty to avoid shifting complex numbering elsewhere)
            set_para(p, "")

    DESK.parent.mkdir(parents=True, exist_ok=True)
    out_tmp = DESK.parent / "_uc_matrix_out.docx"
    d.save(str(out_tmp))

    # verify
    v = Document(str(out_tmp))
    uc_like = 0
    for table in v.tables:
        head = " | ".join(c.text.strip()[:30] for c in table.rows[0].cells)
        if "UC01" in head and "UC02" in head:
            uc_like += 1
            print("COMBINED", len(table.rows), "x", len(table.columns))
            print("header:", head[:120])
        if table.rows[0].cells[0].text.strip() == "Element" and "Specification" in table.rows[0].cells[1].text:
            print("OLD STYLE STILL PRESENT")
    print("combined count", uc_like)
    caps = [p.text.strip() for p in v.paragraphs if "Use Case Matrix" in p.text]
    print("captions:", caps)

    for dest in (DESK, MAIN, NEW):
        try:
            shutil.copy2(out_tmp, dest)
            print("saved", dest, dest.stat().st_size)
        except Exception as e:
            print("LOCKED", dest, e)
    out_tmp.unlink(missing_ok=True)


if __name__ == "__main__":
    main()
