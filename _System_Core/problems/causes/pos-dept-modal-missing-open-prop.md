# Cause: POS dept checkout modal missing `open` prop

`POSDeptPage` conditionally rendered `<POSModal title="Checkout" …>` but `POSModal` requires `open={boolean}`. Without it, the overlay never appeared — checkout looked broken after tapping Checkout.
