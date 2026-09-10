import { apiGet, apiPost } from "@/app/lib/api";

/** Dept products that use the barcode stock-in flow (pouches or jars). */
export const DEPT_POUCH_BARCODE_KEYS = ["pt", "brc", "jar_cacao", "honey_comb"] as const;

export type DeptPouchProductKey = (typeof DEPT_POUCH_BARCODE_KEYS)[number];

export function deptPouchBarcodesEnabled(productKey: string): productKey is DeptPouchProductKey {
  return (DEPT_POUCH_BARCODE_KEYS as readonly string[]).includes(productKey);
}

/** Sell-unit noun for UI copy (Cacao / Honey Comb = jar; Paragis / Black Rice = pouch). */
export function deptPouchUnitLabel(productKey: string): { singular: string; plural: string } {
  if (productKey === "jar_cacao" || productKey === "honey_comb") {
    return { singular: "jar", plural: "jars" };
  }
  return { singular: "pouch", plural: "pouches" };
}

/** Short line labels for scan-history product tabs. */
export function deptPouchHistoryLabel(productKey: string): string {
  switch (productKey) {
    case "pt":
      return "Paragis";
    case "brc":
      return "BRC";
    case "jar_cacao":
      return "Cacao";
    case "honey_comb":
      return "Honey Comb";
    default:
      return productKey;
  }
}

export interface DeptScanByScanner {
  user_id: number | null;
  name: string;
  qty: number;
}

export type DeptPouchRequestStatus =
  | "pending"
  | "barcodes_generated"
  | "in_progress"
  | "completed"
  | "cancelled";

export interface DeptPouchRequest {
  id: number;
  product_key: string;
  requested_by: number;
  requested_by_name: string;
  batch_no: string;
  quantity: number;
  status: DeptPouchRequestStatus;
  notes: string | null;
  created_at: string;
  scanned_count: number;
  generated_count: number;
  latest_batch_id: number | null;
}

export interface DeptSkuPrintJobRow {
  id: number;
  sku_id: number;
  sku_code: string;
  product_key: string;
  label: string;
  quantity: number;
  printed_by_name: string;
  printed_at: string;
  print_url: string;
}

export interface DeptPouchBatch {
  id: number;
  product_key: string;
  request_id: number | null;
  generated_by: number;
  generated_by_name: string;
  batch_no: string;
  batch_date: string;
  quantity_generated: number;
  printed_at: string | null;
  created_at: string;
  scanned_count: number;
}

const ENDPOINT = "dept_pouch_barcodes.php";

export function deptPouchPrintSheetUrl(batchId: number): string {
  return `/rts/api/v1/${ENDPOINT}?action=print_sheet&id=${batchId}`;
}

export const deptPouchBarcodesApi = {
  requestCreate: (
    productKey: string,
    body: { batch_no?: string; quantity: number; notes?: string },
  ) =>
    apiPost<{ request: DeptPouchRequest }>(ENDPOINT, "request_create", {
      ...body,
      product_key: productKey,
    }),
  requestList: (productKey: string, params: { status?: string; page?: number } = {}) =>
    apiGet<{ rows: DeptPouchRequest[]; total: number; page: number; per_page: number }>(
      ENDPOINT,
      "request_list",
      { product_key: productKey, status: params.status ?? "all", page: params.page ?? 1 },
    ),
  requestCancel: (id: number) => apiPost<{ ok: boolean }>(ENDPOINT, "request_cancel", { id }),
  pendingCount: (productKey: string) =>
    apiGet<{ count: number }>(ENDPOINT, "pending_count", { product_key: productKey }),
  pendingCounts: () =>
    apiGet<{ counts: Partial<Record<DeptPouchProductKey, number>> }>(ENDPOINT, "pending_counts"),
  generate: (requestId: number) =>
    apiPost<{
      sku: { id: number; code: string; label?: string };
      print_job_id: number;
      print_url: string;
      request: DeptPouchRequest;
      message?: string;
    }>(ENDPOINT, "generate", { request_id: requestId }),
  generateBatch: (productKey: string, requestIds: number[]) =>
    apiPost<{
      print_url: string;
      requests: DeptPouchRequest[];
      batch_count: number;
      message?: string;
    }>(ENDPOINT, "generate_batch", { product_key: productKey, request_ids: requestIds }),
  skuPrintList: (productKey: string, limit = 40) =>
    apiGet<{ rows: DeptSkuPrintJobRow[] }>(ENDPOINT, "sku_print_list", {
      product_key: productKey,
      limit,
    }),
  batchList: (productKey: string) =>
    apiGet<{ rows: DeptPouchBatch[] }>(ENDPOINT, "batch_list", { product_key: productKey }),
  markPrinted: (batchId: number) =>
    apiPost<{ batch: DeptPouchBatch }>(ENDPOINT, "mark_printed", { batch_id: batchId }),
  scanStockIn: (productKey: string, code: string, qty?: number) =>
    apiPost<{
      code: string;
      product_key: string;
      batch_no?: string;
      unit_id?: number;
      finished_on_hand?: number | null;
      request?: DeptPouchRequest;
      lookup_kind?: string;
      qty?: number;
      label?: string;
      bag_pool?: {
        bags_deducted: number;
        bags_on_hand: number;
        grams_deducted: number;
        grams_remaining: number;
        warned: boolean;
        shortfall_bags: number;
        warning: string | null;
      };
    }>(ENDPOINT, "scan_stock_in", {
      product_key: productKey,
      code,
      ...(qty != null ? { qty } : {}),
    }),
  scanReport: (productKey: string, scanDate: string, page = 1) =>
    apiGet<{
      dates: string[];
      rows: DeptPouchRequest[];
      total: number;
      page: number;
      per_page: number;
      scan_date: string | null;
      scanned_total: number;
      by_scanner: DeptScanByScanner[];
    }>(ENDPOINT, "scan_report", {
      product_key: productKey,
      scan_date: scanDate,
      page,
    }),
  requestScanLog: (requestId: number, page = 1, scanDate = "all") =>
    apiGet<{
      request: DeptPouchRequest;
      rows: {
        code: string;
        batch_no: string;
        scanned_at: string;
        scanned_by: number | null;
        scanned_by_name: string;
        qty: number;
        scan_kind: "sticker" | "sku";
        unit_status: string | null;
      }[];
      total: number;
      total_qty: number;
      page: number;
      per_page: number;
      scan_date: string | null;
    }>(ENDPOINT, "request_scan_log", { request_id: requestId, page, scan_date: scanDate }),
  posLookup: (code: string) =>
    apiGet<{
      barcode: {
        kind: "dept";
        barcode_id: number;
        code: string;
        product_key: string;
        product_label: string;
        batch_no: string;
        unit_id: number;
        unit_status: string;
        unit_noun: string;
      };
    }>(ENDPOINT, "pos_lookup", { code }),
  defectScan: (productKey: string, code: string, note?: string) =>
    apiPost<{
      log_id: number;
      code: string;
      product_key: string;
      product_label: string;
      batch_no: string;
      unit_id: number;
      finished_on_hand: number | null;
    }>(ENDPOINT, "defect_scan", {
      product_key: productKey,
      code,
      ...(note ? { note } : {}),
    }),
  defectHistory: (productKey: string, page = 1) =>
    apiGet<{
      rows: DeptPouchDefectRow[];
      total: number;
      page: number;
      per_page: number;
    }>(ENDPOINT, "defect_history", { product_key: productKey, page }),
};

export interface DeptPouchDefectRow {
  id: number;
  product_key: string;
  code: string;
  batch_no: string;
  unit_id: number;
  scanned_at: string;
  scanned_by: number;
  scanned_by_name: string;
  note: string | null;
}

/** Products with finished-unit defect scan (MVP: Jar Cacao). */
export const DEPT_POUCH_DEFECT_KEYS = ["jar_cacao"] as const;

export function deptPouchDefectEnabled(productKey: string): boolean {
  return (DEPT_POUCH_DEFECT_KEYS as readonly string[]).includes(productKey);
}
