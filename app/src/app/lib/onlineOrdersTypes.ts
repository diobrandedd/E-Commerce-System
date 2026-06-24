export type OnlineOrdersPipeline = "partner" | "vip" | "shopee_spx" | "tiktok" | "unified";

export type PartnerSeller = "ian" | "jono" | "joven";

export type OnlineOrdersPrepTool = "vip" | "spx" | "excel" | "stamper";

export interface OnlineOrdersPrepRow {
  order_no?: string | null;
  name?: string;
  items: string;
  courier?: string;
  tracking_number: string;
}

export interface OnlineOrdersPrepResult {
  excel_token: string;
  excel_tokens?: string[];
  row_count: number;
  rows: OnlineOrdersPrepRow[];
}

export interface OnlineOrdersBatchRun {
  id: number;
  pipeline: OnlineOrdersPipeline;
  status: string;
  meta: Record<string, unknown> | null;
  created_at: string;
}

export interface OnlineOrdersBatch {
  id: number;
  status: "active" | "processed" | "cancelled";
  created_at: string;
  completed_at: string | null;
  line_count: number;
  runs: OnlineOrdersBatchRun[];
  master_download_token: string | null;
  master_filename: string | null;
}

export interface OnlineOrdersLineInput {
  order_no?: string | null;
  name?: string;
  items: string;
  courier?: string;
  tracking_number: string;
}

export interface PartnerStampResult {
  stamp: {
    total: number;
    matched: number;
    unmatched: number;
    unmatched_list: string[];
    download_token: string;
    download_filename: string;
    log_excerpt: string;
  };
  lines_added: number;
  run_id: number;
}

export type DuplicateTrackingSource = "batch_line" | "parcel" | "incoming_upload";

export interface DuplicateTrackingParty {
  recipient_name?: string;
  items?: string;
  courier?: string;
  source_pipeline?: string;
  order_no?: string | null;
}

export interface DuplicateTrackingExisting extends DuplicateTrackingParty {
  source: DuplicateTrackingSource;
  batch_id?: number | null;
  batch_status?: string;
  line_id?: number;
  parcel_id?: number;
  parcel_status?: string;
  ship_date?: string | null;
  created_at?: string;
}

export interface DuplicateTrackingConflict {
  tracking_number: string;
  tracking_raw?: string;
  attempted: DuplicateTrackingParty;
  existing: DuplicateTrackingExisting;
}
