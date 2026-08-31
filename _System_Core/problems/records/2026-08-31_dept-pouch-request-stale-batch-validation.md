---
date: 2026-08-31
type: problem-record
status: fixed
area: fulfillment
tags: [dept-pouch, sku-barcode, requests]
---

# Problem: Static pouch barcode request rejected blank batch code

## Symptom

The static SKU pouch request form correctly hid the batch-code input, but
submitting a quantity returned “Batch code must be 3–32 letters, digits or
dashes.”

## Cause

→ [[causes/dept-pouch-request-stale-batch-validation]]

## Fix

→ [[fixes/dept-pouch-request-stale-batch-validation]]

## Files

| Role | Path / note |
|------|-------------|
| Backend | `includes/dept_pouch_barcodes.php` |
| Existing UI/client | `app/src/app/components/dept/DeptPouchBarcodePanels.tsx`, `app/src/app/lib/deptPouchBarcodes.ts` |

## Deploy

- Deploy the PHP change; no migration is needed.
- Hard-refresh the SPA if the request form was previously cached.
- Verify a blank batch request succeeds and explicit invalid legacy batch input
  is still rejected.
