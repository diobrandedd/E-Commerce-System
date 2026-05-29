import { apiPost, apiPostForm, ApiError, getCsrfToken } from "@/app/lib/api";

export interface WaybillStampResult {
  total: number;
  matched: number;
  unmatched: number;
  unmatched_list: string[];
  download_token: string;
  download_filename: string;
  log_excerpt: string;
}

/** Per-file limit (each stash request is one file; stays under typical 40–64M post_max_size). */
const MAX_FILE_BYTES = 60 * 1024 * 1024;

export function waybillUploadTooLarge(files: File[]): string | null {
  for (const f of files) {
    if (f.size > MAX_FILE_BYTES) {
      const mb = (f.size / (1024 * 1024)).toFixed(1);
      return `"${f.name}" is ${mb} MB (max ~60 MB per file). Split the PDF or raise upload_max_filesize in php.ini.`;
    }
  }
  return null;
}

async function stashWaybillFile(kind: "pdf" | "excel", file: File, signal?: AbortSignal): Promise<string> {
  const form = new FormData();
  form.append("kind", kind);
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form, signal, { refresh: false });
  return data.token;
}

export type WaybillUploadProgress = (done: number, total: number, fileName: string) => void;

/**
 * Upload each file separately (avoids post_max_size on one giant multipart POST), then process.
 */
export async function stampWaybillsFromFiles(
  pdfFiles: File[],
  excelFiles: File[],
  onProgress?: WaybillUploadProgress,
  signal?: AbortSignal
): Promise<WaybillStampResult> {
  const total = pdfFiles.length + excelFiles.length;
  let done = 0;
  const pdfTokens: string[] = [];
  const excelTokens: string[] = [];

  for (const f of pdfFiles) {
    onProgress?.(done, total, f.name);
    try {
      pdfTokens.push(await stashWaybillFile("pdf", f, signal));
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Upload failed";
      throw new ApiError(`Could not upload PDF "${f.name}": ${msg}`, e instanceof ApiError ? e.status : 0);
    }
    done++;
  }
  for (const f of excelFiles) {
    onProgress?.(done, total, f.name);
    try {
      excelTokens.push(await stashWaybillFile("excel", f, signal));
    } catch (e) {
      const msg = e instanceof Error ? e.message : "Upload failed";
      throw new ApiError(`Could not upload Excel "${f.name}": ${msg}`, e instanceof ApiError ? e.status : 0);
    }
    done++;
  }

  onProgress?.(total, total, "Processing…");

  return apiPost<WaybillStampResult>("waybill.php", "process", {
    pdf_tokens: pdfTokens,
    excel_tokens: excelTokens,
  }, signal);
}

export function waybillDownloadUrl(token: string): string {
  const url = new URL("/rts/api/v1/waybill.php", window.location.origin);
  url.searchParams.set("action", "download");
  url.searchParams.set("token", token);
  return url.toString();
}

export function triggerWaybillDownload(token: string, filename: string): void {
  const a = document.createElement("a");
  a.href = waybillDownloadUrl(token);
  a.download = filename;
  a.rel = "noopener";
  document.body.appendChild(a);
  a.click();
  a.remove();
}
