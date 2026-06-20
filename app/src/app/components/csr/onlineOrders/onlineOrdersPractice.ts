import type { OnlineOrdersBatch } from "@/app/lib/onlineOrdersTypes";

/** Synthetic batch shown during practice tour — never persisted. */
export const PRACTICE_BATCH: OnlineOrdersBatch = {
  id: 0,
  status: "active",
  created_at: new Date().toISOString(),
  completed_at: null,
  line_count: 0,
  runs: [],
  master_download_token: null,
  master_filename: null,
};

export type OnlineOrdersPracticeStage =
  | "tools"
  | "vip-upload"
  | "vip-resolve"
  | "vip-build"
  | "vip-done"
  | "spx-mode"
  | "spx-upload"
  | "spx-extract"
  | "spx-resolve"
  | "spx-review"
  | "spx-build"
  | "excel-upload"
  | "excel-validate"
  | "excel-result"
  | "stamper-pipeline"
  | "stamper-excel"
  | "stamper-load"
  | "stamper-pdf"
  | "stamper-run";

const STAGE_ORDER: OnlineOrdersPracticeStage[] = [
  "tools",
  "vip-upload",
  "vip-resolve",
  "vip-build",
  "vip-done",
  "spx-mode",
  "spx-upload",
  "spx-extract",
  "spx-resolve",
  "spx-review",
  "spx-build",
  "excel-upload",
  "excel-validate",
  "excel-result",
  "stamper-pipeline",
  "stamper-excel",
  "stamper-load",
  "stamper-pdf",
  "stamper-run",
];

export function practiceStageAtLeast(stage: OnlineOrdersPracticeStage, min: OnlineOrdersPracticeStage): boolean {
  return STAGE_ORDER.indexOf(stage) >= STAGE_ORDER.indexOf(min);
}
