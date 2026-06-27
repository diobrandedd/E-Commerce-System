import type { PackagingStockSnapshot } from "@/app/lib/honeyPackagingStock";
import { api, API_BASE } from "@/app/lib/api";
import { normalizePartnerStampResult } from "@/app/lib/normalizePartnerStampResult";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import type {
  OnlineOrdersBatch,
  OnlineOrdersLineInput,
  OnlineOrdersPipeline,
  OnlineOrdersPrepResult,
  PartnerSeller,
  PartnerStampResult,
  VipArrangerRow,
  VipArrangerSaveResult,
  VipMergeResult,
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

export async function onlineOrdersToolVipPrepare(
  rows: OnlineOrdersLineInput[],
): Promise<OnlineOrdersPrepResult> {
  return api("online_orders.php", "tool_vip_prepare", {
    method: "POST",
    body: { rows },
  });
}

export async function onlineOrdersToolVipArrangerSave(
  rows: VipArrangerRow[],
): Promise<VipArrangerSaveResult> {
  return api("online_orders.php", "tool_vip_arranger_save", {
    method: "POST",
    body: { rows },
  });
}

export async function onlineOrdersToolVipArrangerGet(
  arrangerToken?: string,
): Promise<{ arranger_token: string | null; rows: VipArrangerRow[]; row_count: number; created_at?: string | null }> {
  return api("online_orders.php", "tool_vip_arranger_get", {
    params: arrangerToken ? { arranger_token: arrangerToken } : {},
  });
}

export async function onlineOrdersToolVipMerge(params: {
  arranger_token?: string;
  tracking_excel_token: string;
  courier?: "jnt" | "spx";
}): Promise<VipMergeResult> {
  return api("online_orders.php", "tool_vip_merge", {
    method: "POST",
    body: params,
  });
}

export async function onlineOrdersToolSpxPrepare(
  rows: { tracking: string; order_no?: string | null; name?: string; items: string; courier?: string }[],
): Promise<OnlineOrdersPrepResult> {
  return api("online_orders.php", "tool_spx_prepare", {
    method: "POST",
    body: { rows },
  });
}

export async function onlineOrdersToolExcelRegister(
  excelToken: string,
  excelTokens?: string[],
): Promise<OnlineOrdersPrepResult> {
  return api("online_orders.php", "tool_excel_register", {
    method: "POST",
    body: excelTokens?.length ? { excel_tokens: excelTokens } : { excel_token: excelToken },
  });
}

export async function onlineOrdersRunUnifiedStamp(
  batchId: number,
  pdfTokens: string[],
  excelToken: string,
  pipeline: OnlineOrdersPipeline,
  excelTokens?: string[],
  partnerSeller?: PartnerSeller | null,
): Promise<PartnerStampResult> {
  const raw = await api("online_orders.php", "run_unified_stamp", {
    method: "POST",
    body: {
      batch_id: batchId,
      pdf_tokens: pdfTokens,
      pipeline,
      ...(partnerSeller ? { partner_seller: partnerSeller } : {}),
      ...(excelTokens?.length ? { excel_tokens: excelTokens } : { excel_token: excelToken }),
    },
  });
  return normalizePartnerStampResult(raw);
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
  rows: { tracking: string; order_no?: string | null; name?: string; items: string; courier?: string }[],
): Promise<{ excel_token: string; run_id: number; row_count: number }> {
  return api("online_orders.php", "run_shopee_resolve", {
    method: "POST",
    body: { batch_id: batchId, rows },
  });
}

export async function onlineOrdersSpxImportPackingList(
  excelToken: string,
  pdfTokens: string[] = [],
): Promise<{ rows: PdfToExcelRow[] }> {
  return api("online_orders.php", "spx_import_packing_list", {
    method: "POST",
    body: { excel_token: excelToken, pdf_tokens: pdfTokens },
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
