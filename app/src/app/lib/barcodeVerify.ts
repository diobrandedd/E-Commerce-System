import { apiGet } from "@/app/lib/api";

export type BarcodeVerifyKind = "honey" | "dept_pouch";

export interface BarcodeVerifyResult {
  found: boolean;
  kind: BarcodeVerifyKind;
  code: string;
  status: string;
  product_label: string;
  supplier_code: string;
  batch_no: string;
  batch_date: string;
  scanned_at: string | null;
  scanned_by_name: string | null;
  unit_status: string | null;
  honey: { variant: string; size: string; size_label: string } | null;
  dept: { product_key: string; unit: string; unit_plural: string } | null;
}

export const barcodeVerifyApi = {
  lookup: (code: string) =>
    apiGet<{ result: BarcodeVerifyResult }>("barcode_verify.php", "lookup", { code }),
};
