# Cause: Stock-in vs Make tea bags confusion

**Stock in** (`dept_raw_stock_in_bags`) only increases **sealed supplier bags** on `stock_entries`.

**Use raw bags** (`dept_pt_use_raw_bags`) is the primary step: enter bags opened; deducts raw leaf; credits `dept_wip_rows` with derived tea bags (~500 per bag).

Users who expect stock-in alone to create WIP will see 0 tea bags until they use **Use raw bags**.
