import { useCallback, useState } from "react";
import { Download, FileSpreadsheet, FileText, Loader2, Package } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { DuplicateTrackingModal } from "@/app/components/csr/onlineOrders/DuplicateTrackingModal";
import { FulfillmentErrorBanner, FulfillmentPressable, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { onlineOrdersRunUnifiedStamp, onlineOrdersToolExcelRegister } from "@/app/lib/onlineOrdersApi";
import type { OnlineOrdersPipeline, OnlineOrdersPrepResult, PartnerStampResult } from "@/app/lib/onlineOrdersTypes";
import { triggerWaybillStashDownload, waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { useDuplicateTrackingHandler } from "@/app/lib/useDuplicateTrackingHandler";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";

const PIPELINE_OPTIONS: { id: OnlineOrdersPipeline; label: string }[] = [
  { id: "partner", label: "Partner" },
  { id: "vip", label: "VIP" },
  { id: "shopee_spx", label: "Shopee SPX" },
];

async function stashToken(kind: "pdf" | "excel", file: File): Promise<string> {
  const form = new FormData();
  form.append("kind", kind);
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form);
  return data.token;
}

export function WaybillStamperTool({
  batchId,
  onStampSuccess,
  onBack,
}: {
  batchId: number;
  onStampSuccess: (result: PartnerStampResult) => void;
  onBack: () => void;
}) {
  const [excelFiles, setExcelFiles] = useState<File[]>([]);
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [prep, setPrep] = useState<OnlineOrdersPrepResult | null>(null);
  const [pipeline, setPipeline] = useState<OnlineOrdersPipeline>("partner");
  const [busy, setBusy] = useState(false);
  const [registerError, setRegisterError] = useState<string | null>(null);
  const {
    duplicateConflicts,
    showDuplicateModal,
    setShowDuplicateModal,
    error,
    setError,
    clearErrors,
    handleApiError,
  } = useDuplicateTrackingHandler();

  const registerExcel = async () => {
    if (excelFiles.length === 0) {
      setRegisterError("Upload the prepared iPYX Excel first.");
      return;
    }
    setBusy(true);
    setRegisterError(null);
    setPrep(null);
    try {
      const tokens: string[] = [];
      for (const f of excelFiles) {
        tokens.push(await stashToken("excel", f));
      }
      const result = await onlineOrdersToolExcelRegister(tokens[0], tokens.length > 1 ? tokens : undefined);
      setPrep(result);
    } catch (e) {
      setRegisterError(e instanceof ApiError ? e.message : "Could not read Excel.");
    } finally {
      setBusy(false);
    }
  };

  const runStamp = useCallback(async () => {
    if (!prep) {
      setError("Register the Excel file first.");
      return;
    }
    setBusy(true);
    clearErrors();
    const sizeErr = waybillUploadTooLarge([...pdfFiles, ...excelFiles]);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    if (pdfFiles.length === 0) {
      setError("Upload at least one waybill PDF.");
      setBusy(false);
      return;
    }
    try {
      const pdfTokens: string[] = [];
      for (const f of pdfFiles) pdfTokens.push(await stashToken("pdf", f));
      const res = await onlineOrdersRunUnifiedStamp(
        batchId,
        pdfTokens,
        prep.excel_token,
        pipeline,
        prep.excel_tokens,
      );
      onStampSuccess(res);
    } catch (e) {
      handleApiError(e, "Stamping failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, excelFiles, prep, pipeline, onStampSuccess, clearErrors, handleApiError, setError]);

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to tools
      </button>
      <p className="text-sm text-muted-foreground">
        Upload a prepared iPYX Excel plus matching waybill PDFs. Download the sheet to double-check, then stamp into
        this batch.
      </p>

      <div className="flex flex-wrap gap-2 items-center">
        <span className="text-xs text-muted-foreground">Order type:</span>
        {PIPELINE_OPTIONS.map(opt => (
          <button
            key={opt.id}
            type="button"
            disabled={busy}
            onClick={() => setPipeline(opt.id)}
            className={`text-xs font-medium px-3 py-1.5 rounded-lg border ${
              pipeline === opt.id
                ? "border-primary bg-primary/10 text-primary"
                : "border-border text-muted-foreground"
            }`}
          >
            {opt.label}
          </button>
        ))}
      </div>

      <FileZone
        label="Prepared iPYX Excel"
        hint="From VIP arranger, SPX merge, or your own sheet"
        accept=".xlsx,.xls"
        icon={FileSpreadsheet}
        files={excelFiles}
        onFiles={fs => {
          setExcelFiles(fs);
          setPrep(null);
        }}
        disabled={busy}
      />
      <button
        type="button"
        disabled={busy || excelFiles.length === 0}
        onClick={() => void registerExcel()}
        className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
      >
        {busy && !prep ? <Loader2 size={16} className="animate-spin inline" /> : null}
        Load Excel ({excelFiles.length} file{excelFiles.length === 1 ? "" : "s"})
      </button>

      {prep && (
        <div className="rounded-lg border bg-muted/30 px-4 py-3 space-y-3 text-sm">
          <p className="text-muted-foreground">
            {prep.row_count} row{prep.row_count === 1 ? "" : "s"} loaded — download to verify before stamping.
          </p>
          <FulfillmentPressable
            variant="outline"
            className="inline-flex items-center gap-2 px-4 py-2"
            onClick={() => triggerWaybillStashDownload(prep.excel_token)}
          >
            <Download size={16} /> Download Excel
          </FulfillmentPressable>
        </div>
      )}

      <FileZone
        label="PDF waybills"
        hint="Courier label PDFs matching the Excel"
        accept=".pdf,application/pdf"
        icon={FileText}
        files={pdfFiles}
        onFiles={setPdfFiles}
        disabled={busy}
      />

      <FulfillmentPressable
        variant="primary"
        disabled={busy || !prep || pdfFiles.length === 0}
        onClick={() => void runStamp()}
        className="inline-flex items-center gap-2"
      >
        {busy ? <Loader2 size={16} className="animate-spin" /> : <Package size={16} />}
        {busy ? "Stamping…" : "Stamp & save to batch"}
      </FulfillmentPressable>

      <FulfillmentErrorBanner message={registerError} />
      <FulfillmentErrorBanner message={error} />
      <DuplicateTrackingModal
        open={showDuplicateModal}
        conflicts={duplicateConflicts}
        onClose={() => setShowDuplicateModal(false)}
      />
    </FulfillmentSectionEnter>
  );
}
