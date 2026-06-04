---
status: draft
spec: "[[specs/TEMPLATE_Feature_Spec]]"
created: YYYY-MM-DD
approved_by: pending
---

# Implementation Plan: [Short title]

> **AI drafts this** from a spec. Human approves before any code.  
> Spec: _link to specs/ note_

## Summary

_One paragraph: what we will build in repo terms._

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | _e.g. new actions on `api/v1/….php`_ |
| Includes | _e.g. `includes/….php` functions_ |
| DB | _migration? tables?_ |
| SPA | _routes, pages, lib clients_ |
| Legacy / Python | _if any_ |

_Run `impact.php` on listed functions before edit._

## Implementation steps

### Phase 1 — Data / backend

1. [ ] _Step_
2. [ ] _Step_

### Phase 2 — API

1. [ ] _Step_

### Phase 3 — Frontend

1. [ ] _Step_

### Phase 4 — Integration & cleanup

1. [ ] Regenerate memory bank
2. [ ] Update [[002_Memory_Bank]]
3. [ ] Move this plan to `plans/archive/`

## Files to touch (expected)

| File | Action |
|------|--------|
| `path/to/file` | create / modify |

## Test plan

- [ ] _Manual test step_
- [ ] _Role: executive tries …_

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| _e.g. breaks honey pricing circular include_ | _lazy load / read 003_ |

## Rollback

_How to undo if wrong (migration down, feature flag, revert files)._

## Approval

- [ ] Human approved — OK to implement in Agent mode
- Approved date: _
