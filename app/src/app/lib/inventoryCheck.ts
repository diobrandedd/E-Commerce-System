import { apiGet, apiPost } from "@/app/lib/api";

const ENDPOINT = "inventory_check.php";

export interface InventoryCheckEvent {
  id: number;
  count_date: string;
  status: "open" | "closed";
  note: string | null;
  opened_by: number;
  opened_by_name: string;
  opened_at: string;
  closed_by_name: string | null;
  closed_at: string | null;
  scan_count: number;
  scanner_count: number;
  compare_run_count: number;
}

export interface InventoryCheckScan {
  id: number;
  kind: "honey" | "dept";
  product_key: string;
  product_label: string;
  code: string;
  packaging_row_id: number | null;
  supplier_code: string | null;
  batch_no: string | null;
  bottling_date: string | null;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  flag: "ok" | "not_in_stock" | "void";
  counted_qty?: number;
  sku_id?: number | null;
  scanned_by: number;
  scanned_by_name: string;
  scanned_at: string;
}

export interface InventoryCheckSummaryRow {
  kind: "honey" | "dept";
  product_key: string;
  product_label: string;
  packaging_row_id: number | null;
  supplier_code: string | null;
  batch_no: string | null;
  bottling_date: string | null;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  qty: number;
  flagged_qty: number;
  scanner_count: number;
  scanner_names: string;
  last_scanned_at: string | null;
}

export interface InventoryCheckSummary {
  event: InventoryCheckEvent;
  rows: InventoryCheckSummaryRow[];
  by_product: { kind: string; product_key: string; product_label: string; qty: number }[];
  total_units: number;
  flagged_units: number;
  scoped_to_user: boolean;
}

export interface InventoryCheckContext {
  can_view_all: boolean;
  can_manage: boolean;
  user_id: number;
  event: InventoryCheckEvent | null;
  summary: InventoryCheckSummary | null;
}

export interface InventoryCheckVarianceLine {
  id: number;
  kind: "honey" | "dept";
  product_key: string;
  product_label: string;
  packaging_row_id: number | null;
  supplier_code: string | null;
  batch_no: string | null;
  bottling_date: string | null;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  counted_qty: number;
  system_qty: number;
  variance: number;
}

export interface InventoryCheckBatchDocLine {
  bottling_date: string | null;
  kind: string;
  product_key: string;
  product_label: string;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  qty: number;
}

export interface InventoryCheckBatchDoc {
  batch_no: string;
  total_qty: number;
  lines: InventoryCheckBatchDocLine[];
}

export interface InventoryCheckCompareRun {
  id: number;
  event_id: number;
  event_ids?: number[];
  scope: string;
  line_count: number;
  mismatch_count: number;
  counted_total: number;
  system_total: number;
  run_by_name: string;
  run_at: string;
  stock_applied?: boolean;
  stock_applied_at?: string | null;
  stock_applied_by_name?: string | null;
  barcodes_voided_count?: number;
  merged_event_ids?: string | null;
  is_merged?: boolean;
}

export interface InventoryCheckAppliedSessionRef {
  id: number;
  count_date: string;
  opened_by_name: string;
  status: string;
}

export interface InventoryCheckAppliedRun extends InventoryCheckCompareRun {
  primary_count_date: string;
  primary_opened_by_name: string;
  is_merged: boolean;
  sessions: InventoryCheckAppliedSessionRef[];
}

export interface InventoryCheckCompareResult {
  preview?: boolean;
  run: InventoryCheckCompareRun;
  lines: InventoryCheckVarianceLine[];
  batches?: InventoryCheckBatchDoc[];
  event: InventoryCheckEvent;
}

export interface InventoryCheckScanResult {
  result: "ok" | "not_in_stock" | "void" | "duplicate_event" | "unknown";
  message: string;
  scan: InventoryCheckScan | null;
  summary: InventoryCheckSummary;
}

export interface InventoryCheckScanLogFilters {
  kind?: string;
  product_key?: string;
  batch_no?: string;
  size?: string;
  variant?: string;
  packaging_row_id?: number;
}

export const inventoryCheckApi = {
  context: () => apiGet<InventoryCheckContext>(ENDPOINT, "context"),

  openEvent: (note?: string) =>
    apiPost<{ event: InventoryCheckEvent }>(ENDPOINT, "event_open", note ? { note } : {}),

  closeEvent: (eventId: number) =>
    apiPost<{ event: InventoryCheckEvent }>(ENDPOINT, "event_close", { event_id: eventId }),

  cancelEvent: (eventId: number) =>
    apiPost<{ cancelled: boolean }>(ENDPOINT, "event_cancel", { event_id: eventId }),

  eventList: (page = 1) =>
    apiGet<{ rows: InventoryCheckEvent[]; total: number; page: number; per_page: number }>(
      ENDPOINT,
      "event_list",
      { page },
    ),

  scan: (
    eventId: number,
    code: string,
    opts?: { qty?: number; batch_no?: string; bottling_date?: string },
  ) =>
    apiPost<InventoryCheckScanResult>(ENDPOINT, "scan", {
      event_id: eventId,
      code,
      ...(opts?.qty != null ? { qty: opts.qty } : {}),
      ...(opts?.batch_no != null ? { batch_no: opts.batch_no } : {}),
      ...(opts?.bottling_date != null ? { bottling_date: opts.bottling_date } : {}),
    }),

  scanUpdate: (
    eventId: number,
    scanId: number,
    opts: { qty?: number; batch_no?: string; bottling_date?: string },
  ) =>
    apiPost<{ scan: InventoryCheckScan; summary: InventoryCheckSummary | null }>(ENDPOINT, "scan_update", {
      event_id: eventId,
      scan_id: scanId,
      ...(opts.qty != null ? { qty: opts.qty } : {}),
      ...(opts.batch_no != null ? { batch_no: opts.batch_no } : {}),
      ...(opts.bottling_date != null ? { bottling_date: opts.bottling_date } : {}),
    }),

  scanRemove: (eventId: number, scanId: number) =>
    apiPost<{ summary: InventoryCheckSummary | null }>(ENDPOINT, "scan_remove", {
      event_id: eventId,
      scan_id: scanId,
    }),

  summary: (eventId: number) =>
    apiGet<InventoryCheckSummary>(ENDPOINT, "summary", { event_id: eventId }),

  scanLog: (eventId: number, filters: InventoryCheckScanLogFilters = {}, page = 1, perPage = 200) =>
    apiGet<{ rows: InventoryCheckScan[]; total: number; page: number; per_page: number }>(
      ENDPOINT,
      "scan_log",
      { event_id: eventId, page, per_page: perPage, ...filters },
    ),

  comparePreview: (eventId: number) =>
    apiGet<InventoryCheckCompareResult>(ENDPOINT, "compare_preview", { event_id: eventId }),

  comparePreviewMerged: (eventIds: number[]) =>
    apiPost<InventoryCheckCompareResult>(ENDPOINT, "compare_preview_merged", { event_ids: eventIds }),

  compareRun: (eventId: number) =>
    apiPost<InventoryCheckCompareResult>(ENDPOINT, "compare_run", { event_id: eventId }),

  finalize: (eventId: number, adminPassword: string) =>
    apiPost<{
      event: InventoryCheckEvent;
      compare: InventoryCheckCompareResult;
      closed?: boolean;
    }>(ENDPOINT, "finalize", {
      event_id: eventId,
      admin_password: adminPassword,
    }),

  finalizeMerged: (eventIds: number[], adminPassword: string) =>
    apiPost<{
      event: InventoryCheckEvent;
      compare: InventoryCheckCompareResult;
      closed?: boolean;
    }>(ENDPOINT, "finalize_merged", {
      event_ids: eventIds,
      admin_password: adminPassword,
    }),

  compareGet: (runId: number) =>
    apiGet<InventoryCheckCompareResult>(ENDPOINT, "compare_get", { run_id: runId }),

  compareList: (eventId: number) =>
    apiGet<{ runs: InventoryCheckCompareRun[] }>(ENDPOINT, "compare_list", { event_id: eventId }),

  appliedRunsList: (page = 1, perPage = 25) =>
    apiGet<{
      rows: InventoryCheckAppliedRun[];
      total: number;
      page: number;
      per_page: number;
    }>(ENDPOINT, "applied_runs_list", { page, per_page: perPage }),
};
