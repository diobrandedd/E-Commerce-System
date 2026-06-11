# Fix: Dept production WIP + formula-driven deduct

## Pattern

**Paragis (pt):**
- **Make tea bags** — staff enters tea bag count; deducts stock-linked formula lines at `make_tea_bags` stage (leaf ÷ 24, sachet ÷ 24 per tea bag); credits `dept_wip_rows`.
- **Pouch** — staff enters pouch count; deducts pouch packaging from formula; WIP −24×pouches; finished +pouches.
- **WIP waste** / **bag waste** — separate actions.

**BRC / Jar:** single **Produce** at sell unit; all stock-linked formula lines × qty.

**Custom formula lines** (`stock_id` NULL) never deduct inventory.

## Backend

- `includes/dept_production.php` — formula deduct, packaging deduct, WIP, production log.
- `sql/migration_phase26_dept_wip.sql` — `dept_wip_rows`, `dept_production_entries`, `PT tea bag sachet` stock + formula line.
- API: `dept_pt_make_tea_bags`, `dept_pt_pouch`, `dept_brc_produce`, `dept_jar_produce`, `dept_wip_waste`, `dept_production_preview`.

## SPA

- `RawDeptMaterialPage.tsx` — three stat cards for PT; Make tea bags / Pouch / waste modals with BOM preview.

## Gotchas

- PT **make_tea_bags** stage: leaf + any packaging except `PT pouch packaging` (sachet, yarn, etc.).
- Avoid duplicate tea-bag packaging on the PT formula (e.g. both `PT tea bag sachet` and `Paragis Tea Bag Yarn`).
- Run `migration_phase26_dept_wip.sql` before production actions.
