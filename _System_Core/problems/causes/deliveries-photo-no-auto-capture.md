---
date: 2026-10-01
type: cause
area: deliveries
tags: [cause]
---

# Cause: Preview step never triggered capture input

`enterPreview` closed the QR scanner and showed tracking UI, but `photoInputRef.click()` only ran from the manual **Take before-shipment photo** button.

→ Problem: [[records/2026-10-01_deliveries-auto-open-parcel-camera]]
