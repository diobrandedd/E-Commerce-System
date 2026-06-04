export type OnlineOrdersPipeline = "partner" | "vip" | "shopee_spx";

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
