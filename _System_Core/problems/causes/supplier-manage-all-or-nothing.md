---
date: 2026-11-10
type: cause
status: documented
area: suppliers
tags: [cause]
---

# Cause: Supplier write access was a single manage permission

`suppliers.manage` gated create, update, activate, deactivate, and purchase-price edits in both the API and Supplier Master UI. There was no narrower grant for create-only users.
