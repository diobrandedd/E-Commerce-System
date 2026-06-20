export interface EditRequestRow {
  id: number;
  table_name: string;
  record_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  reason: string;
  created_at: string;
  status?: "pending" | "approved" | "rejected";
  rejection_note?: string | null;
  reviewed_at?: string | null;
  requested_by_name?: string;
  dept_name?: string | null;
}

export interface StockChangeRow {
  id: number;
  stock_name: string;
  dept_name: string | null;
  old_price: string;
  new_price: string;
  changed_by_name: string | null;
  changed_at: string;
}

export interface CostChangeRow {
  id: number;
  product_name: string;
  dept_name: string | null;
  old_cost: string;
  new_cost: string;
  recalculated_at: string;
}

export interface CsrParcelRow {
  id: number;
  tracking_number: string;
  recipient_name: string | null;
  courier: string | null;
  status: string;
  ship_date: string | null;
  created_at: string;
}

export interface CsrMetrics {
  parcels_by_status: Record<string, number>;
  parcels_shipped_today: number;
  parcels_packing: number;
  rts_pool_count: number;
  recent_parcels: CsrParcelRow[];
}

export interface DashboardData {
  view: "inventory" | "csr" | "staff";
  can_view_financials: boolean;
  role: string;
  name: string;
  dept_id: number | null;
  stats: {
    stocks?: number;
    products?: number;
    active_users?: number;
    stock_entries_30d?: number;
  };
  pending_edit_requests: EditRequestRow[];
  pending_edit_requests_count: number;
  recent_stock_changes: StockChangeRow[];
  recent_cost_changes: CostChangeRow[];
  csr: CsrMetrics | null;
}

export const peso = (v: string | number) => {
  const n = typeof v === "string" ? parseFloat(v) : v;
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

export const fmtDate = (s: string) => {
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};
