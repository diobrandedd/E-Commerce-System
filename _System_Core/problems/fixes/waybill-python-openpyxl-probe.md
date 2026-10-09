---
date: 2026-10-09
type: fix
tags: [fix, python, openpyxl]
---

# Fix: Prefer Python that can `import openpyxl`

`waybill_stamper_resolve_python()` now:

1. Prefers `python` before `py` on Windows.
2. Probes each candidate with `import openpyxl; print(1)` (print required — Windows `shell_exec` returns null on empty stdout).
3. Returns the first interpreter that passes; otherwise throws an actionable `… -m pip install …` message for that same launcher.

Does **not** reinstall packages into a second Python — keeps the operator’s existing 3.12 waybill setup intact.
