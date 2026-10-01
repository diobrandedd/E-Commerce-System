---
date: 2026-10-01
type: fix
area: honey-packaging
tags: [fix]
---

# Fix: by_scanner returns variant×size lines under each person

Group scan events by user + variant + size, fold into `{ user_id, name, qty, lines: [{ variant, size, qty }] }` sorted by total desc. Packaging SPA lists each person’s total then indented Variant · Size · qty rows (Today and All dates).

→ Problem: [[records/2026-10-01_honey-packaging-scanner-variant-size]]
