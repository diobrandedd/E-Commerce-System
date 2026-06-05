import { api, apiGet, apiPost } from "@/app/lib/api";

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
    other_items?: { label: string; qty: number; raw?: string }[];
  } | null;
  recipient_name: string;
  courier: string;
  order_no: string | null;
  source_pipeline: string;
  status: "packing" | "shipped" | "cancelled" | "returned";
  ship_date: string | null;
  deducted_at: string | null;
  created_at: string;
  packing_photo: OnlineParcelPackingPhoto | null;
}

export async function parcelsList(params: {
  q?: string;
  status?: string;
  page?: number;
  per_page?: number;
}): Promise<{ rows: OnlineParcel[]; total: number }> {
  return apiGet("parcels.php", "list", params);
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
  other_items: { label: string; qty: number; raw: string }[];
  errors: string[];
}> {
  return apiPost("parcels.php", "parse_preview", { items });
}
