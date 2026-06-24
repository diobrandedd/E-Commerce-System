import { useCallback, useEffect, useMemo, useState } from "react";
import { BookOpen, Download, Loader2, Search } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { OnlineParcelsGuideModal } from "@/app/components/fulfillment/OnlineParcelsGuideModal";
import { AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { runOnlineParcelsTour } from "@/app/components/tour/guides/onlineParcelsTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { useAuth } from "@/app/context/AuthContext";
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
import { partnerSellerLabel, pipelineLabel } from "@/app/lib/duplicateTrackingUtils";
import {
  parcelsExportSelected,
  parcelsFilterOptions,
  parcelsList,
  parcelsLookup,
  triggerParcelsExportDownload,
  type OnlineParcel,
  type ParcelsListSort,
} from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

const PAGE_SIZE_PRESETS = [1, 5, 10, 25] as const;

function formatCreatedAt(iso: string): string {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString(undefined, { month: "short", day: "numeric", year: "numeric" });
}

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
    case "on_hold":
      return "border-l-4 border-l-violet-400";
    case "cancelled":
    case "returned":
      return "border-l-4 border-l-red-400";
    case "rts":
      return "border-l-4 border-l-orange-500";
    default:
      return "";
  }
}

function showToast(setter: (v: string | null) => void, message: string, ms = 2800) {
  setter(message);
  window.setTimeout(() => setter(null), ms);
}

export function OnlineParcelsPage() {
  const { user } = useAuth();
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [partnerSeller, setPartnerSeller] = useState("");
  const [sourcePipeline, setSourcePipeline] = useState("");
  const [courier, setCourier] = useState("");
  const [sort, setSort] = useState<ParcelsListSort>("created_at");
  const [sortDir, setSortDir] = useState<"asc" | "desc">("desc");
  const [perPage, setPerPage] = useState(10);
  const [customPerPage, setCustomPerPage] = useState("");
  const [rows, setRows] = useState<OnlineParcel[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [exportBusy, setExportBusy] = useState(false);
  const [lookupBusy, setLookupBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [exportNotice, setExportNotice] = useState<string | null>(null);
  const [selected, setSelected] = useState<OnlineParcel | null>(null);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const [courierOptions, setCourierOptions] = useState<string[]>([]);
  const [guideOpen, setGuideOpen] = useState(false);

  const effectivePerPage = useMemo(() => {
    const custom = parseInt(customPerPage, 10);
    if (customPerPage.trim() !== "" && !Number.isNaN(custom)) {
      return Math.min(100, Math.max(1, custom));
    }
    return perPage;
  }, [perPage, customPerPage]);

  useEffect(() => {
    void parcelsFilterOptions()
      .then(opts => setCourierOptions(opts.couriers))
      .catch(() => setCourierOptions([]));
  }, []);

  const load = useCallback(async (quiet = false) => {
    setLoading(true);
    if (!quiet) setError(null);
    try {
      const res = await parcelsList({
        q,
        status: status || undefined,
        page,
        per_page: effectivePerPage,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        partner_seller: partnerSeller || undefined,
        source_pipeline: sourcePipeline || undefined,
        courier: courier || undefined,
        sort,
        dir: sortDir,
      });
      setRows(res.rows);
      setTotal(res.total);
      setSelectedIds(prev => {
        const next = new Set<number>();
        for (const r of res.rows) {
          if (prev.has(r.id)) next.add(r.id);
        }
        return next;
      });
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
      setRows([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [
    q,
    status,
    page,
    effectivePerPage,
    dateFrom,
    dateTo,
    partnerSeller,
    sourcePipeline,
    courier,
    sort,
    sortDir,
  ]);

  useEffect(() => {
    void load(true);
  }, [load]);

  useEffect(() => {
    setPage(1);
  }, [q, status, dateFrom, dateTo, partnerSeller, sourcePipeline, courier, sort, sortDir, effectivePerPage]);

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

  const toggleRow = (id: number) => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const pageIds = rows.map(r => r.id);
  const allPageSelected = pageIds.length > 0 && pageIds.every(id => selectedIds.has(id));

  const toggleAllPage = () => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (allPageSelected) {
        for (const id of pageIds) next.delete(id);
      } else {
        for (const id of pageIds) next.add(id);
      }
      return next;
    });
  };

  const exportSelected = async () => {
    const ids = Array.from(selectedIds);
    if (!ids.length) return;
    setExportBusy(true);
    setError(null);
    try {
      const res = await parcelsExportSelected(ids);
      triggerParcelsExportDownload(res.download_token);
      setExportNotice(`Exported ${res.row_count} row${res.row_count === 1 ? "" : "s"} to Excel`);
      window.setTimeout(() => setExportNotice(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Export failed.");
    } finally {
      setExportBusy(false);
    }
  };

  const toggleSort = (col: ParcelsListSort) => {
    if (sort === col) {
      setSortDir(d => (d === "asc" ? "desc" : "asc"));
    } else {
      setSort(col);
      setSortDir(col === "created_at" ? "desc" : "asc");
    }
  };

  const sortIndicator = (col: ParcelsListSort) => {
    if (sort !== col) return "";
    return sortDir === "asc" ? " ↑" : " ↓";
  };

  const colSpan = 10;

  return (
    <PageLayout
      title="Online parcels"
      subtitle="Search, filter, and export stamped online orders"
      actions={
        <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <FulfillmentToast message={toast} />
      <AppSuccessNotice message={exportNotice} />

      <FulfillmentSectionEnter className="space-y-4">
        <div className="flex flex-wrap gap-2 max-w-5xl" data-tour="online-parcels-filters">
          <div className="flex flex-1 min-w-[220px] gap-2">
            <input
              className="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm"
              placeholder="Tracking, order no., recipient…"
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
          <input
            type="date"
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={dateFrom}
            onChange={e => setDateFrom(e.target.value)}
            title="From date"
          />
          <input
            type="date"
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={dateTo}
            onChange={e => setDateTo(e.target.value)}
            title="To date"
          />
          <select
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={partnerSeller}
            onChange={e => setPartnerSeller(e.target.value)}
          >
            <option value="">All partners</option>
            <option value="ian">Ian</option>
            <option value="jono">Jono</option>
            <option value="joven">Joven</option>
          </select>
          <select
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={sourcePipeline}
            onChange={e => setSourcePipeline(e.target.value)}
          >
            <option value="">All pipelines</option>
            <option value="partner">Partner</option>
            <option value="vip">VIP</option>
            <option value="shopee_spx">Shopee SPX</option>
            <option value="tiktok">TikTok</option>
          </select>
          <select
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm max-w-[12rem]"
            value={courier}
            onChange={e => setCourier(e.target.value)}
          >
            <option value="">All couriers</option>
            {courierOptions.map(c => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </select>
          <select
            className="rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={status}
            onChange={e => setStatus(e.target.value)}
          >
            <option value="">All statuses</option>
            <option value="packing">Packing</option>
            <option value="shipped">Shipped</option>
            <option value="on_hold">On hold</option>
            <option value="cancelled">Cancelled</option>
            <option value="returned">Returned</option>
            <option value="rts">RTS</option>
          </select>
          <FulfillmentPressable
            variant="outline"
            className="px-2 py-2 text-[#C8900A] border-transparent shadow-none hover:border-transparent hover:bg-[#C8900A]/10"
            onClick={() => void refresh()}
          >
            Refresh
          </FulfillmentPressable>
        </div>

        <div className="flex flex-wrap items-center gap-3 px-1">
          <div className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
            <span>Rows per page:</span>
            {PAGE_SIZE_PRESETS.map(n => (
              <button
                key={n}
                type="button"
                onClick={() => {
                  setPerPage(n);
                  setCustomPerPage("");
                }}
                className={cn(
                  "px-2 py-1 rounded-md border",
                  effectivePerPage === n && customPerPage.trim() === ""
                    ? "border-primary bg-primary/10 text-primary font-medium"
                    : "border-border hover:bg-secondary",
                )}
              >
                {n}
              </button>
            ))}
            <input
              type="number"
              min={1}
              max={100}
              placeholder="Custom"
              value={customPerPage}
              onChange={e => setCustomPerPage(e.target.value)}
              className="w-16 rounded-md border border-border bg-background px-2 py-1 text-xs"
            />
          </div>
          <FulfillmentPressable
            variant="primary"
            className="inline-flex items-center gap-2 text-sm ml-auto"
            disabled={exportBusy || selectedIds.size === 0}
            onClick={() => void exportSelected()}
          >
            {exportBusy ? <Loader2 size={14} className="animate-spin" /> : <Download size={14} />}
            Export selected ({selectedIds.size})
          </FulfillmentPressable>
        </div>

        <FulfillmentErrorBanner message={error} />

        <div className="px-1 pb-1">
          <h3 className="text-sm font-semibold text-foreground">Parcels</h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            Click a row for details — use checkboxes to export a subset to Excel
          </p>
        </div>

        <div className="bg-card rounded-xl border overflow-x-auto" data-tour="online-parcels-table">
          <table className="w-full text-sm min-w-[960px]">
            <thead>
              <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                <th className="px-3 py-3 w-10">
                  <input
                    type="checkbox"
                    checked={allPageSelected}
                    onChange={toggleAllPage}
                    aria-label="Select all on page"
                    className="rounded border-border size-3.5"
                  />
                </th>
                <th className="text-left px-3 py-3">
                  <button type="button" className="hover:text-foreground" onClick={() => toggleSort("order_no")}>
                    Order No.{sortIndicator("order_no")}
                  </button>
                </th>
                <th className="text-left px-3 py-3">
                  <button type="button" className="hover:text-foreground" onClick={() => toggleSort("tracking_number")}>
                    Tracking{sortIndicator("tracking_number")}
                  </button>
                </th>
                <th className="text-left px-3 py-3">
                  <button type="button" className="hover:text-foreground" onClick={() => toggleSort("recipient_name")}>
                    Recipient{sortIndicator("recipient_name")}
                  </button>
                </th>
                <th className="text-left px-3 py-3">
                  <button type="button" className="hover:text-foreground" onClick={() => toggleSort("courier")}>
                    Courier{sortIndicator("courier")}
                  </button>
                </th>
                <th className="text-left px-3 py-3">Partner</th>
                <th className="text-left px-3 py-3">Pipeline</th>
                <th className="text-left px-3 py-3">Items</th>
                <th className="text-left px-3 py-3" data-tour="online-parcels-status-col">
                  Status
                </th>
                <th className="text-left px-3 py-3">
                  <button type="button" className="hover:text-foreground" onClick={() => toggleSort("created_at")}>
                    Created{sortIndicator("created_at")}
                  </button>
                </th>
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
                    <td className="px-3 py-3" onClick={e => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={selectedIds.has(r.id)}
                        onChange={() => toggleRow(r.id)}
                        aria-label={`Select ${r.tracking_number}`}
                        className="rounded border-border size-3.5"
                      />
                    </td>
                    <td className="px-3 py-3 font-mono text-xs">{r.order_no || "—"}</td>
                    <td className="px-3 py-3 font-mono text-xs">{r.tracking_number}</td>
                    <td className="px-3 py-3">{r.recipient_name || "—"}</td>
                    <td className="px-3 py-3 max-w-[10rem] truncate" title={r.courier}>
                      {r.courier || "—"}
                    </td>
                    <td className="px-3 py-3 text-xs">{partnerSellerLabel(r.partner_seller)}</td>
                    <td className="px-3 py-3">{pipelineLabel(r.source_pipeline)}</td>
                    <td className="px-3 py-3 max-w-[12rem] truncate" title={r.items_raw}>
                      {r.items_raw || "—"}
                    </td>
                    <td className="px-3 py-3">
                      <ParcelStatusBadgeInline status={r.status} />
                    </td>
                    <td className="px-3 py-3 text-xs text-muted-foreground">{formatCreatedAt(r.created_at)}</td>
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
            perPage={effectivePerPage}
            noun="parcels"
          />
        )}
      </FulfillmentSectionEnter>

      <OnlineParcelDetailModal
        parcel={selected}
        role={user?.role}
        onClose={() => setSelected(null)}
        onUpdated={updated => {
          setSelected(updated);
          setRows(prev => prev.map(r => (r.id === updated.id ? updated : r)));
        }}
      />

      <OnlineParcelsGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        role={user?.role}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runOnlineParcelsTour(user?.role));
        }}
      />
    </PageLayout>
  );
}
