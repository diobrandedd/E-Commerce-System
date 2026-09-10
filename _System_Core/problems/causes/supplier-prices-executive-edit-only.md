---
date: 2026-09-10
type: cause
---

# Cause: Supplier Master price APIs required honey.executive_edit only

Inline honey and dept bag price endpoints on Supplier Master called `api_require_permission('honey.executive_edit')`. **Manage suppliers** (`suppliers.manage`) was enough to create suppliers but not to load/save purchase prices, so the expand panel showed Forbidden.
