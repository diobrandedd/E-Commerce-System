"""Remove Word Expand/Collapse heading indicators by using Body Text outline levels."""
from __future__ import annotations

from copy import deepcopy
from pathlib import Path
import zipfile

from lxml import etree

SRC = Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD.docx")
OUTS = [
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD_no_collapse.docx"),
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_CURRENT.docx"),
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_SQL_ERD_no_collapse.docx"),
]

NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
W = NS["w"]
MAP = {
    "Heading1": ("CapH1", "Cap Heading 1"),
    "Heading2": ("CapH2", "Cap Heading 2"),
    "Heading3": ("CapH3", "Cap Heading 3"),
}
# Word field code: style-based TOC (no outline levels)
NEW_TOC = ' TOC \\h \\z \\u \\t "CapH1,1,CapH2,2,CapH3,3" '


def q(tag: str) -> str:
    return f"{{{W}}}{tag}"


def main() -> None:
    with zipfile.ZipFile(SRC, "r") as zin:
        styles_root = etree.fromstring(zin.read("word/styles.xml"))
        doc_root = etree.fromstring(zin.read("word/document.xml"))
        source_items = list(zin.infolist())
        source_blob = {i.filename: zin.read(i.filename) for i in source_items}

    style_by_id = {
        st.get(q("styleId")): st
        for st in styles_root.xpath("./w:style", namespaces=NS)
        if st.get(q("styleId"))
    }

    for old_id, (new_id, new_name) in MAP.items():
        if new_id not in style_by_id:
            if old_id not in style_by_id:
                raise SystemExit(f"Missing style {old_id}")
            st = deepcopy(style_by_id[old_id])
            st.set(q("styleId"), new_id)
            st.set(q("customStyle"), "1")
            if q("default") in st.attrib:
                del st.attrib[q("default")]
            name_el = st.find("w:name", NS)
            if name_el is None:
                name_el = etree.SubElement(st, q("name"))
            name_el.set(q("val"), new_name)
            based = st.find("w:basedOn", NS)
            if based is None:
                based = etree.Element(q("basedOn"))
                name_el.addnext(based)
            based.set(q("val"), old_id)
            for tag in ("next", "link"):
                el = st.find(f"w:{tag}", NS)
                if el is not None:
                    st.remove(el)
            styles_root.append(st)
            style_by_id[new_id] = st

        st = style_by_id[new_id]
        pPr = st.find("w:pPr", NS)
        if pPr is None:
            pPr = etree.SubElement(st, q("pPr"))
        ol = pPr.find("w:outlineLvl", NS)
        if ol is None:
            ol = etree.SubElement(pPr, q("outlineLvl"))
        ol.set(q("val"), "9")  # Body Text — no Expand/Collapse triangle

    remapped = 0
    for pStyle in doc_root.xpath("//w:pStyle", namespaces=NS):
        val = pStyle.get(q("val"))
        if val in MAP:
            pStyle.set(q("val"), MAP[val][0])
            remapped += 1

    toc_updated = 0
    for instr in doc_root.xpath("//w:instrText", namespaces=NS):
        text = instr.text or ""
        if "TOC" in text:
            instr.text = NEW_TOC
            toc_updated += 1

    new_styles = etree.tostring(
        styles_root, xml_declaration=True, encoding="UTF-8", standalone=True
    )
    new_doc = etree.tostring(
        doc_root, xml_declaration=True, encoding="UTF-8", standalone=True
    )

    results = {}
    for path in OUTS:
        path.parent.mkdir(parents=True, exist_ok=True)
        tmp = path.with_suffix(".tmp.docx")
        try:
            with zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED) as zout:
                for item in source_items:
                    data = source_blob[item.filename]
                    if item.filename == "word/styles.xml":
                        data = new_styles
                    elif item.filename == "word/document.xml":
                        data = new_doc
                    zout.writestr(item, data)
            tmp.replace(path)
            results[str(path)] = path.stat().st_size
        except PermissionError as e:
            if tmp.exists():
                tmp.unlink(missing_ok=True)
            results[str(path)] = f"LOCKED:{e}"

    print("remapped", remapped, "toc_updated", toc_updated)
    for k, v in results.items():
        print(v, k)


if __name__ == "__main__":
    main()
