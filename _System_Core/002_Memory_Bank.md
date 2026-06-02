# Project Change Log & Memory Bank

## 📅 Recent Log Entries

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
