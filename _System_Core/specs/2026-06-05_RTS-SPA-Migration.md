---
status: approved
created: 2026-06-05
owner: team
---

# Feature: RTS full SPA migration

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

RTS inventory is in the SPA but case recording, records list, and operational dashboard remain legacy PHP. CSR `/rts/record` route is wrong. Legacy APIs use `app_users` auth; SPA uses IMS.

## Users & roles

| Role | Capabilities |
|------|----------------|
| CSR staff / TL | Record cases, append lines, view records |
| Executive / PM | All above + void, claim status, dashboard |
| HR | Read-only inventory |
| Prod manager | Inventory sell/deduct |

## Desired outcome

- [ ] All RTS surfaces in React at `/rts`, `/rts/record`, `/rts/records`, `/rts/dashboard`
- [ ] Same `rts_cases` + `rts_line_items` save path and sync flow
- [ ] Legacy PHP pages redirect to SPA

## Behavior

1. Cases saved to operational tables; inventory mirror via sync only.
2. Documentation uploads use RTS storage bucket unchanged.
3. Damaged cases require delivery photo match.
4. Duplicate tracking returns 409.
