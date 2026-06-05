# System Architecture & Core Rules

## 🎯 Project Overview
- **Project Name:** Pyx Tracker (repo path uses `rts`)
- **Primary Goal:** Internal operations platform that unifies inventory/production workflows, RTS handling, sales/POS, supplier/AR tracking, and CSR waybill automation (stamping + PDF-to-Excel) in one system.
- **Tech Stack:** PHP 8 (session-based JSON APIs), MySQL/PDO, React 18 + TypeScript + React Router, Vite, Tailwind CSS, MUI/Radix UI, Python utilities (PyMuPDF, pdfplumber, openpyxl, pytesseract, pdf2image), Composer, npm.

## 🏗️ Folder Structure & Rules
- `_System_Core/` -> Holds system memory, state, and rules (Do not modify code here).
- `api/` -> Backend endpoints (mainly `api/v1`) using action-based JSON handlers.
- `app/` -> React SPA source, routing, UI components, and frontend API clients.
- `includes/` -> Shared PHP business logic (auth, storage, domain modules).
- `config/` -> Environment/config templates (DB, storage root, forwarding settings).
- `sql/` -> Database schema and migration scripts.
- `storage/` -> Runtime working storage for generated/uploaded processing files.
- `waybill-stamper/` -> Python waybill stamping engine.
- `pdf-to-excel/` -> Python/PHP PDF-to-Excel extraction workflow.
- `orders-editor/` -> Order remarks/cleanup standalone utility.
- `tools/` -> Diagnostic/maintenance scripts (e.g., SPA smoke tests).
- `auth/`, `exec/`, `dept/`, `deliveries/`, `prodmgr/`, `pos/`, `sp_sort/` -> Legacy PHP modules still used alongside SPA.
- `vendor/` -> Composer dependencies.
- `assets/` -> Static and uploaded asset files.

## ⚠️ Critical Rules (Never Forget)
- Use the standardized API response flow (`api_dispatch`, `api_ok`, `api_fail`) and action-based endpoint pattern; avoid ad-hoc endpoint styles.
- Enforce auth + CSRF on mutating requests; frontend must pass CSRF token and backend must validate it.
- Keep role checks consistent across backend and frontend route guards (normalized role mapping is expected).
- Follow domain modularity: add features in matching backend module + corresponding frontend page/API client, not as cross-cutting hacks.
- Use storage helpers/configured storage root for file IO; do not hardcode random filesystem paths in feature code.

## 🔗 Connected Memory Anchors
- **Workflow (theory → plan → build):** [[006_AI_Directed_Engineering]]
- **Product theory:** [[010_Product_Theory]]
- Current Active Tasks: [[001_System_State]]
- Project Change Log: [[002_Memory_Bank]]
- Problem graph (chat fixes): [[problems/000_Problem_Index]]
- Fragile / non-obvious traps: [[003_Fragile_Edges]]
- SPA design (colors, layout): [[004_SPA_Design_System]]
- SPA UX (modals, motion, feedback): [[005_SPA_UX_Patterns]]
- Auto-generated backend indexes: [[generated/100_API_Index]] · [[generated/130_Module_Index]] · [[generated/120_Database_Tables]]
- Auto-generated SPA indexes: [[generated/spa/149_SPA_Index]]
- Full project reference: [README.md](../README.md) (repo root)
- Regenerate indexes: `php tools/memory_bank/generate_all.php` (see [[README]])