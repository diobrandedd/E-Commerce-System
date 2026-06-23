import { useCallback, useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router";
import { ChevronDown, ChevronRight, Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import {
  RtsCaseMediaGallery,
  RtsCaseMediaTableCells,
  RTS_MEDIA_TABLE_HEADERS,
} from "@/app/components/rts/RtsCaseMediaUi";
import {
  FulfillmentErrorBanner,
  FulfillmentToast,
  fulfillmentHoverRow,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { cn } from "@/app/components/ui/utils";
import { useAuth } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";
import {
  rtsGetCase,
  rtsGetCatalog,
  rtsListCases,
  rtsUpdateClaim,
  rtsVoidCase,
  type RtsCaseRow,
  type RtsLineItem,
  type RtsProductCatalog,
} from "@/app/lib/rtsApi";
import { rtsFormatLines } from "@/app/lib/rtsProducts";

const PER_PAGE = 10;

function TablePagination({
  page,
  setPage,
  totalRows,
  perPage,
}: {
  page: number;
  setPage: (fn: (p: number) => number) => void;
  totalRows: number;
  perPage: number;
}) {
  if (totalRows <= 0) return null;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-border bg-secondary/20">
      <p className="text-xs text-muted-foreground">
        Showing {start + 1}–{Math.min(start + perPage, totalRows)} of {totalRows} cases
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
        <span className="text-xs text-muted-foreground min-w-[5.5rem] text-center">
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

const inputClass =
  "w-full rounded-lg border-2 border-border bg-card text-sm px-3 py-2 focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 outline-none";
const filterInputClass =
  "w-full rounded-md border border-border bg-background text-xs px-2 py-1.5 focus:border-[#C8900A] focus:ring-2 focus:ring-[#C8900A]/10 outline-none";
const labelClass = "text-xs font-medium text-muted-foreground uppercase tracking-wide";
const filterLabelClass = "text-[10px] font-medium text-muted-foreground uppercase tracking-wide leading-none";

function ClaimBadge({ row, catalog }: { row: RtsCaseRow; catalog: RtsProductCatalog | null }) {
  if (row.voided_at) {
    return <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-800">Voided</span>;
  }
  const label = catalog?.claim_statuses.find(s => s.value === row.claim_status)?.label ?? row.claim_status ?? "—";
  const tone =
    row.claim_status === "approved_claims"
      ? "bg-green-100 text-green-800"
      : row.claim_status === "submitted_claims" || row.claim_status === "on_process"
        ? "bg-blue-100 text-blue-800"
        : "bg-amber-100 text-amber-900";
  return <span className={cn("inline-block text-xs font-semibold px-2 py-0.5 rounded-full", tone)}>{label}</span>;
}

function DamageBadge({ row }: { row: RtsCaseRow }) {
  const disp = row.case_disposition;
  if (disp === "swapped") {
    return <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-900">Swapped</span>;
  }
  if (disp === "leak") {
    return <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-900">Leak</span>;
  }
  if (!row.damaged_reason && !row.damaged_tag) {
    return <span className="text-xs text-muted-foreground">No</span>;
  }
  return (
    <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900">
      {row.damaged_tag ?? "Damaged"}
    </span>
  );
}

function LineCountBadge({ count }: { count: number }) {
  if (count > 0) {
    return <span className="text-xs font-medium text-foreground">{count} line{count === 1 ? "" : "s"}</span>;
  }
  return (
    <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900" title="Add product lines before sync">
      No lines
    </span>
  );
}

export function RTSRecordsPage() {
  const { user } = useAuth();
  const canAdmin = user?.role === "executive" || user?.role === "prod_manager";
  const [searchParams] = useSearchParams();

  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [cases, setCases] = useState<RtsCaseRow[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [filtersOpen, setFiltersOpen] = useState(() => searchParams.toString().length > 0);

  const [q, setQ] = useState(() => searchParams.get("q") ?? "");
  const [shop, setShop] = useState(() => searchParams.get("shop") ?? "");
  const [courier, setCourier] = useState(() => searchParams.get("courier") ?? "");
  const [vipOnly, setVipOnly] = useState(() => searchParams.get("vip_only") === "1");
  const [dateFrom, setDateFrom] = useState(() => searchParams.get("date_from") ?? "");
  const [dateTo, setDateTo] = useState(() => searchParams.get("date_to") ?? "");
  const [delFrom, setDelFrom] = useState(() => searchParams.get("del_from") ?? "");
  const [delTo, setDelTo] = useState(() => searchParams.get("del_to") ?? "");
  const [damage, setDamage] = useState(() => searchParams.get("damage") ?? "");
  const [voidOnly, setVoidOnly] = useState(() => searchParams.get("void_only") === "1");

  const [detail, setDetail] = useState<RtsCaseRow | null>(null);
  const [detailLines, setDetailLines] = useState<string[]>([]);
  const [detailLoading, setDetailLoading] = useState(false);
  const [claimStatus, setClaimStatus] = useState("for_submission");
  const [claimNote, setClaimNote] = useState("");
  const [voidConfirm, setVoidConfirm] = useState(false);
  const [busy, setBusy] = useState(false);

  const activeFilterCount = [
    q,
    shop,
    courier,
    vipOnly,
    dateFrom,
    dateTo,
    delFrom,
    delTo,
    damage,
    voidOnly,
  ].filter(Boolean).length;

  useEffect(() => {
    void rtsGetCatalog().then(setCatalog).catch(() => null);
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await rtsListCases({
        q: q || undefined,
        shop: shop || undefined,
        courier: courier || undefined,
        vip_only: vipOnly ? 1 : undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        del_from: delFrom || undefined,
        del_to: delTo || undefined,
        damage: damage || undefined,
        void_only: voidOnly ? 1 : undefined,
        page,
        per_page: PER_PAGE,
      });
      setCases(res.cases);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load records.");
    } finally {
      setLoading(false);
    }
  }, [q, shop, courier, vipOnly, dateFrom, dateTo, delFrom, delTo, damage, voidOnly, page]);

  useEffect(() => {
    void load();
  }, [load]);

  const clearFilters = () => {
    setQ("");
    setShop("");
    setCourier("");
    setVipOnly(false);
    setDateFrom("");
    setDateTo("");
    setDelFrom("");
    setDelTo("");
    setDamage("");
    setVoidOnly(false);
    setPage(1);
  };

  const applyFilters = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    void load();
  };

  const openDetail = async (row: RtsCaseRow) => {
    setDetail(row);
    setClaimStatus(row.claim_status ?? "for_submission");
    setClaimNote(row.claim_status_note ?? "");
    setDetailLoading(true);
    try {
      const data = await rtsGetCase(row.tracking_number);
      if (data.case) {
        setDetail({ ...row, ...data.case });
      }
      if (catalog && data.lines.length) {
        setDetailLines(rtsFormatLines(data.lines as RtsLineItem[], catalog));
      } else if (data.lines.length) {
        setDetailLines(data.lines.map(l => `${l.quantity}× ${l.product_type}`));
      } else {
        setDetailLines([]);
      }
    } catch {
      setDetailLines([]);
    } finally {
      setDetailLoading(false);
    }
  };

  const saveClaim = async () => {
    if (!detail || !canAdmin) return;
    setBusy(true);
    try {
      await rtsUpdateClaim(detail.id, claimStatus, claimNote);
      setToast("Claim status saved");
      window.setTimeout(() => setToast(null), 2500);
      setDetail({ ...detail, claim_status: claimStatus, claim_status_note: claimNote });
      void load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save claim status.");
    } finally {
      setBusy(false);
    }
  };

  const doVoid = async () => {
    if (!detail || !canAdmin) return;
    setBusy(true);
    try {
      await rtsVoidCase(detail.id);
      setVoidConfirm(false);
      setDetail(null);
      setToast("Record voided");
      window.setTimeout(() => setToast(null), 2500);
      void load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Void failed.");
    } finally {
      setBusy(false);
    }
  };

  const docBase = catalog?.doc_url_base ?? "";

  return (
    <PageLayout
      title="RTS Records"
      subtitle="Operational case list — click a row for details"
    >
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={error} />

      <div className="mb-3 rounded-lg border border-border bg-card overflow-hidden">
        <button
          type="button"
          onClick={() => setFiltersOpen(v => !v)}
          className="w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold text-foreground hover:bg-secondary/40 transition-colors"
        >
          <span>
            Filters
            {activeFilterCount > 0 && (
              <span className="ml-1.5 text-[10px] font-medium text-[#C8900A]">({activeFilterCount})</span>
            )}
          </span>
          <ChevronDown size={14} className={cn("text-muted-foreground transition-transform", filtersOpen && "rotate-180")} />
        </button>

        {filtersOpen && (
          <form onSubmit={applyFilters} className="px-3 pb-2.5 pt-2 border-t border-border space-y-2">
            <div className="flex flex-wrap items-end gap-2">
              <label className="flex-1 min-w-[140px] max-w-xs space-y-0.5">
                <span className={filterLabelClass}>Tracking</span>
                <input
                  value={q}
                  onChange={e => setQ(e.target.value)}
                  placeholder="Search…"
                  className={filterInputClass}
                />
              </label>
              <label className="w-[7.5rem] space-y-0.5">
                <span className={filterLabelClass}>Shop</span>
                <select value={shop} onChange={e => setShop(e.target.value)} className={filterInputClass}>
                  <option value="">All</option>
                  {catalog?.shops.map(s => <option key={s} value={s}>{s}</option>)}
                </select>
              </label>
              <label className="w-[7.5rem] space-y-0.5">
                <span className={filterLabelClass}>Courier</span>
                <select value={courier} onChange={e => setCourier(e.target.value)} className={filterInputClass}>
                  <option value="">All</option>
                  {catalog && Object.entries(catalog.couriers).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
              </label>
              <label className="w-[8.5rem] space-y-0.5">
                <span className={filterLabelClass}>Damage</span>
                <select value={damage} onChange={e => setDamage(e.target.value)} className={filterInputClass}>
                  <option value="">All</option>
                  <option value="all_damaged">Damaged</option>
                  <option value="not_damaged">Not damaged</option>
                  {catalog?.damaged_reasons.map(d => <option key={d.id} value={d.id}>{d.label}</option>)}
                </select>
              </label>
              <label className="flex items-center gap-1.5 text-xs cursor-pointer pb-1.5">
                <input type="checkbox" checked={vipOnly} onChange={e => setVipOnly(e.target.checked)} className="rounded border-border size-3.5" />
                VIP
              </label>
              <label className="flex items-center gap-1.5 text-xs cursor-pointer pb-1.5">
                <input type="checkbox" checked={voidOnly} onChange={e => setVoidOnly(e.target.checked)} className="rounded border-border size-3.5" />
                Voided
              </label>
              <button
                type="button"
                onClick={clearFilters}
                className="rounded-md border border-border px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:bg-secondary/50"
              >
                Clear
              </button>
              <button
                type="submit"
                className="rounded-md bg-primary px-2.5 py-1.5 text-xs font-bold text-primary-foreground hover:brightness-105"
              >
                Apply
              </button>
            </div>
            <div className="flex flex-wrap items-end gap-2">
              <label className="w-[8.75rem] space-y-0.5">
                <span className={filterLabelClass}>Opened from</span>
                <input type="date" value={dateFrom} onChange={e => setDateFrom(e.target.value)} className={filterInputClass} />
              </label>
              <label className="w-[8.75rem] space-y-0.5">
                <span className={filterLabelClass}>Opened to</span>
                <input type="date" value={dateTo} onChange={e => setDateTo(e.target.value)} className={filterInputClass} />
              </label>
              <label className="w-[8.75rem] space-y-0.5">
                <span className={filterLabelClass}>Delivered from</span>
                <input type="date" value={delFrom} onChange={e => setDelFrom(e.target.value)} className={filterInputClass} />
              </label>
              <label className="w-[8.75rem] space-y-0.5">
                <span className={filterLabelClass}>Delivered to</span>
                <input type="date" value={delTo} onChange={e => setDelTo(e.target.value)} className={filterInputClass} />
              </label>
            </div>
          </form>
        )}
      </div>

      <div className="bg-card rounded-xl border border-border overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40 text-xs uppercase tracking-wide text-muted-foreground">
                <th className="px-4 py-3 text-left">Tracking</th>
                <th className="px-4 py-3 text-left">Shop</th>
                <th className="px-4 py-3 text-left">Courier</th>
                <th className="px-4 py-3 text-left">Opened</th>
                <th className="px-4 py-3 text-left">Lines</th>
                <th className="px-4 py-3 text-left">Damaged</th>
                <th className="px-4 py-3 text-left">Claim</th>
                {RTS_MEDIA_TABLE_HEADERS}
                <th className="px-4 py-3 w-8" aria-hidden />
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={11} className="py-10 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={16} />
                  </td>
                </tr>
              )}
              {!loading && cases.map(c => (
                <tr
                  key={c.id}
                  className={cn("border-b cursor-pointer group", fulfillmentHoverRow)}
                  onClick={() => void openDetail(c)}
                >
                  <td className="px-4 py-3 font-mono text-xs">{c.tracking_number}</td>
                  <td className="px-4 py-3 capitalize">
                    {c.shop}
                    {c.vip_subsource ? <span className="text-muted-foreground text-xs"> ({c.vip_subsource})</span> : null}
                  </td>
                  <td className="px-4 py-3">{catalog?.couriers[c.courier_final] ?? c.courier_final}</td>
                  <td className="px-4 py-3 text-muted-foreground">{c.date_opened}</td>
                  <td className="px-4 py-3"><LineCountBadge count={Number(c.line_count ?? 0)} /></td>
                  <td className="px-4 py-3"><DamageBadge row={c} /></td>
                  <td className="px-4 py-3"><ClaimBadge row={c} catalog={catalog} /></td>
                  <RtsCaseMediaTableCells row={c} docBase={docBase} />
                  <td className="px-4 py-3 text-muted-foreground">
                    <ChevronRight size={16} className="opacity-40 group-hover:opacity-100 group-hover:text-[#C8900A] transition-opacity" />
                  </td>
                </tr>
              ))}
              {!loading && !cases.length && (
                <tr>
                  <td colSpan={11} className="px-4 py-12 text-center text-muted-foreground">
                    No records match filters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        {!loading && (
          <TablePagination page={page} setPage={setPage} totalRows={total} perPage={PER_PAGE} />
        )}
      </div>

      <AppModal open={!!detail} onClose={() => setDetail(null)} panelClassName="max-w-lg w-full max-h-[90vh] overflow-y-auto" labelledBy="rts-detail-title">
        {detail && (
          <>
            <AppModalHeader title={detail.tracking_number} titleId="rts-detail-title" onClose={() => setDetail(null)} />
            <AppModalBody>
              <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 text-sm">
                <dt className="text-muted-foreground">Shop</dt><dd className="capitalize">{detail.shop}</dd>
                <dt className="text-muted-foreground">Courier</dt><dd>{catalog?.couriers[detail.courier_final] ?? detail.courier_final}</dd>
                <dt className="text-muted-foreground">Delivered</dt><dd>{detail.date_delivered}</dd>
                <dt className="text-muted-foreground">Opened</dt><dd>{detail.date_opened}</dd>
                <dt className="text-muted-foreground">Damaged</dt><dd><DamageBadge row={detail} /></dd>
                <dt className="text-muted-foreground">Claim</dt><dd><ClaimBadge row={detail} catalog={catalog} /></dd>
                {detail.voided_at && <><dt className="text-muted-foreground">Voided</dt><dd>{detail.voided_at}</dd></>}
              </dl>

              <div className="mt-4 rounded-lg border border-border p-3 bg-secondary/20">
                <p className={labelClass}>Product lines</p>
                {detailLoading ? (
                  <Loader2 size={14} className="animate-spin mt-2" />
                ) : detailLines.length ? (
                  <ul className="text-sm list-disc ml-4 mt-2 space-y-0.5">{detailLines.map((l, i) => <li key={i}>{l}</li>)}</ul>
                ) : (
                  <p className="text-sm text-amber-800 mt-2">
                    No product lines —{" "}
                    <Link to={`/rts/record?edit=${encodeURIComponent(detail.tracking_number)}`} className="font-semibold underline text-[#8a5c00]">
                      add lines
                    </Link>{" "}
                    before syncing inventory.
                  </p>
                )}
              </div>

              <RtsCaseMediaGallery row={detail} docBase={docBase} className="mt-4" />

              {canAdmin && !detail.voided_at && (
                <div className="mt-4 space-y-2 border-t border-border pt-4">
                  <p className={labelClass}>Claim status</p>
                  <select value={claimStatus} onChange={e => setClaimStatus(e.target.value)} className={inputClass}>
                    {catalog?.claim_statuses.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                  </select>
                  <textarea value={claimNote} onChange={e => setClaimNote(e.target.value)} placeholder="Note (optional)" className={cn(inputClass, "min-h-[72px] resize-y")} />
                  <button type="button" disabled={busy} onClick={() => void saveClaim()} className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground disabled:opacity-60">
                    Save claim
                  </button>
                </div>
              )}

              {!detail.voided_at && (
                <div className="mt-4 flex flex-wrap gap-3 pt-2">
                  <Link
                    to={`/rts/record?edit=${encodeURIComponent(detail.tracking_number)}`}
                    className="text-sm font-semibold text-[#C8900A] hover:underline"
                  >
                    Edit record
                  </Link>
                  {canAdmin && (
                    <button type="button" onClick={() => setVoidConfirm(true)} className="text-sm font-semibold text-red-600 hover:underline">
                      Void record
                    </button>
                  )}
                </div>
              )}
            </AppModalBody>
          </>
        )}
      </AppModal>

      <AppModal open={voidConfirm} onClose={() => setVoidConfirm(false)} panelClassName="max-w-sm w-full" labelledBy="rts-void-title">
        <AppModalHeader title="Void this record?" titleId="rts-void-title" onClose={() => setVoidConfirm(false)} />
        <AppModalBody>
          <p className="text-sm text-muted-foreground mb-4">The record stays in the list for audit but is marked voided. Documentation is not deleted.</p>
          <div className="flex gap-2">
            <button type="button" disabled={busy} onClick={() => void doVoid()} className="flex-1 py-2.5 rounded-lg text-white text-sm font-semibold bg-red-600 hover:bg-red-700 disabled:opacity-60">
              Yes, void
            </button>
            <button type="button" onClick={() => setVoidConfirm(false)} className="flex-1 py-2.5 rounded-lg border-2 border-border text-sm font-medium hover:bg-secondary/50">
              Cancel
            </button>
          </div>
        </AppModalBody>
      </AppModal>
    </PageLayout>
  );
}
