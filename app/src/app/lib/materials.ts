import { apiGet, apiPost } from "@/app/lib/api";

export type MaterialCategory = "raw_material" | "packaging" | "supply";

export interface MaterialStockRow {
  id: number;
  name: string;
  dept_id: number;
  dept_name: string;
  unit: string;
  category: MaterialCategory;
  on_hand: number | string;
  is_active: number | boolean;
  current_price?: string;
  last_entry_at?: string | null;
  can_adjust?: boolean;
}

export interface PackagingStockEntry {
  id: number;
  qty: number;
  supplier_batch_no: string;
  created_at: string;
  entered_by_name?: string | null;
  entry_kind?: "lot" | "count_correction" | "issue" | "other";
  lot_total_price?: number;
  lot_qty?: number;
  lot_unit?: number;
}

export type SupplyStockEntry = PackagingStockEntry;

export interface MaterialAdjustmentRequest {
  id: number;
  stock_id: number;
  stock_name: string;
  unit: string;
  category: string;
  action: "add" | "deduct" | "set_on_hand" | "issue";
  amount: string | number;
  reason: string;
  requester_name?: string | null;
  status: "pending" | "approved" | "rejected";
  summary?: string;
  requested_by_name: string;
  reviewed_by_name?: string | null;
  rejection_note?: string | null;
  created_at: string;
  reviewed_at?: string | null;
}

export interface RawSnapshot {
  honey: {
    rows: Array<{
      id: number;
      supplier_code: string;
      variant: string;
      current_qty: string | number;
      initial_qty: string | number;
      arrival_date: string;
    }>;
    total_gallons: number;
    unit: string;
  };
  raw_stocks: Record<
    string,
    {
      id?: number;
      name: string;
      unit?: string;
      on_hand?: number | string;
      entries_sum?: number | string;
      last_entry_at?: string | null;
      dept_name?: string;
    }
  >;
}

export interface Department {
  id: number;
  name: string;
}

export function listMaterials(category: MaterialCategory) {
  return apiGet<{ items: MaterialStockRow[] }>("materials.php", "catalog_list", { category });
}

export function createMaterial(payload: {
  name: string;
  dept_id: number;
  category: MaterialCategory;
  unit?: string;
}) {
  return apiPost<{ stock: MaterialStockRow }>("materials.php", "catalog_create", payload);
}

export function adjustMaterial(payload: {
  stock_id: number;
  action: "add" | "deduct";
  amount: number;
  reason: string;
}) {
  return apiPost<{ stock_id: number; on_hand: number; entry_id: number }>("materials.php", "adjust", payload);
}

export function submitMaterialRequest(payload: {
  stock_id: number;
  action: "add" | "deduct";
  amount: number;
  reason: string;
}) {
  return apiPost<{ id: number }>("materials.php", "request_submit", payload);
}

export function listMaterialRequests() {
  return apiGet<{
    requests: MaterialAdjustmentRequest[];
    readonly: boolean;
    can_approve: boolean;
    can_submit_packaging?: boolean;
    can_submit_supply?: boolean;
  }>("materials.php", "request_list");
}

export function fetchPackagingDetail(stockId: number) {
  return apiGet<{ stock: MaterialStockRow; entries: PackagingStockEntry[] }>(
    "materials.php",
    "packaging_detail",
    { stock_id: stockId }
  );
}

export function setPackagingOnHand(payload: {
  stock_id: number;
  on_hand: number;
  note?: string;
}) {
  return apiPost<{ stock_id: number; on_hand: number; entry_id: number | null }>(
    "materials.php",
    "set_on_hand",
    payload
  );
}

export function submitPackagingOnHandRequest(payload: {
  stock_id: number;
  on_hand: number;
  reason: string;
}) {
  return apiPost<{ id: number }>("materials.php", "request_submit", {
    stock_id: payload.stock_id,
    action: "set_on_hand",
    amount: payload.on_hand,
    reason: payload.reason,
  });
}

export function approveMaterialRequest(id: number) {
  return apiPost("materials.php", "request_approve", { id });
}

export function rejectMaterialRequest(id: number, rejection_note: string) {
  return apiPost("materials.php", "request_reject", { id, rejection_note });
}

export function fetchRawSnapshot() {
  return apiGet<RawSnapshot>("materials.php", "raw_snapshot");
}

export function fetchRawDetail(slug: string) {
  return apiGet<{ stock: Record<string, unknown> }>("materials.php", "raw_detail", { slug });
}

export function listMaterialDepartments() {
  return apiGet<{ departments: Department[] }>("materials.php", "departments");
}

export function stockInPackagingLot(payload: {
  stock_id: number;
  lot_total_price: number;
  lot_qty: number;
  note?: string;
}) {
  return apiPost<{
    stock_id: number;
    on_hand: number;
    current_price: number;
    lot_unit: number;
    entry_id: number;
  }>("materials.php", "stock_in_lot", payload);
}

export function fetchSupplyDetail(stockId: number) {
  return apiGet<{ stock: MaterialStockRow; entries: SupplyStockEntry[] }>(
    "materials.php",
    "supply_detail",
    { stock_id: stockId }
  );
}

export function setSupplyOnHand(payload: {
  stock_id: number;
  on_hand: number;
  note?: string;
}) {
  return apiPost<{ stock_id: number; on_hand: number; entry_id: number | null }>(
    "materials.php",
    "set_on_hand",
    payload
  );
}

export function stockInSupplyLot(payload: {
  stock_id: number;
  lot_total_price: number;
  lot_qty: number;
  note?: string;
}) {
  return apiPost<{
    stock_id: number;
    on_hand: number;
    current_price: number;
    lot_unit: number;
    entry_id: number;
  }>("materials.php", "stock_in_lot", payload);
}

export function submitSupplyIssueRequest(payload: {
  stock_id: number;
  requester_name: string;
  amount: number;
  reason: string;
}) {
  return apiPost<{ id: number }>("materials.php", "request_submit", {
    stock_id: payload.stock_id,
    action: "issue",
    amount: payload.amount,
    requester_name: payload.requester_name,
    reason: payload.reason,
  });
}

export function submitSupplyOnHandRequest(payload: {
  stock_id: number;
  on_hand: number;
  reason: string;
}) {
  return apiPost<{ id: number }>("materials.php", "request_submit", {
    stock_id: payload.stock_id,
    action: "set_on_hand",
    amount: payload.on_hand,
    reason: payload.reason,
  });
}
