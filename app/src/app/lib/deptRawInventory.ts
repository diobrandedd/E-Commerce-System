import { apiGet, apiPost } from "@/app/lib/api";

export interface DeptRawEntry {
  id: number;
  stock_id: number;
  qty: number;
  batching_date: string;
  supplier_batch_no: string | null;
  created_at: string;
  supplier_id: number | null;
  supplier_name: string | null;
}

export interface DeptProductionEntry {
  id: number;
  product_key: string;
  action: string;
  qty: number;
  note: string | null;
  deduct_lines: Array<Record<string, unknown>>;
  created_by_name: string | null;
  created_at: string;
}

export interface DeptProductionPreviewLine {
  stock_id: number;
  stock_name: string;
  kind: string;
  unit: string;
  qty: number;
  estimated_cost?: number;
  pool_yield_qty?: number | null;
  pool_yield_unit?: string | null;
}

export interface DeptRawSnapshot {
  slug: string;
  product_key: string;
  title: string;
  stock_name: string | null;
  unit: string;
  inventory_unit: string;
  sell_units_per_bag: number;
  bags_on_hand: number;
  finished_on_hand: number;
  finished_label: string;
  wip_tea_bags: number | null;
  tea_bags_per_pouch: number | null;
  supports_wip: boolean;
  produce_action: string | null;
  entries: DeptRawEntry[];
  production_entries: DeptProductionEntry[];
}

export function fetchDeptRawDetail(slug: string) {
  return apiGet<DeptRawSnapshot>("honey.php", "dept_raw_detail", { slug });
}

export function fetchDeptProductionPreview(productKey: string, action: string, qty: number) {
  return apiGet<{
    product_key: string;
    action: string;
    qty: number;
    lines: DeptProductionPreviewLine[];
    deduct_lines: DeptProductionPreviewLine[];
    cost_only_lines: DeptProductionPreviewLine[];
    used_formula: boolean;
  }>("honey.php", "dept_production_preview", {
    product_key: productKey,
    action,
    qty: String(qty),
  });
}

export function deptRawStockIn(payload: {
  product_key: string;
  qty: number;
  supplier_id: number;
  arrival_date: string;
  note?: string;
}) {
  return apiPost("honey.php", "dept_raw_stock_in", payload);
}

export function deptRawStockOut(payload: {
  product_key: string;
  qty: number;
  reason?: string;
}) {
  return apiPost("honey.php", "dept_raw_stock_out", payload);
}

export function deptPtMakeTeaBags(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_pt_make_tea_bags", payload);
}

export function deptPtPouch(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_pt_pouch", payload);
}

export function deptBrcProduce(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_brc_produce", payload);
}

export function deptJarProduce(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_jar_produce", payload);
}

export function deptWipWaste(payload: { qty: number; reason?: string }) {
  return apiPost("honey.php", "dept_wip_waste", payload);
}
