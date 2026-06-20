import { useCallback, useEffect, useMemo, useState } from "react";
import { useParams } from "react-router";
import { Plus, Search, AlertCircle, Loader2, Download, RefreshCcw } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";

interface Department { id: number; name: string }
interface StockEntry {
  id: number;
  stock_id: number;
  dept_id: number;
  qty: string;
  batching_date: string;
  supplier_batch_no: string | null;
  entered_by: number;
  created_at: string;
  stock_name: string;
  unit: string;
  current_price: string;
  supplier_name: string | null;
  supplier_code: string | null;
  dept_name: string;
  entered_by_name: string;
}

interface EntriesPayload {
  entries: StockEntry[];
  can_view_supplier: boolean;
  can_view_pricing: boolean;
  can_add: boolean;
  can_view_all: boolean;
}

// URL slugs differ from DB dept names (cacao-brc vs cacao_brc)
const SLUG_TO_KEY: Record<string, string> = {
  honey: "honey",
  paragis: "paragis",
  "cacao-brc": "cacao_brc",
  cacao_brc: "cacao_brc",
  csr: "csr",
};

const DEPT_LABELS: Record<string, string> = {
  honey: "Honey",
  paragis: "Paragis",
  cacao_brc: "Cacao / BRC",
  csr: "Customer Service",
};

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

export function InventoryPage() {
  const { dept } = useParams();
  const { user } = useAuth();
  const role = user?.role ?? "staff";
  const isExec = role === "executive";

  const requestedSlug = dept ? SLUG_TO_KEY[dept] : "";
  const [departments, setDepartments] = useState<Department[]>([]);
  const [entries, setEntries] = useState<StockEntry[]>([]);
  const [flags, setFlags] = useState<Pick<EntriesPayload, "can_view_supplier" | "can_view_pricing" | "can_add" | "can_view_all">>({
    can_view_supplier: false, can_view_pricing: false, can_add: false, can_view_all: false,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);

  const departmentId = useMemo(() => {
    if (!requestedSlug || departments.length === 0) return undefined;
    const match = departments.find(d => d.name === requestedSlug);
    return match?.id;
  }, [requestedSlug, departments]);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [d, e] = await Promise.all([
        apiGet<{ departments: Department[] }>("stocks.php", "departments"),
        apiGet<EntriesPayload>("stocks.php", "entries_list", departmentId ? { dept_id: departmentId } : undefined),
      ]);
      setDepartments(d.departments);
      setEntries(e.entries);
      setFlags({
        can_view_supplier: e.can_view_supplier,
        can_view_pricing: e.can_view_pricing,
        can_add: e.can_add,
        can_view_all: e.can_view_all,
      });
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not load inventory.");
    } finally {
      setLoading(false);
    }
  }, [departmentId]);

  useEffect(() => { refresh(); }, [refresh]);

  const filtered = useMemo(() => {
    const q = search.toLowerCase();
    if (!q) return entries;
    return entries.filter(s =>
      s.stock_name.toLowerCase().includes(q) ||
      s.entered_by_name.toLowerCase().includes(q) ||
      (s.supplier_batch_no ?? "").toLowerCase().includes(q) ||
      s.dept_name.toLowerCase().includes(q)
    );
  }, [entries, search]);

  const title = requestedSlug && DEPT_LABELS[requestedSlug]
    ? `${DEPT_LABELS[requestedSlug]} — Stock`
    : "Inventory — Stock";

  return (
    <PageLayout
      title={title}
      subtitle="Incoming stock entries and current inventory"
      actions={
        <div className="flex items-center gap-2">
          <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
            <RefreshCcw size={13} /> Refresh
          </button>
          {isExec && (
            <button onClick={() => window.alert("Export coming in Phase 5.")} className="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-border hover:bg-secondary transition-colors text-muted-foreground">
              <Download size={13} /> Export
            </button>
          )}
        </div>
      }
    >
      <div className="space-y-5">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        {notice && (
          <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{notice}</div>
        )}

        {flags.can_add && (
          <div className="flex justify-end">
            <button
              onClick={() => setShowForm(true)}
              className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white"
              style={{ background: "#C8900A" }}
            >
              <Plus size={15} /> Add stock entry
            </button>
          </div>
        )}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border gap-4">
            <h3 className="text-sm font-semibold text-foreground">{flags.can_view_all ? "All entries" : "Your entries"}</h3>
            <div className="relative">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search..." className="pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background outline-none focus:border-[#C8900A] transition-colors w-48" />
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  <Th>Stock</Th>
                  <Th>Department</Th>
                  <Th>Qty</Th>
                  <Th>Batching date</Th>
                  {flags.can_view_supplier && <Th>Supplier batch</Th>}
                  {isExec && <Th>Supplier</Th>}
                  {flags.can_view_pricing && <Th>Unit price</Th>}
                  <Th>Entered by</Th>
                  <Th>When</Th>
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={9} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && filtered.length === 0 && (
                  <tr><td colSpan={9} className="px-6 py-8 text-center text-sm text-muted-foreground">No stock entries.</td></tr>
                )}
                {filtered.map((s, i) => (
                  <tr key={s.id} className={`border-b border-border last:border-0 hover:bg-secondary/30 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3.5 font-medium text-foreground">{s.stock_name}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">{s.dept_name}</td>
                    <td className="px-5 py-3.5 font-semibold">{parseFloat(s.qty).toLocaleString()} <span className="text-muted-foreground font-normal">{s.unit}</span></td>
                    <td className="px-5 py-3.5 text-muted-foreground text-xs">{fmtDate(s.batching_date)}</td>
                    {flags.can_view_supplier && <td className="px-5 py-3.5 text-muted-foreground font-mono text-xs">{s.supplier_batch_no ?? "—"}</td>}
                    {isExec && (
                      <td className="px-5 py-3.5">
                        <p className="text-sm text-foreground">{s.supplier_name ?? "—"}</p>
                        <p className="text-xs text-muted-foreground">{s.supplier_code ?? ""}</p>
                      </td>
                    )}
                    {flags.can_view_pricing && <td className="px-5 py-3.5 font-semibold text-foreground">{peso(s.current_price)}</td>}
                    <td className="px-5 py-3.5 text-muted-foreground">{s.entered_by_name}</td>
                    <td className="px-5 py-3.5 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(s.created_at)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {showForm && flags.can_add && (
        <StockEntryModal
          departments={departments}
          presetDept={departmentId ?? null}
          onClose={() => setShowForm(false)}
          onSaved={async () => {
            setShowForm(false);
            setNotice("Stock entry saved.");
            await refresh();
            setTimeout(() => setNotice(null), 3000);
          }}
        />
      )}
    </PageLayout>
  );
}

function Th({ children }: { children: React.ReactNode }) {
  return <th className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{children}</th>;
}

function StockEntryModal({
  departments,
  presetDept,
  onClose,
  onSaved,
}: {
  departments: Department[];
  presetDept: number | null;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [deptId, setDeptId] = useState(String(presetDept ?? departments[0]?.id ?? ""));
  const [stockName, setStockName] = useState("");
  const [unit, setUnit] = useState("kg");
  const [qty, setQty] = useState("");
  const [batching, setBatching] = useState("");
  const [price, setPrice] = useState("");
  const [supplierName, setSupplierName] = useState("");
  const [supplierCode, setSupplierCode] = useState("");
  const [supplierBatchNo, setSupplierBatchNo] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await apiPost("stocks.php", "add_entry", {
        dept_id: deptId ? Number(deptId) : 0,
        stock_name: stockName,
        unit,
        qty,
        batching_date: batching,
        current_price: price,
        supplier_name: supplierName,
        supplier_code: supplierCode,
        supplier_batch_no: supplierBatchNo,
      });
      onSaved();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save entry.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-lg w-full" labelledBy="stock-entry-modal-title">
      <AppModalHeader title="Add stock entry" titleId="stock-entry-modal-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-start gap-2">
              <AlertCircle size={13} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
            </div>
          )}
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Department</label>
            <select value={deptId} onChange={e => setDeptId(e.target.value)} required className={inputCls}>
              <option value="">— Select —</option>
              {departments.map(d => <option key={d.id} value={d.id}>{DEPT_LABELS[d.name] ?? d.name}</option>)}
            </select>
          </div>
          <div className="grid grid-cols-3 gap-2">
            <div className="col-span-2">
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Stock name</label>
              <input value={stockName} onChange={e => setStockName(e.target.value)} required maxLength={160} placeholder="Raw Honey — Light" className={inputCls} />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Unit</label>
              <input value={unit} onChange={e => setUnit(e.target.value)} required maxLength={32} className={inputCls} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-2">
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Quantity</label>
              <input value={qty} onChange={e => setQty(e.target.value)} required type="number" step="0.01" min="0" className={inputCls} />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Batching date</label>
              <input value={batching} onChange={e => setBatching(e.target.value)} required type="datetime-local" className={inputCls} />
            </div>
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Current price (executive only)</label>
            <input value={price} onChange={e => setPrice(e.target.value)} type="number" step="0.01" min="0" className={inputCls} placeholder="0.00" />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier</label>
              <input value={supplierName} onChange={e => setSupplierName(e.target.value)} maxLength={160} className={inputCls} />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier code</label>
              <input value={supplierCode} onChange={e => setSupplierCode(e.target.value)} maxLength={64} className={inputCls} />
            </div>
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier batch no.</label>
            <input value={supplierBatchNo} onChange={e => setSupplierBatchNo(e.target.value)} maxLength={120} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter className="justify-end">
          <button type="button" onClick={onClose} className="px-3 py-2 text-sm rounded-lg border border-border text-muted-foreground hover:text-foreground">Cancel</button>
          <button disabled={saving} type="submit" className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-70" style={{ background: "#C8900A" }}>
            {saving ? "Saving…" : "Save entry"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}

const inputCls = "w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-3 py-2.5 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150";
