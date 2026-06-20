---
title: Database Tables
generated: 2026-06-20T10:05:13+02:00
type: memory-bank
table_count: 55
---


# Database Tables

> AUTO-GENERATED from `sql/*.sql` + SQL regex scan of `includes/` and `api/`.

Schema sources: `sql/full_schema.sql` and migration files

## `rts_cases`

| File | Function |
|------|----------|
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_csr_metrics]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_rts_opened_in_range]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_pool]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_recent]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_inventory_list]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_update]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_append_lines]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_void]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_update_claim]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_list]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_dashboard]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_sync_run]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_cases_fetch_by_tracking]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_cases_insert_row]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_cases_update_row]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_cases_find_active_id_by_tracking]] |

## `rts_line_items`

| File | Function |
|------|----------|
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_csr_metrics]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_pool]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_recent]] |
| `api/v1/rts.php` | [[modules/rts#api_rts_inventory_list]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_update]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_list]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_sync_run]] |
| `includes/rts_products.php` | [[modules/rts_products#rts_line_items_schema_v2]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_line_items_fetch_for_case]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_line_items_ensure_v2_schema]] |
| `includes/rts_schema.php` | [[modules/rts_schema#rts_line_items_insert_batch]] |

## `app_users`

| File | Function |
|------|----------|
| `includes/auth.php` | [[modules/auth#rts_app_users_has_status_column]] |
| `includes/auth.php` | [[modules/auth#rts_delivery_submitter_user_id]] |
| `includes/auth.php` | [[modules/auth#rts_auth_login]] |

## `delivery_batches`

| File | Function |
|------|----------|
| `api/delivery_batch_detail.php` | `(file-level)` |
| `api/delivery_match_tracking.php` | `(file-level)` |
| `api/v1/deliveries.php` | [[modules/deliveries#api_deliveries_batch_for_date]] |
| `api/v1/deliveries.php` | [[modules/deliveries#api_deliveries_review_list]] |
| `includes/deliveries_upload.php` | [[modules/deliveries_upload#deliveries_upload_photos]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_find_existing_recordings]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_get_batch_item_row]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_find_packing_photo]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_enrich_packing_photos_batch]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_ship]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_find_delivery_match_item_id]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_delivery_match]] |

## `delivery_batch_items`

| File | Function |
|------|----------|
| `api/delivery_batch_detail.php` | `(file-level)` |
| `api/delivery_match_tracking.php` | `(file-level)` |
| `api/delivery_resolve.php` | `(file-level)` |
| `api/v1/deliveries.php` | [[modules/deliveries#api_deliveries_batch_for_date]] |
| `api/v1/deliveries.php` | [[modules/deliveries#api_deliveries_review_list]] |
| `includes/deliveries_review_nav.php` | [[modules/deliveries_review_nav#rts_delivery_review_attention_count]] |
| `includes/deliveries_upload.php` | [[modules/deliveries_upload#deliveries_upload_photos]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_find_existing_recordings]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_get_batch_item_row]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_delete_item_by_id]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_resolve_item_by_id]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_find_packing_photo]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_enrich_packing_photos_batch]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_ship]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_find_delivery_match_item_id]] |
| `includes/rts_case_handlers.php` | [[modules/rts_case_handlers#rts_case_handler_delivery_match]] |

## `sp_sort_batches`

| File | Function |
|------|----------|
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_create_batch]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_regenerate_batch_file]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_shop_counts]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_tables_exist]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_courier_counts]] |
| `sp_sort/api/complete.php` | `(file-level)` |
| `sp_sort/api/export.php` | `(file-level)` |

## `sp_sort_rows`

| File | Function |
|------|----------|
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_create_batch]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_load_batch_rows]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_shop_counts]] |
| `includes/sp_sort_service.php` | [[modules/sp_sort_service#sp_sort_courier_counts]] |

## `roles`

| File | Function |
|------|----------|
| `api/v1/staff.php` | [[modules/staff#api_staff_list]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_create]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_update]] |
| `includes/auth_check.php` | [[modules/auth_check#ims_login_user]] |

## `departments`

| File | Function |
|------|----------|
| `api/v1/_bootstrap.php` | [[modules/_bootstrap#api_dept_lookup]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_fetch_product_scope]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_products]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_stocks_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_products_list]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_edit_requests]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_list]] |
| `api/v1/materials.php` | [[modules/materials#api_materials_departments]] |
| `api/v1/pos.php` | [[modules/pos#api_pos_products]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_stock_movement]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_list]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_entries_list]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_departments]] |
| `includes/dashboard_inventory.php` | [[modules/dashboard_inventory#dashboard_dept_products_snapshot]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_fetch_row]] |
| `includes/dept_products.php` | [[modules/dept_products#dept_product_dept_id]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_bottling_deduct_packaging_stocks]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_row_context]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_resolve_honey_dept_id]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_canonical_dept_id]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_fetch_stock]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_raw_snapshot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_raw_stock_by_slug]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_user_is_csr_dept]] |
| `includes/waybill_stamper.php` | [[modules/waybill_stamper#waybill_stamper_user_can_access]] |

## `users`

| File | Function |
|------|----------|
| `api/v1/ar.php` | [[modules/ar#api_ar_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_products_list]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_index]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_edit_requests]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_list]] |
| `api/v1/honey.php` | [[modules/honey#api_honey_correction_list]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_list]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_create]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_update]] |
| `api/v1/staff.php` | [[modules/staff#api_staff_reset_password]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_entries_list]] |
| `includes/auth_check.php` | [[modules/auth_check#ims_login_user]] |
| `includes/auth_check.php` | [[modules/auth_check#ims_change_password]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_recent_entries]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_damage_list]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issues_list]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_unread_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_pool_recent_entries]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_unread_list]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_cancel_log_list]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_list]] |

## `pos_pins`

| File | Function |
|------|----------|
| `api/v1/settings.php` | [[modules/settings#api_settings_pos_pins_list]] |
| `api/v1/settings.php` | [[modules/settings#api_settings_pos_pin_update]] |
| `api/v1/settings.php` | [[modules/settings#api_settings_pos_pin_create]] |
| `includes/auth_check.php` | [[modules/auth_check#ims_login_pos]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_list]] |

## `stocks`

| File | Function |
|------|----------|
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_fetch_product_scope]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_recalculate_baseline]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_apply_price_change]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_build_product_detail]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_stocks_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_products_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_stock_update_price]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_formula_mutate]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_index]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_entries_list]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_for_dept]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_add_entry]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_bags_on_hand]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_recent_entries]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_deduct_bags_fifo]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_stock_in_manual_cost]] |
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_upsert_supplier_costing_stocks]] |
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_leaf_stock_id]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_load_formula_lines]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_formula_deduct_lines]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_packaging_deduct]] |
| `includes/fulfillment_pack_tier_seed.php` | [[modules/fulfillment_pack_tier_seed#fulfillment_pack_stock_ids_by_name]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_bubble_sized_stock_id]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_foam_generic_stock_id]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issue_create]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_balances_list]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_damage_list]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issues_list]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#material_packaging_record_invoice_price]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_bottling_deduct_packaging_stocks]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_upsert_supplier_costing_stocks]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_fetch_stock]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_create]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_update]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_adjust]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_values]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_pool_yield]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_foam_cacao_cut_yields]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_foam_cacao_cut_yields]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_generic_bubble_roll_unit_cost]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_generic_foam_roll_unit_cost]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_stock_in_lot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_on_hand]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_deduct_for_use]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_deduct_fulfillment_line]] |
| `includes/material_inventory.php` | [[modules/material_inventory#parcel_fulfillment_revert_deduct]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_stock_in_lot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_set_on_hand]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_apply_issue]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_unread_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_raw_snapshot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_raw_stock_by_slug]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_tier_lines]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_product_unit_cogs]] |

## `stock_entries`

| File | Function |
|------|----------|
| `api/v1/costing.php` | [[modules/costing#api_costing_stocks_list]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_index]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_submit]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_stock_movement]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_entries_list]] |
| `api/v1/stocks.php` | [[modules/stocks#api_stocks_add_entry]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_bags_on_hand]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_recent_entries]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_stock_in_bags]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_deduct_bags_fifo]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_stock_in_manual_cost]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_packaging_deduct]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issue_create]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#material_packaging_record_invoice_price]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_adjust]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_values]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_pool_unit_cost_from_lots]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_stock_in_lot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_set_on_hand]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_deduct_for_use]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_packaging_deduct_fulfillment_line]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_pool_recent_entries]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_stock_in_lot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_set_on_hand]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_supply_apply_issue]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_raw_snapshot]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_inventory_raw_stock_by_slug]] |

## `edit_requests`

| File | Function |
|------|----------|
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_edit_requests]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_list]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_submit]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_approve]] |
| `api/v1/edit_requests.php` | [[modules/edit_requests#api_er_reject]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_delete_item_by_id]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#delivery_edit_request_assert_no_duplicate_pending]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#delivery_edit_request_validate_submit]] |
| `includes/ims_exec_sidebar.php` | [[modules/ims_exec_sidebar#ims_render_exec_sidebar]] |

## `products`

| File | Function |
|------|----------|
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_fetch_product_scope]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_recalculate_baseline]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_products]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_list]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_take]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_products_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_product_create]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_product_update_selling_price]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_index]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |
| `api/v1/pos.php` | [[modules/pos#api_pos_products]] |
| `api/v1/pos.php` | [[modules/pos#api_pos_checkout]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_catalog_snapshot]] |
| `includes/dept_pos.php` | [[modules/dept_pos#dept_pos_build_cart_lines]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_load_formula_lines]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_product_map]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_fetch_checkouts]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_product_unit_cogs]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_receipt_payload]] |

## `product_formula`

| File | Function |
|------|----------|
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_fetch_product_scope]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_recalculate_baseline]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_apply_price_change]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_build_product_detail]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_products_list]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_formula_mutate]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_load_formula_lines]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_product_unit_cogs]] |

## `stock_deductions`

| File | Function |
|------|----------|
| `api/v1/reports.php` | [[modules/reports#api_reports_stock_movement]] |
| `api/v1/reports.php` | [[modules/reports#api_reports_dept_summary]] |

## `beginning_inventory`

_No references detected in includes/api (may be legacy pages only)._

## `stock_price_history`

| File | Function |
|------|----------|
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_apply_price_change]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_build_product_detail]] |
| `api/v1/costing.php` | [[modules/costing#api_costing_stocks_list]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |

## `product_cost_history`

| File | Function |
|------|----------|
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_apply_price_change]] |
| `api/v1/_costing_helpers.php` | [[modules/_costing_helpers#api_costing_build_product_detail]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_populate_financials]] |

## `inventory_edit_log`

| File | Function |
|------|----------|
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_log_edit]] |

## `supplier_honey_prices`

| File | Function |
|------|----------|
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_resolve_active_price]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_list_supplier_prices]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_save_supplier_prices]] |

## `online_parcel_dept_deduct_lines`

| File | Function |
|------|----------|
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_apply_deduct]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_revert_deduct]] |

## `pos_discount_requests`

| File | Function |
|------|----------|
| `includes/dept_pos.php` | [[modules/dept_pos#dept_pos_checkout]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_checkout]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_create]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_approve]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_decline]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_fetch_for_pin]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_request_list]] |

## `csr_online_batches`

| File | Function |
|------|----------|
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_ensure_schema]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_start]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_get]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_cancel]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_assert_active_batch]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_existing_tracking_map]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_finalize]] |

## `csr_online_batch_runs`

| File | Function |
|------|----------|
| `api/v1/online_orders.php` | [[modules/online_orders#api_online_orders_mark_run_failed]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_get]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_run_start]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_run_complete]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_run_unified_stamp]] |

## `csr_online_order_lines`

| File | Function |
|------|----------|
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_promote_from_batch]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_rewaybill]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_batch_get]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_existing_tracking_map]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_lines_append]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_merge_master_workbook]] |

## `supplier_dept_prices`

| File | Function |
|------|----------|
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_resolve_active_price]] |
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_list_supplier_prices]] |
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_save_supplier_prices]] |

## `online_order_parcels`

| File | Function |
|------|----------|
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_csr_metrics]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_shipped_count_in_range]] |
| `api/v1/dashboard.php` | [[modules/dashboard#api_dashboard_shipped_summary]] |
| `api/v1/deliveries.php` | [[modules/deliveries#api_deliveries_review_list]] |
| `includes/deliveries_upload.php` | [[modules/deliveries_upload#deliveries_upload_photos]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_revert_parcel_to_packing_if_shipped]] |
| `includes/delivery_edit_requests.php` | [[modules/delivery_edit_requests#deliveries_enrich_review_rows]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_promote_from_batch]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_get_by_tracking]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_get_by_id]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_list]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_ship]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_cancel]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_hold]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_rewaybill]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_cancel_log_list]] |
| `includes/online_orders_generator.php` | [[modules/online_orders_generator#online_orders_existing_tracking_map]] |

## `online_parcel_deduct_lines`

| File | Function |
|------|----------|
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_apply_deduct]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_revert_deduct]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_ship]] |

## `material_adjustment_requests`

| File | Function |
|------|----------|
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_submit]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_list]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_approve]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_reject]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_pending_count]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_mark_viewed]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_adjustment_unread_list]] |

## `dept_wip_rows`

| File | Function |
|------|----------|
| `includes/dept_production.php` | [[modules/dept_production#dept_production_tables_ready]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_wip_fetch_row]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_wip_adjust]] |

## `dept_production_entries`

| File | Function |
|------|----------|
| `includes/dept_production.php` | [[modules/dept_production#dept_production_tables_ready]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_log_entry]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_production_recent_entries]] |

## `parcel_pack_tiers`

| File | Function |
|------|----------|
| `includes/fulfillment_pack_tier_seed.php` | [[modules/fulfillment_pack_tier_seed#fulfillment_pack_tier_seed]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_tables_ready]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_resolve_tier_key_from_shape]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_tier_by_key]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_tier_cost_estimate]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_list_tiers]] |

## `parcel_pack_tier_lines`

| File | Function |
|------|----------|
| `includes/fulfillment_pack_tier_seed.php` | [[modules/fulfillment_pack_tier_seed#fulfillment_pack_tier_seed]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_fetch_tier_lines]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_save_tier_line]] |
| `includes/parcel_pack_costing.php` | [[modules/parcel_pack_costing#parcel_pack_delete_tier_line]] |

## `delivery_cancel_events`

| File | Function |
|------|----------|
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_cancel]] |
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_cancel_log_list]] |

## `online_parcel_fulfillment_deduct_lines`

| File | Function |
|------|----------|
| `includes/material_inventory.php` | [[modules/material_inventory#parcel_fulfillment_already_deducted]] |
| `includes/material_inventory.php` | [[modules/material_inventory#parcel_fulfillment_apply_deduct]] |
| `includes/material_inventory.php` | [[modules/material_inventory#parcel_fulfillment_revert_deduct]] |

## `fulfillment_packing_balances`

| File | Function |
|------|----------|
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_tables_ready]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_get_balance]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issue_create]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_consume_line]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_balances_list]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_report_damage]] |
| `includes/material_inventory.php` | [[modules/material_inventory#parcel_fulfillment_revert_deduct]] |

## `fulfillment_packing_issues`

| File | Function |
|------|----------|
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issue_create]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issues_list]] |

## `fulfillment_packing_issue_lines`

| File | Function |
|------|----------|
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issue_create]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_issues_list]] |

## `online_parcel_tracking_history`

| File | Function |
|------|----------|
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_rewaybill]] |

## `online_parcel_hold_events`

| File | Function |
|------|----------|
| `includes/online_order_parcels.php` | [[modules/online_order_parcels#online_parcels_hold]] |

## `fulfillment_packing_damage_events`

| File | Function |
|------|----------|
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_report_damage]] |
| `includes/fulfillment_packing.php` | [[modules/fulfillment_packing#fulfillment_packing_damage_list]] |

## `pos_sales`

| File | Function |
|------|----------|
| `api/v1/pos.php` | [[modules/pos#api_pos_checkout]] |
| `includes/dept_pos.php` | [[modules/dept_pos#dept_pos_checkout]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_checkout]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_fetch_checkouts]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_sold_snapshot]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_receipt_payload]] |

## `pos_receipt_items`

| File | Function |
|------|----------|
| `api/v1/pos.php` | [[modules/pos#api_pos_checkout]] |
| `includes/dept_pos.php` | [[modules/dept_pos#dept_pos_checkout]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_checkout]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_fetch_checkouts]] |
| `includes/pos_discount_requests.php` | [[modules/pos_discount_requests#pos_discount_receipt_payload]] |

## `ar_credits`

| File | Function |
|------|----------|
| `api/v1/ar.php` | [[modules/ar#api_ar_list]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_take]] |
| `api/v1/ar.php` | [[modules/ar#api_ar_mark_paid]] |

## `honey_warehouse_rows`

| File | Function |
|------|----------|
| `api/v1/honey.php` | [[modules/honey#api_honey_warehouse_list]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_fetch_warehouse]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_warehouse_stock_in]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_warehouse_stock_out]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_apply_correction_request]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_traceability]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_row_options]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_executive_edit_row]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_apply_row_cost]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_reprice_pending_for_supplier]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_pending_warehouse_rows]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_pending_count]] |
| `includes/material_inventory.php` | [[modules/material_inventory#material_raw_snapshot]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_fetch_by_honey_code]] |

## `honey_production_rows`

| File | Function |
|------|----------|
| `api/v1/honey.php` | [[modules/honey#api_honey_production_list]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_fetch_production]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_warehouse_stock_out]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_production_stock_out]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_apply_correction_request]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_cascade_warehouse_metadata]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_traceability]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_row_options]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_executive_edit_row]] |

## `honey_packaging_rows`

| File | Function |
|------|----------|
| `api/v1/honey.php` | [[modules/honey#api_honey_production_list]] |
| `api/v1/honey.php` | [[modules/honey#api_honey_packaging_list]] |
| `includes/dashboard_inventory.php` | [[modules/dashboard_inventory#dashboard_honey_packaging_snapshot]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_fetch_packaging]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_fetch_packaging_by_production]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_production_stock_out]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_packaging_stock_out_sold]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_apply_correction_request]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_cascade_warehouse_metadata]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_traceability]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_row_options]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_executive_edit_row]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_packaging_total_inventory_snapshot]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_revert_deduct]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_packaging_availability]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_available_qty]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_deduct_packaging_aggregate]] |

## `honey_sales_log`

| File | Function |
|------|----------|
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_packaging_stock_out_sold]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_build_trace_chain]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_revert_deduct]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_log_sale]] |
| `includes/honey_pos.php` | [[modules/honey_pos#honey_pos_log_sale]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_fetch_checkouts]] |
| `includes/honey_sales_report.php` | [[modules/honey_sales_report#honey_sales_sold_snapshot]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_deduct_sell]] |

## `inventory_correction_requests`

| File | Function |
|------|----------|
| `api/v1/honey.php` | [[modules/honey#api_honey_correction_submit]] |
| `api/v1/honey.php` | [[modules/honey#api_honey_correction_list]] |
| `api/v1/honey.php` | [[modules/honey#api_honey_correction_approve]] |
| `api/v1/honey.php` | [[modules/honey#api_honey_correction_reject]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_unread_count]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_unread_list]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_mark_viewed]] |

## `dept_inventory_rows`

| File | Function |
|------|----------|
| `includes/dashboard_inventory.php` | [[modules/dashboard_inventory#dashboard_dept_products_snapshot]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_fetch_row]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_ensure_row]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_produce_from_bags]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_stock_in_manual_cost]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_inventory_deduct]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_pt_pouch]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_brc_produce_pouches]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_jar_produce]] |
| `includes/dept_production.php` | [[modules/dept_production#dept_honey_comb_produce]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_apply_correction_request]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_executive_edit_row]] |
| `includes/honey_inventory.php` | [[modules/honey_inventory#honey_correction_row_context]] |
| `includes/honey_parcel_inventory.php` | [[modules/honey_parcel_inventory#honey_parcel_revert_deduct]] |

## `rts_inventory_rows`

| File | Function |
|------|----------|
| `api/v1/rts.php` | [[modules/rts#api_rts_inventory_list]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_sync_run]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_deduct_sell]] |

## `rts_sync_state`

| File | Function |
|------|----------|
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_sync_run]] |
| `includes/rts_inventory_sync.php` | [[modules/rts_inventory_sync#rts_inventory_sync_status]] |

## `suppliers`

| File | Function |
|------|----------|
| `api/v1/costing.php` | [[modules/costing#api_costing_stocks_list]] |
| `includes/dept_inventory.php` | [[modules/dept_inventory#dept_raw_recent_entries]] |
| `includes/dept_pricing.php` | [[modules/dept_pricing#dept_pricing_list_supplier_prices]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_sync_all_variant_stocks]] |
| `includes/honey_pricing.php` | [[modules/honey_pricing#honey_pricing_list_supplier_prices]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_fetch]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_fetch_by_honey_code]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_list]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_create]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_update]] |
| `includes/suppliers.php` | [[modules/suppliers#supplier_set_active]] |

