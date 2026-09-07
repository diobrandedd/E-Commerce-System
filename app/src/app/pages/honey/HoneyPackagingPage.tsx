import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { AlertTriangle, Loader2, Printer, ScanLine, Tag } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { BatchTraceabilityDrawer } from "@/app/components/honey/BatchTraceabilityDrawer";
import { PackagingScanLogModal } from "@/app/components/honey/PackagingScanLogModal";
import {
  DeptPouchAdminPanel,
  DeptPouchRequestsPanel,
  DeptPouchScanInPanel,
} from "@/app/components/dept/DeptPouchBarcodePanels";
import { HoneyBottleScanInPanel } from "@/app/components/honey/HoneyBottleBarcodePanels";
import {
  AppPressable,
  AppSectionEnter,
  appHeaderActionClass,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
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

function grossQty(value: string | number): string {
  const qty = Number(value);
  return Number.isFinite(qty) ? qty.toLocaleString() : String(value);
}

export function HoneyPackagingPage() {
  const { user, can } = useAuth();
  const canExecEdit = canExecutiveDirectEdit(user);
  const canLeakDamage = can("inventory.leak_damage.view");
  const canBottleScan = can("honey.bottle.scan");
  const showSupplierProfile = canViewSupplierProfile(user);
  const canCombBarcodes = canDeptPouchBarcodeOps(user, "honey_comb");
  const [rows, setRows] = useState<PkRow[]>([]);
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [variantTotals, setVariantTotals] = useState<
    { variant: string; total: number; liter: number; "250ml": number; "350ml": number; gallon: number }[]
  >([]);
  const [scannedGrandTotal, setScannedGrandTotal] = useState(0);
  const [byScanner, setByScanner] = useState<{ user_id: number | null; name: string; qty: number }[]>(
    [],
  );
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [traceBatch, setTraceBatch] = useState<string | null>(null);
  const [scanLogRow, setScanLogRow] = useState<PkRow | null>(null);
  const [editRow, setEditRow] = useState<PkRow | null>(null);
  const [traceQ, setTraceQ] = useState("");
  const [combModal, setCombModal] = useState<"scan" | "admin" | null>(null);
  const [honeyBottleScanOpen, setHoneyBottleScanOpen] = useState(false);
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
      const data = await bottleBarcodesApi.packagingScanReport(scanDate);
      setRows(data.rows);
      setScanDates(data.dates);
      setVariantTotals(data.totals?.by_variant ?? []);
      setScannedGrandTotal(data.totals?.total ?? 0);
      setByScanner(data.by_scanner ?? []);
      setPage(1);
      await refreshPendingComb();
    } finally {
      setLoading(false);
    }
  }, [refreshPendingComb, scanDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);
  const colCount = canExecEdit ? 9 : 8;

  return (
    <PageLayout
      title="Raw Honey"
      subtitle="Packaging — batch rows and scan-in history"
      actions={
        <input
          value={traceQ}
          onChange={e => setTraceQ(e.target.value)}
          placeholder="Trace batch / supplier…"
          className="text-xs border rounded-lg px-2 py-1.5 w-full max-w-[11rem] sm:max-w-none"
        />
      }
    >
      <div className="space-y-4 mb-6">
        <HoneyStageTabs />
        {canBottleScan && (
          <AppPressable
            type="button"
            className={`${appHeaderActionClass} gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
            onClick={() => setHoneyBottleScanOpen(true)}
          >
            <ScanLine size={14} /> Scan bottle stock in
          </AppPressable>
        )}
        {canLeakDamage && (
          <Link
            to="/inventory/leak-damage"
            className="inline-flex items-center gap-2 py-2 px-3 text-sm font-semibold border border-red-300 text-red-700 rounded-md hover:bg-red-50 transition-colors w-fit"
          >
            <AlertTriangle size={16} /> Leak / damage scan
          </Link>
        )}
      </div>
      {traceQ && (
        <button
          type="button"
          onClick={() => setTraceBatch(traceQ)}
          className="text-xs text-blue-600 underline mb-3"
        >
          Search traceability
        </button>
      )}

      <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-sm font-semibold mb-1">Scanned into inventory</h2>
          <p className="text-xs text-muted-foreground">
            Gross bottle scans only — packing, sales, defects, and other deductions do not reduce these totals.
            Tap a row for sticker details.
          </p>
        </div>
        <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold shrink-0">
          Scan date
          <select
            value={scanDate}
            onChange={e => setScanDate(e.target.value)}
            className="mt-1 block min-w-[10.5rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
          >
            <option value={localToday()}>Today · {scanDateLabel(localToday())}</option>
            {scanDates
              .filter(date => date !== localToday())
              .map(date => (
                <option key={date} value={date}>
                  {scanDateLabel(date)}
                </option>
              ))}
            <option value="all">All dates</option>
          </select>
        </label>
      </div>
      {scanDate !== "all" && !loading && (
        <div className="mb-3 rounded-xl border border-border bg-card px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            Total scanned · {scanDate === localToday() ? "Today" : scanDateLabel(scanDate)}
          </p>
          {variantTotals.length === 0 ? (
            <p className="text-sm text-muted-foreground mt-1">0 bottles</p>
          ) : (
            <ul className="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
              {variantTotals.map(v => (
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
          {scannedGrandTotal > 0 && (
            <p className="text-xs text-muted-foreground mt-2">
              All variants · {grossQty(scannedGrandTotal)} bottle{scannedGrandTotal === 1 ? "" : "s"}
            </p>
          )}
        </div>
      )}
      {!loading && byScanner.length > 0 && (
        <div className="mb-3 rounded-xl border border-border bg-secondary/20 px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            By person · all accounts
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
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm min-w-[36rem]">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Supplier</th>
              <th className="px-3 py-2 text-left">Batch</th>
              <th className="px-3 py-2 text-left">Date</th>
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => (
                <th key={s} className="px-3 py-2 text-left whitespace-nowrap">
                  {s}
                </th>
              ))}
              {canExecEdit && <th className="px-3 py-2">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={colCount} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {pagedRows.map(r => (
              <tr
                key={r.id}
                className={`border-b cursor-pointer ${appHoverRow}`}
                onClick={() => setScanLogRow(r)}
              >
                <td className="px-3 py-2" onClick={e => e.stopPropagation()}>
                  {showSupplierProfile ? (
                    <SupplierCodeButton code={r.supplier_code} className="text-xs" />
                  ) : (
                    <span className="font-mono text-xs">{r.supplier_code}</span>
                  )}
                </td>
                <td className="px-3 py-2 font-medium whitespace-nowrap">{r.batch_no}</td>
                <td className="px-3 py-2 whitespace-nowrap">{r.bottling_date}</td>
                <td className="px-3 py-2 capitalize">{r.variant}</td>
                <td className="px-3 py-2 font-semibold">{grossQty(r.scanned_liter)}</td>
                <td className="px-3 py-2 font-semibold">{grossQty(r.scanned_250ml)}</td>
                <td className="px-3 py-2 font-semibold">{grossQty(r.scanned_350ml)}</td>
                <td className="px-3 py-2 font-semibold">{grossQty(r.scanned_gallon)}</td>
                {canExecEdit && (
                  <td className="px-3 py-2 whitespace-nowrap" onClick={e => e.stopPropagation()}>
                    <button
                      type="button"
                      onClick={() => setEditRow(r)}
                      className="text-xs font-semibold text-muted-foreground hover:text-foreground"
                    >
                      Edit
                    </button>
                  </td>
                )}
              </tr>
            ))}
            {!loading && pagedRows.length === 0 && (
              <tr>
                <td colSpan={colCount} className="py-10 text-center text-sm text-muted-foreground">
                  No bottles scanned into inventory {scanDate === "all" ? "yet." : `on ${scanDate}.`}
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
                className={`${appHeaderActionClass} gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
                onClick={() => {
                  document.getElementById("honey-comb-barcodes")?.scrollIntoView({ behavior: "smooth" });
                }}
              >
                <Tag size={13} /> Request
              </AppPressable>
              <AppPressable
                type="button"
                className={`${appHeaderActionClass} gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
                onClick={() => setCombModal("scan")}
              >
                <ScanLine size={13} /> Scan
              </AppPressable>
              <AppPressable
                type="button"
                className={`${appHeaderActionClass} relative gap-1.5 inline-flex items-center text-xs font-medium px-3 py-1.5 rounded-lg border border-border`}
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

      {honeyBottleScanOpen && (
        <AppModal
          open
          onClose={() => {
            setHoneyBottleScanOpen(false);
            void refresh();
          }}
          panelClassName="max-w-lg w-full"
          labelledBy="honey-bottle-scan-title"
        >
          <AppModalHeader
            title="Scan bottles into packaging stock"
            titleId="honey-bottle-scan-title"
            onClose={() => {
              setHoneyBottleScanOpen(false);
              void refresh();
            }}
          />
          <AppModalBody>
            <HoneyBottleScanInPanel />
          </AppModalBody>
        </AppModal>
      )}

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
          onDone={() => {
            setEditRow(null);
            void refresh();
          }}
        />
      )}
    </PageLayout>
  );
}
