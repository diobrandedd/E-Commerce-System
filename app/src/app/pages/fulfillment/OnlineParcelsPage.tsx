import { useCallback, useEffect, useState } from "react";
import { Loader2, Search } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import {
  OnlineParcelDetailModal,
  ParcelStatusBadgeInline,
} from "@/app/components/fulfillment/OnlineParcelDetailModal";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
  FulfillmentTableRow,
  FulfillmentToast,
  fulfillmentHoverPressablePrimary,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { cn } from "@/app/components/ui/utils";
import { pipelineLabel } from "@/app/lib/duplicateTrackingUtils";
import { parcelsList, parcelsLookup, type OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

const ROWS_PER_PAGE = 12;

function TablePagination({
  page,
  setPage,
  totalRows,
  perPage,
  noun,
}: {
  page: number;
  setPage: (fn: (p: number) => number) => void;
  totalRows: number;
  perPage: number;
  noun: string;
}) {
  if (totalRows <= 0) return null;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 pt-3">
      <p className="text-xs text-muted-foreground">
        Showing {start + 1}–{Math.min(start + perPage, totalRows)} of {totalRows} {noun}
      </p>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => setPage(p => Math.max(1, p - 1))}
          disabled={safePage <= 1}
          className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <span className="text-xs text-muted-foreground">
          Page {safePage} of {totalPages}
        </span>
        <button
          type="button"
          onClick={() => setPage(p => Math.min(totalPages, p + 1))}
          disabled={safePage >= totalPages}
          className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  );
}

function rowAccentClass(status: OnlineParcel["status"]): string {
  switch (status) {
    case "packing":
      return "border-l-4 border-l-amber-400";
    case "shipped":
      return "border-l-4 border-l-green-500";
    case "cancelled":
    case "returned":
      return "border-l-4 border-l-red-400";
    default:
      return "";
  }
}

function showToast(setter: (v: string | null) => void, message: string, ms = 2800) {
  setter(message);
  window.setTimeout(() => setter(null), ms);
}

export function OnlineParcelsPage() {
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [rows, setRows] = useState<OnlineParcel[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [lookupBusy, setLookupBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [selected, setSelected] = useState<OnlineParcel | null>(null);

  const load = useCallback(async (quiet = false) => {
    setLoading(true);
    if (!quiet) setError(null);
    try {
      const res = await parcelsList({ q, status: status || undefined, page, per_page: ROWS_PER_PAGE });
      setRows(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
      setRows([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [q, status, page]);

  useEffect(() => {
    void load(true);
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [q, status]);

  const lookup = async () => {
    if (!q.trim()) return;
    setLookupBusy(true);
    setError(null);
    try {
      const res = await parcelsLookup(q.trim());
      setSelected(res.parcel);
      showToast(setToast, `Found ${res.parcel.tracking_number}`);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Parcel not found.");
    } finally {
      setLookupBusy(false);
    }
  };

  const refresh = async () => {
    await load();
    showToast(setToast, "Parcel list refreshed");
  };

  const colSpan = 7;

  return (
    <PageLayout title="Online parcels" subtitle="Search or scan tracking to view parcel contents">
      <FulfillmentToast message={toast} />

      <FulfillmentSectionEnter className="space-y-4">
        <div className="flex flex-wrap gap-2 max-w-3xl">
          <div className="flex flex-1 min-w-[220px] gap-2">
            <input
              className="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm"
              placeholder="Tracking number or recipient…"
              value={q}
              onChange={e => setQ(e.target.value)}
              onKeyDown={e => e.key === "Enter" && void lookup()}
            />
            <button
              type="button"
              className={cn(
                "inline-flex items-center justify-center rounded-lg bg-primary px-3 py-2 text-primary-foreground disabled:opacity-50",
                fulfillmentHoverPressablePrimary,
              )}
              onClick={() => void lookup()}
              disabled={lookupBusy || !q.trim()}
              aria-label="Look up tracking"
            >
              {lookupBusy ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
            </button>
          </div>
          <select
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={status}
            onChange={e => setStatus(e.target.value)}
          >
            <option value="">All statuses</option>
            <option value="packing">Packing</option>
            <option value="shipped">Shipped</option>
            <option value="cancelled">Cancelled</option>
            <option value="returned">Returned</option>
          </select>
          <FulfillmentPressable
            variant="outline"
            className="px-2 py-2 text-[#C8900A] border-transparent shadow-none hover:border-transparent hover:bg-[#C8900A]/10"
            onClick={() => void refresh()}
          >
            Refresh
          </FulfillmentPressable>
        </div>

        <FulfillmentErrorBanner message={error} />

        <div className="px-1 pb-1">
          <h3 className="text-sm font-semibold text-foreground">Parcels</h3>
          <p className="text-xs text-muted-foreground mt-0.5">Click a row to view full parcel details</p>
        </div>

        <div className="bg-card rounded-xl border overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                <th className="text-left px-4 py-3">Tracking</th>
                <th className="text-left px-4 py-3">Recipient</th>
                <th className="text-left px-4 py-3">Courier</th>
                <th className="text-left px-4 py-3">Pipeline</th>
                <th className="text-left px-4 py-3">Items</th>
                <th className="text-left px-4 py-3">Status</th>
                <th className="text-left px-4 py-3">Ship date</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={colSpan} className="py-10 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={16} />
                  </td>
                </tr>
              )}
              {!loading && rows.length === 0 && (
                <tr>
                  <td colSpan={colSpan} className="py-10 text-center text-sm text-muted-foreground">
                    No parcels match your filters.
                  </td>
                </tr>
              )}
              {!loading &&
                rows.map((r, i) => (
                  <FulfillmentTableRow
                    key={r.id}
                    index={i}
                    className={`border-b cursor-pointer ${rowAccentClass(r.status)}`}
                    onClick={() => setSelected(r)}
                  >
                    <td className="px-4 py-3 font-mono text-xs">{r.tracking_number}</td>
                    <td className="px-4 py-3">{r.recipient_name || "—"}</td>
                    <td className="px-4 py-3">{r.courier || "—"}</td>
                    <td className="px-4 py-3">{pipelineLabel(r.source_pipeline)}</td>
                    <td className="px-4 py-3 max-w-[14rem] truncate" title={r.items_raw}>
                      {r.items_raw || "—"}
                    </td>
                    <td className="px-4 py-3">
                      <ParcelStatusBadgeInline status={r.status} />
                    </td>
                    <td className="px-4 py-3 text-xs text-muted-foreground">{r.ship_date ?? "—"}</td>
                  </FulfillmentTableRow>
                ))}
            </tbody>
          </table>
        </div>

        {!loading && (
          <TablePagination
            page={page}
            setPage={setPage}
            totalRows={total}
            perPage={ROWS_PER_PAGE}
            noun="parcels"
          />
        )}
      </FulfillmentSectionEnter>

      <OnlineParcelDetailModal parcel={selected} onClose={() => setSelected(null)} />
    </PageLayout>
  );
}
