import { useCallback, useEffect, useId, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { ChevronDown, Loader2, Plus, Printer, Trash2 } from "lucide-react";
import {
  AppErrorBanner,
  AppPressable,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { ApiError } from "@/app/lib/api";
import { HONEY_BOTTLE_OPTIONS, HONEY_VARIANTS } from "@/app/lib/honey";
import { variantLabel } from "@/app/lib/posHoney";
import {
  rtsBarcodeLabelJobCodes,
  rtsBarcodeLabelJobCreate,
  rtsBarcodeLabelJobList,
  rtsBarcodeLabelMarkPrinted,
  rtsBarcodeLabelMeta,
  rtsBarcodeLabelPrintUrl,
  type RtsBarcodeLabelCode,
  type RtsBarcodeLabelDraftLine,
  type RtsBarcodeLabelJob,
  type RtsLabelHoneySize,
  type RtsLabelProductKey,
} from "@/app/lib/rtsBarcodeLabels";
import { cn } from "@/app/components/ui/utils";

const PRODUCT_OPTIONS: { value: RtsLabelProductKey; label: string }[] = [
  { value: "honey", label: "Honey" },
  { value: "pt", label: "Paragis Tea" },
  { value: "brc", label: "Black Rice Coffee" },
  { value: "jar_cacao", label: "Jar Cacao" },
  { value: "honey_comb", label: "Honey Comb" },
];

function todayIso(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function newDraftLine(): RtsBarcodeLabelDraftLine {
  return {
    key: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    product_key: "honey",
    variant: "light",
    size: "250ml",
    batch_no: "",
    bottling_date: todayIso(),
    quantity: 5,
  };
}

function scanRatioLabel(job: RtsBarcodeLabelJob): string {
  const total = job.code_count > 0 ? job.code_count : job.label_count;
  return `${job.scanned_count} / ${total} scanned`;
}

function statusBadgeClass(status: string): string {
  if (status === "scanned") return "bg-emerald-500/15 text-emerald-800 dark:text-emerald-300";
  if (status === "void") return "bg-destructive/15 text-destructive";
  if (status === "printed") return "bg-amber-500/15 text-amber-900 dark:text-amber-200";
  return "bg-muted text-muted-foreground";
}

function RtsBarcodeJobRow({
  job,
  open,
  onToggle,
  onPrint,
}: {
  job: RtsBarcodeLabelJob;
  open: boolean;
  onToggle: () => void;
  onPrint: () => void;
}) {
  const reduceMotion = useReducedMotion();
  const [codes, setCodes] = useState<RtsBarcodeLabelCode[] | null>(null);
  const [loadingCodes, setLoadingCodes] = useState(false);
  const [codesError, setCodesError] = useState<string | null>(null);

  useEffect(() => {
    if (!open || codes !== null) return;
    let cancelled = false;
    setLoadingCodes(true);
    setCodesError(null);
    void rtsBarcodeLabelJobCodes(job.id)
      .then((res) => {
        if (!cancelled) setCodes(res.codes ?? []);
      })
      .catch((e) => {
        if (!cancelled) {
          setCodesError(e instanceof ApiError ? e.message : "Could not load barcodes.");
        }
      })
      .finally(() => {
        if (!cancelled) setLoadingCodes(false);
      });
    return () => {
      cancelled = true;
    };
  }, [open, job.id, codes]);

  const total = job.code_count > 0 ? job.code_count : job.label_count;
  const pct = total > 0 ? Math.round((job.scanned_count / total) * 100) : 0;

  return (
    <li className="border-b border-border last:border-b-0">
      <div className={cn(appHoverRow, "flex flex-wrap items-center gap-2 py-3 px-2 -mx-2 rounded-lg")}>
        <AppPressable
          type="button"
          variant="ghost"
          className="!px-1 !py-0.5 flex min-w-0 flex-1 items-start gap-2 text-left font-normal"
          onClick={onToggle}
        >
          <ChevronDown
            className={cn(
              "size-4 shrink-0 mt-1 text-muted-foreground transition-transform",
              open && "rotate-180",
            )}
            aria-hidden
          />
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
              <span className="font-medium">Job #{job.id}</span>
              <span
                className={cn(
                  "text-xs font-medium tabular-nums rounded-md px-1.5 py-0.5",
                  job.scanned_count >= total && total > 0
                    ? "bg-emerald-500/15 text-emerald-800 dark:text-emerald-300"
                    : "bg-muted text-muted-foreground",
                )}
                title="Barcodes stocked in via inventory scan"
              >
                {scanRatioLabel(job)}
                {total > 0 ? ` (${pct}%)` : ""}
              </span>
            </div>
            <div className="text-xs text-muted-foreground">
              {job.created_by_name} · {job.created_at}
              {" · "}
              {job.line_count} line(s) · {job.label_count} label(s)
              {job.printed_at ? " · printed" : ""}
            </div>
            {job.note ? <div className="text-xs text-muted-foreground mt-0.5">{job.note}</div> : null}
            <div className="mt-1.5 h-1.5 max-w-xs rounded-full bg-muted overflow-hidden" aria-hidden>
              <div
                className="h-full rounded-full bg-emerald-600/80 transition-[width] duration-300"
                style={{ width: `${pct}%` }}
              />
            </div>
          </div>
        </AppPressable>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={(e) => {
            e.stopPropagation();
            onPrint();
          }}
        >
          <Printer className="size-3.5" />
          Print
        </Button>
      </div>

      <AnimatePresence initial={false}>
        {open ? (
          <motion.div
            key="panel"
            initial={reduceMotion ? false : { height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
            transition={{ duration: reduceMotion ? 0 : 0.2 }}
            className="overflow-hidden"
          >
            <div className="pb-3 pl-8 pr-2 space-y-2">
              {loadingCodes ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground py-2">
                  <Loader2 className="size-4 animate-spin" />
                  Loading barcodes…
                </div>
              ) : null}
              {codesError ? <AppErrorBanner message={codesError} /> : null}
              {codes && codes.length === 0 ? (
                <p className="text-sm text-muted-foreground py-1">No barcode rows for this job.</p>
              ) : null}
              {codes && codes.length > 0 ? (
                <div className="rounded-lg border border-border bg-muted/30 max-h-64 overflow-y-auto">
                  <table className="w-full text-xs">
                    <thead className="sticky top-0 bg-card border-b border-border text-left text-muted-foreground">
                      <tr>
                        <th className="py-1.5 px-2 font-medium">Code</th>
                        <th className="py-1.5 px-2 font-medium">Batch</th>
                        <th className="py-1.5 px-2 font-medium">Product</th>
                        <th className="py-1.5 px-2 font-medium">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {codes.map((c) => (
                        <tr key={`${c.line_id}-${c.code}`} className="border-b border-border/60 last:border-0">
                          <td className="py-1.5 px-2 font-mono">{c.code}</td>
                          <td className="py-1.5 px-2 font-mono">{c.batch_no}</td>
                          <td className="py-1.5 px-2">
                            {c.product_label}
                            {c.size_label ? ` · ${c.size_label}` : ""}
                          </td>
                          <td className="py-1.5 px-2">
                            <span
                              className={cn(
                                "inline-block rounded px-1.5 py-0.5 capitalize",
                                statusBadgeClass(c.status),
                              )}
                            >
                              {c.status}
                            </span>
                            {c.scanned_at ? (
                              <span className="block text-[10px] text-muted-foreground mt-0.5">
                                {c.scanned_at}
                              </span>
                            ) : null}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : null}
            </div>
          </motion.div>
        ) : null}
      </AnimatePresence>
    </li>
  );
}

export function RTSBarcodeLabelsPage() {
  const formId = useId();
  const [draft, setDraft] = useState<RtsBarcodeLabelDraftLine[]>([newDraftLine()]);
  const [note, setNote] = useState("");
  const [jobs, setJobs] = useState<RtsBarcodeLabelJob[]>([]);
  const [lastJob, setLastJob] = useState<RtsBarcodeLabelJob | null>(null);
  const [expandedJobId, setExpandedJobId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);

  const loadHistory = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      await rtsBarcodeLabelMeta();
      const res = await rtsBarcodeLabelJobList(1);
      setJobs(res.rows ?? []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS label jobs.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadHistory();
  }, [loadHistory]);

  const updateLine = (key: string, patch: Partial<RtsBarcodeLabelDraftLine>) => {
    setDraft((rows) => rows.map((r) => (r.key === key ? { ...r, ...patch } : r)));
  };

  const removeLine = (key: string) => {
    setDraft((rows) => (rows.length <= 1 ? rows : rows.filter((r) => r.key !== key)));
  };

  const openPrint = async (jobId: number) => {
    try {
      await rtsBarcodeLabelMarkPrinted(jobId);
    } catch {
      /* print still useful even if mark fails */
    }
    window.open(rtsBarcodeLabelPrintUrl(jobId), "_blank", "noopener,noreferrer");
    void loadHistory();
  };

  const onGenerate = async () => {
    setSaving(true);
    setError(null);
    setToast(null);
    try {
      for (const row of draft) {
        if (!row.batch_no.trim()) {
          throw new Error("Every line needs a batch number.");
        }
        if (!row.bottling_date) {
          throw new Error("Every line needs a bottling date.");
        }
        if (row.quantity < 1) {
          throw new Error("Quantity must be at least 1.");
        }
      }
      const lines = draft.map((row) => {
        const base = {
          product_key: row.product_key,
          batch_no: row.batch_no.trim().toUpperCase(),
          bottling_date: row.bottling_date,
          quantity: Math.floor(row.quantity),
        };
        if (row.product_key === "honey") {
          return { ...base, variant: row.variant, size: row.size };
        }
        return base;
      });
      const res = await rtsBarcodeLabelJobCreate(lines, note.trim() || undefined);
      setLastJob(res.job);
      setToast(
        `Generated job #${res.job.id}: ${res.job.line_count} batch line(s), ${res.job.label_count} label(s).`,
      );
      setDraft([newDraftLine()]);
      setNote("");
      setExpandedJobId(res.job.id);
      await loadHistory();
    } catch (e) {
      setError(e instanceof ApiError || e instanceof Error ? e.message : "Generate failed.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <PageLayout
      title="Barcode labels"
      subtitle="Generate barcodes for unlabeled RTS units — print, stick, then scan stock-in on the RTS inventory page."
    >
      <AppSectionEnter className="space-y-6">
        {error ? <AppErrorBanner message={error} /> : null}
        {toast ? <AppSuccessNotice message={toast} /> : null}

        <section className="rounded-xl border border-border bg-card p-4 space-y-4">
          <div className="flex flex-wrap items-end justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold text-foreground">Draft batches</h2>
              <p className="text-sm text-muted-foreground">
                One Generate creates all lines as a single print job. Small lots share the same paper.
              </p>
            </div>
            <Button type="button" variant="outline" onClick={() => setDraft((r) => [...r, newDraftLine()])}>
              <Plus className="size-4" />
              Add batch line
            </Button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[860px] text-sm">
              <thead>
                <tr className="border-b border-border text-left text-muted-foreground">
                  <th className="py-2 pr-2 font-medium">Product</th>
                  <th className="py-2 pr-2 font-medium">Variant / size</th>
                  <th className="py-2 pr-2 font-medium">Batch no</th>
                  <th className="py-2 pr-2 font-medium">Bottling date</th>
                  <th className="py-2 pr-2 font-medium">Qty</th>
                  <th className="py-2 font-medium w-12" />
                </tr>
              </thead>
              <tbody>
                {draft.map((row) => (
                  <tr key={row.key} className="border-b border-border/70 align-top">
                    <td className="py-2 pr-2">
                      <select
                        className="w-full rounded-md border border-input bg-background px-2 py-1.5"
                        value={row.product_key}
                        onChange={(e) =>
                          updateLine(row.key, { product_key: e.target.value as RtsLabelProductKey })
                        }
                        aria-label="Product"
                      >
                        {PRODUCT_OPTIONS.map((o) => (
                          <option key={o.value} value={o.value}>
                            {o.label}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="py-2 pr-2">
                      {row.product_key === "honey" ? (
                        <div className="flex flex-wrap gap-2">
                          <select
                            className="rounded-md border border-input bg-background px-2 py-1.5"
                            value={row.variant}
                            onChange={(e) => updateLine(row.key, { variant: e.target.value })}
                            aria-label="Variant"
                          >
                            {HONEY_VARIANTS.map((v) => (
                              <option key={v} value={v}>
                                {variantLabel(v)}
                              </option>
                            ))}
                          </select>
                          <select
                            className="rounded-md border border-input bg-background px-2 py-1.5"
                            value={row.size}
                            onChange={(e) =>
                              updateLine(row.key, { size: e.target.value as RtsLabelHoneySize })
                            }
                            aria-label="Size"
                          >
                            {HONEY_BOTTLE_OPTIONS.map((s) => (
                              <option key={s.value} value={s.value}>
                                {s.label}
                              </option>
                            ))}
                          </select>
                        </div>
                      ) : (
                        <span className="text-muted-foreground text-xs">—</span>
                      )}
                    </td>
                    <td className="py-2 pr-2">
                      <input
                        className="w-full rounded-md border border-input bg-background px-2 py-1.5 font-mono uppercase"
                        value={row.batch_no}
                        onChange={(e) => updateLine(row.key, { batch_no: e.target.value.toUpperCase() })}
                        placeholder="e.g. JR073026"
                        aria-label="Batch number"
                      />
                    </td>
                    <td className="py-2 pr-2">
                      <input
                        type="date"
                        className="w-full rounded-md border border-input bg-background px-2 py-1.5"
                        value={row.bottling_date}
                        onChange={(e) => updateLine(row.key, { bottling_date: e.target.value })}
                        aria-label="Bottling date"
                      />
                    </td>
                    <td className="py-2 pr-2">
                      <input
                        type="number"
                        min={1}
                        max={500}
                        className="w-20 rounded-md border border-input bg-background px-2 py-1.5"
                        value={row.quantity}
                        onChange={(e) => updateLine(row.key, { quantity: Number(e.target.value) || 0 })}
                        aria-label="Quantity"
                      />
                    </td>
                    <td className="py-2">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        disabled={draft.length <= 1}
                        onClick={() => removeLine(row.key)}
                        aria-label="Remove line"
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <label className="block text-sm flex-1 max-w-md" htmlFor={`${formId}-note`}>
              <span className="text-muted-foreground">Note (optional)</span>
              <input
                id={`${formId}-note`}
                className="mt-1 w-full rounded-md border border-input bg-background px-3 py-2"
                value={note}
                onChange={(e) => setNote(e.target.value)}
                placeholder="e.g. Shelf A unlabeled honey"
              />
            </label>
            <Button type="button" disabled={saving} onClick={() => void onGenerate()}>
              {saving ? <Loader2 className="size-4 animate-spin" /> : null}
              Generate all
            </Button>
          </div>
        </section>

        {lastJob ? (
          <section className="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 className="text-base font-semibold">Job #{lastJob.id} ready</h2>
                <p className="text-sm text-muted-foreground">
                  {lastJob.line_count} batch line(s) · {lastJob.label_count} label(s) · continuous print
                </p>
              </div>
              <Button type="button" onClick={() => void openPrint(lastJob.id)}>
                <Printer className="size-4" />
                Print sheet
              </Button>
            </div>
            <ul className="text-sm space-y-1">
              {lastJob.lines.map((line) => (
                <li key={line.id} className="font-mono text-xs sm:text-sm">
                  {line.batch_no} · {line.bottling_date} · {line.product_label}
                  {line.size_label ? ` ${line.size_label}` : ""} · qty {line.quantity}
                </li>
              ))}
            </ul>
          </section>
        ) : null}

        <section className="rounded-xl border border-border bg-card p-4 space-y-3">
          <div>
            <h2 className="text-base font-semibold">Recent jobs</h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Expand a job to see each barcode and how many have been stocked in (scan status).
            </p>
          </div>
          {loading ? (
            <div className="flex items-center gap-2 text-sm text-muted-foreground py-6">
              <Loader2 className="size-4 animate-spin" />
              Loading…
            </div>
          ) : jobs.length === 0 ? (
            <p className="text-sm text-muted-foreground py-4">No RTS label jobs yet.</p>
          ) : (
            <ul>
              {jobs.map((job) => (
                <RtsBarcodeJobRow
                  key={job.id}
                  job={job}
                  open={expandedJobId === job.id}
                  onToggle={() =>
                    setExpandedJobId((id) => (id === job.id ? null : job.id))
                  }
                  onPrint={() => void openPrint(job.id)}
                />
              ))}
            </ul>
          )}
        </section>

        <p className="text-xs text-muted-foreground">
          After sticking, scan stock-in on the <span className="font-medium text-foreground">RTS inventory</span>{" "}
          page (not packaging / Honey production scanners). These batches are marked{" "}
          <span className="font-mono">rts_label</span> so history shows they came from RTS. Ratio counts
          barcodes with status <span className="font-mono">scanned</span>.
        </p>
      </AppSectionEnter>
    </PageLayout>
  );
}
