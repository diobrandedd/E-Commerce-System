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
    body: { batch_no: string; quantity: number; notes?: string },
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
  generate: (requestId: number) =>
    apiPost<{ batch: DeptPouchBatch }>(ENDPOINT, "generate", { request_id: requestId }),
  batchList: (productKey: string) =>
    apiGet<{ rows: DeptPouchBatch[] }>(ENDPOINT, "batch_list", { product_key: productKey }),
  markPrinted: (batchId: number) =>
    apiPost<{ batch: DeptPouchBatch }>(ENDPOINT, "mark_printed", { batch_id: batchId }),
  scanStockIn: (productKey: string, code: string) =>
    apiPost<{
      code: string;
      product_key: string;
      batch_no: string;
      unit_id: number;
      finished_on_hand: number | null;
      request?: DeptPouchRequest;
    }>(ENDPOINT, "scan_stock_in", { product_key: productKey, code }),
};
