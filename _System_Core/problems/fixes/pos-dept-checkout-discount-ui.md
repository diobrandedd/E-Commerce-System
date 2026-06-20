# Fix: Dept POS checkout, discount, compact cart

1. **`dept_pos.php`** — `dept_pos_build_cart_lines()` + discount percent / request id on checkout (mirrors honey).
2. **`pos_discount_requests.php`** — detect dept carts by `product_key`; quote + approve via `dept_pos_checkout()`.
3. **`POSDeptPage.tsx`** — `open={showCheckout}` on modal; Honey-style discount buttons + polling; compact centered cart strip + fixed bottom checkout bar; cart lines include `product_id`.
