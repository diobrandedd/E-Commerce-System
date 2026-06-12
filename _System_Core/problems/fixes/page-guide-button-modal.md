---
type: fix
tags: [fix, spa, ux, guide]
---

# Fix: Page guide = header button + modal (not inline panel)

## Rule

User-facing page guides use a **Guide** button in `PageLayout` actions that opens a scrollable `AppModal`. Do **not** embed collapsible guide panels in the page body.

## Canonical examples

| Page | Button | Modal |
|------|--------|-------|
| Product Costing | `CostingPage.tsx` header actions | `CostingGuideModal.tsx` |
| Packaging Materials | `PackagingMaterialsPage.tsx` header actions | `PackagingInventoryGuideModal.tsx` |
| Supplies | `SuppliesMaterialsPage.tsx` header actions | `SuppliesInventoryGuideModal.tsx` |
| Requests | `InventoryRequestsPage.tsx` header actions | `InventoryRequestsGuideModal.tsx` |

## Button classes (copy exactly)

```tsx
className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
```

`<BookOpen size={13} /> Guide`

## Modal recipe

- `useState(false)` — closed by default
- `AppModal` + `AppModalHeader` (title with BookOpen icon) + scrollable `AppModalBody`
- Role-specific sections via `useAuth().role` when needed (badge in title, e.g. “HR guide”)
- Render modal as sibling at end of page component (same as `CostingGuideModal`)
- **Forbidden routes:** only link pages the role can access (e.g. no `/costing` for HR or prod manager — see [[fixes/inventory-role-permissions]])

## Anti-patterns

- Inline accordion / `CostingCollapsible` guide on the page
- Guide toggle with gold/active border state
- Default-open guide taking vertical space from data views

## Docs

[[005_SPA_UX_Patterns#Page guide button (mandatory)]]
