# Cause: Deliveries scanner had no cancel transition path

Deliveries scanner supported `parcel_by_tracking` and `ship_parcel`, but there was no paired cancellation transition for courier-declined parcels.  
Without a dedicated cancel domain path, operators could not restore shipped deductions and there was no structured cancellation audit history.
