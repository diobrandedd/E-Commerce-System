import { apiGet, apiPost } from "@/app/lib/api";

export const BUBBLE_WIDTHS_CM = [13, 17, 22] as const;
export type BubbleWidthCm = (typeof BUBBLE_WIDTHS_CM)[number];

export interface PackingBalanceRow {
  stock_id: number;
  name: string;
  unit: string;
  warehouse_on_hand: number;
  issued_qty: number;
  consumed_qty: number;
  damaged_qty: number;
  remaining_qty: number;
  is_bubble_sized?: boolean;
}

export interface PackingIssueLine {
  stock_id: number;
  stock_name: string;
  qty: number;
  unit: string;
  bubble_width_cm?: number;
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

export interface PackingDamageRow {
  id: number;
  stock_id: number;
  stock_name: string;
  qty: number;
  note: string;
  created_at: string;
  reported_by_name: string | null;
}

export interface PackingEstimate {
  est_parcels: number;
  est_label: string;
  bottleneck_stock_id: number | null;
  per_tier: Array<{ tier_key: string; label: string; max_parcels: number }>;
}

export function isBubbleGenericName(name: string): boolean {
  return name.toLowerCase().trim() === "bubble wrap roll";
}

export function isBubbleSizedName(name: string): boolean {
  return /^bubble wrap roll - \d+ cm$/i.test(name.trim());
}

export function isFoamGenericName(name: string): boolean {
  return name.toLowerCase().trim() === "insulation foam roll";
}

export function isFoamSizedName(name: string): boolean {
  return /^insulation foam roll - /i.test(name.trim());
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

export async function packingIssueEstimate(
  lines: Array<{ stock_id: number; qty: number; unit?: string; bubble_width_cm?: number }>,
): Promise<{
  estimate: PackingEstimate;
}> {
  return apiPost("fulfillment_packing.php", "packing_issue_estimate", { lines });
}

export async function packingIssueCreate(payload: {
  lines: Array<{ stock_id: number; qty: number; unit?: string; bubble_width_cm?: number }>;
  note?: string;
}): Promise<{
  issue_id: number;
  estimate: PackingEstimate;
  lines: PackingIssueLine[];
}> {
  return apiPost("fulfillment_packing.php", "packing_issue_create", payload);
}

export async function packingDamageReport(payload: {
  stock_id: number;
  qty: number;
  note: string;
}): Promise<{
  stock_id: number;
  qty: number;
  damaged_qty: number;
  remaining_qty: number;
  event_id: number;
}> {
  return apiPost("fulfillment_packing.php", "packing_damage_report", payload);
}

export async function packingDamageList(params?: { page?: number; per_page?: number }): Promise<{
  rows: PackingDamageRow[];
  total: number;
  page: number;
  per_page: number;
}> {
  return apiGet("fulfillment_packing.php", "packing_damage_list", params ?? {});
}
