# SPA UX Patterns

> When to use which modal, toast, animation, and feedback pattern. Auto scan: [[generated/spa/151_Components_Index]] · [[generated/spa/152_Motion_Patterns]]

## Golden rules

1. **Always call `useReducedMotion()`** from `motion/react` when adding Framer Motion animations — disable motion when user prefers reduced motion.
2. **Never invent a fourth modal style** for a domain that already has a shared primitive (see table below).
3. **Feedback after user actions** — successful mutations should show toast, burst, or inline banner; errors use animated red banner (`FulfillmentErrorBanner` / `OrderRemarksToast`).
4. **Hover on interactive UI** — clickable cards, table rows, and buttons get visible highlight + drop shadow on hover (see [[#Feedback animations & hover (mandatory)]]).
5. **Exec/inventory/CSR pages** → wrap in `PageLayout`. **POS** → use `posMotion` kit only.

## Modal patterns — `AppModal` is the standard

**All new center modals** use `AppModal` + `AppModalHeader` / `AppModalBody` / `AppModalFooter` from `components/AppModal.tsx`.

| Piece | Role |
|-------|------|
| `AppModal` | Backdrop `bg-black/50 backdrop-blur-[2px]` + card panel `rounded-2xl shadow-2xl ring-1 ring-black/5`; spring entrance; `useReducedMotion()` |
| `AppModalHeader` | Title (`h2`), optional subtitle, close button |
| `AppModalBody` | Scrollable content (`flex-1 overflow-y-auto`) |
| `AppModalFooter` | Actions row with top border + muted bg |

### Canonical recipe

```tsx
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";

<AppModal open={open} onClose={onClose} panelClassName="max-w-md w-full" labelledBy="my-modal-title">
  <AppModalHeader title="Title" titleId="my-modal-title" onClose={onClose} />
  <AppModalBody>{/* form / content */}</AppModalBody>
  <AppModalFooter className="justify-end">{/* Cancel / Save */}</AppModalFooter>
</AppModal>
```

**Never** add new inline `fixed inset-0` overlays for dialogs. Page-local `ModalShell` helpers should be thin wrappers around `AppModal` (see `CostingPage`).

### Domain-specific shells (built on `AppModal`)

| Context | Component | File |
|---------|-----------|------|
| **Default / exec / honey / inventory / deliveries** | `AppModal` (+ layout parts) | `components/AppModal.tsx` |
| **CSR / fulfillment (alias)** | `FulfillmentModal` (= `AppModal`) | `components/csr/onlineFulfillmentMotion.tsx` |
| **POS checkout / add item** | `POSModal` (same backdrop + panel tokens) | `components/pos/posMotion.tsx` |
| **Radix confirm / alert** | `AlertDialog` / `Dialog` — already aligned backdrop + panel | `components/ui/*.tsx` |
| **Side panel** | `BatchTraceabilityDrawer`, `Sheet`, `Drawer` — not center modals | — |

Named feature modals (`CorrectionReviewModal`, `DeliveryReviewEditModal`, `SupplierProfileModal`, etc.) compose `AppModal` internally — copy one when adding a new feature modal.

### Exceptions (not center modals)

- **POS receipt overlay** — `pointer-events-none` full-screen flash (`POSReceiptView`)
- **Saving spinner** — lightweight `fixed inset-0` without card (e.g. Deliveries ship busy state)
- **Mobile sidebar scrim** — `Sidebar.tsx`

## Sidebar navigation — sibling routes

When a nav group has a **parent path** and **child paths that extend it** (e.g. `/deliveries` + `/deliveries/review`), React Router `NavLink` **prefix-matches** by default — both links look active on the longer path.

**Rule:** Set `end: true` on the shorter/exact-only link in `Sidebar.tsx` `NavLinkChild`:

```tsx
{ label: "Add deliveries", to: "/deliveries", end: true },
{ label: "Review batches", to: "/deliveries/review" },
```

Pass through to `NavLink`: `end={child.end}` (undefined = default prefix match, fine for leaf routes).

**Also:** Auto-expand the parent group when `pathname.startsWith(parentPrefix)` so nested pages stay visible without manual toggle.

**Apply this pattern** whenever adding sibling routes under one prefix (Deliveries today; check Honey/CSR groups if similar bugs appear).

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
5. **Review gate (Shopee)** — `ShopeeItemsReviewPanel` lists **every row** (page, tracking, Original, Final item) before `run_shopee_resolve` — not only carousel-edited lines. Scrollable + sticky header for long batches; **Edit** returns to `ItemResolverCarousel` for that page.

**Components:**

| Pipeline | Carousel | Parent step |
|----------|----------|-------------|
| VIP remarks | `AmbiguousOrderCarousel` | `VipRunStep.tsx` |
| Shopee SPX | `ItemResolverCarousel` | `ShopeeSpxRunStep.tsx` |
| Standalone remarks | `AmbiguousOrderCarousel` | `OrderRemarksPage.tsx` |

### Shopee SPX packing list import

**When:** CSR has Shopee’s **`Packing List.Standard Local_*.xlsx`** (has `order_sn`, tracking, `product_info`) plus waybill PDFs (buyer **name** only on PDF).

**Extract step modes (`ShopeeSpxRunStep`):**

| Mode | Upload | Button |
|------|--------|--------|
| PDF only | Waybill PDFs | Extract from PDF |
| Packing list + PDF | `.xlsx` + waybill PDFs | Import packing list |

**Data merge:**

| Master column | Source |
|---------------|--------|
| Order No. | Packing list `order_sn` → `order_no` |
| Name | PDF extract, matched by tracking |
| Item/s | `product_info` parser (+ variant carousel if needed) |
| Courier / Tracking | Packing list |

**B2T1 UX:** Carousel title **“Pick Light / Amber / Dark for free 350ml”**; confirm merges into `3L-HONEY (LAD) + 350ml (L)` not bare `Light 350ml`.

**API:** `onlineOrdersSpxImportPackingList(excelToken, pdfTokens)` → same resolve/review/stamp phases as PDF-only.

**Helpers:** `shopeePresetToResolvedItemsLine`, `variantPickTitle` in `honeyItemVariant.ts`.

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

## Photo upload performance (SPA + API)

> **Mandatory for camera captures** (Deliveries packing photos, supplier ID photos, any mobile `capture="environment"` flow).

### The problem pattern

Uploading **full phone resolution** on a final **Confirm** button causes multi-second waits: large multipart body + sequential POST → GET refresh → second POST chains feel broken on warehouse Wi‑Fi.

### Standard recipe (copy Deliveries)

| Layer | Rule |
|-------|------|
| **Client compress** | `compressDeliveryPhoto()` — max edge **1280px**, JPEG **~0.82**; skip if already <500 KB JPEG (`app/src/app/lib/compressDeliveryPhoto.ts`) |
| **Eager upload** | Start `upload_photo` (or domain equivalent) **when file is selected/captured**, while user still reviews — not on Done/Save |
| **Confirm step** | Final button only runs **remaining mutation** (e.g. `ship_parcel`); `await` in-flight upload promise if user taps early |
| **Counts / tallies** | Use fields returned by upload API (`batch_item_count`) — **no** extra `refreshBatch` GET after each save |
| **Stale uploads** | Generation counter (`uploadGenRef`) — retake/cancel bumps gen so late responses are ignored |
| **Server hint path** | When client already sent metadata (`client_hint`, tracking, IDs), PHP must **not** `file_get_contents` the image for QR/OCR |

### Deliveries reference implementation

- **File:** `DeliveriesPage.tsx` — `startEagerUpload`, `ensurePhotoUploaded`, `photoUploadState` (`compressing` \| `uploading` \| `ready` \| `error`)
- **API:** `deliveriesUploadPhoto` returns `batch_item_count`; `deliveries_upload.php` hint-only classify path

### AI checklist (before shipping new photo flows)

1. Is upload deferred until confirm? → **Move earlier** + compress.
2. Does confirm trigger upload **and** ship **and** list refresh? → **Drop redundant GETs**; split so confirm does one job.
3. Does PHP re-decode image when hint exists? → **Skip binary read**.
4. Reuse `compressDeliveryPhoto` or same constants — do not invent per-page compression.

### Deliveries (prod_manager)

**Who:** **`prod_manager`** and **`executive`** only — CSR does not see Deliveries in sidebar.

**Routes:** `/deliveries` (scan label → take packing photo → ship) · `/deliveries/review` (batch review / edit requests / executive delete).

**Sidebar:** **Add deliveries** uses `end: true` — see [[#Sidebar navigation — sibling routes]].

**Standard flow:**

1. CSR finalizes Online Orders batch → parcels appear as `packing`.
2. PM opens **Deliveries** → pick business date → **Add deliveries** → scan QR or type tracking.
3. Upload/take **photo** (required before Done / Scan next) — **compresses + uploads in background** on capture; retake cancels in-flight upload via `uploadGenRef`.
4. **Done / Scan next** → `ship_parcel` only (photo usually already saved); packaging stock deduct; parcel → `shipped`.
5. CSR / prod view **Online parcels** detail — packing photo from deliveries enrich.

**Add deliveries feedback (fulfillment motion):**

| Action | Component |
|--------|-----------|
| Scan next (keep batch open) | `FulfillmentToast` bottom pill |
| Done (parcel shipped) | `FulfillmentSuccessPanel` green state |
| Step changes (idle → preview → success) | `FulfillmentStepSwitch` |
| Errors | `FulfillmentErrorBanner` |
| CTAs | `FulfillmentPressable` |

**Do not** add custom `fixed inset-0` success overlays on Deliveries — reuse `onlineFulfillmentMotion.tsx` (same kit as Online Orders / Online Parcels).

**Review batches UX:**

- Table stays **compact** (thumbnail, tracking, status, actions).
- **View** → `AppModal` with full detail: tracking, recipient, items, order no., courier, parcel status, packing photo.
- API `review_list` returns `photo_url` + optional `parcel` via `deliveries_enrich_review_rows()`.
- PM submits **edit/delete requests** on **Requests → Edit requests** (see [[#Requests hub (unified approvals)]]); executive **hard-deletes** mistaken rows (`delete_item`) or approves PM delete requests — last photo delete **reverts ship** + restores packaging stock.

**Duplicate scan:** `DeliveryDuplicateScanModal` when tracking already recorded for the date.

**Photo perf:** See [[#Photo upload performance (SPA + API)]] — `compressDeliveryPhoto.ts` + eager upload; do not regress to upload-on-Done or full-size camera files.

**Login default:** PM lands on `/dashboard` — Deliveries is in sidebar (truck icon), not the home route.

## Requests hub (unified approvals)

**Route:** `/inventory/requests` · Sidebar label **Requests**.

**Not** inventory-only — use **sectioned tables** so new approval types plug in without new nav items.

### Sidebar placement by role

| Role | Where **Requests** lives |
|------|-------------------------|
| **Executive** | Under **Inventory** children (badge on Inventory when pending) |
| **Prod manager** | Under **Inventory** children |
| **HR** | **Top-level** sibling after Inventory (`ClipboardList` icon) — not nested in Inventory dropdown |

HR page shows **Packaging & supplies adjustments** only (honey corrections section hidden). **Packaging count request** button opens `PackagingOnHandRequestModal`.

| Section | API | Who acts |
|---------|-----|----------|
| **Inventory corrections** | `honey.php` `correction_list` | Exec reviews in `CorrectionReviewModal`; PM submits — **hidden for HR** |
| **Packaging & supplies adjustments** | `materials.php` `request_list` | Exec approves; PM supply add/deduct; HR packaging `set_on_hand` |
| **Edit requests** | `edit_requests.php` `list` / `approve` / `reject` | Exec approves inline; PM/TL/staff submit (`delivery_batch_items`, `stock_entries`, …) |
| **POS discount approvals** | `pos_discounts.php` `list` | Exec only |

### Edit requests section

- **Component:** `EditRequestsSection.tsx` — shared by Requests hub + standalone `/edit-requests` (TL/staff).
- **Categories:** `formatEditRequestCategory()` — **Deliveries** (`delivery_batch_items`), **Inventory**, **Products**.
- **Deliveries review:** PM delete/tracking fix → `submitEditRequest` → executive sees **Deliveries** row on **Requests → Edit requests**.
- **Deep links:** `#edit-requests-section`, `#inventory-corrections-section`, `#discount-approvals-section`.
- **Redirects:** executive + prod_manager hitting `/edit-requests` → `/inventory/requests#edit-requests-section`.
- **Notifications:** exec bell includes pending `edit_requests` (with corrections + discounts).

### AI rules for new request types

1. Add a **section** on Requests hub (or row category in `EditRequestsSection`) — avoid new sidebar pages.
2. Backend: prefer `edit_requests` table + `ER_ALLOWED_TABLES` + apply handler pattern when exec must approve.
3. Include pending count in `CorrectionNotificationsContext` if exec must act promptly.

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

## Packaging and materials inventory pages

**Canonical reference:** [[fixes/packaging-inventory-scroll-and-detail-ux]] · live in `PackagingMaterialsPage.tsx` + `PackagingMaterialDetailModal.tsx`.

### List page recipe

1. **Summary cards** — `shrink-0` grid above the table (catalog count, in-stock count, totals).
2. **Viewport-bound column** — outer wrapper `flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem]` so the page does not grow past the viewport.
3. **Table card** — `flex flex-col min-h-0 flex-1 overflow-hidden`; column headers `shrink-0`.
4. **Scrollable body only** — `flex-1 min-h-0 overflow-y-auto overscroll-contain` around rows.
5. **Clickable rows** — full-width `<button>`, `hover:bg-secondary/40`, chevron; opens detail modal (not inline actions in the table).

Apply the same scroll split when refactoring **Supplies** (`MaterialsCategoryPage`) or other long material catalogs.

### Detail modal recipe

1. `AppModal` `max-h-[90vh]`; `AppModalBody` with `flex flex-col min-h-0 overflow-hidden` (override default body scroll).
2. Metrics + action buttons: `shrink-0`.
3. **Recent activity** (or audit log): inner `max-h-44 overflow-y-auto` — never let the modal body grow unbounded.
4. Role actions in modal: **Stock in lot** (exec/HR), **Set on-hand** (exec), **Request count fix** (HR).

### Material modals (inventory folder)

| Modal | Use |
|-------|-----|
| `PackagingStockInLotModal` | Total ₱ + qty received → unit cost preview |
| `PackagingOnHandSetModal` | Executive absolute count |
| `PackagingOnHandRequestModal` | HR target count + reason |
| `PackagingMaterialDetailModal` | Row detail shell + recent entries |
| `MaterialAdjustModal` | Supplies add/deduct (exec) or request (PM) |

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
