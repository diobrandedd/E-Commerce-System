# Memory Bank Generator

Auto-generated Obsidian docs live in `generated/`. **Do not hand-edit files there.**

## Commands

From repo root (Windows XAMPP):

```powershell
c:\xampp\php\php.exe tools/memory_bank/generate_all.php
c:\xampp\php\php.exe tools/memory_bank/impact.php honey_warehouse_stock_in
c:\xampp\php\php.exe tools/memory_bank/impact.php honey_warehouse_stock_in --write
```

If `php` is on PATH:

```bash
php tools/memory_bank/generate_all.php
composer memory-bank
```

## Vault layout

| File | Purpose |
|------|---------|
| [[000_System_Architecture]] | Manual — stack, rules |
| [[001_System_State]] | Manual — current sprint |
| [[002_Memory_Bank]] | Manual — change log with impact |
| [[003_Fragile_Edges]] | Manual — traps scanners miss |
| [[004_SPA_Design_System]] | Manual — colors, layout |
| [[005_SPA_UX_Patterns]] | Manual — modals, toasts, motion |
| [[006_AI_Directed_Engineering]] | **Workflow** — theory → plan → implement |
| [[010_Product_Theory]] | Manual — what the product should be |
| `specs/` | Feature theory (you write) |
| `plans/active/` | Approved implementation plan (AI executes) |
| `plans/archive/` | Completed plans |
| `generated/` | Auto — API, DB, PHP includes |
| `generated/spa/` | Auto — routes, theme, motion, UI usage |

## Workflow

**AI-directed engineering:** see [[006_AI_Directed_Engineering]] — write spec → AI plan → approve → implement → log.

1. Before editing a shared `includes/` function → run `impact.php <function>`
2. Make the code change
3. Regenerate → `generate_all.php`
4. Append impact summary to [[002_Memory_Bank]]

Optional: install pre-commit hook — `powershell -File tools/memory_bank/install-pre-commit.ps1`
