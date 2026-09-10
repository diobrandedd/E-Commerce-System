/** Auto-generated from includes/permissions.php — run: php tools/gen_permission_keys.php */
export type PermissionKey =
  | "ar.mark_paid"
  | "ar.take"
  | "ar.view_all"
  | "barcode.verify"
  | "costing.manage"
  | "costing.view"
  | "csr.historical_import"
  | "csr.online_orders"
  | "csr.parcels.edit_items"
  | "csr.parcels.view"
  | "csr.reconcile.courier_soa"
  | "csr.reconcile.partner_sold"
  | "deliveries.cancel_restock"
  | "deliveries.delete"
  | "deliveries.pack_scan"
  | "deliveries.review"
  | "deliveries.ship"
  | "deliveries.view"
  | "dept.brc.barcodes"
  | "dept.brc.produce"
  | "dept.brc.view"
  | "dept.cacao.barcodes"
  | "dept.cacao.produce"
  | "dept.cacao.view"
  | "dept.comb.barcodes"
  | "dept.comb.produce"
  | "dept.comb.view"
  | "dept.paragis.barcodes"
  | "dept.paragis.produce"
  | "dept.paragis.view"
  | "dept.pouch_barcodes"
  | "honey.bottle.generate"
  | "honey.bottle.request"
  | "honey.bottle.scan"
  | "honey.dept_produce"
  | "honey.executive_edit"
  | "honey.mutate"
  | "honey.ops"
  | "honey.pricing.view_cost"
  | "honey.view"
  | "honey.warehouse_barcode.generate"
  | "honey.warehouse_barcode.request"
  | "inventory.check.manage"
  | "inventory.check.scan"
  | "inventory.check.view_all"
  | "inventory.daily_count"
  | "inventory.edit_requests.approve"
  | "inventory.edit_requests.submit"
  | "inventory.entries.add"
  | "inventory.entries.view"
  | "inventory.leak_damage.record"
  | "inventory.leak_damage.view"
  | "inventory.monthly_count"
  | "inventory.products.view"
  | "inventory.raw.mutate"
  | "inventory.raw.view"
  | "inventory.requests.approve"
  | "inventory.requests.view"
  | "packaging.adjust"
  | "packaging.catalog"
  | "packaging.deduct"
  | "packaging.edit_price"
  | "packaging.request"
  | "packaging.set_on_hand"
  | "packaging.stock_in"
  | "packaging.view"
  | "packing.materials.issue"
  | "packing.materials.view"
  | "packing.on_hold.view"
  | "pos.discounts.approve"
  | "pos.sell"
  | "reports.view"
  | "rts.admin"
  | "rts.barcode_labels"
  | "rts.damaged.use"
  | "rts.inventory.set"
  | "rts.record"
  | "rts.sync.run"
  | "rts.view"
  | "sales.bulk_orders"
  | "sales.view"
  | "sales.view_amounts"
  | "settings.manage"
  | "staff.manage"
  | "suppliers.manage"
  | "suppliers.view"
  | "supplies.catalog"
  | "supplies.issue"
  | "supplies.request"
  | "supplies.stock_in"
  | "supplies.view";

export const PERMISSION_KEYS: readonly PermissionKey[] = [
  "ar.mark_paid",
  "ar.take",
  "ar.view_all",
  "barcode.verify",
  "costing.manage",
  "costing.view",
  "csr.historical_import",
  "csr.online_orders",
  "csr.parcels.edit_items",
  "csr.parcels.view",
  "csr.reconcile.courier_soa",
  "csr.reconcile.partner_sold",
  "deliveries.cancel_restock",
  "deliveries.delete",
  "deliveries.pack_scan",
  "deliveries.review",
  "deliveries.ship",
  "deliveries.view",
  "dept.brc.barcodes",
  "dept.brc.produce",
  "dept.brc.view",
  "dept.cacao.barcodes",
  "dept.cacao.produce",
  "dept.cacao.view",
  "dept.comb.barcodes",
  "dept.comb.produce",
  "dept.comb.view",
  "dept.paragis.barcodes",
  "dept.paragis.produce",
  "dept.paragis.view",
  "dept.pouch_barcodes",
  "honey.bottle.generate",
  "honey.bottle.request",
  "honey.bottle.scan",
  "honey.dept_produce",
  "honey.executive_edit",
  "honey.mutate",
  "honey.ops",
  "honey.pricing.view_cost",
  "honey.view",
  "honey.warehouse_barcode.generate",
  "honey.warehouse_barcode.request",
  "inventory.check.manage",
  "inventory.check.scan",
  "inventory.check.view_all",
  "inventory.daily_count",
  "inventory.edit_requests.approve",
  "inventory.edit_requests.submit",
  "inventory.entries.add",
  "inventory.entries.view",
  "inventory.leak_damage.record",
  "inventory.leak_damage.view",
  "inventory.monthly_count",
  "inventory.products.view",
  "inventory.raw.mutate",
  "inventory.raw.view",
  "inventory.requests.approve",
  "inventory.requests.view",
  "packaging.adjust",
  "packaging.catalog",
  "packaging.deduct",
  "packaging.edit_price",
  "packaging.request",
  "packaging.set_on_hand",
  "packaging.stock_in",
  "packaging.view",
  "packing.materials.issue",
  "packing.materials.view",
  "packing.on_hold.view",
  "pos.discounts.approve",
  "pos.sell",
  "reports.view",
  "rts.admin",
  "rts.barcode_labels",
  "rts.damaged.use",
  "rts.inventory.set",
  "rts.record",
  "rts.sync.run",
  "rts.view",
  "sales.bulk_orders",
  "sales.view",
  "sales.view_amounts",
  "settings.manage",
  "staff.manage",
  "suppliers.manage",
  "suppliers.view",
  "supplies.catalog",
  "supplies.issue",
  "supplies.request",
  "supplies.stock_in",
  "supplies.view",
] as const;
