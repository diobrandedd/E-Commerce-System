import { apiGet, apiPost } from "@/app/lib/api";
import type { Supplier } from "@/app/lib/suppliers";

export interface HoneyWarehouseBarcodeRequest {
  id: number;
  supplier_id: number;
  supplier_name: string;
  supplier_code: string;
  arrival_date: string;
  quantity: number;
  status: "pending" | "printed" | "cancelled";
  requested_by: number;
  requested_by_name: string;
  generated_by?: number | null;
  generated_by_name?: string | null;
  requested_at: string;
  printed_at?: string | null;
}

export interface HoneyWarehouseBarcodeCandidate {
  supplier_id: number;
  supplier_name: string;
  supplier_code: string;
  arrival_date: string;
  variant: string;
  current_qty: number;
  warehouse_row_count: number;
}

export interface HoneyWarehouseBarcodeStockOut {
  production_row_id: number;
  supplier_id: number;
  supplier_code: string;
  arrival_date: string;
  stock_out_date: string;
  variant: string;
  quantity_gallons: number;
  source_row_count: number;
}

export const honeyWarehouseBarcodesApi = {
  requestList: (status = "all") =>
    apiGet<{ rows: HoneyWarehouseBarcodeRequest[] }>(
      "honey.php",
      "warehouse_barcode_request_list",
      { status },
    ),
  requestCreate: (body: { supplier_id: number; arrival_date: string; quantity: number }) =>
    apiPost<{ request: HoneyWarehouseBarcodeRequest }>(
      "honey.php",
      "warehouse_barcode_request_create",
      body,
    ),
  requestCancel: (requestId: number) =>
    apiPost<{ cancelled: boolean; request_id: number }>(
      "honey.php",
      "warehouse_barcode_request_cancel",
      { request_id: requestId },
    ),
  generate: (requestId: number) =>
    apiPost<{
      request: HoneyWarehouseBarcodeRequest;
      print_url: string;
    }>("honey.php", "warehouse_barcode_generate", { request_id: requestId }),

  generateBulk: (requestIds: number[]) =>
    apiPost<{
      requests: HoneyWarehouseBarcodeRequest[];
      request_ids: number[];
      print_url: string;
    }>("honey.php", "warehouse_barcode_generate_bulk", { request_ids: requestIds }),

  lookup: (code: string) =>
    apiGet<{
      code: string;
      candidates: HoneyWarehouseBarcodeCandidate[];
    }>("honey.php", "warehouse_barcode_lookup", { code }),
  stockOut: (body: {
    code: string;
    supplier_id: number;
    arrival_date: string;
    variant: string;
    quantity_gallons: number;
  }) =>
    apiPost<{ stock_out: HoneyWarehouseBarcodeStockOut }>(
      "honey.php",
      "warehouse_barcode_stock_out",
      body,
    ),
  printUrl: (requestId: number) =>
    `/rts/api/v1/honey.php?action=warehouse_barcode_print&request_id=${requestId}`,
  printUrlMany: (requestIds: number[]) =>
    `/rts/api/v1/honey.php?action=warehouse_barcode_print&request_ids=${encodeURIComponent(
      requestIds.join(","),
    )}`,
};

export type HoneyWarehouseSupplier = Supplier;
