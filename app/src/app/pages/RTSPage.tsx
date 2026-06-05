import { useCallback, useEffect, useState } from "react";
import { ExternalLink, RefreshCcw, AlertCircle, Loader2, RotateCcw } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES } from "@/app/lib/honey";

interface RtsInvRow {
  id: number;
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

interface LegacyUrls { records: string; create: string; dashboard: string }

export function RTSPage() {
  const { user } = useAuth();
  const canRecord = user?.role === "csr_staff" || user?.role === "csr_tl";
  const canMutate = user?.role === "prod_manager";
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
  const [legacy, setLegacy] = useState<LegacyUrls | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [sellRow, setSellRow] = useState<RtsInvRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      await apiPost("rts_sync.php", "run").catch(() => null);
      const [inv, urls] = await Promise.all([
        apiGet<{
          honey: RtsInvRow[];
          other: RtsInvRow[];
          sync: typeof sync;
          diagnostic: typeof diagnostic;
          readonly: boolean;
          can_mutate: boolean;
        }>("rts.php", "inventory_list"),
        apiGet<LegacyUrls>("rts.php", "legacy_urls"),
      ]);
      setHoney(inv.honey);
      setOther(inv.other);
      setSync(inv.sync);
      setDiagnostic(inv.diagnostic ?? null);
      setLegacy(urls);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS inventory.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
    const t = setInterval(() => apiPost("rts_sync.php", "run").catch(() => null).then(() => refresh()), 60000);
    return () => clearInterval(t);
  }, [refresh]);

  return (
    <PageLayout
      title="RTS inventory"
      subtitle="Synced from operational RTS records — create cases in legacy RTS"
      actions={
        <div className="flex gap-2">
          <button onClick={refresh} className="text-xs border rounded-lg px-3 py-1.5 text-muted-foreground hover:text-foreground">
            <RefreshCcw size={13} className="inline mr-1" /> Sync now
          </button>
          {legacy && canRecord && (
            <a href={legacy.create} className="text-xs font-semibold text-white px-3 py-1.5 rounded-lg inline-flex items-center gap-1" style={{ background: "#C8900A" }}>
              <ExternalLink size={13} /> Record RTS (legacy)
            </a>
          )}
        </div>
      }
    >
      <div className="space-y-6">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex gap-2">
            <AlertCircle size={16} /> {error}
          </div>
        )}
        {diagnostic && diagnostic.case_count > 0 && diagnostic.line_item_count === 0 && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>No product lines to sync.</strong> You have {diagnostic.case_count} RTS case(s) in{" "}
            <code className="text-xs">rts_cases</code>, but <code className="text-xs">rts_line_items</code> is empty.
            RTS inventory is built from line items (products per case), not from cases alone. Open each case in{" "}
            {legacy ? (
              <a href={legacy.records} className="underline font-semibold text-[#8a5c00]">legacy RTS records</a>
            ) : (
              "legacy RTS records"
            )}{" "}
            and save the product lines, then click <strong>Sync now</strong>.
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
        {legacy && (
          <p className="text-xs text-muted-foreground">
            <a href={legacy.records} className="text-[#C8900A] underline">View all RTS cases</a>
            {" · "}
            <a href={legacy.dashboard} className="text-[#C8900A] underline">RTS dashboard</a>
          </p>
        )}
      </div>
      {sellRow && <SellModal row={sellRow} onClose={() => setSellRow(null)} onDone={() => { setSellRow(null); refresh(); }} />}
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
              <tr key={r.id} className="border-b">
                <td className="px-3 py-2 capitalize">{r.variant ?? r.product_label}</td>
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
            <tr key={r.id} className="border-b">
              <td className="px-4 py-3">{r.product_label}</td>
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
    }
    await apiPost("rts.php", "inventory_deduct_sell", {
      row_id: row.id,
      size_qtys,
      qty_other: row.category === "other" ? parseFloat(qtyOther) : undefined,
      sale_type: saleType,
    });
    onDone();
  };
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="rts-sell-modal-title">
      <AppModalHeader title={`Deduct / Sell — ${row.product_label}`} titleId="rts-sell-modal-title" onClose={onClose} />
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
