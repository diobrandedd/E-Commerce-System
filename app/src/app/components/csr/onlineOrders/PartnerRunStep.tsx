import { useCallback, useState } from "react";
import { FileSpreadsheet, FileText, Loader2, Package } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { DuplicateTrackingModal } from "@/app/components/csr/onlineOrders/DuplicateTrackingModal";
import { FulfillmentErrorBanner, FulfillmentPressable, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { onlineOrdersRunPartner } from "@/app/lib/onlineOrdersApi";
import { waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { useDuplicateTrackingHandler } from "@/app/lib/useDuplicateTrackingHandler";
import { apiPostForm, getCsrfToken } from "@/app/lib/api";
import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";

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

export function PartnerRunStep({
  batchId,
  onStampSuccess,
  onBack,
}: {
  batchId: number;
  onStampSuccess: (result: PartnerStampResult) => void;
  onBack: () => void;
}) {
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [excelFiles, setExcelFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const {
    duplicateConflicts,
    showDuplicateModal,
    setShowDuplicateModal,
    error,
    setError,
    clearErrors,
    handleApiError,
  } = useDuplicateTrackingHandler();

  const run = useCallback(async () => {
    setBusy(true);
    clearErrors();
    const sizeErr = waybillUploadTooLarge([...pdfFiles, ...excelFiles]);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const pdfTokens: string[] = [];
      const excelTokens: string[] = [];
      for (const f of pdfFiles) pdfTokens.push(await stashToken("pdf", f));
      for (const f of excelFiles) excelTokens.push(await stashToken("excel", f));
      const res = await onlineOrdersRunPartner(batchId, pdfTokens, excelTokens);
      onStampSuccess(res);
    } catch (e) {
      handleApiError(e, "Partner processing failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, excelFiles, onStampSuccess, clearErrors, handleApiError, setError]);

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to pipelines
      </button>
      <p className="text-sm text-muted-foreground">
        Partner seller orders: upload worker PDF waybills and matching iPYX Excel, then stamp.
      </p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <FileZone
          label="PDF waybills"
          hint="Courier label PDFs from workers"
          accept=".pdf,application/pdf"
          icon={FileText}
          files={pdfFiles}
          onFiles={setPdfFiles}
          disabled={busy}
        />
        <FileZone
          label="Excel order sheets"
          hint="iPYX format (.xlsx)"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={excelFiles}
          onFiles={setExcelFiles}
          disabled={busy}
        />
      </div>
      <FulfillmentPressable
        variant="primary"
        disabled={busy || pdfFiles.length === 0 || excelFiles.length === 0}
        onClick={() => void run()}
        className="inline-flex items-center gap-2"
      >
        {busy ? <Loader2 size={16} className="animate-spin" /> : <Package size={16} />}
        {busy ? "Processing…" : "Stamp & save to batch"}
      </FulfillmentPressable>
      <FulfillmentErrorBanner message={error} />
      <DuplicateTrackingModal
        open={showDuplicateModal}
        conflicts={duplicateConflicts}
        onClose={() => setShowDuplicateModal(false)}
      />
    </FulfillmentSectionEnter>
  );
}
