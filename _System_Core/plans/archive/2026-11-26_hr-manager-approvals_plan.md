---
status: done
spec: "[[specs/2026-11-26_hr-manager-approvals]]"
created: 2026-11-26
approved_by: chat
completed: 2026-11-26
---

# Implementation Plan: HR manager approvals

> Spec: [[specs/2026-11-26_hr-manager-approvals]]

## Summary

Replace administrator override password with HR-manager email + in-app Approve/Reject for inventory check finalize, dept leaf-pool set, and packaging scan undo. Add SMTP mailer, `override_approval_requests`, Approvals SPA, Settings blocks.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | `approvals.php`; settings HR/mail; rewire inventory_check / honey / bottle_barcodes |
| Includes | `mailer.php`, `override_approvals.php`; retire admin_override_password callers |
| DB | phase 100 `override_approval_requests` |
| SPA | Settings, ApprovalsPage, OverrideConfirmModal, bell, auth `is_hr_manager` |

## Approval

- [x] Human approved — OK to implement (chat + plan attach)
- Approved date: 2026-11-26
