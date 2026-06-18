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
  label: string;
  qty: number;
  unit_price: number;
}

export interface DeptCheckoutResult {
  sale_id: number;
  receipt_no: string;
  total: number;
  items: { product_key: string; name: string; qty: number; unit_price: number; line_total: number }[];
  buyer_name: string;
}

export const POS_DEPT_ROUTE_KEYS: Record<string, string> = {
  paragis: "pt",
  brc: "brc",
  cacao: "jar_cacao",
  "honey-comb": "honey_comb",
};

export const POS_DEPT_LABELS: Record<string, string> = {
  pt: "Paragis Tea",
  brc: "Black Rice Coffee",
  jar_cacao: "Jar Cacao",
  honey_comb: "Honey Comb",
};

export function peso(v: number): string {
  return `₱${v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}
