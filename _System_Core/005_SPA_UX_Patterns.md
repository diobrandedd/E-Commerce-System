# SPA UX Patterns

> When to use which modal, toast, animation, and feedback pattern. Auto scan: [[generated/spa/151_Components_Index]] · [[generated/spa/152_Motion_Patterns]]

## Golden rules

1. **Always call `useReducedMotion()`** from `motion/react` when adding Framer Motion animations — disable motion when user prefers reduced motion.
2. **Never invent a fourth modal style** for a domain that already has a shared primitive (see table below).
3. **Feedback after user actions** — successful mutations should show toast, burst, or inline banner; errors use red banner or `FeedbackBurst kind="error"`.
4. **Exec/inventory/CSR pages** → wrap in `PageLayout`. **POS** → use `posMotion` kit only.

## Modal patterns — pick one

| Context | Use | File |
|---------|-----|------|
| **POS checkout / add item** | `POSModal` | `components/pos/posMotion.tsx` |
| **Costing CRUD** | `ModalShell` + `ModalFooter` + `Field` + `Banner` | inline in `pages/exec/CostingPage.tsx` |
| **Honey corrections review** | `CorrectionReviewModal` | `components/honey/CorrectionReviewModal.tsx` |
| **Honey executive row edit** | `ExecutiveRowEditModal` | `components/honey/ExecutiveRowEditModal.tsx` |
| **Supplier profile** | `SupplierProfileModal` | `components/suppliers/SupplierProfileModal.tsx` |
| **Generic / future exec forms** | Radix `Dialog` from `components/ui/dialog` OR copy `ModalShell` pattern | — |
| **Avoid for new code** | Raw `fixed inset-0 z-50 bg-black/40` inline modals | legacy: `InventoryRequestsPage` `NewRequestModal` |

`POSModal` includes animated backdrop, `role="dialog"`, scale/spring entrance, click-outside close.

`ModalShell` (costing) is static (no motion) but matches card styling — good for data-heavy forms.

When adding a **new** modal in honey/exec/inventory, prefer extracting a named component (like existing honey modals) over inline JSX.

## Toast & transient feedback

| Pattern | Component | Duration | Used in |
|---------|-----------|----------|---------|
| Bottom pill toast | `OrderRemarksToast` | 2.5–3s (`setTimeout` clear) | CSR Order Remarks, PDF→Excel |
| Full-screen burst | `FeedbackBurst` success/error | ~700ms (+ optional `onDone`) | POS honey add-to-cart |
| Button flash | `ActionFeedbackButton` | ~420ms icon swap | POS actions |
| Inline banner | `Banner` ok/err | persistent until dismiss | Costing modals |

**CSR toast recipe** (copy this):

```tsx
const [toast, setToast] = useState<string | null>(null);
// on success:
setToast("Done — …");
window.setTimeout(() => setToast(null), 2800);
// in JSX:
<OrderRemarksToast message={toast} />
```

## Motion libraries by domain

| Domain | Module | Key exports |
|--------|--------|-------------|
| **POS** | `posMotion.tsx` | `POSModal`, `FeedbackBurst`, `AnimatedQuantity`, `VariantCardMotion`, `CartLineMotion`, `springSnappy`, `springSoft` |
| **CSR tools** | `orderRemarksMotion.tsx` | `OrderRemarksToast`, `OrderRemarksSectionEnter`, `OrderRemarksAnimatedCount`, carousel transitions |
| **Costing** | `costingMotion.tsx` | `CostingCollapsible`, `CostingDetailReveal`, `costingSpring`, `costingEase` |

Import motion from **`motion/react`** (not `framer-motion`).

Springs used consistently: stiffness 400–520, damping 30–34. Page enters often use ease `[0.22, 1, 0.36, 1]`.

## POS-specific UX

- Use `PressableRoundButton` / `VariantCardMotion` for tappable tiles — includes `whileTap` scale.
- Hide fixed checkout bar while add modal is open (see `POSHoneyPage`).
- `FeedbackBurst` on successful add; error burst on validation failure.
- `POSPageTransition` wraps page content for route-level fade/slide.

## CSR-specific UX

- Ambiguous order flow uses carousel + animated counts (`AmbiguousOrderCarousel`, `OrderRemarksAnimatedCount`).
- Section stagger via `OrderRemarksSectionEnter`.
- All-done states use scale-in panel (`OrderRemarksAllDonePanel`).

## Honey / inventory UX

- Correction workflow: list on `InventoryRequestsPage` → `CorrectionReviewModal` for approve/reject.
- Executive inline edit: `ExecutiveRowEditModal` on warehouse/production/packaging pages.
- Traceability: `BatchTraceabilityDrawer` (drawer, not modal).

## Auth & loading states

- `RequireAuth` shows centered “Checking session…” on `bg-background` while loading.
- Forbidden role: full-screen message, no sidebar leak.
- POS mode redirects non-POS routes to `/pos`.

## Accessibility checklist for new UI

- Modals: `role="dialog"`, `aria-modal="true"`, backdrop click / X to close
- Toasts: `role="status"`, `aria-live="polite"` (`OrderRemarksToast` already does this)
- Icon-only buttons: `aria-label`
- Respect `useReducedMotion()` — set `initial={false}` or skip animation when true

## Before adding new UI

1. Read [[004_SPA_Design_System]] for colors/layout
2. Check [[generated/spa/151_Components_Index]] — does a sibling page already solve this?
3. Reuse domain `*Motion.tsx` exports instead of new one-off animations
4. Regenerate memory bank after adding new shared components
