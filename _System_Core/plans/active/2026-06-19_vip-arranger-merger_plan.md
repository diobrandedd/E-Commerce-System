---
status: approved
approved: true
created: 2026-06-19
---

# Plan: VIP two-tool workflow (arranger + merger)

Spec: extend [[specs/2026-06-19_vip-spx-order-interpretation]]

## Shipped

1. **VIP Excel arranger** — VIP order list (`Parcel Name` + `Remarks`) → CSR carousel → save arranged rows + remarks export (no iPYX).
2. **VIP Excel merger** — J&T tracking (`Order Number` → strip `PKE`) or SPX (`Customer Reference No.`) → iPYX download + unmatched report.
3. Profiles: `vip_order_list`, `jnt_tracking`; Python `tracking_map` mode; session arranger stash.

## Join keys

| Courier | Arranged | Tracking export | Tracking column |
|---------|----------|-----------------|-----------------|
| J&T | Parcel Name | Order Number | Waybill Number |
| SPX | Parcel Name | Customer Reference No. | Tracking No. |
