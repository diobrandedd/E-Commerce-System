# Fix: Costing hub — products table + parcel recipes table

> Session detail: [[specs/2026-06-18_costing-parcel-packaging-session]]

## What shipped

1. **Products mode** — dept filter chips (Honey / Paragis Tea / BRC & Cacao / Other); rows show COGS + ref. pack + landed; click opens `CostingProductDetailModal` with tabs: Manufacturing, Reference packaging, Try an order.
2. **Parcel recipes mode** — full tier table by **dept bucket** (not raw `family`); order calculator at top; click recipe → `CostingParcelRecipeDetailModal` material breakdown.
3. **Reference addon** — `parcel_pack_reference_addon_for_product()` returns `tier_key` for 250ml/350ml/liter/gallon honey and solo PT/BRC/cacao tiers.

## Design choices

- Hub mode toggle (`products` | `recipes`) on `CostingPage.tsx` — parcel rules editing stays in **Edit tier rules** modal.
- Landed column uses **solo reference tier** only; combos require Parcel recipes or product modal order calculator.
- Motion via `appFeedbackMotion` per [[005_SPA_UX_Patterns]].

## Errors during build

- Accidental deletion of `guideOpen` / `hubMode` state during CostingPage refactor — restored.
- PowerShell: use `;` between commands, not `&&`.

Tier rule editing remains in `ParcelPackTiersModal`.
