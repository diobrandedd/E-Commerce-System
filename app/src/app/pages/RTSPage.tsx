import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { RefreshCcw, AlertCircle, Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { FulfillmentToast } from "@/app/components/csr/onlineFulfillmentMotion";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES } from "@/app/lib/honey";
import { variantLabel } from "@/app/lib/posHoney";

interface RtsInvRow {
  id?: number;
  first_row_id?: number;
  line_count?: number;
  category: string;
  product_label: string;
  variant: string | null;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
  current_qty: string;
  initial_qty: string;
}

export function RTSPage() {
  const { user } = useAuth();
  const canRecord = user?.role === "csr_staff" || user?.role === "csr_tl" || user?.role === "executive" || user?.role === "prod_manager";
  const canMutate = user?.role === "prod_manager";
  const canViewOps = user?.role === "executive" || user?.role === "prod_manager" || user?.role === "hr";
  const readonly = user?.role === "hr" || user?.role === "csr_tl" || user?.role === "csr_staff";

  const [honey, setHoney] = useState<RtsInvRow[]>([]);
  const [other, setOther] = useState<RtsInvRow[]>([]);
  const [sync, setSync] = useState<{ last_synced_at: string | null; last_error: string | null } | null>(null);
  const [diagnostic, setDiagnostic] = useState<{
    case_count: number;
    line_item_count: number;
    syncable_line_item_count: number;
    inventory_row_count: number;
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [sellRow, setSellRow] = useState<RtsInvRow | null>(null);

  const canSync = user?.role === "executive" || user?.role === "prod_manager" || user?.role === "hr"
    || user?.role === "csr_tl" || user?.role === "csr_staff";

  const loadInventory = useCallback(async (showLoader = true) => {
    if (showLoader) setLoading(true);
    setError(null);
    try {
      const inv = await apiGet<{
        honey: RtsInvRow[];
        other: RtsInvRow[];
        sync: typeof sync;
        diagnostic: typeof diagnostic;
        readonly: boolean;
        can_mutate: boolean;
      }>("rts.php", "inventory_list");
      setHoney(inv.honey);
      setOther(inv.other);
      setSync(inv.sync);
      setDiagnostic(inv.diagnostic ?? null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS inventory.");
    } finally {
      if (showLoader) setLoading(false);
    }
  }, []);

  const syncNow = useCallback(async () => {
    if (!canSync) {
      setError("Your role cannot run RTS sync.");
      return;
    }
    setSyncing(true);
    setError(null);
    try {
      const result = await apiPost<{ synced: number; last_synced_at: string | null; last_error: string | null }>(
        "rts_sync.php",
        "run"
      );
      await loadInventory(false);
      if (result.synced > 0) {
        setToast(`Synced ${result.synced} product line(s) to inventory`);
      } else {
        setToast("Sync finished — no new product lines to add (cases need product lines first)");
      }
      window.setTimeout(() => setToast(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "RTS sync failed.");
    } finally {
      setSyncing(false);
    }
  }, [canSync, loadInventory]);

  useEffect(() => {
    void loadInventory();
    const t = setInterval(() => {
      if (!canSync) return;
      void apiPost("rts_sync.php", "run").catch(() => null).then(() => loadInventory(false));
    }, 60000);
    return () => clearInterval(t);
  }, [loadInventory, canSync]);

  return (
    <PageLayout
      title="RTS inventory"
      subtitle="Synced from operational RTS records"
      actions={
        <div className="flex gap-2 flex-wrap">
          <button
            type="button"
            onClick={() => void syncNow()}
            disabled={syncing || !canSync}
            className="text-xs border rounded-lg px-3 py-1.5 text-muted-foreground hover:text-foreground disabled:opacity-50"
          >
            <RefreshCcw size={13} className={`inline mr-1 ${syncing ? "animate-spin" : ""}`} />
            {syncing ? "Syncing…" : "Sync now"}
          </button>
          {canRecord && (
            <Link to="/rts/record" className="text-xs font-semibold text-white px-3 py-1.5 rounded-lg inline-flex items-center gap-1" style={{ background: "#C8900A" }}>
              Record RTS
            </Link>
          )}
        </div>
      }
    >
      <FulfillmentToast message={toast} />
      <div className="space-y-6">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex gap-2">
            <AlertCircle size={16} /> {error}
          </div>
        )}
        {diagnostic && diagnostic.case_count > 0 && diagnostic.line_item_count === 0 && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 space-y-2">
            <p>
              <strong>Sync ran, but inventory stays empty.</strong> You have {diagnostic.case_count} RTS case(s), but none have product lines yet.
              Inventory is built from <code className="text-xs">rts_line_items</code> (honey, pouches, etc. per case), not from tracking numbers alone.
            </p>
            <p className="flex flex-wrap gap-2">
              <Link to="/rts/record" className="inline-block text-xs font-semibold text-white px-3 py-1.5 rounded-lg" style={{ background: "#C8900A" }}>
                Record RTS — add product lines
              </Link>
              <Link to="/rts/records" className="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg border border-amber-400 text-[#8a5c00]">
                Open RTS Records
              </Link>
            </p>
          </div>
        )}
        {sync?.last_error && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Sync warning: {sync.last_error}
          </div>
        )}
        <p className="text-xs text-muted-foreground">
          Last synced: {sync?.last_synced_at ? new Date(sync.last_synced_at.replace(" ", "T")).toLocaleString() : "Never"}
          {readonly && " · Read-only for your role"}
        </p>

        {loading ? (
          <div className="text-sm text-muted-foreground py-8 text-center"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</div>
        ) : (
          <>
            <section>
              <h3 className="text-sm font-semibold mb-3">Honey RTS</h3>
              <RtsTable rows={honey} honey onSell={canMutate ? setSellRow : undefined} />
            </section>
            <section>
              <h3 className="text-sm font-semibold mb-3">Other products</h3>
              <RtsTable rows={other} honey={false} onSell={canMutate ? setSellRow : undefined} />
            </section>
          </>
        )}
        {canViewOps && (
          <p className="text-xs text-muted-foreground">
            <Link to="/rts/records" className="text-[#C8900A] underline">View all RTS cases</Link>
            {" · "}
            <Link to="/rts/dashboard" className="text-[#C8900A] underline">RTS dashboard</Link>
          </p>
        )}
      </div>
      {sellRow && <SellModal row={sellRow} onClose={() => setSellRow(null)} onDone={() => { setSellRow(null); void loadInventory(); }} />}
    </PageLayout>
  );
}

function RtsTable({ rows, honey, onSell }: { rows: RtsInvRow[]; honey: boolean; onSell?: (r: RtsInvRow) => void }) {
  if (!rows.length) return <p className="text-sm text-muted-foreground">No rows yet. Run sync after new RTS records are saved.</p>;
  if (honey) {
    return (
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => <th key={s} className="px-3 py-2 text-left">{s}</th>)}
              {onSell && <th className="px-3 py-2" />}
            </tr>
          </thead>
          <tbody>
            {rows.map(r => (
              <tr key={r.variant ?? r.product_label} className="border-b">
                <td className="px-3 py-2 capitalize">
                  {r.variant ?? r.product_label}
                  {(r.line_count ?? 0) > 1 && (
                    <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                  )}
                </td>
                <td className="px-3 py-2"><QtyCounter current={r.current_liter} initial={r.initial_liter} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_250ml} initial={r.initial_250ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_350ml} initial={r.initial_350ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_gallon} initial={r.initial_gallon} /></td>
                {onSell && (
                  <td className="px-3 py-2">
                    <button onClick={() => onSell(r)} className="text-xs font-semibold text-[#C8900A]">Deduct / Sell</button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }
  return (
    <div className="bg-card rounded-xl border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/40"><th className="px-4 py-2 text-left">Product</th><th className="px-4 py-2 text-left">Qty</th>{onSell && <th />}</tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.product_label} className="border-b">
              <td className="px-4 py-3">
                {r.product_label}
                {(r.line_count ?? 0) > 1 && (
                  <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                )}
              </td>
              <td className="px-4 py-3"><QtyCounter current={r.current_qty} initial={r.initial_qty} /></td>
              {onSell && <td><button onClick={() => onSell(r)} className="text-xs text-[#C8900A] font-semibold">Deduct / Sell</button></td>}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SellModal({ row, onClose, onDone }: { row: RtsInvRow; onClose: () => void; onDone: () => void }) {
  const [saleType, setSaleType] = useState<"walk_in" | "parcel">("walk_in");
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [qtyOther, setQtyOther] = useState("");
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    const size_qtys: Record<string, number> = {};
    if (row.category === "honey") {
      HONEY_SIZES.forEach(s => { const v = parseFloat(sizes[s] || "0"); if (v > 0) size_qtys[s] = v; });
      await apiPost("rts.php", "inventory_deduct_sell", {
        honey_variant: row.variant ?? "other",
        size_qtys,
        sale_type: saleType,
      });
    } else {
      await apiPost("rts.php", "inventory_deduct_sell", {
        product_label: row.product_label,
        qty_other: parseFloat(qtyOther),
        sale_type: saleType,
      });
    }
    onDone();
  };
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="rts-sell-modal-title">
      <AppModalHeader title={`Deduct / Sell — ${row.variant ? variantLabel(row.variant) : row.product_label}`} titleId="rts-sell-modal-title" onClose={onClose} />
      <AppModalBody>
        <form onSubmit={submit} className="space-y-2 text-sm">
          {row.category === "honey" ? HONEY_SIZES.map(s => (
            <input key={s} type="number" step="0.01" placeholder={s} onChange={e => setSizes({ ...sizes, [s]: e.target.value })} className="w-full border rounded-lg px-3 py-2" />
          )) : (
            <input type="number" step="0.01" value={qtyOther} onChange={e => setQtyOther(e.target.value)} className="w-full border rounded-lg px-3 py-2" required />
          )}
          <div className="flex gap-4 pt-2">
            <label><input type="radio" checked={saleType === "walk_in"} onChange={() => setSaleType("walk_in")} /> Walk-in</label>
            <label><input type="radio" checked={saleType === "parcel"} onChange={() => setSaleType("parcel")} /> Parcel</label>
          </div>
          <button type="submit" className="w-full py-2.5 rounded-lg font-bold text-white mt-2" style={{ background: "#C8900A" }}>Confirm</button>
        </form>
      </AppModalBody>
    </AppModal>
  );
}
