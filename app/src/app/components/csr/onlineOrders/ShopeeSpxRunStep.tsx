import { useCallback, useMemo, useState } from "react";
import { CheckCircle2, FileText, Loader2 } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { DuplicateTrackingModal } from "@/app/components/csr/onlineOrders/DuplicateTrackingModal";
import { FulfillmentErrorBanner, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { ItemResolverCarousel, type ShopeeRowResolution } from "@/app/components/csr/onlineOrders/ItemResolverCarousel";
import { ShopeeItemsReviewPanel } from "@/app/components/csr/onlineOrders/ShopeeItemsReviewPanel";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  onlineOrdersRunShopeeResolve,
  onlineOrdersRunShopeeStamp,
  onlineOrdersSpxImportPackingList,
} from "@/app/lib/onlineOrdersApi";
import { extractPdfToExcel, pdfToExcelUploadTooLarge } from "@/app/lib/pdfToExcelApi";
import { waybillUploadTooLarge } from "@/app/lib/waybillApi";
import { useDuplicateTrackingHandler } from "@/app/lib/useDuplicateTrackingHandler";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";
import { shopeeFinalItemsForRow, shopeeRowIsReadyForExport } from "@/app/lib/honeyItemVariant";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";

async function stashWaybillFile(file: File, kind: "pdf" | "excel"): Promise<string> {
  const form = new FormData();
  form.append("kind", kind);
  form.append("file", file);
  form.append("action", "stash");
  const csrf = getCsrfToken();
  if (csrf) form.append("csrf", csrf);
  const data = await apiPostForm<{ token: string }>("waybill.php", "stash", form);
  return data.token;
}

type ImportMode = "pdf_only" | "packing_list";
type Phase = "extract" | "resolve" | "review" | "stamp";

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
  const [importMode, setImportMode] = useState<ImportMode>("pdf_only");
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [packingListFile, setPackingListFile] = useState<File | null>(null);
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
  const [toast, setToast] = useState<string | null>(null);
  const [rows, setRows] = useState<PdfToExcelRow[]>([]);
  const [resolutions, setResolutions] = useState<Record<number, ShopeeRowResolution>>({});
  const [showCompleted, setShowCompleted] = useState(false);
  const [excelToken, setExcelToken] = useState<string | null>(null);
  const [editFocusPage, setEditFocusPage] = useState<number | null>(null);

  const allResolved = useMemo(() => {
    return rows.every(r => shopeeRowIsReadyForExport(r, resolutions[r.page]));
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
      if (importMode === "packing_list") {
        if (!packingListFile) {
          setError("Upload the Shopee packing list Excel.");
          setBusy(false);
          return;
        }
        const excelToken = await stashWaybillFile(packingListFile, "excel");
        const pdfTokens: string[] = [];
        for (const f of pdfFiles) {
          pdfTokens.push(await stashWaybillFile(f, "pdf"));
        }
        const res = await onlineOrdersSpxImportPackingList(excelToken, pdfTokens);
        setRows(res.rows);
        setToast("Packing list imported — pick variant where needed, then confirm each line");
      } else {
        const job = await extractPdfToExcel(pdfFiles);
        const flat: PdfToExcelRow[] = [];
        for (const file of job.files) {
          for (const row of file.rows) flat.push(row);
        }
        setRows(flat);
        setToast("PDF extracted — pick variant, then confirm each line");
      }
      setResolutions({});
      setShowCompleted(false);
      setEditFocusPage(null);
      setPhase("resolve");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Import failed.");
    } finally {
      setBusy(false);
    }
  };

  const goToReview = () => {
    setEditFocusPage(null);
    setPhase("review");
    setToast("Review all item lines before building Excel");
    window.setTimeout(() => setToast(null), 2800);
  };

  const runResolve = async () => {
    setBusy(true);
    clearErrors();
    try {
      const payload = rows
        .map(r => ({
          page: r.page,
          tracking: r.tracking,
          order_no: r.order_no?.trim() || null,
          name: r.name,
          items: shopeeFinalItemsForRow(r, resolutions[r.page]),
          courier: r.courier || "SPX",
        }))
        .filter(r => r.items);
      const res = await onlineOrdersRunShopeeResolve(batchId, payload);
      setExcelToken(res.excel_token);
      setPhase("stamp");
      setToast("Excel ready — now stamp waybills");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      handleApiError(e, "Could not build order sheet.");
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
        wbPdfTokens.push(await stashWaybillFile(f, "pdf"));
      }
      const res = await onlineOrdersRunShopeeStamp(batchId, wbPdfTokens, excelToken);
      onStampSuccess(res);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Stamp failed.");
    } finally {
      setBusy(false);
    }
  }, [batchId, pdfFiles, excelToken, onStampSuccess]);

  const handleEditFromReview = (page: number) => {
    setResolutions(prev => {
      const next = { ...prev };
      delete next[page];
      return next;
    });
    setEditFocusPage(page);
    setShowCompleted(false);
    setPhase("resolve");
  };

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to pipelines
      </button>

      {phase === "extract" && (
        <>
          <p className="text-sm text-muted-foreground">
            Shopee SPX (ours): build the iPYX Excel from waybill PDFs, or from Shopee&apos;s packing list Excel
            plus PDFs for names and stamping.
          </p>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              disabled={busy}
              onClick={() => setImportMode("pdf_only")}
              className={`text-xs font-medium px-3 py-1.5 rounded-lg border ${
                importMode === "pdf_only"
                  ? "border-primary bg-primary/10 text-primary"
                  : "border-border text-muted-foreground"
              }`}
            >
              PDF only
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => setImportMode("packing_list")}
              className={`text-xs font-medium px-3 py-1.5 rounded-lg border ${
                importMode === "packing_list"
                  ? "border-primary bg-primary/10 text-primary"
                  : "border-border text-muted-foreground"
              }`}
            >
              Packing list Excel + PDF
            </button>
          </div>
          {importMode === "packing_list" && (
            <FileZone
              label="Shopee packing list Excel"
              hint="Packing List.Standard Local *.xlsx"
              accept=".xlsx,.xls"
              icon={FileText}
              files={packingListFile ? [packingListFile] : []}
              onFiles={files => setPackingListFile(files[0] ?? null)}
              disabled={busy}
              multiple={false}
            />
          )}
          <FileZone
            label="Shopee waybill PDFs"
            hint={importMode === "packing_list" ? "For customer names + stamp step" : "PDF labels only"}
            accept=".pdf"
            icon={FileText}
            files={pdfFiles}
            onFiles={setPdfFiles}
            disabled={busy}
          />
          <button
            type="button"
            disabled={
              busy ||
              pdfFiles.length === 0 ||
              (importMode === "packing_list" && !packingListFile)
            }
            onClick={() => void runExtract()}
            className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : null}
            {importMode === "packing_list" ? "Import packing list" : "Extract from PDF"}
          </button>
        </>
      )}

      {phase === "resolve" && (
        <>
          <ItemResolverCarousel
            rows={rows}
            resolutions={resolutions}
            showCompleted={showCompleted}
            onShowCompletedChange={setShowCompleted}
            onResolve={(page, res) => {
              setResolutions(prev => ({ ...prev, [page]: res }));
              if (editFocusPage === page) setEditFocusPage(null);
            }}
            onClearResolution={page => {
              setResolutions(prev => {
                const next = { ...prev };
                delete next[page];
                return next;
              });
              setEditFocusPage(page);
              setShowCompleted(false);
            }}
            disabled={busy}
            focusPage={editFocusPage}
          />
          {allResolved && (
            <button
              type="button"
              disabled={busy}
              onClick={goToReview}
              className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
            >
              Review all items
            </button>
          )}
        </>
      )}

      {phase === "review" && (
        <>
          <ShopeeItemsReviewPanel
            rows={rows}
            resolutions={resolutions}
            onEdit={handleEditFromReview}
            disabled={busy}
          />
          <div className="flex flex-wrap gap-2 pt-2">
            <button
              type="button"
              disabled={busy}
              onClick={() => setPhase("resolve")}
              className="text-sm font-medium px-4 py-2.5 rounded-lg border border-border"
            >
              ← Back to variant picks
            </button>
            <button
              type="button"
              disabled={busy || !allResolved}
              onClick={() => void runResolve()}
              className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
            >
              {busy ? <Loader2 size={16} className="animate-spin inline" /> : null}
              Confirm all & build Excel
            </button>
          </div>
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

      <FulfillmentErrorBanner message={error} />

      <DuplicateTrackingModal
        open={showDuplicateModal}
        conflicts={duplicateConflicts}
        onClose={() => setShowDuplicateModal(false)}
      />
    </FulfillmentSectionEnter>
  );
}
