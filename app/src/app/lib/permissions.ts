export type { PermissionKey } from "./permissionKeys";
import type { PermissionKey } from "./permissionKeys";

/** Route path (no query) → permission required to see nav link / access page */
export const ROUTE_PERMISSIONS: Record<string, PermissionKey | PermissionKey[]> = {
  "/verify-barcode": "barcode.verify",
  "/inventory": "inventory.entries.view",
  "/stock-input": "inventory.entries.view",
  "/daily-count": "inventory.daily_count",
  "/beginning-inventory": "inventory.entries.view",
  "/edit-requests": "inventory.edit_requests.submit",
  "/inventory/check": "inventory.check.scan",
  "/inventory/requests": "inventory.requests.view",
  "/inventory/monthly-count": "inventory.monthly_count",
  "/inventory/raw-materials": [
    "inventory.raw.view",
    "honey.view",
    "dept.paragis.view",
    "dept.brc.view",
    "dept.cacao.view",
    "dept.comb.view",
  ],
  "/inventory/raw-materials/honey/warehouse": "honey.view",
  "/inventory/raw-materials/honey/production": ["honey.ops", "honey.bottle.request", "honey.bottle.scan", "honey.bottle.generate"],
  "/inventory/raw-materials/honey/packaging": "honey.view",
  "/inventory/raw-materials/paragis": "dept.paragis.view",
  "/inventory/raw-materials/black-rice": "dept.brc.view",
  "/inventory/raw-materials/cacao": "dept.cacao.view",
  "/inventory/raw-materials/honey-comb": "dept.comb.view",
  "/inventory/packaging-materials": "packaging.view",
  "/inventory/supplies": "supplies.view",
  "/inventory/products/honey": "inventory.products.view",
  "/inventory/products/paragis-tea": "dept.paragis.view",
  "/inventory/products/black-rice-coffee": "dept.brc.view",
  "/inventory/products/cacao-tablets": "dept.cacao.view",
  "/inventory/products/honey-comb": "dept.comb.view",
  "/inventory/leak-damage": "inventory.leak_damage.view",
  "/inventory/cacao-brc": ["dept.brc.view", "dept.cacao.view"],
  "/staff": "staff.manage",
  "/reports": "reports.view",
  "/sales": "sales.view",
  "/sales/bulk-orders": "sales.bulk_orders",
  "/pos": "pos.sell",
  "/suppliers": "suppliers.view",
  "/costing": "costing.view",
  "/ar": "ar.take",
  "/waybill/online-orders": "csr.online_orders",
  "/rts": "rts.view",
  "/rts/record": "rts.record",
  "/rts/records": "rts.view",
  "/rts/swapped": "rts.view",
  "/rts/leaks": "rts.view",
  "/rts/damaged": "rts.view",
  "/rts/barcode-labels": "rts.barcode_labels",
  "/rts/dashboard": "rts.view",
  "/settings": "settings.manage",
  "/deliveries": "deliveries.view",
  "/deliveries/pack-scan": "deliveries.pack_scan",
  "/deliveries/review": "deliveries.review",
  "/deliveries/cancelled": "deliveries.view",
  "/fulfillment/partner-sold-reconcile": "csr.reconcile.partner_sold",
  "/fulfillment/courier-soa-reconcile": "csr.reconcile.courier_soa",
  "/fulfillment/historical-orders-import": "csr.historical_import",
  "/fulfillment/online-parcels": "csr.parcels.view",
  "/fulfillment/packing-materials": "packing.materials.view",
  "/fulfillment/packing-materials/log": "packing.materials.view",
};

export function routePermission(path: string): PermissionKey | PermissionKey[] | undefined {
  const base = path.split("?")[0];
  if (ROUTE_PERMISSIONS[base]) return ROUTE_PERMISSIONS[base];
  if (base.startsWith("/inventory/raw-materials/paragis")) return "dept.paragis.view";
  if (base.startsWith("/inventory/raw-materials/black-rice")) return "dept.brc.view";
  if (base.startsWith("/inventory/raw-materials/cacao")) return "dept.cacao.view";
  if (base.startsWith("/inventory/raw-materials/honey-comb")) return "dept.comb.view";
  if (base.startsWith("/inventory/raw-materials/") && !base.includes("/honey/")) {
    return ["inventory.raw.view", "dept.paragis.view", "dept.brc.view", "dept.cacao.view", "dept.comb.view"];
  }
  if (base.startsWith("/inventory/products/paragis")) return "dept.paragis.view";
  if (base.startsWith("/inventory/products/black-rice")) return "dept.brc.view";
  if (base.startsWith("/inventory/products/cacao")) return "dept.cacao.view";
  if (base.startsWith("/inventory/products/honey-comb")) return "dept.comb.view";
  if (base.startsWith("/inventory/products/")) {
    return ["inventory.products.view", "dept.paragis.view", "dept.brc.view", "dept.cacao.view", "dept.comb.view"];
  }
  return undefined;
}

function permissionGranted(permissions: string[], key: PermissionKey): boolean {
  if (permissions.includes(key)) return true;
  if (/^dept\.(paragis|brc|cacao|comb)\.produce$/.test(key) && permissions.includes("honey.dept_produce")) {
    return true;
  }
  if (/^dept\.(paragis|brc|cacao|comb)\.barcodes$/.test(key) && permissions.includes("dept.pouch_barcodes")) {
    return true;
  }
  return false;
}

export function hasPermission(
  permissions: string[],
  role: string | undefined,
  key: PermissionKey | PermissionKey[],
): boolean {
  if (role === "executive") return true;
  const keys = Array.isArray(key) ? key : [key];
  return keys.some(k => permissionGranted(permissions, k));
}

export function canAccessRoute(
  permissions: string[],
  role: string | undefined,
  path: string,
): boolean {
  const perm = routePermission(path);
  if (!perm) return true;
  return hasPermission(permissions, role, perm);
}
