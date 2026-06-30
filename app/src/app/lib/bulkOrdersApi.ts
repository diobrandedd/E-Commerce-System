import { apiGet, apiPost } from "@/app/lib/api";

export type BulkOrderStatus = "draft" | "confirmed" | "voided";
export type BulkNegotiationMode = "margin_pct" | "unit_price" | "total_price";
export type HoneyVariant = "light" | "amber" | "dark";

export interface BulkWarehouseRow {
  warehouse_row_id: number;
  supplier_code: string;
  arrival_date: string;
  variant: string;
  current_qty: number;
  unit_cost_per_gallon: number | null;
  pricing_status: string;
}

export interface BulkAllocationInput {
  warehouse_row_id: number;
  qty_gallons: number;
}

export interface BulkLineInput {
  variant: HoneyVariant;
  qty_gallons: number;
  negotiation_mode: BulkNegotiationMode;
  target_margin_pct?: number | null;
  unit_sale_price?: number;
  total_deal_price?: number;
  allocations: BulkAllocationInput[];
}

export interface BulkAllocationSlice {
  warehouse_row_id: number;
  supplier_code: string;
  variant: string;
  qty_gallons: number;
  unit_cost_per_gallon: number;
  line_cogs_slice: number;
}

export interface BulkPreviewLine {
  variant: HoneyVariant;
  qty_gallons: number;
  unit_sale_price: number;
  negotiation_mode: BulkNegotiationMode;
  target_margin_pct: number | null;
  line_cogs: number;
  cogs_per_gallon: number;
  break_even_total: number;
  line_revenue: number;
  line_margin: number;
  line_margin_pct: number | null;
  below_cost: boolean;
  allocations: BulkAllocationSlice[];
  margin_suggestions: { margin_pct: number; unit_sale_price: number; total_revenue: number }[];
}

export interface BulkOrderSummary {
  id: number;
  order_no: string;
  customer_name: string;
  partner_label: string | null;
  notes: string | null;
  shipment_date: string | null;
  courier: string | null;
  tracking_ref: string | null;
  status: BulkOrderStatus;
  total_gallons: number;
  total_cogs: number;
  total_revenue: number;
  total_margin: number;
  margin_pct: number | null;
  created_at: string | null;
  confirmed_at: string | null;
  voided_at: string | null;
}

export interface BulkOrderLine extends BulkOrderSummary {
  id: number;
  variant: HoneyVariant;
  qty_gallons: number;
  unit_sale_price: number;
  line_cogs: number;
  cogs_per_gallon: number;
  line_revenue: number;
  line_margin: number;
  line_margin_pct: number | null;
  negotiation_mode: BulkNegotiationMode | null;
  target_margin_pct: number | null;
  allocations: (BulkAllocationSlice & { id?: number })[];
}

export interface BulkOrderDetail {
  order: BulkOrderSummary;
  lines: BulkOrderLine[];
  supplier_codes_used: { supplier_code: string; qty_gallons: number }[];
}

export interface BulkOrderPayload {
  customer_name: string;
  partner_label?: string;
  notes?: string;
  shipment_date?: string;
  courier?: string;
  tracking_ref?: string;
  lines: BulkLineInput[];
}

export function bulkOrdersList(status = "all") {
  return apiGet<{ orders: BulkOrderSummary[]; missing_migration?: boolean }>(
    "bulk_orders.php",
    "list",
    status === "all" ? undefined : { status },
  );
}

export function bulkOrdersGet(id: number) {
  return apiGet<BulkOrderDetail>("bulk_orders.php", "get", { id: String(id) });
}

export function bulkOrdersWarehouseRows(variant: HoneyVariant) {
  return apiGet<{ rows: BulkWarehouseRow[] }>("bulk_orders.php", "warehouse_rows", { variant });
}

export function bulkOrdersSuggestAllocations(variant: HoneyVariant, qty_gallons: number) {
  return apiPost<{ allocations: BulkAllocationInput[] }>("bulk_orders.php", "suggest_allocations", {
    variant,
    qty_gallons,
  });
}

export function bulkOrdersPreviewCost(lines: BulkLineInput[]) {
  return apiPost<{ lines: BulkPreviewLine[]; totals: BulkOrderSummary }>("bulk_orders.php", "preview_cost", {
    lines,
  });
}

export function bulkOrdersCreate(payload: BulkOrderPayload) {
  return apiPost<BulkOrderDetail>("bulk_orders.php", "create", payload);
}

export function bulkOrdersUpdate(id: number, payload: BulkOrderPayload) {
  return apiPost<BulkOrderDetail>("bulk_orders.php", "update", { id, ...payload });
}

export function bulkOrdersConfirm(id: number) {
  return apiPost<BulkOrderDetail>("bulk_orders.php", "confirm", { id });
}

export function bulkOrdersVoid(id: number) {
  return apiPost<BulkOrderDetail>("bulk_orders.php", "void", { id });
}

export function bulkOrdersDelete(id: number) {
  return apiPost<{ deleted: boolean }>("bulk_orders.php", "delete", { id });
}
