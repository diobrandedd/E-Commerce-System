import { useMemo, useState } from "react";
import { CheckCircle2, Download, FileText, Loader2 } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import { FulfillmentErrorBanner, FulfillmentPressable, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { ItemResolverCarousel, type ShopeeRowResolution } from "@/app/components/csr/onlineOrders/ItemResolverCarousel";
import { ShopeeItemsReviewPanel } from "@/app/components/csr/onlineOrders/ShopeeItemsReviewPanel";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import { onlineOrdersSpxImportPackingList, onlineOrdersToolSpxPrepare } from "@/app/lib/onlineOrdersApi";
import { extractPdfToExcel, pdfToExcelUploadTooLarge } from "@/app/lib/pdfToExcelApi";
import type { OnlineOrdersPrepResult } from "@/app/lib/onlineOrdersTypes";
import { shopeeFinalItemsForRow, shopeeRowIsReadyForExport } from "@/app/lib/honeyItemVariant";
import type { PdfToExcelRow } from "@/app/lib/pdfToExcelApi";
import { triggerWaybillStashDownload } from "@/app/lib/waybillApi";
import { apiPostForm, getCsrfToken, ApiError } from "@/app/lib/api";
import { PracticeTap } from "@/app/components/tour/PracticeTap";
import {
  practiceStageAtLeast,
  type OnlineOrdersPracticeStage,
} from "@/app/components/csr/onlineOrders/onlineOrdersPractice";
import { OnlineOrdersBackToToolsButton } from "@/app/components/csr/onlineOrders/OnlineOrdersNavButtons";

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
type Phase = "extract" | "resolve" | "review" | "ready";

export function SpxPrepTool({
  onDone,
  onBack,
  practiceMode = false,
  practiceStage = "spx-mode",
}: {
  onDone: () => void;
  onBack: () => void;
  practiceMode?: boolean;
  practiceStage?: OnlineOrdersPracticeStage;
}) {
  const [phase, setPhase] = useState<Phase>("extract");
  const [importMode, setImportMode] = useState<ImportMode>("pdf_only");
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [packingListFile, setPackingListFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [rows, setRows] = useState<PdfToExcelRow[]>([]);
  const [resolutions, setResolutions] = useState<Record<number, ShopeeRowResolution>>({});
  const [showCompleted, setShowCompleted] = useState(false);
  const [editFocusPage, setEditFocusPage] = useState<number | null>(null);
  const [prepResult, setPrepResult] = useState<OnlineOrdersPrepResult | null>(null);

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

  const runPrepare = async () => {
    setBusy(true);
    setError(null);
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
      const prep = await onlineOrdersToolSpxPrepare(payload);
      setPrepResult(prep);
      setPhase("ready");
      setToast("Excel built — download and check before stamping");
      window.setTimeout(() => setToast(null), 3200);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not build order sheet.");
    } finally {
      setBusy(false);
    }
  };

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

  if (practiceMode) {
    return (
      <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
        <OnlineOrdersBackToToolsButton onClick={onBack} />
        <p className="text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          Practice mode — no files uploaded
        </p>
        {practiceStageAtLeast(practiceStage, "spx-mode") && (
          <div className="flex flex-wrap gap-2" data-tour="online-orders-spx-mode">
            <button type="button" className="text-xs font-medium px-3 py-1.5 rounded-lg border border-primary bg-primary/10 text-primary">
              PDF only
            </button>
            <button type="button" className="text-xs font-medium px-3 py-1.5 rounded-lg border border-border text-muted-foreground">
              Packing list Excel + PDF
            </button>
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "spx-upload") && (
          <div data-tour="online-orders-spx-upload">
            <PracticeTap label="Practice: upload Shopee waybill PDFs" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "spx-extract") && (
          <div data-tour="online-orders-spx-extract">
            <PracticeTap label="Practice: extract from PDF" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "spx-resolve") && (
          <div data-tour="online-orders-spx-resolve" className="rounded-xl border bg-card p-4 space-y-2">
            <p className="text-sm font-medium">Confirm variant — page 1 of 3</p>
            <PracticeTap label="Practice: confirm line variant" className="py-3" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "spx-review") && (
          <div data-tour="online-orders-spx-review" className="rounded-xl border bg-card p-4 space-y-2">
            <p className="text-sm font-medium">Review all items (3 lines)</p>
            <p className="text-xs text-muted-foreground">Verify names, items, and tracking before build.</p>
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "spx-build") && (
          <div data-tour="online-orders-spx-build">
            <PracticeTap label="Practice: build iPYX Excel" />
          </div>
        )}
      </FulfillmentSectionEnter>
    );
  }

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <OnlineOrdersBackToToolsButton onClick={onBack} />

      {phase === "extract" && (
        <>
          <p className="text-sm text-muted-foreground">
            Build the iPYX Excel from waybill PDFs, or from Shopee&apos;s packing list Excel plus PDFs for names.
          </p>
          <div className="flex flex-wrap gap-2" data-tour="online-orders-spx-mode">
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
          <div data-tour="online-orders-spx-upload">
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
          </div>
          <button
            type="button"
            disabled={
              busy ||
              pdfFiles.length === 0 ||
              (importMode === "packing_list" && !packingListFile)
            }
            onClick={() => void runExtract()}
            data-tour="online-orders-spx-extract"
            className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : null}
            {importMode === "packing_list" ? "Import packing list" : "Extract from PDF"}
          </button>
        </>
      )}

      {phase === "resolve" && (
        <div data-tour="online-orders-spx-resolve">
        <>
          {editFocusPage != null && (
            <p className="text-sm text-muted-foreground">
              Editing page {editFocusPage} — type the item line exactly as packers should see it, then save.
            </p>
          )}
          <ItemResolverCarousel
            rows={rows}
            resolutions={resolutions}
            showCompleted={showCompleted}
            onShowCompletedChange={setShowCompleted}
            onResolve={(page, res) => {
              setResolutions(prev => ({ ...prev, [page]: res }));
              if (editFocusPage === page) {
                setEditFocusPage(null);
                setPhase("review");
                setToast("Row updated — back on review");
                window.setTimeout(() => setToast(null), 2800);
              }
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
        </div>
      )}

      {phase === "review" && (
        <div data-tour="online-orders-spx-review">
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
              onClick={() => void runPrepare()}
              data-tour="online-orders-spx-build"
              className="text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary disabled:opacity-50"
            >
              {busy ? <Loader2 size={16} className="animate-spin inline" /> : null}
              Build iPYX Excel
            </button>
          </div>
        </>
        </div>
      )}

      {phase === "ready" && prepResult && (
        <div className="rounded-xl border border-green-200 bg-green-50/80 p-5 space-y-4">
          <p className="text-sm text-green-800 flex items-center gap-2 font-medium">
            <CheckCircle2 size={18} />
            iPYX Excel ready ({prepResult.row_count} rows)
          </p>
          <p className="text-sm text-muted-foreground">
            Download the sheet and verify names, items, and tracking numbers. Go back to tools, then open PDF + Excel
            stamper when ready.
          </p>
          <div className="flex flex-wrap gap-3">
            <FulfillmentPressable
              variant="outline"
              className="inline-flex items-center gap-2 px-4 py-2.5"
              onClick={() => triggerWaybillStashDownload(prepResult.excel_token)}
            >
              <Download size={16} /> Download prepared Excel
            </FulfillmentPressable>
            <OnlineOrdersBackToToolsButton onClick={onDone} />
            <button
              type="button"
              disabled={busy}
              onClick={() => {
                setPrepResult(null);
                setPhase("review");
              }}
              className="text-sm text-muted-foreground hover:text-foreground px-2"
            >
              ← Back to review
            </button>
          </div>
        </div>
      )}

      <FulfillmentErrorBanner message={error} />
    </FulfillmentSectionEnter>
  );
}
