---
title: API Index
generated: 2026-09-07T10:44:19+02:00
type: memory-bank
---


# API Index

> AUTO-GENERATED — do not edit by hand. Run `php tools/memory_bank/generate_all.php`.

See also: [[110_Include_Graph]] · [[130_Module_Index]] · [[140_Frontend_API_Clients]]

## api/v1 (SPA JSON gateway)

### `_costing_helpers.php`

- **Path:** `api/v1/_costing_helpers.php`
- **URL:** `/rts/api/v1/_costing_helpers.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`
- **Note:** No `api_dispatch` map (inline handlers or helper file).

### `ar.php`

- **Path:** `api/v1/ar.php`
- **URL:** `/rts/api/v1/ar.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/ar_credits.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_ar_list()` | `ar_credits_list()`, `ims_pdo()` |
| `take` | `api_ar_take()` | `ar_credits_staff_roles()`, `ar_credits_take_for_user()`, `ims_pdo()` |
| `mark_paid` | `api_ar_mark_paid()` | `ar_credits_mark_paid()`, `ims_pdo()` |
| `mark_unpaid` | `api_ar_mark_unpaid()` | `ar_credits_mark_unpaid()`, `ims_pdo()` |
| `products` | `api_ar_products()` | `ar_credits_sellable_products()`, `ims_pdo()` |
| `staff_picker` | `api_ar_staff_picker()` | `ar_staff_picker_list()`, `ims_pdo()` |

### `auth.php`

- **Path:** `api/v1/auth.php`
- **URL:** `/rts/api/v1/auth.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `me` | `api_auth_me()` | `ims_pos_authenticated()`, `rts_forwarding_api_payload()`, `ims_current_user()`, `ims_user_permissions()` |
| `login` | `api_auth_login()` | `ims_login_user()`, `ims_current_user()`, `ims_user_permissions()`, `rts_forwarding_api_payload()` |
| `pos_login` | `api_auth_pos_login()` | `ims_login_pos()`, `rts_forwarding_api_payload()` |
| `logout` | `api_auth_logout()` | `ims_logout()` |
| `change_password` | `api_auth_change_password()` | `ims_change_password()` |

### `barcode_verify.php`

- **Path:** `api/v1/barcode_verify.php`
- **URL:** `/rts/api/v1/barcode_verify.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `lookup` | `api_barcode_verify_lookup()` | `barcode_verify_lookup()`, `ims_pdo()` |

### `bottle_barcodes.php`

- **Path:** `api/v1/bottle_barcodes.php`
- **URL:** `/rts/api/v1/bottle_barcodes.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `meta` | `api_bottle_meta()` | `honey_bottle_size_label()` |
| `request_create` | `api_bottle_request_create()` | `honey_bottle_request_create()`, `ims_pdo()` |
| `request_list` | `api_bottle_request_list()` | `honey_bottle_request_list()`, `ims_pdo()` |
| `request_get` | `api_bottle_request_get()` | `honey_bottle_request_get()`, `ims_pdo()` |
| `request_cancel` | `api_bottle_request_cancel()` | `honey_bottle_request_cancel()`, `ims_pdo()`, `honey_bottle_user_dept_key()` |
| `pending_count` | `api_bottle_pending_count()` | `honey_bottle_can_generate()`, `honey_bottle_can_request()`, `honey_bottle_pending_request_count()`, `ims_pdo()` |
| `generate` | `api_bottle_generate()` | `honey_bottle_request_get()`, `ims_pdo()`, `sku_barcode_fetch_honey()`, `sku_barcode_record_print()`, `sku_barcode_serialize()` |
| `generate_batch` | `api_bottle_generate_batch()` | `ims_pdo()`, `honey_bottle_request_get()`, `sku_barcode_fetch_honey()`, `sku_barcode_record_print()`, `sku_barcode_print_sheet_url()` |
| `sku_print_list` | `api_bottle_sku_print_list()` | `sku_barcode_print_job_list()`, `ims_pdo()` |
| `batch_list` | `api_bottle_batch_list()` | `honey_bottle_batch_list()`, `ims_pdo()` |
| `batch_get` | `api_bottle_batch_get()` | `honey_bottle_batch_get()`, `ims_pdo()` |
| `mark_printed` | `api_bottle_mark_printed()` | `honey_bottle_mark_printed()`, `ims_pdo()` |
| `print_sheet` | `api_bottle_print_sheet()` | `honey_bottle_label_sheet_html()`, `ims_pdo()` |
| `void` | `api_bottle_void()` | `honey_bottle_void_code()`, `ims_pdo()` |
| `lookup` | `api_bottle_lookup()` | `honey_bottle_lookup()`, `ims_pdo()` |
| `scan_stock_in` | `api_bottle_scan_stock_in()` | `sku_barcode_fetch_by_code()`, `ims_pdo()`, `honey_bottle_create_custom_packaging()`, `sku_barcode_honey_stock_in()`, `honey_bottle_size_label()`, `honey_bottle_request_get()`, `honey_bottle_scan_stock_in()` |
| `pack_start` | `api_bottle_pack_start()` | `honey_bottle_pack_session_start()`, `ims_pdo()` |
| `pack_get` | `api_bottle_pack_get()` | `honey_bottle_pack_session_get()`, `ims_pdo()` |
| `pack_session_list` | `api_bottle_pack_session_list()` | `honey_bottle_pack_session_list()`, `ims_pdo()` |
| `pack_today_stats` | `api_bottle_pack_today_stats()` | `honey_bottle_pack_session_today_stats()`, `ims_pdo()` |
| `pack_scan` | `api_bottle_pack_scan()` | `honey_bottle_pack_session_scan()`, `ims_pdo()` |
| `pack_remove_item` | `api_bottle_pack_remove_item()` | `honey_bottle_pack_session_remove_item()`, `ims_pdo()` |
| `pack_confirm` | `api_bottle_pack_confirm()` | `honey_bottle_pack_session_confirm()`, `ims_pdo()` |
| `pack_discard` | `api_bottle_pack_discard()` | `honey_bottle_pack_session_discard()`, `ims_pdo()` |
| `pos_lookup` | `api_bottle_pos_lookup()` | `honey_bottle_pos_lookup()`, `ims_pdo()` |
| `opened_return_scan` | `api_bottle_opened_return_scan()` | `honey_bottle_opened_return_scan()`, `ims_pdo()` |
| `rts_receive` | `api_bottle_rts_receive()` | `honey_bottle_rts_receive()`, `ims_pdo()` |
| `backfill_preview` | `api_bottle_backfill_preview()` | `honey_bottle_backfill_preview()`, `ims_pdo()` |
| `backfill_generate` | `api_bottle_backfill_generate()` | `honey_bottle_backfill_generate()`, `ims_pdo()` |
| `reconcile` | `api_bottle_reconcile()` | `honey_bottle_reconcile()`, `ims_pdo()` |
| `stock_summary` | `api_bottle_stock_summary()` | `honey_bottle_stock_summary()`, `ims_pdo()` |
| `packaging_options` | `api_bottle_packaging_options()` | `honey_bottle_packaging_options()`, `ims_pdo()` |
| `packaging_scan_report` | `api_bottle_packaging_scan_report()` | `honey_bottle_packaging_scan_report()`, `ims_pdo()` |
| `packaging_scan_log` | `api_bottle_packaging_scan_log()` | `honey_bottle_packaging_scan_log()`, `ims_pdo()` |
| `ensure_packaging_shell` | `api_bottle_ensure_packaging_shell()` | `honey_fetch_packaging_by_production()`, `ims_pdo()`, `honey_bottle_ensure_packaging_shell()`, `honey_bottle_serialize_packaging_option()` |
| `create_custom_packaging` | `api_bottle_create_custom_packaging()` | `honey_bottle_can_request()`, `honey_bottle_user_can_generate()`, `honey_bottle_create_custom_packaging()`, `ims_pdo()`, `honey_bottle_serialize_packaging_option()` |

### `bulk_orders.php`

- **Path:** `api/v1/bulk_orders.php`
- **URL:** `/rts/api/v1/bulk_orders.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/bulk_honey_orders.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_bulk_orders_list()` | `ims_pdo()`, `bulk_honey_orders_table_exists()`, `bulk_honey_orders_list()` |
| `get` | `api_bulk_orders_get()` | `bulk_honey_order_get()`, `ims_pdo()` |
| `warehouse_rows` | `api_bulk_orders_warehouse_rows()` | `bulk_honey_warehouse_rows_for_variant()`, `ims_pdo()` |
| `suggest_allocations` | `api_bulk_orders_suggest_allocations()` | `bulk_honey_suggest_fifo_allocations()`, `ims_pdo()` |
| `preview_cost` | `api_bulk_orders_preview_cost()` | `bulk_honey_preview_cost()`, `ims_pdo()` |
| `create` | `api_bulk_orders_create()` | `bulk_honey_save_draft()`, `ims_pdo()` |
| `update` | `api_bulk_orders_update()` | `bulk_honey_save_draft()`, `ims_pdo()` |
| `confirm` | `api_bulk_orders_confirm()` | `bulk_honey_order_confirm()`, `ims_pdo()` |
| `void` | `api_bulk_orders_void()` | `bulk_honey_order_void()`, `ims_pdo()` |
| `delete` | `api_bulk_orders_delete()` | `bulk_honey_order_delete_draft()`, `ims_pdo()` |

### `costing.php`

- **Path:** `api/v1/costing.php`
- **URL:** `/rts/api/v1/costing.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/suppliers.php`, `includes/honey_volume.php`, `api/v1/_costing_helpers.php`, `includes/material_inventory.php`, `includes/parcel_pack_costing.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stocks_list` | `api_costing_stocks_list()` | `ims_pdo()`, `material_inventory_has_columns()`, `material_inventory_eligible_for_product_formula_row()`, `supplier_display_name()` |
| `products_list` | `api_costing_products_list()` | `ims_pdo()`, `parcel_pack_tables_ready()`, `parcel_pack_reference_addon_for_product()` |
| `product_view` | `api_costing_product_view()` | `ims_pdo()` |
| `stock_update_price` | `api_costing_stock_update_price()` | `ims_pdo()` |
| `formula_add` | `api_costing_formula_add()` | — |
| `formula_update` | `api_costing_formula_update()` | — |
| `formula_delete` | `api_costing_formula_delete()` | — |
| `product_create` | `api_costing_product_create()` | `ims_pdo()` |
| `product_update_selling_price` | `api_costing_product_update_selling_price()` | `ims_pdo()` |
| `parcel_tiers_list` | `api_costing_parcel_tiers_list()` | `ims_pdo()`, `parcel_pack_tables_ready()`, `parcel_pack_list_tiers()` |
| `parcel_tier_line_save` | `api_costing_parcel_tier_line_save()` | `ims_pdo()`, `parcel_pack_tables_ready()`, `parcel_pack_save_tier_line()`, `parcel_pack_tier_cost_estimate()` |
| `parcel_tier_line_delete` | `api_costing_parcel_tier_line_delete()` | `ims_pdo()`, `parcel_pack_tables_ready()`, `parcel_pack_delete_tier_line()`, `parcel_pack_tier_cost_estimate()` |
| `parcel_cost_preview` | `api_costing_parcel_cost_preview()` | `ims_pdo()`, `parcel_pack_tables_ready()`, `parcel_pack_preview_tier_by_key()`, `parcel_pack_preview_from_items_raw()` |

### `courier_soa_reconcile.php`

- **Path:** `api/v1/courier_soa_reconcile.php`
- **URL:** `/rts/api/v1/courier_soa_reconcile.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/courier_soa_reconcile.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_courier_soa_stash()` | `courier_soa_require_access()`, `courier_soa_stash_upload()` |
| `reconcile` | `api_courier_soa_reconcile()` | `courier_soa_require_access()`, `courier_soa_reconcile()`, `ims_pdo()` |
| `download` | `api_courier_soa_download()` | `courier_soa_serve_download()` |

### `dashboard.php`

- **Path:** `api/v1/dashboard.php`
- **URL:** `/rts/api/v1/dashboard.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `index` | `api_dashboard_index()` | `ims_pdo()`, `ims_user_can()` |
| `summary` | `api_dashboard_index()` | `ims_pdo()`, `ims_user_can()` |
| `inventory_snapshot` | `api_dashboard_inventory_snapshot()` | `dashboard_inventory_snapshot()`, `ims_pdo()` |
| `shipped_summary` | `api_dashboard_shipped_summary()` | `dashboard_inventory_resolve_range()`, `ims_pdo()` |

### `deliveries.php`

- **Path:** `api/v1/deliveries.php`
- **URL:** `/rts/api/v1/deliveries.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/deliveries_upload.php`, `includes/delivery_edit_requests.php`, `includes/online_order_parcels.php`, `includes/tracking_search.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `batch_for_date` | `api_deliveries_batch_for_date()` | `ims_pdo()` |
| `upload_photo` | `api_deliveries_upload_photo()` | `ims_pdo()`, `deliveries_ims_submitter_app_user_id()`, `deliveries_upload_photos()` |
| `parcel_by_tracking` | `api_deliveries_parcel_by_tracking()` | `ims_pdo()`, `online_parcels_ensure_schema()`, `online_parcels_normalize_tracking()`, `online_parcels_get_by_tracking()`, `deliveries_repair_orphaned_shipped_parcel()`, `deliveries_find_existing_recordings()` |
| `ship_parcel` | `api_deliveries_ship_parcel()` | `ims_pdo()`, `online_parcels_ship()` |
| `cancel_parcel_scan` | `api_deliveries_cancel_parcel_scan()` | — |
| `cancel_log_list` | `api_deliveries_cancel_log_list()` | `ims_pdo()`, `online_parcels_cancel_log_list()` |
| `hold_parcel` | `api_deliveries_hold_parcel()` | `ims_pdo()`, `online_parcels_hold()` |
| `rewaybill` | `api_deliveries_rewaybill()` | `ims_pdo()`, `online_parcels_rewaybill()` |
| `review_list` | `api_deliveries_review_list()` | `ims_pdo()`, `tracking_search_is_tn_shaped()`, `tracking_search_tn_column_sql()`, `tracking_search_contains_like()`, `rts_db_has_column()`, `online_parcels_format_packing_photo()`, `deliveries_enrich_review_rows()` |
| `void_item` | `api_deliveries_delete_item()` | `ims_pdo()`, `deliveries_delete_item_by_id()` |
| `delete_item` | `api_deliveries_delete_item()` | `ims_pdo()`, `deliveries_delete_item_by_id()` |
| `resolve_item` | `api_deliveries_resolve_item()` | `ims_pdo()`, `deliveries_resolve_item_by_id()` |

### `dept_pouch_barcodes.php`

- **Path:** `api/v1/dept_pouch_barcodes.php`
- **URL:** `/rts/api/v1/dept_pouch_barcodes.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `request_create` | `api_dept_pouch_request_create()` | `dept_pouch_request_create()`, `ims_pdo()` |
| `request_list` | `api_dept_pouch_request_list()` | `dept_pouch_request_list()`, `ims_pdo()` |
| `request_get` | `api_dept_pouch_request_get()` | `dept_pouch_request_get()`, `ims_pdo()` |
| `request_cancel` | `api_dept_pouch_request_cancel()` | `dept_pouch_request_cancel()`, `ims_pdo()` |
| `pending_count` | `api_dept_pouch_pending_count()` | `dept_pouch_pending_request_count()`, `ims_pdo()` |
| `pending_counts` | `api_dept_pouch_pending_counts()` | `dept_pouch_pending_request_counts_for_user()`, `ims_pdo()` |
| `generate` | `api_dept_pouch_generate()` | `dept_pouch_request_get()`, `ims_pdo()`, `sku_barcode_fetch_dept()`, `sku_barcode_record_print()`, `sku_barcode_serialize()` |
| `generate_batch` | `api_dept_pouch_generate_batch()` | `ims_pdo()`, `dept_pouch_request_get()`, `sku_barcode_fetch_dept()`, `sku_barcode_record_print()`, `sku_barcode_print_sheet_url()` |
| `sku_print_list` | `api_dept_pouch_sku_print_list()` | `sku_barcode_print_job_list()`, `ims_pdo()` |
| `batch_list` | `api_dept_pouch_batch_list()` | `dept_pouch_batch_list()`, `ims_pdo()` |
| `mark_printed` | `api_dept_pouch_mark_printed()` | `dept_pouch_mark_printed()`, `ims_pdo()` |
| `print_sheet` | `api_dept_pouch_print_sheet()` | `dept_pouch_label_sheet_html()`, `ims_pdo()` |
| `scan_stock_in` | `api_dept_pouch_scan_stock_in()` | `sku_barcode_fetch_by_code()`, `ims_pdo()`, `sku_barcode_dept_stock_in()`, `dept_pouch_request_get()`, `dept_pouch_scan_stock_in()` |
| `scan_report` | `api_dept_pouch_scan_report()` | `dept_pouch_scan_report()`, `ims_pdo()` |
| `request_scan_log` | `api_dept_pouch_request_scan_log()` | `dept_pouch_request_scan_log()`, `ims_pdo()` |
| `pos_lookup` | `api_dept_pouch_pos_lookup()` | `dept_pouch_pos_lookup()`, `ims_pdo()` |
| `defect_scan` | `api_dept_pouch_defect_scan()` | `dept_pouch_supports_defect()`, `dept_pouch_defect_scan()`, `ims_pdo()` |
| `defect_history` | `api_dept_pouch_defect_history()` | `dept_pouch_supports_defect()`, `dept_pouch_defect_history()`, `ims_pdo()` |

### `edit_requests.php`

- **Path:** `api/v1/edit_requests.php`
- **URL:** `/rts/api/v1/edit_requests.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/delivery_edit_requests.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_er_list()` | `ims_pdo()`, `ims_user_can()` |
| `submit` | `api_er_submit()` | `ims_pdo()`, `delivery_edit_request_validate_submit()`, `delivery_edit_request_normalize_values()` |
| `approve` | `api_er_approve()` | `ims_pdo()`, `delivery_edit_request_apply()` |
| `reject` | `api_er_reject()` | `ims_pdo()` |

### `finished_leak_damage.php`

- **Path:** `api/v1/finished_leak_damage.php`
- **URL:** `/rts/api/v1/finished_leak_damage.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `scan` | `api_finished_leak_damage_scan()` | `ims_user_can()`, `finished_leak_damage_scan()`, `ims_pdo()` |
| `history` | `api_finished_leak_damage_history()` | `finished_leak_damage_history()`, `ims_pdo()` |

### `fulfillment_packing.php`

- **Path:** `api/v1/fulfillment_packing.php`
- **URL:** `/rts/api/v1/fulfillment_packing.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `packing_balances_list` | `api_fulfillment_packing_balances_list()` | `ims_pdo()`, `fulfillment_packing_balances_list()` |
| `packing_issue_create` | `api_fulfillment_packing_issue_create()` | `ims_pdo()`, `fulfillment_packing_issue_create()` |
| `packing_issues_list` | `api_fulfillment_packing_issues_list()` | `ims_pdo()`, `fulfillment_packing_issues_list()` |
| `packing_issue_estimate` | `api_fulfillment_packing_issue_estimate()` | `ims_pdo()`, `fulfillment_packing_estimate_parcels()` |
| `packing_damage_report` | `api_fulfillment_packing_damage_report()` | `ims_pdo()`, `fulfillment_packing_report_damage()` |
| `packing_damage_list` | `api_fulfillment_packing_damage_list()` | `ims_pdo()`, `fulfillment_packing_damage_list()` |

### `historical_orders.php`

- **Path:** `api/v1/historical_orders.php`
- **URL:** `/rts/api/v1/historical_orders.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/historical_orders_import.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_historical_orders_stash()` | `historical_orders_require_access()`, `historical_orders_stash_upload()` |
| `preview` | `api_historical_orders_preview()` | `historical_orders_require_access()`, `historical_orders_preview()`, `ims_pdo()` |
| `commit` | `api_historical_orders_commit()` | `historical_orders_require_access()`, `historical_orders_commit()`, `ims_pdo()` |

### `honey.php`

- **Path:** `api/v1/honey.php`
- **URL:** `/rts/api/v1/honey.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `warehouse_list` | `api_honey_warehouse_list()` | `ims_pdo()`, `honey_warehouse_unit_cost_column()`, `honey_pricing_enrich_warehouse_row()`, `honey_warehouse_normalize_cost_row()`, `honey_pricing_role_can_view_cost()`, `honey_warehouse_has_pricing_columns()`, `honey_pricing_pending_count()` |
| `warehouse_stock_in` | `api_honey_warehouse_stock_in()` | `honey_warehouse_stock_in()`, `ims_pdo()` |
| `warehouse_stock_out` | `api_honey_warehouse_stock_out()` | `honey_warehouse_stock_out()`, `ims_pdo()` |
| `warehouse_barcode_request_list` | `api_honey_warehouse_barcode_request_list()` | `honey_warehouse_barcode_request_list()`, `ims_pdo()` |
| `warehouse_barcode_request_create` | `api_honey_warehouse_barcode_request_create()` | `ims_user_can()`, `honey_warehouse_barcode_request_create()`, `ims_pdo()` |
| `warehouse_barcode_request_cancel` | `api_honey_warehouse_barcode_request_cancel()` | `ims_user_can()`, `ims_pdo()` |
| `warehouse_barcode_generate` | `api_honey_warehouse_barcode_generate()` | `ims_user_can()`, `honey_warehouse_barcode_request_generate()`, `ims_pdo()` |
| `warehouse_barcode_print` | `api_honey_warehouse_barcode_print()` | `ims_user_can()`, `ims_pdo()`, `honey_warehouse_barcode_request_fetch()`, `honey_warehouse_barcode_print_sheet_html()` |
| `warehouse_barcode_lookup` | `api_honey_warehouse_barcode_lookup()` | `honey_warehouse_barcode_normalize()`, `honey_warehouse_barcode_lookup()`, `ims_pdo()` |
| `warehouse_barcode_stock_out` | `api_honey_warehouse_barcode_stock_out()` | `honey_warehouse_barcode_stock_out()`, `ims_pdo()` |
| `production_list` | `api_honey_production_list()` | `ims_pdo()` |
| `production_stock_out` | `api_honey_production_stock_out()` | `honey_parse_bottle_lines_from_input()`, `honey_production_stock_out()`, `ims_pdo()` |
| `packaging_list` | `api_honey_packaging_list()` | `ims_pdo()` |
| `packaging_total_inventory` | `api_honey_packaging_total_inventory()` | `honey_packaging_total_inventory_snapshot()`, `ims_pdo()` |
| `packaging_stock_out` | `api_honey_packaging_stock_out()` | `honey_packaging_stock_out_sold()`, `ims_pdo()` |
| `traceability` | `api_honey_traceability()` | `honey_traceability()`, `ims_pdo()` |
| `correction_submit` | `api_honey_correction_submit()` | `ims_pdo()` |
| `correction_list` | `api_honey_correction_list()` | `ims_user_can()`, `ims_pdo()`, `honey_correction_enrich()` |
| `correction_row_options` | `api_honey_correction_row_options()` | `honey_correction_row_options()`, `ims_pdo()` |
| `correction_approve` | `api_honey_correction_approve()` | `ims_pdo()`, `honey_apply_correction_request()` |
| `correction_reject` | `api_honey_correction_reject()` | `ims_pdo()` |
| `correction_pending_count` | `api_honey_correction_pending_count()` | — |
| `executive_adjust` | `api_honey_executive_adjust()` | `honey_executive_adjust()`, `ims_pdo()` |
| `executive_edit` | `api_honey_executive_edit()` | `ims_pdo()`, `honey_executive_edit_row()` |
| `correction_unread_count` | `api_honey_correction_unread_count()` | `ims_user_can()`, `ims_pdo()`, `honey_correction_unread_count()`, `material_adjustment_pending_count()` |
| `correction_unread_list` | `api_honey_correction_unread_list()` | `ims_user_can()`, `honey_correction_unread_list()`, `ims_pdo()` |
| `correction_mark_viewed` | `api_honey_correction_mark_viewed()` | `honey_correction_mark_viewed()`, `ims_pdo()` |
| `dept_list` | `api_honey_dept_list()` | `ims_user_can_dept_line()`, `ims_pdo()`, `dept_inventory_catalog_snapshot()` |
| `dept_catalog` | `api_honey_dept_catalog()` | `ims_pdo()`, `dept_inventory_catalog_snapshot()` |
| `dept_stock_in` | `api_honey_dept_stock_in()` | `ims_pdo()`, `dept_inventory_stock_in()` |
| `dept_raw_detail` | `api_honey_dept_raw_detail()` | `dept_raw_material_slug_to_product_key()`, `ims_user_can_dept_line()`, `dept_raw_detail_enriched()`, `ims_pdo()` |
| `dept_raw_stock_in` | `api_honey_dept_raw_stock_in()` | `dept_product_by_key()`, `ims_pdo()`, `dept_raw_stock_in_bags()` |
| `dept_raw_stock_out` | `api_honey_dept_raw_stock_out()` | `dept_product_key_valid()`, `ims_pdo()`, `dept_raw_stock_out_bags()` |
| `dept_raw_produce` | `api_honey_dept_raw_produce()` | — |
| `dept_pt_use_raw_bags` | `api_honey_dept_pt_use_raw_bags()` | `ims_pdo()`, `dept_pt_use_raw_bags()` |
| `dept_pt_make_tea_bags` | `api_honey_dept_pt_make_tea_bags()` | `ims_pdo()`, `dept_pt_make_tea_bags()` |
| `dept_pt_pouch` | `api_honey_dept_pt_pouch()` | — |
| `dept_brc_produce` | `api_honey_dept_brc_produce()` | — |
| `dept_jar_produce` | `api_honey_dept_jar_produce()` | — |
| `dept_comb_produce` | `api_honey_dept_comb_produce()` | — |
| `dept_wip_waste` | `api_honey_dept_wip_waste()` | `ims_pdo()`, `dept_wip_waste_tea_bags()` |
| `dept_production_preview` | `api_honey_dept_production_preview()` | `dept_product_key_valid()`, `dept_production_preview_lines()`, `ims_pdo()` |
| `dept_production_log` | `api_honey_dept_production_log()` | `dept_product_key_valid()`, `dept_production_daily_totals()`, `ims_pdo()`, `dept_production_list_entries()` |
| `dept_production_daily_record` | `api_honey_dept_production_daily_record()` | `ims_pdo()`, `dept_production_record_daily()` |
| `dept_add_row` | `api_honey_dept_add_row()` | — |
| `supplier_honey_prices` | `api_honey_supplier_honey_prices()` | `ims_pdo()`, `honey_pricing_list_supplier_prices()`, `supplier_display_name()`, `honey_pricing_pending_count()` |
| `supplier_honey_prices_save` | `api_honey_supplier_honey_prices_save()` | `ims_pdo()`, `honey_pricing_save_supplier_prices()`, `honey_pricing_reprice_pending_for_supplier()`, `honey_pricing_pending_count()` |
| `supplier_dept_prices` | `api_honey_supplier_dept_prices()` | `ims_pdo()`, `dept_pricing_list_supplier_prices()`, `supplier_display_name()` |
| `supplier_dept_prices_save` | `api_honey_supplier_dept_prices_save()` | `ims_pdo()`, `dept_pricing_save_supplier_prices()` |
| `warehouse_pending_prices` | `api_honey_warehouse_pending_prices()` | `ims_pdo()`, `honey_pricing_pending_warehouse_rows()`, `honey_pricing_pending_count()` |
| `warehouse_set_unit_cost` | `api_honey_warehouse_set_unit_cost()` | `honey_pricing_set_warehouse_row_cost()`, `ims_pdo()`, `honey_pricing_pending_count()` |
| `pricing_sync_stocks` | `api_honey_pricing_sync_stocks()` | `honey_pricing_sync_all_variant_stocks()`, `ims_pdo()` |
| `edit_log_list` | `api_honey_edit_log_list()` | `inventory_edit_log_list()`, `ims_pdo()` |
| `seed_production_row` | `api_honey_seed_production_row()` | `ims_pdo()`, `honey_executive_seed_production_row()` |
| `seed_packaging_row` | `api_honey_seed_packaging_row()` | `ims_pdo()`, `honey_executive_seed_packaging_row()` |
| `dept_raw_set_bags` | `api_honey_dept_raw_set_bags()` | `ims_pdo()`, `dept_raw_set_bags_on_hand()` |

### `inventory_check.php`

- **Path:** `api/v1/inventory_check.php`
- **URL:** `/rts/api/v1/inventory_check.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `context` | `api_inventory_check_context()` | `ims_pdo()`, `inventory_check_event_current()`, `inventory_check_can_view_all()`, `inventory_check_can_manage()`, `inventory_check_summary()` |
| `event_open` | `api_inventory_check_event_open()` | `inventory_check_event_open()`, `ims_pdo()` |
| `event_close` | `api_inventory_check_event_close()` | `inventory_check_event_close()`, `ims_pdo()` |
| `event_list` | `api_inventory_check_event_list()` | `inventory_check_event_list()`, `ims_pdo()` |
| `scan` | `api_inventory_check_scan()` | `ims_pdo()`, `inventory_check_scan()`, `inventory_check_summary()`, `inventory_check_can_view_all()` |
| `scan_remove` | `api_inventory_check_scan_remove()` | `ims_pdo()`, `inventory_check_scan_remove()`, `inventory_check_can_manage()`, `inventory_check_summary()`, `inventory_check_can_view_all()` |
| `summary` | `api_inventory_check_summary()` | `inventory_check_can_view_all()`, `inventory_check_summary()`, `ims_pdo()` |
| `scan_log` | `api_inventory_check_scan_log()` | `inventory_check_can_view_all()`, `inventory_check_scan_log()`, `ims_pdo()` |
| `compare_run` | `api_inventory_check_compare_run()` | `inventory_check_compare_run()`, `ims_pdo()` |
| `compare_get` | `api_inventory_check_compare_get()` | `inventory_check_compare_get()`, `ims_pdo()` |
| `compare_list` | `api_inventory_check_compare_list()` | `inventory_check_compare_list()`, `ims_pdo()` |

### `materials.php`

- **Path:** `api/v1/materials.php`
- **URL:** `/rts/api/v1/materials.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `catalog_list` | `api_materials_catalog_list()` | `material_inventory_role_can_view()`, `material_inventory_role_can_issue_packing()`, `ims_pdo()`, `material_inventory_list()`, `ims_user_can()` |
| `catalog_create` | `api_materials_catalog_create()` | `material_inventory_role_can_create_supply_catalog()`, `ims_user_can()`, `ims_pdo()`, `material_inventory_create()` |
| `catalog_update` | `api_materials_catalog_update()` | `ims_pdo()`, `material_inventory_update()` |
| `on_hand_list` | `api_materials_on_hand_list()` | — |
| `adjust` | `api_materials_adjust()` | `ims_pdo()`, `material_inventory_adjust()` |
| `request_submit` | `api_materials_request_submit()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_inventory_role_can_request_packaging()`, `material_inventory_role_can_request_supply()`, `material_adjustment_submit()` |
| `request_list` | `api_materials_request_list()` | `ims_pdo()`, `material_adjustment_list()`, `ims_user_can()`, `material_inventory_role_can_request_packaging()`, `material_inventory_role_can_request_supply()` |
| `request_approve` | `api_materials_request_approve()` | `ims_pdo()`, `material_adjustment_approve()` |
| `request_reject` | `api_materials_request_reject()` | `ims_pdo()`, `material_adjustment_reject()` |
| `request_pending_count` | `api_materials_request_pending_count()` | `ims_user_can()`, `material_adjustment_pending_count()`, `ims_pdo()` |
| `request_mark_viewed` | `api_materials_request_mark_viewed()` | `material_adjustment_mark_viewed()`, `ims_pdo()` |
| `request_unread_list` | `api_materials_request_unread_list()` | `material_adjustment_unread_list()`, `ims_pdo()` |
| `raw_snapshot` | `api_materials_raw_snapshot()` | `material_raw_snapshot()`, `ims_pdo()` |
| `raw_detail` | `api_materials_raw_detail()` | `material_inventory_raw_stock_by_slug()`, `ims_pdo()` |
| `departments` | `api_materials_departments()` | `ims_pdo()` |
| `stock_in_lot` | `api_materials_stock_in_lot()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_inventory_role_can_stock_in_packaging()`, `material_packaging_stock_in_lot()`, `material_supply_stock_in_lot()` |
| `packaging_detail` | `api_materials_packaging_detail()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_inventory_enrich_packaging_row()`, `ims_user_can()`, `material_packaging_recent_entries()`, `material_packaging_is_generic_foam_roll_name()`, `material_inventory_role_can_stock_in_packaging()`, `material_inventory_role_can_edit_packaging_price()`, … |
| `supply_detail` | `api_materials_supply_detail()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `ims_user_can()`, `material_supply_recent_entries()`, `material_supply_detail_permissions()` |
| `set_on_hand` | `api_materials_set_on_hand()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_packaging_set_on_hand()`, `material_supply_set_on_hand()` |
| `set_pool_yield` | `api_materials_set_pool_yield()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_packaging_is_generic_foam_roll_name()`, `material_packaging_set_pool_yield()`, `material_inventory_enrich_packaging_row()` |
| `set_foam_cacao_cut_yields` | `api_materials_set_foam_cacao_cut_yields()` | `ims_pdo()`, `material_inventory_fetch_stock()`, `material_packaging_is_generic_foam_roll_name()`, `material_packaging_set_foam_cacao_cut_yields()`, `material_packaging_foam_cacao_cut_yields()` |
| `packaging_deduct` | `api_materials_packaging_deduct()` | `material_inventory_role_can_deduct_packaging()`, `ims_pdo()`, `material_packaging_deduct_for_use()` |
| `packaging_price_lot` | `api_materials_packaging_price_lot()` | `material_inventory_role_can_edit_packaging_price()`, `ims_pdo()`, `material_packaging_record_invoice_price()` |

### `online_orders.php`

- **Path:** `api/v1/online_orders.php`
- **URL:** `/rts/api/v1/online_orders.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/online_orders_generator.php`, `includes/shopee_spx_packing_list.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `batch_start` | `api_online_orders_batch_start()` | `ims_current_user()`, `ims_pdo()`, `online_orders_batch_start()` |
| `batch_get` | `api_online_orders_batch_get()` | `ims_current_user()`, `ims_pdo()`, `online_orders_batch_get()` |
| `batch_cancel` | `api_online_orders_batch_cancel()` | `ims_current_user()`, `ims_pdo()`, `online_orders_batch_cancel()` |
| `batch_finalize` | `api_online_orders_batch_finalize()` | `ims_current_user()`, `ims_pdo()`, `online_orders_batch_finalize()` |
| `lines_append` | `api_online_orders_lines_append()` | `ims_current_user()`, `ims_pdo()`, `online_orders_assert_active_batch()`, `online_orders_run_start()`, `online_orders_lines_append()`, `online_orders_run_complete()` |
| `tool_vip_prepare` | `api_online_orders_tool_vip_prepare()` | `online_orders_tool_vip_prepare()` |
| `tool_vip_arranger_save` | `api_online_orders_tool_vip_arranger_save()` | `online_orders_vip_arranger_stash_save()` |
| `tool_vip_arranger_get` | `api_online_orders_tool_vip_arranger_get()` | `online_orders_vip_arranger_latest_token()`, `online_orders_vip_arranger_stash_get()` |
| `tool_vip_merge` | `api_online_orders_tool_vip_merge()` | `online_orders_vip_arranger_latest_token()`, `online_orders_tool_vip_merge()` |
| `tool_spx_prepare` | `api_online_orders_tool_spx_prepare()` | `online_orders_tool_spx_prepare()` |
| `tool_excel_register` | `api_online_orders_tool_excel_register()` | `online_orders_tool_excel_register()` |
| `run_unified_stamp` | `api_online_orders_run_unified_stamp()` | `ims_current_user()`, `ims_pdo()`, `online_orders_run_unified_stamp()` |
| `run_partner` | `api_online_orders_run_partner()` | `ims_current_user()`, `ims_pdo()`, `online_orders_run_partner_stamp()` |
| `run_vip_stamp` | `api_online_orders_run_vip_stamp()` | `ims_current_user()`, `ims_pdo()`, `online_orders_run_vip_stamp()` |
| `run_shopee_resolve` | `api_online_orders_run_shopee_resolve()` | `ims_current_user()`, `ims_pdo()`, `online_orders_run_shopee_resolve()` |
| `run_shopee_stamp` | `api_online_orders_run_shopee_stamp()` | `ims_current_user()`, `ims_pdo()`, `online_orders_run_shopee_stamp()` |
| `spx_import_packing_list` | `api_online_orders_spx_import_packing_list()` | `online_orders_spx_import_packing_list()` |
| `packaging_stock` | `api_online_orders_packaging_stock()` | `honey_packaging_total_inventory_snapshot()`, `ims_pdo()` |

### `order_remarks.php`

- **Path:** `api/v1/order_remarks.php`
- **URL:** `/rts/api/v1/order_remarks.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/order_remarks.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_order_remarks_stash()` | `order_remarks_stash_upload()` |
| `parse` | `api_order_remarks_parse()` | `order_remarks_parse_workbook()` |
| `export` | `api_order_remarks_export()` | `order_remarks_export_workbook()` |
| `profiles` | `api_order_remarks_profiles()` | `order_remarks_profile_labels()` |

### `pack_cancel_restock.php`

- **Path:** `api/v1/pack_cancel_restock.php`
- **URL:** `/rts/api/v1/pack_cancel_restock.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `start` | `api_pack_cancel_start()` | `pack_cancel_restock_start()`, `ims_pdo()` |
| `get` | `api_pack_cancel_get()` | `pack_cancel_restock_get()`, `ims_pdo()` |
| `scan` | `api_pack_cancel_scan()` | `pack_cancel_restock_scan()`, `ims_pdo()` |
| `confirm` | `api_pack_cancel_confirm()` | `pack_cancel_restock_confirm()`, `ims_pdo()` |
| `discard` | `api_pack_cancel_discard()` | `pack_cancel_restock_discard()`, `ims_pdo()` |
| `list` | `api_pack_cancel_list()` | `pack_cancel_restock_list()`, `ims_pdo()` |

### `parcels.php`

- **Path:** `api/v1/parcels.php`
- **URL:** `/rts/api/v1/parcels.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/online_order_parcels.php`, `includes/online_orders_generator.php`, `includes/online_orders_generator.php`, `includes/honey_parcel_item_parser.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_parcels_list()` | `ims_pdo()`, `online_parcels_ensure_schema()`, `online_parcels_list()` |
| `get` | `api_parcels_get()` | `ims_pdo()`, `online_parcels_ensure_schema()`, `online_parcels_get_by_id()`, `online_parcels_get_by_tracking()` |
| `lookup` | `api_parcels_lookup()` | `ims_pdo()`, `online_parcels_ensure_schema()`, `online_parcels_lookup_q()` |
| `parse_preview` | `api_parcels_parse_preview()` | `online_parcels_user_can_edit_items()`, `online_orders_normalize_items_display()`, `honey_parcel_parse_items_string()` |
| `update_items` | `api_parcels_update_items()` | `online_parcels_user_can_edit_items()`, `online_parcels_update_items()`, `ims_pdo()` |
| `filter_options` | `api_parcels_filter_options()` | `ims_pdo()`, `online_parcels_ensure_schema()`, `online_parcels_filter_options()` |
| `export` | `api_parcels_export()` | `online_parcels_export_selected()`, `ims_pdo()` |
| `export_download` | `api_parcels_export_download()` | `online_orders_serve_download()` |

### `partner_sold_reconcile.php`

- **Path:** `api/v1/partner_sold_reconcile.php`
- **URL:** `/rts/api/v1/partner_sold_reconcile.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/partner_sold_reconcile.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_partner_sold_stash()` | `partner_sold_require_access()`, `partner_sold_stash_upload()` |
| `reconcile` | `api_partner_sold_reconcile()` | `partner_sold_require_access()`, `partner_sold_reconcile()`, `ims_pdo()` |
| `download` | `api_partner_sold_download()` | `partner_sold_serve_download()` |

### `pdf_to_excel.php`

- **Path:** `api/v1/pdf_to_excel.php`
- **URL:** `/rts/api/v1/pdf_to_excel.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/pdf_to_excel.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_pdf_to_excel_stash()` | `waybill_stamper_upload_preflight()`, `pdf_to_excel_stash_upload()` |
| `extract` | `api_pdf_to_excel_extract()` | `pdf_to_excel_resolve_stash_tokens()`, `pdf_to_excel_extract()`, `pdf_to_excel_release_stash_tokens()` |
| `confirm` | `api_pdf_to_excel_confirm()` | `pdf_to_excel_confirm()` |

### `pos.php`

- **Path:** `api/v1/pos.php`
- **URL:** `/rts/api/v1/pos.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/honey_pos.php`, `includes/dept_pos.php`, `includes/pos_unified.php`, `includes/pos_discount_requests.php`, `includes/ar_credits.php`, `includes/pos_sales_helpers.php`, `includes/pos_thermal_print.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `products` | `api_pos_products()` | `ims_pdo()` |
| `honey_catalog` | `api_pos_honey_catalog()` | `honey_pos_honey_catalog()`, `ims_pdo()` |
| `dept_catalog` | `api_pos_dept_catalog()` | `dept_pos_catalog()`, `ims_pdo()` |
| `checkout` | `api_pos_checkout()` | `ims_pdo()`, `pos_sales_seller_insert()` |
| `honey_checkout` | `api_pos_honey_checkout()` | `honey_pos_checkout()`, `ims_pdo()` |
| `honey_ar_checkout` | `api_pos_honey_ar_checkout()` | `honey_pos_ar_checkout()`, `ims_pdo()` |
| `dept_checkout` | `api_pos_dept_checkout()` | `dept_pos_checkout()`, `ims_pdo()` |
| `dept_ar_checkout` | `api_pos_dept_ar_checkout()` | `dept_pos_ar_checkout()`, `ims_pdo()` |
| `unified_lookup` | `api_pos_unified_lookup()` | `pos_unified_lookup()`, `ims_pdo()` |
| `unified_checkout` | `api_pos_unified_checkout()` | `pos_unified_checkout()`, `ims_pdo()`, `pos_session_actor()` |
| `unified_ar_checkout` | `api_pos_unified_ar_checkout()` | `pos_unified_ar_checkout()`, `ims_pdo()`, `pos_session_actor()` |
| `staff_picker` | `api_pos_staff_picker()` | `ar_staff_picker_list()`, `ims_pdo()` |
| `discount_request_create` | `api_pos_discount_request_create()` | `pos_discount_request_create()`, `ims_pdo()` |
| `discount_request_status` | `api_pos_discount_request_status()` | `pos_discount_request_fetch_for_pin()`, `ims_pdo()` |
| `thermal_print` | `api_pos_thermal_print()` | `pos_thermal_print_text()`, `pos_thermal_print_raw()`, `pos_thermal_print_config()` |

### `pos_discounts.php`

- **Path:** `api/v1/pos_discounts.php`
- **URL:** `/rts/api/v1/pos_discounts.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/pos_discount_requests.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_pos_discounts_list()` | `pos_discount_request_list()`, `ims_pdo()` |
| `approve` | `api_pos_discounts_approve()` | `pos_discount_request_approve()`, `ims_pdo()` |
| `decline` | `api_pos_discounts_decline()` | `pos_discount_request_decline()`, `ims_pdo()` |

### `reports.php`

- **Path:** `api/v1/reports.php`
- **URL:** `/rts/api/v1/reports.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/courier_detect.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stock_movement` | `api_reports_stock_movement()` | `ims_pdo()` |
| `dept_summary` | `api_reports_dept_summary()` | `ims_pdo()` |
| `courier_summary` | `api_reports_courier_summary()` | `ims_pdo()` |
| `pipeline_summary` | `api_reports_pipeline_summary()` | `ims_pdo()` |

### `rts.php`

- **Path:** `api/v1/rts.php`
- **URL:** `/rts/api/v1/rts.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `pool` | `api_rts_pool()` | `ims_pdo()` |
| `recent` | `api_rts_recent()` | `ims_pdo()` |
| `legacy_urls` | `api_rts_legacy_urls()` | — |
| `inventory_list` | `api_rts_inventory_list()` | `ims_pdo()`, `rts_inventory_list_honey_aggregated()`, `rts_inventory_list_other_aggregated()`, `rts_inventory_sync_status()`, `ims_user_can()` |
| `inventory_deduct_sell` | `api_rts_inventory_deduct_sell()` | `rts_sellable_from_cases_enabled()`, `ims_pdo()`, `rts_inventory_deduct_sell_honey_variant()`, `rts_inventory_deduct_sell_other_label()`, `rts_inventory_deduct_sell()` |
| `inventory_use_rts` | `api_rts_inventory_deduct_sell()` | `rts_sellable_from_cases_enabled()`, `ims_pdo()`, `rts_inventory_deduct_sell_honey_variant()`, `rts_inventory_deduct_sell_other_label()`, `rts_inventory_deduct_sell()` |
| `inventory_use_log` | `api_rts_inventory_use_log()` | `rts_pool_use_log_list()`, `ims_pdo()` |
| `inventory_set_honey` | `api_rts_inventory_set_honey()` | `ims_pdo()`, `rts_inventory_set_honey_variant_totals()` |
| `inventory_set_other` | `api_rts_inventory_set_other()` | `ims_pdo()`, `rts_inventory_set_other_label_total()` |
| `stock_in_scan` | `api_rts_stock_in_scan()` | `sku_barcode_fetch_by_code()`, `ims_pdo()`, `sku_barcode_rts_stock_in()`, `honey_bottle_fetch_code_row()`, `honey_bottle_rts_stock_in_scan()`, `dept_pouch_rts_stock_in_scan()` |
| `use_damaged` | `api_rts_use_damaged()` | `rts_damaged_use_apply()`, `ims_pdo()` |
| `damaged_use_log` | `api_rts_damaged_use_log()` | `rts_damaged_use_events_list()`, `ims_pdo()` |
| `sync_status` | `api_rts_sync_status_action()` | `rts_inventory_sync_status()`, `ims_pdo()` |

### `rts_barcode_labels.php`

- **Path:** `api/v1/rts_barcode_labels.php`
- **URL:** `/rts/api/v1/rts_barcode_labels.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `job_create` | `api_rts_barcode_labels_job_create()` | `rts_barcode_label_job_create()`, `ims_pdo()` |
| `job_get` | `api_rts_barcode_labels_job_get()` | `rts_barcode_label_job_get()`, `ims_pdo()` |
| `job_list` | `api_rts_barcode_labels_job_list()` | `rts_barcode_label_job_list()`, `ims_pdo()` |
| `job_codes` | `api_rts_barcode_labels_job_codes()` | `rts_barcode_label_job_codes()`, `ims_pdo()` |
| `mark_printed` | `api_rts_barcode_labels_mark_printed()` | `rts_barcode_label_job_mark_printed()`, `ims_pdo()` |
| `print_sheet` | `api_rts_barcode_labels_print_sheet()` | `rts_barcode_label_print_sheet_html()`, `ims_pdo()` |
| `meta` | `api_rts_barcode_labels_meta()` | `dept_pouch_product_label()`, `honey_bottle_size_label()` |

### `rts_cases.php`

- **Path:** `api/v1/rts_cases.php`
- **URL:** `/rts/api/v1/rts_cases.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `case_get` | `api_rts_cases_get()` | `rts_case_handler_get_by_tracking()`, `ims_pdo()` |
| `case_create` | `api_rts_cases_create()` | `ims_pdo()`, `rts_case_handler_create()` |
| `case_append_lines` | `api_rts_cases_append_lines()` | `ims_pdo()`, `rts_case_handler_append_lines()` |
| `case_update` | `api_rts_cases_update()` | `ims_pdo()`, `rts_case_handler_update()` |
| `case_void` | `api_rts_cases_void()` | `rts_case_handler_void()`, `ims_pdo()` |
| `case_update_claim` | `api_rts_cases_update_claim()` | `rts_case_handler_update_claim()`, `ims_pdo()` |
| `cases_list` | `api_rts_cases_list()` | `rts_case_handler_list()`, `ims_pdo()` |
| `list_swapped` | `api_rts_cases_list_swapped()` | `rts_case_handler_list_swapped()`, `ims_pdo()` |
| `list_leaks` | `api_rts_cases_list_leaks()` | `rts_case_handler_list_leaks()`, `ims_pdo()` |
| `tracking_prefill` | `api_rts_cases_tracking_prefill()` | `rts_case_handler_tracking_prefill_api()`, `ims_pdo()` |
| `dashboard` | `api_rts_cases_dashboard()` | `rts_case_handler_dashboard()`, `ims_pdo()` |
| `product_catalog` | `api_rts_cases_product_catalog()` | `rts_case_handler_product_catalog()` |
| `delivery_match` | `api_rts_cases_delivery_match()` | `rts_case_handler_delivery_match()`, `ims_pdo()` |
| `documentation_upload` | `api_rts_cases_documentation_upload()` | `rts_case_handler_upload_documentation()` |

### `rts_sync.php`

- **Path:** `api/v1/rts_sync.php`
- **URL:** `/rts/api/v1/rts_sync.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `run` | `api_rts_sync_run()` | `rts_inventory_sync_run()`, `ims_pdo()` |
| `status` | `api_rts_sync_status()` | `rts_inventory_sync_status()`, `ims_pdo()` |

### `sales.php`

- **Path:** `api/v1/sales.php`
- **URL:** `/rts/api/v1/sales.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/honey_sales_report.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `honey_sold` | `api_sales_honey_sold()` | `honey_sales_sold_snapshot()`, `ims_pdo()` |

### `settings.php`

- **Path:** `api/v1/settings.php`
- **URL:** `/rts/api/v1/settings.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `pos_pins_list` | `api_settings_pos_pins_list()` | `ims_pdo()` |
| `pos_pin_update` | `api_settings_pos_pin_update()` | `ims_pdo()` |
| `pos_pin_create` | `api_settings_pos_pin_create()` | `ims_pdo()` |

### `sku_barcodes.php`

- **Path:** `api/v1/sku_barcodes.php`
- **URL:** `/rts/api/v1/sku_barcodes.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_sku_barcodes_list()` | `ims_current_user()`, `sku_barcode_list_settings()`, `ims_pdo()` |
| `lookup` | `api_sku_barcodes_lookup()` | `sku_barcode_lookup()`, `ims_pdo()` |
| `save` | `api_sku_barcodes_save()` | `ims_current_user()`, `sku_barcode_save()`, `ims_pdo()` |
| `save_bulk` | `api_sku_barcodes_save_bulk()` | `ims_current_user()`, `sku_barcode_save_bulk()`, `ims_pdo()` |
| `print_sheet` | `api_sku_barcodes_print_sheet()` | `ims_pdo()`, `sku_barcode_combined_label_sheet_html()`, `sku_barcode_label_sheet_html()` |
| `print_record` | `api_sku_barcodes_print_record()` | `ims_user_can()`, `sku_barcode_record_print()`, `ims_pdo()` |
| `stock_in` | `api_sku_barcodes_stock_in()` | `ims_pdo()`, `sku_barcode_fetch_by_id()`, `sku_barcode_fetch_by_code()`, `ims_user_can()`, `sku_barcode_rts_stock_in()`, `sku_barcode_honey_stock_in()`, `sku_barcode_dept_stock_in()`, `sku_barcode_serialize()` |

### `staff.php`

- **Path:** `api/v1/staff.php`
- **URL:** `/rts/api/v1/staff.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_staff_list()` | `ims_pdo()` |
| `create` | `api_staff_create()` | `ims_pdo()` |
| `update` | `api_staff_update()` | `ims_pdo()` |
| `reset_password` | `api_staff_reset_password()` | `ims_pdo()` |
| `permission_catalog` | `api_staff_permission_catalog()` | `ims_permission_catalog_grouped()` |
| `permissions_get` | `api_staff_permissions_get()` | `ims_pdo()`, `ims_user_permissions_expanded()`, `ims_permission_preset_for_role()` |
| `permissions_set` | `api_staff_permissions_set()` | `ims_pdo()`, `ims_user_permissions_set()`, `ims_user_permission_count()` |

### `stocks.php`

- **Path:** `api/v1/stocks.php`
- **URL:** `/rts/api/v1/stocks.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `api/v1/_costing_helpers.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `entries_list` | `api_stocks_entries_list()` | `ims_pdo()`, `ims_user_can()` |
| `stocks_for_dept` | `api_stocks_for_dept()` | `ims_pdo()` |
| `departments` | `api_stocks_departments()` | `ims_pdo()` |
| `add_entry` | `api_stocks_add_entry()` | `ims_pdo()` |

### `suppliers.php`

- **Path:** `api/v1/suppliers.php`
- **URL:** `/rts/api/v1/suppliers.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `list` | `api_suppliers_list()` | `ims_user_can()`, `supplier_list()`, `ims_pdo()` |
| `get` | `api_suppliers_get()` | `supplier_fetch()`, `ims_pdo()`, `supplier_serialize()` |
| `get_by_code` | `api_suppliers_get_by_code()` | `supplier_fetch_by_honey_code()`, `ims_pdo()`, `supplier_serialize()` |
| `create` | `api_suppliers_create()` | `supplier_create()`, `ims_pdo()` |
| `update` | `api_suppliers_update()` | `supplier_update()`, `ims_pdo()` |
| `activate` | `api_suppliers_activate()` | `supplier_set_active()`, `ims_pdo()` |
| `deactivate` | `api_suppliers_deactivate()` | `supplier_set_active()`, `ims_pdo()` |

### `waybill.php`

- **Path:** `api/v1/waybill.php`
- **URL:** `/rts/api/v1/waybill.php?action=...`
- **Includes:** `api/v1/_bootstrap.php`, `includes/waybill_stamper.php`

| Action | Handler | Domain calls |
|--------|---------|--------------|
| `stash` | `api_waybill_stash()` | `waybill_stamper_upload_preflight()`, `waybill_stamper_stash_upload()` |
| `process` | `api_waybill_process()` | `waybill_stamper_resolve_stash_tokens()`, `waybill_stamper_upload_preflight()`, `waybill_stamper_files_field()`, `waybill_stamper_collect_uploads()`, `waybill_stamper_process_uploads()`, `waybill_stamper_release_stash_tokens()` |

## Legacy api/ (non-v1)

### `case.php`
- **Path:** `api/case.php`

### `delivery_batch_detail.php`
- **Path:** `api/delivery_batch_detail.php`

### `delivery_bulk.php`
- **Path:** `api/delivery_bulk.php`

### `delivery_check_tracking.php`
- **Path:** `api/delivery_check_tracking.php`

### `delivery_match_tracking.php`
- **Path:** `api/delivery_match_tracking.php`

### `delivery_resolve.php`
- **Path:** `api/delivery_resolve.php`

### `scan_qr_image.php`
- **Path:** `api/scan_qr_image.php`

### `scan_qr_image_public.php`
- **Path:** `api/scan_qr_image_public.php`

### `storage_media.php`
- **Path:** `api/storage_media.php`

### `upload_documentation.php`
- **Path:** `api/upload_documentation.php`

