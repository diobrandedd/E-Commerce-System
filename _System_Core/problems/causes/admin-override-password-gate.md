---
date: 2026-11-26
type: cause
status: superseded
area: approvals
tags: [cause]
---

# Cause: Shared administrator override password

Sensitive inventory edits (Check Inventory finalize, leaf-pool set, Packaging undo) gated on a single hashed password in `app_settings` (`security.admin_override_password_hash`) via `api_require_admin_override_password`. Anyone with the password could apply changes with no person-level approval trail or email notice.

Superseded by HR manager approvals (2026-11-26).
