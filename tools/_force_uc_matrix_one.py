"""Merge UC01-UC05 into ONE Table 3.1 matrix; repair corrupted DD captions 3.10-3.19."""

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

# True matrix: Element | UC01 | UC02 | UC03 | UC04 | UC05
HEADERS = [
    "Element",
    "UC01 Inventory stock movement",
    "UC02 Product and parcel costing",
    "UC03 Order preparation and finalization",
    "UC04 Before-shipment documentation",
    "UC05 RTS case and sellable stock-in",
]

MATRIX = [
    ["Use case", "UC01 Inventory stock movement", "UC02 Product and parcel costing", "UC03 Order preparation and finalization", "UC04 Before-shipment documentation", "UC05 RTS case and sellable stock-in"],
    ["Author", AUTHOR, AUTHOR, AUTHOR, AUTHOR, AUTHOR],
    ["Primary actor", "Admin/Executive and Staff", "Admin/Executive", "Admin/Executive and Staff", "Admin/Executive and Staff", "Admin/Executive and Staff"],
    ["Trigger", "Material receipt or finished-stock operation", "Review or update of product costs", "Receipt of supported order files", "Parcel presented for photo recording", "Returned parcel received"],
    ["Preconditions", "Valid product, stock unit, permission and source record.", "Configured material prices, units and formulas.", "Supported source files and an active processing batch.", "Readable or manually entered tracking identifier and an image.", "Tracking or case information, item condition and readable product codes."],
    [
        "Basic flow",
        "1. Select category and product.\n2. Enter or scan the movement.\n3. Review quantity and source.\n4. Confirm the allowed operation.",
        "1. Select product or tier.\n2. Review quantities, prices and yield.\n3. Maintain formula lines.\n4. Review calculated cost and price.",
        "1. Choose preparation tool.\n2. Upload and review data.\n3. Run separate PDF/Excel stamper.\n4. Download results and finalize batch.",
        "1. Scan or type tracking.\n2. Capture or upload photo.\n3. Review and save.\n4. Continue to next record.",
        "1. Record case and condition.\n2. Open stock-in session.\n3. Scan received sellable goods.\n4. Review and confirm session.",
    ],
    [
        "Postcondition",
        "The relevant balance and movement record reflect the confirmed quantity.",
        "The cost is reproducible from the stored inputs.",
        "Prepared outputs and finalized order/parcel records are available.",
        "Photo is retrievable by tracking number; stock is unchanged.",
        "Case history is retained; confirmed sellable quantity is credited once.",
    ],
    [
        "Exceptions",
        "Reject invalid input; preserve balances when an unconfirmed action is cancelled.",
        "Deny non-executive access; identify missing configuration before relying on the result.",
        "Report duplicates, unmatched records or missing fields; do not describe partial preparation as finalization.",
        "Provide duplicate feedback and request valid image/tracking input. A CSR parcel is not required.",
        "Case save alone adds no sellable stock; discarding a draft leaves inventory unchanged.",
    ],
]

# Corrupt captions at these paragraph indices (from Desktop snapshot) — restore DD names
DD_FIX = {
    # text that currently wrongly says Table 3.1 Use Case... after Table 3.9 suppliers
    # We match by order after Table 3.9
}

DD_SEQUENCE = [
    "Table 3.10  Data Dictionary for stocks",
    "Table 3.11  Data Dictionary for stock_entries",
    "Table 3.12  Data Dictionary for honey_warehouse_rows",
    "Table 3.13  Data Dictionary for honey_production_rows",
    "Table 3.14  Data Dictionary for honey_packaging_rows",
    "Table 3.15  Data Dictionary for dept_inventory_rows",
    "Table 3.16  Data Dictionary for products",
    "Table 3.17  Data Dictionary for product_formula",
    "Table 3.18  Data Dictionary for fulfillment_pack_tiers",
    "Table 3.19  Data Dictionary for fulfillment_pack_tier_lines",
]


def tc(text: str, *, bold=False, fill=None, white=False, width="900"):
    cell = OxmlElement("w:tc")
    pr = OxmlElement("w:tcPr")
    w = OxmlElement("w:tcW")
    w.set(qn("w:w"), width)
    w.set(qn("w:type"), "dxa")
    pr.append(w)
    if fill:
        shd = OxmlElement("w:shd")
        shd.set(qn("w:fill"), fill)
        shd.set(qn("w:val"), "clear")
        pr.append(shd)
    cell.append(pr)
    lines = text.split("\n") if text else [""]
    for line in lines:
        p = OxmlElement("w:p")
        r = OxmlElement("w:r")
        rPr = OxmlElement("w:rPr")
        rf = OxmlElement("w:rFonts")
        rf.set(qn("w:ascii"), "Times New Roman")
        rf.set(qn("w:hAnsi"), "Times New Roman")
        rPr.append(rf)
        sz = OxmlElement("w:sz")
        sz.set(qn("w:val"), "14")  # 7pt for matrix fit
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
        cell.append(p)
    return cell


def build_matrix_tbl():
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
    for _ in range(6):
        gc = OxmlElement("w:gridCol")
        gc.set(qn("w:w"), "900")
        grid.append(gc)
    tbl.append(grid)

    # header
    tr = OxmlElement("w:tr")
    for h in HEADERS:
        tr.append(tc(h, bold=True, fill="2F80ED", white=True))
    tbl.append(tr)
    for i, row in enumerate(MATRIX):
        fill = "D6EAF8" if i % 2 else "FFFFFF"
        tr = OxmlElement("w:tr")
        for j, val in enumerate(row):
            tr.append(tc(val, bold=(j == 0), fill=fill))
        tbl.append(tr)
    return tbl


def is_uc_table(table) -> bool:
    blob = " ".join(c.text for r in table.rows[:4] for c in r.cells).lower()
    if "uc01" in blob and ("uc02" in blob or "primary actor" in blob or "primary Actor".lower() in blob):
        return True
    if "inventory stock movement" in blob and "element" in blob and "specification" in blob:
        return True
    h0 = table.rows[0].cells[0].text.strip().lower()
    if h0 in ("use case", "element") and ("pyx tracker" in blob or "uc01" in blob or "specification" in blob):
        # carefully: only if basic flow / author present
        if "author" in blob or "basic flow" in blob or "uc01" in blob:
            return True
    return False


def set_para(p, text: str) -> None:
    for r in p.runs:
        r.text = ""
    if p.runs:
        p.runs[0].text = text
    else:
        p.add_run(text)


def main() -> None:
    src = DESK if DESK.exists() else NEW
    d = Document(str(src))

    # Remove every UC matrix table
    removed = 0
    for table in list(d.tables):
        if is_uc_table(table):
            table._tbl.getparent().remove(table._tbl)
            removed += 1
    print("removed", removed)

    # Insert one matrix after Use Case Matrix heading / Table 3.1 caption
    new_tbl = build_matrix_tbl()
    placed = False
    for p in d.paragraphs:
        if p.text.strip() == "Use Case Matrix":
            target = p._element
            probe = target.getnext()
            while probe is not None:
                if probe.tag == qn("w:p"):
                    joined = "".join((t.text or "") for t in probe.findall(".//" + qn("w:t"))).strip()
                    if joined.startswith("Table 3.1"):
                        target = probe
                        break
                    if joined.startswith("Storyboard"):
                        break
                probe = probe.getnext()
            target.addnext(new_tbl)
            placed = True
            break
    if not placed:
        d.element.body.append(new_tbl)
    print("placed matrix", placed)

    # Fix captions:
    # - Exactly one Table 3.1 UC caption under Use Case Matrix
    # - Restore DD 3.10-3.19
    # - Clear any Table 3.2-3.5 Use Case Matrix captions
    dd_fix_i = 0
    seen_uc_caption = False
    after_suppliers = False
    for p in d.paragraphs:
        t = p.text.strip()
        if t.startswith("Table 3.9  Data Dictionary for suppliers"):
            after_suppliers = True
            continue
        if after_suppliers and dd_fix_i < len(DD_SEQUENCE):
            # next captions that are wrong OR empty until 3.20
            if t.startswith("Table 3.1  Use Case Matrix") or t.startswith("Table 3.1 ") or (
                t.startswith("Table 3.1") and "Data Dictionary" not in t
            ):
                set_para(p, DD_SEQUENCE[dd_fix_i])
                print("fix DD caption ->", DD_SEQUENCE[dd_fix_i])
                dd_fix_i += 1
                continue
            if t.startswith("Table 3.20"):
                after_suppliers = False
                continue

        if t.startswith("Table 3.2  Use Case Matrix") or t.startswith("Table 3.3  Use Case Matrix") or t.startswith(
            "Table 3.4  Use Case Matrix"
        ) or t.startswith("Table 3.5  Use Case Matrix"):
            set_para(p, "")
            print("cleared old UC caption", t[:40])
            continue

        if t.startswith("Table 3.1") and "Use Case Matrix" in t:
            if not seen_uc_caption:
                set_para(
                    p,
                    "Table 3.1  Use Case Matrix of Pyx Tracker: Web-based Enterprise Operations Management System.",
                )
                seen_uc_caption = True
            else:
                # duplicate UC caption in wrong place — if still in DD zone handled above; else clear
                if "Data Dictionary" not in t and dd_fix_i >= len(DD_SEQUENCE):
                    set_para(p, "")
                    print("cleared duplicate UC caption")

    out = DESK.parent / "_uc_matrix_final.docx"
    d.save(str(out))

    # verify
    v = Document(str(out))
    uc_tables = [t for t in v.tables if is_uc_table(t)]
    print("UC tables count:", len(uc_tables))
    if uc_tables:
        print("size", len(uc_tables[0].rows), "x", len(uc_tables[0].columns))
        print("header:", [c.text[:28] for c in uc_tables[0].rows[0].cells])
    caps = []
    for p in v.paragraphs:
        t = p.text.strip()
        if "Use Case Matrix" in t or (t.startswith("Table 3.") and "Use Case" in t):
            caps.append(t[:90])
        if t.startswith("Table 3.1") and "Data Dictionary" not in t:
            caps.append("CAP3.1:" + t[:80])
    print("captions:")
    for c in caps:
        print(" ", c)
    # show 3.9-3.21
    for p in v.paragraphs:
        t = p.text.strip()
        if t.startswith("Table 3.9") or t.startswith("Table 3.1") and "Data" in t or t.startswith("Table 3.20"):
            if "Table 3.1 " in t and "Use Case" in t:
                continue
            if t.startswith("Table 3.9") or t.startswith("Table 3.10") or t.startswith("Table 3.11") or t.startswith(
                "Table 3.19"
            ) or t.startswith("Table 3.20"):
                print("DD", t)

    for dest in (DESK, NEW, MAIN):
        try:
            shutil.copy2(out, dest)
            print("SAVED", dest, dest.stat().st_size)
        except Exception as e:
            print("LOCKED", dest, e)

    # Also write unmistakable Desktop copy
    alt = DESK.parent / "Pyx_Tracker_Capstone_Table_3_1_Use_Case_Matrix.docx"
    shutil.copy2(out, alt)
    print("ALSO", alt, alt.stat().st_size)
    out.unlink(missing_ok=True)


if __name__ == "__main__":
    main()
