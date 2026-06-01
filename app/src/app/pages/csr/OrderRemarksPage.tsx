import { useCallback, useMemo, useRef, useState } from "react";
import { motion, useReducedMotion } from "motion/react";
import {
  AlertCircle,
  CheckCircle2,
  ChevronDown,
  Download,
  FileSpreadsheet,
  Loader2,
  RefreshCw,
} from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AmbiguousOrderCarousel } from "@/app/components/csr/AmbiguousOrderCarousel";
import {
  OrderRemarksAnimatedCount,
  OrderRemarksCollapsible,
  OrderRemarksSectionEnter,
  OrderRemarksToast,
  remarksEase,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";
import { formatPreviewLine } from "@/app/lib/orderRowDisplay";
import {
  categorizeRow,
  finalRemarkForRow,
  isRowFullyResolved,
  parseRemarks,
  type ParsedRemark,
  type RowResolution,
} from "@/app/lib/orderRemarksParse";
import {
  exportOrderRemarks,
  orderRemarksUploadTooLarge,
  parseOrderRemarks,
  stashOrderRemarksFile,
  triggerOrderRemarksDownload,
  type OrderRemarksLayout,
  type ParseOrderRemarksResult,
} from "@/app/lib/orderRemarksApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

interface RowState {
  order: { idx: number; receiver: string; phone: string; remarks: string };
  parsed: ParsedRemark;
  category: ReturnType<typeof categorizeRow>;
}

export function OrderRemarksPage() {
  const inputRef = useRef<HTMLInputElement>(null);
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [parseResult, setParseResult] = useState<ParseOrderRemarksResult | null>(null);
  const [profileOverride, setProfileOverride] = useState<string>("");
  const [resolutions, setResolutions] = useState<Record<number, RowResolution>>({});
  const [showLimits, setShowLimits] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [showCompleted, setShowCompleted] = useState(false);
  const [toast, setToast] = useState<string | null>(null);

  const rows: RowState[] = useMemo(() => {
    if (!parseResult) return [];
    return parseResult.orders.map(order => {
      const parsed = parseRemarks(order.remarks);
      return { order, parsed, category: categorizeRow(order.remarks, parsed) };
    });
  }, [parseResult]);

  const stats = useMemo(() => {
    let auto = 0;
    let needInput = 0;
    let passthrough = 0;
    let skipped = 0;
    for (const r of rows) {
      if (r.category === "ambiguous") needInput++;
      else if (r.category === "auto") auto++;
      else if (r.category === "passthrough") passthrough++;
      else skipped++;
    }
    return { auto, needInput, passthrough, skipped, total: rows.length };
  }, [rows]);

  const ambiguousRows = useMemo(
    () => rows.filter(r => r.category === "ambiguous"),
    [rows],
  );

  const allInputResolved = ambiguousRows.every(r =>
    isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx]),
  );

  const canExport =
    parseResult !== null && (ambiguousRows.length === 0 || allInputResolved);

  const runParse = useCallback(async (uploadToken: string, profileId?: string) => {
    const result = await parseOrderRemarks(uploadToken, profileId || undefined);
    setParseResult(result);
    setProfileOverride(result.layout.profileId);
    setResolutions({});
    setShowCompleted(false);
  }, []);

  const pickExcelFromFileList = (list: FileList | null): File | null => {
    if (!list?.length) return null;
    for (let i = 0; i < list.length; i++) {
      const f = list[i];
      const ext = f.name.split(".").pop()?.toLowerCase() ?? "";
      if (ext === "xlsx" || ext === "xls") return f;
    }
    return null;
  };

  const handleUpload = async (selected: File) => {
    const sizeErr = orderRemarksUploadTooLarge(selected);
    if (sizeErr) {
      setError(sizeErr);
      return;
    }
    setFile(selected);
    setError(null);
    setParseResult(null);
    setResolutions({});
    setShowCompleted(false);
    setBusy(true);
    try {
      const token = await stashOrderRemarksFile(selected);
      await runParse(token);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Upload failed";
      setError(msg);
      setParseResult(null);
    } finally {
      setBusy(false);
    }
  };

  const handleReparse = async () => {
    if (!parseResult?.token) return;
    setBusy(true);
    setError(null);
    try {
      await runParse(parseResult.token, profileOverride || undefined);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Parse failed";
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  const handleExport = async () => {
    if (!parseResult || !canExport) return;
    setBusy(true);
    setError(null);
    try {
      const remarks = parseResult.orders.map(order => {
        const parsed = parseRemarks(order.remarks);
        return finalRemarkForRow(order.remarks, parsed, resolutions[order.idx]);
      });
      const result = await exportOrderRemarks(
        parseResult.token,
        remarks,
        parseResult.layout,
      );
      triggerOrderRemarksDownload(result.download_token, result.download_filename);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Export failed";
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  const layout: OrderRemarksLayout | null = parseResult?.layout ?? null;
  const pendingInput = ambiguousRows.filter(
    r => !isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx]),
  ).length;

  const reduceMotion = useReducedMotion();

  return (
    <PageLayout
      title="Order Remarks"
      subtitle="Clean marketplace remarks in courier export Excel (JNT, SPX, Flash, and similar formats)."
      actions={
        parseResult ? (
          <button
            type="button"
            disabled={busy || !canExport}
            onClick={handleExport}
            className={cn(
              "inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors",
              canExport
                ? "bg-[#C8900A] text-white hover:bg-[#b07f09]"
                : "bg-muted text-muted-foreground cursor-not-allowed",
            )}
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : <Download size={16} />}
            Export Excel
          </button>
        ) : null
      }
    >
      <OrderRemarksToast message={toast} />
      <div className="max-w-5xl mx-auto space-y-6">
        {error && (
          <motion.div
            initial={reduceMotion ? false : { opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            className="flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm"
          >
            <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
            <p>{error}</p>
          </motion.div>
        )}

        <motion.div
          animate={
            reduceMotion
              ? undefined
              : {
                  scale: dragOver ? 1.01 : 1,
                  borderColor: dragOver ? "#C8900A" : "var(--border)",
                }
          }
          transition={remarksSpring}
          className={cn(
            "bg-card rounded-xl border-2 border-dashed p-8 text-center transition-colors",
            dragOver ? "border-[#C8900A] bg-[#C8900A]/5" : "border-border",
            busy && "opacity-60 pointer-events-none",
          )}
          onDragEnter={e => {
            e.preventDefault();
            e.stopPropagation();
            if (!busy) setDragOver(true);
          }}
          onDragOver={e => {
            e.preventDefault();
            e.stopPropagation();
            if (!busy) setDragOver(true);
          }}
          onDragLeave={() => setDragOver(false)}
          onDrop={e => {
            e.preventDefault();
            e.stopPropagation();
            setDragOver(false);
            if (busy) return;
            const picked = pickExcelFromFileList(e.dataTransfer.files);
            if (picked) {
              void handleUpload(picked);
            } else if (e.dataTransfer.files.length > 0) {
              setError("Please drop an Excel file (.xlsx or .xls).");
            }
          }}
        >
          <input
            ref={inputRef}
            type="file"
            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
            className="hidden"
            disabled={busy}
            onChange={e => {
              const f = e.target.files?.[0];
              if (f) void handleUpload(f);
              e.target.value = "";
            }}
          />
          <FileSpreadsheet size={36} className="mx-auto text-muted-foreground mb-3" />
          <p className="text-sm font-semibold">Upload courier order Excel</p>
          <p className="text-xs text-muted-foreground mt-1 mb-4">
            JNT, SPX, Flash, or similar — only the remarks column is updated on export.
            <br />
            Drag and drop a file here, or choose one below.
          </p>
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            className="text-xs font-semibold px-4 py-2 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A]"
          >
            {file ? "Choose another file" : "Choose .xlsx file"}
          </button>
          {file && <p className="text-xs text-muted-foreground mt-3">{file.name}</p>}
          {busy && !parseResult && (
            <p className="text-xs text-[#C8900A] mt-3 flex items-center justify-center gap-2">
              <Loader2 size={14} className="animate-spin" />
              Detecting format and parsing rows…
            </p>
          )}
        </motion.div>

        {layout && parseResult && (
          <OrderRemarksSectionEnter index={0}>
          <div className="bg-card rounded-xl border border-border p-4 space-y-4">
            <div className="flex flex-wrap items-center gap-3">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#C8900A]/15 text-[#8a6500] text-xs font-semibold">
                {layout.profileLabel}
              </span>
              <span className="text-xs text-muted-foreground">
                Sheet: <strong className="text-foreground">{layout.sheetName}</strong>
                {" · "}
                Remarks col: <strong className="text-foreground">{layout.remarksColumnLetter ?? "—"}</strong>
                {" · "}
                {stats.total} rows
              </span>
              <div className="ml-auto flex items-center gap-2">
                <label className="text-xs text-muted-foreground" htmlFor="profile-override">
                  Format:
                </label>
                <select
                  id="profile-override"
                  value={profileOverride}
                  disabled={busy}
                  onChange={e => setProfileOverride(e.target.value)}
                  className="text-xs border border-border rounded-lg px-2 py-1.5 bg-background"
                >
                  {parseResult.profiles.map(p => (
                    <option key={p.id} value={p.id}>
                      {p.label}
                    </option>
                  ))}
                </select>
                <button
                  type="button"
                  disabled={busy}
                  onClick={() => void handleReparse()}
                  className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1.5 rounded-lg border border-border hover:border-[#C8900A]"
                >
                  <RefreshCw size={12} className={busy ? "animate-spin" : ""} />
                  Re-parse
                </button>
              </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
              <StatPill label="Auto" value={stats.auto} tone="green" />
              <StatPill label="Need input" value={stats.needInput} tone="amber" />
              <StatPill label="Unchanged" value={stats.passthrough} tone="muted" />
              <StatPill label="Skipped" value={stats.skipped} tone="muted" />
            </div>

            {parseResult.sampleRows.length > 0 && (
              <div className="text-xs text-muted-foreground border-t border-border pt-3">
                <p className="font-semibold text-foreground mb-1">Preview (first rows)</p>
                <ul className="space-y-1">
                  {parseResult.sampleRows.map(row => {
                    const parsed = parseRemarks(row.remarks);
                    return (
                      <li key={row.idx} className="truncate">
                        <span className="font-medium text-foreground">
                          {formatPreviewLine(row, parsed)}
                        </span>
                        {" — "}
                        {(row.remarks || "").slice(0, 60)}
                        {(row.remarks?.length ?? 0) > 60 ? "…" : ""}
                      </li>
                    );
                  })}
                </ul>
              </div>
            )}
          </div>
          </OrderRemarksSectionEnter>
        )}

        {ambiguousRows.length > 0 && (
          <OrderRemarksSectionEnter index={1} className="space-y-3">
            <h2 className="text-sm font-semibold text-foreground">
              Orders needing input (
              <OrderRemarksAnimatedCount value={pendingInput} className="tabular-nums" /> remaining)
            </h2>
            <AmbiguousOrderCarousel
              rows={ambiguousRows}
              resolutions={resolutions}
              showCompleted={showCompleted}
              onShowCompletedChange={setShowCompleted}
              onResolve={(idx, resolution) => {
                setResolutions(prev => ({ ...prev, [idx]: resolution }));
              }}
              onCardCompleted={remaining => {
                setToast(
                  remaining > 0
                    ? `Done — ${remaining} remaining`
                    : "Done — all orders resolved",
                );
                window.setTimeout(() => setToast(null), 2800);
              }}
              disabled={busy}
            />
          </OrderRemarksSectionEnter>
        )}

        {parseResult && ambiguousRows.length === 0 && stats.auto > 0 && (
          <OrderRemarksSectionEnter index={1}>
          <motion.div
            initial={reduceMotion ? false : { opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={remarksSpring}
            className="flex items-center gap-2 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm"
          >
            <CheckCircle2 size={18} />
            All rows resolved automatically. You can export now.
          </motion.div>
          </OrderRemarksSectionEnter>
        )}

        {parseResult && !canExport && ambiguousRows.length > 0 && (
          <p className="text-xs text-amber-700 text-center">
            Export is disabled until every card above is completed (including free 350ml when applicable).
          </p>
        )}

        <div className="bg-card rounded-xl border border-border overflow-hidden">
          <button
            type="button"
            className="w-full flex items-center justify-between px-4 py-3 text-sm font-semibold hover:bg-muted/50"
            onClick={() => setShowLimits(v => !v)}
          >
            Limitations &amp; supported formats
            <ChevronDown size={16} className={cn("transition-transform", showLimits && "rotate-180")} />
          </button>
          <OrderRemarksCollapsible open={showLimits}>
            <div className="px-4 pb-4 text-xs text-muted-foreground space-y-3 border-t border-border pt-3">
              <p>
                <strong className="text-foreground">Supported carriers (v1):</strong> J&amp;T, SPX, Flash, and
                generic Receiver + Remarks exports.
              </p>
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="border-b border-border">
                    <th className="py-1 pr-2 font-semibold text-foreground">Scenario</th>
                    <th className="py-1 font-semibold text-foreground">Behavior</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  <tr>
                    <td className="py-1.5 pr-2">3 L Honey (LAD) in remark</td>
                    <td className="py-1.5">3L auto-resolved; 350ml pick only if Set B / FREE promo</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">Set B + textarea (e.g. 2 Dark &amp; 1 Amber)</td>
                    <td className="py-1.5">3L auto (ADD, etc.); card asks for free 350ml only (L / A / D)</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">2L all Amber in text</td>
                    <td className="py-1.5">Auto AA — no card</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">Export format</td>
                    <td className="py-1.5">e.g. 1 x 3L Honey (LLA) + 350ml (A)</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">Receiver vs Place</td>
                    <td className="py-1.5">Receiver = column A; Place = City column (e.g. Mayamot)</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">Completed cards</td>
                    <td className="py-1.5">Hidden from queue; use Show completed to review</td>
                  </tr>
                  <tr>
                    <td className="py-1.5 pr-2">Non-honey / blank receiver</td>
                    <td className="py-1.5">Pass-through or skipped</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </OrderRemarksCollapsible>
        </div>
      </div>
    </PageLayout>
  );
}

function StatPill({
  label,
  value,
  tone,
}: {
  label: string;
  value: number;
  tone: "green" | "amber" | "muted";
}) {
  const colors = {
    green: "bg-green-50 text-green-800 border-green-200",
    amber: "bg-amber-50 text-amber-900 border-amber-200",
    muted: "bg-muted/50 text-muted-foreground border-border",
  };
  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={remarksEase}
      className={cn("rounded-lg border px-3 py-2", colors[tone])}
    >
      <p className="text-lg font-bold">
        <OrderRemarksAnimatedCount value={value} />
      </p>
      <p className="text-[10px] uppercase tracking-wide opacity-80">{label}</p>
    </motion.div>
  );
}
