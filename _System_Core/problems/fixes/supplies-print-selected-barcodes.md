---
date: 2026-10-29
type: fix
---

# Fix: Print selected supply barcodes

1. Row checkboxes store stock ids in page state, not in the filtered list, so category and search changes keep the ticks.
2. Header checkbox selects or clears only the rows currently visible.
3. Print selected calls `packaging_print_sheet&ids=`. Generate all still omits sized bubble and insulation rolls.
