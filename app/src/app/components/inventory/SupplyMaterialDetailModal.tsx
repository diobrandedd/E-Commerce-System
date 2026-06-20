import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { Link } from "react-router";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { SupplyIssueRequestModal } from "@/app/components/inventory/SupplyIssueRequestModal";
import { SupplyOnHandSetModal } from "@/app/components/inventory/SupplyOnHandSetModal";
import { SupplyStockInLotModal } from "@/app/components/inventory/SupplyStockInLotModal";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { useAuth } from "@/app/context/AuthContext";
import {
  fetchSupplyDetail,
  type MaterialStockRow,
  type SupplyDetailPermissions,
  type SupplyStockEntry,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface SupplyMaterialDetailModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onChanged: () => void;
}

const peso = (v: string | number | undefined) => {
  const n = typeof v === "string" ? parseFloat(v) : Number(v ?? 0);
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

function formatEntryLine(entry: SupplyStockEntry, unit: string) {
  if (entry.entry_kind === "lot" && entry.lot_unit != null) {
    return `Stock in · ${peso(entry.lot_total_price)} total · ${entry.qty} ${unit} · ${peso(entry.lot_unit)}/pc`;
  }
  if (entry.entry_kind === "count_correction") {
    return `Count correction · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
  }
  if (entry.entry_kind === "issue") {
    const label = entry.supplier_batch_no || "Issue";
    return `${label} · ${entry.qty} ${unit}`;
  }
  const label = entry.supplier_batch_no || "Adjustment";
  return `${label} · ${entry.qty >= 0 ? "+" : ""}${entry.qty} ${unit}`;
}

const defaultPermissions = (role: string | undefined): SupplyDetailPermissions => ({
  can_stock_in: role === "executive" || role === "hr",
  can_issue_request: role === "executive" || role === "hr",
  can_set_on_hand: role === "executive",
  can_create_catalog: role === "executive" || role === "hr",
  can_view_extended_log: role === "executive",
});

export function SupplyMaterialDetailModal({ stock, onClose, onChanged }: SupplyMaterialDetailModalProps) {
  const { user } = useAuth();
  const { refresh: refreshNotifications } = useCorrectionNotifications();
  const [detail, setDetail] = useState<MaterialStockRow | null>(stock);
  const [entries, setEntries] = useState<SupplyStockEntry[]>([]);
  const [permissions, setPermissions] = useState<SupplyDetailPermissions>(() => defaultPermissions(user?.role));
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);
  const [showSetOnHand, setShowSetOnHand] = useState(false);
  const [showIssueRequest, setShowIssueRequest] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchSupplyDetail(stock.id);
      setDetail(data.stock);
      setEntries(data.entries);
      if (data.permissions) setPermissions(data.permissions);
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
    setShowIssueRequest(false);
    void refresh();
    onChanged();
  };

  const handleIssueDone = () => {
    handleChildDone();
    void refreshNotifications();
  };

  return (
    <>
      <AppModal open onClose={onClose} panelClassName="max-w-lg w-full max-h-[90vh]">
        <AppModalHeader
          title={row.name}
          subtitle={`${row.unit} · org-wide pool`}
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
            {permissions.can_issue_request && (
              <button
                type="button"
                onClick={() => setShowIssueRequest(true)}
                className="text-xs font-semibold text-white px-3 py-2 rounded-lg"
                style={{ background: "#C8900A" }}
              >
                Issue request
              </button>
            )}
            {permissions.can_issue_request && (
              <Link
                to="/inventory/requests#supply-requests-section"
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
        <SupplyStockInLotModal stock={row} onClose={() => setShowStockIn(false)} onDone={handleChildDone} />
      )}
      {showSetOnHand && (
        <SupplyOnHandSetModal stock={row} onClose={() => setShowSetOnHand(false)} onDone={handleChildDone} />
      )}
      {showIssueRequest && (
        <SupplyIssueRequestModal stock={row} onClose={() => setShowIssueRequest(false)} onDone={handleIssueDone} />
      )}
    </>
  );
}
