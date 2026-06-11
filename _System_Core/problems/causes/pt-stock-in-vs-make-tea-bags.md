# Cause: Stock-in vs Make tea bags confusion

**Stock in** (`dept_raw_stock_in_bags`) only increases **sealed supplier bags** on `stock_entries`.

**Make tea bags** (`dept_pt_make_tea_bags`) is a separate step: deducts leaf + tea-bag packaging from the Product Costing formula, credits `dept_wip_rows`.

Users who expect one-step bag→tea-bag conversion will see 0 WIP until they use Make tea bags.
