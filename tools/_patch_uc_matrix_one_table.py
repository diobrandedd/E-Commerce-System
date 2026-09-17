"""Force a single continuous Use Case Matrix table (2-column sample style)."""

from __future__ import annotations

import shutil
from pathlib import Path

from docx import Document
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

DESK = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx")
MAIN = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx")
NEW = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new")

AUTHOR = "Cayunda, Klint Russel R.; Delos Santos, Brent R.; Fin, Noli III M."

# One continuous table: Use Case | Specification (all UCs stacked)
BLOCKS = [
    {
        "title": "UC01 Inventory stock movement",
        "rows": [
            ("Author", AUTHOR),
            ("Primary Actor", "Admin/Executive and Staff"),
            ("Trigger", "Material receipt or finished-stock operation"),
            ("Preconditions", "Valid product, stock unit, permission and source record."),
            (
                "Basic Flow",
                "1. Select category and product.\n"
                "2. Enter or scan the movement.\n"
                "3. Review quantity and source.\n"
                "4. Confirm the allowed operation.",
            ),
            ("Postcondition", "The relevant balance and movement record reflect the confirmed quantity."),
            (
                "Exceptions",
                "Reject invalid input; preserve balances when an unconfirmed action is cancelled.",
            ),
        ],
    },
    {
        "title": "UC02 Product and parcel costing",
        "rows": [
            ("Author", AUTHOR),
            ("Primary Actor", "Admin/Executive"),
            ("Trigger", "Review or update of product costs"),
            ("Preconditions", "Configured material prices, units and formulas."),
            (
                "Basic Flow",
                "1. Select product or tier.\n"
                "2. Review quantities, prices and yield.\n"
                "3. Maintain formula lines.\n"
                "4. Review calculated cost and price.",
            ),
            ("Postcondition", "The cost is reproducible from the stored inputs."),
            (
                "Exceptions",
                "Deny non-executive access; identify missing configuration before relying on the result.",
            ),
        ],
    },
    {
        "title": "UC03 Order preparation and finalization",
        "rows": [
            ("Author", AUTHOR),
            ("Primary Actor", "Admin/Executive and Staff"),
            ("Trigger", "Receipt of supported order files"),
            ("Preconditions", "Supported source files and an active processing batch."),
            (
                "Basic Flow",
                "1. Choose preparation tool.\n"
                "2. Upload and review data.\n"
                "3. Run separate PDF/Excel stamper.\n"
                "4. Download results and finalize batch.",
            ),
            ("Postcondition", "Prepared outputs and finalized order/parcel records are available."),
            (
                "Exceptions",
                "Report duplicates, unmatched records or missing fields; do not describe partial preparation as finalization.",
            ),
        ],
    },
    {
        "title": "UC04 Before-shipment documentation",
        "rows": [
            ("Author", AUTHOR),
            ("Primary Actor", "Admin/Executive and Staff"),
            ("Trigger", "Parcel presented for photo recording"),
            ("Preconditions", "Readable or manually entered tracking identifier and an image."),
            (
                "Basic Flow",
                "1. Scan or type tracking.\n"
                "2. Capture or upload photo.\n"
                "3. Review and save.\n"
                "4. Continue to next record.",
            ),
            ("Postcondition", "Photo is retrievable by tracking number; stock is unchanged."),
            (
                "Exceptions",
                "Provide duplicate feedback and request valid image/tracking input. A CSR parcel is not required.",
            ),
        ],
    },
    {
        "title": "UC05 RTS case and sellable stock-in",
        "rows": [
            ("Author", AUTHOR),
            ("Primary Actor", "Admin/Executive and Staff"),
            ("Trigger", "Returned parcel received"),
            ("Preconditions", "Tracking or case information, item condition and readable product codes."),
            (
                "Basic Flow",
                "1. Record case and condition.\n"
                "2. Open stock-in session.\n"
                "3. Scan received sellable goods.\n"
                "4. Review and confirm session.",
            ),
            ("Postcondition", "Case history is retained; confirmed sellable quantity is credited once."),
            (
                "Exceptions",
                "Case save alone adds no sellable stock; discarding a draft leaves inventory unchanged.",
            ),
        ],
    },
]


def _tc(text: str, *, bold: bool = False, fill: str | None = None, white: bool = False, width: str = "2500"):
    tc = OxmlElement("w:tc")
    tcPr = OxmlElement("w:tcPr")
    tcW = OxmlElement("w:tcW")
    tcW.set(qn("w:w"), width)
    tcW.set(qn("w:type"), "dxa")
    tcPr.append(tcW)
    if fill:
        shd = OxmlElement("w:shd")
        shd.set(qn("w:fill"), fill)
        shd.set(qn("w:val"), "clear")
        tcPr.append(shd)
    tc.append(tcPr)

    # support multiline
    first = True
    for line in text.split("\n"):
        p = OxmlElement("w:p")
        r = OxmlElement("w:r")
        rPr = OxmlElement("w:rPr")
        rf = OxmlElement("w:rFonts")
        rf.set(qn("w:ascii"), "Times New Roman")
        rf.set(qn("w:hAnsi"), "Times New Roman")
        rPr.append(rf)
        sz = OxmlElement("w:sz")
        sz.set(qn("w:val"), "20")  # 10pt
        rPr.append(sz)
        if bold:
            rPr.append(OxmlElement("w:b"))
        if white:
            c = OxmlElement("w:color")
            c.set(qn("w:val"), "FFFFFF")
            rPr.append(c)
        r.append(rPr)
        t = OxmlElement("w:t")
        t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
        t.text = line
        r.append(t)
        p.append(r)
        tc.append(p)
        first = False
    if text == "":
        p = OxmlElement("w:p")
        tc.append(p)
    return tc


def build_single_table():
    tbl = OxmlElement("w:tbl")
    tblPr = OxmlElement("w:tblPr")
    tblW = OxmlElement("w:tblW")
    tblW.set(qn("w:w"), "5000")
    tblW.set(qn("w:type"), "pct")
    tblPr.append(tblW)
    # keep rows together preference
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
    for w in ("2000", "3000"):
        gc = OxmlElement("w:gridCol")
        gc.set(qn("w:w"), w)
        grid.append(gc)
    tbl.append(grid)

    # Header like sample: Use Case | Pyx Tracker (single matrix title)
    tr = OxmlElement("w:tr")
    tr.append(_tc("Use Case", bold=True, fill="2F80ED", white=True, width="2000"))
    tr.append(_tc("Pyx Tracker", bold=True, fill="2F80ED", white=True, width="3000"))
    tbl.append(tr)

    alt = False
    for block in BLOCKS:
        # section title row (use case name spans as left label + right name)
        tr = OxmlElement("w:tr")
        tr.append(_tc("Use case", bold=True, fill="D6EAF8", width="2000"))
        tr.append(_tc(block["title"], bold=True, fill="D6EAF8", width="3000"))
        tbl.append(tr)
        for left, right in block["rows"]:
            fill = "EEF6FC" if alt else "FFFFFF"
            tr = OxmlElement("w:tr")
            tr.append(_tc(left, bold=True, fill=fill, width="2000"))
            tr.append(_tc(right, fill=fill, width="3000"))
            tbl.append(tr)
            alt = not alt
    return tbl


def is_uc_matrix_table(table) -> bool:
    if len(table.columns) < 2:
        return False
    h0 = table.rows[0].cells[0].text.strip().lower()
    h1 = table.rows[0].cells[1].text.strip().lower()
    blob = " ".join(c.text for r in table.rows[:3] for c in r.cells).lower()
    if "uc01" in blob or "inventory stock movement" in blob:
        return True
    if h0 in ("use case", "element") and ("specification" in h1 or "pyx tracker" in h1 or "uc01" in h1):
        return True
    if "primary actor" in blob and "basic flow" in blob:
        return True
    return False


def set_para(p, text: str) -> None:
    for r in p.runs:
        r.text = ""
    if p.runs:
        p.runs[0].text = text
    else:
        p.add_run(text)


def process(src: Path) -> Path:
    d = Document(str(src))
    # remove ALL existing UC matrix tables
    removed = 0
    for table in list(d.tables):
        if is_uc_matrix_table(table):
            el = table._tbl
            el.getparent().remove(el)
            removed += 1
    print("removed UC tables:", removed)

    new_tbl = build_single_table()
    placed = False
    for p in d.paragraphs:
        if p.text.strip() == "Use Case Matrix":
            # place after Table 3.1 caption if present
            target = p._element
            probe = target.getnext()
            while probe is not None:
                if probe.tag == qn("w:p"):
                    texts = "".join((t.text or "") for t in probe.findall(".//" + qn("w:t"))).strip()
                    if texts.startswith("Table 3.1"):
                        target = probe
                        break
                    if texts.startswith("Storyboard") or texts.startswith("Table 3."):
                        break
                probe = probe.getnext()
            target.addnext(new_tbl)
            placed = True
            break
    if not placed:
        d.element.body.append(new_tbl)

    for p in d.paragraphs:
        t = p.text.strip()
        if t.startswith("Table 3.1"):
            set_para(
                p,
                "Table 3.1  Use Case Matrix of Pyx Tracker: Web-based Enterprise Operations Management System.",
            )
        if t.startswith("Table 3.2  Use Case Matrix") or t.startswith("Table 3.3  Use Case Matrix") or t.startswith(
            "Table 3.4  Use Case Matrix"
        ) or t.startswith("Table 3.5  Use Case Matrix"):
            set_para(p, "")

    out = DESK.parent / "_uc_one_table.docx"
    DESK.parent.mkdir(parents=True, exist_ok=True)
    d.save(str(out))
    return out


def verify(path: Path) -> None:
    d = Document(str(path))
    uc = [t for t in d.tables if is_uc_matrix_table(t)]
    print("verify", path.name, "UC matrix tables:", len(uc))
    if uc:
        t = uc[0]
        print("  size", len(t.rows), "x", len(t.columns))
        print("  header", t.rows[0].cells[0].text, "|", t.rows[0].cells[1].text)
        titles = [r.cells[1].text for r in t.rows if r.cells[0].text.strip() == "Use case"]
        print("  use cases in one table:", titles)


def main() -> None:
    src = DESK if DESK.exists() else (NEW if NEW.exists() else MAIN)
    out = process(src)
    verify(out)
    for dest in (DESK, NEW, MAIN):
        try:
            shutil.copy2(out, dest)
            print("saved", dest, dest.stat().st_size)
            verify(dest)
        except Exception as e:
            print("LOCKED", dest, e)
    out.unlink(missing_ok=True)


if __name__ == "__main__":
    main()
