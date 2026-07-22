import { apiPost, apiPostForm, getCsrfToken } from "@/app/lib/api";

export type HistoricalPipeline = "vip" | "partner" | "shopee_spx" | "tiktok";
export type ConflictAction = "keep" | "use_uploaded";

export interface HistoricalUploadedRow {
  row_key: string;
  tracking_number: string;
  recipient_name: string;
  order_no: string | null;
  items_raw: string;
  items_parsed_json: string | null;
  parse_ok: boolean;
  parse_errors: string[];
  courier: string;
  source_pipeline: string;
  partner_seller: string | null;
  status: string;
  ship_date: string;
}

export interface HistoricalExistingSnapshot {
  id: number;
  tracking_number: string;
  recipient_name: string;
  order_no: string | null;
  items_raw: string;
  courier: string;
  source_pipeline: string;
  partner_seller: string | null;
  status: string;
  ship_date: string | null;
  rts_case_id: number | null;
  has_packing_photo?: boolean;
  sensitive: boolean;
}

export interface HistoricalConflict {
  row_key: string;
  tracking_number: string;
  default_action: ConflictAction;
  existing: HistoricalExistingSnapshot;
  uploaded: HistoricalUploadedRow;
  diff_fields: string[];
}

export interface HistoricalFailedRow {
  row_key: string;
  parcel_ref: string;
  receiver: string;
  remarks: string;
  error: string;
}

export interface HistoricalPreviewResult {
  preview_token: string;
  month_label: string;
  ship_date: string;
  pipeline: HistoricalPipeline;
  counts: {
    new: number;
    conflicts: number;
    failed: number;
    total_excel_rows: number;
  };
  new: HistoricalUploadedRow[];
  conflicts: HistoricalConflict[];
  failed: HistoricalFailedRow[];
}

export interface HistoricalCommitResult {
  month_label: string;
  ship_date: string;
  pipeline: string;
  inserted: number;
  updated: number;
  kept: number;
  failed_preview: number;
  errors: { row_key: string; tracking_number: string; error: string }[];
}

export async function historicalOrdersStashUpload(file: File): Promise<string> {
  const form = new FormData();
  form.append("file", file);
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("historical_orders.php", "stash", form);
  return data.token;
}

export async function historicalOrdersPreview(payload: {
  token: string;
  pipeline: HistoricalPipeline;
  year: number;
  month: number;
  profile?: string;
}): Promise<HistoricalPreviewResult> {
  return apiPost<HistoricalPreviewResult>("historical_orders.php", "preview", payload);
}

export async function historicalOrdersCommit(payload: {
  preview_token: string;
  import_new?: boolean;
  decisions: { row_key: string; action: ConflictAction }[];
}): Promise<HistoricalCommitResult> {
  return apiPost<HistoricalCommitResult>("historical_orders.php", "commit", payload);
}
