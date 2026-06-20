---
type: fix
tags: [fix, api]
---

# Fix: Move api_dispatch to end of API file

## What we changed

Moved `api_dispatch([...])` to the bottom of `edit_requests.php` after const and handler definitions.

## Verify

PM submits delivery edit/delete request on review page → success, no server error.

## Related problems

- [[records/2026-06-05_edit-requests-submit-server-error]]
