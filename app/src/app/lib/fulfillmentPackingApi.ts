import { apiGet, apiPost } from "@/app/lib/api";

export interface PackingBalanceRow {
  stock_id: number;
  name: string;
  unit: string;
  warehouse_on_hand: number;
  issued_qty: number;
  consumed_qty: number;
  remaining_qty: number;
}

export interface PackingIssueLine {
  stock_id: number;
  stock_name: string;
  qty: number;
  unit: string;
}

export interface PackingIssueRow {
  id: number;
  note: string | null;
  est_parcels_typical: number | null;
  est_parcels_label: string | null;
  created_at: string;
  issued_by_name: string | null;
  lines: PackingIssueLine[];
}

export interface PackingEstimate {
  est_parcels: number;
  est_label: string;
  bottleneck_stock_id: number | null;
  per_tier: Array<{ tier_key: string; label: string; max_parcels: number }>;
}

export async function packingBalancesList(): Promise<{ rows: PackingBalanceRow[] }> {
  return apiGet("fulfillment_packing.php", "packing_balances_list");
}

export async function packingIssuesList(params?: { page?: number; per_page?: number }): Promise<{
  rows: PackingIssueRow[];
  total: number;
  page: number;
  per_page: number;
}> {
  return apiGet("fulfillment_packing.php", "packing_issues_list", params ?? {});
}

export async function packingIssueEstimate(lines: Array<{ stock_id: number; qty: number; unit?: string }>): Promise<{
  estimate: PackingEstimate;
}> {
  return apiPost("fulfillment_packing.php", "packing_issue_estimate", { lines });
}

export async function packingIssueCreate(payload: {
  lines: Array<{ stock_id: number; qty: number; unit?: string }>;
  note?: string;
}): Promise<{
  issue_id: number;
  estimate: PackingEstimate;
  lines: PackingIssueLine[];
}> {
  return apiPost("fulfillment_packing.php", "packing_issue_create", payload);
}
