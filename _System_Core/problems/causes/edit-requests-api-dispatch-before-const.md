---
type: cause
tags: [cause, api, edit-requests]
---

# Cause: edit_requests API dispatched before ER_ALLOWED_TABLES defined

## What went wrong

`api/v1/edit_requests.php` called `api_dispatch()` at the top of the file before `const ER_ALLOWED_TABLES` was defined. Submit handler ran with undefined constant → server error.

## Where in code

- `api/v1/edit_requests.php` — dispatch order vs const/handlers

## Related problems

- [[records/2026-06-05_edit-requests-submit-server-error]]

## Prevent

Always place `api_dispatch()` at end of v1 API files after constants and handler functions.
