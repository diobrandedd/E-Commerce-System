import { apiGet } from "@/app/lib/api";

export interface DeptInventoryRow {
  id: number | null;
  dept_id: number;
  dept_name: string;
  product_key: string;
  product_label: string;
  unit?: string;
  stock_in_unit?: string;
  inventory_unit?: string;
  uses_supplier_pricing?: boolean;
  tea_bags_per_pouch?: number | null;
  tea_bags_per_bag?: number | null;
  grams_per_pouch?: number | null;
  grams_per_bag?: number | null;
  grams_per_sell_unit?: number | null;
  tablets_per_jar?: number | null;
  pouches_per_bag?: number | null;
  sell_units_per_bag?: number | null;
  current_qty: string | number;
  initial_qty: string | number;
  last_stock_in_at?: string | null;
  product_id?: number | null;
  selling_price?: string | number | null;
}

export function fetchDeptInventory(deptKey: string, productKey?: string) {
  const params: Record<string, string> = { dept: deptKey };
  if (productKey) params.product_key = productKey;
  return apiGet<{ rows: DeptInventoryRow[] }>("honey.php", "dept_list", params);
}
