import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { AlertTriangle, ChevronDown, Loader2, Printer, ScanLine, Tag } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { HoneyStageContent, HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
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
  appHeaderActionClass,
  appHoverCard,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { HONEY_SIZES } from "@/app/lib/honey";
import { canDeptPouchBarcodeOps } from "@/app/lib/honeyPermissions";
import { deptPouchBarcodesApi } from "@/app/lib/deptPouchBarcodes";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { variantLabel } from "@/app/lib/posHoney";
import { cn } from "@/app/components/ui/utils";

const HONEY_COMB_KEY = "honey_comb";
const HONEY_COMB_LABEL = "Honey Comb";
const VARIANT_ORDER = ["light", "amber", "dark"] as const;

type SizeKey = (typeof HONEY_SIZES)[number];

interface VariantScanned {
  variant: string;
  total: number;
  liter: number;
  "250ml": number;
  "350ml": number;
  gallon: number;
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

function filterScanLabel(value: string): string {
  if (value === "all") return "All scan dates";
  if (value === localToday()) return `Today · ${scanDateLabel(value)}`;
  return scanDateLabel(value);
}

function grossQty(value: string | number): string {
  const qty = Number(value);
  return Number.isFinite(qty) ? qty.toLocaleString() : String(value);
}

function emptyVariant(variant: string): VariantScanned {
  return { variant, total: 0, liter: 0, "250ml": 0, "350ml": 0, gallon: 0 };
}

function sizeQty(v: VariantScanned, size: SizeKey): number {
  return Number(v[size]) || 0;
}

export function HoneyPackagingPage() {
  const { user, can } = useAuth();
  const reduceMotion = useReducedMotion();
  const canLeakDamage = can("inventory.leak_damage.view");
  const canCombBarcodes = canDeptPouchBarcodeOps(user, "honey_comb");
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [byVariant, setByVariant] = useState<VariantScanned[]>([]);
  const [grandTotal, setGrandTotal] = useState(0);
  const [byScanner, setByScanner] = useState<{ user_id: number | null; name: string; qty: number }[]>([]);
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
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

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await bottleBarcodesApi.packagingScanReport(scanDate, "all");
      setScanDates(data.dates);
      setGrandTotal(data.totals?.total ?? 0);
      setByVariant(data.totals?.by_variant ?? []);
      setByScanner(data.by_scanner ?? []);
      await refreshPendingComb();
      setLoadError(null);
    } catch (error) {
      setLoadError(error instanceof Error ? error.message : "Could not load stock-in report.");
    } finally {
      setLoading(false);
    }
  }, [refreshPendingComb, scanDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const variantCards: VariantScanned[] = VARIANT_ORDER.map(v => {
    const found = byVariant.find(row => row.variant.toLowerCase() === v);
    return found ? { ...found, variant: v } : emptyVariant(v);
  });

  return (
    <PageLayout
      title="Raw Honey"
      subtitle="Packaging — bottles scanned into inventory (report by date)"
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
              <Button type="button" variant="outline" className="min-h-11" onClick={() => void refresh()}>
                Retry loading
              </Button>
            </div>
          )}

          <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h2 className="text-sm font-semibold mb-1">Stocked into inventory</h2>
              <p className="text-xs text-muted-foreground">
                Gross bottles scanned in for the selected date. This does not change when packing or POS sells — live
                finished on hand (and executive Edit) is on{" "}
                <Link to="/inventory/products/honey" className="underline hover:text-foreground font-medium">
                  Products → Honey
                </Link>
                . Editing this report would not change stock.
              </p>
            </div>
            <label
              htmlFor="packaging-scan-date-filter"
              className="text-xs uppercase tracking-wide text-muted-foreground font-semibold shrink-0"
            >
              Scan date
              <select
                id="packaging-scan-date-filter"
                name="packaging_scan_date"
                value={scanDate}
                onChange={e => setScanDate(e.target.value)}
                className="mt-1 block min-h-11 min-w-[12rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
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
          </div>

          {scanDate !== "all" && (
            <div
              className="mb-3 flex flex-wrap items-center gap-2"
              data-testid="packaging-active-filters"
              aria-live="polite"
            >
              <span className="text-xs text-muted-foreground">Showing</span>
              <AppPressable
                type="button"
                className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-border bg-card px-2.5 text-xs font-medium"
                onClick={() => setScanDate("all")}
              >
                {filterScanLabel(scanDate)}
                <span className="text-muted-foreground">×</span>
              </AppPressable>
            </div>
          )}

          {!loading && (
            <p className="mb-3 text-sm text-muted-foreground" aria-live="polite">
              {filterScanLabel(scanDate)} ·{" "}
              <span className="font-semibold text-foreground tabular-nums">{grossQty(grandTotal)}</span> bottle
              {grandTotal === 1 ? "" : "s"} scanned in
            </p>
          )}

          {loading ? (
            <div className="rounded-xl border border-border bg-card px-4 py-8 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-3 md:grid-cols-3" data-testid="honey-packaging-stocked-cards">
              {variantCards.map(pool => {
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
                        <p className="mt-2 text-3xl font-bold tabular-nums">{grossQty(pool.total)}</p>
                        <p className="text-xs text-muted-foreground">scanned into inventory</p>
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
                                  {grossQty(sizeQty(pool, size))}
                                </dd>
                              </div>
                            ))}
                          </dl>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                );
              })}
            </div>
          )}

          {!loading && byScanner.length > 0 && (
            <div className="mt-4 rounded-xl border border-border bg-secondary/20 px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                Scanned by person · {filterScanLabel(scanDate)}
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

          {!loading && grandTotal === 0 && (
            <p className="mt-4 text-sm text-muted-foreground">
              No bottles scanned into inventory for {filterScanLabel(scanDate).toLowerCase()}. Stock-in from Production
              first.
            </p>
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

      {traceBatch && <BatchTraceabilityDrawer query={traceBatch} onClose={() => setTraceBatch(null)} />}
    </PageLayout>
  );
}
