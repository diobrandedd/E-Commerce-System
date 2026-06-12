import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PackagingDeductModal } from "@/app/components/inventory/PackagingDeductModal";
import { PackagingOnHandSetModal } from "@/app/components/inventory/PackagingOnHandSetModal";
import { PackagingStockInLotModal } from "@/app/components/inventory/PackagingStockInLotModal";
import {
  fetchPackagingDetail,
  setPackagingPoolYield,
  type MaterialStockRow,
  type PackagingDetailPermissions,
  type PackagingStockEntry,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

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
    return `Deduct for use · ${Math.abs(entry.qty)} ${unit}${note ? ` · ${note}` : ""}`;
  }
  const label = entry.supplier_batch_no || "Adjustment";
  return `${label} · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
}

const defaultPermissions = (
  isExec: boolean,
  isHr: boolean,
  isPm: boolean,
): PackagingDetailPermissions => ({
  can_stock_in: isExec || isHr,
  can_deduct: isExec || isPm,
  can_set_on_hand: isExec,
  can_set_pool_yield: isExec,
  can_view_extended_log: isExec,
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
  const [showDeduct, setShowDeduct] = useState(false);
  const [showSetOnHand, setShowSetOnHand] = useState(false);
  const [yieldQty, setYieldQty] = useState("");
  const [yieldSaving, setYieldSaving] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchPackagingDetail(stock.id);
      setDetail(data.stock);
      setEntries(data.entries);
      if (data.permissions) {
        setPermissions(data.permissions);
      }
      if (data.stock.is_pool_packaging && data.stock.pool_yield_qty != null) {
        setYieldQty(String(data.stock.pool_yield_qty));
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load details.");
    } finally {
      setLoading(false);
    }
  }, [stock.id]);

  useEffect(() => { void refresh(); }, [refresh]);

  const row = detail ?? stock;
  const onHand = Number(row.on_hand ?? 0);
  const unitCost = Number(row.current_price ?? 0);
  const poolValue = onHand * unitCost;
  const isPool = Boolean(row.is_pool_packaging);
  const poolRemaining = row.pool_estimated_remaining;

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

  const handleChildDone = () => {
    setShowStockIn(false);
    setShowDeduct(false);
    setShowSetOnHand(false);
    void refresh();
    onChanged();
  };

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

          {isPool && (
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

          <div className="grid grid-cols-3 gap-3 shrink-0">
            <div className="rounded-xl border bg-secondary/30 px-3 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Unit cost</p>
              <p className="text-sm font-semibold mt-1">{peso(row.current_price)}</p>
              <p className="text-[10px] text-muted-foreground">per {row.unit}</p>
            </div>
            <div className="rounded-xl border bg-secondary/30 px-3 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">On hand</p>
              <p className="text-sm font-semibold mt-1">{onHand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground">{row.unit}</p>
            </div>
            <div className="rounded-xl border bg-secondary/30 px-3 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Pool value</p>
              <p className="text-sm font-semibold mt-1">{peso(poolValue)}</p>
              <p className="text-[10px] text-muted-foreground">at unit cost</p>
            </div>
          </div>

          <div className="flex flex-wrap gap-2 shrink-0">
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
            {permissions.can_deduct && (
              <button
                type="button"
                onClick={() => setShowDeduct(true)}
                className="text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50"
              >
                Deduct for use
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
              {permissions.can_view_extended_log ? "Activity log" : "Recent activity"}
            </h4>
            {loading ? (
              <div className="py-6 text-center text-muted-foreground shrink-0">
                <Loader2 className="inline animate-spin" size={14} />
              </div>
            ) : entries.length === 0 ? (
              <p className="text-sm text-muted-foreground py-4 text-center shrink-0">No stock movements yet.</p>
            ) : (
              <div
                className={`min-h-0 overflow-y-auto overscroll-contain rounded-xl border border-border ${
                  permissions.can_view_extended_log ? "max-h-64" : "max-h-44"
                }`}
                aria-label="Activity log"
              >
                <ul className="divide-y divide-border">
                  {entries.map(entry => (
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
          </div>
        </AppModalBody>
      </AppModal>

      {showStockIn && (
        <PackagingStockInLotModal stock={row} onClose={() => setShowStockIn(false)} onDone={handleChildDone} />
      )}
      {showDeduct && (
        <PackagingDeductModal stock={row} onClose={() => setShowDeduct(false)} onDone={handleChildDone} />
      )}
      {showSetOnHand && (
        <PackagingOnHandSetModal stock={row} onClose={() => setShowSetOnHand(false)} onDone={handleChildDone} />
      )}
    </>
  );
}
