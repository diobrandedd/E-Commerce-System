---
date: 2026-09-11
type: fix
---

# Fix: Private sessions + counted-only compare + merge history

1. Phase 80 drops date-only unique; open session is per `opened_by`.
2. Compare builds only audited > 0 keys; optional sticky batch metadata.
3. History scoped to owner without view_all; view_all multi-select merge compare.
