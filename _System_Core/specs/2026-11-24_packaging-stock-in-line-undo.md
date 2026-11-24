---
status: approved
created: 2026-11-24
owner: ops
---

# Feature: Packaging stock-in — undo one person/size line

> **Theory only** — what we want and why. Implementation: `plans/active/`.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[003_Fragile_Edges]]

## Problem

Coworkers sometimes stock the wrong bottles into packaging (Production → Scan bottles into packaging). The Packaging stage report shows **gross** stocked-in by date and person, but today it is **read-only**. Fixing finished on-hand via Products → Honey Edit leaves the scan history wrong, so the report still shows the mistaken stock-in.

## Users & roles

- Staff who review **Raw Honey → Packaging** (“Stocked into inventory”).
- Only someone who knows the **administrator override password** (Settings) may confirm an undo — same gate as inventory sync / leaf-pool edits.

## Desired outcome (success criteria)

- [ ] From Packaging report, undo **one** “Scanned by person” line (person + variant + size for the selected scan date), not the whole person’s day total.
- [ ] Undo opens a **confirmation modal** that requires entering the **administrator password**.
- [ ] After undo: that qty disappears from the gross report **and** finished packaging on-hand drops by the same qty; production gallons taken for those bottles are restored.
- [ ] Bottles already sold / packed / not still in packaging stock cannot be silently undone — clear error.

## Behavior (business rules)

1. **Undo unit** = one report line: `(scan_date, scanned_by, variant, size)` with the displayed qty (e.g. Noli Fin · Dark · 250ml · 27 on Aug 14).
2. Undo reverses **all underlying stock-in events** for that line (unique stickers **and** SKU qty events), not a rewrite of POOL `current_*` alone.
3. Confirmation **AppModal**: summarize who / date / variant / size / qty; require non-empty administrator password; Cancel closes with no change.
4. Server verifies password via the existing administrator override setting; reject if unset or wrong.
5. If any unique sticker in the set is no longer `in_stock` in packaging (sold, packed, void, RTS, etc.), fail the whole undo (no partial apply).
6. Insufficient finished on-hand for the line qty → fail (do not drive POOL negative via undo).
7. Audit: record who undid, when, which line, qty, and that admin password was used (no password stored).

## User flow (high level)

1. Open Packaging → pick scan date → expand “Scanned by person”.
2. On a size line, choose **Undo**.
3. Modal shows the line summary + password field → Confirm.
4. On success: toast/banner, report and cards refresh; that line’s qty is gone (or the line disappears).

## Data & integrity (conceptual)

- Gross history = sticker `scanned_at` ∪ honey SKU stock-in events (same sources as today’s report).
- Live finished stock = variant packaging pool on-hand.
- Stock-in also FIFO-deducted production gallons (and bottling packaging materials) — undo must reverse finished qty + restore production gallons for the undone bottles. Material packaging restore is best-effort / documented if exact lot restore is unsafe.

## UI/UX intent

- Same Packaging page; Undo control on each person size line (not on variant summary cards alone).
- `AppModal` + `AppErrorBanner` / success notice; password field like executive leaf-pool edit.
- Motion: existing feedback kit (pressable / modal spring).

## Out of scope

- Undo whole person-day in one click (unless every line is undone separately).
- Editing/rewriting the report without reversing inventory.
- RTS stock-in session undo (separate flow).
- Pack Scan / POS / ship reversals.
- Products → Honey executive Edit changes (remains for other corrections).

## Open questions

- Who sees the Undo button: anyone who can open Packaging, or executive-only (password still required either way)? **Default proposal: executive-only + admin password.**
