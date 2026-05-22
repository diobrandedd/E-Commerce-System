import { useCallback, useEffect, useMemo, useState } from "react";
import { TrendingUp, TrendingDown, Plus, ChevronDown, History, Pencil, AlertCircle, RefreshCcw, X, Loader2, Trash2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";

interface StockRow {
  id: number;
  name: string;
  unit: string;
  current_price: string;
  dept_id: number;
  dept_name: string;
  supplier_name: string | null;
  supplier_code: string | null;
  created_at: string;
  last_entry_at: string | null;
}

interface ProductRow {
  id: number;
  name: string;
  dept_id: number;
  dept_name: string;
  size: string | null;
  variant: string | null;
  selling_price: string;
  baseline_cost: string;
  current_cost: string;
  formula_count: number;
  created_by_name: string;
  created_at: string;
}

interface FormulaRow {
  id: number;
  stock_id: number;
  stock_name: string;
  unit: string;
  qty_per_unit: number;
  price_at_creation: number;
  current_price: number;
  baseline_line_cost: number;
  current_line_cost: number;
  delta: number;
}

interface CostHistoryRow {
  old_cost: string;
  new_cost: string;
  recalculated_at: string;
  triggered_by_stock_id: number;
}

interface StockHistoryRow {
  stock_id: number;
  stock_name: string;
  old_price: string;
  new_price: string;
  changed_at: string;
}

interface ProductDetail {
  product: {
    id: number;
    dept_id: number;
    dept_name: string;
    name: string;
    size: string | null;
    variant: string | null;
    selling_price: number;
    baseline_cost: number;
    current_cost: number;
  };
  formula_rows: FormulaRow[];
  cost_history: CostHistoryRow[];
  stock_history: StockHistoryRow[];
  show_pricing: boolean;
}

const peso = (n: number | string) => {
  const v = typeof n === "string" ? parseFloat(n) : n;
  if (!Number.isFinite(v)) return "—";
  return `₱${v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const fmtDate = (s: string | null | undefined) => {
  if (!s) return "—";
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

function CostBadge({ current, baseline }: { current: number; baseline: number }) {
  if (!Number.isFinite(current) || !Number.isFinite(baseline) || baseline === 0) {
    return <span className="text-xs text-muted-foreground">—</span>;
  }
  const diff = current - baseline;
  const pct = ((diff / baseline) * 100).toFixed(1);
  if (Math.abs(diff) < 0.01) return <span className="text-xs text-muted-foreground">No change</span>;
  return (
    <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${diff > 0 ? "bg-red-100 text-red-600" : "bg-green-100 text-green-700"}`}>
      {diff > 0 ? <TrendingUp size={11} /> : <TrendingDown size={11} />}
      {diff > 0 ? "+" : ""}{pct}%
    </span>
  );
}

export function CostingPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";

  const [stocks, setStocks] = useState<StockRow[]>([]);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [detailsById, setDetailsById] = useState<Record<number, ProductDetail | "loading">>({});
  const [editingStock, setEditingStock] = useState<StockRow | null>(null);
  const [creatingProduct, setCreatingProduct] = useState(false);
  const [addFormulaProductId, setAddFormulaProductId] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [stocksRes, productsRes] = await Promise.all([
        apiGet<{ stocks: StockRow[] }>("costing.php", "stocks_list"),
        apiGet<{ products: ProductRow[] }>("costing.php", "products_list"),
      ]);
      setStocks(stocksRes.stocks);
      setProducts(productsRes.products);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load costing data.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  const flash = (msg: string) => {
    setNotice(msg);
    setTimeout(() => setNotice(null), 3000);
  };

  const fetchDetail = async (productId: number) => {
    setDetailsById(prev => ({ ...prev, [productId]: "loading" }));
    try {
      const detail = await apiGet<ProductDetail>("costing.php", "product_view", { product_id: productId });
      setDetailsById(prev => ({ ...prev, [productId]: detail }));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load product detail.");
      setDetailsById(prev => {
        const next = { ...prev };
        delete next[productId];
        return next;
      });
    }
  };

  const toggleProduct = async (productId: number) => {
    if (expandedId === productId) {
      setExpandedId(null);
      return;
    }
    setExpandedId(productId);
    if (!detailsById[productId] || detailsById[productId] === "loading") {
      await fetchDetail(productId);
    }
  };

  const handleStockSaved = async () => {
    setEditingStock(null);
    flash("Stock updated. Affected products recosted.");
    await refresh();
    // Refresh any open detail panels
    if (expandedId) await fetchDetail(expandedId);
  };

  const handleProductCreated = async () => {
    setCreatingProduct(false);
    flash("Product created.");
    await refresh();
  };

  const handleFormulaChanged = async (productId: number, detail: ProductDetail, message: string) => {
    setDetailsById(prev => ({ ...prev, [productId]: detail }));
    flash(message);
    await refresh();
  };

  const stocksForDept = useMemo(() => {
    const map: Record<number, StockRow[]> = {};
    for (const s of stocks) {
      (map[s.dept_id] ||= []).push(s);
    }
    return map;
  }, [stocks]);

  return (
    <PageLayout title="Product Costing" subtitle="Formula builder and live cost tracking" actions={
      <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
        <RefreshCcw size={13} /> Refresh
      </button>
    }>
      <div className="space-y-5">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        {notice && (
          <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{notice}</div>
        )}

        {/* Stocks table */}
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border">
            <div>
              <h3 className="text-sm font-semibold text-foreground">Stock price management</h3>
              <p className="text-xs text-muted-foreground">{stocks.length} stocks{isExec ? " — click Edit to recost dependent products." : "."}</p>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Stock", "Department", "Unit", "Current price", "Supplier", "Last entry", isExec ? "Actions" : ""].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={isExec ? 7 : 6} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} />Loading…</td></tr>
                )}
                {!loading && stocks.length === 0 && (
                  <tr><td colSpan={isExec ? 7 : 6} className="px-6 py-8 text-center text-sm text-muted-foreground">No stocks yet.</td></tr>
                )}
                {stocks.map((s, i) => (
                  <tr key={s.id} className={`border-b border-border last:border-0 hover:bg-secondary/20 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3 font-medium text-foreground">{s.name}</td>
                    <td className="px-5 py-3 text-muted-foreground">{s.dept_name}</td>
                    <td className="px-5 py-3 text-muted-foreground">{s.unit}</td>
                    <td className="px-5 py-3 font-semibold text-foreground">{peso(s.current_price)}</td>
                    <td className="px-5 py-3 text-muted-foreground">
                      {s.supplier_name ?? "—"}{s.supplier_code ? ` · ${s.supplier_code}` : ""}
                    </td>
                    <td className="px-5 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(s.last_entry_at)}</td>
                    {isExec && (
                      <td className="px-5 py-3">
                        <button onClick={() => setEditingStock(s)} className="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A] transition-colors">
                          <Pencil size={12} /> Edit
                        </button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Products */}
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-foreground">Products</h3>
            <p className="text-xs text-muted-foreground">{products.length} products — click a row to inspect formula and history.</p>
          </div>
          <button
            onClick={() => setCreatingProduct(true)}
            className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white"
            style={{ background: "#C8900A" }}
          >
            <Plus size={15} /> New Product
          </button>
        </div>

        {products.map(p => {
          const isExpanded = expandedId === p.id;
          const current = parseFloat(p.current_cost);
          const baseline = parseFloat(p.baseline_cost);
          const selling = parseFloat(p.selling_price);
          const margin = selling - current;
          const marginPct = selling > 0 ? ((margin / selling) * 100).toFixed(1) : "0.0";
          const detail = detailsById[p.id];
          return (
            <div key={p.id} className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
              <button
                onClick={() => toggleProduct(p.id)}
                className="w-full flex items-center gap-4 px-6 py-5 hover:bg-secondary/30 transition-colors text-left"
              >
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-semibold text-foreground">{p.name}</span>
                    {p.size && <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">{p.size}</span>}
                    {p.variant && <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">{p.variant}</span>}
                    <span className="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{p.dept_name}</span>
                    <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">{p.formula_count} lines</span>
                  </div>
                </div>
                <div className="flex items-center gap-6 flex-shrink-0">
                  <div className="text-right hidden sm:block">
                    <p className="text-xs text-muted-foreground">Production Cost</p>
                    <p className="font-bold text-foreground">{peso(current)}</p>
                  </div>
                  {isExec && (
                    <div className="text-right hidden sm:block">
                      <p className="text-xs text-muted-foreground">Selling Price</p>
                      <p className="font-bold text-foreground">{peso(selling)}</p>
                    </div>
                  )}
                  {isExec && (
                    <div className="text-right hidden md:block">
                      <p className="text-xs text-muted-foreground">Margin</p>
                      <p className={`font-bold ${margin > 0 ? "text-green-600" : "text-red-500"}`}>
                        {peso(margin)} <span className="text-xs font-normal">({marginPct}%)</span>
                      </p>
                    </div>
                  )}
                  <div className="text-right">
                    <p className="text-xs text-muted-foreground">vs Baseline</p>
                    <CostBadge current={current} baseline={baseline} />
                  </div>
                  <ChevronDown size={16} className={`text-muted-foreground transition-transform duration-200 ${isExpanded ? "rotate-180" : ""}`} />
                </div>
              </button>

              {isExpanded && (
                <div className="border-t border-border">
                  {detail === "loading" || !detail ? (
                    <div className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading formula…</div>
                  ) : (
                    <ProductDetailView
                      detail={detail}
                      stocksInDept={stocksForDept[p.dept_id] ?? []}
                      onAddRow={() => setAddFormulaProductId(p.id)}
                      onUpdated={(d, msg) => handleFormulaChanged(p.id, d, msg)}
                    />
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Modals */}
      {editingStock && isExec && (
        <EditStockModal stock={editingStock} onClose={() => setEditingStock(null)} onSaved={handleStockSaved} />
      )}
      {creatingProduct && (
        <CreateProductModal stocks={stocks} onClose={() => setCreatingProduct(false)} onSaved={handleProductCreated} />
      )}
      {addFormulaProductId !== null && (
        <AddFormulaModal
          productId={addFormulaProductId}
          stocks={stocksForDept[products.find(p => p.id === addFormulaProductId)?.dept_id ?? 0] ?? []}
          onClose={() => setAddFormulaProductId(null)}
          onSaved={(detail) => {
            setAddFormulaProductId(null);
            handleFormulaChanged(addFormulaProductId, detail, "Formula updated.");
          }}
        />
      )}
    </PageLayout>
  );
}

function ProductDetailView({
  detail,
  stocksInDept,
  onAddRow,
  onUpdated,
}: {
  detail: ProductDetail;
  stocksInDept: StockRow[];
  onAddRow: () => void;
  onUpdated: (detail: ProductDetail, msg: string) => void;
}) {
  const { product, formula_rows, cost_history, stock_history, show_pricing } = detail;
  const [busyRow, setBusyRow] = useState<number | null>(null);

  const deleteRow = async (formulaId: number) => {
    if (!confirm("Delete this formula line?")) return;
    setBusyRow(formulaId);
    try {
      const result = await apiPost<ProductDetail>("costing.php", "formula_delete", {
        product_id: product.id,
        formula_id: formulaId,
      });
      onUpdated(result, "Formula row deleted.");
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Delete failed.");
    } finally {
      setBusyRow(null);
    }
  };

  return (
    <div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-secondary/40">
              {["Stock Used", "Qty / Unit", "Baseline price", "Current price", "Line cost (current)", "vs Baseline", "Actions"].map(h => (
                <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {formula_rows.length === 0 && (
              <tr><td colSpan={7} className="px-6 py-6 text-center text-sm text-muted-foreground">No formula lines yet. Add one to start costing.</td></tr>
            )}
            {formula_rows.map(f => (
              <tr key={f.id} className="border-b border-border last:border-0 hover:bg-secondary/20 transition-colors">
                <td className="px-6 py-3 font-medium text-foreground">{f.stock_name}</td>
                <td className="px-5 py-3 text-muted-foreground">{f.qty_per_unit} {f.unit}</td>
                <td className="px-5 py-3 text-muted-foreground">{peso(f.price_at_creation)}</td>
                <td className="px-5 py-3 font-medium text-foreground">{peso(f.current_price)}</td>
                <td className="px-5 py-3 font-semibold text-foreground">{peso(f.current_line_cost)}</td>
                <td className="px-5 py-3"><CostBadge current={f.current_line_cost} baseline={f.baseline_line_cost} /></td>
                <td className="px-5 py-3">
                  <button
                    onClick={() => deleteRow(f.id)}
                    disabled={busyRow === f.id}
                    className="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-lg border border-border text-red-600 hover:bg-red-50 disabled:opacity-50"
                  >
                    <Trash2 size={11} /> Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
          <tfoot>
            <tr className="border-t-2 border-border bg-secondary/30">
              <td className="px-6 py-3 font-bold text-foreground" colSpan={4}>Total production cost per unit</td>
              <td className="px-5 py-3 font-bold text-foreground">{peso(product.current_cost)}</td>
              <td className="px-5 py-3"><CostBadge current={product.current_cost} baseline={product.baseline_cost} /></td>
              <td className="px-5 py-3"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div className="px-6 py-3 border-t border-border flex justify-between items-center">
        <p className="text-xs text-muted-foreground">Baseline: {peso(product.baseline_cost)} · Current: {peso(product.current_cost)}</p>
        <button onClick={onAddRow} className="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg text-white" style={{ background: "#C8900A" }}>
          <Plus size={12} /> Add formula line
        </button>
      </div>

      {show_pricing && (cost_history.length > 0 || stock_history.length > 0) && (
        <div className="border-t border-border grid grid-cols-1 md:grid-cols-2">
          <div className="p-5">
            <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3 flex items-center gap-1"><History size={12} /> Cost history</h4>
            {cost_history.length === 0
              ? <p className="text-xs text-muted-foreground">No recalculations yet.</p>
              : (
                <ul className="space-y-1.5 text-xs">
                  {cost_history.map((h, i) => (
                    <li key={i} className="flex items-center justify-between gap-2 text-muted-foreground">
                      <span>{fmtDate(h.recalculated_at)}</span>
                      <span>
                        <span className="line-through">{peso(h.old_cost)}</span>{" → "}
                        <span className="font-semibold text-foreground">{peso(h.new_cost)}</span>
                      </span>
                    </li>
                  ))}
                </ul>
              )}
          </div>
          <div className="p-5 border-l border-border">
            <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3 flex items-center gap-1"><History size={12} /> Stock price history</h4>
            {stock_history.length === 0
              ? <p className="text-xs text-muted-foreground">No stock price changes for this product yet.</p>
              : (
                <ul className="space-y-1.5 text-xs">
                  {stock_history.map((h, i) => (
                    <li key={i} className="flex items-center justify-between gap-2 text-muted-foreground">
                      <span>{h.stock_name} · {fmtDate(h.changed_at)}</span>
                      <span>
                        <span className="line-through">{peso(h.old_price)}</span>{" → "}
                        <span className="font-semibold text-foreground">{peso(h.new_price)}</span>
                      </span>
                    </li>
                  ))}
                </ul>
              )}
          </div>
        </div>
      )}
    </div>
  );
}

// ----------------- modals -----------------

function EditStockModal({ stock, onClose, onSaved }: { stock: StockRow; onClose: () => void; onSaved: () => void }) {
  const [name, setName] = useState(stock.name);
  const [unit, setUnit] = useState(stock.unit);
  const [supplierName, setSupplierName] = useState(stock.supplier_name ?? "");
  const [supplierCode, setSupplierCode] = useState(stock.supplier_code ?? "");
  const [price, setPrice] = useState(stock.current_price);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await apiPost("costing.php", "stock_update_price", {
        stock_id: stock.id,
        name,
        unit,
        supplier_name: supplierName,
        supplier_code: supplierCode,
        current_price: price,
      });
      onSaved();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <ModalShell title={`Edit stock: ${stock.name}`} onClose={onClose}>
      <form onSubmit={submit} className="space-y-3">
        {error && <Banner kind="err">{error}</Banner>}
        <Field label="Stock name"><input value={name} onChange={e => setName(e.target.value)} required maxLength={160} className={inputCls} /></Field>
        <Field label="Unit"><input value={unit} onChange={e => setUnit(e.target.value)} required maxLength={32} className={inputCls} /></Field>
        <Field label="Current price"><input value={price} onChange={e => setPrice(e.target.value)} required type="number" step="0.01" min="0" className={inputCls} /></Field>
        <div className="grid grid-cols-2 gap-2">
          <Field label="Supplier name"><input value={supplierName} onChange={e => setSupplierName(e.target.value)} maxLength={160} className={inputCls} /></Field>
          <Field label="Supplier code"><input value={supplierCode} onChange={e => setSupplierCode(e.target.value)} maxLength={64} className={inputCls} /></Field>
        </div>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Save & recost" />
      </form>
    </ModalShell>
  );
}

function CreateProductModal({ stocks, onClose, onSaved }: { stocks: StockRow[]; onClose: () => void; onSaved: () => void }) {
  const deptOptions = useMemo(() => {
    const map = new Map<number, string>();
    for (const s of stocks) map.set(s.dept_id, s.dept_name);
    return Array.from(map.entries());
  }, [stocks]);
  const [name, setName] = useState("");
  const [deptId, setDeptId] = useState(String(deptOptions[0]?.[0] ?? ""));
  const [size, setSize] = useState("");
  const [variant, setVariant] = useState("");
  const [selling, setSelling] = useState("0");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      await apiPost("costing.php", "product_create", {
        name,
        dept_id: deptId ? Number(deptId) : 0,
        size,
        variant,
        selling_price: selling,
      });
      onSaved();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not create product.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <ModalShell title="Create product" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3">
        {error && <Banner kind="err">{error}</Banner>}
        <Field label="Product name"><input value={name} onChange={e => setName(e.target.value)} required maxLength={160} className={inputCls} /></Field>
        <Field label="Department">
          <select value={deptId} onChange={e => setDeptId(e.target.value)} required className={inputCls}>
            <option value="">— Select —</option>
            {deptOptions.map(([id, n]) => <option key={id} value={id}>{n}</option>)}
          </select>
        </Field>
        <div className="grid grid-cols-2 gap-2">
          <Field label="Size (optional, honey)">
            <select value={size} onChange={e => setSize(e.target.value)} className={inputCls}>
              <option value="">—</option>
              <option value="liter">Liter</option>
              <option value="350ml">350ml</option>
              <option value="250ml">250ml</option>
              <option value="gallon">Gallon</option>
            </select>
          </Field>
          <Field label="Variant (optional, honey)">
            <select value={variant} onChange={e => setVariant(e.target.value)} className={inputCls}>
              <option value="">—</option>
              <option value="light">Light</option>
              <option value="amber">Amber</option>
              <option value="dark">Dark</option>
            </select>
          </Field>
        </div>
        <Field label="Selling price (executive only)"><input value={selling} onChange={e => setSelling(e.target.value)} type="number" step="0.01" min="0" className={inputCls} /></Field>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Create product" />
      </form>
    </ModalShell>
  );
}

function AddFormulaModal({
  productId,
  stocks,
  onClose,
  onSaved,
}: {
  productId: number;
  stocks: StockRow[];
  onClose: () => void;
  onSaved: (detail: ProductDetail) => void;
}) {
  const [stockId, setStockId] = useState(String(stocks[0]?.id ?? ""));
  const [qty, setQty] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const detail = await apiPost<ProductDetail>("costing.php", "formula_add", {
        product_id: productId,
        stock_id: Number(stockId),
        qty_per_unit: qty,
      });
      onSaved(detail);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save formula line.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <ModalShell title="Add formula line" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3">
        {error && <Banner kind="err">{error}</Banner>}
        <Field label="Stock">
          <select value={stockId} onChange={e => setStockId(e.target.value)} required className={inputCls}>
            <option value="">— Select —</option>
            {stocks.map(s => <option key={s.id} value={s.id}>{s.name} ({peso(s.current_price)}/{s.unit})</option>)}
          </select>
        </Field>
        <Field label="Quantity per unit"><input value={qty} onChange={e => setQty(e.target.value)} required type="number" step="0.0001" min="0.0001" className={inputCls} /></Field>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Add line" />
      </form>
    </ModalShell>
  );
}

// ----------------- shared modal primitives -----------------

const inputCls = "w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-3 py-2.5 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150";

function ModalShell({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
  return (
    <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4" onClick={onClose}>
      <div className="bg-card rounded-2xl border border-border w-full max-w-md max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <h3 className="text-base font-semibold text-foreground">{title}</h3>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground"><X size={18} /></button>
        </div>
        <div className="px-5 py-5">{children}</div>
      </div>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">{label}</label>
      {children}
    </div>
  );
}

function Banner({ kind, children }: { kind: "ok" | "err"; children: React.ReactNode }) {
  const cls = kind === "ok"
    ? "rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-700"
    : "rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-start gap-2";
  return <div className={cls}>{kind === "err" && <AlertCircle size={13} className="mt-0.5 flex-shrink-0" />}<span>{children}</span></div>;
}

function ModalFooter({ onClose, saving, submitLabel }: { onClose: () => void; saving: boolean; submitLabel: string }) {
  return (
    <div className="pt-2 flex justify-end gap-2">
      <button type="button" onClick={onClose} className="px-3 py-2 text-sm rounded-lg border border-border text-muted-foreground hover:text-foreground">Cancel</button>
      <button disabled={saving} type="submit" className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-70" style={{ background: "#C8900A" }}>
        {saving ? "Saving…" : submitLabel}
      </button>
    </div>
  );
}
