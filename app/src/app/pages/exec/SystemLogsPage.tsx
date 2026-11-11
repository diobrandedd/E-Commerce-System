import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { Input } from "@/app/components/ui/input";
import { ApiError } from "@/app/lib/api";
import {
  SYSTEM_LOG_CATEGORIES,
  SYSTEM_LOG_CATEGORY_LABELS,
  systemLogsApi,
  type SystemLogCategory,
  type SystemLogRow,
} from "@/app/lib/systemLogs";
import { cn } from "@/app/components/ui/utils";

const PER_PAGE_OPTIONS = [10, 20, 50, 100] as const;

function categoryLabel(cat: string): string {
  if ((SYSTEM_LOG_CATEGORIES as readonly string[]).includes(cat)) {
    return SYSTEM_LOG_CATEGORY_LABELS[cat as SystemLogCategory];
  }
  return cat;
}

function actorLabel(row: SystemLogRow): string {
  return row.actor_name || row.actor_username || (row.actor_user_id ? `#${row.actor_user_id}` : "—");
}

/** Compact page window: always include 1, last, current ±2. */
function pageNumbers(current: number, totalPages: number): (number | "…")[] {
  if (totalPages <= 7) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }
  const set = new Set<number>([1, totalPages, current]);
  for (let i = current - 2; i <= current + 2; i++) {
    if (i >= 1 && i <= totalPages) set.add(i);
  }
  const sorted = [...set].sort((a, b) => a - b);
  const out: (number | "…")[] = [];
  for (let i = 0; i < sorted.length; i++) {
    const n = sorted[i]!;
    if (i > 0 && n - sorted[i - 1]! > 1) out.push("…");
    out.push(n);
  }
  return out;
}

export function SystemLogsPage() {
  const [rows, setRows] = useState<SystemLogRow[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [category, setCategory] = useState("");
  const [q, setQ] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [applied, setApplied] = useState({ category: "", q: "", dateFrom: "", dateTo: "" });

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await systemLogsApi.list({
        page,
        per_page: perPage,
        category: applied.category || undefined,
        q: applied.q || undefined,
        date_from: applied.dateFrom || undefined,
        date_to: applied.dateTo || undefined,
      });
      setRows(res.rows ?? []);
      setTotal(res.total ?? 0);
    } catch (e) {
      setRows([]);
      setTotal(0);
      setError(e instanceof ApiError ? e.message : "Could not load logs.");
    } finally {
      setLoading(false);
    }
  }, [page, perPage, applied]);

  useEffect(() => {
    void load();
  }, [load]);

  const totalPages = Math.max(1, Math.ceil(total / perPage) || 1);
  const rangeFrom = total === 0 ? 0 : (page - 1) * perPage + 1;
  const rangeTo = Math.min(page * perPage, total);

  const applyFilters = () => {
    setPage(1);
    setApplied({ category, q: q.trim(), dateFrom, dateTo });
  };

  const changePerPage = (next: number) => {
    setPerPage(next);
    setPage(1);
  };

  return (
    <PageLayout
      title="Logs"
      subtitle="System activity across product setup, RTS, deliveries, orders, POS, sales, inventory, and staff."
    >
      <AppSectionEnter className="space-y-4">
        {error ? <AppErrorBanner message={error} /> : null}

        <div className="flex flex-wrap gap-2 items-end rounded-xl border border-border bg-secondary/20 p-3">
          <label className="text-xs space-y-1">
            <span className="text-muted-foreground">Category</span>
            <select
              className="block border rounded-lg px-2 py-1.5 text-sm bg-background min-w-[10rem]"
              value={category}
              onChange={e => setCategory(e.target.value)}
              data-testid="system-logs-category"
            >
              <option value="">All</option>
              {SYSTEM_LOG_CATEGORIES.map(c => (
                <option key={c} value={c}>
                  {SYSTEM_LOG_CATEGORY_LABELS[c]}
                </option>
              ))}
            </select>
          </label>
          <label className="text-xs space-y-1 flex-1 min-w-[12rem]">
            <span className="text-muted-foreground">Search</span>
            <Input
              value={q}
              onChange={e => setQ(e.target.value)}
              placeholder="Summary, action, user…"
              data-testid="system-logs-search"
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  applyFilters();
                }
              }}
            />
          </label>
          <label className="text-xs space-y-1">
            <span className="text-muted-foreground">From</span>
            <Input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} />
          </label>
          <label className="text-xs space-y-1">
            <span className="text-muted-foreground">To</span>
            <Input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} />
          </label>
          <Button type="button" variant="outline" size="sm" onClick={applyFilters}>
            Apply
          </Button>
        </div>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <p className="text-xs text-muted-foreground">
            {total === 0
              ? "0 log rows"
              : `Showing ${rangeFrom}–${rangeTo} of ${total} log row${total === 1 ? "" : "s"}`}
            {` · page ${page} of ${totalPages}`}
          </p>
          <label className="text-xs flex items-center gap-2">
            <span className="text-muted-foreground">Per page</span>
            <select
              className="border rounded-lg px-2 py-1.5 text-sm bg-background"
              value={perPage}
              disabled={loading}
              onChange={e => changePerPage(Number(e.target.value))}
              data-testid="system-logs-per-page"
            >
              {PER_PAGE_OPTIONS.map(n => (
                <option key={n} value={n}>
                  {n}
                </option>
              ))}
            </select>
          </label>
        </div>

        <div className="rounded-xl border overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-secondary/40 text-xs text-muted-foreground">
              <tr>
                <th className="text-left px-3 py-2">When</th>
                <th className="text-left px-3 py-2">Who</th>
                <th className="text-left px-3 py-2">Category</th>
                <th className="text-left px-3 py-2">Action</th>
                <th className="text-left px-3 py-2">Summary</th>
                <th className="text-left px-3 py-2">Change</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-3 py-10 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={16} />
                  </td>
                </tr>
              ) : null}
              {!loading &&
                rows.map(r => (
                  <tr key={r.id} className={cn("border-t", appHoverRow)}>
                    <td className="px-3 py-2 whitespace-nowrap text-xs">{r.created_at}</td>
                    <td className="px-3 py-2">{actorLabel(r)}</td>
                    <td className="px-3 py-2">
                      <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-secondary">
                        {categoryLabel(r.category)}
                      </span>
                    </td>
                    <td className="px-3 py-2 font-mono text-xs">{r.action}</td>
                    <td className="px-3 py-2 max-w-[20rem]">
                      <span className="line-clamp-2">{r.summary}</span>
                    </td>
                    <td className="px-3 py-2 text-xs max-w-[14rem]">
                      {r.old_value != null || r.new_value != null ? (
                        <>
                          <span className="text-muted-foreground">{r.old_value ?? "—"}</span>
                          {" → "}
                          <span className="font-medium">{r.new_value ?? "—"}</span>
                        </>
                      ) : (
                        "—"
                      )}
                    </td>
                  </tr>
                ))}
              {!loading && rows.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-3 py-10 text-center text-muted-foreground text-sm">
                    No logs yet. New activity will appear here as changes are made.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>

        <div
          className="flex flex-wrap items-center justify-end gap-1"
          data-testid="system-logs-pagination"
        >
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page <= 1 || loading || total === 0}
            onClick={() => setPage(p => Math.max(1, p - 1))}
          >
            Previous
          </Button>
          {pageNumbers(page, totalPages).map((item, idx) =>
            item === "…" ? (
              <span key={`e-${idx}`} className="px-1 text-xs text-muted-foreground">
                …
              </span>
            ) : (
              <Button
                key={item}
                type="button"
                variant={item === page ? "default" : "outline"}
                size="sm"
                className={cn(
                  "min-w-8",
                  item === page ? "bg-[#C8900A] hover:bg-[#b07f09] text-white" : "",
                )}
                disabled={loading || total === 0}
                onClick={() => setPage(item)}
              >
                {item}
              </Button>
            ),
          )}
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page >= totalPages || loading || total === 0}
            onClick={() => setPage(p => Math.min(totalPages, p + 1))}
          >
            Next
          </Button>
        </div>
      </AppSectionEnter>
    </PageLayout>
  );
}
