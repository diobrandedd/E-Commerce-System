import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { BookOpen, Loader2, Minus, Plus, Factory, Package, ListChecks } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";
import { canMutateHoneyInventory } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import {
  deptStockInYieldHint,
  deptSupplierCategoryForProduct,
  PT_TEA_BAGS_PER_POUCH,
  ptTeaBagsPerBag,
  type DeptSupplierProductKey,
} from "@/app/lib/deptProducts";
import { RAW_MATERIAL_ROUTES, type RawMaterialRoute } from "@/app/lib/inventoryRoutes";
import {
  deptBrcProduce,
  deptJarProduce,
  deptPtMakeTeaBags,
  deptPtUseRawBags,
  deptPtPouch,
  type DeptProductionEntry,
  deptRawStockIn,
  deptRawStockOut,
  deptWipWaste,
  fetchDeptProductionPreview,
  fetchDeptRawDetail,
  type DeptProductionPreviewLine,
  type DeptRawSnapshot,
} from "@/app/lib/deptRawInventory";
import { listSuppliers, type Supplier } from "@/app/lib/suppliers";
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
  const canMutate = canMutateHoneyInventory(user?.role);
  const productKey = route.productKey;
  const isParagis = productKey === "pt";
  const isSingleStageProduce = productKey === "brc" || productKey === "jar_cacao";
  const showGuide = isParagis || isSingleStageProduce;
  const [data, setData] = useState<DeptRawSnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);
  const [showWarehouseWaste, setShowWarehouseWaste] = useState(false);
  const [showUseRawBags, setShowUseRawBags] = useState(false);
  const [showMakeTeaBagsAdvanced, setShowMakeTeaBagsAdvanced] = useState(false);
  const [showPouch, setShowPouch] = useState(false);
  const [showWipWaste, setShowWipWaste] = useState(false);
  const [showProduce, setShowProduce] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const [actionsOpen, setActionsOpen] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      setData(await fetchDeptRawDetail(route.slug));
    } catch (err) {
      setData(null);
      setLoadError(err instanceof ApiError ? err.message : "Could not load raw inventory.");
    } finally {
      setLoading(false);
    }
  }, [route.slug]);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const wipTeaBags = data?.wip_tea_bags ?? 0;
  const maxPouchesFromWip = Math.floor(wipTeaBags / PT_TEA_BAGS_PER_POUCH);

  return (
    <PageLayout
      title={route.title}
      subtitle={route.subtitle}
      actions={
        <div className="flex items-center gap-2">
          <div className="sm:hidden flex items-center gap-2">
            {showGuide && (
              <button
                type="button"
                onClick={() => setGuideOpen(true)}
                className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground px-2.5 py-1.5 rounded-lg border border-border"
              >
                <BookOpen size={12} /> Guide
              </button>
            )}
            {canMutate && (
              <button
                type="button"
                onClick={() => setActionsOpen(true)}
                className="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-[#C8900A] text-[#8a6506]"
              >
                <ListChecks size={12} /> Actions
              </button>
            )}
          </div>

          <div className="hidden sm:flex flex-wrap items-center gap-2">
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
              className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
            >
              <BookOpen size={13} /> Guide
            </button>
          )}
          {canMutate && (
            <>
              <button
                type="button"
                onClick={() => setShowStockIn(true)}
                className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
                style={{ background: "#C8900A" }}
              >
                <Plus size={13} /> Stock in
              </button>
              {isParagis ? (
                <>
                  <button
                    type="button"
                    disabled={(data?.bags_on_hand ?? 0) <= 0}
                    onClick={() => setShowUseRawBags(true)}
                    className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#C8900A] text-[#8a6506] disabled:opacity-40"
                  >
                    <Factory size={13} /> Use raw bags
                  </button>
                  <button
                    type="button"
                    disabled={maxPouchesFromWip <= 0 && wipTeaBags < PT_TEA_BAGS_PER_POUCH}
                    onClick={() => setShowPouch(true)}
                    className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#C8900A] text-[#8a6506] disabled:opacity-40"
                  >
                    <Package size={13} /> Pouch
                  </button>
                  <button
                    type="button"
                    disabled={wipTeaBags <= 0}
                    onClick={() => setShowWipWaste(true)}
                    className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary disabled:opacity-40"
                  >
                    <Minus size={13} /> WIP waste
                  </button>
                </>
              ) : (
                <button
                  type="button"
                  disabled={(data?.bags_on_hand ?? 0) <= 0}
                  onClick={() => setShowProduce(true)}
                  className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#C8900A] text-[#8a6506] disabled:opacity-40"
                >
                  <Factory size={13} /> Produce
                </button>
              )}
              <button
                type="button"
                disabled={(data?.bags_on_hand ?? 0) <= 0}
                onClick={() => setShowWarehouseWaste(true)}
                className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary disabled:opacity-40"
              >
                <Minus size={13} /> Bag waste
              </button>
            </>
          )}
          </div>
        </div>
      }
    >
      {loading ? (
        <div className="py-16 text-center text-muted-foreground">
          <Loader2 className="inline animate-spin" size={20} />
        </div>
      ) : !data ? (
        <p className="text-sm text-red-600">{loadError ?? "Could not load raw inventory."}</p>
      ) : (
        <div className="space-y-5">
          <div className={`grid gap-3 ${isParagis ? "grid-cols-1 sm:grid-cols-2 lg:grid-cols-4" : "grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"}`} data-tour="raw-dept-metrics">
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Raw bags</p>
              <p className="text-2xl font-semibold mt-1">{data.bags_on_hand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{data.unit}s sealed</p>
            </div>
            {isParagis && (
              <div className="rounded-xl border bg-card px-4 py-3">
                <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Tea bags (WIP)</p>
                <p className="text-2xl font-semibold mt-1">{wipTeaBags.toLocaleString()}</p>
                <p className="text-[10px] text-muted-foreground mt-0.5">
                  ≈ {maxPouchesFromWip} pouch{maxPouchesFromWip === 1 ? "" : "es"} packable
                </p>
              </div>
            )}
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Finished {data.inventory_unit}s</p>
              <p className="text-2xl font-semibold mt-1">{data.finished_on_hand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{data.finished_label}</p>
            </div>
            <div className="rounded-xl border bg-card px-4 py-3 col-span-2 lg:col-span-1">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Costing stock</p>
              <p className="text-sm font-semibold mt-1 leading-snug">{data.stock_name ?? "—"}</p>
            </div>
          </div>

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

      {actionsOpen && canMutate && (
        <RawActionsModal
          finishedProductPath={route.finishedProductPath}
          finishedTitle={route.finishedTitle}
          isParagis={isParagis}
          canUseRawBags={(data?.bags_on_hand ?? 0) > 0}
          canPouch={maxPouchesFromWip > 0 || wipTeaBags >= PT_TEA_BAGS_PER_POUCH}
          canWipWaste={wipTeaBags > 0}
          canBagWaste={(data?.bags_on_hand ?? 0) > 0}
          canProduce={(data?.bags_on_hand ?? 0) > 0}
          onClose={() => setActionsOpen(false)}
          onStockIn={() => { setActionsOpen(false); setShowStockIn(true); }}
          onUseRawBags={() => { setActionsOpen(false); setShowUseRawBags(true); }}
          onPouch={() => { setActionsOpen(false); setShowPouch(true); }}
          onWipWaste={() => { setActionsOpen(false); setShowWipWaste(true); }}
          onProduce={() => { setActionsOpen(false); setShowProduce(true); }}
          onBagWaste={() => { setActionsOpen(false); setShowWarehouseWaste(true); }}
        />
      )}

      {isParagis && (
        <ParagisProductionGuideModal
          open={guideOpen}
          onClose={() => setGuideOpen(false)}
          role={user?.role}
          canMutate={canMutate}
          onOpenExactTeaBagCount={
            canMutate && (data?.bags_on_hand ?? 0) > 0
              ? () => setShowMakeTeaBagsAdvanced(true)
              : undefined
          }
          onStartTour={() => {
            setGuideOpen(false);
            startPageTourAfterModalClose(() => runParagisProductionTour());
          }}
        />
      )}
      {isSingleStageProduce && (
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
      {showPouch && data && (
        <ProductionQtyModal
          title="Pouch finished product"
          label="Pouches packed"
          unit="pouches"
          productKey="pt"
          action="pouch"
          maxQty={maxPouchesFromWip}
          hint={`Uses ${PT_TEA_BAGS_PER_POUCH} tea bags per pouch · ${wipTeaBags} WIP tea bags on hand`}
          onClose={() => setShowPouch(false)}
          onSubmit={(qty, note) => deptPtPouch({ qty, note })}
          onDone={() => { setShowPouch(false); void refresh(); }}
        />
      )}
      {showWipWaste && data && (
        <WipWasteModal
          maxQty={wipTeaBags}
          onClose={() => setShowWipWaste(false)}
          onDone={() => { setShowWipWaste(false); void refresh(); }}
        />
      )}
      {showProduce && data && (
        <ProductionQtyModal
          title="Produce finished product"
          label={`${data.inventory_unit}s produced`}
          unit={data.inventory_unit}
          productKey={productKey}
          action={productKey === "brc" ? "produce" : "produce"}
          onClose={() => setShowProduce(false)}
          onSubmit={(qty, note) =>
            productKey === "brc" ? deptBrcProduce({ qty, note }) : deptJarProduce({ qty, note })
          }
          onDone={() => { setShowProduce(false); void refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function RawActionsModal({
  finishedProductPath,
  finishedTitle,
  isParagis,
  canUseRawBags,
  canPouch,
  canWipWaste,
  canBagWaste,
  canProduce,
  onClose,
  onStockIn,
  onUseRawBags,
  onPouch,
  onWipWaste,
  onProduce,
  onBagWaste,
}: {
  finishedProductPath: string;
  finishedTitle: string;
  isParagis: boolean;
  canUseRawBags: boolean;
  canPouch: boolean;
  canWipWaste: boolean;
  canBagWaste: boolean;
  canProduce: boolean;
  onClose: () => void;
  onStockIn: () => void;
  onUseRawBags: () => void;
  onPouch: () => void;
  onWipWaste: () => void;
  onProduce: () => void;
  onBagWaste: () => void;
}) {
  const btnCls = "w-full text-left px-3 py-2.5 rounded-lg border border-border text-sm font-medium hover:bg-secondary disabled:opacity-40";
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="raw-actions-title">
      <AppModalHeader title="Quick actions" titleId="raw-actions-title" onClose={onClose} />
      <AppModalBody className="space-y-2">
        <Link
          to={finishedProductPath}
          onClick={onClose}
          className="block w-full text-left px-3 py-2.5 rounded-lg border border-border text-sm font-medium hover:bg-secondary"
        >
          Open {finishedTitle}
        </Link>
        <button type="button" onClick={onStockIn} className={btnCls}>
          <span className="inline-flex items-center gap-2"><Plus size={14} /> Stock in</span>
        </button>
        {isParagis ? (
          <>
            <button type="button" onClick={onUseRawBags} disabled={!canUseRawBags} className={btnCls}>
              <span className="inline-flex items-center gap-2"><Factory size={14} /> Use raw bags</span>
            </button>
            <button type="button" onClick={onPouch} disabled={!canPouch} className={btnCls}>
              <span className="inline-flex items-center gap-2"><Package size={14} /> Pouch</span>
            </button>
            <button type="button" onClick={onWipWaste} disabled={!canWipWaste} className={btnCls}>
              <span className="inline-flex items-center gap-2"><Minus size={14} /> WIP waste</span>
            </button>
          </>
        ) : (
          <button type="button" onClick={onProduce} disabled={!canProduce} className={btnCls}>
            <span className="inline-flex items-center gap-2"><Factory size={14} /> Produce</span>
          </button>
        )}
        <button type="button" onClick={onBagWaste} disabled={!canBagWaste} className={btnCls}>
          <span className="inline-flex items-center gap-2"><Minus size={14} /> Bag waste</span>
        </button>
      </AppModalBody>
      <AppModalFooter>
        <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Close</button>
      </AppModalFooter>
    </AppModal>
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
          {error && <p className="text-xs text-red-600">{error}</p>}
          <p className="text-xs text-muted-foreground">
            Each 1 kg bag yields ~{teaPerBag.toLocaleString()} tea bags at 2 g each. Enter how many bags you opened — no need to count every tea bag.
          </p>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Bags opened (1 kg each) — max {maxBags.toLocaleString()}
            </label>
            <input
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
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
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
          {error && <p className="text-xs text-red-600">{error}</p>}
          {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              {label}{maxQty != null ? ` — max ${maxQty}` : ""}
            </label>
            <input
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
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
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
          {error && <p className="text-xs text-red-600">{error}</p>}
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier</label>
            {suppliersLoading ? (
              <p className="text-xs text-muted-foreground flex items-center gap-1">
                <Loader2 size={12} className="animate-spin" /> Loading suppliers…
              </p>
            ) : (
              <select value={supplierId} onChange={e => setSupplierId(e.target.value)} required className={inputCls}>
                <option value="">Select supplier…</option>
                {suppliers.map(s => (
                  <option key={s.id} value={s.id}>{s.display_name}</option>
                ))}
              </select>
            )}
            {yieldHint && <p className="text-xs text-muted-foreground mt-1">Price from Supplier Master (₱/{unit}). {yieldHint}</p>}
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Quantity ({unit}s)</label>
            <input type="number" step="0.01" min="0.01" value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Arrival date</label>
            <input type="date" value={arrival} onChange={e => setArrival(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button type="submit" disabled={saving || !supplierId} className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Stock in"}
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
          {error && <p className="text-xs text-red-600">{error}</p>}
          <p className="text-xs text-muted-foreground">Deduct sealed supplier bags (spoilage, return, etc.).</p>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Quantity ({unit}s) — max {maxQty.toLocaleString()}
            </label>
            <input type="number" step="0.01" min="0.01" max={maxQty} value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Reason</label>
            <input type="text" value={reason} onChange={e => setReason(e.target.value)} required maxLength={120} className={inputCls} />
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
          {error && <p className="text-xs text-red-600">{error}</p>}
          <p className="text-xs text-muted-foreground">Remove spoiled or unusable filled tea bags from WIP (raw materials already consumed).</p>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
              Tea bags — max {maxQty.toLocaleString()}
            </label>
            <input type="number" step="1" min="1" max={maxQty} value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Reason</label>
            <input type="text" value={reason} onChange={e => setReason(e.target.value)} required maxLength={120} className={inputCls} />
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
