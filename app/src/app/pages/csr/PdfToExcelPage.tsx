import { useCallback, useMemo, useRef, useState } from "react";
import { Link } from "react-router";
import {
  AlertCircle,
  CheckCircle2,
  Download,
  FileSpreadsheet,
  FileText,
  Loader2,
  RotateCcw,
} from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import {
  OrderRemarksSectionEnter,
  OrderRemarksToast,
} from "@/app/components/csr/orderRemarksMotion";
import {
  confirmPdfToExcel,
  extractPdfToExcel,
  pdfToExcelUploadTooLarge,
  triggerPdfToExcelDownload,
  type PdfToExcelExtractResult,
  type PdfToExcelFileResult,
  type PdfToExcelRow,
} from "@/app/lib/pdfToExcelApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

function missingLabel(fields: string[]): string {
  if (fields.length === 0) return "Complete";
  return fields.map(f => (f === "tracking" ? "Tracking" : f === "name" ? "Name" : "Item/s")).join(", ");
}

function ReviewTable({
  rows,
  showIncompleteOnly,
}: {
  rows: PdfToExcelRow[];
  showIncompleteOnly: boolean;
}) {
  const visible = showIncompleteOnly ? rows.filter(r => !r.complete) : rows;

  if (visible.length === 0) {
    return (
      <p className="text-xs text-muted-foreground py-4 text-center">
        {showIncompleteOnly ? "No incomplete rows." : "No rows."}
      </p>
    );
  }

  return (
    <div className="overflow-x-auto max-h-[min(50vh,420px)] overflow-y-auto border border-border rounded-lg">
      <table className="w-full text-xs text-left">
        <thead className="sticky top-0 bg-muted/90 backdrop-blur z-10">
          <tr className="border-b border-border">
            <th className="px-2 py-2 font-semibold w-12">Page</th>
            <th className="px-2 py-2 font-semibold min-w-[8rem]">Tracking</th>
            <th className="px-2 py-2 font-semibold min-w-[7rem]">Name</th>
            <th className="px-2 py-2 font-semibold min-w-[10rem]">Item/s</th>
            <th className="px-2 py-2 font-semibold w-24">Courier</th>
            <th className="px-2 py-2 font-semibold w-28">Status</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {visible.map(row => (
            <tr
              key={row.page}
              className={cn(!row.complete && "bg-amber-50/80")}
            >
              <td className="px-2 py-1.5 tabular-nums text-muted-foreground">{row.page}</td>
              <td className="px-2 py-1.5 font-mono text-[11px] break-all">
                {row.tracking || <span className="text-amber-700">—</span>}
              </td>
              <td className="px-2 py-1.5">{row.name || <span className="text-amber-700">—</span>}</td>
              <td className="px-2 py-1.5 line-clamp-2" title={row.items}>
                {row.items || <span className="text-amber-700">—</span>}
              </td>
              <td className="px-2 py-1.5 text-muted-foreground">{row.courier}</td>
              <td className="px-2 py-1.5">
                {row.complete ? (
                  <span className="text-green-700 font-medium">OK</span>
                ) : (
                  <span className="text-amber-800 font-medium">
                    Missing {missingLabel(row.missing_fields)}
                  </span>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function FileReviewPanel({
  file,
  showIncompleteOnly,
}: {
  file: PdfToExcelFileResult;
  showIncompleteOnly: boolean;
}) {
  return (
    <div className="space-y-2">
      <div className="flex flex-wrap items-center gap-2">
        <span className="text-sm font-semibold text-foreground truncate max-w-[240px]" title={file.pdf_name}>
          {file.pdf_name}
        </span>
        <span className="text-[10px] px-2 py-0.5 rounded-full bg-muted text-muted-foreground">
          {file.total} pages
        </span>
        <span className="text-[10px] px-2 py-0.5 rounded-full bg-green-50 text-green-800 border border-green-200">
          {file.complete} complete
        </span>
        {file.missing > 0 && (
          <span className="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-900 border border-amber-200">
            {file.missing} incomplete
          </span>
        )}
      </div>
      <ReviewTable rows={file.rows} showIncompleteOnly={showIncompleteOnly} />
    </div>
  );
}

export function PdfToExcelPage() {
  const inputRef = useRef<HTMLInputElement>(null);
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [progressLabel, setProgressLabel] = useState<string | null>(null);
  const [extractResult, setExtractResult] = useState<PdfToExcelExtractResult | null>(null);
  const [activeFileIdx, setActiveFileIdx] = useState(0);
  const [showIncompleteOnly, setShowIncompleteOnly] = useState(false);
  const [reviewConfirmed, setReviewConfirmed] = useState(false);
  const [downloads, setDownloads] = useState<{ filename: string; download_token: string }[] | null>(null);
  const [dragOver, setDragOver] = useState(false);
  const [toast, setToast] = useState<string | null>(null);

  const totalIncomplete = useMemo(() => {
    if (!extractResult) return 0;
    return extractResult.files.reduce((s, f) => s + f.missing, 0);
  }, [extractResult]);

  const resetWorkflow = useCallback(() => {
    setExtractResult(null);
    setDownloads(null);
    setReviewConfirmed(false);
    setShowIncompleteOnly(false);
    setActiveFileIdx(0);
    setError(null);
  }, []);

  const handleExtract = async () => {
    if (pdfFiles.length === 0) return;
    const sizeErr = pdfToExcelUploadTooLarge(pdfFiles);
    if (sizeErr) {
      setError(sizeErr);
      return;
    }
    setBusy(true);
    setError(null);
    resetWorkflow();
    try {
      const result = await extractPdfToExcel(pdfFiles, (_done, _total, label) => {
        setProgressLabel(label);
      });
      setExtractResult(result);
      setActiveFileIdx(0);
      setToast("Extraction complete — review rows before download");
      window.setTimeout(() => setToast(null), 3000);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Extraction failed";
      setError(msg);
    } finally {
      setBusy(false);
      setProgressLabel(null);
    }
  };

  const handleConfirmDownload = async () => {
    if (!extractResult || !reviewConfirmed) return;
    setBusy(true);
    setError(null);
    try {
      const result = await confirmPdfToExcel(extractResult.job_token);
      setDownloads(result.downloads);
      setToast("Ready to download");
      window.setTimeout(() => setToast(null), 2500);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Confirm failed";
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  const addPdfs = (list: FileList | null) => {
    if (!list?.length) return;
    const next = [...pdfFiles];
    for (let i = 0; i < list.length; i++) {
      const f = list[i];
      if (f.name.toLowerCase().endsWith(".pdf")) next.push(f);
    }
    setPdfFiles(next);
    if (extractResult) resetWorkflow();
  };

  const activeFile = extractResult?.files[activeFileIdx] ?? null;

  return (
    <PageLayout
      title="PDF → Excel"
      subtitle="Extract Shopee waybill PDFs into iPYX order sheets when workers send PDFs without Excel"
    >
      <OrderRemarksToast message={toast} />
      <div className="max-w-5xl mx-auto space-y-6">
        {error && (
          <div className="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
            <p>{error}</p>
          </div>
        )}

        {!extractResult && (
          <div
            className={cn(
              "bg-card rounded-xl border-2 border-dashed p-8 text-center transition-colors",
              dragOver ? "border-[#C8900A] bg-[#C8900A]/5" : "border-border",
              busy && "opacity-60 pointer-events-none",
            )}
            onDragEnter={e => {
              e.preventDefault();
              if (!busy) setDragOver(true);
            }}
            onDragOver={e => {
              e.preventDefault();
              if (!busy) setDragOver(true);
            }}
            onDragLeave={() => setDragOver(false)}
            onDrop={e => {
              e.preventDefault();
              setDragOver(false);
              if (!busy) addPdfs(e.dataTransfer.files);
            }}
          >
            <input
              ref={inputRef}
              type="file"
              accept=".pdf,application/pdf"
              multiple
              className="hidden"
              disabled={busy}
              onChange={e => {
                addPdfs(e.target.files);
                e.target.value = "";
              }}
            />
            <FileText size={36} className="mx-auto text-muted-foreground mb-3" />
            <p className="text-sm font-semibold">Upload waybill PDFs</p>
            <p className="text-xs text-muted-foreground mt-1 mb-4 max-w-md mx-auto">
              One Excel file is generated per PDF. Tracking, receiver name, and item/s are read from
              the text layer (OCR fallback when needed). Order No. and Note stay blank.
            </p>
            <button
              type="button"
              onClick={() => inputRef.current?.click()}
              className="text-xs font-semibold px-4 py-2 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A]"
            >
              Choose PDF files
            </button>
            {pdfFiles.length > 0 && (
              <ul className="mt-4 text-left max-w-sm mx-auto space-y-1 max-h-28 overflow-y-auto">
                {pdfFiles.map((f, i) => (
                  <li key={`${f.name}-${i}`} className="flex justify-between gap-2 text-xs">
                    <span className="truncate">{f.name}</span>
                    <button
                      type="button"
                      className="text-muted-foreground hover:text-red-600 flex-shrink-0"
                      onClick={() => {
                        setPdfFiles(pdfFiles.filter((_, j) => j !== i));
                        resetWorkflow();
                      }}
                    >
                      Remove
                    </button>
                  </li>
                ))}
              </ul>
            )}
            <button
              type="button"
              disabled={busy || pdfFiles.length === 0}
              onClick={() => void handleExtract()}
              className={cn(
                "mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold",
                pdfFiles.length > 0 && !busy
                  ? "bg-[#C8900A] text-white hover:bg-[#b07f09]"
                  : "bg-muted text-muted-foreground cursor-not-allowed",
              )}
            >
              {busy ? <Loader2 size={16} className="animate-spin" /> : <FileSpreadsheet size={16} />}
              {busy ? (progressLabel ?? "Extracting…") : "Extract to Excel"}
            </button>
          </div>
        )}

        {extractResult && (
          <OrderRemarksSectionEnter index={0} className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-sm font-semibold">Review extraction</h2>
              <button
                type="button"
                disabled={busy}
                onClick={() => {
                  setPdfFiles([]);
                  resetWorkflow();
                }}
                className="inline-flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
              >
                <RotateCcw size={12} />
                Start over
              </button>
            </div>

            {totalIncomplete > 0 && (
              <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                {totalIncomplete} incomplete row(s) across this batch. Incomplete rows may not stamp
                correctly in Waybill Stamper.
              </p>
            )}

            <label className="flex items-center gap-2 text-xs cursor-pointer select-none">
              <input
                type="checkbox"
                checked={showIncompleteOnly}
                onChange={e => setShowIncompleteOnly(e.target.checked)}
                className="rounded border-border"
              />
              Show incomplete rows only
            </label>

            {extractResult.files.length > 1 && (
              <div className="flex flex-wrap gap-1 border-b border-border pb-2">
                {extractResult.files.map((f, idx) => (
                  <button
                    key={f.pdf_name}
                    type="button"
                    onClick={() => setActiveFileIdx(idx)}
                    className={cn(
                      "text-xs px-3 py-1.5 rounded-lg border transition-colors",
                      idx === activeFileIdx
                        ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a6500] font-semibold"
                        : "border-border text-muted-foreground hover:border-[#C8900A]/50",
                    )}
                  >
                    {f.pdf_name}
                    {f.missing > 0 ? ` (${f.missing}?)` : ""}
                  </button>
                ))}
              </div>
            )}

            {activeFile && <FileReviewPanel file={activeFile} showIncompleteOnly={showIncompleteOnly} />}

            {!downloads ? (
              <div className="border-t border-border pt-4 space-y-3">
                <label className="flex items-start gap-2 text-xs cursor-pointer select-none">
                  <input
                    type="checkbox"
                    checked={reviewConfirmed}
                    onChange={e => setReviewConfirmed(e.target.checked)}
                    className="rounded border-border mt-0.5"
                  />
                  <span>
                    I reviewed the extraction{totalIncomplete > 0 ? " including incomplete rows" : ""}{" "}
                    and accept this export.
                  </span>
                </label>
                <button
                  type="button"
                  disabled={busy || !reviewConfirmed}
                  onClick={() => void handleConfirmDownload()}
                  className={cn(
                    "inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold",
                    reviewConfirmed && !busy
                      ? "bg-[#C8900A] text-white hover:bg-[#b07f09]"
                      : "bg-muted text-muted-foreground cursor-not-allowed",
                  )}
                >
                  {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
                  Confirm &amp; enable download
                </button>
              </div>
            ) : (
              <div className="border-t border-border pt-4 space-y-3">
                <p className="text-sm font-semibold text-green-800 flex items-center gap-2">
                  <CheckCircle2 size={18} />
                  Downloads ready
                </p>
                <div className="flex flex-col gap-2">
                  {downloads.map(d => (
                    <button
                      key={d.download_token}
                      type="button"
                      onClick={() => triggerPdfToExcelDownload(d.download_token, d.filename)}
                      className="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-4 py-2.5 rounded-lg bg-[#C8900A] text-white text-sm font-semibold hover:bg-[#b07f09]"
                    >
                      <Download size={16} />
                      Download {d.filename}
                    </button>
                  ))}
                </div>
                <p className="text-xs text-muted-foreground">
                  Next: upload the same PDF(s) and downloaded Excel file(s) in{" "}
                  <Link to="/waybill/stamper" className="text-[#C8900A] font-semibold hover:underline">
                    Waybill Stamper
                  </Link>
                  .
                </p>
              </div>
            )}

            {extractResult.log_excerpt && (
              <details className="text-xs text-muted-foreground">
                <summary className="cursor-pointer hover:text-foreground">Processing log</summary>
                <pre className="mt-2 p-3 rounded-lg bg-muted/50 overflow-x-auto whitespace-pre-wrap max-h-40">
                  {extractResult.log_excerpt}
                </pre>
              </details>
            )}
          </OrderRemarksSectionEnter>
        )}

        <div className="bg-card rounded-xl border border-border p-4 text-xs text-muted-foreground space-y-2">
          <p className="font-semibold text-foreground text-sm">Workflow</p>
          <ol className="list-decimal list-inside space-y-1">
            <li>Upload Shopee waybill PDF (no Excel from worker)</li>
            <li>Review extracted rows, then confirm download</li>
            <li>Use Excel + PDF in Waybill Stamper to stamp item lines</li>
          </ol>
          <p>
            <strong className="text-foreground">Layout:</strong> iPYX columns A–H (row # in A, blank F spacer,
            tracking in H). <strong className="text-foreground">Filled:</strong> Name, Item/s, Courier,
            Tracking Number. <strong className="text-foreground">Left blank:</strong> Order No., Note, column F.
          </p>
        </div>
      </div>
    </PageLayout>
  );
}
