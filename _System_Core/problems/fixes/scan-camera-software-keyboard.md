---
type: fix
tags: [fix]
---

# Fix: Keep Gboard down during camera scan

## What we changed

Camera modals dismiss the software keyboard. Scan fields use `inputMode="none"` unless the user taps to type, and they do not steal focus while the camera is open.

## Implementation

- `useDismissSoftwareKeyboard` blurs the active input and focuses a non-editable sink.
- `useScanWedgeField` is the shared scan box: no Gboard on programmatic focus; tap enables typing; `readOnly` while camera is open.

## Verify

1. On a phone, open bottle (or pouch) Camera scan.
2. Keyboard must stay closed while the camera is running.
3. After a sticker reads, keyboard must stay closed; keep scanning.
4. Close camera, tap the scan box — keyboard may open for typing. Desktop USB scanner still types into the field.

## Related problems

- [[records/2026-08-18_scan-camera-software-keyboard]]
