---
type: fix
tags: [fix, deliveries, performance]
---

# Fix: Eager compressed photo upload and lean save path

## What we changed

Photo uploads now start in the background right after capture and are compressed client-side. Save actions usually only execute shipping.

## Implementation

- Added `compressDeliveryPhoto.ts` (1280px max edge, JPEG quality tune)
- Started upload on capture; save waits only if upload still in flight
- Removed redundant batch refresh calls using response counters
- Updated PHP upload path to skip binary read when `client_hint` is present

## Verify

1. Scan + capture photo
2. Wait briefly for background upload status
3. Tap Done/Scan next and confirm much faster completion

## Related problems

- [[records/2026-06-05_deliveries-save-latency]]
