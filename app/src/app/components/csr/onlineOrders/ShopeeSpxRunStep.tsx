import { useCallback, useMemo, useState } from "react";
import { AlertCircle, CheckCircle2, FileText, Loader2 } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { ItemResolverCarousel, type ShopeeRowResolution } from "@/app/components/csr/onlineOrders/ItemResolverCarousel";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import { onlineOrdersRunShopeeResolve, onlineOrdersRunShopeeStamp } from "@/app/lib/onlineOrdersApi";
import { extractPdfToExcel, pdfToExcelUploadTooLarge } from "@/app/lib/pdfToExcelApi";
import { waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";
import { shopeeRowNeedsItemResolution } from "@/app/lib/honeyItemVariant";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";

async function stashWaybillPdf(file: File): Promise<string> {
  const form = new FormData();
  form.append("kind", "pdf");
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form);
  return data.token;
}

type Phase = "extract" | "resolve" | "stamp";

export function ShopeeSpxRunStep({
  batchId,
  onStampSuccess,
  onBack,
}: {
  batchId: number;
  onStampSuccess: (result: PartnerStampResult) => void;
  onBack: () => void;
}) {
  const [phase, setPhase] = useState<Phase>("extract");
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [rows, setRows] = useState<PdfToExcelRow[]>([]);
  const [resolutions, setResolutions] = useState<Record<number, ShopeeRowResolution>>({});
  const [excelToken, setExcelToken] = useState<string | null>(null);

  const allResolved = useMemo(() => {
    return rows.every(r => !shopeeRowNeedsItemResolution(r, resolutions[r.page]?.items));
  }, [rows, resolutions]);

  const runExtract = async () => {
    setBusy(true);
    setError(null);
    const sizeErr = pdfToExcelUploadTooLarge(pdfFiles);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const job = await extractPdfToExcel(pdfFiles);
      const flat: PdfToExcelRow[] = [];
      for (const file of job.files) {
        for (const row of file.rows) flat.push(row);
      }
      setRows(flat);
      setResolutions({});
      setPhase("resolve");
      setToast("PDF extracted — set exact items for each row");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Extraction failed.");
    } finally {
      setBusy(false);
    }
  };

  const runResolve = async () => {
    setBusy(true);
    setError(null);
    try {
      const payload = rows.map(r => ({
        page: r.page,
        tracking: r.tracking,
        name: r.name,
        items: resolutions[r.page]?.items?.trim() || r.items?.trim() || "",
        courier: r.courier || "SPX",
      })).filter(r => {
        if (!r.items) return false;
        const src = rows.find(x => x.page === r.page);
        return src ? !shopeeRowNeedsItemResolution(src, r.items) : true;
      });
      const res = await onlineOrdersRunShopeeResolve(batchId, payload);
      setExcelToken(res.excel_token);
      setPhase("stamp");
      setToast("Excel ready — now stamp waybills");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not build order sheet.");
    } finally {
      setBusy(false);
    }
  };

  const runStamp = useCallback(async () => {
    if (!excelToken) return;
    setBusy(true);
    setError(null);
    const sizeErr = waybillUploadTooLarge(pdfFiles);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const wbPdfTokens: string[] = [];
      for (const f of pdfFiles) {
        wbPdfTokens.push(await stashWaybillPdf(f));
      }
      const res = await onlineOrdersRunShopeeStamp(batchId, wbPdfTokens, excelToken);
      onStampSuccess(res);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Stamp failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, excelToken, onStampSuccess]);

  return (
    <div className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to pipelines
      </button>

      {phase === "extract" && (
        <>
          <p className="text-sm text-muted-foreground">
            Shopee SPX (ours): upload waybill PDF only — we build the iPYX Excel, then you confirm each item.
          </p>
          <FileZone
            label="Shopee waybill PDFs"
            hint="PDF labels only"
            accept=".pdf"
            icon={FileText}
            files={pdfFiles}
            onFiles={setPdfFiles}
            disabled={busy}
          />
          <button
            type="button"
            disabled={busy || pdfFiles.length === 0}
            onClick={() => void runExtract()}
            className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : null}
            Extract from PDF
          </button>
        </>
      )}

      {phase === "resolve" && (
        <>
          <ItemResolverCarousel
            rows={rows}
            resolutions={resolutions}
            onResolve={(page, res) => setResolutions(prev => ({ ...prev, [page]: res }))}
            disabled={busy}
          />
          <button
            type="button"
            disabled={busy || !allResolved}
            onClick={() => void runResolve()}
            className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
          >
            Confirm items & build Excel
          </button>
        </>
      )}

      {phase === "stamp" && (
        <>
          <p className="text-sm text-green-700 flex items-center gap-2">
            <CheckCircle2 size={18} /> Order sheet ready. Stamp waybills (same PDFs).
          </p>
          <button
            type="button"
            disabled={busy}
            onClick={() => void runStamp()}
            className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : null}
            Stamp waybills
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
