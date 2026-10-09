import { useCallback, useEffect, useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PackagingOnHandSetModal } from "@/app/components/inventory/PackagingOnHandSetModal";
import { PackagingStockInLotModal } from "@/app/components/inventory/PackagingStockInLotModal";
import { PackagingPriceLotModal } from "@/app/components/inventory/PackagingPriceLotModal";
import { AppErrorBanner, AppStepSwitch } from "@/app/components/layout/appFeedbackMotion";
import {
  fetchPackagingDamageTotals,
  fetchPackagingDetail,
  isGenericInsulationFoamRoll,
  removePackagingCatalogItem,
  setPackagingFoamCacaoCutYields,
  setPackagingPoolYield,
  type FoamCacaoCutYield,
  type MaterialStockRow,
  type PackagingDetailPermissions,
  type PackagingStockEntry,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";
import { Button } from "@/app/components/ui/button";
import { cn } from "@/app/components/ui/utils";

type ActivityTab = "stock_out" | "stock_in" | "damaged";

const ACTIVITY_TABS: { id: ActivityTab; label: string }[] = [
  { id: "stock_out", label: "Stock out" },
  { id: "stock_in", label: "Stock in" },
  { id: "damaged", label: "Damaged history" },
];

function entryMatchesTab(entry: PackagingStockEntry, tab: ActivityTab): boolean {
  const kind = entry.entry_kind;
  const qty = Number(entry.qty ?? 0);
  if (tab === "damaged") return kind === "damage";
  if (tab === "stock_in") return kind === "lot" || qty > 0;
  // Stock out: use / issue / other negatives (legacy produce, etc.) — not damage
  if (kind === "damage") return false;
  if (kind === "use" || kind === "issue") return true;
  return qty < 0;
}

interface PackagingMaterialDetailModalProps {
  stock: MaterialStockRow;
  isExec: boolean;
  isHr: boolean;
  isPm: boolean;
  onClose: () => void;
  onChanged: () => void;
}

const peso = (v: string | number | undefined) => {
  const n = typeof v === "string" ? parseFloat(v) : Number(v ?? 0);
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

function formatEntryLine(entry: PackagingStockEntry, unit: string) {
  if (entry.entry_kind === "lot" && entry.lot_unit != null) {
    return `Stock in · ${peso(entry.lot_total_price)} total · ${entry.qty} ${unit} · ${peso(entry.lot_unit)}/${unit}`;
  }
  if (entry.entry_kind === "count_correction") {
    return `Count correction · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
  }
  if (entry.entry_kind === "use") {
    const note = entry.supplier_batch_no?.replace(/^use:\s*/i, "").trim();
    return `Stock out · ${Math.abs(entry.qty)} ${unit}${note ? ` · ${note}` : ""}`;
  }
  if (entry.entry_kind === "damage") {
    const note = entry.supplier_batch_no?.replace(/^damage:\s*/i, "").trim();
    return `Damaged · ${Math.abs(entry.qty)} ${unit}${note ? ` · ${note}` : ""}`;
  }
  const label = entry.supplier_batch_no || "Adjustment";
  return `${label} · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
}

const defaultPermissions = (
  isExec: boolean,
  isHr: boolean,
  isPm: boolean,
): PackagingDetailPermissions => ({
  can_stock_in: isExec || isPm || isHr,
  can_edit_price: isExec || isHr,
  can_deduct: isExec || isPm,
  can_set_on_hand: isExec,
  can_set_pool_yield: isExec,
  can_set_foam_cacao_cut_yields: false,
  can_view_extended_log: isExec,
  can_remove_catalog: isExec,
});

export function PackagingMaterialDetailModal({
  stock,
  isExec,
  isHr,
  isPm,
  onClose,
  onChanged,
}: PackagingMaterialDetailModalProps) {
  const [detail, setDetail] = useState<MaterialStockRow | null>(stock);
  const [entries, setEntries] = useState<PackagingStockEntry[]>([]);
  const [permissions, setPermissions] = useState<PackagingDetailPermissions>(
    () => defaultPermissions(isExec, isHr, isPm),
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);
  const [showPriceLot, setShowPriceLot] = useState(false);
  const [showSetOnHand, setShowSetOnHand] = useState(false);
  const [yieldQty, setYieldQty] = useState("");
  const [yieldSaving, setYieldSaving] = useState(false);
  const [foamCutYields, setFoamCutYields] = useState<FoamCacaoCutYield[]>([]);
  const [foamCuts9x12, setFoamCuts9x12] = useState("");
  const [foamCuts12_5x12, setFoamCuts12_5x12] = useState("");
  const [foamCutsSaving, setFoamCutsSaving] = useState(false);
  const [activityTab, setActivityTab] = useState<ActivityTab>("stock_out");
  const [damagedTotal, setDamagedTotal] = useState(0);
  const [showRemove, setShowRemove] = useState(false);
  const [removePassword, setRemovePassword] = useState("");
  const [removeBusy, setRemoveBusy] = useState(false);
  const [removeError, setRemoveError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const today = new Date();
      const ymd = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
      const [data, damage] = await Promise.all([
        fetchPackagingDetail(stock.id),
        fetchPackagingDamageTotals({ from: "2020-01-01", to: ymd }).catch(() => null),
      ]);
      setDetail(data.stock);
      setEntries(data.entries);
      if (data.permissions) {
        setPermissions(data.permissions);
      }
      const dmg = damage?.totals.find(t => t.stock_id === stock.id);
      setDamagedTotal(dmg ? Number(dmg.qty) || 0 : 0);
      if (data.stock.is_pool_packaging && data.stock.pool_yield_qty != null) {
        setYieldQty(String(data.stock.pool_yield_qty));
      }
      if (data.foam_cacao_cut_yields) {
        setFoamCutYields(data.foam_cacao_cut_yields);
        const nine = data.foam_cacao_cut_yields.find(c => c.key === "9x12");
        const twelve = data.foam_cacao_cut_yields.find(c => c.key === "12_5x12");
        if (nine?.pool_yield_qty != null) setFoamCuts9x12(String(nine.pool_yield_qty));
        if (twelve?.pool_yield_qty != null) setFoamCuts12_5x12(String(twelve.pool_yield_qty));
      } else {
        setFoamCutYields([]);
        setFoamCuts9x12("");
        setFoamCuts12_5x12("");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load details.");
    } finally {
      setLoading(false);
    }
  }, [stock.id]);

  useEffect(() => { void refresh(); }, [refresh]);

  useEffect(() => {
    setActivityTab("stock_out");
  }, [stock.id]);

  const row = detail ?? stock;
  const onHand = Number(row.on_hand ?? 0);
  const unitCost = Number(row.current_price ?? 0);
  const isPool = Boolean(row.is_pool_packaging);
  const isGenericFoam = isGenericInsulationFoamRoll(row.name);
  const poolRemaining = row.pool_estimated_remaining;
  const foamCut9Label = foamCutYields.find(c => c.key === "9x12")?.label ?? '9" x 12"';
  const foamCut12Label = foamCutYields.find(c => c.key === "12_5x12")?.label ?? '12.5" x 12"';

  const foamPieceCostHint = (cutsRaw: string) => {
    const cuts = parseFloat(cutsRaw);
    if (!Number.isFinite(cuts) || cuts <= 0 || unitCost <= 0) return null;
    return `≈ ${peso(unitCost / cuts)} per cut`;
  };

  const saveYield = async () => {
    const n = parseFloat(yieldQty);
    if (!Number.isFinite(n) || n <= 0) {
      setError("Yield estimate must be greater than zero.");
      return;
    }
    setYieldSaving(true);
    setError(null);
    try {
      await setPackagingPoolYield({
        stock_id: row.id,
        pool_yield_qty: n,
        pool_yield_unit: row.pool_yield_unit ?? undefined,
      });
      await refresh();
      onChanged();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save yield.");
    } finally {
      setYieldSaving(false);
    }
  };

  const saveFoamCuts = async () => {
    const nine = parseFloat(foamCuts9x12);
    const twelve = parseFloat(foamCuts12_5x12);
    if (!Number.isFinite(nine) || nine <= 0 || !Number.isFinite(twelve) || twelve <= 0) {
      setError("Both cut yields must be greater than zero.");
      return;
    }
    setFoamCutsSaving(true);
    setError(null);
    try {
      const res = await setPackagingFoamCacaoCutYields({
        stock_id: row.id,
        cuts_9x12: nine,
        cuts_12_5x12: twelve,
      });
      setFoamCutYields(res.foam_cacao_cut_yields);
      await refresh();
      onChanged();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save cut yields.");
    } finally {
      setFoamCutsSaving(false);
    }
  };

  const handleChildDone = () => {
    setShowStockIn(false);
    setShowPriceLot(false);
    setShowSetOnHand(false);
    void refresh();
    onChanged();
  };

  const closeRemove = () => {
    if (removeBusy) return;
    setShowRemove(false);
    setRemovePassword("");
    setRemoveError(null);
  };

  const submitRemove = async () => {
    if (!removePassword.trim()) {
      setRemoveError("Enter the removal password.");
      return;
    }
    setRemoveBusy(true);
    setRemoveError(null);
    try {
      await removePackagingCatalogItem({
        stock_id: row.id,
        confirm_password: removePassword,
      });
      setShowRemove(false);
      setRemovePassword("");
      onChanged();
      onClose();
    } catch (e) {
      setRemoveError(e instanceof ApiError ? e.message : "Could not remove catalog item.");
    } finally {
      setRemoveBusy(false);
    }
  };

  const tabEntries = useMemo(
    () => entries.filter(e => entryMatchesTab(e, activityTab)),
    [entries, activityTab],
  );

  const tabCounts = useMemo(() => {
    const counts: Record<ActivityTab, number> = { stock_out: 0, stock_in: 0, damaged: 0 };
    for (const e of entries) {
      if (entryMatchesTab(e, "stock_out")) counts.stock_out += 1;
      if (entryMatchesTab(e, "stock_in")) counts.stock_in += 1;
      if (entryMatchesTab(e, "damaged")) counts.damaged += 1;
    }
    return counts;
  }, [entries]);

  return (
    <>
      <AppModal open onClose={onClose} panelClassName="max-w-lg w-full max-h-[90vh]">
        <AppModalHeader
          title={row.name}
          subtitle={`${row.unit} · ${row.dept_name}`}
          onClose={onClose}
        />
        <AppModalBody className="flex flex-col min-h-0 overflow-hidden gap-5">
          {error && <p className="text-sm text-red-600 shrink-0">{error}</p>}

          {isPool && isGenericFoam && (
            <div className="rounded-xl border border-dashed bg-secondary/20 px-3 py-3 shrink-0 space-y-3">
              <div>
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  Jar Cacao cut yields
                </p>
                <p className="text-[11px] text-muted-foreground mt-1 leading-relaxed">
                  Pieces per warehouse roll for each cut size. Parcel costing and cacao ship deduct use these —
                  not the full roll price per jar.
                </p>
              </div>
              {permissions.can_set_foam_cacao_cut_yields ? (
                <div className="space-y-3">
                  <label className="block text-xs">
                    <span className="text-muted-foreground block mb-1">{foamCut9Label} cuts per roll</span>
                    <input
                      type="number"
                      min="1"
                      step="1"
                      value={foamCuts9x12}
                      onChange={e => setFoamCuts9x12(e.target.value)}
                      className="w-full border border-border rounded-lg px-2 py-1.5 text-sm bg-background"
                      placeholder="e.g. 50"
                    />
                    {foamPieceCostHint(foamCuts9x12) && (
                      <span className="text-[10px] text-muted-foreground mt-0.5 block">
                        {foamPieceCostHint(foamCuts9x12)}
                      </span>
                    )}
                  </label>
                  <label className="block text-xs">
                    <span className="text-muted-foreground block mb-1">{foamCut12Label} cuts per roll</span>
                    <input
                      type="number"
                      min="1"
                      step="1"
                      value={foamCuts12_5x12}
                      onChange={e => setFoamCuts12_5x12(e.target.value)}
                      className="w-full border border-border rounded-lg px-2 py-1.5 text-sm bg-background"
                      placeholder="e.g. 40"
                    />
                    {foamPieceCostHint(foamCuts12_5x12) && (
                      <span className="text-[10px] text-muted-foreground mt-0.5 block">
                        {foamPieceCostHint(foamCuts12_5x12)}
                      </span>
                    )}
                  </label>
                  <Button
                    type="button"
                    size="sm"
                    disabled={foamCutsSaving}
                    onClick={() => void saveFoamCuts()}
                    className="w-full sm:w-auto"
                  >
                    {foamCutsSaving ? "Saving…" : "Save cut yields"}
                  </Button>
                </div>
              ) : (
                <ul className="text-sm space-y-1">
                  {foamCutYields.map(cut => (
                    <li key={cut.key}>
                      <span className="text-muted-foreground">{cut.label}:</span>{" "}
                      {cut.pool_yield_qty != null ? (
                        <span className="font-medium">~{cut.pool_yield_qty.toLocaleString()} cuts/roll</span>
                      ) : (
                        <span className="text-amber-700">Not set</span>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          )}

          {isPool && !isGenericFoam && (
            <div className="rounded-xl border border-dashed bg-secondary/20 px-3 py-3 shrink-0 space-y-2">
              <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Pool (cost estimate)</p>
                <span className="text-[10px] text-muted-foreground">Deduct rolls when opened</span>
              </div>
              {row.pool_yield_qty != null && row.pool_yield_unit && (
                <p className="text-sm">
                  ~{Number(row.pool_yield_qty).toLocaleString()} {row.pool_yield_unit.replace(/_/g, " ")}s per {row.unit}
                </p>
              )}
              {poolRemaining != null && (
                <p className="text-xs text-muted-foreground">
                  ~{poolRemaining.toLocaleString()} {row.pool_yield_unit?.replace(/_/g, " ") ?? "units"} remaining (estimate)
                </p>
              )}
              {permissions.can_set_pool_yield && (
                <div className="flex gap-2 items-end">
                  <label className="flex-1 text-xs">
                    <span className="text-muted-foreground block mb-1">Yield per {row.unit}</span>
                    <input
                      type="number"
                      min="1"
                      step="1"
                      value={yieldQty}
                      onChange={e => setYieldQty(e.target.value)}
                      className="w-full border border-border rounded-lg px-2 py-1.5 text-sm bg-background"
                    />
                  </label>
                  <button
                    type="button"
                    disabled={yieldSaving}
                    onClick={() => void saveYield()}
                    className="text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50 disabled:opacity-50"
                  >
                    {yieldSaving ? "Saving…" : "Save yield"}
                  </button>
                </div>
              )}
            </div>
          )}

          <div className="grid grid-cols-2 gap-3 shrink-0">
            <div className="rounded-xl border bg-secondary/30 px-3 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">On hand</p>
              <p className="text-sm font-semibold mt-1 tabular-nums">{onHand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground">{row.unit}</p>
            </div>
            <div className="rounded-xl border bg-secondary/30 px-3 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Damaged</p>
              <p
                className={cn(
                  "text-sm font-semibold mt-1 tabular-nums",
                  damagedTotal > 0 ? "text-destructive" : "text-foreground",
                )}
              >
                {damagedTotal.toLocaleString()}
              </p>
              <p className="text-[10px] text-muted-foreground">{row.unit} · all time</p>
            </div>
          </div>

          <div className="flex flex-wrap gap-2 shrink-0">
            {permissions.can_edit_price && !permissions.can_stock_in && (
              <button
                type="button"
                onClick={() => setShowPriceLot(true)}
                className="text-xs font-semibold text-white px-3 py-2 rounded-lg"
                style={{ background: "#C8900A" }}
              >
                Update price
              </button>
            )}
            {permissions.can_stock_in && (
              <button
                type="button"
                onClick={() => setShowStockIn(true)}
                className="text-xs font-semibold text-white px-3 py-2 rounded-lg"
                style={{ background: "#C8900A" }}
              >
                Stock in lot
              </button>
            )}
            {permissions.can_set_on_hand && (
              <button
                type="button"
                onClick={() => setShowSetOnHand(true)}
                className="text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50"
              >
                Set on-hand
              </button>
            )}
          </div>

          <div className="flex flex-col min-h-0 flex-1">
            <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2 shrink-0">
              History
            </h4>
            <div
              className="flex gap-1 p-1 rounded-xl border border-border bg-secondary/30 shrink-0 mb-2"
              role="tablist"
              aria-label="Movement history"
            >
              {ACTIVITY_TABS.map(tab => {
                const active = activityTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    role="tab"
                    aria-selected={active}
                    onClick={() => setActivityTab(tab.id)}
                    className={cn(
                      "flex-1 min-h-8 rounded-lg px-2 text-[11px] font-semibold transition-colors",
                      "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40",
                      active
                        ? tab.id === "damaged"
                          ? "bg-destructive/15 text-destructive shadow-sm"
                          : "bg-card text-foreground shadow-sm"
                        : "text-muted-foreground hover:text-foreground hover:bg-card/60",
                    )}
                  >
                    {tab.label}
                    <span className="ml-1 tabular-nums opacity-70">({tabCounts[tab.id]})</span>
                  </button>
                );
              })}
            </div>
            {loading ? (
              <div className="py-6 text-center text-muted-foreground shrink-0">
                <Loader2 className="inline animate-spin" size={14} />
              </div>
            ) : (
              <AppStepSwitch stepKey={activityTab} className="min-h-0 flex-1 flex flex-col">
                {tabEntries.length === 0 ? (
                  <p className="text-sm text-muted-foreground py-4 text-center shrink-0">
                    {activityTab === "damaged"
                      ? "No damaged reports yet."
                      : activityTab === "stock_in"
                        ? "No stock-in lots yet."
                        : "No stock-out entries yet."}
                  </p>
                ) : (
                  <div
                    className={`min-h-0 overflow-y-auto overscroll-contain rounded-xl border border-border ${
                      permissions.can_view_extended_log ? "max-h-64" : "max-h-44"
                    }`}
                    aria-label={`${ACTIVITY_TABS.find(t => t.id === activityTab)?.label ?? "History"} list`}
                  >
                    <ul className="divide-y divide-border">
                      {tabEntries.map(entry => (
                        <li key={entry.id} className="px-3 py-2.5 text-xs">
                          <p className="font-medium text-foreground">{formatEntryLine(entry, row.unit)}</p>
                          <p className="text-muted-foreground mt-0.5">
                            {new Date(entry.created_at.replace(" ", "T")).toLocaleString()}
                            {entry.entered_by_name ? ` · ${entry.entered_by_name}` : ""}
                          </p>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </AppStepSwitch>
            )}
          </div>

          {permissions.can_remove_catalog && (
            <div className="shrink-0 pt-2 border-t border-border/50">
              <button
                type="button"
                onClick={() => {
                  setRemoveError(null);
                  setRemovePassword("");
                  setShowRemove(true);
                }}
                className="text-[11px] text-muted-foreground/60 hover:text-destructive transition-colors underline-offset-2 hover:underline"
              >
                Remove from catalog…
              </button>
            </div>
          )}
        </AppModalBody>
      </AppModal>

      {showStockIn && (
        <PackagingStockInLotModal stock={row} onClose={() => setShowStockIn(false)} onDone={handleChildDone} />
      )}
      {showPriceLot && (
        <PackagingPriceLotModal stock={row} onClose={() => setShowPriceLot(false)} onDone={handleChildDone} />
      )}
      {showSetOnHand && (
        <PackagingOnHandSetModal stock={row} onClose={() => setShowSetOnHand(false)} onDone={handleChildDone} />
      )}

      {showRemove && (
        <AppModal open onClose={closeRemove} panelClassName="max-w-sm w-full">
          <AppModalHeader
            title="Remove from catalog"
            subtitle={`${row.name} will be hidden from Supplies. History is kept.`}
            onClose={closeRemove}
          />
          <AppModalBody className="space-y-3">
            {removeError && <AppErrorBanner message={removeError} />}
            <p className="text-xs text-muted-foreground leading-relaxed">
              Enter Noli_F&apos;s account password to confirm. This does not erase stock history.
            </p>
            <div>
              <label htmlFor="packaging-remove-pw" className="text-xs text-muted-foreground">
                Password
              </label>
              <input
                id="packaging-remove-pw"
                type="password"
                autoComplete="current-password"
                value={removePassword}
                onChange={e => setRemovePassword(e.target.value)}
                onKeyDown={e => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    void submitRemove();
                  }
                }}
                disabled={removeBusy}
                autoFocus
                className="mt-1 w-full rounded-lg border-2 border-border bg-card px-3 py-2.5 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
              />
            </div>
          </AppModalBody>
          <AppModalFooter>
            <Button type="button" variant="outline" onClick={closeRemove} disabled={removeBusy}>
              Cancel
            </Button>
            <Button
              type="button"
              variant="destructive"
              onClick={() => void submitRemove()}
              disabled={removeBusy || !removePassword.trim()}
            >
              {removeBusy ? <Loader2 className="size-4 animate-spin" /> : null}
              Remove
            </Button>
          </AppModalFooter>
        </AppModal>
      )}
    </>
  );
}
