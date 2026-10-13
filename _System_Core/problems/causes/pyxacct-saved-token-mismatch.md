---
type: cause
tags: [cause]
---

# Cause: Saved PyxAcct token did not match the server secret

## What went wrong

The API token field was saved as the whole `TRACKER_HONEY_TOKEN=...` line from PyxAcct `web/.env.local`. Tracker sent that line as the bearer token. The PHP app at `http://localhost/PyxAcct` compared it to a different `integration.tracker_honey_token` in `config/config.php`, so every test returned “Invalid or missing integration token.”

## Where in code

- `pyxacct_api_settings_save()` — null token keeps the existing secret; empty string would clear it
- `pyxacct_test_connection()` — Bearer and `X-PyxAcct-Token` from the stored value
- PyxAcct `features/Payables/actions.php` `pac_require_tracker_honey_token()`

## Related problems

- [[records/2026-10-10_pyxacct-test-rejected-token]]
