---
date: 2026-08-20
type: cause
tags: [permissions, pack-scan]
---

# Cause: Pack scan API permission mismatch

Deliveries **Pack scan** (`deliveries.pack_scan`) and Honey **Scan bottle barcodes** (`honey.bottle.scan`) are separate permissions in `includes/permissions.php`. Pack scan reuses `api/v1/bottle_barcodes.php` but pack endpoints were gated only on `honey.bottle.scan`, so granting Pack scan in Staff Management did not authorize the API.
