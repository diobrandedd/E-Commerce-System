---
status: shipped
spec: "[[specs/2026-09-10_inventory-check-scan-only]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Inventory check scan-only (no batch)

> Spec: [[specs/2026-09-10_inventory-check-scan-only]]

## Summary

Optional batch on scan API; honey compare by variant|size; SPA removes batch field, adds 500ms cooldown + item success popup.

## Files

| File | Action |
|------|--------|
| `includes/inventory_check.php` | modify |
| `InventoryCheckPage.tsx` | modify |
| `BarcodeCameraScannerModal.tsx` | cooldown prop |
