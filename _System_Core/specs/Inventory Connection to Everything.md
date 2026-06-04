---
status: theory
created: YYYY-MM-DD
owner: you
---

# Feature: [Inventory Connection to Everything]

> Inventory being connected in every scope of the system when it comes to product deducting via POS or online orders and make online orders Tracking numbers reflect what items are in the parcel.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

Currently we create orders and print them right, parcels are marked with what honey and variants are in there. However when we ship them we just deduct from the inventory by inputting how many we took out today in a paper form. Even POS are recorded via paper whenever taking from inventory

## Users & roles

CSR TL and CSR Staff use this when Ordering and Production Manager

## Desired outcome (success criteria)

- [ ] CSR TL and CSR Staff when done download master excel, the system will then put it in the database in a parcels table and then save the Tracking number, Item/s (contains), ship_date as null, return_date as null, and status as packing
- [ ] The deliveries is now migrated to react SPA and is now accessible by Production manager and Executives only
- [ ] Now when a parcel is scanned in the deliveries, its item contents is shown too, and also the ship_date from parcels table will be updated to the current date.
- [ ] When the production manager clicks scan next parcel, that parcel will automatically deduct from the inventory. Deduct from any batch, batch number tracking wont matter so we'll have to remove count progression from batch numbers, just save how many were put that day, no need to have 90/100. just 100
- [ ] the system should know what to deduct from the inventory. E.G if 1 x 3L Honey (LAD) + 350ml (A), this means the system will deduct 1L of light, 1L of Amber, 1L of Dark and 350ml of Amber.
- [ ] The system should also recognize other formats from other formats of items like 1 LITER Amber, etc so that situational excel items wont make the system not know.
- [ ] When executives, CSR TL and Production Manager goes to a tab with table with all the online order parcels and searches a tracking number or scans a qr code, the system will display the info about that parcel (contents, etc.)
- [ ] 

## Behavior (business rules)

1. Users will be able to see what's inside a parcel
2. _Edge case: I still dont know what edge cases could there be

## User flow (high level)

1. User saves from CSR TL done download master excel, the item for that parcel is saved in the database and its detail via parcel table
2. User Production Manager scan the wrapped parcel, the contents can be seen.
3. When production manager either clicks done or scan next parcel, the system will then deduct from inventory and automatically knows what the parcel item/s mean so that the system wont be confused what to deduct from inventory

## Data & integrity (conceptual)

The parcel contents matter so that when we search a tracking number or scan it, we can see what its insides are without opening it
## UI/UX intent (if applicable)

- Layout feel: Make it same as our theme, also put modals or those if needed
- Feedback: Feedback animations when done
- Reference screens: 

## Out of scope

we have not included the possibility of when parcels get cancelled during packaging. This happens when j&t or spx courier arrives to pickup the parcel, they scan the tracking number if the parcel is continued or cancelled. some are cancelled some are continued, we'll also have to have a solution for this, probably another scan for cancelled parcels so that the deducted inventory from that parcel will be retrieved after scanning the cancelled parcel

## Open questions

- [ ] _Question for you or stakeholder before planning_

## Approval to plan

- [ ] Spec reviewed by human
- [ ] Ready for AI to draft `plans/active/[name]_plan.md`
