import { useCallback, useEffect, useMemo, useState } from "react";
import { TrendingUp, TrendingDown, Plus, ChevronDown, History, Pencil, AlertCircle, RefreshCcw, Loader2, Trash2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_FORMULA_LITERS_BY_SIZE, HONEY_LITERS_PER_US_GALLON } from "@/app/lib/honeyPricing";
import { splitCostingProducts, groupHoneyByVariant } from "@/app/lib/costingProducts";
import {
  deptDefaultFormulaStockName,
  deptSuggestedFormulaQty,
} from "@/app/lib/deptProducts";
import { CostBadge, peso } from "@/app/lib/costingDisplay";
import { CostingHoneyVariantGroups } from "@/app/components/costing/CostingHoneyVariantGroups";
import { CostingDetailLoading, CostingDetailReveal } from "@/app/components/costing/costingMotion";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { cn } from "@/app/components/ui/utils";

interface StockRow {
  id: number;
  name: string;
  unit: string;
  current_price: string;
  dept_id: number;
  dept_name: string;
  supplier_id?: number | null;
  supplier_name: string | null;
  supplier_code: string | null;
  supplier_display_name?: string | null;
  created_at: string;
  last_entry_at: string | null;
  previous_price?: number | null;
  price_changed_at?: string | null;
  price_direction?: "up" | "down" | "unchanged" | "unknown";
  is_honey_raw?: boolean;
  category?: string | null;
}

interface ProductRow {
  id: number;
  name: string;
  dept_id: number;
  dept_name: string;
  dept_product_key?: string | null;
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

const fmtDate = (s: string | null | undefined) => {
  if (!s) return "—";
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

function StockPriceTrendBadge({ stock }: { stock: StockRow }) {
  const current = parseFloat(stock.current_price);
  const previous = stock.previous_price ?? null;
  const direction = stock.price_direction ?? "unknown";

  if (direction === "unknown" || previous === null || !Number.isFinite(current)) {
    return <span className="text-xs text-muted-foreground">—</span>;
  }
  if (direction === "unchanged") {
    return <span className="text-xs text-muted-foreground">No change</span>;
  }
  const diff = current - previous;
  const pct = previous !== 0 ? ((diff / previous) * 100).toFixed(1) : "0.0";
  const isUp = direction === "up";
  return (
    <span
      className={`inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full ${
        isUp ? "bg-red-100 text-red-600" : "bg-green-100 text-green-700"
      }`}
      title={`Was ${peso(previous)}`}
    >
      {isUp ? <TrendingUp size={11} /> : <TrendingDown size={11} />}
      {isUp ? "+" : ""}
      {pct}%
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
  const [createDefaults, setCreateDefaults] = useState<{ variant: string; size: string } | null>(null);
  const [addFormulaProductId, setAddFormulaProductId] = useState<number | null>(null);
  const [editingSellingPrice, setEditingSellingPrice] = useState<{
    id: number;
    name: string;
    selling_price: string;
  } | null>(null);
  const [supplierFilter, setSupplierFilter] = useState<string>("all");
  const [stockPricesOpen, setStockPricesOpen] = useState(false);
  const [honeyGuideOpen, setHoneyGuideOpen] = useState(false);

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

  const handleSellingPriceSaved = async (productId: number, sellingPrice: number) => {
    setEditingSellingPrice(null);
    flash("Selling price updated.");
    await refresh();
    if (expandedId === productId) {
      await fetchDetail(productId);
    }
    setDetailsById(prev => {
      const cur = prev[productId];
      if (!cur || cur === "loading") return prev;
      return {
        ...prev,
        [productId]: {
          ...cur,
          product: { ...cur.product, selling_price: sellingPrice },
        },
      };
    });
  };

  const stocksForDept = useMemo(() => {
    const map: Record<number, StockRow[]> = {};
    for (const s of stocks) {
      (map[s.dept_id] ||= []).push(s);
    }
    return map;
  }, [stocks]);

  const honeySupplierOptions = useMemo(() => {
    const seen = new Map<number, string>();
    for (const s of stocks) {
      if (s.supplier_id && s.supplier_display_name) {
        seen.set(s.supplier_id, s.supplier_display_name);
      }
    }
    return Array.from(seen.entries()).sort((a, b) => a[1].localeCompare(b[1]));
  }, [stocks]);

  const displayedStocks = useMemo(() => {
    if (supplierFilter === "all") return stocks;
    if (supplierFilter === "other") {
      return stocks.filter(s => !s.supplier_id);
    }
    const sid = Number(supplierFilter);
    return stocks.filter(s => s.supplier_id === sid);
  }, [stocks, supplierFilter]);

  const { honey: honeyProducts, dept: deptProducts, other: otherProducts } = useMemo(
    () => splitCostingProducts(products),
    [products],
  );

  const honeyVariantCount = useMemo(
    () => groupHoneyByVariant(honeyProducts).filter(g => g.products.length > 0).length,
    [honeyProducts],
  );

  const productsSubtitle = useMemo(() => {
    if (honeyProducts.length === 0 && deptProducts.length === 0 && otherProducts.length === 0) {
      return "No products yet.";
    }
    const parts: string[] = [];
    if (honeyProducts.length > 0) {
      parts.push(
        `${honeyProducts.length} honey SKU${honeyProducts.length === 1 ? "" : "s"} in ${honeyVariantCount} variant${honeyVariantCount === 1 ? "" : "s"}`,
      );
    }
    if (deptProducts.length > 0) {
      parts.push(`${deptProducts.length} dept product${deptProducts.length === 1 ? "" : "s"}`);
    }
    if (otherProducts.length > 0) {
      parts.push(`${otherProducts.length} other`);
    }
    return `${parts.join(" · ")} — expand a size to inspect formula and history.`;
  }, [honeyProducts.length, honeyVariantCount, deptProducts.length, otherProducts.length]);

  const renderProductDetail = (p: ProductRow) => {
    const detail = detailsById[p.id];
    if (detail === "loading" || !detail) {
      return <CostingDetailLoading />;
    }
    return (
      <ProductDetailView
        detail={detail}
        stocksInDept={stocksForDept[p.dept_id] ?? []}
        onAddRow={() => setAddFormulaProductId(p.id)}
        onUpdated={(d, msg) => handleFormulaChanged(p.id, d, msg)}
        onEditSellingPrice={() =>
          setEditingSellingPrice({
            id: p.id,
            name: p.name,
            selling_price: String(detail.product.selling_price),
          })
        }
      />
    );
  };

  const openCreateProduct = (defaults?: { variant: string; size: string }) => {
    setCreateDefaults(defaults ?? null);
    setCreatingProduct(true);
  };

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

        {isExec && (
          <div className="rounded-lg border border-border bg-secondary/30 overflow-hidden">
            <button
              type="button"
              onClick={() => setHoneyGuideOpen(v => !v)}
              className="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-secondary/40 transition-colors"
            >
              <ChevronDown
                size={16}
                className={`text-muted-foreground flex-shrink-0 transition-transform ${honeyGuideOpen ? "rotate-180" : ""}`}
              />
              <div className="min-w-0 flex-1">
                <span className="text-sm font-semibold text-foreground">Honey costing setup</span>
                <span className="block text-xs text-muted-foreground mt-0.5">
                  Supplier ₱/gallon → formula liters per bottle size
                </span>
              </div>
            </button>
            {honeyGuideOpen && (
              <div className="px-4 pb-4 pt-0 text-xs text-muted-foreground space-y-2 border-t border-border">
                <p>
                  <strong className="text-foreground">Purchase:</strong> Set ₱/gallon under{" "}
                  <span className="font-semibold">Supplier Master</span> (expand a honey row). Costing stocks sync as ₱/liter (÷ 3.785).
                </p>
                <p>
                  <strong className="text-foreground">Products:</strong> Add each variant × size below; formula raw-honey qty in liters:
                </p>
                <table className="w-full max-w-md text-[11px] border border-border rounded-lg overflow-hidden">
                  <thead>
                    <tr className="bg-secondary/50">
                      <th className="text-left px-2 py-1 font-semibold">Bottle size</th>
                      <th className="text-left px-2 py-1 font-semibold">Raw honey in formula</th>
                    </tr>
                  </thead>
                  <tbody>
                    {[
                      ["250ml", "0.25 L"],
                      ["350ml", "0.35 L"],
                      ["1L (liter)", "1.0 L"],
                      ["Gallon jar", "3.785 L (1 US gal)"],
                    ].map(([size, liters]) => (
                      <tr key={size} className="border-t border-border">
                        <td className="px-2 py-1">{size}</td>
                        <td className="px-2 py-1 font-mono">{liters}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {/* Stock prices — collapsible */}
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-border">
            <button
              type="button"
              onClick={() => setStockPricesOpen(v => !v)}
              className="flex items-center gap-2 min-w-0 flex-1 text-left hover:opacity-80"
            >
              <ChevronDown
                size={18}
                className={`text-[#C8900A] flex-shrink-0 transition-transform ${stockPricesOpen ? "rotate-180" : ""}`}
              />
              <div className="min-w-0">
                <h3 className="text-sm font-semibold text-foreground">Stock prices</h3>
                <p className="text-xs text-muted-foreground truncate">
                  {stockPricesOpen
                    ? `${displayedStocks.length} shown — from Supplier Master (honey) or editable materials`
                    : `${stocks.length} stocks — click to view supplier-linked prices`}
                </p>
              </div>
            </button>
            {stockPricesOpen && honeySupplierOptions.length > 0 && (
              <label
                className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold shrink-0"
                onClick={e => e.stopPropagation()}
              >
                Supplier
                <select
                  value={supplierFilter}
                  onChange={e => setSupplierFilter(e.target.value)}
                  className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card min-w-[140px]"
                >
                  <option value="all">All suppliers</option>
                  {honeySupplierOptions.map(([id, name]) => (
                    <option key={id} value={String(id)}>{name}</option>
                  ))}
                  <option value="other">Other (no supplier link)</option>
                </select>
              </label>
            )}
          </div>
          {stockPricesOpen && (
          <div className="overflow-x-auto max-h-[min(280px,40vh)] overflow-y-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Stock", "Department", "Unit", "Current price", "Change", "Last changed", "Source", isExec ? "Actions" : ""].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={isExec ? 8 : 7} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} />Loading…</td></tr>
                )}
                {!loading && displayedStocks.length === 0 && (
                  <tr><td colSpan={isExec ? 8 : 7} className="px-6 py-8 text-center text-sm text-muted-foreground">No stocks match this filter.</td></tr>
                )}
                {displayedStocks.map((s, i) => (
                  <tr key={s.id} className={`border-b border-border last:border-0 hover:bg-secondary/20 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3 font-medium text-foreground">
                      {s.name}
                      {s.is_honey_raw && s.supplier_display_name && (
                        <span className="block text-[10px] font-normal text-muted-foreground mt-0.5">
                          {s.supplier_display_name}
                        </span>
                      )}
                    </td>
                    <td className="px-5 py-3 text-muted-foreground">{s.dept_name}</td>
                    <td className="px-5 py-3 text-muted-foreground">{s.unit}</td>
                    <td className="px-5 py-3 font-semibold text-foreground">{peso(s.current_price)}</td>
                    <td className="px-5 py-3">
                      <StockPriceTrendBadge stock={s} />
                    </td>
                    <td className="px-5 py-3 text-xs text-muted-foreground whitespace-nowrap">
                      {fmtDate(s.price_changed_at ?? s.last_entry_at)}
                    </td>
                    <td className="px-5 py-3 text-xs font-mono text-muted-foreground max-w-[140px] truncate" title={s.supplier_code ?? undefined}>
                      {s.supplier_code ?? (s.supplier_name ?? "—")}
                    </td>
                    {isExec && (
                      <td className="px-5 py-3">
                        {s.is_honey_raw ? (
                          <span className="text-xs text-muted-foreground">Read-only</span>
                        ) : (
                          <button onClick={() => setEditingStock(s)} className="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A] transition-colors">
                            <Pencil size={12} /> Edit
                          </button>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          )}
        </div>

        {/* Products */}
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-foreground">Products</h3>
            <p className="text-xs text-muted-foreground">{productsSubtitle}</p>
          </div>
          <button
            onClick={() => openCreateProduct()}
            className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white"
            style={{ background: "#C8900A" }}
          >
            <Plus size={15} /> New Product
          </button>
        </div>

        <CostingHoneyVariantGroups
          honeyProducts={honeyProducts}
          isExec={isExec}
          expandedId={expandedId}
          onToggleProduct={toggleProduct}
          renderDetail={renderProductDetail}
          onRequestCreate={(variant, size) => openCreateProduct({ variant, size })}
        />

        {deptProducts.length > 0 && (
          <>
            <h4 className="text-sm font-semibold text-foreground pt-2">Dept products (PT / BRC / Cacao)</h4>
            {deptProducts.map(p => {
              const isExpanded = expandedId === p.id;
              const current = parseFloat(p.current_cost);
              const baseline = parseFloat(p.baseline_cost);
              const selling = parseFloat(p.selling_price);
              const margin = selling - current;
              const marginPct = selling > 0 ? ((margin / selling) * 100).toFixed(1) : "0.0";
              return (
                <div
                  key={p.id}
                  className={cn(
                    "bg-card rounded-xl border overflow-hidden transition-all duration-200",
                    isExpanded ? "border-[#C8900A]/35 shadow-md" : "border-border",
                  )}
                >
                  <button
                    type="button"
                    onClick={() => toggleProduct(p.id)}
                    className="w-full flex items-center gap-4 px-6 py-5 text-left hover:bg-secondary/30"
                  >
                    <div className="flex-1 min-w-0">
                      <span className="font-semibold text-foreground">{p.name}</span>
                      <span className="ml-2 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{p.dept_name}</span>
                    </div>
                    <div className="text-right">
                      <p className="font-bold text-foreground">{peso(current)}</p>
                      {isExec && <p className="text-xs text-muted-foreground">Sell {peso(selling)} · {marginPct}%</p>}
                    </div>
                  </button>
                  <CostingDetailReveal open={isExpanded} className="border-t border-border bg-secondary/10">
                    {renderProductDetail(p)}
                  </CostingDetailReveal>
                </div>
              );
            })}
          </>
        )}

        {otherProducts.length > 0 && (
          <>
            <h4 className="text-sm font-semibold text-foreground pt-2">Other products</h4>
            {otherProducts.map(p => {
              const isExpanded = expandedId === p.id;
              const current = parseFloat(p.current_cost);
              const baseline = parseFloat(p.baseline_cost);
              const selling = parseFloat(p.selling_price);
              const margin = selling - current;
              const marginPct = selling > 0 ? ((margin / selling) * 100).toFixed(1) : "0.0";
              return (
                <div
                  key={p.id}
                  className={cn(
                    "bg-card rounded-xl border overflow-hidden transition-all duration-200",
                    isExpanded ? "border-[#C8900A]/35 shadow-md" : "border-border",
                  )}
                  style={{ boxShadow: isExpanded ? undefined : "0 1px 4px rgba(0,0,0,0.05)" }}
                >
                  <button
                    type="button"
                    onClick={() => toggleProduct(p.id)}
                    aria-expanded={isExpanded}
                    className={cn(
                      "w-full flex items-center gap-4 px-6 py-5 text-left transition-colors duration-200",
                      isExpanded
                        ? "bg-[#C8900A]/[0.06] hover:bg-[#C8900A]/[0.08]"
                        : "hover:bg-secondary/30",
                    )}
                  >
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <span className="font-semibold text-foreground">{p.name}</span>
                        {p.size && (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                            {p.size}
                          </span>
                        )}
                        {p.variant && (
                          <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                            {p.variant}
                          </span>
                        )}
                        <span className="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                          {p.dept_name}
                        </span>
                        <span className="text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                          {p.formula_count} lines
                        </span>
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
                      <ChevronDown
                        size={16}
                        className={cn(
                          "flex-shrink-0 transition-transform duration-300 ease-out",
                          isExpanded ? "rotate-180 text-[#C8900A]" : "text-muted-foreground",
                        )}
                      />
                    </div>
                  </button>
                  <CostingDetailReveal open={isExpanded} className="border-t border-border bg-secondary/10">
                    {renderProductDetail(p)}
                  </CostingDetailReveal>
                </div>
              );
            })}
          </>
        )}
      </div>

      {/* Modals */}
      {editingStock && isExec && (
        <EditStockModal stock={editingStock} onClose={() => setEditingStock(null)} onSaved={handleStockSaved} />
      )}
      {creatingProduct && (
        <CreateProductModal
          stocks={stocks}
          initialVariant={createDefaults?.variant ?? ""}
          initialSize={createDefaults?.size ?? ""}
          onClose={() => {
            setCreatingProduct(false);
            setCreateDefaults(null);
          }}
          onSaved={handleProductCreated}
        />
      )}
      {addFormulaProductId !== null && (
        <AddFormulaModal
          productId={addFormulaProductId}
          product={products.find(p => p.id === addFormulaProductId) ?? null}
          stocks={stocksForDept[products.find(p => p.id === addFormulaProductId)?.dept_id ?? 0] ?? []}
          onClose={() => setAddFormulaProductId(null)}
          onSaved={(detail) => {
            setAddFormulaProductId(null);
            handleFormulaChanged(addFormulaProductId, detail, "Formula updated.");
          }}
        />
      )}
      {editingSellingPrice && isExec && (
        <EditSellingPriceModal
          product={editingSellingPrice}
          onClose={() => setEditingSellingPrice(null)}
          onSaved={(price) => handleSellingPriceSaved(editingSellingPrice.id, price)}
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
  onEditSellingPrice,
}: {
  detail: ProductDetail;
  stocksInDept: StockRow[];
  onAddRow: () => void;
  onUpdated: (detail: ProductDetail, msg: string) => void;
  onEditSellingPrice?: () => void;
}) {
  const { product, formula_rows, cost_history, stock_history, show_pricing } = detail;
  const [busyRow, setBusyRow] = useState<number | null>(null);
  const margin = product.selling_price - product.current_cost;
  const marginPct =
    product.selling_price > 0 ? ((margin / product.selling_price) * 100).toFixed(1) : "0.0";

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
      {show_pricing && (
        <div className="px-6 py-4 border-b border-border bg-secondary/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div className="flex flex-wrap items-center gap-6">
            <div>
              <p className="text-xs text-muted-foreground uppercase tracking-wide">Selling price</p>
              <p className="text-lg font-bold text-foreground">{peso(product.selling_price)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground uppercase tracking-wide">Production cost</p>
              <p className="text-lg font-bold text-foreground">{peso(product.current_cost)}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground uppercase tracking-wide">Margin</p>
              <p className={`text-lg font-bold ${margin > 0 ? "text-green-600" : "text-red-500"}`}>
                {peso(margin)} <span className="text-sm font-normal">({marginPct}%)</span>
              </p>
            </div>
          </div>
          {onEditSellingPrice && (
            <button
              type="button"
              onClick={onEditSellingPrice}
              className="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:border-[#C8900A] hover:text-[#C8900A] transition-colors self-start sm:self-center"
            >
              <Pencil size={12} /> Edit selling price
            </button>
          )}
        </div>
      )}

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

function EditSellingPriceModal({
  product,
  onClose,
  onSaved,
}: {
  product: { id: number; name: string; selling_price: string };
  onClose: () => void;
  onSaved: (sellingPrice: number) => void;
}) {
  const [price, setPrice] = useState(product.selling_price);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const result = await apiPost<{ selling_price: number }>("costing.php", "product_update_selling_price", {
        product_id: product.id,
        selling_price: price,
      });
      onSaved(result.selling_price);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save selling price.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <ModalShell title={`Edit selling price: ${product.name}`} onClose={onClose}>
      <form onSubmit={submit} className="space-y-3">
        {error && <Banner kind="err">{error}</Banner>}
        <p className="text-xs text-muted-foreground">
          This price is used at the honey POS register. Set to 0 to mark the product as not for sale.
        </p>
        <Field label="Selling price">
          <input
            value={price}
            onChange={e => setPrice(e.target.value)}
            required
            type="number"
            step="0.01"
            min="0"
            className={inputCls}
          />
        </Field>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Save price" />
      </form>
    </ModalShell>
  );
}

function EditStockModal({ stock, onClose, onSaved }: { stock: StockRow; onClose: () => void; onSaved: () => void }) {
  const honeyRaw = !!stock.is_honey_raw;
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
        current_price: honeyRaw ? stock.current_price : price,
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
        {honeyRaw && (
          <p className="text-xs text-muted-foreground rounded-lg border border-border bg-secondary/30 px-3 py-2">
            Price is set from Supplier Master (₱/gallon list). Current: {peso(stock.current_price)}/L.
          </p>
        )}
        <Field label="Stock name"><input value={name} onChange={e => setName(e.target.value)} required maxLength={160} className={inputCls} /></Field>
        <Field label="Unit"><input value={unit} onChange={e => setUnit(e.target.value)} required maxLength={32} className={inputCls} /></Field>
        {!honeyRaw && (
          <Field label="Current price"><input value={price} onChange={e => setPrice(e.target.value)} required type="number" step="0.01" min="0" className={inputCls} /></Field>
        )}
        <div className="grid grid-cols-2 gap-2">
          <Field label="Supplier name"><input value={supplierName} onChange={e => setSupplierName(e.target.value)} maxLength={160} className={inputCls} /></Field>
          <Field label="Supplier code"><input value={supplierCode} onChange={e => setSupplierCode(e.target.value)} maxLength={64} className={inputCls} /></Field>
        </div>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Save & recost" />
      </form>
    </ModalShell>
  );
}

function CreateProductModal({
  stocks,
  onClose,
  onSaved,
  initialVariant = "",
  initialSize = "",
}: {
  stocks: StockRow[];
  onClose: () => void;
  onSaved: () => void;
  initialVariant?: string;
  initialSize?: string;
}) {
  const deptOptions = useMemo(() => {
    const map = new Map<number, string>();
    for (const s of stocks) map.set(s.dept_id, s.dept_name);
    return Array.from(map.entries());
  }, [stocks]);
  const honeyDeptId = useMemo(() => {
    for (const s of stocks) {
      if (s.dept_name.toLowerCase().includes("honey")) return String(s.dept_id);
    }
    return "";
  }, [stocks]);
  const defaultDept =
    initialVariant && initialSize && honeyDeptId ? honeyDeptId : String(deptOptions[0]?.[0] ?? "");
  const [name, setName] = useState(initialVariant && initialSize ? "Honey" : "");
  const [deptId, setDeptId] = useState(defaultDept);
  const [size, setSize] = useState(initialSize);
  const [variant, setVariant] = useState(initialVariant);
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

function honeyRawStockNameForVariant(variant: string | null): string | null {
  if (!variant) return null;
  const v = variant.toLowerCase();
  if (v === "light" || v === "amber" || v === "dark") {
    return `Raw Honey — ${v.charAt(0).toUpperCase()}${v.slice(1)}`;
  }
  return null;
}

function resolveSuggestedFormulaQty(product: ProductRow | null, stock: StockRow | undefined): string {
  if (!product || !stock) return "";
  const deptQty = deptSuggestedFormulaQty(product.dept_product_key, stock.name);
  if (deptQty !== null) return deptQty;
  const honeyStock = honeyRawStockNameForVariant(product.variant ?? null);
  if (honeyStock && stock.name === honeyStock && product.size && HONEY_FORMULA_LITERS_BY_SIZE[product.size] != null) {
    return String(HONEY_FORMULA_LITERS_BY_SIZE[product.size]);
  }
  return "";
}

function pickDefaultFormulaStock(product: ProductRow | null, stocks: StockRow[]): StockRow | undefined {
  if (!product) return stocks[0];
  const deptPreferred = deptDefaultFormulaStockName(product.dept_product_key ?? null);
  if (deptPreferred) {
    const match = stocks.find(s => s.name === deptPreferred);
    if (match) return match;
  }
  const honeyPreferred = honeyRawStockNameForVariant(product.variant ?? null);
  if (honeyPreferred) {
    const match = stocks.find(s => s.name === honeyPreferred);
    if (match) return match;
  }
  return stocks[0];
}

function AddFormulaModal({
  productId,
  product,
  stocks,
  onClose,
  onSaved,
}: {
  productId: number;
  product: ProductRow | null;
  stocks: StockRow[];
  onClose: () => void;
  onSaved: (detail: ProductDetail) => void;
}) {
  const targetStockName = honeyRawStockNameForVariant(product?.variant ?? null);
  const defaultStock = pickDefaultFormulaStock(product, stocks);
  const initialSuggestedQty = resolveSuggestedFormulaQty(product, defaultStock);

  const stockGroups = useMemo(() => {
    const bySupplier = new Map<string, StockRow[]>();
    const packaging: StockRow[] = [];
    const other: StockRow[] = [];
    for (const s of stocks) {
      if (s.category === "packaging") {
        packaging.push(s);
        continue;
      }
      if (s.supplier_id && s.supplier_display_name) {
        const key = s.supplier_display_name;
        if (!bySupplier.has(key)) bySupplier.set(key, []);
        bySupplier.get(key)!.push(s);
      } else {
        other.push(s);
      }
    }
    return { bySupplier, packaging, other };
  }, [stocks]);

  const [stockId, setStockId] = useState(String(defaultStock?.id ?? ""));
  const [qty, setQty] = useState(initialSuggestedQty);
  const [qtyTouched, setQtyTouched] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selectedStock = stocks.find(s => String(s.id) === stockId);
  const deptAutoQty = deptSuggestedFormulaQty(product?.dept_product_key, selectedStock?.name ?? "");
  const isDeptAutoQty = deptAutoQty !== null;

  useEffect(() => {
    if (!selectedStock || qtyTouched) return;
    const next = resolveSuggestedFormulaQty(product, selectedStock);
    if (next !== "") setQty(next);
  }, [stockId, selectedStock, product, qtyTouched]);

  const handleStockChange = (nextId: string) => {
    setStockId(nextId);
    setQtyTouched(false);
    const stock = stocks.find(s => String(s.id) === nextId);
    const nextQty = resolveSuggestedFormulaQty(product, stock);
    if (nextQty !== "") setQty(nextQty);
  };

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
        {product?.dept_product_key && isDeptAutoQty && (
          <p className="text-xs text-muted-foreground">
            Dept BOM qty is set automatically from product rules ({selectedStock?.name}: <strong>{deptAutoQty}</strong> per
            sell unit).
          </p>
        )}
        {product?.variant && targetStockName && !product?.dept_product_key && (
          <p className="text-xs text-muted-foreground">
            For this product, add <span className="font-mono">{targetStockName}</span> from your chosen supplier.
            {qty && product.size && (
              <>
                {" "}
                Suggested qty: <strong>{qty} L</strong> ({product.size}).
                {product.size === "gallon" && (
                  <> Saved as exact <strong>{HONEY_LITERS_PER_US_GALLON}</strong> L (US gallon).</>
                )}
              </>
            )}
          </p>
        )}
        <Field label="Stock">
          <select value={stockId} onChange={e => handleStockChange(e.target.value)} required className={inputCls}>
            <option value="">— Select —</option>
            {stockGroups.packaging.length > 0 && (
              <optgroup label="Packaging materials">
                {stockGroups.packaging.map(s => (
                  <option key={s.id} value={s.id}>
                    {s.name} — {peso(s.current_price)}/{s.unit}
                  </option>
                ))}
              </optgroup>
            )}
            {Array.from(stockGroups.bySupplier.entries()).map(([label, group]) => (
              <optgroup key={label} label={label}>
                {group.map(s => (
                  <option key={s.id} value={s.id}>
                    {s.name} — {peso(s.current_price)}/{s.unit}
                    {s.supplier_code ? ` · ${s.supplier_code}` : ""}
                  </option>
                ))}
              </optgroup>
            ))}
            {stockGroups.other.length > 0 && (
              <optgroup label="Other materials">
                {stockGroups.other.map(s => (
                  <option key={s.id} value={s.id}>
                    {s.name} ({peso(s.current_price)}/{s.unit})
                  </option>
                ))}
              </optgroup>
            )}
          </select>
        </Field>
        <Field label="Quantity per unit">
          <input
            value={qty}
            onChange={e => {
              setQtyTouched(true);
              setQty(e.target.value);
            }}
            readOnly={isDeptAutoQty}
            required
            type="number"
            step="any"
            min="0.00000001"
            className={cn(inputCls, isDeptAutoQty && "bg-secondary/50 text-muted-foreground cursor-default")}
          />
        </Field>
        <ModalFooter onClose={onClose} saving={saving} submitLabel="Add line" />
      </form>
    </ModalShell>
  );
}

// ----------------- shared modal primitives -----------------

const inputCls = "w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-3 py-2.5 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150";

function ModalShell({ title, onClose, children }: { title: string; onClose: () => void; children: React.ReactNode }) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="costing-modal-title">
      <AppModalHeader title={title} titleId="costing-modal-title" onClose={onClose} />
      <AppModalBody className="py-5">{children}</AppModalBody>
    </AppModal>
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
