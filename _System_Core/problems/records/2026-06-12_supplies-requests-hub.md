---
date: 2026-06-12
type: problem-record
status: fixed
area: supplies-inventory
tags: [supplies, requests, notifications, guide]
---

# Problem: Supplies lacked dedicated requests UX and executive notifications

> Supplies backend existed but Requests page mixed packaging + supply rows, review used `prompt()`, and bell did not surface supply issues.

## Symptom

HR could submit supply issue requests, but executives reviewed them in a combined table with inline approve/reject and no deny-note modal. No role-specific guides on Supplies or Requests. Bell notifications omitted supply requests.

## Cause

Phase 25 shipped core API/DB; SPA never split the Requests hub or wired `request_mark_viewed` / `request_unread_list`.

## Fix

→ [[fixes/supplies-hr-lot-inventory]]

## Files

| Role | Path / note |
|------|-------------|
| SPA | `SuppliesMaterialsPage.tsx`, `SuppliesInventoryGuideModal.tsx`, `SupplyMaterialDetailModal.tsx`, `InventoryRequestsPage.tsx`, `InventoryRequestsGuideModal.tsx`, `SupplyRequestReviewModal.tsx` |
| Notifications | `CorrectionNotificationsContext.tsx`, `PageLayout.tsx` |
| Client API | `app/src/app/lib/materials.ts` |
| Backend | `includes/material_inventory.php`, `api/v1/materials.php` |
| Docs | [[005_SPA_UX_Patterns#Requests hub]], `.cursor/rules/inventory-roles-and-guides.mdc` |

## Deploy

`cd app && npm run build` + hard refresh · `php tools/memory_bank/generate_all.php`

## Links

- **Patterns:** [[fixes/page-guide-button-modal]] · [[fixes/inventory-role-permissions]]
- **Plan:** `plans/active/supplies_requests_ux` (archived after ship)

## AI rules

Drive supply detail buttons from `supply_detail.permissions`; executive supply review on Requests via `SupplyRequestReviewModal`, not inline `prompt()`.
