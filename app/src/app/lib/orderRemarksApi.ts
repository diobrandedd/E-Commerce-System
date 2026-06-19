import { apiGet, apiPost, apiPostForm, getCsrfToken } from "@/app/lib/api";

export interface CarrierProfile {
  id: string;
  label: string;
}

export interface OrderRemarksLayout {
  profileId: string;
  profileLabel: string;
  sheetName: string;
  headerRow: number;
  columns: { receiver: number; phone: number; remarks: number };
  remarksColumnLetter?: string;
  brandingSample?: string;
}

export interface OrderRow {
  idx: number;
  rowNum: number;
  receiver: string;
  phone: string;
  remarks: string;
  itemText?: string;
  tracking?: string;
  address?: string;
  city?: string;
  province?: string;
  region?: string;
}

export interface ParseOrderRemarksResult {
  token: string;
  layout: OrderRemarksLayout;
  orders: OrderRow[];
  sampleRows: OrderRow[];
  totalRows: number;
  profiles: CarrierProfile[];
}

export interface ExportOrderRemarksResult {
  download_token: string;
  download_filename: string;
}

const MAX_FILE_BYTES = 60 * 1024 * 1024;

export function orderRemarksUploadTooLarge(file: File): string | null {
  if (file.size > MAX_FILE_BYTES) {
    const mb = (file.size / (1024 * 1024)).toFixed(1);
    return `"${file.name}" is ${mb} MB (max ~60 MB).`;
  }
  return null;
}

export async function stashOrderRemarksFile(file: File, signal?: AbortSignal): Promise<string> {
  const form = new FormData();
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("order_remarks.php", "stash", form, signal, {
    refresh: false,
  });
  return data.token;
}

export async function parseOrderRemarks(
  token: string,
  profileId?: string,
  signal?: AbortSignal,
): Promise<ParseOrderRemarksResult> {
  return apiGet<ParseOrderRemarksResult>("order_remarks.php", "parse", {
    token,
    profile_id: profileId ?? undefined,
  }, signal);
}

export async function exportOrderRemarks(
  token: string,
  remarks: string[],
  layout: OrderRemarksLayout,
  signal?: AbortSignal,
): Promise<ExportOrderRemarksResult> {
  return apiPost<ExportOrderRemarksResult>("order_remarks.php", "export", {
    token,
    remarks,
    layout,
  }, signal);
}

export function orderRemarksDownloadUrl(token: string): string {
  const url = new URL("/rts/api/v1/order_remarks.php", window.location.origin);
  url.searchParams.set("action", "download");
  url.searchParams.set("token", token);
  return url.toString();
}

export function triggerOrderRemarksDownload(token: string, filename: string): void {
  const a = document.createElement("a");
  a.href = orderRemarksDownloadUrl(token);
  a.download = filename;
  a.rel = "noopener";
  document.body.appendChild(a);
  a.click();
  a.remove();
}
