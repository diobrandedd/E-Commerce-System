export interface DeptPosProduct {
  key: string;
  label: string;
  code: string;
  dept: string;
  unit: string;
  available_qty: number;
  product_id: number | null;
  selling_price: number | null;
  sellable: boolean;
}

export interface DeptCartLine {
  product_key: string;
  product_id: number;
  label: string;
  qty: number;
  unit_price: number;
}

export interface DeptCheckoutResult {
  sale_id: number;
  receipt_no: string;
  subtotal?: number;
  discount_percent?: number | null;
  discount_amount?: number;
  total: number;
  items: {
    product_id: number;
    product_key: string;
    name: string;
    qty: number;
    list_unit_price?: number;
    unit_price: number;
    line_subtotal?: number;
    line_total: number;
  }[];
  buyer_name: string;
}

export interface DeptDiscountRequestResult {
  request_id: number;
  request_no: string;
  status: "pending" | "approved" | "declined";
  buyer_name: string;
  discount_percent: number;
  subtotal: number;
  discount_amount: number;
  total: number;
}

export interface DeptDiscountRequestStatus extends DeptDiscountRequestResult {
  decline_note?: string | null;
  receipt?: DeptCheckoutResult;
}

export const POS_DEPT_ROUTE_KEYS: Record<string, string> = {
  paragis: "pt",
  brc: "brc",
  cacao: "jar_cacao",
  "honey-comb": "honey_comb",
};

export const POS_DEPT_ROUTE_SEGMENTS = Object.keys(POS_DEPT_ROUTE_KEYS);

export function posDeptProductKeyFromRoute(routeSegment: string): string | null {
  const key = POS_DEPT_ROUTE_KEYS[routeSegment];
  return key ?? null;
}

export const POS_DEPT_LABELS: Record<string, string> = {
  pt: "Paragis Tea",
  brc: "Black Rice Coffee",
  jar_cacao: "Jar Cacao",
  honey_comb: "Honey Comb",
};

export function peso(v: number): string {
  return `₱${v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
