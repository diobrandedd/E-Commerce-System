---
type: fix
tags: [fix, inventory, roles, honey, tl]
---

# Fix: Honey TL can run gallon pipeline ops

## Rule

IMS role `tl` (SPA `team_leader`) assigned to the **honey** department may:

- Warehouse **Stock in** / **Stock out → Production**
- Production **Stock out → Packaging** (bottle)
- Submit inventory **correction** requests via API (Requests hub UI remains exec / PM / HR for now)

Packaging **Sell** was removed from the Packaging batch table for all roles — sales go through POS.

They do **not** get:

- Executive direct edit / seed / monthly set
- Finished dept catalog stock-in (`honey_role_can_mutate` stays exec + prod_manager)
- Packaging materials / bubble-wrap pools (unchanged)

## Enforcement

- PHP: `honey_role_can_honey_ops()` + `api_honey_require_honey_ops()` (TL must resolve dept key `honey`)
- SPA: `canMutateHoneyPipeline()` on warehouse / production / packaging pages

## Also required

`suppliers.php?action=list` must allow IMS `tl` (picker only — no create/edit on Supplier Master). Without this, Warehouse stock-in shows “No active Honey suppliers” even when Master has rows.

See also: [[fixes/dept-raw-tl-produce]] · [[fixes/inventory-role-permissions]]
