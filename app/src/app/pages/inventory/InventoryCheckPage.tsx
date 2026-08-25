import { useCallback, useEffect, useId, useMemo, useState } from "react";
import { Camera, ClipboardCheck, Loader2, Scale, Trash2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppStepSwitch,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { InventoryCheckScanLogModal } from "@/app/components/inventory/InventoryCheckScanLogModal";
import { ApiError } from "@/app/lib/api";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import {
  inventoryCheckApi,
  type InventoryCheckCompareResult,
  type InventoryCheckCompareRun,
  type InventoryCheckContext,
  type InventoryCheckScan,
  type InventoryCheckSummary,
  type InventoryCheckSummaryRow,
} from "@/app/lib/inventoryCheck";

type Tab = "scan" | "summary" | "compare" | "history";

function varianceTone(variance: number): string {
  if (Math.abs(variance) < 0.001) return "text-muted-foreground";
  return variance > 0 ? "text-emerald-600" : "text-destructive";
}

function formatQty(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

export function InventoryCheckPage() {
  const cameraTitleId = useId();
  const [tab, setTab] = useState<Tab>("scan");
  const [ctx, setCtx] = useState<InventoryCheckContext | null>(null);
  const [summary, setSummary] = useState<InventoryCheckSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [code, setCode] = useState("");
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [recent, setRecent] = useState<InventoryCheckScan[]>([]);
  const [detailRow, setDetailRow] = useState<InventoryCheckSummaryRow | null>(null);
  const [compare, setCompare] = useState<InventoryCheckCompareResult | null>(null);
  const [runs, setRuns] = useState<InventoryCheckCompareRun[]>([]);
  const [history, setHistory] = useState<InventoryCheckContext["event"][]>([]);
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen && tab === "scan", tab);

  const event = ctx?.event ?? null;
  const canViewAll = ctx?.can_view_all ?? false;
  const canManage = ctx?.can_manage ?? false;

  const loadContext = useCallback(async () => {
    setLoading(true);
    try {
      const res = await inventoryCheckApi.context();
      setCtx(res);
      setSummary(res.summary);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load the inventory count.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadContext();
  }, [loadContext]);

  useEffect(() => {
    if (tab !== "history" || !canViewAll) return;
    void (async () => {
      try {
        const res = await inventoryCheckApi.eventList(1);
        setHistory(res.rows);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load count history.");
      }
    })();
  }, [tab, canViewAll]);

  useEffect(() => {
    if (tab !== "compare" || !canViewAll || event === null) return;
    void (async () => {
      try {
        const res = await inventoryCheckApi.compareList(event.id);
        setRuns(res.runs);
      } catch {
        setRuns([]);
      }
    })();
  }, [tab, canViewAll, event]);

  const tabs = useMemo<Tab[]>(
    () => (canViewAll ? ["scan", "summary", "compare", "history"] : ["scan"]),
    [canViewAll],
  );

  const openCount = async () => {
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.openEvent();
      setOk("Today's inventory count is open — everyone can scan now.");
      await loadContext();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not open the count.");
    } finally {
      setBusy(false);
    }
  };

  const closeCount = async () => {
    if (event === null) return;
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.closeEvent(event.id);
      setOk("Count closed. Scanning is now locked for this date.");
      await loadContext();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not close the count.");
    } finally {
      setBusy(false);
    }
  };

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value || event === null) return;
    setCode("");
    setOk(null);
    setError(null);
    try {
      const res = await inventoryCheckApi.scan(event.id, value);
      setSummary(res.summary);
      if (res.result === "unknown" || res.result === "duplicate_event") {
        playScanBeep("bad");
        setError(res.message);
        refocus();
        return { kind: "error", message: res.message };
      }
      playScanBeep("ok");
      if (res.scan !== null) {
        setRecent(prev => [res.scan as InventoryCheckScan, ...prev].slice(0, 25));
      }
      const label = res.scan
        ? `${res.scan.product_label}${res.scan.size_label ? ` ${res.scan.size_label}` : ""}`
        : value;
      setOk(`${label} — ${res.message}`);
      refocus();
      return { kind: "success", message: label };
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Scan failed.";
      playScanBeep("bad");
      setError(msg);
      refocus();
      return { kind: "error", message: msg };
    }
  };

  const removeScan = async (scanId: number) => {
    if (event === null) return;
    setError(null);
    try {
      const res = await inventoryCheckApi.scanRemove(event.id, scanId);
      if (res.summary) setSummary(res.summary);
      setRecent(prev => prev.filter(r => r.id !== scanId));
      setOk("Scan removed from the count.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not remove that scan.");
    }
  };

  const runCompare = async () => {
    if (event === null) return;
    setBusy(true);
    setError(null);
    try {
      const res = await inventoryCheckApi.compareRun(event.id);
      setCompare(res);
      setRuns(prev => [res.run, ...prev]);
      setTab("compare");
      setOk(
        res.run.mismatch_count === 0
          ? "Everything counted matches system inventory."
          : `${res.run.mismatch_count} of ${res.run.line_count} lines do not match.`,
      );
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not compare with current inventory.");
    } finally {
      setBusy(false);
    }
  };

  const loadRun = async (runId: number) => {
    setBusy(true);
    try {
      setCompare(await inventoryCheckApi.compareGet(runId));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load that comparison.");
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <PageLayout title="Check inventory" subtitle="Physical stock take against system inventory.">
        <p className="text-sm text-muted-foreground flex items-center gap-2">
          <Loader2 className="animate-spin" size={16} /> Loading…
        </p>
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Check inventory"
      subtitle="Scan the finished products physically on hand, then compare the count with system inventory."
    >
      <AppSectionEnter className="space-y-4">
        <div className="rounded-xl border border-border bg-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
          <div>
            {event === null ? (
              <p className="text-sm text-muted-foreground">
                No count is open. {canManage ? "Start one to let everyone scan." : "Ask a manager to open today's count."}
              </p>
            ) : (
              <>
                <p className="text-sm font-semibold">
                  Count for {event.count_date}
                  <span
                    className={`ml-2 text-xs font-medium rounded-full px-2 py-0.5 ${
                      event.status === "open"
                        ? "bg-emerald-100 text-emerald-700"
                        : "bg-secondary text-muted-foreground"
                    }`}
                  >
                    {event.status === "open" ? "Open" : "Closed"}
                  </span>
                </p>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Opened by {event.opened_by_name} · {event.scan_count} unit
                  {event.scan_count === 1 ? "" : "s"} counted by {event.scanner_count} staff
                </p>
              </>
            )}
          </div>
          {canManage && (
            <div className="flex gap-2">
              {event === null || event.status === "closed" ? (
                <Button type="button" onClick={() => void openCount()} disabled={busy}>
                  <ClipboardCheck size={16} className="mr-1" /> Start today's count
                </Button>
              ) : (
                <Button type="button" variant="outline" onClick={() => void closeCount()} disabled={busy}>
                  Close count
                </Button>
              )}
            </div>
          )}
        </div>

        {tabs.length > 1 && (
          <div className="flex flex-wrap gap-2">
            {tabs.map(t => (
              <Button
                key={t}
                type="button"
                size="sm"
                variant={tab === t ? "default" : "outline"}
                onClick={() => setTab(t)}
              >
                {t === "scan" ? "Scan" : t === "summary" ? "Summary" : t === "compare" ? "Compare" : "History"}
              </Button>
            ))}
          </div>
        )}

        {ok && <AppSuccessNotice message={ok} />}
        {error && <AppErrorBanner message={error} />}

        <AppStepSwitch stepKey={tab}>
          {tab === "scan" ? (
            <div className="space-y-4">
              {event === null || event.status !== "open" ? (
                <p className="text-sm text-muted-foreground">
                  Scanning opens once a manager starts the count.
                </p>
              ) : (
                <>
                  <div className="flex gap-2 max-w-xl">
                    <input
                      ref={inputRef}
                      {...inputProps}
                      className="flex-1 border rounded-lg px-3 py-2 text-sm"
                      placeholder="Scan any finished product barcode"
                      value={code}
                      onChange={e => setCode(e.target.value)}
                      onKeyDown={e => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          void onScan();
                        }
                      }}
                    />
                    <Button type="button" variant="outline" onClick={() => setCameraOpen(true)}>
                      <Camera size={16} className="mr-1" /> Camera
                    </Button>
                  </div>

                  <p className="text-xs text-muted-foreground">
                    Counting never changes stock. Honey and every department line can be scanned here.
                  </p>

                  {recent.length > 0 && (
                    <div className="space-y-2">
                      <p className="text-xs uppercase tracking-wide text-muted-foreground">
                        Just scanned
                      </p>
                      <ul className="divide-y rounded-xl border border-border overflow-hidden">
                        {recent.map(r => (
                          <li
                            key={r.id}
                            className={`px-3 py-2 flex items-center justify-between gap-3 ${appHoverRow}`}
                          >
                            <div className="min-w-0">
                              <p className="text-sm font-medium">
                                {r.product_label}
                                {r.size_label ? ` · ${r.size_label}` : ""}
                                {r.batch_no ? ` · ${r.batch_no}` : ""}
                              </p>
                              <p className="font-mono text-xs text-muted-foreground">
                                {r.code}
                                {r.flag !== "ok" ? ` · ${r.flag.replace(/_/g, " ")}` : ""}
                              </p>
                            </div>
                            <Button
                              type="button"
                              size="sm"
                              variant="ghost"
                              onClick={() => void removeScan(r.id)}
                              aria-label={`Remove ${r.code}`}
                            >
                              <Trash2 size={15} />
                            </Button>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </>
              )}

              {summary && summary.rows.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs uppercase tracking-wide text-muted-foreground">
                    {summary.scoped_to_user ? "What you counted today" : "Counted today"} ·{" "}
                    {summary.total_units} unit{summary.total_units === 1 ? "" : "s"}
                  </p>
                  <SummaryTable
                    summary={summary}
                    onRowClick={canViewAll ? setDetailRow : undefined}
                  />
                </div>
              )}
            </div>
          ) : tab === "summary" ? (
            <div className="space-y-4">
              {summary === null || summary.rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">Nothing has been counted yet.</p>
              ) : (
                <>
                  <div className="grid gap-3 sm:grid-cols-3">
                    <StatCard label="Units counted" value={String(summary.total_units)} />
                    <StatCard label="Lines" value={String(summary.rows.length)} />
                    <StatCard
                      label="Flagged units"
                      value={String(summary.flagged_units)}
                      tone={summary.flagged_units > 0 ? "warn" : "plain"}
                    />
                  </div>
                  <SummaryTable summary={summary} onRowClick={setDetailRow} />
                  <div className="flex justify-end">
                    <Button type="button" onClick={() => void runCompare()} disabled={busy}>
                      <Scale size={16} className="mr-1" /> Compare with current inventory
                    </Button>
                  </div>
                </>
              )}
            </div>
          ) : tab === "compare" ? (
            <div className="space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">
                  Compares only the product lines that were counted.
                </p>
                <Button type="button" onClick={() => void runCompare()} disabled={busy || event === null}>
                  <Scale size={16} className="mr-1" /> Run comparison
                </Button>
              </div>

              {compare && (
                <div className="space-y-3">
                  <div className="grid gap-3 sm:grid-cols-4">
                    <StatCard label="Scope" value={compare.run.scope || "—"} />
                    <StatCard label="Counted" value={String(compare.run.counted_total)} />
                    <StatCard label="System" value={String(compare.run.system_total)} />
                    <StatCard
                      label="Mismatched lines"
                      value={String(compare.run.mismatch_count)}
                      tone={compare.run.mismatch_count > 0 ? "warn" : "plain"}
                    />
                  </div>
                  <div className="overflow-x-auto rounded-xl border border-border">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                          <th className="px-3 py-2">Product</th>
                          <th className="px-3 py-2">Batch</th>
                          <th className="px-3 py-2">Bottled</th>
                          <th className="px-3 py-2">Size</th>
                          <th className="px-3 py-2 text-right">Counted</th>
                          <th className="px-3 py-2 text-right">System</th>
                          <th className="px-3 py-2 text-right">Variance</th>
                        </tr>
                      </thead>
                      <tbody>
                        {compare.lines.map(l => (
                          <tr key={l.id} className={appHoverRow}>
                            <td className="px-3 py-2">{l.product_label}</td>
                            <td className="px-3 py-2">
                              {l.batch_no ?? "—"}
                              {l.supplier_code ? (
                                <span className="text-xs text-muted-foreground"> · {l.supplier_code}</span>
                              ) : null}
                            </td>
                            <td className="px-3 py-2 whitespace-nowrap">{l.bottling_date ?? "—"}</td>
                            <td className="px-3 py-2">{l.size_label ?? "—"}</td>
                            <td className="px-3 py-2 text-right">{l.counted_qty}</td>
                            <td className="px-3 py-2 text-right">{formatQty(l.system_qty)}</td>
                            <td className={`px-3 py-2 text-right font-semibold ${varianceTone(l.variance)}`}>
                              {l.variance > 0 ? `+${formatQty(l.variance)}` : formatQty(l.variance)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {runs.length > 0 && (
                <div className="space-y-2">
                  <p className="text-xs uppercase tracking-wide text-muted-foreground">
                    Comparison runs for this count
                  </p>
                  <ul className="divide-y rounded-xl border border-border overflow-hidden">
                    {runs.map(r => (
                      <li key={r.id}>
                        <button
                          type="button"
                          onClick={() => void loadRun(r.id)}
                          className={`w-full text-left px-3 py-2 flex items-center justify-between gap-3 ${appHoverRow}`}
                        >
                          <span className="text-sm">
                            {new Date(r.run_at.replace(" ", "T")).toLocaleString()} · {r.run_by_name}
                          </span>
                          <span
                            className={`text-xs font-medium ${
                              r.mismatch_count > 0 ? "text-destructive" : "text-emerald-600"
                            }`}
                          >
                            {r.mismatch_count > 0 ? `${r.mismatch_count} mismatched` : "All matched"}
                          </span>
                        </button>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          ) : (
            <div className="space-y-2">
              {history.length === 0 ? (
                <p className="text-sm text-muted-foreground">No inventory counts recorded yet.</p>
              ) : (
                <div className="overflow-x-auto rounded-xl border border-border">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                        <th className="px-3 py-2">Date</th>
                        <th className="px-3 py-2">Status</th>
                        <th className="px-3 py-2">Opened by</th>
                        <th className="px-3 py-2 text-right">Units</th>
                        <th className="px-3 py-2 text-right">Staff</th>
                        <th className="px-3 py-2 text-right">Comparisons</th>
                      </tr>
                    </thead>
                    <tbody>
                      {history.filter(Boolean).map(h => (
                        <tr key={h!.id} className={appHoverRow}>
                          <td className="px-3 py-2 whitespace-nowrap">{h!.count_date}</td>
                          <td className="px-3 py-2">{h!.status === "open" ? "Open" : "Closed"}</td>
                          <td className="px-3 py-2">{h!.opened_by_name}</td>
                          <td className="px-3 py-2 text-right">{h!.scan_count}</td>
                          <td className="px-3 py-2 text-right">{h!.scanner_count}</td>
                          <td className="px-3 py-2 text-right">{h!.compare_run_count}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          )}
        </AppStepSwitch>
      </AppSectionEnter>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          titleId={cameraTitleId}
          title="Count inventory"
          continuous
          scanPrompt="Point at a honey bottle or pouch/jar sticker."
          onClose={() => setCameraOpen(false)}
          onDecoded={onScan}
        />
      ) : null}

      {detailRow && event ? (
        <InventoryCheckScanLogModal
          eventId={event.id}
          countDate={event.count_date}
          row={detailRow}
          onClose={() => setDetailRow(null)}
        />
      ) : null}
    </PageLayout>
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
      <p className={`text-lg font-semibold mt-1 ${tone === "warn" ? "text-destructive" : ""}`}>
        {value}
      </p>
    </div>
  );
}

function SummaryTable({
  summary,
  onRowClick,
}: {
  summary: InventoryCheckSummary;
  onRowClick?: (row: InventoryCheckSummaryRow) => void;
}) {
  return (
    <div className="overflow-x-auto rounded-xl border border-border">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
            <th className="px-3 py-2">Product</th>
            <th className="px-3 py-2">Supplier / batch</th>
            <th className="px-3 py-2">Bottled</th>
            <th className="px-3 py-2">Variant</th>
            <th className="px-3 py-2">Size</th>
            <th className="px-3 py-2 text-right">Counted</th>
            <th className="px-3 py-2">Scanned by</th>
          </tr>
        </thead>
        <tbody>
          {summary.rows.map(r => (
            <tr
              key={`${r.kind}-${r.product_key}-${r.packaging_row_id ?? 0}-${r.batch_no ?? ""}-${r.size ?? ""}`}
              className={`${appHoverRow} ${onRowClick ? "cursor-pointer" : ""}`}
              onClick={onRowClick ? () => onRowClick(r) : undefined}
            >
              <td className="px-3 py-2">{r.product_label}</td>
              <td className="px-3 py-2">
                {r.supplier_code && r.supplier_code !== r.batch_no ? `${r.supplier_code} · ` : ""}
                {r.batch_no ?? "—"}
              </td>
              <td className="px-3 py-2 whitespace-nowrap">{r.bottling_date ?? "—"}</td>
              <td className="px-3 py-2 capitalize">{r.variant ?? "—"}</td>
              <td className="px-3 py-2">{r.size_label ?? "—"}</td>
              <td className="px-3 py-2 text-right font-semibold">
                {r.qty}
                {r.flagged_qty > 0 ? (
                  <span className="ml-1 text-xs font-normal text-destructive">
                    ({r.flagged_qty} flagged)
                  </span>
                ) : null}
              </td>
              <td className="px-3 py-2 text-xs text-muted-foreground">{r.scanner_names || "—"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
