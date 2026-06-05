---
date: 2026-06-05
type: problem-record
status: fixed
area: deliveries
tags: [problem, deliveries, performance, spa]
---

# Problem: Done/Scan next felt slow on deliveries

## Symptom

PM users saw long “Saving parcel…” waits on every parcel after taking photos.

## Cause

→ [[causes/deliveries-save-latency-upload-and-refresh-chain]]

## Fix

→ [[fixes/eager-compressed-photo-upload-for-deliveries]]

## Files

| Role | Path / note |
|------|-------------|
| SPA compression | `app/src/app/lib/compressDeliveryPhoto.ts` |
| SPA flow | `app/src/app/pages/deliveries/DeliveriesPage.tsx` |
| API client | `app/src/app/lib/deliveriesApi.ts` |
| PHP upload | `includes/deliveries_upload.php` |

## Deploy

- PHP + SPA build (`cd app && npm run build`) + hard refresh

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Photo upload performance (SPA + API)]]

## AI rules

For camera-heavy flows, prefer capture-time compressed background upload over confirm-time upload.
