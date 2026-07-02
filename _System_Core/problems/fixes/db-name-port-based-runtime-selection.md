---
type: fix
area: backend-config
tags: [fix, db]
---

# Fix: Port-based runtime database selection

`includes/db.php` now reads `$_SERVER['SERVER_PORT']` and switches the database name automatically:

- `8080` -> development database
- any other port -> production database

This keeps one Git-committed file safe across local/dev/prod folders without manual edits.
