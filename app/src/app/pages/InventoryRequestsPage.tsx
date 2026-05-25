import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "react-router";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { CorrectionReviewModal, type CorrectionRequestDetail } from "@/app/components/honey/CorrectionReviewModal";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost } from "@/app/lib/api";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import type { Supplier } from "@/app/lib/suppliers";

type CorrectionType = "qty_delta" | "set_qty" | "variant" | "supplier" | "packaging_size";

function formatRequestLine(r: CorrectionRequestDetail): string {
  if (r.summary) return r.summary;
  const ctx = r.row_context;
  const prefix = ctx?.supplier_code ? `${ctx.supplier_code} · ` : "";
  const t = (r.correction_type ?? "qty_delta") as CorrectionType;
  if (t === "set_qty") return `${prefix}Set qty → ${r.new_value}`;
  if (t === "variant") return `${prefix}Variant → ${r.new_value}`;
  if (t === "supplier") return `${prefix}Supplier change`;
  if (t === "packaging_size") return `${prefix}Packaging ${r.new_value}`;
  return `${prefix}${r.action} ${r.amount}${r.size ? ` (${r.size})` : ""}`;
}

export function InventoryRequestsPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const isPm = user?.role === "prod_manager";
  const readonly = user?.role === "hr";
  const [searchParams, setSearchParams] = useSearchParams();
  const { refresh: refreshNotifications, markViewed } = useCorrectionNotifications();
  const [requests, setRequests] = useState<CorrectionRequestDetail[]>([]);
  const [loading, setLoading] = useState(true);
  const [showNew, setShowNew] = useState(false);
  const [reviewId, setReviewId] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ requests: CorrectionRequestDetail[] }>("honey.php", "correction_list");
      setRequests(data.requests);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const openReview = useCallback(
    async (id: number) => {
      if (isExec) await markViewed(id);
      setReviewId(id);
      setSearchParams({ request: String(id) }, { replace: true });
    },
    [isExec, markViewed, setSearchParams],
  );

  useEffect(() => {
    const param = searchParams.get("request");
    if (!param) return;
    const id = parseInt(param, 10);
    if (id > 0 && requests.length > 0) {
      setReviewId(id);
      if (isExec) markViewed(id);
    }
  }, [searchParams, requests, isExec, markViewed]);

  const closeReview = () => {
    setReviewId(null);
    setSearchParams({}, { replace: true });
  };

  const reviewRequest = reviewId ? requests.find(r => r.id === reviewId) : null;

  const handleReviewDone = async () => {
    closeReview();
    await refresh();
    await refreshNotifications();
  };

  return (
    <PageLayout
      title="Correction requests"
      subtitle="PM requests changes to qty, variant, or supplier; Executive approves or edits directly on inventory tables"
      actions={
        isPm ? (
          <button
            onClick={() => setShowNew(true)}
            className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
            style={{ background: "#C8900A" }}
          >
            <Plus size={13} /> New request
          </button>
        ) : undefined
      }
    >
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              {["Date", "Stage", "Change", "Reason", "Status", isExec && !readonly ? "Review" : ""].filter(Boolean).map(h => (
                <th key={h} className="text-left px-4 py-3">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={6} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {requests.map(r => (
              <tr
                key={r.id}
                className={`border-b cursor-pointer hover:bg-secondary/30 ${
                  r.status === "pending" ? "border-l-4 border-l-amber-400" : r.status === "approved" ? "border-l-4 border-l-green-500" : "border-l-4 border-l-red-400"
                }`}
                onClick={() => openReview(r.id)}
              >
                <td className="px-4 py-3 text-xs">{r.created_at}</td>
                <td className="px-4 py-3 capitalize">{r.stage}</td>
                <td className="px-4 py-3">{formatRequestLine(r)}</td>
                <td className="px-4 py-3 max-w-xs truncate">{r.reason}</td>
                <td className="px-4 py-3 capitalize">{r.status}</td>
                {isExec && !readonly && (
                  <td className="px-4 py-3" onClick={e => e.stopPropagation()}>
                    {r.status === "pending" ? (
                      <button type="button" onClick={() => openReview(r.id)} className="text-xs text-[#C8900A] font-semibold">
                        Review
                      </button>
                    ) : (
                      <span className="text-xs text-muted-foreground">{r.reviewed_by_name ?? "—"}</span>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {showNew && <NewRequestModal onClose={() => setShowNew(false)} onDone={() => { setShowNew(false); refresh(); }} />}
      {reviewRequest && (
        <CorrectionReviewModal request={reviewRequest} onClose={closeReview} onDone={handleReviewDone} />
      )}
    </PageLayout>
  );
}

function NewRequestModal({ onClose, onDone }: { onClose: () => void; onDone: () => void }) {
  const [stage, setStage] = useState("warehouse");
  const [options, setOptions] = useState<{ id: number; label: string; row_table: string }[]>([]);
  const [rowId, setRowId] = useState("");
  const [rowTable, setRowTable] = useState("");
  const [correctionType, setCorrectionType] = useState<CorrectionType>("qty_delta");
  const [action, setAction] = useState<"add" | "deduct">("deduct");
  const [amount, setAmount] = useState("");
  const [setQty, setSetQty] = useState("");
  const [variant, setVariant] = useState("light");
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [packSize, setPackSize] = useState("250ml");
  const [packQty, setPackQty] = useState("");
  const [reason, setReason] = useState("");

  useEffect(() => {
    apiGet<{ options: typeof options }>("honey.php", "correction_row_options", { stage }).then(d => {
      setOptions(d.options);
      setRowId("");
    });
  }, [stage]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    const opt = options.find(o => String(o.id) === rowId);
    const payload: Record<string, unknown> = {
      stage,
      row_table: opt?.row_table || rowTable,
      row_id: parseInt(rowId, 10),
      correction_type: correctionType,
      reason,
    };
    if (correctionType === "qty_delta") {
      payload.action = action;
      payload.amount = parseFloat(amount);
    } else if (correctionType === "set_qty") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = setQty;
    } else if (correctionType === "variant") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = variant;
    } else if (correctionType === "supplier") {
      if (!supplier) return;
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = String(supplier.id);
    } else if (correctionType === "packaging_size") {
      payload.action = "add";
      payload.amount = 0;
      payload.new_value = `${packSize}:${packQty}`;
      payload.size = packSize;
    }
    await apiPost("honey.php", "correction_submit", payload);
    onDone();
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4 overflow-y-auto" onClick={onClose}>
      <div className="bg-card rounded-xl border p-6 max-w-md w-full my-8" onClick={e => e.stopPropagation()}>
        <h3 className="font-bold mb-4">New correction request</h3>
        <form onSubmit={submit} className="space-y-3 text-sm">
          <select value={stage} onChange={e => setStage(e.target.value)} className="w-full border rounded-lg px-3 py-2">
            {["warehouse", "production", "packaging"].map(s => (
              <option key={s} value={s}>
                {s}
              </option>
            ))}
          </select>
          <select
            value={rowId}
            onChange={e => {
              setRowId(e.target.value);
              const o = options.find(x => String(x.id) === e.target.value);
              if (o) setRowTable(o.row_table);
            }}
            className="w-full border rounded-lg px-3 py-2"
            required
          >
            <option value="">Select row…</option>
            {options.map(o => (
              <option key={o.id} value={o.id}>
                {o.label}
              </option>
            ))}
          </select>
          <select value={correctionType} onChange={e => setCorrectionType(e.target.value as CorrectionType)} className="w-full border rounded-lg px-3 py-2">
            <option value="qty_delta">Quantity — add or deduct</option>
            <option value="set_qty">Quantity — set to exact value</option>
            <option value="variant">Change variant</option>
            {stage === "warehouse" && <option value="supplier">Change supplier</option>}
            {stage === "packaging" && <option value="packaging_size">Set packaging size qty</option>}
          </select>

          {correctionType === "qty_delta" && (
            <>
              <select value={action} onChange={e => setAction(e.target.value as "add" | "deduct")} className="w-full border rounded-lg px-3 py-2">
                <option value="add">Add</option>
                <option value="deduct">Deduct</option>
              </select>
              <input type="number" step="0.01" value={amount} onChange={e => setAmount(e.target.value)} placeholder="Amount" className="w-full border rounded-lg px-3 py-2" required />
              {stage === "packaging" && (
                <select value={packSize} onChange={e => setPackSize(e.target.value)} className="w-full border rounded-lg px-3 py-2">
                  {HONEY_SIZES.map(s => (
                    <option key={s} value={s}>
                      {s}
                    </option>
                  ))}
                </select>
              )}
            </>
          )}
          {correctionType === "set_qty" && (
            <>
              <input type="number" step="0.01" value={setQty} onChange={e => setSetQty(e.target.value)} placeholder="New quantity" className="w-full border rounded-lg px-3 py-2" required />
              <p className="text-xs text-muted-foreground">
                Sets both current and initial to this value (full reset). Use add/deduct to change only what is on hand.
              </p>
            </>
          )}
          {correctionType === "variant" && (
            <select value={variant} onChange={e => setVariant(e.target.value)} className="w-full border rounded-lg px-3 py-2">
              {HONEY_VARIANTS.map(v => (
                <option key={v} value={v}>
                  {v}
                </option>
              ))}
            </select>
          )}
          {correctionType === "supplier" && (
            <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
          )}
          {correctionType === "packaging_size" && (
            <div className="flex gap-2">
              <select value={packSize} onChange={e => setPackSize(e.target.value)} className="border rounded-lg px-2 py-2 flex-1">
                {HONEY_SIZES.map(s => (
                  <option key={s} value={s}>
                    {s}
                  </option>
                ))}
              </select>
              <input type="number" step="0.01" value={packQty} onChange={e => setPackQty(e.target.value)} placeholder="Qty" className="border rounded-lg px-2 py-2 w-24" required />
            </div>
          )}

          <textarea value={reason} onChange={e => setReason(e.target.value)} placeholder="Reason (required)" className="w-full border rounded-lg px-3 py-2" rows={3} required />
          <button type="submit" className="w-full py-2.5 rounded-lg font-bold text-white" style={{ background: "#C8900A" }}>
            Submit request
          </button>
        </form>
      </div>
    </div>
  );
}
