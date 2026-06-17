import { apiGet, apiPost } from "@/app/lib/api";

export interface ParcelPackTierLine {
  id: number;
  tier_id: number;
  stock_id: number | null;
  stock_name?: string | null;
  line_label: string | null;
  qty_typical: number;
  qty_min: number | null;
  qty_max: number | null;
  unit: string;
  sort_order: number;
  unit_cost: number;
  cost_typical?: number;
  cost_min?: number;
  cost_max?: number;
}

export interface ParcelPackTier {
  id: number;
  family: "honey" | "dept_pt_brc" | "jar_cacao";
  tier_key: string;
  label: string;
  match_rule: Record<string, unknown>;
  is_active: boolean;
  sort_order: number;
  cost_typical: number;
  cost_min: number;
  cost_max: number;
  lines: ParcelPackTierLine[];
}

export interface ParcelCostPreview {
  items_raw?: string;
  order_shape?: Record<string, number>;
  tier_keys?: string[];
  tier_key?: string | null;
  tiers: Array<ParcelPackTier & { tier_id?: number; label?: string }>;
  cost_typical: number;
  cost_min: number;
  cost_max: number;
  warnings: string[];
  parsed_errors?: string[];
}

export interface ParcelProductAddon {
  scenario: string;
  cost_typical: number;
  cost_min: number;
  cost_max: number;
}

export async function fetchParcelTiers(): Promise<{ tiers: ParcelPackTier[]; ready: boolean }> {
  return apiGet("costing.php", "parcel_tiers_list");
}

export async function previewParcelCost(params: {
  items_raw?: string;
  tier_key?: string;
}): Promise<ParcelCostPreview> {
  if (params.tier_key) {
    return apiGet("costing.php", "parcel_cost_preview", { tier_key: params.tier_key });
  }
  return apiPost("costing.php", "parcel_cost_preview", { items_raw: params.items_raw ?? "" });
}

export async function saveParcelTierLine(body: {
  tier_id: number;
  line_id?: number;
  stock_id?: number | null;
  line_label?: string;
  qty_typical: number;
  qty_min?: number | null;
  qty_max?: number | null;
  unit?: string;
  sort_order?: number;
}): Promise<{ line_id: number; lines: ParcelPackTierLine[]; tier: ParcelPackTier }> {
  return apiPost("costing.php", "parcel_tier_line_save", body);
}

export async function deleteParcelTierLine(tierId: number, lineId: number): Promise<{ tier: ParcelPackTier }> {
  return apiPost("costing.php", "parcel_tier_line_delete", { tier_id: tierId, line_id: lineId });
}

export const PARCEL_FAMILY_LABELS: Record<ParcelPackTier["family"], string> = {
  honey: "Honey parcels",
  dept_pt_brc: "Paragis Tea & Black Rice Coffee",
  jar_cacao: "Jar Cacao",
};
