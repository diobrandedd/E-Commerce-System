---
date: 2026-06-12
area: dept-paragis-production
status: fixed
---

# Paragis production — impractical tea-bag counting

## Problem

Staff could not realistically count thousands of individual 2 g tea bags per production run. **Make tea bags** required an exact integer count, blocking normal floor workflow.

## Cause

UX and API were designed around per–tea-bag input (`dept_pt_make_tea_bags`) even though yield math is defined per 1 kg raw bag (500 tea bags).

## Fix

Added **Use raw bags** (`dept_pt_use_raw_bags`): input = bags opened; WIP += `bags × 500`; raw leaf deducts by bag qty. SPA default action; **Make tea bags** kept as advanced override.

## Files

- `includes/dept_production.php`
- `api/v1/honey.php`
- `app/src/app/pages/inventory/RawDeptMaterialPage.tsx`
- `app/src/app/lib/deptRawInventory.ts`
- `_System_Core/specs/2026-06-12_paragis-production-wip.md`

## Links

[[fixes/paragis-use-raw-bags]] · [[fixes/dept-raw-material-guides]] · [[records/2026-06-12_dept-raw-ux-session]] · [[specs/2026-06-12_paragis-production-wip]]
