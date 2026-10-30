---
date: 2026-10-30
type: problem-record
status: fixed
area: dept-raw
tags: [leaf-pool, executive, password]
---

# Problem: Team leaders could edit the leaf pool without a password

## Symptom

Edit pool (grams) showed for accounts that only had the honey executive-edit permission, including team leaders. Saving did not ask for the administrator password.

## Cause

The button used `honey.executive_edit`, and `dept_raw_executive_set` accepted that permission with no password.

## Fix

The button is executive role only. The API rejects every other role and requires the Settings administrator password.

## Files

| Role | Path |
|------|------|
| SPA | `RawDeptMaterialPage.tsx`, `ExecutiveRawPoolEditModal.tsx`, `deptRawInventory.ts` |
| API | `api/v1/honey.php` `dept_raw_executive_set` |
