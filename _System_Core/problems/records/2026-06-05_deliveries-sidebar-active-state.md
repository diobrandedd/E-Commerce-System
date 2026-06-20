---
date: 2026-06-05
type: problem-record
status: fixed
area: navigation
tags: [problem, sidebar, spa]
---

# Problem: Both deliveries sidebar links were active on review route

## Symptom

On `/deliveries/review`, both **Add deliveries** and **Review batches** highlighted as active.

## Cause

→ [[causes/sidebar-prefix-match-activated-sibling-route]]

## Fix

→ [[fixes/sidebar-exact-match-for-add-deliveries]]

## Files

| Role | Path / note |
|------|-------------|
| Sidebar nav | `app/src/app/components/layout/Sidebar.tsx` |
| Route context | [[generated/spa/150_Routes]] |

## Deploy

- SPA build + hard refresh

## Links

- **Patterns:** [[005_SPA_UX_Patterns#Sidebar navigation — sibling routes]]
