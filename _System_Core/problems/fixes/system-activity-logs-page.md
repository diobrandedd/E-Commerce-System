---
date: 2026-11-11
type: fix
status: applied
area: system-logs
tags: [fix]
---

# Fix: system_activity_log + Logs page

Add `system_activity_log`, soft-fail `system_activity_log_write` mirrors at high-value choke points, `logs.view` permission, and SPA `/logs` with category filter, search, dates, and pagination.
