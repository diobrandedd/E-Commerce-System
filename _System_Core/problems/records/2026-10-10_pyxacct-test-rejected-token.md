---
date: 2026-10-10
type: problem-record
status: fixed
area: settings
tags: [problem, pyxacct, settings]
---

# Problem: PyxAcct Test connection rejected the saved token

## Symptom

Save connection succeeded, then Test connection showed “Invalid or missing integration token.” The API token field was blank with a “already stored” placeholder, so it looked like the token had been lost.

## Cause

→ [[causes/pyxacct-saved-token-mismatch]]

## Fix

→ [[fixes/pyxacct-token-mismatch-message]]

## Files

| Role | Path / note |
|------|-------------|
| Test | `includes/pyxacct_ap_sync.php` `pyxacct_test_connection()` |
| Save | `api/v1/settings.php` `pyxacct_sync_save` (blank token keeps the stored value) |
| UI | `SettingsPage.tsx` PyxAcct card |

## Deploy

- PHP only. Hard-refresh is not required for the error text.
- Local app setting `pyxacct_api_token` was updated to match PyxAcct `integration.tracker_honey_token`.
