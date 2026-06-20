import { apiGet, apiPost } from "@/app/lib/api";

export type EditRequestStatus = "pending" | "approved" | "rejected";

export interface EditRequest {
  id: number;
  table_name: string;
  record_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  reason: string;
  status: EditRequestStatus;
  rejection_note: string | null;
  created_at: string;
  reviewed_at: string | null;
  requested_by_name: string;
  dept_name: string | null;
  reviewer_name: string | null;
}

export async function listEditRequests(
  isReviewer: boolean,
  statusFilter?: "pending" | "approved" | "rejected" | "all",
): Promise<{ requests: EditRequest[]; is_reviewer: boolean }> {
  return apiGet("edit_requests.php", "list", isReviewer && statusFilter ? { status: statusFilter } : undefined);
}

export async function submitEditRequest(payload: {
  table_name: string;
  record_id: number;
  field_name: string;
  old_value?: string;
  new_value?: string;
  reason: string;
}): Promise<{ id: number }> {
  return apiPost("edit_requests.php", "submit", payload);
}

export async function approveEditRequest(id: number): Promise<void> {
  await apiPost("edit_requests.php", "approve", { id });
}

export async function rejectEditRequest(id: number, rejection_note: string): Promise<void> {
  await apiPost("edit_requests.php", "reject", { id, rejection_note });
}

/** High-level category for unified Requests hub tables. */
export function formatEditRequestCategory(table: string): string {
  if (table === "delivery_batch_items") return "Deliveries";
  if (table === "stock_entries" || table === "stocks" || table === "beginning_inventory") return "Inventory";
  if (table === "products") return "Products";
  return table.replace(/_/g, " ");
}

export function formatEditRequestTable(table: string, recordId: number): string {
  if (table === "delivery_batch_items") {
    return `Delivery photo #${recordId}`;
  }
  return `${table} #${recordId}`;
}

export function formatEditRequestField(table: string, field: string): string {
  if (table === "delivery_batch_items") {
    if (field === "void" || field === "delete") return "Delete row";
    if (field === "tracking_number") return "Edit tracking";
  }
  return field.replace(/_/g, " ");
}

export function formatEditRequestChange(r: EditRequest): string {
  const field = formatEditRequestField(r.table_name, r.field_name);
  if (r.field_name === "delete" || r.field_name === "void") {
    return field;
  }
  const oldV = r.old_value ?? "—";
  const newV = r.new_value ?? "—";
  return `${field}: ${oldV} → ${newV}`;
}
