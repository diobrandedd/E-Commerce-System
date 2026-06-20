# Cause: Cancelled confused with opened return

Courier cancel was implemented like an RTS/opened return: `online_parcels_cancel` called `honey_parcel_revert_deduct` for shipped parcels and cleared `deducted_at`. Re-waybill required `on_hold` only.

Ops reality: courier-cancelled orders are dead at the courier but the physical box is still sealed and packed — same as wrong-label on-hold, not an opened return.
