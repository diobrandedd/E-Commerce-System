import { apiGet, apiPost, apiPostForm, getCsrfToken } from "@/app/lib/api";

export interface RtsLineItem {
  product_type: string;
  honey_size?: string | null;
  honey_variant?: string | null;
  combo_product?: string | null;
  combo_quantity?: number | null;
  combo_honey_size?: string | null;
  combo_honey_variant?: string | null;
  quantity: number;
  is_bonus?: boolean;
  line_role?: "reusable" | "damaged";
}

export interface RtsAttachment {
  kind: "image" | "video";
  file: string;
}

export type RtsCaseDisposition = "standard" | "swapped" | "leak";

export interface RtsCaseRow {
  id: number;
  tracking_number: string;
  courier_detected?: string;
  courier_final: string;
  shop: string;
  vip_subsource?: string | null;
  date_delivered: string;
  date_opened: string;
  documentation_photo?: string | null;
  documentation_attachments?: RtsAttachment[];
  case_disposition?: RtsCaseDisposition;
  damaged_reason?: string | null;
  damaged_tag?: string | null;
  claim_status?: string | null;
  claim_status_note?: string | null;
  voided_at?: string | null;
  created_at?: string;
  line_count?: number;
  before_shipment?: RtsBeforeShipment;
  lines?: RtsLineItem[];
}

export interface RtsBeforeShipment {
  available: boolean;
  item_id?: number | null;
  delivery_date?: string | null;
  photo_filename?: string | null;
  download_url?: string | null;
}

export interface RtsTrackingPrefill {
  tracking_normalized: string;
  found: { parcel: boolean; delivery: boolean; csr_line: boolean };
  suggested: {
    shop: string | null;
    courier_final: string | null;
    date_delivered: string | null;
    lines: RtsLineItem[];
    lines_parse_warnings: string[];
    recipient_name: string | null;
    order_no: string | null;
  };
  before_shipment: { available: boolean; download_url: string | null };
  field_sources: Record<string, string>;
  manual_required: { shop: boolean; courier: boolean; lines: boolean };
  existing_rts_case: boolean;
  detected_courier: string;
}

export interface RtsProductCatalog {
  product_types: string[];
  product_labels: Record<string, string>;
  honey_sizes: string[];
  honey_size_labels: Record<string, string>;
  honey_variants: string[];
  honey_variant_labels: Record<string, string>;
  combo_products: string[];
  combo_product_labels: Record<string, string>;
  shop_couriers: Record<string, string[]>;
  shops: string[];
  couriers: Record<string, string>;
  claim_statuses: { value: string; label: string }[];
  dispositions: { id: RtsCaseDisposition; label: string }[];
  damaged_reasons: { id: string; label: string }[];
  doc_url_base: string;
  attachments_max: number;
  video_max_mb?: number;
}

export interface RtsCaseGetResult {
  exists: boolean;
  detected_courier: string;
  case: RtsCaseRow | null;
  lines: RtsLineItem[];
}

export interface RtsCasesListResult {
  cases: RtsCaseRow[];
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

export interface RtsDashboardData {
  total_cases: number;
  cases_last_30: number;
  cases_this_month: number;
  swapped_last_30?: number;
  leaks_last_30?: number;
  vip_count: number;
  vip_pct: number;
  by_shop: Record<string, number>;
  by_courier: Record<string, number>;
  recent: {
    id: number;
    tracking_number: string;
    shop: string;
    courier_final: string;
    date_opened: string;
    damaged_tag?: string | null;
    voided_at?: string | null;
  }[];
  timeline: { d: string; cnt: number }[];
}

export function rtsGetCatalog() {
  return apiGet<RtsProductCatalog>("rts_cases.php", "product_catalog");
}

export function rtsGetCase(tracking: string) {
  return apiGet<RtsCaseGetResult>("rts_cases.php", "case_get", { tracking });
}

export function rtsDeliveryMatch(tracking: string) {
  return apiGet<{
    match: boolean;
    item_id: number | null;
    photo_filename: string | null;
    delivery_date: string | null;
    file_url: string | null;
  }>("rts_cases.php", "delivery_match", { tracking });
}

export function rtsTrackingPrefill(tracking: string) {
  return apiGet<RtsTrackingPrefill>("rts_cases.php", "tracking_prefill", { tracking });
}

export function rtsListSwapped(params: Record<string, string | number | boolean | null | undefined>) {
  return apiGet<RtsCasesListResult>("rts_cases.php", "list_swapped", params);
}

export function rtsListLeaks(params: Record<string, string | number | boolean | null | undefined>) {
  return apiGet<RtsCasesListResult>("rts_cases.php", "list_leaks", params);
}

export function rtsUploadDocumentation(file: File) {
  const form = new FormData();
  form.append("file", file);
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  return apiPostForm<{ file_name: string; file_url: string; media_kind: string }>(
    "rts_cases.php",
    "documentation_upload",
    form
  );
}

export function rtsCreateCase(body: Record<string, unknown>) {
  return apiPost<{ case_id: number }>("rts_cases.php", "case_create", body);
}

export function rtsAppendLines(tracking: string, lines: RtsLineItem[]) {
  return apiPost<{ case_id: number }>("rts_cases.php", "case_append_lines", { tracking_number: tracking, lines });
}

export function rtsUpdateCase(body: Record<string, unknown>) {
  return apiPost<{ case_id: number }>("rts_cases.php", "case_update", body);
}

export function rtsVoidCase(caseId: number) {
  return apiPost("rts_cases.php", "case_void", { case_id: caseId });
}

export function rtsUpdateClaim(caseId: number, claimStatus: string, claimStatusNote: string) {
  return apiPost("rts_cases.php", "case_update_claim", {
    case_id: caseId,
    claim_status: claimStatus,
    claim_status_note: claimStatusNote,
  });
}

export function rtsListCases(params: Record<string, string | number | boolean | null | undefined>) {
  return apiGet<RtsCasesListResult>("rts_cases.php", "cases_list", params);
}

export function rtsDashboard() {
  return apiGet<RtsDashboardData>("rts_cases.php", "dashboard");
}

export interface RtsInventoryListResult {
  honey: Record<string, string>[];
  other: Record<string, string>[];
  sync?: { last_synced_at: string | null; last_error: string | null; sellable_from_cases?: number | boolean };
  diagnostic?: {
    case_count: number;
    line_item_count: number;
    syncable_line_item_count: number;
    inventory_row_count: number;
  };
  readonly?: boolean;
  can_mutate?: boolean;
  can_use_damaged?: boolean;
  can_executive_edit?: boolean;
  /** When false, sellable Use RTS by qty is disabled (barcode cutover). */
  can_use_rts_qty?: boolean;
  /** When true, sellable on-hand is barcode-backed (not case sync). */
  barcode_backed_sellable?: boolean;
  pool?: string;
}

export function rtsListInventory(pool?: "sellable" | "damaged") {
  return apiGet<RtsInventoryListResult>(
    "rts.php",
    "inventory_list",
    pool ? { pool } : undefined,
  );
}

export function rtsListDamagedInventory() {
  return apiGet<RtsInventoryListResult>("rts.php", "inventory_list", { pool: "damaged" });
}

export interface RtsStockInScanResult {
  kind: "honey" | "dept";
  lookup_kind?: "sku" | "unique_honey" | "unique_dept";
  unit: {
    code?: string;
    variant?: string;
    size?: string;
    size_label?: string;
    product_key?: string;
    product_label?: string;
    unit_id?: number;
    rts_stock_in?: boolean;
    qty?: number;
    [key: string]: unknown;
  };
}

/** @deprecated Direct stock-in is disabled; use an RTS stock-in review session. */
export function rtsStockInScan(code: string, qty?: number) {
  return apiPost<RtsStockInScanResult>("rts.php", "stock_in_scan", {
    code: code.trim(),
    ...(qty != null ? { qty } : {}),
  });
}

export interface RtsStockInSessionItem {
  id: number;
  kind: "honey" | "dept";
  lookup_kind: "sku" | "unique_honey" | "unique_dept";
  code: string;
  sku_id: number | null;
  variant: string | null;
  size: string | null;
  size_label: string | null;
  product_key: string | null;
  product_label: string;
  qty: number;
  created_at: string;
  updated_at: string;
}

export interface RtsStockInSession {
  id: number;
  status: "draft" | "confirmed" | "discarded";
  started_by: number;
  started_by_name: string;
  confirmed_by_name: string | null;
  created_at: string;
  updated_at: string;
  confirmed_at: string | null;
  discarded_at: string | null;
  items: RtsStockInSessionItem[];
  line_count: number;
  total_qty: number;
}

export interface RtsStockInSessionScanResult {
  result: "ok" | "duplicate_session" | "replayed";
  session: RtsStockInSession;
  lookup: {
    kind: "honey" | "dept";
    lookup_kind: "sku" | "unique_honey" | "unique_dept";
    code: string;
    product_label: string;
    variant?: string | null;
    size?: string | null;
    size_label?: string | null;
    product_key?: string | null;
    qty: number;
    added_qty: number;
  } | null;
}

export function rtsStockInSessionStart() {
  return apiPost<{ session: RtsStockInSession }>("rts.php", "stock_in_session_start");
}

export function rtsStockInSessionGet(sessionId: number) {
  return apiGet<{ session: RtsStockInSession }>("rts.php", "stock_in_session_get", {
    session_id: sessionId,
  });
}

export function rtsStockInSessionScan(sessionId: number, code: string, qty = 1, requestKey: string) {
  return apiPost<RtsStockInSessionScanResult>("rts.php", "stock_in_session_scan", {
    session_id: sessionId,
    code: code.trim(),
    qty,
    request_key: requestKey,
  });
}

export function rtsStockInSessionUpdate(sessionId: number, itemId: number, qty: number) {
  return apiPost<{ session: RtsStockInSession }>("rts.php", "stock_in_session_update", {
    session_id: sessionId,
    item_id: itemId,
    qty,
  });
}

export function rtsStockInSessionRemove(sessionId: number, itemId: number) {
  return apiPost<{ session: RtsStockInSession }>("rts.php", "stock_in_session_remove", {
    session_id: sessionId,
    item_id: itemId,
  });
}

export function rtsStockInSessionCancel(sessionId: number) {
  return apiPost<{ session: RtsStockInSession }>("rts.php", "stock_in_session_cancel", {
    session_id: sessionId,
  });
}

export function rtsStockInSessionConfirm(sessionId: number) {
  return apiPost<{ session: RtsStockInSession }>("rts.php", "stock_in_session_confirm", {
    session_id: sessionId,
  });
}

export interface RtsDamagedUseLogRow {
  id: number;
  outcome: string;
  summary: string;
  account: string;
  created_at: string;
  notes?: string | null;
}

export function rtsListDamagedUseLog(limit = 100) {
  return apiGet<{ rows: RtsDamagedUseLogRow[] }>("rts.php", "damaged_use_log", {
    limit: String(limit),
  });
}

export function rtsUseDamaged(body: {
  row_id: number;
  outcome: "mix_production" | "scrap" | "cap_reseal";
  variant?: string;
  size_qtys?: Record<string, number>;
  qty_other?: number;
  notes?: string;
}) {
  return apiPost<{
    outcome: string;
    volume_gallons?: number | null;
    production_row_id?: number | null;
    sellable_row_id?: number | null;
    bottles_resealed?: number | null;
    packaging_deduct?: { stock_name: string; qty: number; on_hand: number }[];
  }>(
    "rts.php",
    "use_damaged",
    body,
  );
}

export function rtsLineDisplay(line: RtsLineItem, labels: Record<string, string>): string {
  const pt = line.product_type;
  const qty = line.quantity;
  const name = labels[pt] ?? pt;
  const parts: string[] = [];
  if (line.honey_size && line.honey_variant) {
    parts.push(`${qty}× ${name} ${line.honey_size} ${line.honey_variant}`);
  } else {
    parts.push(`${qty}× ${name}`);
  }
  if (line.is_bonus) parts.push("(bonus)");
  return parts.join(" ");
}
