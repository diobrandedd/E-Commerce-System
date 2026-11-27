---
date: 2026-11-27
type: fix
status: current
area: deliveries-pack-scan
tags: [fix]
---

# Fix: Pack session undo + HR approval

1. Phase 101: `honey_bottle_pack_sessions.status` includes `undone`; `override_approval_requests` adds `pack_session_undo`.
2. `honey_bottle_pack_session_undo` restores unique units + pool/RTS/freebie credits, deletes `honey_sales_log` for the session, marks undone.
3. API `pack_session_undo` — executive; HR applies now / others pending.
4. Pack scan History: Undo session + `OverrideConfirmModal`.

## Related

- Spec [[specs/2026-11-27_pack-session-undo]]
- Record [[problems/records/2026-11-27_pack-session-undo]]
- HR approvals [[fixes/hr-manager-approvals]]
