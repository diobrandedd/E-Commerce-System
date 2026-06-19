---
date: 2026-06-19
type: cause
area: csr-order-remarks
tags: [cause, vip, spx, parser]
---

# Cause: Size token `L` in `1 L HONEY` read as Light variant

## What happened

`detectVariants()` used a regex like `(?:^|[\s,&])l(?:\s|$|[,&])` on the **full remark string**. The **`L` in `1 L HONEY`** (bottle size, not color) matched as **Light (L)**.

## Symptom

- Order `1 x 1 L HONEY: 1 JAR CACAO` looked **resolved** without CSR picking a real variant.
- Export wrote only **`1 Jar Cacao`** — honey line dropped because fake `L` failed validation downstream.

## Rule for agents

**Always strip honey size tokens** (`1 L HONEY`, `2L-HONEY`, `350ml`, `250ml`, etc.) **before** single-letter L/A/D variant detection. Use `stripHoneySizeTokens()` in `itemLineBuilder.ts` / mirror in PHP if needed.

## Related

- [[fixes/vip-spx-order-remarks-parser-guards]]
- [[records/2026-06-19_vip-spx-parser-pitfalls]]
