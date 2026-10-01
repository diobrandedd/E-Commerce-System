---
date: 2026-10-01
type: problem-record
status: fixed
area: deliveries
tags: [problem]
---

# Problem: Add deliveries required a second tap to open parcel camera

> Main hub note — links to **Cause**, **Fix**, and **Files** so Obsidian graph shows the chain.

## Symptom

After scanning a waybill on Photo tab, staff landed on the tracking preview and had to tap **Take before-shipment photo** before the camera opened.

## Cause

→ [[causes/deliveries-photo-no-auto-capture]]

## Fix

→ [[fixes/deliveries-auto-open-parcel-camera]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `app/src/app/pages/deliveries/DeliveriesPage.tsx` |

## Deploy

- SPA build + hard refresh on phone

## Links

- **Fragile edges:** [[003_Fragile_Edges#Deliveries]]
- **Related:** [[problems/records/2026-09-11_deliveries-photo-only]]
