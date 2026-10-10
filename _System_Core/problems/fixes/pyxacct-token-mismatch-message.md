---
type: fix
tags: [fix]
---

# Fix: Explain a rejected PyxAcct token and align the local secret

## What changed

`pyxacct_api_token_normalize()` stores and sends only the secret when the field contains a `NAME=value` line. Local PyxAcct `config.php` `tracker_honey_token` was set to the same secret as `web/.env.local` `TRACKER_HONEY_TOKEN`. `ingest_status` then returns HTTP 200.

## Related problems

- [[records/2026-10-10_pyxacct-test-rejected-token]]
