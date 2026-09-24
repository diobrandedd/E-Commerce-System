import { apiPost, apiPostForm, API_BASE, getCsrfToken } from "@/app/lib/api";

export type PartnerSeller = "ian" | "jono" | "joven";

export interface PartnerSoldReconcileSummary {
  total_rows: number;
  rows_with_order_no: number;
  excluded_status: number;
  checked: number;
  matched: number;
  not_found: number;
  skipped_no_order_no: number;
  skipped_blank_rows?: number;
}

export interface PartnerSoldReconcilePreviewRow {
  row: number;
  order_no: string | null;
  tracking_number?: string | null;
  remark: string;
  excluded: boolean;
  matched: boolean;
  exists?: boolean;
  status: string;
}

export interface PartnerSoldReconcileResult {
  summary: PartnerSoldReconcileSummary;
  partner_seller: PartnerSeller;
  sheet: string;
  download_token: string;
  download_filename: string;
  preview: PartnerSoldReconcilePreviewRow[];
}

export async function partnerSoldStashUpload(file: File): Promise<string> {
  const form = new FormData();
  form.append("file", file);
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("partner_sold_reconcile.php", "stash", form);
  return data.token;
}

export async function partnerSoldReconcile(payload: {
  token: string;
  partner_seller: PartnerSeller;
  date_from?: string;
  date_to?: string;
}): Promise<PartnerSoldReconcileResult> {
  return apiPost<PartnerSoldReconcileResult>("partner_sold_reconcile.php", "reconcile", payload);
}

export function triggerPartnerSoldDownload(token: string): void {
  const url = `${API_BASE}/partner_sold_reconcile.php?action=download&token=${encodeURIComponent(token)}`;
  window.open(url, "_blank", "noopener,noreferrer");
}
