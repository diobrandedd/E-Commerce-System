# SPA Design System

> Manual design rules for the Pyx Tracker React app (`app/`). Auto token list: [[generated/spa/153_Theme_Tokens]] · Routes: [[generated/spa/150_Routes]]

## Brand & feel

Pyx Tracker uses a **warm wellness palette** — cream backgrounds, honey-gold accents, soft borders. The product should feel calm and operational, not flashy.

- **Font:** DM Sans (`PageLayout` sets `fontFamily: 'DM Sans', sans-serif`)
- **Base size:** 16px (`--font-size` in `app/src/styles/theme.css`)
- **Corner radius:** `--radius: 0.625rem` — cards/modals use `rounded-xl` or `rounded-2xl`

## Colors — always use CSS variables

**Do not hardcode hex in new components** except where legacy POS/costing buttons already use `#C8900A` inline (match existing files when editing nearby code; prefer tokens for new UI).

| Role | CSS var | Tailwind |
|------|---------|----------|
| Page background | `--background` `#fff9ee` | `bg-background` |
| Body text | `--foreground` `#1a1510` | `text-foreground` |
| Primary / CTA | `--primary` `#C8900A` | `bg-primary`, `text-primary` |
| Cards / panels | `--card` | `bg-card` |
| Muted labels | `--muted-foreground` | `text-muted-foreground` |
| Borders | `--border` | `border-border` |
| Errors | `--destructive` | `text-destructive`, `bg-destructive` |
| Focus ring | `--ring` | `ring-ring`, `focus:ring-[#C8900A]/10` |

Source of truth: `app/src/styles/theme.css` (light + `.dark` variants exist but production UI is mostly light).

## Layout shell

**Standard exec/inventory/CSR pages** must use `PageLayout`:

```tsx
import { PageLayout } from "@/app/components/PageLayout";

<PageLayout title="…" subtitle="…" actions={…}>
  {children}
</PageLayout>
```

Provides: sidebar, sticky header, notification bell (executive corrections), mobile menu.

**POS pages** (`/pos`, `/pos/honey`) do **not** use `PageLayout` — full-screen terminal UX with `POSHeader` and `posMotion` transitions.

## Component library

Shared primitives live in `app/src/app/components/ui/` (Radix + Tailwind, shadcn-style):

- `Button`, `Input`, `Select`, `Dialog`, `Sheet`, `Tabs`, `Table`, …
- Utility merge: `cn()` from `@/app/components/ui/utils`

**Prefer `ui/*` for new generic controls.** Domain-specific widgets go under `components/honey/`, `components/pos/`, `components/csr/`, etc.

## Forms & inputs

Costing page established the standard input class (reuse when adding forms in exec/inventory modals):

```text
rounded-lg border-2 border-border bg-card text-sm px-3 py-2.5
focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10
```

Labels: `text-xs font-medium text-muted-foreground uppercase tracking-wide`

Primary submit: gold background, white text, `rounded-lg`, disabled opacity ~70%.

## Status & banners

| Kind | Classes |
|------|---------|
| Success | `bg-green-50 border-green-200 text-green-700` (or `green-800`) |
| Warning | `text-amber-700`, `bg-amber-50` |
| Error | `bg-red-50 border-red-200 text-red-700` |

Use Lucide icons at 16–18px alongside short copy.

## Typography scale

Prefer Tailwind utilities over custom CSS:

- Page title (in PageLayout header): layout handles sizing
- Section headings: `text-sm font-semibold text-foreground`
- Helper text: `text-xs text-muted-foreground`
- Numeric tables: add `tabular-nums` for quantities/prices

## File organization

| Path | Purpose |
|------|---------|
| `app/src/app/pages/` | Route-level screens |
| `app/src/app/lib/` | API clients + display helpers |
| `app/src/app/components/` | Shared + domain UI |
| `app/src/app/context/` | Auth, notifications |
| `app/src/styles/` | theme, tailwind, fonts |

New feature = page + `lib/*.ts` client + backend `api/v1` (see [[000_System_Architecture]]).

## Related

- UX patterns (modals, motion): [[005_SPA_UX_Patterns]]
- Auto route map: [[generated/spa/150_Routes]]
- API wiring: [[generated/140_Frontend_API_Clients]]
