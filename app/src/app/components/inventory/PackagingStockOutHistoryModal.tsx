import { useCallback, useEffect, useState } from "react";
import { History, Loader2, Search } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useDebouncedValue } from "@/app/hooks/useDebouncedValue";
import { ApiError } from "@/app/lib/api";
import {
  fetchPackagingStockOutHistory,
  type PackagingStockOutHistoryActor,
  type PackagingStockOutHistoryRow,
} from "@/app/lib/materials";
import { cn } from "@/app/components/ui/utils";

const PER_PAGE = 50;

const SEARCH_INPUT_CLASS =
  "w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border-2 border-border bg-card " +
  "focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-shadow";

const FILTER_INPUT_CLASS =
  "min-h-10 rounded-lg border-2 border-border bg-card px-3 text-sm outline-none " +
  "focus:border-primary focus:ring-4 focus:ring-primary/10";

function formatWhen(raw: string): string {
  if (!raw) return "—";
  const d = new Date(raw.replace(" ", "T"));
  if (!Number.isFinite(d.getTime())) return raw;
  return d.toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

interface PackagingStockOutHistoryModalProps {
  open: boolean;
  onClose: () => void;
}

export function PackagingStockOutHistoryModal({ open, onClose }: PackagingStockOutHistoryModalProps) {
  const [rows, setRows] = useState<PackagingStockOutHistoryRow[]>([]);
  const [actors, setActors] = useState<PackagingStockOutHistoryActor[]>([]);
  const [q, setQ] = useState("");
  const debouncedQ = useDebouncedValue(q, 300);
  const [userId, setUserId] = useState(0);
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!open) return;
    setLoading(true);
    setError(null);
    try {
      const data = await fetchPackagingStockOutHistory({
        q: debouncedQ.trim() || undefined,
        user_id: userId > 0 ? userId : undefined,
        from: from || undefined,
        to: to || undefined,
        page,
        per_page: PER_PAGE,
      });
      setRows(data.rows);
      setTotal(data.total);
      setActors(data.actors);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load stock-out history.");
      setRows([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [open, debouncedQ, userId, from, to, page]);

  useEffect(() => {
    if (!open) return;
    void load();
  }, [open, load]);

  useEffect(() => {
    if (!open) return;
    setPage(1);
  }, [open, debouncedQ, userId, from, to]);

  useEffect(() => {
    if (open) return;
    setQ("");
    setUserId(0);
    setFrom("");
    setTo("");
    setPage(1);
    setRows([]);
    setError(null);
  }, [open]);

  if (!open) return null;

  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
  const safePage = Math.min(page, totalPages);

  return (
    <AppModal
      open
      onClose={onClose}
      panelClassName="max-w-4xl w-full max-h-[90vh] flex flex-col"
      labelledBy="packaging-stock-out-history-title"
    >
      <AppModalHeader
        title="Stock-out history"
        titleId="packaging-stock-out-history-title"
        subtitle="Scan and manual warehouse stock-outs · who deducted what"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-hidden gap-3 !overflow-y-hidden">
        {error && <AppErrorBanner message={error} />}

        <div className="shrink-0 space-y-2.5">
          <div className="relative min-w-0">
            <Search
              size={15}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none"
              aria-hidden
            />
            <input
              type="search"
              value={q}
              onChange={e => setQ(e.target.value)}
              placeholder="Search item, reason, or person…"
              className={SEARCH_INPUT_CLASS}
              aria-label="Search stock-out history"
            />
          </div>

          <div className="flex flex-col sm:flex-row flex-wrap gap-2">
            <div className="flex gap-2 flex-1 min-w-0">
              <label className="sr-only" htmlFor="packaging-history-modal-from">From date</label>
              <input
                id="packaging-history-modal-from"
                type="date"
                value={from}
                onChange={e => setFrom(e.target.value)}
                className={cn(FILTER_INPUT_CLASS, "flex-1 min-w-0")}
              />
              <label className="sr-only" htmlFor="packaging-history-modal-to">To date</label>
              <input
                id="packaging-history-modal-to"
                type="date"
                value={to}
                onChange={e => setTo(e.target.value)}
                className={cn(FILTER_INPUT_CLASS, "flex-1 min-w-0")}
              />
            </div>
            <label className="sr-only" htmlFor="packaging-history-modal-person">Person</label>
            <select
              id="packaging-history-modal-person"
              value={userId}
              onChange={e => setUserId(Number(e.target.value))}
              className={cn(FILTER_INPUT_CLASS, "sm:w-52")}
            >
              <option value={0}>All people</option>
              {actors.map(a => (
                <option key={a.id} value={a.id}>{a.name}</option>
              ))}
            </select>
          </div>
        </div>

        <div className="rounded-xl border border-border overflow-hidden flex flex-col min-h-0 flex-1 bg-secondary/10">
          <div className="hidden sm:grid sm:grid-cols-[8.5rem_minmax(0,1.1fr)_5.5rem_minmax(0,1fr)_7rem] gap-2 px-3 py-2 border-b border-border bg-secondary/35 text-[10px] uppercase tracking-wide text-muted-foreground font-medium shrink-0">
            <span>When</span>
            <span>Item</span>
            <span>Qty</span>
            <span>Reason</span>
            <span>Who</span>
          </div>

          <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain max-h-[min(50vh,28rem)]">
            {loading && (
              <div className="py-12 text-center space-y-2">
                <Loader2 className="inline animate-spin text-muted-foreground" size={20} />
                <p className="text-xs text-muted-foreground">Loading history…</p>
              </div>
            )}

            {!loading && rows.length === 0 && (
              <div className="py-12 px-6 text-center space-y-2">
                <History className="mx-auto text-muted-foreground/50" size={28} strokeWidth={1.5} />
                <p className="text-sm font-medium text-foreground">No stock-outs yet</p>
                <p className="text-xs text-muted-foreground">
                  Scan or manual deducts will show up here.
                </p>
              </div>
            )}

            {!loading && rows.length > 0 && (
              <>
                <div className="sm:hidden divide-y divide-border">
                  {rows.map(row => (
                    <div key={row.id} className="px-3 py-3 space-y-1.5">
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 space-y-0.5">
                          <p className="font-medium text-sm text-foreground leading-snug break-words">
                            {row.stock_name}
                          </p>
                          <p className="text-xs text-muted-foreground">{formatWhen(row.created_at)}</p>
                        </div>
                        <p className="shrink-0 text-sm font-medium tabular-nums text-foreground">
                          {row.qty.toLocaleString()}{" "}
                          <span className="text-muted-foreground font-normal">{row.unit}</span>
                        </p>
                      </div>
                      <p className="text-xs text-foreground leading-relaxed">
                        <span className="text-muted-foreground">Reason · </span>
                        {row.reason || "—"}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        By {row.entered_by_name ?? "Unknown"}
                      </p>
                    </div>
                  ))}
                </div>

                <div className="hidden sm:block">
                  {rows.map(row => (
                    <div
                      key={row.id}
                      className="border-b border-border last:border-b-0 px-3 py-2 grid grid-cols-[8.5rem_minmax(0,1.1fr)_5.5rem_minmax(0,1fr)_7rem] gap-2 items-start"
                    >
                      <p className="text-xs text-muted-foreground leading-snug">{formatWhen(row.created_at)}</p>
                      <p className="text-sm font-medium text-foreground leading-snug break-words">{row.stock_name}</p>
                      <p className="text-sm tabular-nums text-foreground">
                        {row.qty.toLocaleString()}{" "}
                        <span className="text-muted-foreground">{row.unit}</span>
                      </p>
                      <p className="text-sm text-foreground leading-snug break-words">{row.reason || "—"}</p>
                      <p className="text-sm text-muted-foreground leading-snug">
                        {row.entered_by_name ?? "Unknown"}
                      </p>
                    </div>
                  ))}
                </div>
              </>
            )}
          </div>
        </div>
      </AppModalBody>
      <AppModalFooter className="justify-between gap-3">
        <p className="text-xs text-muted-foreground tabular-nums">
          {total > 0 ? `${total.toLocaleString()} stock-out${total === 1 ? "" : "s"}` : "\u00a0"}
        </p>
        <div className="flex items-center gap-2">
          {total > 0 && (
            <>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={safePage <= 1 || loading}
                onClick={() => setPage(p => Math.max(1, p - 1))}
              >
                Prev
              </Button>
              <span className="text-xs text-muted-foreground tabular-nums">
                {safePage} / {totalPages}
              </span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={safePage >= totalPages || loading}
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
              >
                Next
              </Button>
            </>
          )}
          <Button type="button" variant="outline" onClick={onClose}>
            Close
          </Button>
        </div>
      </AppModalFooter>
    </AppModal>
  );
}
