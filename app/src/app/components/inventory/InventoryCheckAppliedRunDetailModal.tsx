import { useCallback, useEffect, useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { InventoryCheckBatchDocs } from "@/app/components/inventory/InventoryCheckBatchDocs";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import {
  inventoryCheckApi,
  type InventoryCheckAppliedRun,
  type InventoryCheckCompareResult,
} from "@/app/lib/inventoryCheck";

interface InventoryCheckAppliedRunDetailModalProps {
  run: InventoryCheckAppliedRun;
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

export function InventoryCheckAppliedRunDetailModal({
  run,
  onClose,
}: InventoryCheckAppliedRunDetailModalProps) {
  const titleId = useId();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [compare, setCompare] = useState<InventoryCheckCompareResult | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setCompare(await inventoryCheckApi.compareGet(run.id));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load this confirmed update.");
      setCompare(null);
    } finally {
      setLoading(false);
    }
  }, [run.id]);

  useEffect(() => {
    void load();
  }, [load]);

  const sessionLabel =
    run.sessions.length > 0
      ? run.sessions.map(s => `${s.count_date} (${s.opened_by_name || "—"})`).join(" · ")
      : run.primary_count_date || "—";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-3xl w-full" labelledBy={titleId}>
      <AppModalHeader
        title={run.is_merged ? "Merged inventory update" : "Confirmed inventory update"}
        titleId={titleId}
        subtitle={`Applied ${formatWhen(run.stock_applied_at ?? run.run_at)}`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4 max-h-[75vh] overflow-y-auto">
        <AppSectionEnter className="space-y-4">
          {error && (
            <div>
              <AppErrorBanner message={error} />
              <Button type="button" variant="outline" className="min-h-11 mt-2" onClick={() => void load()}>
                Retry loading
              </Button>
            </div>
          )}

          {loading ? (
            <div className="py-12 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : compare ? (
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
                Sessions · {sessionLabel}
                <br />
                Compared by {compare.run.run_by_name || "—"} · {formatWhen(compare.run.run_at)}
              </p>
              {compare.run.stock_applied ? (
                <p className="text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                  Stock applied
                  {compare.run.stock_applied_at ? ` · ${formatWhen(compare.run.stock_applied_at)}` : ""}
                  {compare.run.stock_applied_by_name ? ` · ${compare.run.stock_applied_by_name}` : ""}
                  {(compare.run.barcodes_voided_count ?? 0) > 0
                    ? ` · ${compare.run.barcodes_voided_count} unique barcode(s) voided`
                    : ""}
                </p>
              ) : null}
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
          ) : null}
        </AppSectionEnter>
      </AppModalBody>
    </AppModal>
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
