---
name: Packaging Lot Stock-In
status: shipped
approved: true
approved_date: 2026-06-05
overview: "Replace packaging set absolute price/qty with lot stock-in: HR/executive enter total lot price + quantity; system derives unit cost, adds qty to on-hand pool via WAC, costing uses stocks.current_price per piece."
---

# Implementation Plan: Packaging lot stock-in (total price → unit cost → pool)

> **Iterates on** shipped [[plans/archive/2026-06-05_packaging-price-qty_plan]] (absolute `set_values`).  
> **Shipped:** 2026-06-05 — see [[problems/records/2026-06-05_packaging-lot-stock-in]]

## Summary

HR records purchase lots: total price paid + quantity received → unit cost = total ÷ qty; `on_hand` adds lot qty; WAC updates `current_price` when pool already has stock. API: `materials.php?action=stock_in_lot`. SPA: `PackagingStockInLotModal`, Packaging Materials "Stock in" UX.

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-06-05
