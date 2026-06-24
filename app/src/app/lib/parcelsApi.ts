import { api, apiGet, apiPost, API_BASE } from "@/app/lib/api";

export interface OnlineParcelPackingPhoto {
  delivery_item_id: number;
  photo_filename: string;
  photo_url: string;
  delivery_date: string;
}

export interface OnlineParcel {
  id: number;
  csr_batch_id: number | null;
  tracking_number: string;
  items_raw: string;
  items_parsed: {
    deltas?: { variant: string; size: string; qty: number }[];
    other_items?: { label: string; qty: number; raw?: string; product_key?: string }[];
  } | null;
  recipient_name: string;
  courier: string;
  order_no: string | null;
  source_pipeline: string;
  partner_seller: string | null;
  status: "packing" | "shipped" | "on_hold" | "cancelled" | "returned" | "rts";
  ship_date: string | null;
  return_date?: string | null;
  deducted_at: string | null;
  rts_case_id?: number | null;
  rts_recorded_at?: string | null;
  created_at: string;
  packing_photo: OnlineParcelPackingPhoto | null;
}

export type ParcelsListSort =
  | "created_at"
  | "order_no"
  | "tracking_number"
  | "courier"
  | "recipient_name";

export interface ParcelsListParams {
  q?: string;
  status?: string;
  page?: number;
  per_page?: number;
  date_from?: string;
  date_to?: string;
  partner_seller?: string;
  source_pipeline?: string;
  courier?: string;
  sort?: ParcelsListSort;
  dir?: "asc" | "desc";
}

export async function parcelsList(params: ParcelsListParams): Promise<{ rows: OnlineParcel[]; total: number }> {
  return apiGet("parcels.php", "list", params);
}

export async function parcelsFilterOptions(): Promise<{
  couriers: string[];
  pipelines: string[];
  partner_sellers: string[];
}> {
  return apiGet("parcels.php", "filter_options");
}

export async function parcelsExportSelected(ids: number[]): Promise<{
  download_token: string;
  download_filename: string;
  row_count: number;
}> {
  return apiPost("parcels.php", "export", { ids });
}

export function triggerParcelsExportDownload(token: string): void {
  const url = `${API_BASE}/parcels.php?action=export_download&token=${encodeURIComponent(token)}`;
  window.open(url, "_blank", "noopener,noreferrer");
}

export async function parcelsLookup(q: string): Promise<{ parcel: OnlineParcel; tracking_normalized: string }> {
  return apiGet("parcels.php", "lookup", { q });
}

export async function parcelsGetByTracking(tracking: string): Promise<OnlineParcel> {
  const data = await apiGet<{ parcel: OnlineParcel }>("parcels.php", "get", { tracking });
  return data.parcel;
}

export async function parcelsParsePreview(items: string): Promise<{
  deltas: { variant: string; size: string; qty: number }[];
  other_items: { label: string; qty: number; raw: string; product_key?: string }[];
  errors: string[];
}> {
  return apiPost("parcels.php", "parse_preview", { items });
}

export async function parcelsUpdateItems(parcelId: number, items: string): Promise<{ parcel: OnlineParcel }> {
  return apiPost("parcels.php", "update_items", { parcel_id: parcelId, items });
}
