import { apiGet, apiPost, apiPostForm } from "@/app/lib/api";
import type { OnlineParcel } from "@/app/lib/parcelsApi";

export async function deliveriesBatchForDate(deliveryDate: string): Promise<{
  batch_id: number | null;
  delivery_date: string;
  item_count: number;
}> {
  return apiGet("deliveries.php", "batch_for_date", { delivery_date: deliveryDate });
}

export async function deliveriesParcelByTracking(tracking: string): Promise<{
  tracking_normalized: string;
  parcel: OnlineParcel | null;
  recent_delivery_items: { delivery_item_id: number; photo_filename: string; tracking_number: string | null }[];
}> {
  return apiGet("deliveries.php", "parcel_by_tracking", { tracking });
}

export async function deliveriesUploadPhoto(
  deliveryDate: string,
  photo: File,
  clientHint: string,
): Promise<{
  batch_id: number;
  items: { delivery_item_id: number; tracking_number: string | null }[];
}> {
  const form = new FormData();
  form.append("delivery_date", deliveryDate);
  form.append("photo", photo);
  if (clientHint) form.append("client_hint", clientHint);
  return apiPostForm("deliveries.php", "upload_photo", form);
}

export async function deliveriesShipParcel(
  trackingNumber: string,
  deliveryItemId: number,
  shipDate?: string,
): Promise<{ parcel: OnlineParcel; already_shipped: boolean; message?: string }> {
  return apiPost("deliveries.php", "ship_parcel", {
    tracking_number: trackingNumber,
    delivery_item_id: deliveryItemId,
    ship_date: shipDate ?? new Date().toISOString().slice(0, 10),
  });
}

export async function deliveriesReviewList(params: {
  q?: string;
  from?: string;
  to?: string;
  void_only?: boolean;
  page?: number;
  per_page?: number;
}): Promise<{ rows: DeliveryReviewRow[]; total: number; page: number; per_page: number }> {
  return apiGet("deliveries.php", "review_list", {
    ...params,
    void_only: params.void_only ? "1" : undefined,
  });
}

export async function deliveriesVoidItem(itemId: number): Promise<void> {
  await apiPost("deliveries.php", "void_item", { item_id: itemId });
}

export async function deliveriesResolveItem(itemId: number, trackingNumber: string): Promise<void> {
  await apiPost("deliveries.php", "resolve_item", { item_id: itemId, tracking_number: trackingNumber });
}

export interface DeliveryReviewRow {
  item_id: number;
  batch_id: number;
  photo_filename: string;
  item_status: string;
  tracking_number: string | null;
  voided_at: string | null;
  delivery_date: string;
}
