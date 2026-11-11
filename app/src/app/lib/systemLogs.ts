import { apiGet } from "@/app/lib/api";

const ENDPOINT = "system_logs.php";

export const SYSTEM_LOG_CATEGORIES = [
  "product_setup",
  "rts",
  "deliveries",
  "order_management",
  "pos",
  "sales",
  "inventory",
  "staff_settings",
] as const;

export type SystemLogCategory = (typeof SYSTEM_LOG_CATEGORIES)[number];

export type SystemLogRow = {
  id: number;
  category: SystemLogCategory | string;
  action: string;
  summary: string;
  entity_type: string | null;
  entity_id: string | null;
  old_value: string | null;
  new_value: string | null;
  meta: Record<string, unknown> | null;
  actor_user_id: number | null;
  actor_name: string | null;
  actor_username: string | null;
  created_at: string;
};

export type SystemLogListResult = {
  rows: SystemLogRow[];
  total: number;
  page: number;
  per_page: number;
};

export const SYSTEM_LOG_CATEGORY_LABELS: Record<SystemLogCategory, string> = {
  product_setup: "Product setup",
  rts: "RTS",
  deliveries: "Deliveries",
  order_management: "Order management",
  pos: "POS",
  sales: "Sales",
  inventory: "Inventory",
  staff_settings: "Staff & settings",
};

export const systemLogsApi = {
  list: (opts: {
    page?: number;
    per_page?: number;
    category?: string;
    q?: string;
    date_from?: string;
    date_to?: string;
  }) =>
    apiGet<SystemLogListResult>(ENDPOINT, "list", {
      page: opts.page ?? 1,
      per_page: opts.per_page ?? 20,
      ...(opts.category ? { category: opts.category } : {}),
      ...(opts.q ? { q: opts.q } : {}),
      ...(opts.date_from ? { date_from: opts.date_from } : {}),
      ...(opts.date_to ? { date_to: opts.date_to } : {}),
    }),
};
