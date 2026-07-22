import { useMemo, useState } from "react";
import { CheckCircle2, FileSpreadsheet, Loader2, ShieldAlert } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { FileZone } from "@/app/components/csr/FileZone";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";
import {
  historicalOrdersCommit,
  historicalOrdersPreview,
  historicalOrdersStashUpload,
  type ConflictAction,
  type HistoricalConflict,
  type HistoricalPipeline,
  type HistoricalPreviewResult,
  type HistoricalCommitResult,
} from "@/app/lib/historicalOrdersApi";

const PIPELINES: { value: HistoricalPipeline; label: string }[] = [
  { value: "vip", label: "VIP" },
  { value: "partner", label: "Partner" },
  { value: "shopee_spx", label: "Shopee / SPX" },
  { value: "tiktok", label: "TikTok" },
];

const MONTHS = [
  { value: 1, label: "January" },
  { value: 2, label: "February" },
  { value: 3, label: "March" },
  { value: 4, label: "April" },
  { value: 5, label: "May" },
  { value: 6, label: "June" },
  { value: 7, label: "July" },
  { value: 8, label: "August" },
  { value: 9, label: "September" },
  { value: 10, label: "October" },
  { value: 11, label: "November" },
  { value: 12, label: "December" },
];

function StatCard({
  label,
  value,
  tone = "default",
}: {
  label: string;
  value: number;
  tone?: "default" | "good" | "warn" | "bad";
}) {
  return (
    <div
      className={cn(
        "rounded-xl border px-4 py-3",
        tone === "good" && "border-green-200 bg-green-50",
        tone === "warn" && "border-amber-200 bg-amber-50",
        tone === "bad" && "border-red-200 bg-red-50",
        tone === "default" && "border-border bg-card",
      )}
    >
      <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
      <p className="text-2xl font-bold tabular-nums mt-1">{value.toLocaleString()}</p>
    </div>
  );
}

function FieldCell({
  label,
  value,
  highlight,
}: {
  label: string;
  value: string;
  highlight?: boolean;
}) {
  return (
    <div className={cn("rounded-lg px-2.5 py-2", highlight && "bg-amber-50 ring-1 ring-amber-200")}>
      <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{label}</p>
      <p className="text-xs mt-0.5 break-words whitespace-pre-wrap">{value || "—"}</p>
    </div>
  );
}

function ConflictCard({
  conflict,
  action,
  onAction,
}: {
  conflict: HistoricalConflict;
  action: ConflictAction;
  onAction: (a: ConflictAction) => void;
}) {
  const diff = new Set(conflict.diff_fields);
  const ex = conflict.existing;
  const up = conflict.uploaded;

  return (
    <div
      className={cn(
        "rounded-xl border p-4 space-y-3",
        ex.sensitive ? "border-amber-300 bg-amber-50/40" : "border-border bg-card",
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <p className="text-sm font-semibold font-mono">{conflict.tracking_number}</p>
          {ex.sensitive && (
            <p className="text-xs text-amber-800 mt-1 inline-flex items-center gap-1">
              <ShieldAlert size={14} />
              Live / RTS / packing photo — default Keep current
            </p>
          )}
        </div>
        <div className="flex gap-2">
          <FulfillmentPressable
            className={cn(
              "px-3 py-1.5 text-xs font-semibold border rounded-lg",
              action === "keep"
                ? "bg-muted border-foreground/20 text-foreground"
                : "bg-card border-border text-muted-foreground",
            )}
            onClick={() => onAction("keep")}
          >
            Keep current
          </FulfillmentPressable>
          <FulfillmentPressable
            className={cn(
              "px-3 py-1.5 text-xs font-semibold border rounded-lg",
              action === "use_uploaded"
                ? "bg-primary text-primary-foreground border-primary"
                : "bg-card border-border text-muted-foreground",
            )}
            onClick={() => onAction("use_uploaded")}
          >
            Use uploaded
          </FulfillmentPressable>
        </div>
      </div>

      <div className="grid gap-3 md:grid-cols-2">
        <div className="space-y-2">
          <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Current (DB)</p>
          <FieldCell label="Recipient" value={ex.recipient_name} highlight={diff.has("recipient_name")} />
          <FieldCell label="Order no" value={ex.order_no ?? ""} highlight={diff.has("order_no")} />
          <FieldCell label="Items" value={ex.items_raw} highlight={diff.has("items_raw")} />
          <FieldCell
            label="Status / ship date"
            value={`${ex.status}${ex.ship_date ? ` · ${ex.ship_date}` : ""}`}
            highlight={diff.has("status") || diff.has("ship_date")}
          />
          <FieldCell
            label="Courier / pipeline"
            value={`${ex.courier} · ${ex.source_pipeline}`}
            highlight={diff.has("courier") || diff.has("source_pipeline")}
          />
        </div>
        <div className="space-y-2">
          <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Uploaded</p>
          <FieldCell label="Recipient" value={up.recipient_name} highlight={diff.has("recipient_name")} />
          <FieldCell label="Order no" value={up.order_no ?? ""} highlight={diff.has("order_no")} />
          <FieldCell label="Items" value={up.items_raw} highlight={diff.has("items_raw")} />
          <FieldCell
            label="Status / ship date"
            value={`${up.status} · ${up.ship_date}`}
            highlight={diff.has("status") || diff.has("ship_date")}
          />
          <FieldCell
            label="Courier / pipeline"
            value={`${up.courier} · ${up.source_pipeline}`}
            highlight={diff.has("courier") || diff.has("source_pipeline")}
          />
          {!up.parse_ok && up.parse_errors.length > 0 && (
            <p className="text-[11px] text-amber-700">Parse soft-fail: {up.parse_errors[0]} (raw remarks kept)</p>
          )}
        </div>
      </div>
    </div>
  );
}

export function HistoricalOrdersImportPage() {
  const now = new Date();
  const [pipeline, setPipeline] = useState<HistoricalPipeline>("vip");
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(Math.max(1, now.getMonth())); // prior month default-ish; JS month 0-based
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [preview, setPreview] = useState<HistoricalPreviewResult | null>(null);
  const [decisions, setDecisions] = useState<Record<string, ConflictAction>>({});
  const [commitResult, setCommitResult] = useState<HistoricalCommitResult | null>(null);

  const yearOptions = useMemo(() => {
    const y = now.getFullYear();
    return [y, y - 1, y - 2];
  }, [now]);

  const runPreview = async () => {
    if (!file) {
      setError("Upload a Snappy Excel with Tracking number first.");
      return;
    }
    setBusy(true);
    setError(null);
    setPreview(null);
    setCommitResult(null);
    setDecisions({});
    try {
      const token = await historicalOrdersStashUpload(file);
      const data = await historicalOrdersPreview({
        token,
        pipeline,
        year,
        month,
      });
      setPreview(data);
      const next: Record<string, ConflictAction> = {};
      for (const c of data.conflicts) {
        next[c.row_key] = c.default_action;
      }
      setDecisions(next);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Preview failed.");
    } finally {
      setBusy(false);
    }
  };

  const setAllConflicts = (action: ConflictAction) => {
    if (!preview) return;
    const next: Record<string, ConflictAction> = {};
    for (const c of preview.conflicts) {
      next[c.row_key] = action;
    }
    setDecisions(next);
  };

  const runCommit = async () => {
    if (!preview) return;
    setBusy(true);
    setError(null);
    try {
      const result = await historicalOrdersCommit({
        preview_token: preview.preview_token,
        import_new: true,
        decisions: preview.conflicts.map(c => ({
          row_key: c.row_key,
          action: decisions[c.row_key] ?? c.default_action,
        })),
      });
      setCommitResult(result);
      setPreview(null);
      setFile(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Commit failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <PageLayout
      title="Import historical orders"
      subtitle="Backfill Snappy shipped-order Excel (with Tracking number) as already-shipped Online parcels. No inventory deduct. Conflicts show current vs uploaded before you commit."
    >
      <FulfillmentSectionEnter className="max-w-5xl space-y-6">
        <div className="grid gap-4 sm:grid-cols-3">
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Pipeline</span>
            <select
              value={pipeline}
              disabled={busy || !!preview}
              onChange={e => setPipeline(e.target.value as HistoricalPipeline)}
              className="w-full border border-border rounded-lg px-3 py-2.5 text-sm bg-card"
            >
              {PIPELINES.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
          </label>
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Month</span>
            <select
              value={month}
              disabled={busy || !!preview}
              onChange={e => setMonth(Number(e.target.value))}
              className="w-full border border-border rounded-lg px-3 py-2.5 text-sm bg-card"
            >
              {MONTHS.map(m => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
          </label>
          <label className="block space-y-1.5">
            <span className="text-xs font-medium text-muted-foreground">Year</span>
            <select
              value={year}
              disabled={busy || !!preview}
              onChange={e => setYear(Number(e.target.value))}
              className="w-full border border-border rounded-lg px-3 py-2.5 text-sm bg-card"
            >
              {yearOptions.map(y => (
                <option key={y} value={y}>
                  {y}
                </option>
              ))}
            </select>
          </label>
        </div>

        <p className="text-xs text-muted-foreground">
          Uses the Excel <span className="font-semibold text-foreground">Tracking number</span> column as-is (e.g.{" "}
          <span className="font-mono text-foreground">JT0012987721845</span>). Ship date = last day of the selected
          month. Status = shipped. Rows with no tracking number still import with a temporary placeholder so you can edit
          them later in Online parcels.
        </p>

        <FileZone
          label="Snappy shipped orders Excel"
          hint="Columns: Order ID, Tracking number, Recipient/Customer, Product name, Quantity…"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={file ? [file] : []}
          disabled={busy || !!preview}
          multiple={false}
          onFiles={fs => {
            setFile(fs[0] ?? null);
            setPreview(null);
            setCommitResult(null);
            setError(null);
          }}
        />

        {!preview && (
          <FulfillmentPressable
            className="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-primary-foreground bg-primary disabled:opacity-50"
            disabled={busy || !file}
            onClick={() => void runPreview()}
          >
            {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
            Preview import
          </FulfillmentPressable>
        )}

        <FulfillmentErrorBanner message={error} />

        {commitResult && (
          <AppSuccessNotice
            message={`Imported ${commitResult.month_label}: ${commitResult.inserted.toLocaleString()} inserted, ${commitResult.updated.toLocaleString()} overwritten, ${commitResult.kept.toLocaleString()} kept. Ship date ${commitResult.ship_date}.${
              commitResult.errors.length > 0
                ? ` ${commitResult.errors.length} row error(s) during commit.`
                : ""
            }`}
          />
        )}

        {preview && (
          <div className="space-y-5">
            <AppSuccessNotice
              message={`Preview for ${preview.month_label} · ship date ${preview.ship_date} · pipeline ${preview.pipeline}`}
            />

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <StatCard label="New (will insert)" value={preview.counts.new} tone="good" />
              <StatCard label="Conflicts" value={preview.counts.conflicts} tone="warn" />
              <StatCard label="Failed parse" value={preview.counts.failed} tone="bad" />
              <StatCard label="Excel rows" value={preview.counts.total_excel_rows} />
            </div>

            {preview.conflicts.length > 0 && (
              <div className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <h2 className="text-sm font-semibold">Conflicts — compare then choose</h2>
                  <div className="flex gap-2">
                    <FulfillmentPressable
                      className="px-3 py-1.5 text-xs font-semibold border border-border rounded-lg bg-card"
                      onClick={() => setAllConflicts("keep")}
                    >
                      Keep all current
                    </FulfillmentPressable>
                    <FulfillmentPressable
                      className="px-3 py-1.5 text-xs font-semibold border border-border rounded-lg bg-card"
                      onClick={() => setAllConflicts("use_uploaded")}
                    >
                      Use all uploaded
                    </FulfillmentPressable>
                  </div>
                </div>
                <div className="space-y-3 max-h-[28rem] overflow-y-auto pr-1">
                  {preview.conflicts.map(c => (
                    <ConflictCard
                      key={c.row_key}
                      conflict={c}
                      action={decisions[c.row_key] ?? c.default_action}
                      onAction={a => setDecisions(prev => ({ ...prev, [c.row_key]: a }))}
                    />
                  ))}
                </div>
              </div>
            )}

            {preview.new.length > 0 && (
              <div className="rounded-xl border border-border overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 bg-muted/40 border-b border-border">
                  New rows sample (first 15 of {preview.counts.new.toLocaleString()})
                </p>
                <div className="overflow-x-auto">
                  <table className="w-full text-xs">
                    <thead>
                      <tr className="text-left text-muted-foreground border-b border-border">
                        <th className="px-3 py-2">Tracking</th>
                        <th className="px-3 py-2">Recipient</th>
                        <th className="px-3 py-2">Order no</th>
                      </tr>
                    </thead>
                    <tbody>
                      {preview.new.slice(0, 15).map(r => (
                        <tr key={r.row_key} className="border-b border-border/60">
                          <td className="px-3 py-2 font-mono">
                            {r.tracking_number}
                            {r.tracking_missing ? (
                              <span className="ml-2 font-sans text-[11px] text-amber-700">(temporary)</span>
                            ) : null}
                          </td>
                          <td className="px-3 py-2">{r.recipient_name}</td>
                          <td className="px-3 py-2">{r.order_no}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {preview.failed.length > 0 && (
              <div className="rounded-xl border border-red-200 bg-red-50/40 overflow-hidden">
                <p className="text-xs font-semibold px-3 py-2 border-b border-red-200">
                  Failed rows ({preview.failed.length}) — skipped on commit
                </p>
                <ul className="max-h-40 overflow-y-auto text-xs divide-y divide-red-100">
                  {preview.failed.slice(0, 40).map(f => (
                    <li key={f.row_key} className="px-3 py-2">
                      <span className="font-mono">{f.parcel_ref || f.row_key}</span> — {f.error}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            <div className="flex flex-wrap gap-3">
              <FulfillmentPressable
                className="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-primary-foreground bg-primary disabled:opacity-50"
                disabled={busy}
                onClick={() => void runCommit()}
              >
                {busy ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
                Commit import
              </FulfillmentPressable>
              <FulfillmentPressable
                className="px-4 py-2.5 text-sm font-semibold border border-border rounded-lg bg-card"
                disabled={busy}
                onClick={() => {
                  setPreview(null);
                  setDecisions({});
                }}
              >
                Cancel preview
              </FulfillmentPressable>
            </div>
          </div>
        )}
      </FulfillmentSectionEnter>
    </PageLayout>
  );
}
