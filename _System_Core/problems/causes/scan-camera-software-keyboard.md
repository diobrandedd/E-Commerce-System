---
type: cause
tags: [cause]
---

# Cause: Scan field focus opens Gboard

## What went wrong

Scan pages keep a text input focused so a USB/Bluetooth imager can type codes. After every camera decode they called `.focus()` again. Android Chrome treats a focused `<input>` as “type here” and opens the software keyboard — even when the camera modal is on top.

## Where in code

- `inputRef.current?.focus()` in stock-in / pack / POS / leak-damage / verify
- Camera modals did not blur the field or trap focus off inputs

## Related problems

- [[records/2026-08-18_scan-camera-software-keyboard]]

## Prevent

See [[003_Fragile_Edges#Scan fields vs phone software keyboard]].
