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

export function rtsListDamagedInventory() {
  return apiGet<{
    honey: Record<string, string>[];
    other: Record<string, string>[];
    can_use_damaged: boolean;
    pool: string;
  }>("rts.php", "inventory_list", { pool: "damaged" });
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
