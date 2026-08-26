import { apiGet, apiPost } from "@/app/lib/api";

export type PackCancelRestockSession = {
  id: number;
  status: "draft" | "confirmed" | "discarded";
  started_by: number;
  started_by_name?: string;
  confirmed_by?: number | null;
  confirmed_by_name?: string;
  confirmed_at?: string | null;
  created_at: string;
  item_count: number;
  items: {
    id: number;
    kind: "honey" | "dept";
    product_key: string;
    product_label: string;
    code: string;
    variant: string;
    size_label: string;
    batch_no: string | null;
    unit_status?: string;
    unit_pool?: string;
    stock_label?: string;
    scanned_at: string;
  }[];
  grouped: {
    kind: "honey" | "dept";
    product_key: string;
    product_label: string;
    variant: string;
    size_label: string;
    unit_pool?: string;
    stock_label?: string;
    qty: number;
  }[];
};

const ENDPOINT = "pack_cancel_restock.php";

export const packCancelRestockApi = {
  start: () => apiPost<{ session: PackCancelRestockSession }>(ENDPOINT, "start", {}),
  get: (id: number) => apiGet<{ session: PackCancelRestockSession }>(ENDPOINT, "get", { id }),
  scan: (sessionId: number, code: string) =>
    apiPost<{
      result: string;
      session: PackCancelRestockSession;
      lookup: {
        kind?: string;
        product_label?: string;
        variant?: string;
        size_label?: string;
        code?: string;
        stock_label?: string;
        unit_pool?: string;
      } | null;
    }>(ENDPOINT, "scan", { session_id: sessionId, code }),
  confirm: (sessionId: number) =>
    apiPost<{ session: PackCancelRestockSession }>(ENDPOINT, "confirm", { session_id: sessionId }),
  discard: (sessionId: number) => apiPost<{ ok: boolean }>(ENDPOINT, "discard", { session_id: sessionId }),
  list: (params: { from?: string; to?: string; product_key?: string; page?: number } = {}) =>
    apiGet<{
      rows: PackCancelRestockSession[];
      total: number;
      page: number;
      per_page: number;
      from: string;
      to: string;
      product_key?: string;
    }>(ENDPOINT, "list", {
      from: params.from,
      to: params.to,
      product_key: params.product_key || undefined,
      page: params.page ?? 1,
    }),
};
