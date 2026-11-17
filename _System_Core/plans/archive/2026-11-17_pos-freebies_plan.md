---
status: shipped
spec: "[[specs/2026-11-17_pos-freebies]]"
created: 2026-11-17
approved_by: chat
---

# Implementation Plan: POS freebies + Sales Freebies given

> Spec: [[specs/2026-11-17_pos-freebies]]

## Summary

`pos_receipt_freebies` + `pos_unified` freebie lookup/picker/checkout deduct; Sales checkout freebie lines + `honey_sales_freebie_breakdown`; SPA POS Add freebie + Sales card.

## Deploy

- Run `sql/migration_phase99_pos_receipt_freebies.sql`
- Rebuild SPA · hard refresh
