---
type: cause
tags: [cause, inventory-check, history]
---

# Cause: Audit history rows had no detail surface

`event_list` showed closed audit metadata only. Summary, comparison, and scan-log APIs already existed for an event id, but History never opened them, so operators could not inspect a past session without starting a new count.
