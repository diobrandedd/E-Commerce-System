---
status: approved
created: 2026-11-13
approved_by: chat
---

# Feature Spec: Supplies — assign stock-out scanners on page

## Problem

Assigning who may scan stock out / report damage required Staff → Permissions (`packaging.deduct`). Executives already see a read-only roster on Supplies and want to assign/remove there.

## Goals

1. Executive Assign / Remove on Supplies **Assigned stock-out staff** card.
2. Still grants/revokes `packaging.deduct` only (no new table); Staff Permissions stays in sync.
3. Candidates = active non-executives without the key.

## Non-goals

- New allowlist table; removing the permission from Staff Permissions; changing stock-in rules.

## Acceptance

- Executive can assign a staff member from Supplies; they can open Supplies scan-only.
- Remove chip revokes only `packaging.deduct`.
- Non-executives do not see assign UI.
