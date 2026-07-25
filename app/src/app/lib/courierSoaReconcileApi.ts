import { apiPost, apiPostForm, API_BASE, getCsrfToken } from "@/app/lib/api";

export type CourierSoaFormat = "jnt" | "spx";

export interface CourierSoaReconcileSummary {
  soa_rows: number;
  soa_unique: number;
  soa_duplicates: number;
  our_shipped: number;
  matched: number;
  missing_from_soa: number;
  extra_in_soa: number;
  file_count?: number;
}

export interface CourierSoaMissingRow {
  tracking_number: string;
  ship_date: string | null;
  order_no: string;
  courier: string;
}

export interface CourierSoaExtraRow {
  row: number;
  tracking_number: string;
  source_file?: string;
  format?: CourierSoaFormat;
}

export interface CourierSoaFileResult {
  original_name: string;
  format: CourierSoaFormat;
  courier_label: string;
  sheet: string;
  soa_rows: number;
  download_token: string;
  download_filename: string;
}

export interface CourierSoaReconcileResult {
  summary: CourierSoaReconcileSummary;
  format: CourierSoaFormat;
  formats?: CourierSoaFormat[];
  courier_label: string;
  date_from: string;
  date_to: string;
  sheet: string;
  files?: CourierSoaFileResult[];
  download_token: string;
  download_filename: string;
  missing_from_soa: CourierSoaMissingRow[];
  extra_in_soa: CourierSoaExtraRow[];
}

export async function courierSoaStashUpload(files: File | File[]): Promise<string> {
  const list = Array.isArray(files) ? files : [files];
  if (list.length === 0) {
    throw new Error("No file selected.");
  }
  const form = new FormData();
  for (const file of list) {
    form.append("file[]", file);
  }
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("courier_soa_reconcile.php", "stash", form);
  return data.token;
}

export async function courierSoaReconcile(payload: {
  token: string;
  date_from: string;
  date_to: string;
}): Promise<CourierSoaReconcileResult> {
  return apiPost<CourierSoaReconcileResult>("courier_soa_reconcile.php", "reconcile", payload);
}

export function triggerCourierSoaDownload(token: string): void {
  const url = `${API_BASE}/courier_soa_reconcile.php?action=download&token=${encodeURIComponent(token)}`;
  window.open(url, "_blank", "noopener,noreferrer");
}
