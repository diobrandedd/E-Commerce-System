import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";

/** API may return stamp stats nested under `stamp` or flat (waybill processor shape). */
export function normalizePartnerStampResult(raw: unknown): PartnerStampResult {
  const r = raw as Record<string, unknown>;
  if (r && typeof r.stamp === "object" && r.stamp !== null) {
    const stamp = r.stamp as Record<string, unknown>;
    return {
      stamp: {
        total: Number(stamp.total ?? 0),
        matched: Number(stamp.matched ?? 0),
        unmatched: Number(stamp.unmatched ?? 0),
        unmatched_list: Array.isArray(stamp.unmatched_list) ? (stamp.unmatched_list as string[]) : [],
        download_token: String(stamp.download_token ?? ""),
        download_filename: String(stamp.download_filename ?? "Stamped_Waybills.pdf"),
        log_excerpt: String(stamp.log_excerpt ?? ""),
      },
      lines_added: Number(r.lines_added ?? 0),
      run_id: Number(r.run_id ?? 0),
    };
  }

  return {
    stamp: {
      total: Number(r.total ?? 0),
      matched: Number(r.matched ?? 0),
      unmatched: Number(r.unmatched ?? 0),
      unmatched_list: Array.isArray(r.unmatched_list) ? (r.unmatched_list as string[]) : [],
      download_token: String(r.download_token ?? ""),
      download_filename: String(r.download_filename ?? "Stamped_Waybills.pdf"),
      log_excerpt: String(r.log_excerpt ?? ""),
    },
    lines_added: Number(r.lines_added ?? 0),
    run_id: Number(r.run_id ?? 0),
  };
}
