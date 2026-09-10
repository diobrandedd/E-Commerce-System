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

export interface DeptBagPool {
  bags_on_hand: number;
  bags_received_gross: number;
  grams_remaining: number;
  grams_capacity: number;
  grams_per_bag: number;
  grams_per_pouch: number;
  grams_per_tea_bag: number;
  tea_bags_per_pouch: number;
  pouches_per_bag: number;
  overdrawn: boolean;
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
  bag_pool_workflow?: boolean;
  bag_pool?: DeptBagPool;
  produce_action: string | null;
  entries: DeptRawEntry[];
  production_entries: DeptProductionEntry[];
}

export function fetchDeptRawDetail(slug: string) {
  return apiGet<DeptRawSnapshot>("honey.php", "dept_raw_detail", { slug });
}

export type DeptProductionDailyRow = {
  production_date: string;
  qty_total: number;
  run_count: number;
};

export function fetchDeptProductionLog(params: {
  product_key: string;
  log_action?: string;
  date?: string;
  page?: number;
  per_page?: number;
  group_by?: "day";
}) {
  return apiGet<{
    rows: Array<DeptProductionEntry | DeptProductionDailyRow>;
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
  }>("honey.php", "dept_production_log", {
    product_key: params.product_key,
    ...(params.log_action ? { log_action: params.log_action } : {}),
    ...(params.date ? { date: params.date } : {}),
    ...(params.group_by ? { group_by: params.group_by } : {}),
    page: String(params.page ?? 1),
    per_page: String(params.per_page ?? 10),
  });
}

/** Daily totals for a production action (pouch / produce_brc / produce_jar). */
export function fetchDeptProductionDaily(params: {
  product_key: string;
  log_action: string;
  date?: string;
  page?: number;
  per_page?: number;
}) {
  return apiGet<{
    rows: DeptProductionDailyRow[];
    total: number;
    page: number;
    per_page: number;
    total_pages: number;
  }>("honey.php", "dept_production_log", {
    product_key: params.product_key,
    log_action: params.log_action,
    group_by: "day",
    ...(params.date ? { date: params.date } : {}),
    page: String(params.page ?? 1),
    per_page: String(params.per_page ?? 10),
  });
}

/** Log-only: append qty for a calendar day (no stock / packaging changes). */
export function recordDeptProductionDaily(payload: {
  product_key: string;
  qty: number;
  production_date: string;
  note?: string;
}) {
  return apiPost<{
    id: number;
    product_key: string;
    action: string;
    qty: number;
    production_date: string;
  }>("honey.php", "dept_production_daily_record", payload);
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
    derived_tea_bags?: number;
    packable_pouches?: number;
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

export function deptPtUseRawBags(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_pt_use_raw_bags", payload);
}

export function deptPtMakeTeaBags(payload: { qty: number; note?: string }) {
  return apiPost("honey.php", "dept_pt_make_tea_bags", payload);
}

export function deptWipWaste(payload: { qty: number; reason?: string }) {
  return apiPost("honey.php", "dept_wip_waste", payload);
}
