---
status: approved
created: 2026-11-26
approved_by: chat
---

# Feature Spec: HR manager approvals (replace administrator password)

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

Sensitive stock overrides (Check Inventory confirm, leaf-pool edit, Packaging undo) require a shared administrator password. That is hard to audit and easy to share. Ops want a named **HR manager** to approve those edits by email + in-app action instead.

## Users & roles

- **Executive:** configures HR manager + Gmail SMTP in Settings; may submit override requests.
- **HR manager (selected user):** receives email; logs in; Approve / Reject on Approvals page; when they perform the edit themselves, only an “Are you sure?” confirm modal (no email, no password).
- **Other staff** with feature permissions: submit for approval; cannot apply directly.

## Desired outcome

- [ ] Administrator password removed from Settings and the three override APIs.
- [ ] Settings: select HR manager user + approval email; Gmail SMTP + test send.
- [ ] Non–HR manager submit → pending request + email link to `/approvals?id=N`.
- [ ] HR manager Approve / Reject after login (confirm modal); Apply re-validates and may mark `failed`.
- [ ] HR manager doing the edit themselves → confirm modal → apply now.

## Behavior

1. Who submits: same permission gates as today for each feature.
2. Who approves: only the configured HR manager user (email link never applies alone).
3. HR manager not set → clear error on submit.
4. Packaging undo: if line qty changed since request, approve marks `failed`.
5. Email failure does not delete the pending request (bell / Approvals still work).

## Out of scope

- Packaging catalog remove password (separate).
- Legacy delivery_resolve user-password delete.
- Changing who can open Check Inventory / leaf pool / Packaging undo pages.

## Acceptance

- All three features use HR approval or HR-self confirm; no admin password UI/API.
- Test email works when Gmail app password is set.
- Stale Packaging undo fails cleanly on approve.
