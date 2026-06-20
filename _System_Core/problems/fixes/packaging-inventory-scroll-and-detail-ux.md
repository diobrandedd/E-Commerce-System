---
type: fix
tags: [fix, inventory, packaging, spa, ux]
---

# Fix: Packaging materials — scroll regions, detail modal, role-split nav

## What we changed

Standardized Packaging Materials (and future material catalog pages) so **summary + headers stay fixed** and **only the list body scrolls**. Detail modals cap **Recent activity** scroll separately so the modal never grows with history.

HR **Requests** is a **top-level sidebar item** (not nested under Inventory). Executive/prod_manager keep Requests under Inventory with badge.

## Implementation patterns (copy for Supplies / similar pages)

### Page layout — fixed stats + scrollable table

Reference: [`PackagingMaterialsPage.tsx`](../../../app/src/app/pages/inventory/PackagingMaterialsPage.tsx)

```tsx
<div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0">
  <div className="grid ... shrink-0">{/* summary cards */}</div>
  <div className="bg-card rounded-xl border overflow-hidden flex flex-col min-h-0 flex-1">
    <div className="... shrink-0">{/* column headers */}</div>
    <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain">
      {/* rows */}
    </div>
  </div>
</div>
```

- `8rem` accounts for `PageLayout` sticky header + main padding; tune if header height changes.
- Do **not** let `PageLayout` `<main>` be the only scroll container for long tables — inner body scroll keeps stats visible.

### Clickable rows → detail modal

- Whole row is `<button>` with `hover:bg-secondary/40` + `ChevronRight`.
- Open `PackagingMaterialDetailModal` — metrics grid, actions, recent activity.
- API: `materials.php?action=packaging_detail` → stock + `entries[]`.

### Detail modal — capped activity scroll

Reference: [`PackagingMaterialDetailModal.tsx`](../../../app/src/app/components/inventory/PackagingMaterialDetailModal.tsx)

```tsx
<AppModal panelClassName="max-w-lg w-full max-h-[90vh]">
  <AppModalBody className="flex flex-col min-h-0 overflow-hidden gap-5">
    {/* metrics + actions: shrink-0 */}
    <div className="flex flex-col min-h-0 flex-1">
      <div className="min-h-0 max-h-44 overflow-y-auto overscroll-contain rounded-xl border">
        {/* activity list */}
      </div>
    </div>
  </AppModalBody>
</AppModal>
```

- Override default `AppModalBody` `overflow-y-auto` with `overflow-hidden` when inner regions scroll.
- `max-h-44` ≈ 3–4 activity rows; increase only if product asks for taller history.

### Sidebar — HR Requests top-level

Reference: [`Sidebar.tsx`](../../../app/src/app/components/Sidebar.tsx)

- `inventoryNavHr` — children **without** Requests link.
- `requestsNav` — `{ icon: ClipboardList, label: "Requests", to: "/inventory/requests" }` as sibling after Inventory for `hr` role only.

## Backend (on-hand roles)

| Role | Action |
|------|--------|
| Executive | `set_on_hand` direct (`materials.php`) |
| HR | `request_submit` with `action: set_on_hand` |
| Prod manager | supply add/deduct requests only |

Migration: `sql/migration_phase21_packaging_on_hand_requests.sql`

## Verify

1. Many packaging rows → page header + stats fixed; table body scrolls
2. Detail modal with 10+ entries → modal height stable; activity scrolls
3. HR sidebar: Requests outside Inventory dropdown
4. HR Requests page: packaging count request; no honey corrections section

## Related problems

- [[records/2026-06-11_packaging-materials-ui-on-hand]]
- [[records/2026-06-05_packaging-lot-stock-in]]
