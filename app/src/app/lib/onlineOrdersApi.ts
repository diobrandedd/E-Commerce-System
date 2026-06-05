import type { PackagingStockSnapshot } from "@/app/lib/honeyPackagingStock";
import { api, API_BASE } from "@/app/lib/api";
import { normalizePartnerStampResult } from "@/app/lib/normalizePartnerStampResult";
import type {
  OnlineOrdersBatch,
  OnlineOrdersLineInput,
  OnlineOrdersPipeline,
  PartnerStampResult,
} from "@/app/lib/onlineOrdersTypes";

export async function onlineOrdersBatchStart(): Promise<OnlineOrdersBatch> {
  const data = await api<{ batch: OnlineOrdersBatch }>("online_orders.php", "batch_start", {
    method: "POST",
    body: {},
  });
  return data.batch;
}

export async function onlineOrdersBatchGet(batchId?: number): Promise<OnlineOrdersBatch | null> {
  const data = await api<{ batch: OnlineOrdersBatch | null }>("online_orders.php", "batch_get", {
    params: batchId ? { batch_id: batchId } : {},
  });
  return data.batch;
}

export async function onlineOrdersBatchCancel(batchId: number): Promise<void> {
  await api("online_orders.php", "batch_cancel", {
    method: "POST",
    body: { batch_id: batchId },
  });
}

export async function onlineOrdersLinesAppend(
  batchId: number,
  pipeline: OnlineOrdersPipeline,
  lines: OnlineOrdersLineInput[],
): Promise<{ lines_added: number }> {
  return api("online_orders.php", "lines_append", {
    method: "POST",
    body: { batch_id: batchId, pipeline, lines },
  });
}

export async function onlineOrdersRunPartner(
  batchId: number,
  pdfTokens: string[],
  excelTokens: string[],
): Promise<PartnerStampResult> {
  const raw = await api("online_orders.php", "run_partner", {
    method: "POST",
    body: { batch_id: batchId, pdf_tokens: pdfTokens, excel_tokens: excelTokens },
  });
  return normalizePartnerStampResult(raw);
}

export async function onlineOrdersRunVipStamp(
  batchId: number,
  pdfTokens: string[],
  excelToken: string,
): Promise<PartnerStampResult> {
  const raw = await api("online_orders.php", "run_vip_stamp", {
    method: "POST",
    body: { batch_id: batchId, pdf_tokens: pdfTokens, excel_token: excelToken },
  });
  return normalizePartnerStampResult(raw);
}

export async function onlineOrdersRunShopeeResolve(
  batchId: number,
  rows: { tracking: string; name?: string; items: string; courier?: string }[],
): Promise<{ excel_token: string; run_id: number; row_count: number }> {
  return api("online_orders.php", "run_shopee_resolve", {
    method: "POST",
    body: { batch_id: batchId, rows },
  });
}

export async function onlineOrdersRunShopeeStamp(
  batchId: number,
  pdfTokens: string[],
  excelToken: string,
): Promise<PartnerStampResult> {
  const raw = await api("online_orders.php", "run_shopee_stamp", {
    method: "POST",
    body: { batch_id: batchId, pdf_tokens: pdfTokens, excel_token: excelToken },
  });
  return normalizePartnerStampResult(raw);
}

export async function onlineOrdersBatchFinalize(batchId: number): Promise<{
  batch: OnlineOrdersBatch;
  download_token: string;
  download_filename: string;
  parcels_promoted?: number;
  parcels_skipped?: number;
  parcels_errors?: string[];
}> {
  return api("online_orders.php", "batch_finalize", {
    method: "POST",
    body: { batch_id: batchId },
  });
}

export function triggerOnlineOrdersDownload(token: string, _filename?: string): void {
  const url = `${API_BASE}/online_orders.php?action=batch_download&token=${encodeURIComponent(token)}`;
  window.location.href = url;
}

export async function onlineOrdersPackagingStock(): Promise<PackagingStockSnapshot> {
  return api("online_orders.php", "packaging_stock", { method: "GET" });
}
