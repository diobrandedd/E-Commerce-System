import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { Link } from "react-router";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PackagingOnHandRequestModal } from "@/app/components/inventory/PackagingOnHandRequestModal";
import { PackagingOnHandSetModal } from "@/app/components/inventory/PackagingOnHandSetModal";
import { PackagingStockInLotModal } from "@/app/components/inventory/PackagingStockInLotModal";
import {
  fetchPackagingDetail,
  type MaterialStockRow,
  type PackagingStockEntry,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingMaterialDetailModalProps {
  stock: MaterialStockRow;
  isExec: boolean;
  isHr: boolean;
  canStockIn: boolean;
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
    return `Stock in · ${peso(entry.lot_total_price)} total · ${entry.qty} ${unit} · ${peso(entry.lot_unit)}/pc`;
  }
  if (entry.entry_kind === "count_correction") {
    return `Count correction · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
  }
  const label = entry.supplier_batch_no || "Adjustment";
  return `${label} · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
}

export function PackagingMaterialDetailModal({
  stock,
  isExec,
  isHr,
  canStockIn,
  onClose,
  onChanged,
}: PackagingMaterialDetailModalProps) {
  const [detail, setDetail] = useState<MaterialStockRow | null>(stock);
  const [entries, setEntries] = useState<PackagingStockEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);
  const [showSetOnHand, setShowSetOnHand] = useState(false);
  const [showRequest, setShowRequest] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchPackagingDetail(stock.id);
      setDetail(data.stock);
      setEntries(data.entries);
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

  const handleChildDone = () => {
    setShowStockIn(false);
    setShowSetOnHand(false);
    setShowRequest(false);
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
            {canStockIn && (
              <button
                type="button"
                onClick={() => setShowStockIn(true)}
                className="text-xs font-semibold text-white px-3 py-2 rounded-lg"
                style={{ background: "#C8900A" }}
              >
                Stock in lot
              </button>
            )}
            {isExec && (
              <button
                type="button"
                onClick={() => setShowSetOnHand(true)}
                className="text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50"
              >
                Set on-hand
              </button>
            )}
            {isHr && (
              <button
                type="button"
                onClick={() => setShowRequest(true)}
                className="text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50"
              >
                Request count fix
              </button>
            )}
            {isHr && (
              <Link
                to="/inventory/requests#material-adjustments-section"
                onClick={onClose}
                className="text-xs font-medium text-[#C8900A] px-3 py-2 hover:underline"
              >
                View my requests →
              </Link>
            )}
          </div>

          <div className="flex flex-col min-h-0 flex-1">
            <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2 shrink-0">
              Recent activity
            </h4>
            {loading ? (
              <div className="py-6 text-center text-muted-foreground shrink-0">
                <Loader2 className="inline animate-spin" size={14} />
              </div>
            ) : entries.length === 0 ? (
              <p className="text-sm text-muted-foreground py-4 text-center shrink-0">No stock movements yet.</p>
            ) : (
              <div
                className="min-h-0 max-h-44 overflow-y-auto overscroll-contain rounded-xl border border-border"
                aria-label="Recent activity list"
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
      {showSetOnHand && (
        <PackagingOnHandSetModal stock={row} onClose={() => setShowSetOnHand(false)} onDone={handleChildDone} />
      )}
      {showRequest && (
        <PackagingOnHandRequestModal stock={row} onClose={() => setShowRequest(false)} onDone={handleChildDone} />
      )}
    </>
  );
}
