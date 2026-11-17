import { apiGet, apiPost } from "@/app/lib/api";
import type { HoneyCheckoutResult } from "@/app/lib/posHoney";

export type UnifiedPosKind = "honey" | "dept" | "freebie";
export type UnifiedLookupKind = "sku" | "unique_honey" | "unique_dept" | "freebie";

export interface UnifiedPosLookup {
  kind: UnifiedPosKind;
  lookup_kind?: UnifiedLookupKind;
  requires_qty?: boolean;
  label: string;
  product_id: number;
  product_name: string;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  product_key: string | null;
  unit_id: number | null;
  sku_id?: number | null;
  code: string;
  batch_no: string | null;
  suggested_price: number | null;
  on_hand?: number;
  pool?: string;
}

export interface UnifiedCartLine {
  key: string;
  kind: UnifiedPosKind;
  lookup_kind: UnifiedLookupKind;
  label: string;
  code: string;
  batch_no: string;
  product_id: number;
  variant?: string;
  size?: string;
  product_key?: string;
  unit_id: number | null;
  sku_id?: number | null;
  list_unit_price: number;
  qty: number;
  pool?: string;
}

export interface UnifiedFreebieOption {
  product_key: string;
  label: string;
  code: string;
  on_hand: number;
}

export const posUnifiedApi = {
  lookup: (code: string) =>
    apiGet<{ barcode: UnifiedPosLookup }>("pos.php", "unified_lookup", { code }),

  freebieOptions: () =>
    apiGet<{ options: UnifiedFreebieOption[] }>("pos.php", "unified_freebie_options"),

  checkout: (payload: {
    items: Array<Record<string, unknown>>;
    buyer_name: string;
    seller_name: string;
    buyer_credentials?: string;
    discount_percent?: number;
    discount_request_id?: number;
  }) => apiPost<HoneyCheckoutResult>("pos.php", "unified_checkout", payload),
};

export function unifiedCartPayload(lines: UnifiedCartLine[]) {
  return lines.map(l => {
    if (l.kind === "freebie" || l.lookup_kind === "freebie") {
      return {
        kind: "freebie",
        product_key: l.product_key,
        qty: l.qty,
        ...(l.code ? { code: l.code } : {}),
      };
    }
    const base = {
      kind: l.kind,
      product_id: l.product_id,
      list_unit_price: l.list_unit_price,
      qty: l.qty,
      lookup_kind: l.lookup_kind,
    };
    if (l.lookup_kind === "sku") {
      return {
        ...base,
        sku_id: l.sku_id,
        code: l.code,
        ...(l.kind === "honey"
          ? { variant: l.variant, size: l.size }
          : { product_key: l.product_key }),
        ...(l.pool ? { pool: l.pool } : {}),
      };
    }
    return {
      ...base,
      ...(l.kind === "honey"
        ? {
            variant: l.variant,
            size: l.size,
            bottle_unit_ids: l.unit_id != null ? [l.unit_id] : [],
          }
        : {
            product_key: l.product_key,
            pouch_unit_ids: l.unit_id != null ? [l.unit_id] : [],
          }),
    };
  });
}
