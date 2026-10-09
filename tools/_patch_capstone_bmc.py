"""Replace BMC image (image4.png / rId11) in the capstone DOCX."""
from __future__ import annotations

from pathlib import Path
import zipfile

PNG = Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Business_Model_Canvas.png")
SRC = Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD.docx")
OUTS = [
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD.docx"),
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_CURRENT.docx"),
    Path(r"C:\Users\Admin\Desktop\capstone\Pyx_Tracker_Capstone_SQL_ERD_bmc.docx"),
    Path(r"C:\xampp\htdocs\rts\output\documents\Pyx_Tracker_Capstone_SQL_ERD.docx"),
]

MEDIA = "word/media/image4.png"


def main() -> None:
    png = PNG.read_bytes()
    with zipfile.ZipFile(SRC, "r") as zin:
        items = list(zin.infolist())
        blob = {i.filename: zin.read(i.filename) for i in items}

    if MEDIA not in blob:
        raise SystemExit(f"Missing {MEDIA}")

    blob[MEDIA] = png
    results = {}
    for path in OUTS:
        path.parent.mkdir(parents=True, exist_ok=True)
        tmp = path.with_suffix(".tmp.docx")
        try:
            with zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED) as zout:
                for item in items:
                    zout.writestr(item, blob[item.filename])
            tmp.replace(path)
            # verify
            with zipfile.ZipFile(path) as z:
                got = z.read(MEDIA)
            results[str(path)] = f"ok size={path.stat().st_size} media={len(got)}"
        except PermissionError as e:
            if tmp.exists():
                tmp.unlink(missing_ok=True)
            results[str(path)] = f"LOCKED:{e}"

    for k, v in results.items():
        print(v, "=>", k)


if __name__ == "__main__":
    main()
