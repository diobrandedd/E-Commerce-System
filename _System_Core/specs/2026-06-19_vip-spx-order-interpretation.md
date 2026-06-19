---
status: approved
created: 2026-06-19
owner: CSR / ops
approved: true
---

# Feature: VIP / SPX order interpretation (deterministic + CSR builder)

Links: [[2026-06-04_Online-Orders-Generator]] · [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

Real J&T and SPX courier exports use different columns and messy item text. VIP prep drops trailing 350ml bottles, loses dept products in honey combos, uses phone as tracking, and SPX exports lack a Remarks column. CSRs need full remarks visible, structured edit when wrong, and per-color stock on 2L/3L cards.

## Outcome

- Auto-detect J&T (`Waybill Number`, `Remarks`, `Item Name`) and SPX (`Tracking No.`, `Item in Parcel`) exports.
- Parse multi-item lines (honey + Jar Cacao / Paragis / BRC / Honey Comb), 350ml promos (B2T1 LAD), gallon, mix → CSR carousel or structured builder.
- Raw remarks always visible on cards; Edit opens product / variant / qty builder (VIP, SPX, Order Remarks).
- 2L/3L preset cards show per-color stock (L · A · D), not one min number.

## Non-goals

- LLM / cloud API interpretation.
- Rewriting all four parser copies into one engine (extend in place).

## Lessons learned (2026-06-19 follow-up)

Documented in Obsidian: [[records/2026-06-19_vip-spx-parser-pitfalls]] · [[fixes/vip-spx-order-remarks-parser-guards]]

| Mistake | Prevention |
|---------|------------|
| `L` in `1 L HONEY` → false Light | `stripHoneySizeTokens()` before variant detect |
| Combo export = Jar Cacao only | Never drop honey from packer line; require builder save on multi-item |
| LLA parsed but "Pick variant" | Sync full 3L code list TS + PHP (incl. LLA) |
| 250ml orders ignored | Treat 250ml like 350ml in builder/parser/stock |
| J&T `3L HONEY(ALL DARK)1 PC 350ML(DARK)` drops 350ml | Split before `\d+ PC 350ML`; parse `(DARK)` word variant |

## Test strings (regression)

```
1 x 1 L HONEY: 1 JAR CACAO
1 x 2 L HONEY: 1 JAR CACAO, 1 x ALL LIGHT
textarea_input_1: 2 lights and 1 amber  +  3L-HONEY:/ Color:Mix  +  Set B free 350ml
1 x 250ml Honey (A)
3L HONEY(ALL DARK)1 PC 350ML(DARK)
```
