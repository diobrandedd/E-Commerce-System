---
date: 2026-11-26
type: fix
status: current
area: approvals
tags: [fix]
---

# Fix: HR manager email + in-app approvals

1. **Settings (executive):** pick HR manager user + approval email; configure Gmail SMTP (address, app password write-only, from-name, test send). Host/port fixed `smtp.gmail.com:587` TLS.
2. **Table** `override_approval_requests` (phase 100) stores pending/approved/rejected/failed with payload + summary.
3. **Branching APIs:** if caller is HR manager → apply now; else `override_approval_create` + email link to `/approvals?id=N`.
4. **`api/v1/approvals.php`:** list/get/approve/reject/pending_count/unread/mark_viewed. Approve locks row, re-validates, executes as original requester.
5. **SPA:** Approvals page, bell source for HR, `is_hr_manager` on auth, `OverrideConfirmModal` on the three features.
6. **Removed:** `admin_override_password.php`, Settings admin password block, `AdminOverridePasswordModal`.

## Related

- Spec [[specs/2026-11-26_hr-manager-approvals]]
- Record [[problems/records/2026-11-26_hr-manager-approvals]]
