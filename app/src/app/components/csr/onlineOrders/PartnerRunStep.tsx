import { useCallback, useState } from "react";
import { AlertCircle, FileSpreadsheet, FileText, Loader2, Package } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { onlineOrdersRunPartner } from "@/app/lib/onlineOrdersApi";
import { waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
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
  const [error, setError] = useState<string | null>(null);

  const run = useCallback(async () => {
    setBusy(true);
    setError(null);
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
      setError(e instanceof ApiError ? e.message : "Partner processing failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, excelFiles, onStampSuccess]);

  return (
    <div className="space-y-4 max-w-4xl">
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
      <button
        type="button"
        disabled={busy || pdfFiles.length === 0 || excelFiles.length === 0}
        onClick={() => void run()}
        className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
      >
        {busy ? <Loader2 size={16} className="animate-spin" /> : <Package size={16} />}
        {busy ? "Processing…" : "Stamp & save to batch"}
      </button>
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800 flex gap-2">
          <AlertCircle size={18} className="flex-shrink-0" />
          {error}
        </div>
      )}
    </div>
  );
}
