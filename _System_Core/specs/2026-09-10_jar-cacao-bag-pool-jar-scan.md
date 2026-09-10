---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [jar-cacao, cacao, bag-pool, jar-scan, settings, bom]
---

# Jar Cacao bag pool → jar scan

> Stock raw 1 kg bags into a **bag pool**. Scanning Cacao jars into inventory credits finished goods and deducts cacao from the pool (default **220 g** / jar). Progress bar like Paragis/BRC. **Grams per jar** editable in Settings.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[specs/2026-09-10_brc-bag-pool-pouch-scan]] · [[specs/2026-06-05_jar-cacao-bom-math]]

## Problem

Jar Cacao still uses inventorial **Produce jars** (formula deduct raw + packaging). Ops wants the same bag-pool model: stock bags → scan jars → pool drops with a progress bar.

## Locked behavior (chat 2026-09-10)

1. **Hide** inventorial **Produce jars** as the primary floor path. Keep bag waste + stock-in.
2. **Stock in raw bags** fills the **bag pool**.
3. **Scan jar to inventory** (unique or SKU) → +1 finished jar **and** deduct **grams_per_jar** from the pool (default **220 g** from a **1000 g** bag → **0.22 bags**).
4. If pool is insufficient → **warn**, but **allow** scan; pool may go **negative**.
5. **No** packaging materials deduct on jar scan (jar packaging / sticker / foil ignored on scan).
6. **Settings** (executive):
   - `jar_cacao.grams_per_jar` — default **220** (deduct per scan) — primary editable field requested
   - `jar_cacao.grams_per_bag` — default **1000** (supplier bag size / capacity math; same pattern as BRC)
7. Reuse `DeptBagPoolStrip` with jar wording (g per jar / jars left).
8. PT / BRC / Honey Comb unchanged in this spec.

## Success criteria

- [ ] Cacao raw page shows bag-pool progress; updates on stock-in and each jar/SKU scan.
- [ ] Scan credits finished inventory and deducts configured g; negative allowed with warning.
- [ ] Settings can change grams/jar (and grams/bag); next scans use new factors.
- [ ] Produce jars inventorial CTA hidden/demoted.
- [ ] Guide / subtitle / Do next copy match bag-pool language.

## Non-goals

- Historical `produce_jar` cleanup.
- Packaging on-hand deduct on scan.
- Honey Comb bag pool.

## Approval

- [x] Behavior confirmed
- [x] Implementation plan approved
- Approved date: 2026-09-10
