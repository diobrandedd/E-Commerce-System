import { useCallback, useEffect, useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { InventoryCheckBatchDocs } from "@/app/components/inventory/InventoryCheckBatchDocs";
import { InventoryCheckScanLogModal } from "@/app/components/inventory/InventoryCheckScanLogModal";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import {
  inventoryCheckApi,
  type InventoryCheckCompareResult,
  type InventoryCheckCompareRun,
  type InventoryCheckEvent,
  type InventoryCheckScan,
  type InventoryCheckSummary,
  type InventoryCheckSummaryRow,
} from "@/app/lib/inventoryCheck";

interface InventoryCheckHistoryDetailModalProps {
  event: InventoryCheckEvent;
  onClose: () => void;
}

function formatWhen(raw: string | null | undefined) {
  if (!raw) return "—";
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function formatQty(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

function varianceTone(variance: number): string {
  if (Math.abs(variance) < 0.001) return "text-muted-foreground";
  return variance > 0 ? "text-emerald-600" : "text-destructive";
}

type TabId = "summary" | "compare" | "scans";

export function InventoryCheckHistoryDetailModal({ event, onClose }: InventoryCheckHistoryDetailModalProps) {
  const titleId = useId();
  const [tab, setTab] = useState<TabId>("summary");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [summary, setSummary] = useState<InventoryCheckSummary | null>(null);
  const [runs, setRuns] = useState<InventoryCheckCompareRun[]>([]);
  const [compare, setCompare] = useState<InventoryCheckCompareResult | null>(null);
  const [scans, setScans] = useState<InventoryCheckScan[]>([]);
  const [scanTotal, setScanTotal] = useState(0);
  const [scanPage, setScanPage] = useState(1);
  const [scanLoading, setScanLoading] = useState(false);
  const [logRow, setLogRow] = useState<InventoryCheckSummaryRow | null>(null);

  const loadCore = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [summaryRes, compareList] = await Promise.all([
        inventoryCheckApi.summary(event.id),
        inventoryCheckApi.compareList(event.id),
      ]);
      setSummary(summaryRes);
      setRuns(compareList.runs);
      const latest = compareList.runs[0];
      if (latest) {
        setCompare(await inventoryCheckApi.compareGet(latest.id));
      } else {
        setCompare(null);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load this audit session.");
      setSummary(null);
      setRuns([]);
      setCompare(null);
    } finally {
      setLoading(false);
    }
  }, [event.id]);

  const loadScans = useCallback(
    async (page: number) => {
      setScanLoading(true);
      setError(null);
      try {
        const res = await inventoryCheckApi.scanLog(event.id, {}, page, 50);
        setScans(res.rows);
        setScanTotal(res.total);
        setScanPage(res.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load the scan log.");
        setScans([]);
        setScanTotal(0);
      } finally {
        setScanLoading(false);
      }
    },
    [event.id],
  );

  useEffect(() => {
    void loadCore();
  }, [loadCore]);

  useEffect(() => {
    if (tab === "scans") void loadScans(1);
  }, [tab, loadScans]);

  const scanPages = Math.max(1, Math.ceil(scanTotal / 50));
  const subtitle = [
    event.status === "open" ? "Open session" : "Closed session",
    event.opened_by_name ? `opened by ${event.opened_by_name}` : null,
    event.closed_at ? `closed ${formatWhen(event.closed_at)}` : null,
  ]
    .filter(Boolean)
    .join(" · ");

  return (
    <>
      <AppModal open onClose={onClose} panelClassName="max-w-3xl w-full" labelledBy={titleId}>
        <AppModalHeader
          title={`Audit · ${event.count_date}`}
          titleId={titleId}
          subtitle={subtitle}
          onClose={onClose}
        />
        <AppModalBody className="space-y-4 max-h-[75vh] overflow-y-auto">
          <AppSectionEnter className="space-y-4">
            <div
              className="flex gap-1 p-1 rounded-xl border border-border bg-secondary/30 w-fit max-w-full"
              role="tablist"
              aria-label="Audit history sections"
            >
              {(
                [
                  ["summary", "Counted"],
                  ["compare", "Comparison"],
                  ["scans", "Scan log"],
                ] as const
              ).map(([id, label]) => (
                <button
                  key={id}
                  type="button"
                  role="tab"
                  aria-selected={tab === id}
                  className={`min-h-11 px-3 py-2 rounded-lg text-xs font-semibold transition-colors ${
                    tab === id
                      ? "bg-card text-foreground shadow-sm"
                      : "text-muted-foreground hover:text-foreground hover:bg-card/60"
                  }`}
                  onClick={() => setTab(id)}
                >
                  {label}
                </button>
              ))}
            </div>

            {error && (
              <div>
                <AppErrorBanner message={error} />
                <Button
                  type="button"
                  variant="outline"
                  className="min-h-11 mt-2"
                  onClick={() => {
                    if (tab === "scans") void loadScans(scanPage);
                    else void loadCore();
                  }}
                >
                  Retry loading
                </Button>
              </div>
            )}

            {loading ? (
              <div className="py-12 text-center text-muted-foreground">
                <Loader2 className="inline animate-spin" size={18} />
              </div>
            ) : tab === "summary" ? (
              <div className="space-y-3">
                <div className="grid gap-3 sm:grid-cols-3">
                  <StatCard label="Lines" value={String(summary?.rows.length ?? 0)} />
                  <StatCard label="Units counted" value={String(summary?.total_units ?? 0)} />
                  <StatCard
                    label="Flagged"
                    value={String(summary?.flagged_units ?? 0)}
                    tone={(summary?.flagged_units ?? 0) > 0 ? "warn" : "plain"}
                  />
                </div>
                {!summary || summary.rows.length === 0 ? (
                  <p className="text-sm text-muted-foreground py-6 text-center">No products were counted.</p>
                ) : (
                  <div className="overflow-x-auto rounded-xl border border-border">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                          <th className="px-3 py-2">Product</th>
                          <th className="px-3 py-2">Batch</th>
                          <th className="px-3 py-2">Size</th>
                          <th className="px-3 py-2 text-right">Qty</th>
                          <th className="px-3 py-2">Scanners</th>
                          <th className="px-3 py-2" />
                        </tr>
                      </thead>
                      <tbody>
                        {summary.rows.map((row, index) => (
                          <tr
                            key={`${row.kind}-${row.product_key}-${row.batch_no ?? ""}-${row.size ?? ""}-${index}`}
                            className={appHoverRow}
                          >
                            <td className="px-3 py-2">
                              {row.product_label}
                              {row.variant ? ` · ${row.variant}` : ""}
                            </td>
                            <td className="px-3 py-2">{row.batch_no ?? "—"}</td>
                            <td className="px-3 py-2">{row.size_label ?? "—"}</td>
                            <td className="px-3 py-2 text-right font-semibold tabular-nums">{row.qty}</td>
                            <td className="px-3 py-2 text-xs text-muted-foreground">
                              {row.scanner_names || "—"}
                            </td>
                            <td className="px-3 py-2 text-right">
                              <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="min-h-11"
                                onClick={() => setLogRow(row)}
                              >
                                Log
                              </Button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            ) : tab === "compare" ? (
              <div className="space-y-3">
                {!compare ? (
                  <p className="text-sm text-muted-foreground py-6 text-center">
                    {runs.length === 0
                      ? "No comparison was saved for this audit."
                      : "Could not load the comparison run."}
                  </p>
                ) : (
                  <>
                    <div className="grid gap-3 sm:grid-cols-4">
                      <StatCard label="Scope" value={compare.run.scope || "—"} />
                      <StatCard label="Audited" value={String(compare.run.counted_total)} />
                      <StatCard label="System" value={String(compare.run.system_total)} />
                      <StatCard
                        label="Mismatched"
                        value={String(compare.run.mismatch_count)}
                        tone={compare.run.mismatch_count > 0 ? "warn" : "plain"}
                      />
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Run by {compare.run.run_by_name || "—"} · {formatWhen(compare.run.run_at)}
                      {runs.length > 1 ? ` · ${runs.length} runs total` : ""}
                    </p>
                    <div className="overflow-x-auto rounded-xl border border-border">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                            <th className="px-3 py-2">Product</th>
                            <th className="px-3 py-2">Size</th>
                            <th className="px-3 py-2 text-right">Audited</th>
                            <th className="px-3 py-2 text-right">System</th>
                            <th className="px-3 py-2 text-right">Variance</th>
                          </tr>
                        </thead>
                        <tbody>
                          {compare.lines.map(line => (
                            <tr key={line.id} className={appHoverRow}>
                              <td className="px-3 py-2">
                                {line.product_label}
                                {line.variant ? ` · ${line.variant}` : ""}
                              </td>
                              <td className="px-3 py-2">{line.size_label ?? "—"}</td>
                              <td className="px-3 py-2 text-right tabular-nums">{line.counted_qty}</td>
                              <td className="px-3 py-2 text-right tabular-nums">{formatQty(line.system_qty)}</td>
                              <td
                                className={`px-3 py-2 text-right font-semibold tabular-nums ${varianceTone(line.variance)}`}
                              >
                                {line.variance > 0 ? `+${formatQty(line.variance)}` : formatQty(line.variance)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                    <InventoryCheckBatchDocs batches={compare.batches ?? []} />
                  </>
                )}
              </div>
            ) : (
              <div className="space-y-3">
                {scanLoading ? (
                  <div className="py-12 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={18} />
                  </div>
                ) : scanTotal === 0 ? (
                  <p className="text-sm text-muted-foreground py-6 text-center">No scans on this audit.</p>
                ) : (
                  <>
                    <p className="text-xs text-muted-foreground">
                      {scanTotal} scan line{scanTotal === 1 ? "" : "s"}
                    </p>
                    <ul className="divide-y rounded-xl border border-border overflow-hidden">
                      {scans.map(scan => (
                        <li key={scan.id} className={`px-3 py-2.5 ${appHoverRow}`}>
                          <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                              <p className="font-mono text-sm font-semibold tracking-wide">{scan.code}</p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {scan.product_label}
                                {scan.size_label ? ` · ${scan.size_label}` : ""}
                                {scan.batch_no ? ` · ${scan.batch_no}` : ""}
                                {scan.bottling_date ? ` · bottled ${scan.bottling_date}` : ""}
                                {scan.counted_qty != null && scan.counted_qty !== 1
                                  ? ` · ×${scan.counted_qty}`
                                  : ""}
                                {scan.flag !== "ok" ? ` · ${scan.flag.replace(/_/g, " ")}` : ""}
                              </p>
                            </div>
                            <div className="text-right shrink-0">
                              <p className="text-sm font-medium">{scan.scanned_by_name || "—"}</p>
                              <p className="text-[11px] text-muted-foreground mt-0.5 whitespace-nowrap">
                                {formatWhen(scan.scanned_at)}
                              </p>
                            </div>
                          </div>
                        </li>
                      ))}
                    </ul>
                    {scanPages > 1 && (
                      <div className="flex items-center justify-between gap-2 pt-1">
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          className="min-h-11"
                          disabled={scanPage <= 1 || scanLoading}
                          onClick={() => void loadScans(scanPage - 1)}
                        >
                          Previous
                        </Button>
                        <span className="text-xs text-muted-foreground">
                          Page {scanPage} of {scanPages}
                        </span>
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          className="min-h-11"
                          disabled={scanPage >= scanPages || scanLoading}
                          onClick={() => void loadScans(scanPage + 1)}
                        >
                          Next
                        </Button>
                      </div>
                    )}
                  </>
                )}
              </div>
            )}
          </AppSectionEnter>
        </AppModalBody>
      </AppModal>

      {logRow ? (
        <InventoryCheckScanLogModal
          eventId={event.id}
          countDate={event.count_date}
          row={logRow}
          onClose={() => setLogRow(null)}
        />
      ) : null}
    </>
  );
}

function StatCard({
  label,
  value,
  tone = "plain",
}: {
  label: string;
  value: string;
  tone?: "plain" | "warn";
}) {
  return (
    <div className="rounded-xl border border-border bg-card px-4 py-3">
      <p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p>
      <p className={`text-lg font-semibold mt-1 ${tone === "warn" ? "text-destructive" : ""}`}>{value}</p>
    </div>
  );
}
