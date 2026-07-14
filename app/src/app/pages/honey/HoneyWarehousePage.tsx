import { useCallback, useEffect, useState } from "react";
import { Plus, Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { HoneyStockStatusBadge } from "@/app/components/honey/HoneyStockStatusBadge";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_VARIANTS } from "@/app/lib/honey";
import { canExecutiveDirectEdit, canMutateHoneyPipeline, canViewSupplierProfile } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { previewSupplierCode, type Supplier } from "@/app/lib/suppliers";
import type { WarehousePricingRow } from "@/app/lib/honeyPricing";

type WhRow = WarehousePricingRow;

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function HoneyWarehousePage() {
  const { user } = useAuth();
  const canMutate = canMutateHoneyPipeline(user?.role);
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const showSupplierProfile = canViewSupplierProfile(user?.role);
  const [rows, setRows] = useState<WhRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showIn, setShowIn] = useState(false);
  const [showOut, setShowOut] = useState<WhRow | null>(null);
  const [editRow, setEditRow] = useState<WhRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: WhRow[] }>("honey.php", "warehouse_list");
      setRows(data.rows);
      setPage(1);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed");
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
    <PageLayout title="Raw Honey" subtitle="Warehouse — stock-in and stock-out to production">
      <div className="space-y-4">
        <HoneyStageTabs />
        {error && <p className="text-sm text-red-600">{error}</p>}
        {canMutate && (
          <button onClick={() => setShowIn(true)} className="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-2 rounded-lg" style={{ background: "#C8900A" }}>
            <Plus size={14} /> Stock in
          </button>
        )}
        <div className="bg-card rounded-xl border border-border overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40">
                {[
                  "Supplier code",
                  "Arrival",
                  "Variant",
                  "Qty",
                  "Status",
                  canMutate || canExecEdit ? "Actions" : "",
                ]
                  .filter(Boolean)
                  .map(h => (
                    <th key={h} className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase">{h}</th>
                  ))}
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={canMutate || canExecEdit ? 6 : 5} className="px-4 py-8 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={14} />
                  </td>
                </tr>
              )}
              {!loading && pagedRows.map(r => {
                return (
                  <tr key={r.id} className="border-b hover:bg-secondary/20">
                    <td className="px-4 py-3">
                      {showSupplierProfile ? (
                        <SupplierCodeButton code={r.supplier_code} />
                      ) : (
                        <span className="font-medium font-mono">{r.supplier_code}</span>
                      )}
                    </td>
                    <td className="px-4 py-3">{r.arrival_date}</td>
                    <td className="px-4 py-3 capitalize">{r.variant}</td>
                    <td className="px-4 py-3">
                      <QtyCounter current={r.current_qty} initial={r.initial_qty} />
                    </td>
                    <td className="px-4 py-3">
                      <HoneyStockStatusBadge current={r.current_qty} initial={r.initial_qty} />
                    </td>
                    {(canMutate || canExecEdit) && (
                      <td className="px-4 py-3 space-x-2">
                        {canExecEdit && (
                          <button type="button" onClick={() => setEditRow(r)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                            Edit
                          </button>
                        )}
                        {canMutate && (
                          <button disabled={Number(r.current_qty) <= 0} onClick={() => setShowOut(r)} className="text-xs font-semibold text-[#C8900A] disabled:opacity-40">
                            Stock out
                          </button>
                        )}
                      </td>
                    )}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        {!loading && totalRows > 0 && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1">
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
              <span className="text-xs text-muted-foreground">
                Page {safePage} of {totalPages}
              </span>
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
      </div>
      {showIn && <StockInModal onClose={() => setShowIn(false)} onDone={() => { setShowIn(false); refresh(); }} />}
      {showOut && <StockOutModal row={showOut} onClose={() => setShowOut(null)} onDone={() => { setShowOut(null); refresh(); }} />}
      {editRow && (
        <ExecutiveRowEditModal
          stage="warehouse"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function StockInModal({ onClose, onDone }: { onClose: () => void; onDone: () => void }) {
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [variants, setVariants] = useState([{ variant: "light", qty: "" }]);
  const [showConfirm, setShowConfirm] = useState(false);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const codePreview = supplier ? previewSupplierCode(supplier.display_name, arrival) : "";

  const variantsValid = variants.every(v => parseFloat(v.qty) > 0);
  const canProceed = supplier !== null && variantsValid;

  const save = async () => {
    if (!supplier) return;
    setBusy(true);
    setErr(null);
    try {
      await apiPost("honey.php", "warehouse_stock_in", {
        supplier_id: supplier.id,
        arrival_date: arrival,
        variants: variants.map(v => ({ variant: v.variant, qty: parseFloat(v.qty) })),
      });
      onDone();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Failed");
      setShowConfirm(false);
    } finally {
      setBusy(false);
    }
  };

  if (showConfirm && supplier) {
    return (
      <Modal title="Confirm stock-in" onClose={() => setShowConfirm(false)}>
        <div className="space-y-3 text-sm">
          <p><strong>Supplier:</strong> {supplier.display_name}</p>
          <p><strong>Arrival:</strong> {arrival}</p>
          <p><strong>Supplier code:</strong> <span className="font-mono">{codePreview}</span></p>
          <p className="text-xs text-muted-foreground">Same supplier + same arrival date always share this code. A later date gets a new code.</p>
          <table className="w-full text-xs border rounded-lg">
            <thead><tr className="bg-secondary/50"><th className="text-left px-2 py-1">Variant</th><th className="text-left px-2 py-1">Gallon/s</th></tr></thead>
            <tbody>
              {variants.map((v, i) => (
                <tr key={i} className="border-t"><td className="px-2 py-1 capitalize">{v.variant}</td><td className="px-2 py-1">{v.qty}</td></tr>
              ))}
            </tbody>
          </table>
          {err && <p className="text-xs text-red-600">{err}</p>}
          <div className="flex gap-2 pt-2">
            <button type="button" onClick={() => setShowConfirm(false)} className="flex-1 py-2 rounded-lg border text-sm">Back</button>
            <button type="button" disabled={busy} onClick={save} className="flex-1 py-2 rounded-lg text-sm font-bold text-white" style={{ background: "#C8900A" }}>
              {busy ? "Saving…" : "Confirm & save"}
            </button>
          </div>
        </div>
      </Modal>
    );
  }

  return (
    <Modal title="Warehouse stock-in" onClose={onClose}>
      <div className="space-y-3">
        {err && <p className="text-xs text-red-600">{err}</p>}
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Supplier</label>
          <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
        </div>
        {supplier && (
          <p className="text-xs text-muted-foreground">Supplier code preview: <strong className="font-mono">{codePreview}</strong></p>
        )}
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Arrival date</label>
          <input type="date" value={arrival} onChange={e => setArrival(e.target.value)} className="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <p className="text-xs font-semibold text-muted-foreground">Variants (one row each — qty is always in gallon/s)</p>
        {variants.map((v, i) => (
          <div key={i} className="flex gap-2 items-center">
            <select value={v.variant} onChange={e => { const n = [...variants]; n[i].variant = e.target.value; setVariants(n); }} className="border rounded-lg px-2 py-2 text-sm flex-1">
              {HONEY_VARIANTS.map(x => <option key={x} value={x}>{x}</option>)}
            </select>
            <span className="text-xs text-muted-foreground whitespace-nowrap shrink-0">Gallon/s</span>
            <input type="number" step="0.01" min="0" value={v.qty} onChange={e => { const n = [...variants]; n[i].qty = e.target.value; setVariants(n); }} placeholder="0" className="border rounded-lg px-2 py-2 text-sm w-20" required />
            {variants.length > 1 && (
              <button type="button" onClick={() => setVariants(variants.filter((_, j) => j !== i))} className="text-xs text-red-600">Remove</button>
            )}
          </div>
        ))}
        <button type="button" onClick={() => setVariants([...variants, { variant: "light", qty: "" }])} className="text-xs text-[#C8900A]">+ Add another variant</button>
        <button
          type="button"
          disabled={!canProceed}
          onClick={() => setShowConfirm(true)}
          className="w-full py-2.5 rounded-lg text-sm font-bold text-white disabled:opacity-50"
          style={{ background: "#C8900A" }}
        >
          Review & confirm
        </button>
      </div>
    </Modal>
  );
}

function StockOutModal({ row, onClose, onDone }: { row: WhRow; onClose: () => void; onDone: () => void }) {
  const [qty, setQty] = useState("");
  const [busy, setBusy] = useState(false);
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    try {
      await apiPost("honey.php", "warehouse_stock_out", { warehouse_row_id: row.id, qty: parseFloat(qty) });
      onDone();
    } finally {
      setBusy(false);
    }
  };
  return (
    <Modal title="Stock out → Production" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3 text-sm">
        <p>Code: <strong>{row.supplier_code}</strong> · {row.variant}</p>
        <p>Available: <QtyCounter current={row.current_qty} initial={row.initial_qty} /></p>
        <input type="number" step="0.01" value={qty} onChange={e => setQty(e.target.value)} placeholder="Quantity" className="w-full border rounded-lg px-3 py-2" required />
        <button type="submit" disabled={busy} className="w-full py-2.5 rounded-lg font-bold text-white" style={{ background: "#C8900A" }}>Confirm</button>
      </form>
    </Modal>
  );
}

function Modal({ title, children, onClose }: { title: string; children: React.ReactNode; onClose: () => void }) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="honey-warehouse-modal-title">
      <AppModalHeader title={title} titleId="honey-warehouse-modal-title" onClose={onClose} />
      <AppModalBody>{children}</AppModalBody>
    </AppModal>
  );
}
