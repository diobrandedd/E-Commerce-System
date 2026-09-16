---
status: shipped
created: 2026-09-15
owner: ops
area: inventory
tags: [honey, production, warehouse, stock-out, merge]
---

# Honey production lot merge (part-by-part stock-out)

> Shipped — see [[plans/archive/2026-09-15_honey-production-lot-merge_plan]] · [[problems/records/2026-09-15_honey-production-lot-merge]]

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[problems/records/2026-08-28_honey-warehouse-supplier-barcodes]] · [[specs/2026-09-10_honey-lot-gallon-progress]]

## Problem

Warehouse staff move honey to Production **part by part**, not all at once. Each partial stock-out can create another Production row with the **same supplier code**. The Production lot dropdown for bottle scan then fills with near-duplicate choices, so packers pick the wrong lot or waste time hunting.

Scan stock-out already merges same-day lots. Manual row stock-out still creates a new row every time. The Production list can also repeat a lot when one production row has multiple packaging batches.

## Users & roles

- Honey warehouse / production ops (`honey` ops permissions)
- Anyone who selects a **Production lot** before packaging stock-in

## Desired outcome (success criteria)

- [ ] Part-by-part stock-outs for the **same supplier code + arrival + variant on the same calendar day** land on **one** Production lot (gallons add up).
- [ ] Production table and Production-lot dropdown show that one lot once (no packaging-join duplicates).
- [ ] Existing same-day duplicate open lots can be consolidated so today’s screen is not cluttered.
- [ ] Light / Amber / Dark under the same supplier code stay **separate** lots.
- [ ] Gallon progress stays **per lot** (not summed by supplier code alone) — matches [[specs/2026-09-10_honey-lot-gallon-progress]].

## Behavior (business rules)

1. **Merge key (locked):** supplier identity + supplier code + warehouse arrival date + variant + **stock-out date** (same daily key already used by scan stock-out).
2. Manual warehouse → production stock-out must use the same merge rules as scan stock-out.
3. Different variants under one supplier code are different lots.
4. Different arrival dates (different supplier codes or same code rebuilt) stay different lots.
5. Different stock-out **days** stay different lots unless ops later asks to collapse across days (out of scope here).
6. Bottle/SKU stock-in still targets one concrete production lot id; overdraw warn behavior unchanged.
7. Immutable stock-out event ledger remains when gallons are merged onto an existing daily lot.

## User flow (high level)

1. Worker stocks out 10 gal of Amber for supplier code `JR090426` (scan or manual).
2. Later same day, stocks out another 5 gal of the same Amber lot.
3. Production shows **one** Amber `JR090426` lot with 15 gal (not two rows).
4. Packer opens Production → Production lot dropdown → one clear Amber choice → scans bottles.

## Data & integrity (conceptual)

- Production lot = daily merged gallons for one supplier/arrival/variant.
- Warehouse source lines stay auditable via stock-out events.
- Packaging batches may still fork from one production lot across bottling dates; the **list** must not multiply the lot because of that.

## UI/UX intent

- Production table / cards: one row per merged lot.
- Dropdown label: supplier code · variant · gallons left (and stock-out date when it helps disambiguate).
- Guide copy: part-by-part stock-outs same day merge into one lot; variants stay separate.

## Out of scope

- Merging **only** by supplier code (would mix Light/Amber/Dark).
- Aggregating gallon progress bars by supplier code.
- Changing packaging bottle math / `honey.ml_per_gallon`.
- Auto-merging lots across **different stock-out days** (optional follow-up if ops wants it).

## Open question (need ops answer)

Do same supplier + variant lots on **different days** (e.g. stock out Sep 14 and Sep 15) need to become one forever lot, or is **same-day merge** enough?

## Decision log

| Date | Decision |
|------|----------|
| 2026-09-15 | Draft: reuse existing daily merge key; fix manual path + list fan-out + optional historical consolidate |
