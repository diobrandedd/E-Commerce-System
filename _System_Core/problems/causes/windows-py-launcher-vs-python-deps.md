---
date: 2026-10-09
type: cause
tags: [cause, python, windows]
---

# Cause: Windows `py -3` ≠ PATH `python` deps

On Windows, `py -3` often launches the **newest** Python (e.g. 3.14) while `python` / `pip` target an older install (e.g. 3.12) where CSR packages were installed.

`waybill_stamper_resolve_python()` previously preferred `py` first whenever `--version` succeeded, so Apache ran extractor/stamper on a bare interpreter even after a successful `pip install openpyxl`.
