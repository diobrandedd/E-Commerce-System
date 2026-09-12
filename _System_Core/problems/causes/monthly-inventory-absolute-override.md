---
date: 2026-09-12
type: cause
status: documented
area: inventory
tags: [cause, monthly-inventory, security]
---

# Cause: Monthly inventory overrides absolute stock

Monthly inventory is an executive physical-count tool that **sets** on-hand / lot quantities to counted values (not stage moves). That path must stay gated by `inventory.monthly_count` **and** a shared administrator override password so a logged-in executive session alone cannot silently rewrite stock.

Hiding the module (redirect to Check inventory) removed the only intentional absolute-override UI without replacing the password gate.
