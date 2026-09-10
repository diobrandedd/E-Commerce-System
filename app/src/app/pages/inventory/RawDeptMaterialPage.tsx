import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { BookOpen, AlertTriangle, History, Loader2, Minus, MoreHorizontal, Plus, Package, Printer, ScanLine, Tag } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";
import { canMutateDeptRawProduction, canDeptPouchBarcodeOps } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import {
  deptStockInYieldHint,
  deptSupplierCategoryForProduct,
  PT_TEA_BAGS_PER_POUCH,
  ptTeaBagsPerBag,
  type DeptSupplierProductKey,
} from "@/app/lib/deptProducts";
import { type RawMaterialRoute } from "@/app/lib/inventoryRoutes";
import {
  deptPtMakeTeaBags,
  deptPtUseRawBags,
  type DeptProductionEntry,
  deptRawStockIn,
  deptRawStockOut,
  deptWipWaste,
  fetchDeptProductionPreview,
  fetchDeptProductionDaily,
  fetchDeptRawDetail,
  recordDeptProductionDaily,
  type DeptProductionDailyRow,
  type DeptProductionPreviewLine,
  type DeptRawSnapshot,
} from "@/app/lib/deptRawInventory";
import { listSuppliers, type Supplier } from "@/app/lib/suppliers";
import { deptPouchBarcodesApi, deptPouchBarcodesEnabled, deptPouchUnitLabel } from "@/app/lib/deptPouchBarcodes";
import {
  AppErrorBanner,
  AppPressable,
  AppSuccessNotice,
  appHoverCard,
  appHoverRow,
  appHeaderActionClass,
} from "@/app/components/layout/appFeedbackMotion";
import {
  DeptPouchAdminPanel,
  DeptPouchRequestsPanel,
  DeptPouchScanInPanel,
} from "@/app/components/dept/DeptPouchBarcodePanels";
import { ParagisBagPoolStrip } from "@/app/components/dept/ParagisBagPoolStrip";
import { DeptRawProduceGuideModal } from "@/app/components/inventory/DeptRawProduceGuideModal";
import { ParagisProductionGuideModal } from "@/app/components/inventory/ParagisProductionGuideModal";
import { runDeptRawProduceTour } from "@/app/components/tour/guides/deptRawProduceTour";
import { runParagisProductionTour } from "@/app/components/tour/guides/paragisProductionTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";

function formatWhen(raw: string | null | undefined) {
  if (!raw) return "—";
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function entryLabel(entry: DeptRawSnapshot["entries"][number]) {
  const note = entry.supplier_batch_no?.trim();
  if (note) return note;
  if (entry.qty < 0) return "Stock out";
  return entry.supplier_name ?? "Stock in";
}

function productionActionLabel(action: string): string {
  const map: Record<string, string> = {
    use_raw_bags: "Use raw bags",
    make_tea_bags: "Make tea bags (exact count)",
    pouch: "Pouch",
    produce_brc: "Produce pouches",
    produce_jar: "Produce jars",
    produce_comb: "Produce jars (legacy)",
    wip_waste: "WIP waste",
  };
  return map[action] ?? action.replace(/_/g, " ");
}

function formatProductionQty(entry: DeptProductionEntry): string {
  if (entry.action === "use_raw_bags") {
    const tea = Math.round(entry.qty * ptTeaBagsPerBag());
    return `${entry.qty} bag${entry.qty === 1 ? "" : "s"} → ~${tea.toLocaleString()} tea bags`;
  }
  if (entry.action === "make_tea_bags") {
    return `${entry.qty.toLocaleString()} tea bags`;
  }
  if (entry.action === "pouch") {
    return `${entry.qty.toLocaleString()} pouch${entry.qty === 1 ? "" : "es"}`;
  }
  if (entry.action === "wip_waste") {
    return `${entry.qty.toLocaleString()} tea bags wasted`;
  }
  return entry.qty.toLocaleString();
}

interface RawDeptMaterialPageProps {
  route: RawMaterialRoute;
}

export function RawDeptMaterialPage({ route }: RawDeptMaterialPageProps) {
  const { user } = useAuth();
  const canMutate = canMutateDeptRawProduction(user, route.productKey);
  const canPouchBarcodes = canDeptPouchBarcodeOps(user, route.productKey);
  const productKey = route.productKey;
  const isParagis = productKey === "pt";
  const isSingleStageProduce = productKey === "brc" || productKey === "jar_cacao" || productKey === "honey_comb";
  const showGuide = isParagis || isSingleStageProduce;
  /** Products stocked in by scanning pouch stickers instead of a Produce/Pouch button. */
  const hasPouchBarcodes = deptPouchBarcodesEnabled(productKey);
  /**
   * Honey Comb barcode UI lives on Raw Honey → Packaging (below bottle batches).
   * Other dept products keep Request/Scan/Generate on this raw page.
   */
  const showPouchBarcodes = hasPouchBarcodes && canPouchBarcodes && productKey !== "honey_comb";
  const pouchUnit = deptPouchUnitLabel(productKey);
  const dailyProduce =
    productKey === "pt"
      ? { logAction: "pouch", title: "Daily finished pouch record", unitPlural: "pouches", unitSingular: "pouch" }
      : productKey === "brc"
        ? { logAction: "produce_brc", title: "Pouch creation", unitPlural: "pouches", unitSingular: "pouch" }
        : productKey === "jar_cacao"
          ? { logAction: "produce_jar", title: "Jar creation", unitPlural: "jars", unitSingular: "jar" }
          : null;
  const [data, setData] = useState<DeptRawSnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);
  const [showWarehouseWaste, setShowWarehouseWaste] = useState(false);
  const [showUseRawBags, setShowUseRawBags] = useState(false);
  const [showMakeTeaBagsAdvanced, setShowMakeTeaBagsAdvanced] = useState(false);
  const [showWipWaste, setShowWipWaste] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const [pouchLogTick, setPouchLogTick] = useState(0);
  const [pouchModal, setPouchModal] = useState<"request" | "scan" | "admin" | "history" | null>(null);
  const [pendingPouchLabels, setPendingPouchLabels] = useState(0);
  const pouchRequestTitleId = useId();
  const pouchScanTitleId = useId();
  const pouchAdminTitleId = useId();
  const pouchHistoryTitleId = useId();

  const refreshPendingPouchLabels = useCallback(async () => {
    if (!showPouchBarcodes) {
      setPendingPouchLabels(0);
      return;
    }
    try {
      const res = await deptPouchBarcodesApi.pendingCount(productKey);
      setPendingPouchLabels(res.count);
    } catch {
      setPendingPouchLabels(0);
    }
  }, [productKey, showPouchBarcodes]);

  const refresh = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      setData(await fetchDeptRawDetail(route.slug));
      await refreshPendingPouchLabels();
    } catch (err) {
      setData(null);
      setLoadError(err instanceof ApiError ? err.message : "Could not load raw inventory.");
    } finally {
      setLoading(false);
    }
  }, [refreshPendingPouchLabels, route.slug]);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const wipTeaBags = data?.wip_tea_bags ?? 0;
  const maxPouchesFromWip = Math.floor(wipTeaBags / PT_TEA_BAGS_PER_POUCH);

  return (
    <PageLayout
      title={route.title}
      subtitle={route.subtitle}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <Link
            to={route.finishedProductPath}
            className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
          >
            {route.finishedTitle} →
          </Link>
          {showGuide && (
            <button
              type="button"
              onClick={() => setGuideOpen(true)}
              className={appHeaderActionClass}
            >
              <BookOpen size={13} /> Guide
            </button>
          )}
        </div>
      }
    >
      {loading ? (
        <div className="py-16 text-center text-muted-foreground">
          <Loader2 className="inline animate-spin" size={20} />
        </div>
      ) : !data ? (
        <div>
          <AppErrorBanner message={loadError ?? "Could not load raw inventory."} />
          <Button type="button" variant="outline" className="min-h-11" onClick={() => void refresh()}>
            Retry loading
          </Button>
        </div>
      ) : (
        <div className="space-y-5">
          {productKey === "honey_comb" && (
            <div className="rounded-xl border border-[#C8900A]/30 bg-[#C8900A]/5 px-4 py-3 text-sm text-[#8a6506]">
              Finished Honey Comb jars are stocked in with barcodes on{" "}
              <Link
                to="/inventory/raw-materials/honey/packaging#honey-comb-barcodes"
                className="font-semibold underline hover:text-foreground"
              >
                Raw Honey → Packaging
              </Link>
              . Use this page for stock-in raw bags and bag waste only.
            </div>
          )}
          <div className={`grid gap-3 ${isParagis ? "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3" : "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"}`} data-tour="raw-dept-metrics">
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Raw bags</p>
              <p className="text-2xl font-semibold mt-1">{data.bags_on_hand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{data.unit}s in bag pool</p>
            </div>
            {showPouchBarcodes ? (
              <button
                type="button"
                onClick={() => setPouchModal("history")}
                className={`rounded-xl border bg-card px-4 py-3 text-left w-full ${appHoverCard}`}
              >
                <p className="text-[10px] uppercase tracking-wide text-muted-foreground">
                  Finished {data.inventory_unit}s
                </p>
                <p className="text-2xl font-semibold mt-1">{data.finished_on_hand.toLocaleString()}</p>
                <p className="text-[10px] text-muted-foreground mt-0.5">
                  {data.finished_label} · tap for scan history
                </p>
              </button>
            ) : (
              <div className="rounded-xl border bg-card px-4 py-3">
                <p className="text-[10px] uppercase tracking-wide text-muted-foreground">
                  Finished {data.inventory_unit}s
                </p>
                <p className="text-2xl font-semibold mt-1">{data.finished_on_hand.toLocaleString()}</p>
                <p className="text-[10px] text-muted-foreground mt-0.5">{data.finished_label}</p>
              </div>
            )}
            <div className="rounded-xl border bg-card px-4 py-3 col-span-2 lg:col-span-1">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Costing stock</p>
              <p className="text-sm font-semibold mt-1 leading-snug">{data.stock_name ?? "—"}</p>
            </div>
          </div>

          {isParagis && data.bag_pool ? <ParagisBagPoolStrip pool={data.bag_pool} /> : null}

          {(canMutate || showPouchBarcodes) && (
            <RawDeptInlineActions
                isParagis={isParagis}
                finishedProductPath={route.finishedProductPath}
                finishedTitle={route.finishedTitle}
                bagsOnHand={data.bags_on_hand}
                canMutate={canMutate}
                showPouchBarcodes={showPouchBarcodes}
                unitSingular={pouchUnit.singular}
                unitPlural={pouchUnit.plural}
                onStockIn={() => setShowStockIn(true)}
                onRequestBarcodes={() => setPouchModal("request")}
                onScanPouches={() => setPouchModal("scan")}
                onScanHistory={() => setPouchModal("history")}
                onGeneratePrint={() => setPouchModal("admin")}
                pendingPouchLabels={pendingPouchLabels}
                onBagWaste={() => setShowWarehouseWaste(true)}
            />
          )}

          {dailyProduce && (
            <DailyProductionTable
              productKey={productKey}
              logAction={dailyProduce.logAction}
              title={dailyProduce.title}
              unitPlural={dailyProduce.unitPlural}
              unitSingular={dailyProduce.unitSingular}
              canMutate={canMutate}
              refreshTick={pouchLogTick}
              onRecorded={() => setPouchLogTick((t) => t + 1)}
            />
          )}

          <div className="rounded-xl border bg-card overflow-hidden" data-tour="raw-dept-production-log">
            <div className="px-5 py-4 border-b bg-secondary/30">
              <p className="text-sm font-semibold">Production activity</p>
            </div>
            {(data.production_entries?.length ?? 0) === 0 ? (
              <p className="px-5 py-8 text-sm text-muted-foreground text-center">No production entries yet.</p>
            ) : (
              <>
              <div className="sm:hidden divide-y divide-border">
                {data.production_entries.map(e => (
                  <div key={e.id} className="px-4 py-3 space-y-1.5">
                    <p className="text-[11px] text-muted-foreground">{formatWhen(e.created_at)}</p>
                    <p className="text-sm font-medium text-foreground">{productionActionLabel(e.action)}</p>
                    <p className="text-sm">{formatProductionQty(e)}</p>
                    <p className="text-xs text-muted-foreground">{e.note ?? "—"}</p>
                  </div>
                ))}
              </div>
              <div className="hidden sm:block overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/20">
                      {["When", "Action", "Qty", "Note"].map(h => (
                        <th key={h} className="text-left px-4 py-2.5 text-xs font-semibold text-muted-foreground uppercase">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {data.production_entries.map(e => (
                      <tr key={e.id} className="border-b last:border-0 hover:bg-secondary/10">
                        <td className="px-4 py-2.5 text-muted-foreground whitespace-nowrap">{formatWhen(e.created_at)}</td>
                        <td className="px-4 py-2.5">{productionActionLabel(e.action)}</td>
                        <td className="px-4 py-2.5 font-medium">{formatProductionQty(e)}</td>
                        <td className="px-4 py-2.5 text-muted-foreground">{e.note ?? "—"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              </>
            )}
          </div>

          <div className="rounded-xl border bg-card overflow-hidden" data-tour="raw-dept-activity">
            <div className="px-5 py-4 border-b bg-secondary/30">
              <p className="text-sm font-semibold">Raw bag entries</p>
            </div>
            {data.entries.length === 0 ? (
              <p className="px-5 py-8 text-sm text-muted-foreground text-center">No bag entries yet.</p>
            ) : (
              <>
              <div className="sm:hidden divide-y divide-border">
                {data.entries.map(e => (
                  <div key={e.id} className="px-4 py-3 space-y-1.5">
                    <p className="text-[11px] text-muted-foreground">{formatWhen(e.created_at)}</p>
                    <p className="text-sm font-medium text-foreground">{e.supplier_name ?? "—"}</p>
                    <p className={`text-sm font-medium ${e.qty < 0 ? "text-red-700" : ""}`}>
                      {e.qty > 0 ? "+" : ""}{e.qty} {data.unit}
                    </p>
                    <p className="text-xs text-muted-foreground">{entryLabel(e)}</p>
                  </div>
                ))}
              </div>
              <div className="hidden sm:block overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/20">
                      {["When", "Supplier", "Qty", "Note"].map(h => (
                        <th key={h} className="text-left px-4 py-2.5 text-xs font-semibold text-muted-foreground uppercase">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {data.entries.map(e => (
                      <tr key={e.id} className="border-b last:border-0 hover:bg-secondary/10">
                        <td className="px-4 py-2.5 text-muted-foreground whitespace-nowrap">{formatWhen(e.created_at)}</td>
                        <td className="px-4 py-2.5">{e.supplier_name ?? "—"}</td>
                        <td className={`px-4 py-2.5 font-medium ${e.qty < 0 ? "text-red-700" : ""}`}>
                          {e.qty > 0 ? "+" : ""}{e.qty} {data.unit}
                        </td>
                        <td className="px-4 py-2.5 text-muted-foreground">{entryLabel(e)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              </>
            )}
          </div>
        </div>
      )}

      {isParagis && (
        <ParagisProductionGuideModal
          open={guideOpen}
          onClose={() => setGuideOpen(false)}
          role={user?.role}
          onStartTour={() => {
            setGuideOpen(false);
            startPageTourAfterModalClose(() => runParagisProductionTour());
          }}
        />
      )}
      {isSingleStageProduce && productKey !== "honey_comb" && (
        <DeptRawProduceGuideModal
          productKey={productKey}
          open={guideOpen}
          onClose={() => setGuideOpen(false)}
          role={user?.role}
          onStartTour={() => {
            setGuideOpen(false);
            startPageTourAfterModalClose(() => runDeptRawProduceTour(productKey));
          }}
        />
      )}
      {productKey === "honey_comb" && (
        <DeptRawProduceGuideModal
          productKey="honey_comb"
          open={guideOpen}
          onClose={() => setGuideOpen(false)}
          role={user?.role}
          onStartTour={() => {
            setGuideOpen(false);
          }}
        />
      )}
      {showStockIn && (
        <RawStockInModal
          productKey={productKey}
          unit={data?.unit ?? "bag"}
          onClose={() => setShowStockIn(false)}
          onDone={() => { setShowStockIn(false); void refresh(); }}
        />
      )}
      {showWarehouseWaste && data && (
        <WarehouseWasteModal
          productKey={productKey}
          unit={data.unit}
          maxQty={data.bags_on_hand}
          onClose={() => setShowWarehouseWaste(false)}
          onDone={() => { setShowWarehouseWaste(false); void refresh(); }}
        />
      )}
      {showUseRawBags && data && (
        <UseRawBagsModal
          maxBags={data.bags_on_hand}
          onClose={() => setShowUseRawBags(false)}
          onDone={() => { setShowUseRawBags(false); void refresh(); }}
        />
      )}
      {showMakeTeaBagsAdvanced && (
        <ProductionQtyModal
          title="Make tea bags (exact count)"
          label="Tea bags produced"
          unit="tea bags"
          productKey="pt"
          action="make_tea_bags"
          hint="Advanced: only use when you have an exact count. For normal runs, use Use raw bags instead."
          onClose={() => setShowMakeTeaBagsAdvanced(false)}
          onSubmit={(qty, note) => deptPtMakeTeaBags({ qty, note })}
          onDone={() => { setShowMakeTeaBagsAdvanced(false); void refresh(); }}
        />
      )}
      {pouchModal === "request" && (
        <AppModal open onClose={() => setPouchModal(null)} panelClassName="max-w-2xl w-full" labelledBy={pouchRequestTitleId}>
          <AppModalHeader title={`Request ${pouchUnit.singular} barcodes`} titleId={pouchRequestTitleId} onClose={() => setPouchModal(null)} />
          <AppModalBody>
            <DeptPouchRequestsPanel productKey={productKey} productLabel={route.finishedTitle} />
          </AppModalBody>
        </AppModal>
      )}
      {pouchModal === "scan" && (
        <AppModal open onClose={() => { setPouchModal(null); void refresh(); }} panelClassName="max-w-lg w-full" labelledBy={pouchScanTitleId}>
          <AppModalHeader title={`Scan ${pouchUnit.plural} to inventory`} titleId={pouchScanTitleId} onClose={() => { setPouchModal(null); void refresh(); }} />
          <AppModalBody>
            <DeptPouchScanInPanel productKey={productKey} productLabel={route.finishedTitle} />
          </AppModalBody>
        </AppModal>
      )}
      {pouchModal === "admin" && (
        <AppModal open onClose={() => { setPouchModal(null); void refresh(); }} panelClassName="max-w-2xl w-full" labelledBy={pouchAdminTitleId}>
          <AppModalHeader title="Generate / print requests" titleId={pouchAdminTitleId} onClose={() => { setPouchModal(null); void refresh(); }} />
          <AppModalBody>
            <DeptPouchAdminPanel productKey={productKey} productLabel={route.finishedTitle} />
          </AppModalBody>
        </AppModal>
      )}
      {pouchModal === "history" && (
        <AppModal open onClose={() => setPouchModal(null)} panelClassName="max-w-3xl w-full" labelledBy={pouchHistoryTitleId}>
          <AppModalHeader
            title="Scan history — finished stock"
            titleId={pouchHistoryTitleId}
            onClose={() => setPouchModal(null)}
          />
          <AppModalBody>
            <DeptPouchRequestsPanel
              productKey={productKey}
              productLabel={route.finishedTitle}
              mode="history"
            />
          </AppModalBody>
        </AppModal>
      )}
      {showWipWaste && data && (
        <WipWasteModal
          maxQty={wipTeaBags}
          onClose={() => setShowWipWaste(false)}
          onDone={() => { setShowWipWaste(false); void refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function RawDeptInlineActions({
  isParagis,
  finishedProductPath,
  finishedTitle,
  bagsOnHand,
  canMutate,
  showPouchBarcodes,
  unitSingular,
  unitPlural,
  onStockIn,
  onRequestBarcodes,
  onScanPouches,
  onScanHistory,
  onGeneratePrint,
  pendingPouchLabels,
  onBagWaste,
}: {
  isParagis: boolean;
  finishedProductPath: string;
  finishedTitle: string;
  bagsOnHand: number;
  canMutate: boolean;
  showPouchBarcodes: boolean;
  unitSingular: string;
  unitPlural: string;
  onStockIn: () => void;
  onRequestBarcodes: () => void;
  onScanPouches: () => void;
  onScanHistory: () => void;
  onGeneratePrint: () => void;
  pendingPouchLabels: number;
  onBagWaste: () => void;
}) {
  const canBags = bagsOnHand > 0;
  const [mobileActionsOpen, setMobileActionsOpen] = useState(false);
  const runMobileAction = (action: () => void) => {
    setMobileActionsOpen(false);
    window.setTimeout(action, 0);
  };

  return (
    <div
      className="rounded-xl border bg-card overflow-hidden"
      data-tour="raw-dept-inline-actions"
    >
      <div className="px-4 py-3 border-b bg-secondary/30">
        <p className="text-sm font-semibold">Do next</p>
        <p className="text-xs text-muted-foreground mt-0.5">
          {isParagis && showPouchBarcodes
            ? "Stock raw bags into the bag pool, then scan pouches — each scan deducts leaf from the pool."
            : "Production steps — choose the action you need."}
        </p>
      </div>
      <div className="p-3 sm:hidden space-y-2">
        {showPouchBarcodes ? (
          <AppPressable
            variant="primary"
            onClick={onScanPouches}
            className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
          >
            <ScanLine size={16} /> Scan {unitPlural} to inventory
          </AppPressable>
        ) : canMutate ? (
          <AppPressable
            variant="primary"
            onClick={onStockIn}
            className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
          >
            <Plus size={16} /> Stock in raw bags
          </AppPressable>
        ) : null}
        {canMutate && showPouchBarcodes ? (
          <AppPressable
            variant="outline"
            onClick={onStockIn}
            className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
          >
            <Plus size={16} /> Stock in raw bags
          </AppPressable>
        ) : null}
        <AppPressable
          variant="outline"
          onClick={() => setMobileActionsOpen(true)}
          className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
        >
          <MoreHorizontal size={16} /> More actions
        </AppPressable>
      </div>
      <div className="hidden p-3 sm:grid sm:grid-cols-2 xl:grid-cols-3 gap-2">
        {canMutate && (
          <AppPressable
            variant="primary"
            onClick={onStockIn}
            className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold"
          >
            <Plus size={16} /> Stock in raw bags
          </AppPressable>
        )}
        {showPouchBarcodes && (
          <>
            <AppPressable
              variant="outline"
              onClick={onRequestBarcodes}
              className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold border-[#C8900A] text-[#8a6506]"
            >
              <Tag size={16} /> Request {unitSingular} barcodes
            </AppPressable>
            <AppPressable
              variant="outline"
              onClick={onScanPouches}
              className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold border-[#C8900A] text-[#8a6506]"
            >
              <ScanLine size={16} /> Scan {unitPlural} to inventory
            </AppPressable>
            <AppPressable
              variant="outline"
              onClick={onScanHistory}
              className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold border-[#C8900A] text-[#8a6506]"
            >
              <History size={16} /> Scan history
            </AppPressable>
            {showPouchBarcodes && (
              <Link
                to="/inventory/leak-damage"
                className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold border border-red-300 text-red-700 rounded-md px-3 hover:bg-red-50 transition-colors"
              >
                <AlertTriangle size={16} /> Leak / damage scan
              </Link>
            )}
            <AppPressable
              variant="outline"
              onClick={onGeneratePrint}
              className="relative w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold border-[#C8900A] text-[#8a6506]"
            >
              <Printer size={16} /> Generate / print
              {pendingPouchLabels > 0 ? (
                <span className="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold text-white">
                  {pendingPouchLabels > 99 ? "99+" : pendingPouchLabels}
                </span>
              ) : null}
            </AppPressable>
          </>
        )}
        {canMutate && (
          <AppPressable
            variant="outline"
            disabled={!canBags}
            onClick={onBagWaste}
            className="w-full inline-flex items-center justify-start gap-2 py-3 text-sm font-semibold"
          >
            <Minus size={16} /> Bag waste
          </AppPressable>
        )}
        <Link
          to={finishedProductPath}
          className="w-full inline-flex items-center justify-start gap-2 py-3 px-3 text-sm font-semibold rounded-xl border border-border hover:bg-secondary"
        >
          <Package size={16} /> {finishedTitle} →
        </Link>
      </div>
      <AppModal
        open={mobileActionsOpen}
        onClose={() => setMobileActionsOpen(false)}
        panelClassName="max-w-md w-full"
        labelledBy="raw-dept-mobile-actions-title"
      >
        <AppModalHeader
          title="Raw material actions"
          titleId="raw-dept-mobile-actions-title"
          subtitle="Barcode tools, waste, and finished product."
          onClose={() => setMobileActionsOpen(false)}
        />
        <AppModalBody className="space-y-2">
          {showPouchBarcodes ? (
            <>
              <AppPressable
                variant="outline"
                onClick={() => runMobileAction(onRequestBarcodes)}
                className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
              >
                <Tag size={16} /> Request {unitSingular} barcodes
              </AppPressable>
              <AppPressable
                variant="outline"
                onClick={() => runMobileAction(onScanHistory)}
                className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
              >
                <History size={16} /> Scan history
              </AppPressable>
              <AppPressable
                variant="outline"
                onClick={() => runMobileAction(onGeneratePrint)}
                className="relative min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
              >
                <Printer size={16} /> Generate / print
                {pendingPouchLabels > 0 ? (
                  <span className="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold text-white">
                    {pendingPouchLabels > 99 ? "99+" : pendingPouchLabels}
                  </span>
                ) : null}
              </AppPressable>
              <Link
                to="/inventory/leak-damage"
                className="min-h-11 w-full inline-flex items-center justify-start gap-2 rounded-xl border border-red-300 px-3 text-sm font-semibold text-red-700 transition-colors hover:bg-red-50"
              >
                <AlertTriangle size={16} /> Leak / damage scan
              </Link>
            </>
          ) : null}
          {canMutate ? (
            <AppPressable
              variant="outline"
              disabled={!canBags}
              onClick={() => runMobileAction(onBagWaste)}
              className="min-h-11 w-full inline-flex items-center justify-start gap-2 text-sm font-semibold"
            >
              <Minus size={16} /> Bag waste
            </AppPressable>
          ) : null}
          <Link
            to={finishedProductPath}
            className="min-h-11 w-full inline-flex items-center justify-start gap-2 rounded-xl border border-border px-3 text-sm font-semibold transition-colors hover:bg-secondary"
          >
            <Package size={16} /> Open {finishedTitle}
          </Link>
        </AppModalBody>
      </AppModal>
    </div>
  );
}

function ProductionPreviewPanel({
  deductLines,
  costOnlyLines,
}: {
  deductLines: DeptProductionPreviewLine[];
  costOnlyLines: DeptProductionPreviewLine[];
}) {
  if (deductLines.length === 0 && costOnlyLines.length === 0) return null;
  const peso = (n: number) =>
    `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  return (
    <div className="space-y-2">
      {deductLines.length > 0 && (
        <div className="rounded-lg border border-border bg-secondary/20 px-3 py-2 space-y-1">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Will deduct</p>
          {deductLines.map((l, i) => (
            <p key={i} className="text-xs text-muted-foreground">
              {l.qty.toLocaleString(undefined, { maximumFractionDigits: 4 })} {l.unit} — {l.stock_name}
            </p>
          ))}
        </div>
      )}
      {costOnlyLines.length > 0 && (
        <div className="rounded-lg border border-dashed border-border bg-secondary/10 px-3 py-2 space-y-1">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">Cost only (pool)</p>
          {costOnlyLines.map((l, i) => (
            <p key={i} className="text-xs text-muted-foreground">
              {l.qty.toLocaleString(undefined, { maximumFractionDigits: 4 })} {l.unit} — {l.stock_name}
              {l.estimated_cost != null && l.estimated_cost > 0 ? ` · est. ${peso(l.estimated_cost)}` : ""}
            </p>
          ))}
        </div>
      )}
    </div>
  );
}

function UseRawBagsModal({
  maxBags,
  onClose,
  onDone,
}: {
  maxBags: number;
  onClose: () => void;
  onDone: () => void;
}) {
  const [bags, setBags] = useState("");
  const [note, setNote] = useState("");
  const [deductPreview, setDeductPreview] = useState<DeptProductionPreviewLine[]>([]);
  const [costOnlyPreview, setCostOnlyPreview] = useState<DeptProductionPreviewLine[]>([]);
  const [derivedTeaBags, setDerivedTeaBags] = useState<number | null>(null);
  const [packablePouches, setPackablePouches] = useState<number | null>(null);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";
  const n = parseFloat(bags);
  const teaPerBag = ptTeaBagsPerBag();

  useEffect(() => {
    if (!Number.isFinite(n) || n <= 0) {
      setDeductPreview([]);
      setCostOnlyPreview([]);
      setDerivedTeaBags(null);
      setPackablePouches(null);
      return;
    }
    setDerivedTeaBags(Math.round(n * teaPerBag));
    setPackablePouches(Math.floor((n * teaPerBag) / PT_TEA_BAGS_PER_POUCH));
    setPreviewLoading(true);
    const t = window.setTimeout(() => {
      void fetchDeptProductionPreview("pt", "use_raw_bags", n)
        .then(res => {
          setDeductPreview(res.deduct_lines ?? res.lines);
          setCostOnlyPreview(res.cost_only_lines ?? []);
          if (res.derived_tea_bags != null) setDerivedTeaBags(Math.round(res.derived_tea_bags));
          if (res.packable_pouches != null) setPackablePouches(res.packable_pouches);
        })
        .catch(() => {
          setDeductPreview([]);
          setCostOnlyPreview([]);
        })
        .finally(() => setPreviewLoading(false));
    }, 300);
    return () => window.clearTimeout(t);
  }, [n, teaPerBag]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await deptPtUseRawBags({ qty: parseFloat(bags), note: note.trim() || undefined });
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not save.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="use-raw-bags-title">
      <AppModalHeader title="Use raw bags" titleId="use-raw-bags-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <AppErrorBanner message={error} />}
          <p className="text-xs text-muted-foreground">
            Each 1 kg bag yields ~{teaPerBag.toLocaleString()} tea bags at 2 g each. Enter how many bags you opened — no need to count every tea bag.
          </p>
          <div>
            <label htmlFor="use-raw-bags-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Bags opened (1 kg each) — max {maxBags.toLocaleString()}
            </label>
            <input
              id="use-raw-bags-qty"
              type="number"
              step="0.01"
              min="0.01"
              max={maxBags}
              value={bags}
              onChange={e => setBags(e.target.value)}
              required
              className={inputCls}
            />
          </div>
          {Number.isFinite(n) && n > 0 && derivedTeaBags != null && (
            <div className="rounded-lg border border-[#C8900A]/25 bg-[#C8900A]/5 px-3 py-2 text-xs text-[#8a6506]">
              <p className="font-semibold">Estimated WIP credit</p>
              <p className="mt-0.5">
                ~{derivedTeaBags.toLocaleString()} tea bags
                {packablePouches != null && packablePouches > 0
                  ? ` · enough for ~${packablePouches.toLocaleString()} pouch${packablePouches === 1 ? "" : "es"}`
                  : ""}
              </p>
            </div>
          )}
          {previewLoading && (
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <Loader2 size={12} className="animate-spin" /> Loading deduct preview…
            </p>
          )}
          {!previewLoading && (
            <ProductionPreviewPanel deductLines={deductPreview} costOnlyLines={costOnlyPreview} />
          )}
          <div>
            <label htmlFor="use-raw-bags-note" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input id="use-raw-bags-note" type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Confirm"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

function ProductionQtyModal({
  title,
  label,
  unit: _unit,
  productKey,
  action,
  maxQty,
  hint,
  onClose,
  onSubmit,
  onDone,
}: {
  title: string;
  label: string;
  unit: string;
  productKey: string;
  action: string;
  maxQty?: number;
  hint?: string;
  onClose: () => void;
  onSubmit: (qty: number, note?: string) => Promise<unknown>;
  onDone: () => void;
}) {
  const [qty, setQty] = useState("");
  const [note, setNote] = useState("");
  const [deductPreview, setDeductPreview] = useState<DeptProductionPreviewLine[]>([]);
  const [costOnlyPreview, setCostOnlyPreview] = useState<DeptProductionPreviewLine[]>([]);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";
  const n = parseFloat(qty);

  useEffect(() => {
    if (!Number.isFinite(n) || n <= 0) {
      setDeductPreview([]);
      setCostOnlyPreview([]);
      return;
    }
    setPreviewLoading(true);
    const t = window.setTimeout(() => {
      void fetchDeptProductionPreview(productKey, action, n)
        .then(res => {
          setDeductPreview(res.deduct_lines ?? res.lines);
          setCostOnlyPreview(res.cost_only_lines ?? []);
        })
        .catch(() => {
          setDeductPreview([]);
          setCostOnlyPreview([]);
        })
        .finally(() => setPreviewLoading(false));
    }, 300);
    return () => window.clearTimeout(t);
  }, [productKey, action, n]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await onSubmit(parseFloat(qty), note.trim() || undefined);
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not save.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="prod-qty-title">
      <AppModalHeader title={title} titleId="prod-qty-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <AppErrorBanner message={error} />}
          {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
          <div>
            <label htmlFor="production-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              {label}{maxQty != null ? ` — max ${maxQty}` : ""}
            </label>
            <input
              id="production-qty"
              type="number"
              step="1"
              min="1"
              max={maxQty}
              value={qty}
              onChange={e => setQty(e.target.value)}
              required
              className={inputCls}
            />
          </div>
          {previewLoading && (
            <p className="text-xs text-muted-foreground flex items-center gap-1">
              <Loader2 size={12} className="animate-spin" /> Loading deduct preview…
            </p>
          )}
          {!previewLoading && (
            <ProductionPreviewPanel deductLines={deductPreview} costOnlyLines={costOnlyPreview} />
          )}
          <div>
            <label htmlFor="production-qty-note" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input id="production-qty-note" type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Confirm"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

function RawStockInModal({
  productKey,
  unit,
  onClose,
  onDone,
}: {
  productKey: DeptSupplierProductKey;
  unit: string;
  onClose: () => void;
  onDone: () => void;
}) {
  const [qty, setQty] = useState("");
  const [supplierId, setSupplierId] = useState("");
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [suppliersLoading, setSuppliersLoading] = useState(true);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [note, setNote] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const supplierCategory = deptSupplierCategoryForProduct(productKey);
  const yieldHint = deptStockInYieldHint(productKey);
  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";

  useEffect(() => {
    setSuppliersLoading(true);
    void listSuppliers({ category: supplierCategory, active: true })
      .then(res => {
        setSuppliers(res.suppliers);
        if (res.suppliers.length === 1) setSupplierId(String(res.suppliers[0].id));
      })
      .catch(() => setSuppliers([]))
      .finally(() => setSuppliersLoading(false));
  }, [supplierCategory]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await deptRawStockIn({
        product_key: productKey,
        qty: parseFloat(qty),
        supplier_id: parseInt(supplierId, 10),
        arrival_date: arrival,
        note: note.trim() || undefined,
      });
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not save stock-in.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="raw-stock-in-title">
      <AppModalHeader title="Stock in raw bags" titleId="raw-stock-in-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <AppErrorBanner message={error} />}
          <div>
            <label htmlFor="raw-stock-in-supplier" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier</label>
            {suppliersLoading ? (
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                <Loader2 size={12} className="animate-spin" /> Loading suppliers…
              </p>
            ) : (
              <select id="raw-stock-in-supplier" value={supplierId} onChange={e => setSupplierId(e.target.value)} required className={inputCls}>
                <option value="">Select supplier…</option>
                {suppliers.map(s => (
                  <option key={s.id} value={s.id}>{s.display_name}</option>
                ))}
              </select>
            )}
            {yieldHint && <p className="text-xs text-muted-foreground mt-1">Price from Supplier Master (₱/{unit}). {yieldHint}</p>}
          </div>
          <div>
            <label htmlFor="raw-stock-in-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Quantity ({unit}s)</label>
            <input id="raw-stock-in-qty" type="number" step="0.01" min="0.01" value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label htmlFor="raw-stock-in-arrival" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Arrival date</label>
            <input id="raw-stock-in-arrival" type="date" value={arrival} onChange={e => setArrival(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label htmlFor="raw-stock-in-note" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input id="raw-stock-in-note" type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving || !supplierId} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Stock in raw bags"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

function WarehouseWasteModal({
  productKey,
  unit,
  maxQty,
  onClose,
  onDone,
}: {
  productKey: string;
  unit: string;
  maxQty: number;
  onClose: () => void;
  onDone: () => void;
}) {
  const [qty, setQty] = useState("");
  const [reason, setReason] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await deptRawStockOut({
        product_key: productKey,
        qty: parseFloat(qty),
        reason: reason.trim() || undefined,
      });
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not stock out.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="wh-waste-title">
      <AppModalHeader title="Waste sealed bags" titleId="wh-waste-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <AppErrorBanner message={error} />}
          <p className="text-xs text-muted-foreground">Deduct sealed supplier bags (spoilage, return, etc.).</p>
          <div>
            <label htmlFor="warehouse-waste-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Quantity ({unit}s) — max {maxQty.toLocaleString()}
            </label>
            <input id="warehouse-waste-qty" type="number" step="0.01" min="0.01" max={maxQty} value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label htmlFor="warehouse-waste-reason" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Reason</label>
            <input id="warehouse-waste-reason" type="text" value={reason} onChange={e => setReason(e.target.value)} required maxLength={120} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Stock out"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

function WipWasteModal({
  maxQty,
  onClose,
  onDone,
}: {
  maxQty: number;
  onClose: () => void;
  onDone: () => void;
}) {
  const [qty, setQty] = useState("");
  const [reason, setReason] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await deptWipWaste({ qty: parseFloat(qty), reason: reason.trim() || undefined });
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not record waste.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="wip-waste-title">
      <AppModalHeader title="WIP tea bag waste" titleId="wip-waste-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <AppErrorBanner message={error} />}
          <p className="text-xs text-muted-foreground">Remove spoiled or unusable filled tea bags from WIP (raw materials already consumed).</p>
          <div>
            <label htmlFor="wip-waste-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Tea bags — max {maxQty.toLocaleString()}
            </label>
            <input id="wip-waste-qty" type="number" step="1" min="1" max={maxQty} value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label htmlFor="wip-waste-reason" className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Reason</label>
            <input id="wip-waste-reason" type="text" value={reason} onChange={e => setReason(e.target.value)} required maxLength={120} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Record waste"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

function DailyProductionTable({
  productKey,
  logAction,
  title,
  unitPlural,
  unitSingular,
  canMutate,
  refreshTick,
  onRecorded,
}: {
  productKey: string;
  logAction: string;
  title: string;
  unitPlural: string;
  unitSingular: string;
  canMutate: boolean;
  refreshTick: number;
  onRecorded: () => void;
}) {
  const [date, setDate] = useState("");
  const [dateDraft, setDateDraft] = useState("");
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<DeptProductionDailyRow[]>([]);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [addOpen, setAddOpen] = useState(false);
  const [addDate, setAddDate] = useState("");
  const [addQty, setAddQty] = useState("");
  const [addNote, setAddNote] = useState("");
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const d = await fetchDeptProductionDaily({
        product_key: productKey,
        log_action: logAction,
        date: date || undefined,
        page,
        per_page: 10,
      });
      setRows(d.rows ?? []);
      setTotal(d.total ?? 0);
      setTotalPages(Math.max(1, d.total_pages ?? 1));
    } catch (err) {
      setRows([]);
      setError(err instanceof ApiError ? err.message : `Could not load ${title.toLowerCase()}.`);
    } finally {
      setLoading(false);
    }
  }, [date, page, productKey, logAction, title]);

  useEffect(() => {
    void load();
  }, [load, refreshTick]);

  useEffect(() => {
    if (!success) return;
    const t = window.setTimeout(() => setSuccess(null), 4000);
    return () => window.clearTimeout(t);
  }, [success]);

  const applyDate = () => {
    setPage(1);
    setDate(dateDraft);
  };

  const clearDate = () => {
    setDateDraft("");
    setDate("");
    setPage(1);
  };

  const formatDay = (isoDate: string) => {
    const d = new Date(`${isoDate}T12:00:00`);
    if (Number.isNaN(d.getTime())) return isoDate;
    return d.toLocaleDateString(undefined, {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };

  const formatQty = (n: number) => {
    const rounded = Math.round(n);
    const unit = rounded === 1 ? unitSingular : unitPlural;
    return `${rounded.toLocaleString()} ${unit}`;
  };

  const openAdd = () => {
    setAddDate(dateDraft || date || new Date().toISOString().slice(0, 10));
    setAddQty("");
    setAddNote("");
    setSaveError(null);
    setAddOpen(true);
  };

  const submitAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    const qty = parseFloat(addQty);
    if (!addDate || !Number.isFinite(qty) || qty <= 0) {
      setSaveError("Enter a date and a quantity greater than 0.");
      return;
    }
    setSaving(true);
    setSaveError(null);
    try {
      await recordDeptProductionDaily({
        product_key: productKey,
        qty,
        production_date: addDate,
        note: addNote.trim() || undefined,
      });
      setAddOpen(false);
      setSuccess(`Recorded ${formatQty(qty)} for ${formatDay(addDate)}.`);
      setPage(1);
      onRecorded();
    } catch (err) {
      setSaveError(err instanceof ApiError ? err.message : "Could not save.");
    } finally {
      setSaving(false);
    }
  };

  const inputCls =
    "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-[#C8900A]/40";

  return (
    <div className="rounded-xl border bg-card overflow-hidden" data-tour="raw-dept-daily-produce-log">
      <div className="px-5 py-4 border-b bg-secondary/30 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm font-semibold">{title}</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            Total {unitPlural} made per day — search by date, 10 days per page.
          </p>
        </div>
        <div className="flex flex-wrap items-end gap-2">
          <label className="text-xs space-y-1">
            <span className="text-muted-foreground">Date</span>
            <input
              type="date"
              value={dateDraft}
              onChange={(e) => setDateDraft(e.target.value)}
              className="block border border-border rounded-lg px-2.5 py-1.5 text-sm bg-background"
            />
          </label>
          <button
            type="button"
            onClick={applyDate}
            className="text-xs font-medium rounded-lg px-3 py-1.5 border border-border hover:bg-secondary/50"
          >
            Search
          </button>
          {date ? (
            <button
              type="button"
              onClick={clearDate}
              className="text-xs font-medium rounded-lg px-3 py-1.5 border border-border hover:bg-secondary/50"
            >
              Clear
            </button>
          ) : null}
          {canMutate ? (
            <Button type="button" size="sm" onClick={openAdd}>
              Add
            </Button>
          ) : null}
        </div>
      </div>

      {success ? (
        <div className="px-5 pt-3">
          <AppSuccessNotice message={success} />
        </div>
      ) : null}

      {error ? (
        <div className="px-5 py-3">
          <AppErrorBanner message={error} />
        </div>
      ) : null}

      {loading ? (
        <div className="px-5 py-8 flex items-center justify-center gap-2 text-sm text-muted-foreground">
          <Loader2 className="size-4 animate-spin" /> Loading…
        </div>
      ) : rows.length === 0 ? (
        <p className="px-5 py-8 text-sm text-muted-foreground text-center">
          {date ? `No ${unitPlural} made on this date.` : `No ${title.toLowerCase()} yet.`}
        </p>
      ) : (
        <>
          <div className="sm:hidden divide-y divide-border">
            {rows.map((e) => (
              <div key={e.production_date} className="px-4 py-3 space-y-1">
                <p className="text-sm font-medium">{formatDay(e.production_date)}</p>
                <p className="text-sm">{formatQty(e.qty_total)}</p>
                {e.run_count > 1 ? (
                  <p className="text-xs text-muted-foreground">{e.run_count} runs</p>
                ) : null}
              </div>
            ))}
          </div>
          <div className="hidden sm:block overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-secondary/20">
                  {["Date", `${unitPlural.charAt(0).toUpperCase()}${unitPlural.slice(1)} made`].map((h) => (
                    <th
                      key={h}
                      className="text-left px-4 py-2.5 text-xs font-semibold text-muted-foreground uppercase"
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {rows.map((e) => (
                  <tr key={e.production_date} className={`border-b last:border-0 ${appHoverRow}`}>
                    <td className="px-4 py-2.5 whitespace-nowrap">{formatDay(e.production_date)}</td>
                    <td className="px-4 py-2.5 font-medium">
                      {formatQty(e.qty_total)}
                      {e.run_count > 1 ? (
                        <span className="ml-2 text-xs font-normal text-muted-foreground">
                          ({e.run_count} runs)
                        </span>
                      ) : null}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}

      {!loading && total > 0 ? (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-5 py-3 border-t bg-secondary/10">
          <p className="text-xs text-muted-foreground">
            Showing {(page - 1) * 10 + 1}–{Math.min(page * 10, total)} of {total} day
            {total === 1 ? "" : "s"}
          </p>
          <div className="flex items-center gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <span className="text-xs text-muted-foreground">
              Page {page} of {totalPages}
            </span>
            <button
              type="button"
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      ) : null}

      {addOpen ? (
        <AppModal open onClose={() => setAddOpen(false)} panelClassName="max-w-md w-full" labelledBy="daily-prod-add-title">
          <AppModalHeader title={`Add ${title.toLowerCase()}`} titleId="daily-prod-add-title" onClose={() => setAddOpen(false)} />
          <form onSubmit={(e) => void submitAdd(e)}>
            <AppModalBody className="space-y-3">
              <p className="text-xs text-muted-foreground">
                Records {unitPlural} for that date only. Does not change stock or deduct packaging.
              </p>
              {saveError ? <AppErrorBanner message={saveError} /> : null}
              <div>
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
                  Date
                </label>
                <input
                  type="date"
                  value={addDate}
                  onChange={(e) => setAddDate(e.target.value)}
                  required
                  className={inputCls}
                />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
                  {unitPlural.charAt(0).toUpperCase() + unitPlural.slice(1)} made
                </label>
                <input
                  type="number"
                  min="0.01"
                  step="1"
                  value={addQty}
                  onChange={(e) => setAddQty(e.target.value)}
                  required
                  className={inputCls}
                  placeholder={`Number of ${unitPlural}`}
                />
              </div>
              <div>
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
                  Note (optional)
                </label>
                <input
                  type="text"
                  value={addNote}
                  onChange={(e) => setAddNote(e.target.value)}
                  maxLength={200}
                  className={inputCls}
                />
              </div>
            </AppModalBody>
            <AppModalFooter>
              <Button type="button" variant="outline" onClick={() => setAddOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={saving}>
                {saving ? "Saving…" : "Save"}
              </Button>
            </AppModalFooter>
          </form>
        </AppModal>
      ) : null}
    </div>
  );
}

