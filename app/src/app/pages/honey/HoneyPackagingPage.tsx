import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { AlertTriangle, Loader2, Printer, ScanLine, Tag } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStageContent, HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { BatchTraceabilityDrawer } from "@/app/components/honey/BatchTraceabilityDrawer";
import { PackagingScanLogModal } from "@/app/components/honey/PackagingScanLogModal";
import {
  DeptPouchAdminPanel,
  DeptPouchRequestsPanel,
  DeptPouchScanInPanel,
} from "@/app/components/dept/DeptPouchBarcodePanels";
import {
  AppErrorBanner,
  AppPressable,
  AppSectionEnter,
  AppSuccessNotice,
  appHeaderActionClass,
  appHoverCard,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { HONEY_SIZES } from "@/app/lib/honey";
import { canDeptPouchBarcodeOps, canExecutiveDirectEdit, canViewSupplierProfile } from "@/app/lib/honeyPermissions";
import { deptPouchBarcodesApi } from "@/app/lib/deptPouchBarcodes";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { variantLabel } from "@/app/lib/posHoney";

const HONEY_COMB_KEY = "honey_comb";
const HONEY_COMB_LABEL = "Honey Comb";

interface PkRow {
  id: number;
  supplier_code: string;
  batch_no: string;
  bottling_date: string;
  variant: string;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
  scanned_liter: string;
  scanned_250ml: string;
  scanned_350ml: string;
  scanned_gallon: string;
}

function localToday(): string {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60_000;
  return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

function scanDateLabel(date: string): string {
  return new Date(`${date}T00:00:00`).toLocaleDateString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function filterAxisLabel(kind: "scan" | "bottling", value: string): string {
  if (value === "all") {
    return kind === "scan" ? "All scan dates" : "All bottling dates";
  }
  if (value === localToday()) {
    return kind === "scan" ? `Stock-in · Today (${scanDateLabel(value)})` : `Lot · Today (${scanDateLabel(value)})`;
  }
  return kind === "scan" ? `Stock-in · ${scanDateLabel(value)}` : `Lot · ${scanDateLabel(value)}`;
}

function emptyFilterMessage(scanDate: string, bottlingDate: string): string {
  const scanPart =
    scanDate === "all" ? "any scan date" : scanDate === localToday() ? "today’s stock-in" : `stock-in on ${scanDateLabel(scanDate)}`;
  const bottlePart =
    bottlingDate === "all"
      ? "any bottling date"
      : bottlingDate === localToday()
        ? "today’s bottling lots"
        : `lots bottled ${scanDateLabel(bottlingDate)}`;
  if (scanDate === "all" && bottlingDate === "all") {
    return "No bottles scanned into inventory yet.";
  }
  return `No bottles match ${scanPart} and ${bottlePart}. Try clearing one filter.`;
}

function grossQty(value: string | number): string {
  const qty = Number(value);
  return Number.isFinite(qty) ? qty.toLocaleString() : String(value);
}

function currentField(size: (typeof HONEY_SIZES)[number]): keyof PkRow {
  return `current_${size}` as keyof PkRow;
}

function scannedField(size: (typeof HONEY_SIZES)[number]): keyof PkRow {
  return `scanned_${size}` as keyof PkRow;
}

function QtyCell({ current, scanned }: { current: string | number; scanned: string | number }) {
  const cur = Number(current);
  const scan = Number(scanned);
  const showScanHint = Number.isFinite(scan) && (!Number.isFinite(cur) || Math.abs(scan - cur) > 1e-9);
  return (
    <div className="leading-tight">
      <span className="font-semibold tabular-nums">{grossQty(current)}</span>
      {showScanHint ? (
        <span className="mt-0.5 block text-[10px] font-normal text-muted-foreground tabular-nums">
          scanned {grossQty(scanned)}
        </span>
      ) : null}
    </div>
  );
}

function sumOnHandByVariant(rows: PkRow[]): {
  byVariant: { variant: string; total: number; liter: number; "250ml": number; "350ml": number; gallon: number }[];
  grandTotal: number;
} {
  const map = new Map<
    string,
    { variant: string; total: number; liter: number; "250ml": number; "350ml": number; gallon: number }
  >();
  for (const r of rows) {
    const key = String(r.variant || "unknown").toLowerCase();
    const liter = Number(r.current_liter) || 0;
    const ml250 = Number(r.current_250ml) || 0;
    const ml350 = Number(r.current_350ml) || 0;
    const gallon = Number(r.current_gallon) || 0;
    const total = liter + ml250 + ml350 + gallon;
    const prev = map.get(key) ?? {
      variant: key,
      total: 0,
      liter: 0,
      "250ml": 0,
      "350ml": 0,
      gallon: 0,
    };
    prev.total += total;
    prev.liter += liter;
    prev["250ml"] += ml250;
    prev["350ml"] += ml350;
    prev.gallon += gallon;
    map.set(key, prev);
  }
  const byVariant = [...map.values()].sort((a, b) => a.variant.localeCompare(b.variant));
  return {
    byVariant,
    grandTotal: byVariant.reduce((n, v) => n + v.total, 0),
  };
}

export function HoneyPackagingPage() {
  const { user, can } = useAuth();
  const canExecEdit = canExecutiveDirectEdit(user);
  const canLeakDamage = can("inventory.leak_damage.view");
  const showSupplierProfile = canViewSupplierProfile(user);
  const canCombBarcodes = canDeptPouchBarcodeOps(user, "honey_comb");
  const [rows, setRows] = useState<PkRow[]>([]);
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [bottlingDate, setBottlingDate] = useState("all");
  const [bottlingDates, setBottlingDates] = useState<string[]>([]);
  const [scannedGrandTotal, setScannedGrandTotal] = useState(0);
  const [byScanner, setByScanner] = useState<{ user_id: number | null; name: string; qty: number }[]>(
    [],
  );
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [traceBatch, setTraceBatch] = useState<string | null>(null);
  const [scanLogRow, setScanLogRow] = useState<PkRow | null>(null);
  const [editRow, setEditRow] = useState<PkRow | null>(null);
  const [editSavedNotice, setEditSavedNotice] = useState<string | null>(null);
  const [traceQ, setTraceQ] = useState("");
  const [combModal, setCombModal] = useState<"scan" | "admin" | null>(null);
  const [pendingCombLabels, setPendingCombLabels] = useState(0);
  const combScanTitleId = useId();
  const combAdminTitleId = useId();

  const refreshPendingComb = useCallback(async () => {
    if (!canCombBarcodes) {
      setPendingCombLabels(0);
      return;
    }
    try {
      const res = await deptPouchBarcodesApi.pendingCount(HONEY_COMB_KEY);
      setPendingCombLabels(res.count);
    } catch {
      setPendingCombLabels(0);
    }
  }, [canCombBarcodes]);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await bottleBarcodesApi.packagingScanReport(scanDate, bottlingDate);
      setRows(data.rows);
      setScanDates(data.dates);
      setBottlingDates(data.bottling_dates ?? []);
      setScannedGrandTotal(data.totals?.total ?? 0);
      setByScanner(data.by_scanner ?? []);
      setPage(1);
      await refreshPendingComb();
      setLoadError(null);
    } catch (error) {
      setLoadError(error instanceof Error ? error.message : "Could not load packaging scan history.");
    } finally {
      setLoading(false);
    }
  }, [bottlingDate, refreshPendingComb, scanDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);
  const colCount = 9;
  const filtersNarrowed = scanDate !== "all" || bottlingDate !== "all";
  const showTotalsBand = filtersNarrowed && !loading;
  const totalsBandTitle = [
    scanDate !== "all" ? filterAxisLabel("scan", scanDate) : null,
    bottlingDate !== "all" ? filterAxisLabel("bottling", bottlingDate) : null,
  ]
    .filter(Boolean)
    .join(" · ");
  const onHandTotals = sumOnHandByVariant(rows);

  return (
    <PageLayout
      title="Raw Honey"
      subtitle="Packaging — batch rows and scan-in history (stock-in from Production)"
      actions={
        <form
          className="flex items-center gap-2"
          onSubmit={event => {
            event.preventDefault();
            if (traceQ.trim()) setTraceBatch(traceQ.trim());
          }}
        >
          <label htmlFor="honey-trace-search" className="sr-only">Trace batch or supplier</label>
          <input
            id="honey-trace-search"
            value={traceQ}
            onChange={e => setTraceQ(e.target.value)}
            placeholder="Batch or supplier"
            className="min-h-11 w-full max-w-[11rem] rounded-lg border border-border bg-card px-3 text-sm sm:max-w-none"
          />
          <Button type="submit" variant="outline" className="min-h-11" disabled={!traceQ.trim()}>
            Trace
          </Button>
        </form>
      }
    >
      <div className="space-y-4 mb-6">
        <HoneyStageTabs />
        {canLeakDamage && (
          <Link
            to="/inventory/leak-damage"
            className="inline-flex min-h-11 items-center gap-2 py-2 px-3 text-sm font-semibold border border-red-300 text-red-700 rounded-md hover:bg-red-50 transition-colors w-fit"
          >
            <AlertTriangle size={16} /> Leak / damage scan
          </Link>
        )}
      </div>
      <HoneyStageContent>
        <div>
      {loadError && (
        <div className="mb-3">
          <AppErrorBanner message={loadError} />
          <Button type="button" variant="outline" className="min-h-11" onClick={() => void refresh()}>
            Retry loading
          </Button>
        </div>
      )}

      <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-sm font-semibold mb-1">Packaging inventory</h2>
          <p className="text-xs text-muted-foreground">
            Quantities are <strong className="text-foreground font-medium">current on hand</strong> (what packing and
            sales use). Edit updates those levels. When scanned stock-in differs, a small “scanned” note appears under
            the cell. Filters still use scan date (when stickers were stocked in) and bottling date (lot on the row).
            Use Details for the sticker log.
          </p>
          {editSavedNotice ? (
            <div className="mt-2">
              <AppSuccessNotice message={editSavedNotice} />
            </div>
          ) : null}
        </div>
        <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
          <label
            htmlFor="packaging-scan-date-filter"
            className="text-xs uppercase tracking-wide text-muted-foreground font-semibold shrink-0"
          >
            Scan date (stock-in)
            <select
              id="packaging-scan-date-filter"
              name="packaging_scan_date"
              value={scanDate}
              onChange={e => setScanDate(e.target.value)}
              className="mt-1 block min-h-11 min-w-[10.5rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
              data-testid="packaging-scan-date-filter"
            >
              <option value={localToday()}>Today (stock-in) · {scanDateLabel(localToday())}</option>
              {scanDates
                .filter(date => date !== localToday())
                .map(date => (
                  <option key={date} value={date}>
                    {scanDateLabel(date)}
                  </option>
                ))}
              <option value="all">All scan dates</option>
            </select>
          </label>
          <label
            htmlFor="packaging-bottling-date-filter"
            className="text-xs uppercase tracking-wide text-muted-foreground font-semibold shrink-0"
          >
            Bottling date (lot)
            <select
              id="packaging-bottling-date-filter"
              name="packaging_bottling_date"
              value={bottlingDate}
              onChange={e => setBottlingDate(e.target.value)}
              className="mt-1 block min-h-11 min-w-[10.5rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
              data-testid="packaging-bottling-date-filter"
            >
              <option value="all">All bottling dates</option>
              <option value={localToday()}>Today (lot) · {scanDateLabel(localToday())}</option>
              {bottlingDates
                .filter(date => date !== localToday())
                .map(date => (
                  <option key={date} value={date}>
                    {scanDateLabel(date)}
                  </option>
                ))}
            </select>
          </label>
        </div>
      </div>
      {filtersNarrowed && (
        <div
          className="mb-3 flex flex-wrap items-center gap-2"
          data-testid="packaging-active-filters"
          aria-live="polite"
        >
          <span className="text-xs text-muted-foreground">Showing</span>
          {scanDate !== "all" && (
            <AppPressable
              type="button"
              className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 text-xs font-medium"
              onClick={() => setScanDate("all")}
            >
              {filterAxisLabel("scan", scanDate)}
              <span className="text-muted-foreground">×</span>
            </AppPressable>
          )}
          {bottlingDate !== "all" && (
            <AppPressable
              type="button"
              className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 text-xs font-medium"
              onClick={() => setBottlingDate("all")}
            >
              {filterAxisLabel("bottling", bottlingDate)}
              <span className="text-muted-foreground">×</span>
            </AppPressable>
          )}
          {(scanDate !== "all" || bottlingDate !== "all") && (
            <Button
              type="button"
              variant="ghost"
              className="min-h-9 px-2 text-xs"
              onClick={() => {
                setScanDate("all");
                setBottlingDate("all");
              }}
            >
              Clear filters
            </Button>
          )}
        </div>
      )}
      {showTotalsBand && (
        <div className="mb-3 rounded-xl border border-border bg-card px-4 py-3" aria-live="polite">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            On hand · {totalsBandTitle}
          </p>
          {onHandTotals.byVariant.length === 0 ? (
            <p className="text-sm text-muted-foreground mt-1">0 bottles</p>
          ) : (
            <ul className="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
              {onHandTotals.byVariant.map(v => (
                <li key={v.variant} className="rounded-lg border border-border/70 bg-secondary/20 px-3 py-2">
                  <p className="text-xs text-muted-foreground capitalize">{variantLabel(v.variant)}</p>
                  <p className="text-xl font-bold mt-0.5">{grossQty(v.total)}</p>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    {grossQty(v.liter)} L · {grossQty(v["250ml"])} 250ml · {grossQty(v["350ml"])} 350ml ·{" "}
                    {grossQty(v.gallon)} gal
                  </p>
                </li>
              ))}
            </ul>
          )}
          {onHandTotals.grandTotal > 0 && (
            <p className="text-xs text-muted-foreground mt-2">
              All variants · {grossQty(onHandTotals.grandTotal)} bottle
              {onHandTotals.grandTotal === 1 ? "" : "s"} on hand
            </p>
          )}
          {scannedGrandTotal > 0 && scannedGrandTotal !== onHandTotals.grandTotal && (
            <p className="text-[11px] text-muted-foreground mt-1">
              Gross scanned under these filters · {grossQty(scannedGrandTotal)} (history only; Edit does not change
              scan history)
            </p>
          )}
        </div>
      )}
      {!loading && byScanner.length > 0 && (
        <div className="mb-3 rounded-xl border border-border bg-secondary/20 px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            Gross scanned by person · current filters
          </p>
          <ul className="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
            {byScanner.map(row => (
              <li
                key={`${row.user_id ?? "unk"}-${row.name}`}
                className="flex items-baseline justify-between gap-3 text-sm"
              >
                <span className="font-medium truncate">{row.name}</span>
                <span className="tabular-nums shrink-0 text-muted-foreground">
                  {row.qty.toLocaleString()} bottles
                </span>
              </li>
            ))}
          </ul>
        </div>
      )}
      <div
        className="sm:hidden space-y-3"
        data-testid="honey-packaging-mobile-cards"
        aria-busy={loading || undefined}
      >
        {loading ? (
          <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground">
            <Loader2 className="mr-2 inline animate-spin" size={18} aria-hidden />
            Loading packaging batches…
          </div>
        ) : pagedRows.length === 0 ? (
          <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground">
            {emptyFilterMessage(scanDate, bottlingDate)}
          </div>
        ) : (
          pagedRows.map(r => (
            <article key={r.id} className={`rounded-xl border border-border bg-card p-4 ${appHoverCard}`}>
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Batch</p>
                  <p className="font-semibold">{r.batch_no}</p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    Bottled {scanDateLabel(r.bottling_date)} · <span className="capitalize">{r.variant}</span>
                  </p>
                </div>
                {showSupplierProfile ? (
                  <SupplierCodeButton code={r.supplier_code} className="min-h-11 text-xs" />
                ) : (
                  <span className="font-mono text-xs">{r.supplier_code}</span>
                )}
              </div>
              <dl className="mt-3 grid grid-cols-2 gap-2">
                {HONEY_SIZES.map(size => (
                  <div key={size} className="rounded-lg bg-secondary/30 px-3 py-2">
                    <dt className="text-[11px] uppercase tracking-wide text-muted-foreground">{size}</dt>
                    <dd className="mt-0.5 text-base">
                      <QtyCell
                        current={r[currentField(size)] as string}
                        scanned={r[scannedField(size)] as string}
                      />
                    </dd>
                  </div>
                ))}
              </dl>
              <div className="mt-3 flex gap-2">
                <Button type="button" className="min-h-11 flex-1" onClick={() => setScanLogRow(r)}>
                  View scan details
                </Button>
                {canExecEdit && (
                  <Button type="button" className="min-h-11" variant="outline" onClick={() => setEditRow(r)}>
                    Edit
                  </Button>
                )}
              </div>
            </article>
          ))
        )}
      </div>
      <div
        className="hidden sm:block bg-card rounded-xl border overflow-x-auto"
        data-testid="honey-packaging-desktop-table"
        aria-busy={loading || undefined}
      >
        <table className="w-full text-sm min-w-[36rem]">
          <caption className="sr-only">Honey packaging batch inventory on hand</caption>
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th scope="col" className="px-3 py-2 text-left">Supplier</th>
              <th scope="col" className="px-3 py-2 text-left">Batch</th>
              <th scope="col" className="px-3 py-2 text-left">Bottling date</th>
              <th scope="col" className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => (
                <th scope="col" key={s} className="px-3 py-2 text-left whitespace-nowrap">
                  {s}
                </th>
              ))}
              <th scope="col" className="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={colCount} className="py-8 text-center text-sm text-muted-foreground">
                  <Loader2 className="mr-2 inline animate-spin" size={14} aria-hidden />
                  Loading packaging batches…
                </td>
              </tr>
            )}
            {pagedRows.map(r => (
              <tr key={r.id} className={`border-b ${appHoverRow}`}>
                <td className="px-3 py-2">
                  {showSupplierProfile ? (
                    <SupplierCodeButton code={r.supplier_code} className="text-xs" />
                  ) : (
                    <span className="font-mono text-xs">{r.supplier_code}</span>
                  )}
                </td>
                <td className="px-3 py-2 font-medium whitespace-nowrap">{r.batch_no}</td>
                <td className="px-3 py-2 whitespace-nowrap">{scanDateLabel(r.bottling_date)}</td>
                <td className="px-3 py-2 capitalize">{r.variant}</td>
                {HONEY_SIZES.map(size => (
                  <td key={size} className="px-3 py-2">
                    <QtyCell
                      current={r[currentField(size)] as string}
                      scanned={r[scannedField(size)] as string}
                    />
                  </td>
                ))}
                <td className="px-3 py-2 whitespace-nowrap">
                  <Button type="button" size="sm" variant="ghost" onClick={() => setScanLogRow(r)}>
                    Details
                  </Button>
                  {canExecEdit && (
                    <Button type="button" size="sm" variant="ghost" onClick={() => setEditRow(r)}>
                      Edit
                    </Button>
                  )}
                </td>
              </tr>
            ))}
            {!loading && pagedRows.length === 0 && (
              <tr>
                <td colSpan={colCount} className="py-10 text-center text-sm text-muted-foreground">
                  {emptyFilterMessage(scanDate, bottlingDate)}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {!loading && totalRows > 0 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 mt-3">
          <p className="text-xs text-muted-foreground">
            Showing {start + 1}-{Math.min(start + perPage, totalRows)} of {totalRows} rows
          </p>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={safePage <= 1}
              className="min-h-11 text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
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
              className="min-h-11 text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      )}

      {canCombBarcodes ? (
        <AppSectionEnter className="mt-8 space-y-3 border-t border-border pt-6">
          <div
            id="honey-comb-barcodes"
            className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between scroll-mt-4"
          >
            <div className="min-w-0">
              <h2 className="text-sm font-semibold">Honey Comb barcodes</h2>
              <p className="text-xs text-muted-foreground mt-1">
                Request jar stickers by batch code (no variant or size). Scan each jar into finished{" "}
                {HONEY_COMB_LABEL} stock. Raw bags and packaging are not deducted here.{" "}
                <Link
                  to="/inventory/raw-materials/honey-comb"
                  className="underline hover:text-foreground"
                >
                  Stock in raw bags →
                </Link>
              </p>
            </div>
            <div className="flex flex-wrap items-center gap-2 shrink-0">
              <AppPressable
                type="button"
                className={`${appHeaderActionClass} min-h-11 gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
                onClick={() => {
                  document.getElementById("honey-comb-barcodes")?.scrollIntoView({ behavior: "smooth" });
                }}
              >
                <Tag size={13} /> Request
              </AppPressable>
              <AppPressable
                type="button"
                className={`${appHeaderActionClass} min-h-11 gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
                onClick={() => setCombModal("scan")}
              >
                <ScanLine size={13} /> Scan
              </AppPressable>
              <AppPressable
                type="button"
                className={`${appHeaderActionClass} relative min-h-11 gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
                onClick={() => setCombModal("admin")}
              >
                <Printer size={13} /> Generate / print
                {pendingCombLabels > 0 ? (
                  <span className="absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                    {pendingCombLabels > 99 ? "99+" : pendingCombLabels}
                  </span>
                ) : null}
              </AppPressable>
            </div>
          </div>
          <DeptPouchRequestsPanel productKey={HONEY_COMB_KEY} productLabel={HONEY_COMB_LABEL} />
        </AppSectionEnter>
      ) : null}
        </div>
      </HoneyStageContent>

      {combModal === "scan" && (
        <AppModal
          open
          onClose={() => {
            setCombModal(null);
            void refresh();
          }}
          panelClassName="max-w-lg w-full"
          labelledBy={combScanTitleId}
        >
          <AppModalHeader
            title="Scan Honey Comb jars to inventory"
            titleId={combScanTitleId}
            onClose={() => {
              setCombModal(null);
              void refresh();
            }}
          />
          <AppModalBody>
            <DeptPouchScanInPanel productKey={HONEY_COMB_KEY} productLabel={HONEY_COMB_LABEL} />
          </AppModalBody>
        </AppModal>
      )}
      {combModal === "admin" && (
        <AppModal
          open
          onClose={() => {
            setCombModal(null);
            void refresh();
          }}
          panelClassName="max-w-2xl w-full"
          labelledBy={combAdminTitleId}
        >
          <AppModalHeader
            title="Generate / print Honey Comb requests"
            titleId={combAdminTitleId}
            onClose={() => {
              setCombModal(null);
              void refresh();
            }}
          />
          <AppModalBody>
            <DeptPouchAdminPanel productKey={HONEY_COMB_KEY} productLabel={HONEY_COMB_LABEL} />
          </AppModalBody>
        </AppModal>
      )}

      {scanLogRow && (
        <PackagingScanLogModal
          packagingRowId={scanLogRow.id}
          batchNo={scanLogRow.batch_no}
          variant={scanLogRow.variant}
          scanDate={scanDate}
          onClose={() => setScanLogRow(null)}
        />
      )}
      {traceBatch && (
        <BatchTraceabilityDrawer query={traceBatch} onClose={() => setTraceBatch(null)} />
      )}
      {editRow && (
        <ExecutiveRowEditModal
          stage="packaging"
          row={editRow}
          onClose={() => setEditRow(null)}
          onSaved={() => {
            setEditRow(null);
            setEditSavedNotice("Packaging on-hand updated.");
            window.setTimeout(() => setEditSavedNotice(null), 3500);
            void refresh();
          }}
        />
      )}
    </PageLayout>
  );
}
