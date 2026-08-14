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

export interface HoneyPackSession {
  id: number;
  status: "draft" | "confirmed" | "discarded";
  started_by: number;
  created_at: string;
  item_count: number;
  items: { id: number; code: string; variant: string; size: string; size_label: string; scanned_at: string }[];
  grouped: { variant: string; size: string; size_label: string; qty: number }[];
}

export interface HoneyPackagingOption {
  id: number;
  batch_no: string;
  supplier_code: string;
  variant: string;
  bottling_date: string;
  label: string;
}

export function bottlePrintSheetUrl(batchId: number): string {
  return `/rts/api/v1/bottle_barcodes.php?action=print_sheet&id=${batchId}`;
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
  generate: (requestId: number, packagingRowId?: number | null, productionRowId?: number | null) =>
    apiPost<{ batch: HoneyBarcodeBatch }>("bottle_barcodes.php", "generate", {
      request_id: requestId,
      packaging_row_id: packagingRowId ?? undefined,
      production_row_id: productionRowId ?? undefined,
    }),
  ensurePackagingShell: (productionRowId: number) =>
    apiPost<{ packaging: HoneyPackagingOption & { created: boolean } }>(
      "bottle_barcodes.php",
      "ensure_packaging_shell",
      { production_row_id: productionRowId },
    ),
  batchList: () => apiGet<{ rows: HoneyBarcodeBatch[] }>("bottle_barcodes.php", "batch_list"),
  markPrinted: (batchId: number) =>
    apiPost<{ batch: HoneyBarcodeBatch }>("bottle_barcodes.php", "mark_printed", { batch_id: batchId }),
  lookup: (code: string) =>
    apiGet<{ barcode: HoneyBottleLookup }>("bottle_barcodes.php", "lookup", { code }),
  scanStockIn: (code: string, adopt = false) =>
    apiPost<HoneyBottleLookup & { adopted?: boolean; request?: HoneyBottleRequest }>(
      "bottle_barcodes.php",
      "scan_stock_in",
      { code, adopt: adopt ? 1 : 0 },
    ),
  packStart: () => apiPost<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_start", {}),
  packGet: (id: number) =>
    apiGet<{ session: HoneyPackSession }>("bottle_barcodes.php", "pack_get", { id }),
  packScan: (sessionId: number, code: string) =>
    apiPost<{ result: string; session: HoneyPackSession; lookup: HoneyBottleLookup | null }>(
      "bottle_barcodes.php",
      "pack_scan",
      { session_id: sessionId, code },
    ),
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
};
