import { apiGet, apiPost } from "@/app/lib/api";

export type LeakDamageReason = "leaked" | "damaged";

export interface LeakDamageRow {
  id: number;
  kind: "honey" | "dept";
  product_key: string | null;
  variant: string | null;
  size: string | null;
  code: string;
  batch_no: string;
  reason: LeakDamageReason;
  reason_label: string;
  product_label: string;
  scanned_by: number;
  scanned_by_name: string;
  scanned_at: string;
  note: string | null;
}

export interface LeakDamageScanResult {
  id: number;
  kind: "honey" | "dept";
  product_key: string | null;
  variant: string | null;
  size: string | null;
  code: string;
  batch_no: string;
  reason: LeakDamageReason;
  reason_label: string;
  product_label: string;
}

export const finishedLeakDamageApi = {
  scan: (code: string, reason: LeakDamageReason, note?: string) =>
    apiPost<{ row: LeakDamageScanResult }>("finished_leak_damage.php", "scan", {
      code,
      reason,
      note,
    }),

  history: (page = 1, filters?: { reason?: LeakDamageReason; scan_date?: string }) =>
    apiGet<{ rows: LeakDamageRow[]; total: number; page: number; per_page: number; total_pages: number }>(
      "finished_leak_damage.php",
      "history",
      {
        page: String(page),
        ...(filters?.reason ? { reason: filters.reason } : {}),
        ...(filters?.scan_date ? { scan_date: filters.scan_date } : {}),
      },
    ),
};
