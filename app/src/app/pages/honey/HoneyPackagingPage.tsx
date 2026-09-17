import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { AlertTriangle, ChevronDown, Loader2, Printer, ScanLine, Tag } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStageContent, HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { ExecutiveRowEditModal, type PackagingEditRow } from "@/app/components/honey/ExecutiveRowEditModal";
import { BatchTraceabilityDrawer } from "@/app/components/honey/BatchTraceabilityDrawer";
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
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { HONEY_SIZES } from "@/app/lib/honey";
import { canDeptPouchBarcodeOps, canExecutiveDirectEdit } from "@/app/lib/honeyPermissions";
import { deptPouchBarcodesApi } from "@/app/lib/deptPouchBarcodes";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { apiGet, ApiError } from "@/app/lib/api";
import { variantLabel } from "@/app/lib/posHoney";
import { cn } from "@/app/components/ui/utils";

const HONEY_COMB_KEY = "honey_comb";
const HONEY_COMB_LABEL = "Honey Comb";

interface PoolVariant {
  variant: string;
  label: string;
  packaging_row_id: number;
  total_bottles: number;
  by_size: {
    "250ml": number;
    "350ml": number;
    liter: number;
    gallon: number;
  };
  current_250ml: number;
  current_350ml: number;
  current_liter: number;
  current_gallon: number;
  initial_250ml?: number;
  initial_350ml?: number;
  initial_liter?: number;
  initial_gallon?: number;
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

function sizeKey(size: (typeof HONEY_SIZES)[number]): keyof PoolVariant["by_size"] {
  return size as keyof PoolVariant["by_size"];
}

export function HoneyPackagingPage() {
  const { user, can } = useAuth();
  const reduceMotion = useReducedMotion();
  const canExecEdit = canExecutiveDirectEdit(user);
  const canLeakDamage = can("inventory.leak_damage.view");
  const canCombBarcodes = canDeptPouchBarcodeOps(user, "honey_comb");
  const [pools, setPools] = useState<PoolVariant[]>([]);
  const [grandTotal, setGrandTotal] = useState(0);
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});
  const [historyOpen, setHistoryOpen] = useState(false);
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [scannedGrandTotal, setScannedGrandTotal] = useState(0);
  const [byScanner, setByScanner] = useState<{ user_id: number | null; name: string; qty: number }[]>([]);
  const [loading, setLoading] = useState(true);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [editRow, setEditRow] = useState<PackagingEditRow | null>(null);
  const [editSavedNotice, setEditSavedNotice] = useState<string | null>(null);
  const [traceQ, setTraceQ] = useState("");
  const [traceBatch, setTraceBatch] = useState<string | null>(null);
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

  const refreshPools = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ variants: PoolVariant[]; total_bottles: number }>(
        "honey.php",
        "packaging_variant_pools",
      );
      setPools(data.variants ?? []);
      setGrandTotal(Number(data.total_bottles) || 0);
      await refreshPendingComb();
      setLoadError(null);
    } catch (error) {
      setLoadError(error instanceof ApiError ? error.message : "Could not load packaging pools.");
    } finally {
      setLoading(false);
    }
  }, [refreshPendingComb]);

  const refreshHistory = useCallback(async () => {
    setHistoryLoading(true);
    try {
      const data = await bottleBarcodesApi.packagingScanReport(scanDate, "all");
      setScanDates(data.dates);
      setScannedGrandTotal(data.totals?.total ?? 0);
      setByScanner(data.by_scanner ?? []);
    } catch {
      setByScanner([]);
      setScannedGrandTotal(0);
    } finally {
      setHistoryLoading(false);
    }
  }, [scanDate]);

  useEffect(() => {
    void refreshPools();
  }, [refreshPools]);
  useLiveRefresh(refreshPools);

  useEffect(() => {
    if (!historyOpen) return;
    void refreshHistory();
  }, [historyOpen, refreshHistory]);

  const openPoolEdit = (pool: PoolVariant) => {
    setEditRow({
      id: pool.packaging_row_id,
      supplier_code: "",
      batch_no: "POOL",
      variant: pool.variant,
      current_liter: String(pool.current_liter),
      initial_liter: String(pool.initial_liter ?? pool.current_liter),
      current_250ml: String(pool.current_250ml),
      initial_250ml: String(pool.initial_250ml ?? pool.current_250ml),
      current_350ml: String(pool.current_350ml),
      initial_350ml: String(pool.initial_350ml ?? pool.current_350ml),
      current_gallon: String(pool.current_gallon),
      initial_gallon: String(pool.initial_gallon ?? pool.current_gallon),
    });
  };

  return (
    <PageLayout
      title="Raw Honey"
      subtitle="Packaging — finished bottles by variant (stock-in from Production)"
      actions={
        <form
          className="flex items-center gap-2"
          onSubmit={event => {
            event.preventDefault();
            if (traceQ.trim()) setTraceBatch(traceQ.trim());
          }}
        >
          <label htmlFor="honey-trace-search" className="sr-only">
            Trace batch or supplier
          </label>
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
              <Button type="button" variant="outline" className="min-h-11" onClick={() => void refreshPools()}>
                Retry loading
              </Button>
            </div>
          )}

          <div className="mb-3">
            <h2 className="text-sm font-semibold mb-1">Finished honey on hand</h2>
            <p className="text-xs text-muted-foreground">
              Light, Amber, and Dark pools. Expand a card for size breakdown. Stock-in from Production credits these
              pools (no lot picker). Packing and sales deduct from the same totals.
            </p>
            {editSavedNotice ? (
              <div className="mt-2">
                <AppSuccessNotice message={editSavedNotice} />
              </div>
            ) : null}
            {!loading && grandTotal > 0 && (
              <p className="mt-2 text-xs text-muted-foreground">
                All variants · {grossQty(grandTotal)} bottle{grandTotal === 1 ? "" : "s"} on hand
              </p>
            )}
          </div>

          {loading ? (
            <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-3" data-testid="honey-packaging-variant-cards">
              {pools.map(pool => {
                const open = !!expanded[pool.variant];
                return (
                  <div
                    key={pool.variant}
                    className={cn("rounded-xl border border-border bg-card overflow-hidden", appHoverCard)}
                  >
                    <button
                      type="button"
                      className="w-full flex items-start gap-3 px-4 py-4 text-left"
                      onClick={() =>
                        setExpanded(prev => ({ ...prev, [pool.variant]: !prev[pool.variant] }))
                      }
                    >
                      <ChevronDown
                        size={18}
                        className={cn(
                          "mt-1 shrink-0 text-muted-foreground transition-transform",
                          open && "rotate-180",
                        )}
                      />
                      <div className="min-w-0 flex-1">
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Variant</p>
                        <p className="text-lg font-semibold text-foreground">{variantLabel(pool.variant)}</p>
                        <p className="mt-2 text-3xl font-bold tabular-nums">{grossQty(pool.total_bottles)}</p>
                        <p className="text-xs text-muted-foreground">bottles on hand</p>
                      </div>
                    </button>
                    <AnimatePresence initial={false}>
                      {open && (
                        <motion.div
                          initial={reduceMotion ? false : { height: 0, opacity: 0 }}
                          animate={{ height: "auto", opacity: 1 }}
                          exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
                          transition={{ duration: 0.22 }}
                          className="overflow-hidden border-t border-border"
                        >
                          <dl className="grid grid-cols-2 gap-2 p-4">
                            {HONEY_SIZES.map(size => (
                              <div key={size} className="rounded-lg bg-secondary/30 px-3 py-2">
                                <dt className="text-[11px] uppercase tracking-wide text-muted-foreground">{size}</dt>
                                <dd className="mt-0.5 text-base font-semibold tabular-nums">
                                  {grossQty(pool.by_size[sizeKey(size)] ?? 0)}
                                </dd>
                              </div>
                            ))}
                          </dl>
                          {canExecEdit && (
                            <div className="px-4 pb-4">
                              <Button
                                type="button"
                                variant="outline"
                                className="min-h-11 w-full"
                                onClick={() => openPoolEdit(pool)}
                              >
                                Edit pool on hand
                              </Button>
                            </div>
                          )}
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                );
              })}
            </div>
          )}

          <div className="mt-6 rounded-xl border border-border bg-card overflow-hidden">
            <button
              type="button"
              className="w-full flex items-center gap-3 px-4 py-3 text-left"
              onClick={() => setHistoryOpen(o => !o)}
              data-testid="packaging-scan-history-toggle"
            >
              <ChevronDown
                size={18}
                className={cn("shrink-0 text-muted-foreground transition-transform", historyOpen && "rotate-180")}
              />
              <div>
                <p className="text-sm font-semibold">Scan history</p>
                <p className="text-xs text-muted-foreground">Gross scanned by date and person (does not change on-hand).</p>
              </div>
            </button>
            <AnimatePresence initial={false}>
              {historyOpen && (
                <motion.div
                  initial={reduceMotion ? false : { height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
                  transition={{ duration: 0.22 }}
                  className="overflow-hidden border-t border-border"
                >
                  <div className="p-4 space-y-3">
                    <label
                      htmlFor="packaging-scan-date-filter"
                      className="text-xs uppercase tracking-wide text-muted-foreground font-semibold block w-fit"
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
                        <option value={localToday()}>Today · {scanDateLabel(localToday())}</option>
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
                    {historyLoading ? (
                      <p className="text-sm text-muted-foreground">
                        <Loader2 className="mr-2 inline animate-spin" size={14} /> Loading…
                      </p>
                    ) : (
                      <>
                        <p className="text-sm">
                          Gross scanned · <span className="font-semibold tabular-nums">{grossQty(scannedGrandTotal)}</span>
                        </p>
                        {byScanner.length > 0 ? (
                          <ul className="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
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
                        ) : (
                          <p className="text-sm text-muted-foreground">No scans for this filter.</p>
                        )}
                      </>
                    )}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>

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
                    <Link to="/inventory/raw-materials/honey-comb" className="underline hover:text-foreground">
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
            void refreshPools();
          }}
          panelClassName="max-w-lg w-full"
          labelledBy={combScanTitleId}
        >
          <AppModalHeader
            title="Scan Honey Comb jars to inventory"
            titleId={combScanTitleId}
            onClose={() => {
              setCombModal(null);
              void refreshPools();
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
            void refreshPools();
          }}
          panelClassName="max-w-2xl w-full"
          labelledBy={combAdminTitleId}
        >
          <AppModalHeader
            title="Generate / print Honey Comb requests"
            titleId={combAdminTitleId}
            onClose={() => {
              setCombModal(null);
              void refreshPools();
            }}
          />
          <AppModalBody>
            <DeptPouchAdminPanel productKey={HONEY_COMB_KEY} productLabel={HONEY_COMB_LABEL} />
          </AppModalBody>
        </AppModal>
      )}

      {traceBatch && <BatchTraceabilityDrawer query={traceBatch} onClose={() => setTraceBatch(null)} />}
      {editRow && (
        <ExecutiveRowEditModal
          stage="packaging"
          row={editRow}
          onClose={() => setEditRow(null)}
          onSaved={() => {
            setEditRow(null);
            setEditSavedNotice("Packaging pool on-hand updated.");
            window.setTimeout(() => setEditSavedNotice(null), 3500);
            void refreshPools();
          }}
        />
      )}
    </PageLayout>
  );
}
