---
type: fix
tags: [fix]
---

# Fix: Windows notification on new barcode requests

## What we changed

Shared pending-count poller fires a Windows toast when bottle or pouch/jar pending counts **increase** after a baseline. Banner prompts Allow; My Account can turn alerts off.

## Implementation

- `BarcodePendingProvider` polls every 15s and on live refresh; first snapshot is silent.
- `showBarcodeRequestNotification()` uses the Notification API; click focuses Pyx and navigates to the matching generate page.
- Same watchers as sidebar badges: `honey.bottle.generate` / `honey.bottle.request` and dept `*.barcodes`.

## Verify

1. Sign in as a user who can generate barcodes.
2. Click **Turn on** (or My Account toggle) and Allow in the browser.
3. Minimize Pyx; have another user submit a barcode request.
4. Within ~15s a Windows toast appears; click it to open the generate page.

## Related problems

- [[records/2026-08-18_barcode-request-desktop-notify]]
