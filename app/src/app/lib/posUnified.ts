import { apiGet, apiPost } from "@/app/lib/api";
import type { HoneyCheckoutResult } from "@/app/lib/posHoney";

export type UnifiedPosKind = "honey" | "dept";

export interface UnifiedPosLookup {
  kind: UnifiedPosKind;
  label: string;
  product_id: number;
  product_name: string;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  product_key: string | null;
  unit_id: number;
  code: string;
  batch_no: string;
  suggested_price: number | null;
}

export interface UnifiedCartLine {
  key: string;
  kind: UnifiedPosKind;
  label: string;
  code: string;
  batch_no: string;
  product_id: number;
  variant?: string;
  size?: string;
  product_key?: string;
  unit_id: number;
  list_unit_price: number;
  qty: number;
}

export const posUnifiedApi = {
  lookup: (code: string) =>
    apiGet<{ barcode: UnifiedPosLookup }>("pos.php", "unified_lookup", { code }),

  checkout: (payload: {
    items: Array<Record<string, unknown>>;
    buyer_name: string;
    buyer_credentials?: string;
    discount_percent?: number;
    discount_request_id?: number;
  }) => apiPost<HoneyCheckoutResult>("pos.php", "unified_checkout", payload),
};

export function unifiedCartPayload(lines: UnifiedCartLine[]) {
  return lines.map(l => ({
    kind: l.kind,
    product_id: l.product_id,
    list_unit_price: l.list_unit_price,
    qty: l.qty,
    ...(l.kind === "honey"
      ? {
          variant: l.variant,
          size: l.size,
          bottle_unit_ids: [l.unit_id],
        }
      : {
          product_key: l.product_key,
          pouch_unit_ids: [l.unit_id],
        }),
  }));
}
