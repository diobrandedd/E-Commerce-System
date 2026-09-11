# Problem: Deliveries required CSR parcel to save before-shipment photo

**Date:** 2026-09-11

## Problem

Warehouse could not document a tracking photo unless CSR had finalized an online parcel. Ship messaging still implied inventory deduct.

## Cause

Add deliveries gated on `parcel_by_tracking` + `ship_parcel`.

## Fix

Photo tab: scan TN → photo → save to `delivery_batch_items` only. No CSR required, no deduct, no contents UI. Duplicate warn allows “another photo anyway”. RTS still matches by TN.

## Files

- `app/src/app/pages/deliveries/DeliveriesPage.tsx`
- `DeliveryDuplicateScanModal.tsx`, `DeliveriesAddGuideModal.tsx`, `deliveriesAddTour.ts`
- Spec/plan: `2026-09-11_deliveries-photo-only`
