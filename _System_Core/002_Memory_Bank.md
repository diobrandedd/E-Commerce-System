# Project Change Log & Memory Bank

## 📅 Recent Log Entries

### 2026-06-04 - AI-directed engineering workflow in Obsidian
- **What we did:** Added `006_AI_Directed_Engineering.md`, `010_Product_Theory.md`, `specs/` + `plans/` templates, Cursor rule `ai-directed-engineering.mdc`, updated `001_System_State` with Active Spec/Plan pointers.
- **Why:** Separate theory (you) → plan (AI, approved) → implement (AI) so work is not lost in chat sessions.

### 2026-06-04 - SPA design system & UX patterns in memory bank
- **What we did:** Added `004_SPA_Design_System.md`, `005_SPA_UX_Patterns.md`, SPA auto-scanners (`generated/spa/`: routes, components, motion, theme), and `.cursor/rules/spa-ui-design.mdc`.
- **Why we did it:** Keep AI-generated UI consistent with existing colors, PageLayout, modals, and feedback animations.
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### 2026-06-04 - Automated Memory Bank generators
- **What we did:** Built `tools/memory_bank/` scanners that regenerate `_System_Core/generated/` (API index, include graph, DB table map, per-module caller index, frontend API clients). Added `impact.php` CLI, `003_Fragile_Edges.md`, Cursor rules, and composer `memory-bank` script.
- **Why we did it:** Give AI agents up-to-date dependency maps so code changes do not break unrelated features weeks later.
- **Key files:** `tools/memory_bank/generate_all.php`, `tools/memory_bank/impact.php`, `.cursor/rules/memory-bank-always.mdc`
- **Regenerate:** `c:\xampp\php\php.exe tools/memory_bank/generate_all.php`

### [Today's Date] - System Initialization
- **What we did:** Connected Obsidian to Cursor and built the System Memory Core.
- **Why we did it:** Prevent Cursor from forgetting architectural rules, tech stack differences (React/PHP/Python), and endpoint patterns.
- **Key decisions:** Established `_System_Core` as the single source of truth.

### 2026-06-02 - PDF Extraction Bug Fix (`pdf-to-excel/extractor.py`)
- **What we did:** Fixed tracking extraction reliability for OCR-heavy waybills in `pdf-to-excel/`.
- **Technical changes:**
  - Replaced simple first-match regex scanning with **priority-based matching**: `TN_PATTERNS` now uses `(priority, pattern)` tuples.
  - Added high-confidence numeric patterns for Lazada/J&T first: `8201...` and `820...` (priority 20).
  - Added broader carrier coverage: `FE...` (Flash), plus existing `SPEPH`, `SPX`, `JT`, `PH`, and marketplace `P...`.
  - Implemented `_valid_marketplace_parcel_id()` to reject OCR garbage for `P...` IDs (requires at least 2 digits), preventing false positives from vertical text noise.
  - Updated `find_tracking()` to evaluate all candidate matches and return the **best-priority** valid TN instead of the first regex hit.
  - Expanded `detect_courier()` mappings for clearer downstream labeling: `Flash / Marketplace`, `Flash Express`, `Lazada / J&T`.
- **Why we did it:** OCR pages sometimes surfaced noisy `P...` strings before the real Lazada numeric TN, causing incorrect extraction and downstream no-item matches.
- **Result:** PDF extraction now prefers correct TN candidates on mixed-carrier/OCR pages and reduces false matches from OCR artifacts.
