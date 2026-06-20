---
date: 2026-06-05
type: problem-record
status: fixed
area: approvals
tags: [problem, requests, spa]
---

# Problem: Approval requests were split across separate pages

## Symptom

Executives expected delivery edit requests under sidebar **Requests**, but those approvals lived on a separate Edit Requests page.

## Cause

→ [[causes/requests-hub-siloed-edit-approvals]]

## Fix

→ [[fixes/unified-requests-sections-and-redirects]]

## Files

| Role | Path / note |
|------|-------------|
| Hub page | `app/src/app/pages/inventory/InventoryRequestsPage.tsx` |
| Reusable section | `app/src/app/components/honey/EditRequestsSection.tsx` |
| Redirect rules | `app/src/app/pages/EditRequestsPage.tsx` |
| Sidebar | `app/src/app/components/layout/Sidebar.tsx` |

## Deploy

- `cd app && npm run build` + hard refresh

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Requests hub (unified approvals)]]

## AI rules

Add new approval workflows as sections inside Requests hub before adding new top-level nav pages.
