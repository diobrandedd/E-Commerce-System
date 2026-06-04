import { useCallback, useMemo, useState } from "react";
import { AlertCircle, CheckCircle2, Download, FileSpreadsheet, FileText, Loader2 } from "lucide-react";
import { AmbiguousOrderCarousel } from "@/app/components/csr/AmbiguousOrderCarousel";
import { FileZone } from "@/app/components/csr/FileZone";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  categorizeRow,
  finalRemarkForRow,
  isRowFullyResolved,
  parseRemarks,
  type RowResolution,
} from "@/app/lib/orderRemarksParse";
import {
  onlineOrdersLinesAppend,
  onlineOrdersRunVipStamp,
} from "@/app/lib/onlineOrdersApi";
import {
  exportOrderRemarks,
  parseOrderRemarks,
  stashOrderRemarksFile,
  triggerOrderRemarksDownload,
  orderRemarksUploadTooLarge,
  type ParseOrderRemarksResult,
} from "@/app/lib/orderRemarksApi";
import { waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";

async function stashWaybillToken(kind: "pdf" | "excel", file: File): Promise<string> {
  const form = new FormData();
  form.append("kind", kind);
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form);
  return data.token;
}

type Phase = "remarks" | "stamp";

export function VipRunStep({
  batchId,
  onStampSuccess,
  onBack,
}: {
  batchId: number;
  onStampSuccess: (result: PartnerStampResult) => void;
  onBack: () => void;
}) {
  const [phase, setPhase] = useState<Phase>("remarks");
  const [courierExcelFile, setCourierExcelFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [parseResult, setParseResult] = useState<ParseOrderRemarksResult | null>(null);
  const [resolutions, setResolutions] = useState<Record<number, RowResolution>>({});
  const [remarksToken, setRemarksToken] = useState<string | null>(null);
  const [vipExcelFile, setVipExcelFile] = useState<File | null>(null);
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);

  const rows = useMemo(() => {
    if (!parseResult) return [];
    return parseResult.orders.map(order => {
      const parsed = parseRemarks(order.remarks);
      return { order, parsed, category: categorizeRow(order.remarks, parsed) };
    });
  }, [parseResult]);

  const ambiguousRows = useMemo(() => rows.filter(r => r.category === "ambiguous"), [rows]);
  const canExport =
    parseResult !== null && (ambiguousRows.length === 0 || ambiguousRows.every(r => isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx])));

  const uploadRemarks = async (file: File) => {
    setBusy(true);
    setError(null);
    const sizeErr = orderRemarksUploadTooLarge(file);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const token = await stashOrderRemarksFile(file);
      setRemarksToken(token);
      const result = await parseOrderRemarks(token);
      setParseResult(result);
      setResolutions({});
      setToast("Workbook loaded — resolve any ambiguous orders");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not parse workbook.");
    } finally {
      setBusy(false);
    }
  };

  const runExport = async () => {
    if (!remarksToken || !parseResult) return;
    setBusy(true);
    setError(null);
    try {
      const remarks = parseResult.orders.map(o =>
        finalRemarkForRow(o.remarks, parseRemarks(o.remarks), resolutions[o.idx]),
      );
      const exp = await exportOrderRemarks(remarksToken, remarks);
      triggerOrderRemarksDownload(exp.download_token, exp.download_filename);

      const lines = parseResult.orders.map((o, i) => {
        const final = remarks[i] ?? o.remarks;
        const tn = o.phone?.trim() || `VIP-${o.idx}`;
        return {
          order_no: null,
          name: o.receiver,
          items: final,
          courier: parseResult.layout.profileLabel,
          tracking_number: tn,
        };
      });
      await onlineOrdersLinesAppend(batchId, "vip", lines);
      setToast("Remarks exported — download the Excel, then upload it below with waybill PDFs");
      window.setTimeout(() => setToast(null), 4000);
      setPhase("stamp");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Export failed.");
    } finally {
      setBusy(false);
    }
  };

  const runStamp = useCallback(async () => {
    if (!vipExcelFile) {
      setError("Upload the exported VIP Excel first.");
      return;
    }
    setBusy(true);
    setError(null);
    const sizeErr = waybillUploadTooLarge([...pdfFiles, vipExcelFile]);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const excelToken = await stashWaybillToken("excel", vipExcelFile);
      const pdfTokens: string[] = [];
      for (const f of pdfFiles) pdfTokens.push(await stashWaybillToken("pdf", f));
      const res = await onlineOrdersRunVipStamp(batchId, pdfTokens, excelToken);
      onStampSuccess(res);
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Stamping failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, vipExcelFile, onStampSuccess]);

  return (
    <div className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to pipelines
      </button>

      {phase === "remarks" && (
        <>
          <p className="text-sm text-muted-foreground">
            Step 1: Upload VIP courier Excel (J&T / SPX), resolve variants, export cleaned remarks.
          </p>
          <FileZone
            label="VIP courier Excel"
            hint="Drop J&T or SPX workbook (.xlsx) — same drag-and-drop as partner / Shopee steps"
            accept=".xlsx,.xls"
            icon={FileSpreadsheet}
            files={courierExcelFile ? [courierExcelFile] : []}
            disabled={busy}
            multiple={false}
            onFiles={fs => {
              const f = fs[0] ?? null;
              setCourierExcelFile(f);
              if (f) {
                void uploadRemarks(f);
              } else {
                setParseResult(null);
                setRemarksToken(null);
                setResolutions({});
              }
            }}
          />
          {busy && (
            <p className="text-sm text-muted-foreground flex items-center gap-2">
              <Loader2 size={16} className="animate-spin" /> Loading workbook…
            </p>
          )}

          {ambiguousRows.length > 0 && (
            <AmbiguousOrderCarousel
              rows={ambiguousRows}
              resolutions={resolutions}
              showCompleted={false}
              onShowCompletedChange={() => {}}
              onResolve={(idx, resolution) => setResolutions(prev => ({ ...prev, [idx]: resolution }))}
              disabled={busy}
              onCardCompleted={remaining => {
                setToast(remaining > 0 ? `${remaining} remaining` : "All orders resolved");
                window.setTimeout(() => setToast(null), 2800);
              }}
            />
          )}

          {canExport && (
            <button
              type="button"
              disabled={busy}
              onClick={() => void runExport()}
              className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary"
            >
              <Download size={16} /> Export remarks Excel & continue
            </button>
          )}
        </>
      )}

      {phase === "stamp" && (
        <>
          <p className="text-sm text-muted-foreground">
            Step 2: Upload the exported Excel you downloaded, plus VIP waybill PDFs, then stamp.
          </p>
          <FileZone
            label="Exported VIP Excel"
            hint="File from previous export step"
            accept=".xlsx,.xls"
            icon={FileSpreadsheet}
            files={vipExcelFile ? [vipExcelFile] : []}
            onFiles={fs => setVipExcelFile(fs[0] ?? null)}
            disabled={busy}
            multiple={false}
          />
          <FileZone
            label="VIP waybill PDFs"
            hint="PDFs from courier after remarks export"
            accept=".pdf"
            icon={FileText}
            files={pdfFiles}
            onFiles={setPdfFiles}
            disabled={busy}
          />
          <button
            type="button"
            disabled={busy || !vipExcelFile || pdfFiles.length === 0}
            onClick={() => void runStamp()}
            className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
            Stamp VIP waybills
          </button>
        </>
      )}

      {error && (
        <div className="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800 flex gap-2">
          <AlertCircle size={18} /> {error}
        </div>
      )}

    </div>
  );
}
