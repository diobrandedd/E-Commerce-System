import { useCallback, useEffect, useMemo, useState } from "react";
import { CheckCircle, AlertCircle, Loader2, RefreshCcw, Plus, RotateCcw } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";

interface SellableProduct {
  id: number;
  name: string;
  size: string | null;
  variant: string | null;
  selling_price: string;
  dept_name: string;
}

interface ARCredit {
  id: number;
  user_id: number | null;
  debtor_user_id: number | null;
  debtor_name: string;
  debtor_mobile: string | null;
  product_id: number;
  qty: string;
  price_at_time: string;
  total_amount: string;
  status: "outstanding" | "paid";
  taken_at: string;
  paid_at: string | null;
  marked_paid_by: number | null;
  user_name: string;
  dept_name: string | null;
  product_name: string;
  product_size: string | null;
  product_variant: string | null;
  paid_by_name: string | null;
  pos_sale_id: number | null;
  receipt_no: string | null;
  source: string;
}

interface ListPayload {
  credits: ARCredit[];
  missing_migration?: boolean;
}

const peso = (v: string | number) => {
  const n = typeof v === "string" ? parseFloat(v) : v;
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const fmtDate = (s: string | null) => {
  if (!s) return "—";
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

export function ARCreditsPage() {
  const { can } = useAuth();
  const isExec = can("ar.view_all");
  const canTakeCredit = user
    ? ["executive", "prod_manager", "team_leader", "csr_tl", "staff", "csr_staff"].includes(user.role)
    : false;
  const [credits, setCredits] = useState<ARCredit[]>([]);
  const [missingMigration, setMissingMigration] = useState(false);
  const [filter, setFilter] = useState<"all" | "outstanding" | "paid">("outstanding");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [busy, setBusy] = useState<number | null>(null);
  const [showTakeModal, setShowTakeModal] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<ListPayload>("ar.php", "list");
      setCredits(payload.credits);
      setMissingMigration(!!payload.missing_migration);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load AR credits.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  const filtered = useMemo(
    () => credits.filter(c => filter === "all" ? true : c.status === filter),
    [credits, filter]
  );

  const totals = useMemo(() => ({
    outstandingAmount: credits.filter(c => c.status === "outstanding").reduce((s, c) => s + parseFloat(c.total_amount), 0),
    outstandingCount: credits.filter(c => c.status === "outstanding").length,
    paidAmount: credits.filter(c => c.status === "paid").reduce((s, c) => s + parseFloat(c.total_amount), 0),
  }), [credits]);

  const markPaid = async (id: number) => {
    if (!confirm("Mark this credit as paid?")) return;
    setBusy(id);
    try {
      await apiPost("ar.php", "mark_paid", { id });
      setNotice("Credit marked as paid.");
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not mark as paid.");
    } finally {
      setBusy(null);
    }
  };

  const markUnpaid = async (id: number) => {
    if (!confirm("Mark this credit as unpaid again?")) return;
    setBusy(id);
    try {
      await apiPost("ar.php", "mark_unpaid", { id });
      setNotice("Credit marked as outstanding.");
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not mark as unpaid.");
    } finally {
      setBusy(null);
    }
  };

  const debtorLabel = (c: ARCredit) => c.debtor_name || c.user_name || "—";

  return (
    <PageLayout
      title="AR Credits"
      subtitle={isExec ? "Accounts receivable — staff product credits" : "Your outstanding product credits"}
      actions={
        <div className="flex items-center gap-2">
          {canTakeCredit && (
            <button
              onClick={() => setShowTakeModal(true)}
              className="inline-flex items-center gap-1.5 text-xs font-semibold text-white px-3 py-1.5 rounded-lg transition-transform active:scale-[0.98] hover:opacity-95"
              style={{ background: "#C8900A" }}
            >
              <Plus size={13} /> Take on credit
            </button>
          )}
          <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border transition-colors hover:bg-secondary">
            <RefreshCcw size={13} /> Refresh
          </button>
        </div>
      }
    >
      <div className="space-y-5">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        <AppSuccessNotice message={notice} />
        {missingMigration && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Setup needed:</strong> run <code>php tools/apply_migration_phase50.php</code> to enable AR checkout fields.
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="bg-card rounded-xl border border-border p-5" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Total outstanding</p>
            <p className="text-2xl font-bold text-red-600">{peso(totals.outstandingAmount)}</p>
          </div>
          <div className="bg-card rounded-xl border border-border p-5" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Outstanding accounts</p>
            <p className="text-2xl font-bold text-foreground">{totals.outstandingCount}</p>
          </div>
          <div className="bg-card rounded-xl border border-border p-5" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Cleared</p>
            <p className="text-2xl font-bold text-green-600">{peso(totals.paidAmount)}</p>
          </div>
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center gap-2 px-6 py-4 border-b border-border">
            {(["all", "outstanding", "paid"] as const).map(f => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                className={`text-xs font-semibold px-3 py-1.5 rounded-lg capitalize transition-colors active:scale-[0.98] ${filter === f ? "text-white" : "text-muted-foreground hover:bg-secondary"}`}
                style={filter === f ? { background: "#C8900A" } : {}}
              >
                {f}
              </button>
            ))}
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Debtor", "Mobile", "Department", "Product", "Qty", "Unit price", "Total", "Date", "Status", isExec ? "Action" : ""].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={10} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && filtered.length === 0 && (
                  <tr><td colSpan={10} className="px-6 py-8 text-center text-sm text-muted-foreground">No credits.</td></tr>
                )}
                {filtered.map((c, i) => (
                  <tr key={c.id} className={`border-b border-border last:border-0 hover:bg-secondary/30 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3.5 font-medium text-foreground">
                      {debtorLabel(c)}
                      {c.source === "pos" && (
                        <span className="ml-1.5 inline-flex rounded-full bg-red-100 text-red-700 text-[10px] px-1.5 py-0.5 font-semibold">POS</span>
                      )}
                    </td>
                    <td className="px-5 py-3.5 text-muted-foreground text-xs whitespace-nowrap">{c.debtor_mobile ?? "—"}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">{c.dept_name ?? "—"}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">
                      {c.product_name}
                      {c.product_size ? ` · ${c.product_size}` : ""}
                      {c.product_variant ? ` (${c.product_variant})` : ""}
                    </td>
                    <td className="px-5 py-3.5 text-foreground">{parseFloat(c.qty).toLocaleString()}</td>
                    <td className="px-5 py-3.5 text-foreground">{peso(c.price_at_time)}</td>
                    <td className="px-5 py-3.5 font-semibold text-foreground">{peso(c.total_amount)}</td>
                    <td className="px-5 py-3.5 text-muted-foreground text-xs whitespace-nowrap">
                      {fmtDate(c.taken_at)}
                      {c.receipt_no && (
                        <p className="font-mono text-[10px] mt-0.5 text-foreground/70">{c.receipt_no}</p>
                      )}
                    </td>
                    <td className="px-5 py-3.5">
                      <span className={`inline-block text-xs font-semibold px-2.5 py-1 rounded-full ${c.status === "outstanding" ? "bg-red-100 text-red-600" : "bg-green-100 text-green-700"}`}>
                        {c.status === "outstanding" ? "Outstanding" : "Paid"}
                      </span>
                    </td>
                    <td className="px-5 py-3.5">
                      {isExec && c.status === "outstanding" && (
                        <button
                          onClick={() => markPaid(c.id)}
                          disabled={busy === c.id}
                          className="flex items-center gap-1 text-xs font-semibold text-green-700 hover:text-green-900 transition-colors disabled:opacity-50"
                        >
                          <CheckCircle size={13} /> Mark paid
                        </button>
                      )}
                      {isExec && c.status === "paid" && (
                        <button
                          onClick={() => markUnpaid(c.id)}
                          disabled={busy === c.id}
                          className="flex items-center gap-1 text-xs font-semibold text-red-700 hover:text-red-900 transition-colors disabled:opacity-50"
                        >
                          <RotateCcw size={13} /> Mark unpaid
                        </button>
                      )}
                      {c.status === "paid" && c.paid_by_name && (
                        <p className="text-xs text-muted-foreground mt-1">By {c.paid_by_name}</p>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {showTakeModal && (
        <TakeCreditModal
          onClose={() => setShowTakeModal(false)}
          onSuccess={(msg) => {
            setShowTakeModal(false);
            setNotice(msg);
            refresh();
          }}
        />
      )}
    </PageLayout>
  );
}

interface TakeCreditModalProps {
  onClose: () => void;
  onSuccess: (message: string) => void;
}

function TakeCreditModal({ onClose, onSuccess }: TakeCreditModalProps) {
  const [products, setProducts] = useState<SellableProduct[]>([]);
  const [productId, setProductId] = useState<number | null>(null);
  const [qty, setQty] = useState("1");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      try {
        const data = await apiGet<{ products: SellableProduct[] }>("ar.php", "products");
        setProducts(data.products);
        if (data.products.length > 0) setProductId(data.products[0].id);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load products.");
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const selected = products.find(p => p.id === productId);
  const qtyNum = parseFloat(qty);
  const lineTotal = selected && Number.isFinite(qtyNum) ? parseFloat(selected.selling_price) * qtyNum : 0;

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!productId || !Number.isFinite(qtyNum) || qtyNum <= 0) {
      setError("Pick a product and enter a positive quantity.");
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      await apiPost("ar.php", "take", { product_id: productId, qty: qtyNum });
      onSuccess(`Credit recorded: ${selected?.name} x${qtyNum}`);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not record credit.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="ar-credit-modal-title">
      <AppModalHeader title="Take product on credit" titleId="ar-credit-modal-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-4">
          {error && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-start gap-2">
              <AlertCircle size={14} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
            </div>
          )}
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Product</label>
            {loading ? (
              <p className="text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-1" size={13} /> Loading…</p>
            ) : products.length === 0 ? (
              <p className="text-sm text-muted-foreground">No sellable products yet.</p>
            ) : (
              <select
                value={productId ?? ""}
                onChange={e => setProductId(parseInt(e.target.value, 10))}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
              >
                {products.map(p => (
                  <option key={p.id} value={p.id}>
                    {p.name}{p.size ? ` · ${p.size}` : ""}{p.variant ? ` (${p.variant})` : ""} — ₱{parseFloat(p.selling_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                  </option>
                ))}
              </select>
            )}
          </div>
          <div>
            <label className="block text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-1.5">Quantity</label>
            <input
              type="number"
              min="0.01"
              step="0.01"
              value={qty}
              onChange={e => setQty(e.target.value)}
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-[#C8900A]"
            />
          </div>
          {selected && (
            <div className="flex justify-between items-center text-sm bg-secondary/40 rounded-lg px-3 py-2">
              <span className="text-muted-foreground">Total credit</span>
              <span className="font-bold" style={{ color: "#C8900A" }}>
                ₱{lineTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </span>
            </div>
          )}
        </AppModalBody>
        <AppModalFooter className="justify-end">
          <button type="button" onClick={onClose} className="px-4 py-2 rounded-lg text-sm font-medium border border-border text-muted-foreground hover:bg-secondary transition-colors">
            Cancel
          </button>
          <button
            type="submit"
            disabled={submitting || loading || products.length === 0}
            className="px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50 transition-transform active:scale-[0.98]"
            style={{ background: "#C8900A" }}
          >
            {submitting ? "Saving…" : "Record credit"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}
