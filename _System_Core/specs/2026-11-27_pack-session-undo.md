---
date: 2026-11-27
type: feature-spec
status: implemented
area: deliveries-pack-scan
---

# Spec: Undo confirmed pack session (HR approval)

## Problem

Pack scan History is read-only. Mistaken confirmed sessions leave stock deducted with no way to reverse the whole pack back to packaging / RTS sellable / freebies.

## Solution

Executive **Undo session** on a History entry reverses the entire confirmed pack session:

- Unique honey/dept units must still be `packed` for this session (fail closed if shipped/sold/restocked).
- SKU lines and freebies are credited back.
- `honey_sales_log` rows for `pack_session_id` are deleted.
- Session status becomes `undone` (hidden from History).

Uses the same HR manager approval path as Packaging stock-in undo: HR applies now; others create a pending email request.

## Out of scope

- Per-line undo inside a session
- Restoring bottling packaging materials
- Undoing sessions whose units already left pack status

## Links

- Plan: [[plans/archive/2026-11-27_pack-session-undo_plan]]
- Related: [[specs/2026-11-26_hr-manager-approvals]] · [[specs/2026-11-24_packaging-stock-in-line-undo]]
