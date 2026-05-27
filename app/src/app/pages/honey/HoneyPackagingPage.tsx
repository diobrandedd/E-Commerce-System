import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { BatchTraceabilityDrawer } from "@/app/components/honey/BatchTraceabilityDrawer";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost } from "@/app/lib/api";
import { HONEY_SIZES } from "@/app/lib/honey";
import { canExecutiveDirectEdit, canMutateHoneyInventory, canViewSupplierProfile } from "@/app/lib/honeyPermissions";

interface PkRow {
  id: number;
  supplier_code: string;
  batch_no: string;
  bottling_date: string;
  variant: string;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
}

export function HoneyPackagingPage() {
  const { user } = useAuth();
  const canMutate = canMutateHoneyInventory(user?.role);
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const showSupplierProfile = canViewSupplierProfile(user?.role);
  const [rows, setRows] = useState<PkRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [traceBatch, setTraceBatch] = useState<string | null>(null);
  const [sellRow, setSellRow] = useState<PkRow | null>(null);
  const [editRow, setEditRow] = useState<PkRow | null>(null);
  const [traceQ, setTraceQ] = useState("");

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: PkRow[] }>("honey.php", "packaging_list");
      setRows(data.rows);
      setPage(1);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);

  return (
    <PageLayout
      title="Honey — Packaging"
      subtitle="Per-size counters and sales"
      actions={
        <input value={traceQ} onChange={e => setTraceQ(e.target.value)} placeholder="Trace batch / supplier…" className="text-xs border rounded-lg px-2 py-1.5" />
      }
    >
      {traceQ && (
        <button onClick={() => setTraceBatch(traceQ)} className="text-xs text-blue-600 underline mb-3">Search traceability</button>
      )}
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Supplier</th>
              <th className="px-3 py-2 text-left">Batch</th>
              <th className="px-3 py-2 text-left">Date</th>
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => <th key={s} className="px-3 py-2 text-left">{s}</th>)}
              {(canMutate || canExecEdit) && <th className="px-3 py-2">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td colSpan={(canMutate || canExecEdit) ? 9 : 8} className="py-8 text-center"><Loader2 className="inline animate-spin" size={14} /></td></tr>}
            {pagedRows.map(r => (
              <tr key={r.id} className="border-b">
                <td className="px-3 py-2">
                  {showSupplierProfile ? (
                    <SupplierCodeButton code={r.supplier_code} className="text-xs" />
                  ) : (
                    <span className="font-mono text-xs">{r.supplier_code}</span>
                  )}
                </td>
                <td className="px-3 py-2">
                  <button type="button" onClick={() => setTraceBatch(r.batch_no)} className="text-blue-600 underline font-medium">{r.batch_no}</button>
                </td>
                <td className="px-3 py-2">{r.bottling_date}</td>
                <td className="px-3 py-2 capitalize">{r.variant}</td>
                <td className="px-3 py-2"><QtyCounter current={r.current_liter} initial={r.initial_liter} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_250ml} initial={r.initial_250ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_350ml} initial={r.initial_350ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_gallon} initial={r.initial_gallon} /></td>
                {(canMutate || canExecEdit) && (
                  <td className="px-3 py-2 space-x-2 whitespace-nowrap">
                    {canExecEdit && (
                      <button type="button" onClick={() => setEditRow(r)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                        Edit
                      </button>
                    )}
                    {canMutate && (
                      <button onClick={() => setSellRow(r)} className="text-xs font-semibold text-[#C8900A]">
                        Sell
                      </button>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {!loading && totalRows > 0 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 mt-3">
          <p className="text-xs text-muted-foreground">
            Showing {start + 1}-{Math.min(start + perPage, totalRows)} of {totalRows} rows
          </p>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={safePage <= 1}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <span className="text-xs text-muted-foreground">Page {safePage} of {totalPages}</span>
            <button
              type="button"
              onClick={() => setPage(p => Math.min(totalPages, p + 1))}
              disabled={safePage >= totalPages}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      )}
      {traceBatch && <BatchTraceabilityDrawer query={traceBatch} onClose={() => setTraceBatch(null)} />}
      {sellRow && <SellModal row={sellRow} onClose={() => setSellRow(null)} onDone={() => { setSellRow(null); refresh(); }} />}
      {editRow && (
        <ExecutiveRowEditModal
          stage="packaging"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function SellModal({ row, onClose, onDone }: { row: PkRow; onClose: () => void; onDone: () => void }) {
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [saleType, setSaleType] = useState<"walk_in" | "parcel">("walk_in");
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    const size_qtys: Record<string, number> = {};
    HONEY_SIZES.forEach(s => { const v = parseFloat(sizes[s] || "0"); if (v > 0) size_qtys[s] = v; });
    await apiPost("honey.php", "packaging_stock_out", { packaging_row_id: row.id, size_qtys, sale_type: saleType });
    onDone();
  };
  return (
    <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4" onClick={onClose}>
      <div className="bg-card rounded-xl border p-6 max-w-sm w-full" onClick={e => e.stopPropagation()}>
        <h3 className="font-bold mb-3">Sell — {row.batch_no}</h3>
        <form onSubmit={submit} className="space-y-2 text-sm">
          {HONEY_SIZES.map(s => (
            <input key={s} type="number" step="0.01" placeholder={s} onChange={e => setSizes({ ...sizes, [s]: e.target.value })} className="w-full border rounded-lg px-3 py-2" />
          ))}
          <div className="flex gap-4">
            <label><input type="radio" checked={saleType === "walk_in"} onChange={() => setSaleType("walk_in")} /> Walk-in</label>
            <label><input type="radio" checked={saleType === "parcel"} onChange={() => setSaleType("parcel")} /> Parcel</label>
          </div>
          <button type="submit" className="w-full py-2.5 rounded-lg font-bold text-white" style={{ background: "#C8900A" }}>Confirm sale</button>
        </form>
      </div>
    </div>
  );
}
