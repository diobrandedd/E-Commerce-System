import { apiGet, apiPost, apiPostForm } from "@/app/lib/api";
import { submitEditRequest } from "@/app/lib/editRequestsApi";
import type { OnlineParcel } from "@/app/lib/parcelsApi";

export async function deliveriesBatchForDate(deliveryDate: string): Promise<{
  batch_id: number | null;
  delivery_date: string;
  item_count: number;
}> {
  return apiGet("deliveries.php", "batch_for_date", { delivery_date: deliveryDate });
}

export interface DeliveryExistingRecording {
  delivery_item_id: number;
  photo_filename: string;
  item_status: string;
  tracking_number: string | null;
  delivery_date: string;
}

export async function deliveriesParcelByTracking(tracking: string): Promise<{
  tracking_normalized: string;
  parcel: OnlineParcel | null;
  existing_recordings: DeliveryExistingRecording[];
  already_recorded: boolean;
}> {
  return apiGet("deliveries.php", "parcel_by_tracking", { tracking });
}

export async function deliveriesUploadPhoto(
  deliveryDate: string,
  photo: File,
  clientHint: string,
): Promise<{
  batch_id: number;
  batch_item_count: number;
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

export interface DeliveryCancelResult {
  parcel: OnlineParcel;
  already_cancelled: boolean;
  previous_status: "packing" | "shipped" | "cancelled" | "returned" | string;
  inventory_reverted: boolean;
  cancel_event_id: number | null;
  message?: string;
}

export async function deliveriesCancelParcelScan(
  trackingNumber: string,
  note?: string,
): Promise<DeliveryCancelResult> {
  return apiPost("deliveries.php", "cancel_parcel_scan", {
    tracking_number: trackingNumber,
    cancel_source: "scanner",
    note: note?.trim() ? note.trim() : undefined,
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

export interface DeliveryCancelLogRow {
  id: number;
  online_parcel_id: number;
  tracking_number: string;
  previous_status: string;
  inventory_reverted: boolean;
  cancel_source: string;
  note: string | null;
  cancelled_by_user_id: number | null;
  cancelled_by_name: string | null;
  cancelled_by_username: string | null;
  created_at: string;
  recipient_name: string | null;
  items_raw: string | null;
  order_no: string | null;
  courier: string | null;
}

export async function deliveriesCancelLogList(params: {
  q?: string;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}): Promise<{ rows: DeliveryCancelLogRow[]; total: number; page: number; per_page: number }> {
  return apiGet("deliveries.php", "cancel_log_list", params);
}

export async function deliveriesDeleteItem(itemId: number): Promise<void> {
  await apiPost("deliveries.php", "delete_item", { item_id: itemId });
}

export async function deliveriesResolveItem(itemId: number, trackingNumber: string): Promise<void> {
  await apiPost("deliveries.php", "resolve_item", { item_id: itemId, tracking_number: trackingNumber });
}

/** Production manager — delete/edit goes through executive edit-request approval. */
export async function deliveriesRequestDelete(itemId: number, reason: string): Promise<{ id: number }> {
  return submitEditRequest({
    table_name: "delivery_batch_items",
    record_id: itemId,
    field_name: "delete",
    reason,
  });
}

export async function deliveriesRequestEditContent(
  itemId: number,
  trackingNumber: string,
  reason: string,
): Promise<{ id: number }> {
  return submitEditRequest({
    table_name: "delivery_batch_items",
    record_id: itemId,
    field_name: "tracking_number",
    new_value: trackingNumber,
    reason,
  });
}

export interface DeliveryReviewParcel {
  id: number;
  tracking_number: string;
  recipient_name: string;
  items_raw: string;
  order_no: string | null;
  courier: string;
  status: string;
}

export interface DeliveryReviewRow {
  item_id: number;
  batch_id: number;
  photo_filename: string;
  photo_url: string;
  item_status: string;
  tracking_number: string | null;
  voided_at: string | null;
  delivery_date: string;
  parcel: DeliveryReviewParcel | null;
}
