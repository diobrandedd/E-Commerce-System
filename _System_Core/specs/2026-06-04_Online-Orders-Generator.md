---
status: theory
created: YYYY-MM-DD
owner: you
---

# Feature: [Online orders generator]

As of currently, it is only a waybill stamper, pdf to excel export and excel order remarks organizer. I want this to be now a singular feature with its sub features since they all will do the same thing. Stamp every waybill, and then record every excel into one excel for printing.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]]

## Problem

Currently we have to get an excel with order lists and then a waybill pdf for printing from different sources. 3 are from different workers processing their own shipment orders, they only send us the waybill pdf and the excel with orders (We already have implemented the stamper with the template to read, so we have to stick with this template.). and then the most hectic part, it's when we have to process VIP orders. VIP orders are orders that are ordered directly from us and not from online shops like shopee, lazada, or tiktok. Here we have 2 types, j&t and spx couriers, although we already have the Order remarks feature which is specifically made for this problem, its supposed to reorganize the excel order remarks to a much more easy to understand and will require the user to input every variant for every bottle ordered. This excel is then sent to our courier partners for them to process it and create waybills for it. Now the PDF to Excel is made specifically for the next step, its to have a excel for that VIP waybill to have the same template as the other orders from other workrs. We have to put them into one excel because we'll use this to print the whole thing later. There is also one more thing, shopee spx orders from us. They only give us the Waybill pdf, which is very exhausting because we need to create an excel with the same format like the 3 different workers excel format, but the orders in this waybill are not exact (e.g. Color Mix), we still need to change them to be exact variants of honey (same with order remarks but for actual excel format not like the VIP), and then after that stamp the waybills with it.

## Users & roles

_The CSR TL is the one that works with this task, sometimes CSR staffs_

## Desired outcome (success criteria)

- [ ] _For these features to be acting in sync, currently users are confused where to put where for order processing_
- [ ] _Users easily using the feature, no more having to use manual inputting, just checking for mistakes_

## Behavior (business rules)

1. _User friendly and easy to use_
2. _Edge case: when an order is created, saved to database, save its tracking number and contents_

## User flow (high level)

1. User opens feature side bar tab
2. The system opens a modal saying proceed to creating batch of orders, buttons for Confirm and Cancel.
3. clicking Confirm - User first needs to select which task to do, either process orders from partner sellers, process VIP orders, or process shopee spx orders (ours)
4. When users select process orders from partner sellers (the 3 workers) the system will ask to import a pdf, and an excel to Stamp the waybill with the excel items. 
5. if the user selects process VIP orders, the system will ask the user to import a excel for the system to edit the order remarks, if detected no variants like it currently does, the system will ask for the user to select variant like it currently does. Then when done with this step and confirmed that variants has been selected successfully, the system will now go and make the user download the excel and then wait for the user to import a pdf for the waybills. When the user has imported the PDF containing the waybills for that excel, the system will now proceed to stamp on the waybills. 
6. if the user selects process shopee spx orders, then the system asks to import the pdf file, and then the system creates an excel with the same format as the workers (3 partners) with the pdf contents. now since the pdf reader can only get the name, courier and tracking number column rows, the next step will be for the user to input every item for that tracking Name/tracking number to complete the excel, and then after than confirmation and then waybill stamper.
7. after completing either, 4 5 or 6, the system will ask to continue doing other processes or done.
8. if the user clicks done, every processes done will be saved into one excel for download. the format for the excel should be order no. name item/s column e blank, column F blank, Courier, and then Tracking number. Although some dont have order number, its fine leave blank.
9. now the system will save it into the database and is marked as processed
10. then the printed excel is given to the parcel packers

## Data & integrity (conceptual)

_What matters is the item of the waybill/ parcel, we need to save this so that we can deduct from the inventory later._

## UI/UX intent (if applicable)

- Layout feel: Layout should be the the same or similar to what it currently is
- Feedback: toast/ modals for check confirmed/ animations
- Reference screens: Order remarks still carousel, make it the same for the others when trying to select a variant

## Out of scope

- We still have not merged the inventory with this online order generator

## Open questions

- [ ] I want to know if we could make it so that we can make this even more better or have a better process you think you can make

## Approval to plan

- [x] Spec reviewed by human
- [x] Ready for AI to draft `plans/active/[name]_plan.md` — see [[plans/active/2026-06-04_Online-Orders-Generator_plan]]
