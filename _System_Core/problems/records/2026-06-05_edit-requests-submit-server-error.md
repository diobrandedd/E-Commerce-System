---
date: 2026-06-05
type: problem-record
status: fixed
area: edit-requests
tags: [problem, api]
---

# Problem: Edit request submit returned server error

## Symptom

Production manager submitted edit/delete request on Deliveries review → **Server error.**

## Cause

→ [[causes/edit-requests-api-dispatch-before-const]]

## Fix

→ [[fixes/move-api-dispatch-to-end-of-file]]

## Files

| Role | Path |
|------|------|
| API | `api/v1/edit_requests.php` |

## Deploy

- PHP only
