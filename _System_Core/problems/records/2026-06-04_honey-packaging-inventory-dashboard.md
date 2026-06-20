---
date: 2026-06-04
type: problem-record
status: fixed
area: honey
tags: [problem, inventory, enhancement]
---

# Problem: No bottled stock summary on Honey Packaging page

## Symptom

CSR could not see total sellable bottled inventory at a glance.

## Cause

→ [[causes/packaging-stock-not-visible-on-dashboard]]

## Fix

→ [[fixes/packaging-total-inventory-dashboard]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/honey_inventory.php`, `api/v1/honey.php` |
| SPA | `HoneyPackagingPage.tsx`, `HoneyVariantPieGrid.tsx` |
