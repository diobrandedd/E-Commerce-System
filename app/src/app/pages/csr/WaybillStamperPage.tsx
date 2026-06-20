import { useCallback, useRef, useState } from "react";
import { Link } from "react-router";
import {
  AlertCircle,
  CheckCircle2,
  ChevronDown,
  Download,
  FileSpreadsheet,
  FileText,
  Loader2,
  Package,
  X,
} from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import {
  stampWaybillsFromFiles,
  triggerWaybillDownload,
  waybillUploadTooLarge,
  type WaybillStampResult,
} from "@/app/lib/waybillApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

function FileZone({
  label,
  hint,
  accept,
  icon: Icon,
  files,
  onFiles,
  disabled,
}: {
  label: string;
  hint: string;
  accept: string;
  icon: React.ElementType;
  files: File[];
  onFiles: (files: File[]) => void;
  disabled?: boolean;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);

  const addFiles = (list: FileList | null) => {
    if (!list?.length) return;
    const next = [...files];
    for (let i = 0; i < list.length; i++) {
      next.push(list[i]);
    }
    onFiles(next);
  };

  return (
    <div
      className={cn(
        "bg-card rounded-xl border-2 border-dashed p-6 text-center transition-colors",
        dragOver ? "border-[#C8900A] bg-[#C8900A]/5" : "border-border",
        disabled && "opacity-60 pointer-events-none",
      )}
      onDragOver={e => {
        e.preventDefault();
        setDragOver(true);
      }}
      onDragLeave={() => setDragOver(false)}
      onDrop={e => {
        e.preventDefault();
        setDragOver(false);
        addFiles(e.dataTransfer.files);
      }}
    >
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple
        className="hidden"
        disabled={disabled}
        onChange={e => {
          addFiles(e.target.files);
          e.target.value = "";
        }}
      />
      <Icon size={32} className="mx-auto text-muted-foreground mb-3" />
      <p className="text-sm font-semibold text-foreground">{label}</p>
      <p className="text-xs text-muted-foreground mt-1 mb-4">{hint}</p>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className="text-xs font-semibold px-4 py-2 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A] transition-colors"
      >
        Choose files
      </button>
      {files.length > 0 && (
        <ul className="mt-4 text-left space-y-1 max-h-28 overflow-y-auto">
          {files.map((f, idx) => (
            <li key={`${f.name}-${idx}`} className="flex items-center justify-between gap-2 text-xs">
              <span className="truncate text-foreground">{f.name}</span>
              <button
                type="button"
                className="text-muted-foreground hover:text-red-600 flex-shrink-0"
                onClick={() => onFiles(files.filter((_, i) => i !== idx))}
                aria-label={`Remove ${f.name}`}
              >
                <X size={14} />
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export function WaybillStamperPage() {
  const [pdfFiles, setPdfFiles] = useState<File[]>([]);
  const [excelFiles, setExcelFiles] = useState<File[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<WaybillStampResult | null>(null);
  const [showLog, setShowLog] = useState(false);
  const [showReqs, setShowReqs] = useState(false);
  const [progress, setProgress] = useState<{ done: number; total: number; label: string } | null>(null);

  const canStamp = pdfFiles.length > 0 && excelFiles.length > 0 && !busy;

  const runStamp = useCallback(async () => {
    setBusy(true);
    setError(null);
    setResult(null);
    setProgress(null);
    const sizeErr = waybillUploadTooLarge([...pdfFiles, ...excelFiles]);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const data = await stampWaybillsFromFiles(pdfFiles, excelFiles, (done, total, label) => {
        setProgress({ done, total, label });
      });
      setResult(data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Stamping failed.");
    } finally {
      setBusy(false);
      setProgress(null);
    }
  }, [pdfFiles, excelFiles]);

  const reset = () => {
    setPdfFiles([]);
    setExcelFiles([]);
    setResult(null);
    setError(null);
    setShowLog(false);
  };

  return (
    <PageLayout
      title="Waybill Stamper"
      subtitle="Match courier waybills to Excel orders and stamp item lines for packers"
    >
      <div className="space-y-6">
        <div className="bg-card rounded-xl border border-border p-4 text-sm text-muted-foreground">
          <p>
            Upload PDF waybills (TikTok, Lazada, Shopee) and iPYX Excel order sheets. The system reads each
            tracking number, finds the matching order, and prints the item line on the waybill. Unmatched pages
            are flagged in red on the output PDF.
          </p>
          <p className="mt-2">
            No Excel from the worker?{" "}
            <Link to="/waybill/pdf-to-excel" className="text-[#C8900A] font-semibold hover:underline">
              PDF → Excel
            </Link>{" "}
            builds an order sheet from the waybill PDF first.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FileZone
            label="PDF waybills"
            hint="One or more courier label PDFs"
            accept=".pdf,application/pdf"
            icon={FileText}
            files={pdfFiles}
            onFiles={setPdfFiles}
            disabled={busy}
          />
          <FileZone
            label="Excel order sheets"
            hint="iPYX exports (.xlsx / .xls)"
            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
            icon={FileSpreadsheet}
            files={excelFiles}
            onFiles={setExcelFiles}
            disabled={busy}
          />
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            disabled={!canStamp}
            onClick={() => void runStamp()}
            className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-white disabled:opacity-50"
            style={{ background: "#C8900A" }}
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : <Package size={16} />}
            {busy
              ? progress
                ? progress.done < progress.total
                  ? `Uploading ${progress.done + 1}/${progress.total}…`
                  : "Processing…"
                : "Stamping…"
              : "Stamp waybills"}
          </button>
          {(pdfFiles.length > 0 || excelFiles.length > 0 || result) && (
            <button
              type="button"
              onClick={reset}
              disabled={busy}
              className="text-sm font-medium px-4 py-2.5 rounded-lg border border-border hover:bg-secondary disabled:opacity-50"
            >
              Clear
            </button>
          )}
        </div>

        {busy && (
          <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900 flex items-start gap-2">
            <Loader2 size={18} className="animate-spin flex-shrink-0 mt-0.5" />
            <p>
              {progress && progress.done < progress.total ? (
                <>
                  Uploading file {progress.done + 1} of {progress.total}: <strong>{progress.label}</strong>
                </>
              ) : (
                <>
                  Processing… OCR pages can take a few seconds each. A large batch may take several minutes —
                  please keep this tab open.
                </>
              )}
            </p>
          </div>
        )}

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800 flex items-start gap-2">
            <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
            <p>{error}</p>
          </div>
        )}

        {result && (
          <div className="bg-card rounded-xl border border-border overflow-hidden">
            <div className="px-5 py-4 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div className="flex items-center gap-2 text-green-700">
                <CheckCircle2 size={20} />
                <span className="font-semibold text-foreground">Stamping complete</span>
              </div>
              <button
                type="button"
                onClick={() => triggerWaybillDownload(result.download_token, result.download_filename)}
                className="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white"
                style={{ background: "#C8900A" }}
              >
                <Download size={16} /> Download stamped PDF
              </button>
            </div>
            <div className="grid grid-cols-3 divide-x divide-border border-b border-border">
              <div className="px-5 py-4 text-center">
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Total pages</p>
                <p className="text-2xl font-bold text-foreground mt-1">{result.total}</p>
              </div>
              <div className="px-5 py-4 text-center">
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Matched</p>
                <p className="text-2xl font-bold text-green-600 mt-1">{result.matched}</p>
              </div>
              <div className="px-5 py-4 text-center">
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Unmatched</p>
                <p className="text-2xl font-bold text-red-600 mt-1">{result.unmatched}</p>
              </div>
            </div>
            {result.unmatched_list.length > 0 && (
              <div className="px-5 py-4">
                <p className="text-xs font-semibold text-red-700 uppercase tracking-wide mb-2">
                  Unmatched tracking numbers
                </p>
                <ul className="text-xs text-red-800 space-y-1 max-h-40 overflow-y-auto font-mono">
                  {result.unmatched_list.map((line, i) => (
                    <li key={i}>{line}</li>
                  ))}
                </ul>
              </div>
            )}
            {result.log_excerpt && (
              <div className="border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowLog(v => !v)}
                  className="w-full flex items-center justify-between px-5 py-3 text-xs font-semibold text-muted-foreground hover:bg-secondary/40"
                >
                  Processing log
                  <ChevronDown size={14} className={cn("transition-transform", showLog && "rotate-180")} />
                </button>
                {showLog && (
                  <pre className="px-5 pb-4 text-[10px] text-muted-foreground overflow-x-auto whitespace-pre-wrap max-h-48 overflow-y-auto">
                    {result.log_excerpt}
                  </pre>
                )}
              </div>
            )}
          </div>
        )}

        <div className="border border-border rounded-xl overflow-hidden">
          <button
            type="button"
            onClick={() => setShowReqs(v => !v)}
            className="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-muted-foreground hover:bg-secondary/40"
          >
            Server requirements (Python, Tesseract, Poppler)
            <ChevronDown size={14} className={cn("transition-transform", showReqs && "rotate-180")} />
          </button>
          {showReqs && (
            <div className="px-4 pb-4 text-xs text-muted-foreground space-y-2 border-t border-border">
              <p>Stamping runs on the server via Python. Your admin needs:</p>
              <ul className="list-disc pl-5 space-y-1">
                <li>Python 3 with pdfplumber, openpyxl, PyMuPDF, pytesseract, pdf2image, Pillow</li>
                <li>Tesseract OCR (for image-only Lazada pages)</li>
                <li>Poppler (for pdf2image)</li>
                <li>PHP shell_exec enabled in php.ini</li>
              </ul>
              <p>See <code className="text-foreground">waybill-stamper/SETUP.md</code> in the project folder.</p>
            </div>
          )}
        </div>
      </div>
    </PageLayout>
  );
}
