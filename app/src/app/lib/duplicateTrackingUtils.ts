import { ApiError } from "@/app/lib/api";
import type { DuplicateTrackingConflict } from "@/app/lib/onlineOrdersTypes";

export function duplicateConflictsFromApiError(e: unknown): DuplicateTrackingConflict[] | null {
  if (!(e instanceof ApiError)) {
    return null;
  }
  const data = e.data as { duplicates?: unknown } | DuplicateTrackingConflict[] | null;
  if (Array.isArray(data) && data.length > 0) {
    return data as DuplicateTrackingConflict[];
  }
  if (data && typeof data === "object" && Array.isArray((data as { duplicates?: unknown }).duplicates)) {
    const dups = (data as { duplicates: DuplicateTrackingConflict[] }).duplicates;
    return dups.length > 0 ? dups : null;
  }
  return null;
}

export function pipelineLabel(pipeline?: string): string {
  switch (pipeline) {
    case "partner":
      return "Partner";
    case "vip":
      return "VIP";
    case "shopee_spx":
      return "Shopee SPX";
    default:
      return pipeline ?? "—";
  }
}

export function existingSourceLabel(source: string): string {
  switch (source) {
    case "batch_line":
      return "Saved batch line";
    case "parcel":
      return "Fulfillment parcel";
    case "incoming_upload":
      return "Duplicate in this upload";
    default:
      return source;
  }
}
