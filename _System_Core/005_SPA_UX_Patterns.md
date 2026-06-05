# SPA UX Patterns

> When to use which modal, toast, animation, and feedback pattern. Auto scan: [[generated/spa/151_Components_Index]] · [[generated/spa/152_Motion_Patterns]]

## Golden rules

1. **Always call `useReducedMotion()`** from `motion/react` when adding Framer Motion animations — disable motion when user prefers reduced motion.
2. **Never invent a fourth modal style** for a domain that already has a shared primitive (see table below).
3. **Feedback after user actions** — successful mutations should show toast, burst, or inline banner; errors use animated red banner (`FulfillmentErrorBanner` / `OrderRemarksToast`).
4. **Hover on interactive UI** — clickable cards, table rows, and buttons get visible highlight + drop shadow on hover (see [[#Feedback animations & hover (mandatory)]]).
5. **Exec/inventory/CSR pages** → wrap in `PageLayout`. **POS** → use `posMotion` kit only.

## Modal patterns — pick one

| Context | Use | File |
|---------|-----|------|
| **POS checkout / add item** | `POSModal` | `components/pos/posMotion.tsx` |
| **Costing CRUD** | `ModalShell` + `ModalFooter` + `Field` + `Banner` | inline in `pages/exec/CostingPage.tsx` |
| **Honey corrections review** | `CorrectionReviewModal` | `components/honey/CorrectionReviewModal.tsx` |
| **Honey executive row edit** | `ExecutiveRowEditModal` | `components/honey/ExecutiveRowEditModal.tsx` |
| **Online Orders duplicate TN** | `DuplicateTrackingModal` | `components/csr/onlineOrders/DuplicateTrackingModal.tsx` |
| **Online parcel detail** | `OnlineParcelDetailModal` | `components/fulfillment/OnlineParcelDetailModal.tsx` |
| **Online Orders / Parcels motion shell** | `FulfillmentModal` | `components/csr/onlineFulfillmentMotion.tsx` |
| **Supplier profile** | `SupplierProfileModal` | `components/suppliers/SupplierProfileModal.tsx` |
| **Generic / future exec forms** | Radix `Dialog` from `components/ui/dialog` OR copy `ModalShell` pattern | — |
| **Avoid for new code** | One-off inline `fixed inset-0` modals | legacy: `InventoryRequestsPage` `NewRequestModal` — use `FulfillmentModal` for CSR/fulfillment instead |

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

### CSR variant resolution (ambiguous honey)

**When:** PDF or VIP Excel shows honey **size only** (e.g. `1 x 1L HONEY w/SF`) — packers need Light/Amber/Dark before stamp or master Excel.

**Standard flow (copy for new pipelines):**

1. **Carousel** — one card at a time; `VariantCardStrip` for presets; optional custom text.
2. **Confirm inline** — preset/custom does **not** commit until user taps **Yes, correct** (Shopee) or resolution is complete with `finalRemark` (VIP).
3. **Show completed** — parent owns `showCompleted` state; pass `onShowCompletedChange={setShowCompleted}`. **Never** hardcode `false` or no-op handler.
4. **Edit** — `onClearResolution(idx|page)` removes entry from `resolutions` state; card returns to picker. VIP: **Edit choice**; Shopee: **Edit item**.
5. **Review gate (Shopee)** — optional table step before API `run_shopee_resolve` so user scans all lines once.

**Components:**

| Pipeline | Carousel | Parent step |
|----------|----------|-------------|
| VIP remarks | `AmbiguousOrderCarousel` | `VipRunStep.tsx` |
| Shopee SPX | `ItemResolverCarousel` | `ShopeeSpxRunStep.tsx` |
| Standalone remarks | `AmbiguousOrderCarousel` | `OrderRemarksPage.tsx` |

**Readiness helpers:** `isRowFullyResolved` (VIP, `orderRemarksParse.ts`) · `shopeeRowIsReadyForExport` / `shopeeFinalItemsForRow` (Shopee, `honeyItemVariant.ts`).

**Toast on progress:** `OrderRemarksToast` + `onCardCompleted(remaining)` or finalize-phase toast.

### CSR variant picker stock indicators

**When:** CSR resolves ambiguous honey variant in **Shopee SPX** (`ItemResolverCarousel`), **VIP** (`AmbiguousOrderCarousel` in `VipRunStep`), or **Order Remarks** (same carousel).

**UX rule:** Each `VariantPresetButton` shows variant label + **`{qty} left`** on a second line (bottled packaging only).

| Pipeline | Preset shape | Stock mapping |
|----------|--------------|---------------|
| Shopee | `Light 1L`, `Amber 350ml`, … | Direct variant + size |
| VIP 1L / free 350ml | `L`, `A`, `D` | Letter → light/amber/dark + liter or 350ml |
| VIP 2L / 3L combos | `LA`, `LAD`, … | **Min** 1L stock across letters in code |

**Data:** `GET api/v1/online_orders.php?action=packaging_stock` → `honey_packaging_total_inventory_snapshot()`. Use **`onlineOrdersPackagingStock()`** / **`useHoneyPackagingStock()`** — not `honey.php` (CSR roles lack honey view permission).

**Enrichment helpers:** `enrichShopeePresets`, `enrichVipPresets` in `honeyPackagingStock.ts`; attach to `PresetOption.stockLeft` before passing to `VariantCardStrip`.

**Scroll chevrons:** `OrderRemarksScrollButton` overlays preset strip — keep **translucent** (`opacity-55`, `bg-card/45`) so stock counts remain readable; full opacity on hover.

**Regression trap:** `ItemResolverCarousel` uses `cn()` for card borders — do not remove `cn` import when editing stock imports.

### Duplicate tracking conflict (Online Orders)

**When:** User stamps Partner, exports VIP, or confirms Shopee resolve with a tracking number already in another batch line or fulfillment parcel.

**UX rule:** **Modal only** — do not show the red inline error banner for duplicate conflicts. User should see tracking TN + existing contents immediately.

**Standard flow (copy for new pipelines):**

1. API call throws `ApiError` with `data.duplicates[]` (409 from backend).
2. `useDuplicateTrackingHandler()` → `handleApiError(e, fallback)` sets conflicts, opens modal, clears `error`.
3. `DuplicateTrackingModal` shows:
   - Highlighted **tracking number**
   - **Existing record** (recipient, items, courier, pipeline, batch # / parcel # / status)
   - **What you tried to add** (when different)
4. **OK** closes modal; no navigation away from pipeline step.

**Components / helpers:**

| Piece | File |
|-------|------|
| Modal | `DuplicateTrackingModal.tsx` |
| Hook | `useDuplicateTrackingHandler.ts` |
| Parse API payload | `duplicateTrackingUtils.ts` → `duplicateConflictsFromApiError()` |
| Types | `onlineOrdersTypes.ts` → `DuplicateTrackingConflict` |

**Parent steps using hook:** `PartnerRunStep`, `VipRunStep` (export), `ShopeeSpxRunStep` (resolve).

**Modal implementation:** Use `FulfillmentModal` from `onlineFulfillmentMotion.tsx` (animated backdrop + spring panel).

### Online parcel detail (packing photo)

**When:** User opens **`OnlineParcelDetailModal`** from Online Parcels table row or tracking lookup.

**Data rule:** Packing photos live on **`delivery_batch_items`**, not `online_order_parcels`. API enriches each parcel with optional `packing_photo`:

| Field | Meaning |
|-------|---------|
| `photo_url` | Public deliveries bucket URL (`/rts/api/storage_media.php?b=deliveries_documentation&f=…`) |
| `delivery_date` | Batch date when photo was captured |
| `delivery_item_id` | Same id required for `ship_parcel` |

**UX rule:**

1. **Has photo** → show image + optional delivery batch date caption.
2. **No photo yet** → dashed placeholder, package icon, text **“Not yet packed”** (not an error state).
3. Photo appears after prod uploads during Deliveries scan; refresh list or re-open modal to see update.

**Backend helpers:** `online_parcels_find_packing_photo`, `online_parcels_enrich_packing_photo`, `online_parcels_enrich_packing_photos_batch` in `includes/online_order_parcels.php`.

**Do not** duplicate photo storage on the parcel row — single source of truth is deliveries documentation bucket.

## Feedback animations & hover (mandatory)

**Applies to all new SPA features** (CSR, fulfillment, inventory, exec). When building UI, ship both **motion feedback** and **hover affordance** — not plain static blocks.

### Motion kit — Online Orders + Online Parcels

| Export | Use |
|--------|-----|
| `FulfillmentToast` | Success/info pill (bottom center) |
| `FulfillmentErrorBanner` | Animated error enter/exit |
| `FulfillmentSectionEnter` | Page section fade/slide in |
| `FulfillmentStepSwitch` | Wizard step crossfade |
| `FulfillmentSuccessPanel` | Batch complete / green state |
| `FulfillmentModal` | Detail / conflict modals |
| `FulfillmentPickerCard` | Pipeline picker tiles |
| `FulfillmentTableRow` | Clickable table rows (stagger + hover) |
| `FulfillmentPressable` | Primary/outline buttons |
| `AnimatedStat` | Count-up on stamp stats |

**File:** `app/src/app/components/csr/onlineFulfillmentMotion.tsx`  
**CSR variant carousels** still use `orderRemarksMotion.tsx`; **POS** still uses `posMotion.tsx`.

### Hover tokens (copy or use components)

```tsx
import {
  fulfillmentHoverCard,      // cards: ring + amber shadow
  fulfillmentHoverRow,       // table rows: bg + drop shadow
  fulfillmentHoverPressablePrimary,
  fulfillmentHoverPressableOutline,
} from "@/app/components/csr/onlineFulfillmentMotion";
```

| Element | Hover behavior |
|---------|----------------|
| **Pipeline / picker cards** | Lift (`y: -4`), amber ring, `shadow-lg shadow-[#C8900A]/12` |
| **Clickable table rows** | `bg-secondary/50` + `shadow-[0_4px_14px_-4px_rgba(0,0,0,0.14)]` |
| **Primary buttons** | Brightness + amber drop shadow; `whileTap` scale 0.97 |
| **Outline buttons** | `bg-secondary/60`, border tint, light shadow |

Prefer **`FulfillmentPressable`** / **`FulfillmentTableRow`** / **`FulfillmentPickerCard`** over raw `<button>` / `<tr>` so hover stays consistent.

### Checklist for new pages

1. Success action → toast or success panel animation  
2. Error → `FulfillmentErrorBanner` or domain equivalent  
3. Step/section changes → enter or step switch animation  
4. Every clickable row/card/button → hover highlight + shadow  
5. `useReducedMotion()` — disable motion when true  

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
