# Fix: Confirm unique stickers before SKU qty

In `honey_bottle_pack_session_confirm`, partition honey (and dept) session items into unique-unit lines vs SKU aggregate lines, then process **unique first**, then SKU.

SKU FIFO then sees stock already reserved by leftover stickers in the same session. Guide text on Pack scan notes this order.
