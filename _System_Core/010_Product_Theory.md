# Product Theory — Pyx Tracker

> **You maintain this.** It describes what the system *should be* — not how files are laid out.  
> Technical how-to: [[000_System_Architecture]] · Workflow: [[006_AI_Directed_Engineering]]

## Vision

Pyx Tracker is the internal operations hub for **Finest Wellness PH**: one place for inventory/production, returns (RTS), sales/POS, suppliers, and CSR fulfillment tools (waybill stamp, PDF→Excel, order remarks).

Staff should complete daily work **without switching between disconnected spreadsheets and ad-hoc scripts**.

## Who uses it

| Role | Primary jobs |
|------|----------------|
| **Executive** | Oversight, staff, suppliers, **Product Costing (only role)**, settings, material request approval, full packaging/supplies control |
| **HR** | **Packaging stock-in** (purchase lots), supplies stock-in + issue requests, inventory requests, RTS read-only |
| **Production manager** | Dept production (produce, BOM preview), **packaging deduct for use**, honey pipeline, RTS, deliveries — **no Product Costing** |
| **CSR TL / staff** | Waybill tools, RTS recording (legacy + SPA) |
| **POS** | Walk-in sales — honey + dept products (PT, BRC, Jar Cacao) |

## Domain principles (theory)

1. **Single source per workflow** — Inventory IA has four branches: **Raw Materials**, **Packaging Materials**, **Supplies**, and **Products** (finished goods). **Packaging Materials roles:** **HR** records purchase lots (total ₱ + qty); **production manager** **deducts for use** when materials go to the floor; **executive** can do all of that plus direct on-hand edits, pool yield, catalog, and full activity logs ([[fixes/packaging-inventory-roles]]). **Per-unit** vs **pool** packaging: per-unit counted in pieces; pool tracked in whole rolls with yield-based costing ([[specs/2026-06-05_packaging-production-deduct]]). Honey uses warehouse → production → packaging (gallons then bottles). **Paragis Tea** uses raw bags → WIP tea bags (derived from bags opened, not per-bag counting) → finished pouches ([[specs/2026-06-12_paragis-production-wip]] · [[fixes/paragis-use-raw-bags]] · [[fixes/dept-raw-material-guides]]). **BRC / Jar Cacao** stock in bags → single **Produce** step at sell unit ([[fixes/dept-raw-material-guides]]). **BRC / Jar Cacao** produce finished units from raw bags in one step. **Supplies:** org-wide HR pool — HR adds catalog items, stock-in lots, and **issue** requests (requester name + qty + reason); production manager view-only; executive set on-hand and approves/denies on **Requests** (`#supply-requests-section`) with notifications ([[fixes/supplies-hr-lot-inventory]] · [[specs/2026-06-12_supplies-hr-lot-inventory]]). **Product Costing** is executive-only. Honey/dept qty corrections and supply requests go through approval where specified; packaging on-hand corrections are executive direct set, not HR requests.
2. **RTS cases vs sellable inventory** — Record RTS captures cases (tracking, photos, claim, sellable + damaged/leak lines). After the barcode inventory cutover ([[specs/2026-08-25_rts-barcode-inventory-cutover]]), **sellable** RTS on-hand is **barcode-backed**: stock-in scan creates units in the RTS sellable pool; packing/POS consume by scan. Case sync no longer writes sellable qty. Damaged/leak remain on the case (and damaged pool sync) without requiring sellable barcode stock-in. SPA RTS inventory is not merely a mirror of case lines for sellable stock.
3. **CSR waybill chain** — One batch workflow: **prep tools** (VIP arranger, SPX merge, validate Excel) produce downloadable iPYX sheets; **PDF + Excel stamper** (separate tools-page entry) matches PDFs to a prepared sheet via `run_unified_stamp` → master Excel + parcels. All prep outputs use the same iPYX column layout.
4. **Financial traceability** — costing, supplier prices, and POS sales must be explainable from rows (audit-friendly). **Manufacturing COGS** (product formula) is separate from **fulfillment parcel packaging** (bubble, cushion, pouch, foam at ship). Departments: **Paragis Tea** vs **BRC + Jar Cacao** (`cacao_brc`). Parcel recipes follow ops spreadsheet — [[specs/2026-06-18_costing-parcel-packaging-session]].
5. **Role-appropriate UI** — each role sees only their actions on inventory pages; page **Guide** modals document role workflows ([[fixes/page-guide-button-modal]]); executives get breadth; POS gets speed and large touch targets; CSR gets batch tools.

## Non-goals (unless spec says otherwise)

- Public customer-facing storefront
- Multi-tenant SaaS
- Replacing legacy PHP overnight without migration specs

## Feature specs (theory documents)

New work starts as a note in `specs/`:

- [[specs/TEMPLATE_Feature_Spec]] — copy this template
- [[specs/2026-06-18_costing-parcel-packaging-session]] — costing hub + parcel tiers + Excel alignment (2026-06-18 session)
- [[specs/2026-06-17_fulfillment-packaging-recipes]] — tier matcher + SKU catalog
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
