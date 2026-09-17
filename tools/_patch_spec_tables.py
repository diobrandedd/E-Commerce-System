"""Rewrite Software/Hardware/Program Specification tables to sample 2-column layout."""

from __future__ import annotations

import shutil
from pathlib import Path

from docx import Document
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

SRC = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx.new")
DESK = Path(r"C:/Users/Admin/Desktop/capstone/Pyx_Tracker_Capstone_SQL_ERD.docx")
MAIN = Path(r"C:/xampp/htdocs/rts/output/documents/Pyx_Tracker_Capstone_SQL_ERD.docx")

SOFTWARE_ROWS = [
    ("Software Requirements", ""),
    ("Language", "PHP, React, TypeScript"),
    ("Technology", "Visual Studio Code"),
    ("Database", "MySQL"),
    ("IDE", "Visual Studio Code"),
    ("Operating system", "Microsoft Windows 11"),
]
HARDWARE_ROWS = [
    ("Processor", "Intel Core i5 or equivalent"),
    ("Solid State Drive", "250 GB"),
    ("Ram", "8 GB RAM"),
]
PROGRAM_ROWS = [
    ("Language", "PHP"),
    ("Database", "MySQL, XAMPP Server"),
]


def rebuild(old_tbl, rows) -> None:
    parent = old_tbl.getparent()
    idx = parent.index(old_tbl)

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

    tblGrid = OxmlElement("w:tblGrid")
    for _ in range(2):
        gc = OxmlElement("w:gridCol")
        gc.set(qn("w:w"), "4500")
        tblGrid.append(gc)
    tbl.append(tblGrid)

    for left, right in rows:
        tr = OxmlElement("w:tr")
        for text, bold in ((left, True), (right, False)):
            tc = OxmlElement("w:tc")
            tcPr = OxmlElement("w:tcPr")
            tcW = OxmlElement("w:tcW")
            tcW.set(qn("w:w"), "4500")
            tcW.set(qn("w:type"), "dxa")
            tcPr.append(tcW)
            tc.append(tcPr)
            p = OxmlElement("w:p")
            r = OxmlElement("w:r")
            rPr = OxmlElement("w:rPr")
            rf = OxmlElement("w:rFonts")
            rf.set(qn("w:ascii"), "Times New Roman")
            rf.set(qn("w:hAnsi"), "Times New Roman")
            rPr.append(rf)
            sz = OxmlElement("w:sz")
            sz.set(qn("w:val"), "22")
            rPr.append(sz)
            if bold:
                rPr.append(OxmlElement("w:b"))
            r.append(rPr)
            t = OxmlElement("w:t")
            t.set("{http://www.w3.org/XML/1998/namespace}space", "preserve")
            t.text = text
            r.append(t)
            p.append(r)
            tc.append(p)
            tr.append(tc)
        tbl.append(tr)

    parent.insert(idx, tbl)
    parent.remove(old_tbl)


def set_para_text(paragraph, text: str) -> None:
    for r in paragraph.runs:
        r.text = ""
    if paragraph.runs:
        paragraph.runs[0].text = text
    else:
        paragraph.add_run(text)


def main() -> None:
    d = Document(str(SRC))
    targets: dict[str, int] = {}
    for ti, table in enumerate(d.tables):
        blob = " ".join(c.text for r in table.rows for c in r.cells).lower()
        if "client interface" in blob and "react" in blob:
            targets["software"] = ti
        if "application server" in blob and "barcode scanner" in blob:
            targets["hardware"] = ti
        if "presentation" in blob and "business processing" in blob:
            targets["program"] = ti
        if "software requirements" in blob and "operating system" in blob:
            targets["software"] = ti
        if blob.startswith("processor") or ("solid state drive" in blob and "ram" in blob):
            targets["hardware"] = ti

    print("targets", targets)
    required = ("software", "hardware", "program")
    if not all(k in targets for k in required):
        raise SystemExit(f"Missing table targets: {targets}")

    soft_tbl = d.tables[targets["software"]]._tbl
    hard_tbl = d.tables[targets["hardware"]]._tbl
    prog_tbl = d.tables[targets["program"]]._tbl

    rebuild(soft_tbl, SOFTWARE_ROWS)
    rebuild(hard_tbl, HARDWARE_ROWS)
    rebuild(prog_tbl, PROGRAM_ROWS)

    for p in d.paragraphs:
        t = p.text.strip()
        if t == "Table 3.32  Software Specification" or t.startswith("Table 3.32"):
            set_para_text(
                p,
                "Table 3.32  The Software Specification Table for Pyx Tracker: "
                "Web-based Enterprise Operations Management System.",
            )
        elif t.startswith("Table 3.33"):
            set_para_text(
                p,
                "Table 3.33  The Hardware Specification Table for Pyx Tracker: "
                "Web-based Enterprise Operations Management System.",
            )
        elif t.startswith("Table 3.34"):
            set_para_text(
                p,
                "Table 3.34  The Program Specification Table for Pyx Tracker: "
                "Web-based Enterprise Operations Management System.",
            )
        elif t.startswith("The sources do not identify exact deployed equipment"):
            set_para_text(
                p,
                "Hardware values state the minimum recommended configuration for development "
                "and local deployment. Installed equipment at Pyx Food Products should be "
                "confirmed against this table.",
            )

    DESK.parent.mkdir(parents=True, exist_ok=True)
    tmp = SRC.parent / "_spec_tables_out.docx"
    d.save(str(tmp))
    print("built", tmp, tmp.stat().st_size)

    for dest in (SRC, DESK, MAIN):
        try:
            shutil.copy2(tmp, dest)
            print("saved", dest, dest.stat().st_size)
        except Exception as e:
            print("LOCKED", dest, e)

    v = Document(str(tmp))
    for table in v.tables:
        cells = [c.text for r in table.rows for c in r.cells]
        if "Software Requirements" in cells:
            print("SOFTWARE")
            for r in table.rows:
                print(" | ".join(c.text for c in r.cells))
        if "Processor" in cells and "Solid State Drive" in cells:
            print("HARDWARE")
            for r in table.rows:
                print(" | ".join(c.text for c in r.cells))
        if "XAMPP Server" in cells:
            print("PROGRAM")
            for r in table.rows:
                print(" | ".join(c.text for c in r.cells))

    tmp.unlink(missing_ok=True)


if __name__ == "__main__":
    main()
