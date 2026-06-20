import { useCallback, useEffect, useState } from "react";
import { AlertTriangle, ClipboardList, Loader2, Package } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import {
  formatStagingQty,
  packingDamageList,
  packingIssuesList,
  type PackingDamageRow,
  type PackingIssueRow,
} from "@/app/lib/fulfillmentPackingApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

const PER_PAGE = 10;

function TablePagination({
  page,
  setPage,
  totalRows,
  perPage,
  label,
}: {
  page: number;
  setPage: (fn: (p: number) => number) => void;
  totalRows: number;
  perPage: number;
  label: string;
}) {
  if (totalRows <= 0) return null;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-border bg-secondary/20">
      <p className="text-xs text-muted-foreground">
        Showing {start + 1}–{Math.min(start + perPage, totalRows)} of {totalRows} {label}
      </p>
      <div className="flex items-center gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="text-xs"
          onClick={() => setPage((p) => Math.max(1, p - 1))}
          disabled={safePage <= 1}
        >
          Previous
        </Button>
        <span className="text-xs text-muted-foreground min-w-[5.5rem] text-center">
          Page {safePage} of {totalPages}
        </span>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="text-xs"
          onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
          disabled={safePage >= totalPages}
        >
          Next
        </Button>
      </div>
    </div>
  );
}

function TakeOutCard({ issue }: { issue: PackingIssueRow }) {
  return (
    <li className="rounded-xl border border-border p-4 text-sm bg-card">
      <div className="flex flex-wrap justify-between gap-2 mb-2">
        <span className="font-semibold flex items-center gap-1.5">
          <Package className="h-3.5 w-3.5 text-muted-foreground" />
          Take-out #{issue.id}
        </span>
        <span className="text-xs text-muted-foreground">
          {issue.issued_by_name ?? "—"} · {new Date(issue.created_at).toLocaleString()}
        </span>
      </div>
      {issue.est_parcels_typical != null && (
        <p className="text-xs text-amber-800 bg-amber-50 rounded-lg px-2 py-1 mb-2 inline-block">
          ~{issue.est_parcels_typical} parcels
          {issue.est_parcels_label ? ` (${issue.est_parcels_label})` : ""}
        </p>
      )}
      <ul className="text-xs text-muted-foreground space-y-0.5">
        {issue.lines.map((l, i) => (
          <li key={i}>
            {l.stock_name}: {formatStagingQty(l.qty)} {l.unit}
            {l.roll_qty != null ? ` (${l.roll_qty} roll${l.roll_qty === 1 ? "" : "s"})` : ""}
            {l.bubble_width_cm ? ` · ${l.bubble_width_cm} cm` : ""}
          </li>
        ))}
      </ul>
      {issue.note && <p className="text-xs mt-2 italic text-muted-foreground">{issue.note}</p>}
    </li>
  );
}

export function PackingTakeOutsPanel({ refreshKey }: { refreshKey?: number }) {
  const [page, setPage] = useState(1);
  const [issues, setIssues] = useState<PackingIssueRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await packingIssuesList({ page, per_page: PER_PAGE });
      setIssues(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setLoading(false);
    }
  }, [page, refreshKey]);

  useEffect(() => {
    void load();
  }, [load]);

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <AppSectionEnter>
      {error && <AppErrorBanner message={error} className="mb-4" />}
      <div className="rounded-2xl border border-border overflow-hidden">
        {issues.length === 0 ? (
          <p className="text-sm text-muted-foreground p-6">No take-outs yet.</p>
        ) : (
          <ul className="space-y-3 p-4">
            {issues.map((issue) => (
              <TakeOutCard key={issue.id} issue={issue} />
            ))}
          </ul>
        )}
        <TablePagination page={page} setPage={setPage} totalRows={total} perPage={PER_PAGE} label="take-outs" />
      </div>
    </AppSectionEnter>
  );
}

export function PackingDamageLogPanel({ refreshKey }: { refreshKey?: number }) {
  const [page, setPage] = useState(1);
  const [damageRows, setDamageRows] = useState<PackingDamageRow[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await packingDamageList({ page, per_page: PER_PAGE });
      setDamageRows(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setLoading(false);
    }
  }, [page, refreshKey]);

  useEffect(() => {
    void load();
  }, [load]);

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <AppSectionEnter>
      {error && <AppErrorBanner message={error} className="mb-4" />}
      <div className="rounded-2xl border border-border overflow-hidden">
        {damageRows.length === 0 ? (
          <p className="text-sm text-muted-foreground p-6">No damage reports yet.</p>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
              <tr>
                <th className="px-4 py-3 font-semibold">When</th>
                <th className="px-4 py-3 font-semibold">SKU</th>
                <th className="px-4 py-3 font-semibold">Qty</th>
                <th className="px-4 py-3 font-semibold">Note</th>
                <th className="px-4 py-3 font-semibold">Reported by</th>
              </tr>
            </thead>
            <tbody>
              {damageRows.map((row) => (
                <tr key={row.id} className={cn("border-t border-border", appHoverRow)}>
                  <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                    {new Date(row.created_at).toLocaleString()}
                  </td>
                  <td className="px-4 py-3 font-medium">{row.stock_name}</td>
                  <td className="px-4 py-3">
                    {formatStagingQty(row.qty)} {row.unit}
                    {row.roll_qty != null ? (
                      <span className="text-xs text-muted-foreground ml-1">
                        ({row.roll_qty} roll{row.roll_qty === 1 ? "" : "s"})
                      </span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 text-xs text-muted-foreground max-w-[14rem] truncate">
                    {row.note}
                  </td>
                  <td className="px-4 py-3 text-xs">{row.reported_by_name ?? "—"}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <TablePagination page={page} setPage={setPage} totalRows={total} perPage={PER_PAGE} label="reports" />
      </div>
    </AppSectionEnter>
  );
}