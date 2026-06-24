---
status: theory
created: YYYY-MM-DD
owner: you
---

# Feature: [waybill stamper and online orders remake]

> changing the approach in how we deal with online orders and how we stamp them
> 
Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

Now it kind of feels wonky. Having to have different processes or things like that, feels too confusing.

## Users & roles

Executive, CSR TL, CSR staff

## Desired outcome (success criteria)

- [ ] _The users wont have to have many processes, instead have tools to use to do one process_
- [ ] _1 excel to 1 stamp waybill, no more having so many processes for a batch_

## Behavior (business rules)

1. _Should be flexible in knowing what the product item order is, or a prediction to what it would be. Some cells in an excel has many orders, others have mention b1t2 350 ml meaning it only counts as 3 350 ml_
2. _Edge case:_

## User flow (high level)

1. User goes to online orders tab
2. User selects a tool; Excel arranger (for VIP), extract names from pdf to excel (this is the spx process where it fetches the names from the pdf and then puts the name that was from the pdf and the excel that was imported into one excel with the same format as the partners excel)
3. After that the user will go back and then click on stamp waybills now.
4. Here the user will import a pdf and an excel to merge 2 files, just the same waybill stamper, but now instead of locked to partner sellers, its now just the final step.
5. after that the excel items for the excel will be saved to the database as item/s for inventory later, just like the same as how we are doing things

## Data & integrity (conceptual)

- **Online Parcels registry:** promoted from CSR batch finalize; statuses include `packing`, `shipped`, `on_hold`, `cancelled`, `returned`, **`rts`**; keyed by tracking number and item lines.
- **RTS sync (phase 49):** saving Record RTS marks matching parcel `rts` (skipped if no parcel or status `cancelled`); voiding RTS case restores `status_before_rts`.

## UI/UX intent (if applicable)

- Layout feel: just like how we design, shadow highlights etc
- Feedback: _toast / modal / inline error_
- Reference screens: _e.g. “like Order Remarks carousel”_

## Out of scope

- _Explicitly not in this initiative_

## Open questions

- [ ] _Question for you or stakeholder before planning_

## Approval to plan

- [ ] Spec reviewed by human
- [ ] Ready for AI to draft `plans/active/[name]_plan.md`
