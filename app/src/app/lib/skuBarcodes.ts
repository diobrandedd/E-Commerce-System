import { apiGet, apiPost } from "@/app/lib/api";

const ENDPOINT = "sku_barcodes.php";

export interface SkuBarcodeRow {
  id: number | null;
  kind: "honey" | "dept";
  variant: string | null;
  size: string | null;
  size_label: string | null;
  product_key: string | null;
  label: string;
  code: string;
  selling_price_hint: number | null;
  updated_at: string | null;
  updated_by_name: string | null;
}

export interface SkuLookup {
  lookup_kind: "sku" | "unique_honey" | "unique_dept";
  requires_qty?: boolean;
  kind: "honey" | "dept";
  id?: number;
  label?: string;
  code: string;
  variant?: string | null;
  size?: string | null;
  size_label?: string | null;
  product_key?: string | null;
  product_label?: string | null;
  unit_id?: number | null;
  unit_status?: string | null;
  sku_id?: number;
  suggested_price?: number | null;
  product_id?: number;
  product_name?: string;
  pool?: string;
  batch_no?: string | null;
}

export function skuPrintSheetUrl(skuId: number, qty: number): string {
  return `/rts/api/v1/${ENDPOINT}?action=print_sheet&sku_id=${skuId}&qty=${qty}`;
}

export function skuPrintSheetCombinedUrl(
  lines: { sku_id: number; qty: number; banner?: string }[],
): string {
  return `/rts/api/v1/${ENDPOINT}?action=print_sheet&lines=${encodeURIComponent(JSON.stringify(lines))}`;
}

export const skuBarcodesApi = {
  list: () => apiGet<{ rows: SkuBarcodeRow[] }>(ENDPOINT, "list"),
  lookup: (code: string) =>
    apiGet<{ lookup: SkuLookup }>(ENDPOINT, "lookup", { code }),
  save: (body: {
    kind: "honey" | "dept";
    variant?: string;
    size?: string;
    product_key?: string;
    code: string;
  }) => apiPost<{ row: SkuBarcodeRow }>(ENDPOINT, "save", body),
  saveBulk: (items: {
    kind: "honey" | "dept";
    variant?: string;
    size?: string;
    product_key?: string;
    code: string;
    selling_price?: string | number;
  }[]) => apiPost<{ rows: SkuBarcodeRow[]; saved_count: number }>(ENDPOINT, "save_bulk", { items }),
  printRecord: (body: {
    sku_id: number;
    qty: number;
    request_kind?: string;
    honey_request_id?: number;
    dept_request_id?: number;
    rts_label_job_id?: number;
  }) =>
    apiPost<{ print_job_id: number; print_url: string }>(ENDPOINT, "print_record", body),
  stockIn: (body: {
    code?: string;
    sku_id?: number;
    qty: number;
    packaging_row_id?: number;
    pool?: string;
  }) => apiPost<{ stock_in: Record<string, unknown>; sku: SkuBarcodeRow }>(ENDPOINT, "stock_in", body),
};
