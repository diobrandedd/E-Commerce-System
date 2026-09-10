import { apiGet, apiPost } from "@/app/lib/api";

export type HoneyBottleSize = "liter" | "250ml" | "350ml" | "gallon";
export type HoneyBottleRequestStatus =
  | "pending"
  | "barcodes_generated"
  | "in_progress"
  | "completed"
  | "cancelled";

export interface HoneyBottleRequest {
  id: number;
  requested_by: number;
  requested_by_name: string;
  packaging_row_id: number | null;
  batch_no: string;
  variant: string;
  size: HoneyBottleSize;
  size_label: string;
  quantity: number;
  status: HoneyBottleRequestStatus;
  notes: string | null;
  created_at: string;
  scanned_count: number;
  generated_count: number;
  latest_batch_id: number | null;
  sku_scanned_qty?: number;
  sku_print_qty?: number;
  label_kind?: "sku" | "legacy" | "mixed";
}

export interface SkuPrintJobRow {
  id: number;
  sku_id: number;
  sku_code: string;
  label: string;
  quantity: number;
  printed_by_name: string;
  printed_at: string;
  print_url: string;
  honey_request_id: number | null;
}

export interface HoneyBarcodeBatch {
  id: number;
  request_id: number | null;
  source: "request" | "backfill";
  generated_by: number;
  generated_by_name: string;
  packaging_row_id: number | null;
  supplier_code: string;
  batch_no: string;
  batch_date: string;
  variant: string;
  size: HoneyBottleSize;
  size_label: string;
  quantity_generated: number;
  printed_at: string | null;
  created_at: string;
  scanned_count: number;
}

export interface HoneyBottleLookup {
  barcode_id: number;
  code: string;
  barcode_status: string;
  size: HoneyBottleSize;
  size_label: string;
  variant: string;
  supplier_code: string;
  batch_no: string;
  packaging_row_id: number | null;
  request_id: number | null;
  batch_source: string;
  unit_id: number | null;
  unit_status: string | null;
  unit_pool: string | null;
  scanned_at: string | null;
}

export interface HoneyPackSessionItem {
  id: number;
  kind?: "honey" | "dept";
  product_key?: string;
  product_label?: string;
  code: string;
  variant: string;
  size: string;
  size_label: string;
  batch_no?: string | null;
  scanned_at: string;
  qty?: number;
  sku_id?: number | null;
  lookup_kind?: string;
  pool?: string | null;
}

export interface HoneyPackSessionGroup {
  kind?: "honey" | "dept";
  product_key?: string;
  product_label?: string;
  variant: string;
  size: string;
  size_label: string;
  qty: number;
}

export interface HoneyPackSession {
  id: number;
  status: "draft" | "confirmed" | "discarded";
  started_by: number;
  started_by_name?: string;
  confirmed_by?: number | null;
  confirmed_by_name?: string;
  confirmed_at?: string | null;
  created_at: string;
  item_count: number;
  /** Sum of line quantities (SKU qty + unique stickers). */
  total_qty?: number;
  items: HoneyPackSessionItem[];
  grouped: HoneyPackSessionGroup[];
  by_batch?: {
    kind?: "honey" | "dept";
    product_key?: string;
    product_label?: string;
    batch_no: string | null;
    variant: string;
    size: string;
    size_label: string;
    qty: number;
  }[];
}

export interface HoneyPackSessionListRow {
  id: number;
  status: "confirmed";
  started_by: number;
  started_by_name: string;
  confirmed_by: number | null;
  confirmed_by_name: string;
  confirmed_at: string;
  created_at: string;
  item_count: number;
  grouped: HoneyPackSession["grouped"];
  by_batch: NonNullable<HoneyPackSession["by_batch"]>;
  items: HoneyPackSession["items"];
}

export interface HoneyPackagingOption {
  id: number;
  batch_no: string;
  supplier_code: string;
  variant: string;
  bottling_date: string;
  label: string;
  custom?: boolean;
}

export function bottlePrintSheetUrl(batchId: number): string {
  return `/rts/api/v1/bottle_barcodes.php?action=print_sheet&id=${batchId}`;
}

export interface HoneyPackagingScanReportRow {
  id: number;
  production_row_id: number | null;
  supplier_code: string;
  batch_no: string;
  bottling_date: string;
  variant: string;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
  scanned_liter: string;
  scanned_250ml: string;
  scanned_350ml: string;
  scanned_gallon: string;
}

export const bottleBarcodesApi = {
  meta: () =>
    apiGet<{ variants: string[]; sizes: { value: string; label: string }[] }>("bottle_barcodes.php", "meta"),
  packagingOptions: () =>
    apiGet<{ rows: HoneyPackagingOption[] }>("bottle_barcodes.php", "packaging_options"),
  requestCreate: (body: {
    variant: string;
    size: string;
    quantity: number;
    packaging_row_id?: number | null;
    notes?: string;
  }) => apiPost<{ request: HoneyBottleRequest }>("bottle_barcodes.php", "request_create", body),
  requestList: (params: { status?: string; mine?: boolean; page?: number }) =>
    apiGet<{ rows: HoneyBottleRequest[]; total: number; page: number; per_page: number }>(
      "bottle_barcodes.php",
      "request_list",
      {
        status: params.status ?? "all",
        mine: params.mine ? 1 : 0,
        page: params.page ?? 1,
      },
    ),
  requestCancel: (id: number) => apiPost<{ ok: boolean }>("bottle_barcodes.php", "request_cancel", { id }),
  pendingCount: () => apiGet<{ count: number }>("bottle_barcodes.php", "pending_count"),
  generate: (requestId: number) =>
    apiPost<{
      sku: { id: number; code: string; label?: string };
      print_job_id: number;
      print_url: string;
      request: HoneyBottleRequest;
      message?: string;
    }>("bottle_barcodes.php", "generate", {
      request_id: requestId,
    }),
  generateBatch: (requestIds: number[]) =>
    apiPost<{
      print_url: string;
      requests: HoneyBottleRequest[];
      batch_count: number;
      message?: string;
    }>("bottle_barcodes.php", "generate_batch", { request_ids: requestIds }),
  skuPrintList: (limit = 40) =>
    apiGet<{ rows: SkuPrintJobRow[] }>("bottle_barcodes.php", "sku_print_list", { limit }),
  ensurePackagingShell: (productionRowId: number) =>
    apiPost<{ packaging: HoneyPackagingOption & { created: boolean } }>(
      "bottle_barcodes.php",
      "ensure_packaging_shell",
      { production_row_id: productionRowId },
    ),
  createCustomPackaging: (body: { supplier_name?: string; code?: string; variant: string; bottling_date: string }) =>
    apiPost<{ packaging: HoneyPackagingOption & { created: boolean }; reused: boolean }>(
      "bottle_barcodes.php",
      "create_custom_packaging",
      body,
    ),
  batchList: () => apiGet<{ rows: HoneyBarcodeBatch[] }>("bottle_barcodes.php", "batch_list"),
  markPrinted: (batchId: number) =>
    apiPost<{ batch: HoneyBarcodeBatch }>("bottle_barcodes.php", "mark_printed", { batch_id: batchId }),
  lookup: (code: string) =>
    apiGet<{ barcode: HoneyBottleLookup }>("bottle_barcodes.php", "lookup", { code }),
  scanStockIn: (
    code: string,
    opts: {
      adopt?: boolean;
      qty?: number;
      packaging_row_id?: number;
      batch_no?: string;
      production_row_id?: number;
    } = {},
  ) =>
    apiPost<
      HoneyBottleLookup & {
        adopted?: boolean;
        request?: HoneyBottleRequest;
        lookup_kind?: string;
        qty?: number;
        used_gallons?: number;
        remaining_production_gallons?: number;
        ml_per_gallon?: number;
        production_row_id?: number;
        batch_no?: string;
      }
    >("bottle_barcodes.php", "scan_stock_in", {
      code,
      adopt: opts.adopt ? 1 : 0,
      qty: opts.qty,
      packaging_row_id: opts.packaging_row_id,
      batch_no: opts.batch_no,
      production_row_id: opts.production_row_id,
    }),
  packStart: () => apiPost<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_start", {}),
  packGet: (id: number) =>
    apiGet<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_get", { id }),
  packSessionList: (params: {
    from?: string;
    to?: string;
    batch_no?: string;
    product_key?: string;
    page?: number;
  } = {}) =>
    apiGet<{
      rows: HoneyPackSessionListRow[];
      total: number;
      page: number;
      per_page: number;
      from: string;
      to: string;
      product_key?: string;
    }>("bottle_barcodes.php", "pack_session_list", {
      from: params.from,
      to: params.to,
      batch_no: params.batch_no || undefined,
      product_key: params.product_key || undefined,
      page: params.page ?? 1,
    }),
  packTodayStats: () =>
    apiGet<{
      date: string;
      packaging: number;
      rts_sellable: number;
      total: number;
    }>("bottle_barcodes.php", "pack_today_stats"),
  packScan: (
    sessionId: number,
    code: string,
    opts?: { qty?: number; pool?: string; packaging_row_id?: number },
  ) =>
    apiPost<{
      result: string;
      session: HoneyPackSession;
      lookup: (HoneyBottleLookup & {
        kind?: "honey" | "dept";
        product_key?: string;
        product_label?: string;
        lookup_kind?: string;
        qty?: number;
        pool?: string;
      }) | null;
    }>("bottle_barcodes.php", "pack_scan", {
      session_id: sessionId,
      code,
      ...(opts?.qty != null ? { qty: opts.qty } : {}),
      ...(opts?.pool ? { pool: opts.pool } : {}),
      ...(opts?.packaging_row_id != null ? { packaging_row_id: opts.packaging_row_id } : {}),
    }),
  packRemoveItem: (sessionId: number, kind: "honey" | "dept", itemId: number) =>
    apiPost<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_remove_item", {
      session_id: sessionId,
      kind,
      item_id: itemId,
    }),
  packSetItemQty: (sessionId: number, kind: "honey" | "dept", itemId: number, qty: number) =>
    apiPost<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_set_item_qty", {
      session_id: sessionId,
      kind,
      item_id: itemId,
      qty,
    }),
  packConfirm: (sessionId: number) =>
    apiPost<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_confirm", { session_id: sessionId }),
  packDiscard: (sessionId: number) =>
    apiPost<{ ok: boolean }>("bottle_barcodes.php", "pack_discard", { session_id: sessionId }),
  posLookup: (code: string) =>
    apiGet<{ barcode: HoneyBottleLookup }>("bottle_barcodes.php", "pos_lookup", { code }),
  openedReturnScan: (code: string) =>
    apiPost<{ barcode: HoneyBottleLookup }>("bottle_barcodes.php", "opened_return_scan", { code }),
  backfillPreview: () => apiGet<{ rows: unknown[]; fractions: unknown[] }>("bottle_barcodes.php", "backfill_preview"),
  backfillGenerate: () =>
    apiPost<{ batches: HoneyBarcodeBatch[]; count: number }>("bottle_barcodes.php", "backfill_generate", {}),
  reconcile: () =>
    apiGet<{
      drifted: number;
      rows: {
        packaging_row_id: number;
        batch_no: string;
        variant: string;
        drift: boolean;
        sizes: Record<string, { aggregate: number; units: number; diff: number }>;
      }[];
    }>("bottle_barcodes.php", "reconcile"),
  stockSummary: () =>
    apiGet<{ rows: { variant: string; size: string; size_label: string; qty: number }[] }>(
      "bottle_barcodes.php",
      "stock_summary",
    ),
  packagingScanReport: (scanDate: string, bottlingDate = "all") =>
    apiGet<{
      dates: string[];
      bottling_dates: string[];
      rows: HoneyPackagingScanReportRow[];
      scan_date: string | null;
      bottling_date: string | null;
      totals: {
        total: number;
        by_variant: {
          variant: string;
          total: number;
          liter: number;
          "250ml": number;
          "350ml": number;
          gallon: number;
        }[];
      };
      by_scanner: { user_id: number | null; name: string; qty: number }[];
    }>("bottle_barcodes.php", "packaging_scan_report", {
      scan_date: scanDate,
      bottling_date: bottlingDate,
    }),
  packagingScanLog: (packagingRowId: number, page = 1, scanDate = "all") =>
    apiGet<{
      packaging: {
        id: number;
        batch_no: string;
        supplier_code: string;
        variant: string;
        bottling_date: string;
      };
      rows: {
        code: string;
        size: string;
        size_label: string;
        scanned_at: string;
        scanned_by: number | null;
        scanned_by_name: string;
        qty: number;
        scan_kind: "sticker" | "sku";
        unit_status: string | null;
        unit_pool: string | null;
      }[];
      total: number;
      total_qty: number;
      page: number;
      per_page: number;
      scan_date: string | null;
    }>("bottle_barcodes.php", "packaging_scan_log", {
      packaging_row_id: packagingRowId,
      page,
      scan_date: scanDate,
    }),
};
