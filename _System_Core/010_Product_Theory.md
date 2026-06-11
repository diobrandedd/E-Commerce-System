# Product Theory — Pyx Tracker

> **You maintain this.** It describes what the system *should be* — not how files are laid out.  
> Technical how-to: [[000_System_Architecture]] · Workflow: [[006_AI_Directed_Engineering]]

## Vision

Pyx Tracker is the internal operations hub for **Finest Wellness PH**: one place for inventory/production, returns (RTS), sales/POS, suppliers, and CSR fulfillment tools (waybill stamp, PDF→Excel, order remarks).

Staff should complete daily work **without switching between disconnected spreadsheets and ad-hoc scripts**.

## Who uses it

| Role | Primary jobs |
|------|----------------|
| **Executive** | Oversight, staff, suppliers, costing, settings, corrections approval |
| **Production manager / HR** | Honey pipeline, dept inventory, RTS mirror, correction requests |
| **CSR TL / staff** | Waybill tools, RTS recording (legacy + SPA) |
| **POS** | Walk-in sales — honey + dept products (PT, BRC, Jar Cacao) |

## Domain principles (theory)

1. **Single source per workflow** — Inventory IA has four branches: **Raw Materials**, **Packaging Materials**, **Supplies**, and **Products** (finished goods). Honey uses warehouse → production → packaging (gallons then bottles). **Paragis Tea** uses raw bags → WIP tea bags → finished pouches with formula-driven material deduct ([[specs/2026-06-12_paragis-production-wip]]). **BRC / Jar Cacao** produce finished units from raw bags in one step. Packaging and supplies use `stocks.on_hand` with lot stock-in (WAC unit cost). **Packaging:** executive `set_on_hand`; HR count requests. **Supplies:** org-wide HR pool — HR stock-in lots + **issue** requests (requester name) for staff; executive approves on Requests. See [[specs/2026-06-12_supplies-hr-lot-inventory]]. **Paragis Tea**, **BRC**, and **Jar Cacao** each have BOM costing stocks — see [[specs/2026-06-05_paragis-tea-bom-math]] and related BOM notes. Honey/dept qty corrections and material requests go through approval, not silent DB edits.
2. **RTS operational data vs SPA mirror** — cases still originate in legacy record flow; SPA inventory is a **synced mirror** for sell/deduct, not a replacement for case capture without an explicit product decision.
3. **CSR waybill chain** — One batch workflow: **prep tools** (VIP arranger, SPX merge, validate Excel) produce downloadable iPYX sheets; **PDF + Excel stamper** (separate tools-page entry) matches PDFs to a prepared sheet via `run_unified_stamp` → master Excel + parcels. All prep outputs use the same iPYX column layout.
4. **Financial traceability** — costing, supplier prices, and POS sales must be explainable from rows (audit-friendly).
5. **Role-appropriate UI** — executives get breadth; POS gets speed and large touch targets; CSR gets batch tools.

## Non-goals (unless spec says otherwise)

- Public customer-facing storefront
- Multi-tenant SaaS
- Replacing legacy PHP overnight without migration specs

## Feature specs (theory documents)

New work starts as a note in `specs/`:

- [[specs/TEMPLATE_Feature_Spec]] — copy this template
- Active initiative: see [[001_System_State]] → **Active Spec**

## Relationship to code

| Theory (this file + specs/) | Reality (repo + generated/) |
|-----------------------------|-----------------------------|
| “Warehouse stock-in must require active supplier” | `honey_warehouse_stock_in`, `api/v1/honey.php` |
| “CSR toast after each resolved order” | `OrderRemarksToast`, [[005_SPA_UX_Patterns]] |

When theory and code disagree, **update theory first** (or document intentional drift in [[002_Memory_Bank]]), then plan a fix.

## Open product questions

_Use this section for decisions you have not finalized. AI should ask before planning._

- (none listed — add items as they come up)
