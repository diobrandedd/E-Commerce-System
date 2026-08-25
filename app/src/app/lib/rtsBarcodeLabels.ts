import { apiGet, apiPost } from "@/app/lib/api";

const ENDPOINT = "rts_barcode_labels.php";

export type RtsLabelProductKey = "honey" | "pt" | "brc" | "jar_cacao" | "honey_comb";
export type RtsLabelHoneySize = "liter" | "250ml" | "350ml" | "gallon";

export interface RtsBarcodeLabelLine {
  id: number;
  job_id: number;
  sort_order: number;
  kind: "honey" | "dept";
  honey_batch_id: number | null;
  dept_batch_id: number | null;
  product_key: string;
  product_label: string;
  size: string | null;
  size_label: string | null;
  variant: string | null;
  batch_no: string;
  bottling_date: string;
  quantity: number;
}

export interface RtsBarcodeLabelJob {
  id: number;
  created_by: number;
  created_by_name: string;
  note: string | null;
  printed_at: string | null;
  created_at: string;
  line_count: number;
  label_count: number;
  code_count: number;
  scanned_count: number;
  lines: RtsBarcodeLabelLine[];
}

export interface RtsBarcodeLabelCode {
  line_id: number;
  kind: "honey" | "dept";
  product_label: string;
  batch_no: string;
  size_label: string | null;
  code: string;
  sequence_no: number;
  status: string;
  scanned_at: string | null;
  unit_pool: string | null;
}

export interface RtsBarcodeLabelDraftLine {
  key: string;
  product_key: RtsLabelProductKey;
  variant: string;
  size: RtsLabelHoneySize;
  batch_no: string;
  bottling_date: string;
  quantity: number;
}

export interface RtsBarcodeLabelMeta {
  honey_variants: string[];
  honey_sizes: { value: string; label: string }[];
  dept_products: { product_key: string; label: string }[];
}

export function rtsBarcodeLabelPrintUrl(jobId: number): string {
  return `/rts/api/v1/${ENDPOINT}?action=print_sheet&id=${jobId}`;
}

export async function rtsBarcodeLabelMeta(): Promise<RtsBarcodeLabelMeta> {
  return apiGet<RtsBarcodeLabelMeta>(ENDPOINT, "meta");
}

export async function rtsBarcodeLabelJobCreate(
  lines: Array<{
    product_key: string;
    batch_no: string;
    bottling_date: string;
    quantity: number;
    variant?: string;
    size?: string;
  }>,
  note?: string,
): Promise<{ job: RtsBarcodeLabelJob }> {
  return apiPost(ENDPOINT, "job_create", {
    lines,
    ...(note ? { note } : {}),
  });
}

export async function rtsBarcodeLabelJobGet(id: number): Promise<{ job: RtsBarcodeLabelJob }> {
  return apiGet(ENDPOINT, "job_get", { id });
}

export async function rtsBarcodeLabelJobList(
  page = 1,
): Promise<{ rows: RtsBarcodeLabelJob[]; page: number; per_page: number; total: number }> {
  return apiGet(ENDPOINT, "job_list", { page });
}

export async function rtsBarcodeLabelJobCodes(
  id: number,
): Promise<{ job: RtsBarcodeLabelJob; codes: RtsBarcodeLabelCode[] }> {
  return apiGet(ENDPOINT, "job_codes", { id });
}

export async function rtsBarcodeLabelMarkPrinted(id: number): Promise<{ job: RtsBarcodeLabelJob }> {
  return apiPost(ENDPOINT, "mark_printed", { id });
}
