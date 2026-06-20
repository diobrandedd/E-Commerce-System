# Fix: Dept raw vs finished inventory split

## Pattern

- **Raw Materials** (`/inventory/raw-materials/{paragis|black-rice|cacao}`): supplier bag stock-in; header **Guide** → [[fixes/dept-raw-material-guides]]; **Paragis** Use raw bags → Pouch (see [[fixes/paragis-use-raw-bags]]); BRC/Jar single Produce.
- **Products** (`/inventory/products/{paragis-tea|black-rice-coffee|cacao-tablets}`): finished on-hand counts only; prod manager does not stock in here.

## Backend

- `dept_raw_stock_in_bags()` — positive `stock_entries` only.
- `dept_raw_stock_out_bags()` — sealed bag waste only.
- Paragis production: see [[fixes/dept-production-wip-bom]] (`dept_pt_use_raw_bags`, `dept_pt_pouch`; advanced `dept_pt_make_tea_bags`).
- API: `dept_raw_detail`, `dept_raw_stock_in`, `dept_raw_stock_out`, `dept_pt_use_raw_bags`, `dept_production_preview`; `dept_raw_produce` deprecated (410).

## SPA

- `RawDeptMaterialPage.tsx` — operational raw page with modals.
- `DeptProductFinishedPage.tsx` — removed Stock in; links to raw materials.

## IA

| Sidebar | Purpose |
|---------|---------|
| Raw Materials → Dried Paragis / Black Rice / Cacao Beans | Raw bag ops |
| Products → Paragis Tea / BRC / Cacao Tablets | Finished counts |

## Gotcha

`dept_raw_recent_entries` must not reference `suppliers.display_name` (column does not exist). Use `supplier_display_name(first_name, last_name)` in PHP after the join.
