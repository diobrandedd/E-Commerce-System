import { apiPost, apiPostForm, ApiError, getCsrfToken } from "@/app/lib/api";

export interface PdfToExcelRow {
  page: number;
  tracking: string;
  name: string;
  items: string;
  courier: string;
  complete: boolean;
  missing_fields: string[];
  /** Shopee packing list `order_sn` — merged into master Excel Order No. */
  order_no?: string;
}

export interface PdfToExcelFileResult {
  pdf_name: string;
  excel_filename: string;
  rows: PdfToExcelRow[];
  total: number;
  complete: number;
  missing: number;
}

export interface PdfToExcelExtractResult {
  job_token: string;
  files: PdfToExcelFileResult[];
  log_excerpt: string;
}

export interface PdfToExcelDownload {
  filename: string;
  download_token: string;
}

export interface PdfToExcelConfirmResult {
  downloads: PdfToExcelDownload[];
}

const MAX_FILE_BYTES = 60 * 1024 * 1024;

export function pdfToExcelUploadTooLarge(files: File[]): string | null {
  for (const f of files) {
    if (f.size > MAX_FILE_BYTES) {
      const mb = (f.size / (1024 * 1024)).toFixed(1);
      return `"${f.name}" is ${mb} MB (max ~60 MB per file).`;
    }
  }
  return null;
}

async function stashPdfFile(file: File, signal?: AbortSignal): Promise<string> {
  const form = new FormData();
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("pdf_to_excel.php", "stash", form, signal, {
    refresh: false,
  });
  return data.token;
}

export type PdfToExcelUploadProgress = (done: number, total: number, fileName: string) => void;

export async function extractPdfToExcel(
  pdfFiles: File[],
  onProgress?: PdfToExcelUploadProgress,
  signal?: AbortSignal,
): Promise<PdfToExcelExtractResult> {
  const tokens: string[] = [];
  for (let i = 0; i < pdfFiles.length; i++) {
    const f = pdfFiles[i];
    onProgress?.(i, pdfFiles.length, f.name);
    try {
      tokens.push(await stashPdfFile(f, signal));
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Upload failed";
      throw new ApiError(`Could not upload "${f.name}": ${msg}`, e instanceof ApiError ? e.status : 0);
    }
  }
  onProgress?.(pdfFiles.length, pdfFiles.length, "Extracting…");
  return apiPost<PdfToExcelExtractResult>("pdf_to_excel.php", "extract", { pdf_tokens: tokens }, signal);
}

export async function confirmPdfToExcel(
  jobToken: string,
  signal?: AbortSignal,
): Promise<PdfToExcelConfirmResult> {
  return apiPost<PdfToExcelConfirmResult>("pdf_to_excel.php", "confirm", { job_token: jobToken }, signal);
}

export function pdfToExcelDownloadUrl(token: string): string {
  const url = new URL("/rts/api/v1/pdf_to_excel.php", window.location.origin);
  url.searchParams.set("action", "download");
  url.searchParams.set("token", token);
  return url.toString();
}

export function triggerPdfToExcelDownload(token: string, filename: string): void {
  const a = document.createElement("a");
  a.href = pdfToExcelDownloadUrl(token);
  a.download = filename;
  a.rel = "noopener";
  document.body.appendChild(a);
  a.click();
  a.remove();
}
