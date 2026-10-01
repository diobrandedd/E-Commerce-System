---
date: 2026-10-01
type: fix
area: deliveries
tags: [fix]
---

# Fix: Auto-click capture input once per TN after scan

Always-mounted `input[capture=environment]`. When Photo preview has tracking and no preview image and the QR modal is closed, click the input after ~220ms (once per TN via `autoPhotoForTnRef`). Manual Take / Retake remain as fallback if the OS blocks programmatic open.

→ Problem: [[records/2026-10-01_deliveries-auto-open-parcel-camera]]
→ Fragile: [[003_Fragile_Edges#Deliveries]]
