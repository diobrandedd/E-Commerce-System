import { useCallback, useEffect, useMemo, useState } from "react";
import { Plus, History, Pencil, AlertCircle, RefreshCcw, Trash2, BookOpen } from "lucide-react";
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
import { CostingGuideModal } from "@/app/components/costing/CostingGuideModal";
import { ParcelPackLandedIntro, ParcelPackTiersModal } from "@/app/components/costing/ParcelPackTiersModal";
import { CostingProductMetrics, CostingRowColumnHeaders } from "@/app/components/costing/CostingProductMetrics";
import {
  CostingParcelProductMetrics,
  CostingParcelRowColumnHeaders,
  costingParcelRowGridClass,
} from "@/app/components/costing/CostingParcelMetrics";
import { costingRowGridClass } from "@/app/components/costing/costingRowLayout";
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
  parcel_addon_typical?: number | null;
  landed_typical?: number | null;
  margin_typical?: number | null;
  parcel_addon?: { scenario: string; cost_typical: number } | null;
}

type CostingViewMode = "product" | "parcel";

interface FormulaRow {
  id: number;
  stock_id: number | null;
  is_custom?: boolean;
  is_pool_packaging?: boolean;
  pool_yield_qty?: number | null;
  pool_yield_unit?: string | null;
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

export function CostingPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";

  const [stocks, setStocks] = useState<StockRow[]>([]);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [detailsById, setDetailsById] = useState<Record<number, ProductDetail | "loading">>({});
  const [guideOpen, setGuideOpen] = useState(false);
  const [viewMode, setViewMode] = useState<CostingViewMode>("product");
  const [parcelReady, setParcelReady] = useState(false);
  const [parcelTiersModalOpen, setParcelTiersModalOpen] = useState(false);
  const [creatingProduct, setCreatingProduct] = useState(false);
  const [createDefaults, setCreateDefaults] = useState<{ variant: string; size: string } | null>(null);
  const [addFormulaProductId, setAddFormulaProductId] = useState<number | null>(null);
  const [editingSellingPrice, setEditingSellingPrice] = useState<{
    id: number;
    name: string;
    selling_price: string;
  } | null>(null);
  const refresh = useCallback(async () => {
    setError(null);
    try {
      const includeParcel = viewMode === "parcel";
      const [stocksRes, productsRes] = await Promise.all([
        apiGet<{ stocks: StockRow[] }>("costing.php", "stocks_list"),
        apiGet<{ products: ProductRow[]; parcel_ready?: boolean }>("costing.php", "products_list", includeParcel ? { parcel: "1" } : undefined),
      ]);
      setStocks(stocksRes.stocks);
      setProducts(productsRes.products);
      setParcelReady(Boolean(productsRes.parcel_ready));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load costing data.");
    }
  }, [viewMode]);

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
    if (viewMode === "parcel" && parcelReady) {
      return "Typical landed cost per SKU — manufacturing plus reference parcel packaging. Open tier rules to edit materials or try a real order.";
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
  }, [honeyProducts.length, honeyVariantCount, deptProducts.length, otherProducts.length, viewMode, parcelReady]);

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
    <PageLayout contentWidth="wide" title="Product Costing" subtitle={viewMode === "product" ? "Formula builder and live cost tracking" : "Landed cost per product (manufacturing + packaging)"} actions={
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => setGuideOpen(true)}
          className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
        >
          <BookOpen size={13} /> Guide
        </button>
        <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
          <RefreshCcw size={13} /> Refresh
        </button>
      </div>
    }>
      <div className="flex flex-col gap-5 min-w-0 max-w-full">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        {notice && (
          <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{notice}</div>
        )}

        <div className="flex rounded-lg border border-border p-0.5 bg-secondary/30 max-w-md">
          {(["product", "parcel"] as const).map(m => (
            <button
              key={m}
              type="button"
              onClick={() => setViewMode(m)}
              className={cn(
                "flex-1 text-xs font-semibold px-3 py-2 rounded-md transition-colors",
                viewMode === m ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground",
              )}
            >
              {m === "product" ? "Product COGS" : "Parcel packaging"}
            </button>
          ))}
        </div>

        {viewMode === "parcel" && parcelReady && (
          <ParcelPackLandedIntro onOpenTiers={() => setParcelTiersModalOpen(true)} />
        )}

        {/* Products — header fixed, list scrolls inside viewport */}
        <div className="flex flex-col min-h-0 min-w-0">
          <div className="flex flex-wrap items-center justify-between gap-3 shrink-0">
            <div className="min-w-0">
              <h3 className="text-sm font-semibold text-foreground">
                {viewMode === "parcel" ? "Landed cost by product" : "Products"}
              </h3>
              <p className="text-xs text-muted-foreground">{productsSubtitle}</p>
            </div>
            {viewMode === "product" && (
              <button
                onClick={() => openCreateProduct()}
                className="flex items-center gap-2 text-sm font-semibold px-3 py-2 rounded-lg text-white shrink-0"
                style={{ background: "#C8900A" }}
              >
                <Plus size={15} /> New Product
              </button>
            )}
            {viewMode === "parcel" && parcelReady && (
              <button
                type="button"
                onClick={() => setParcelTiersModalOpen(true)}
                className="flex items-center gap-2 text-sm font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/40 shrink-0"
              >
                Tier rules
              </button>
            )}
          </div>

          <div className="mt-3 min-h-0 max-h-[calc(100dvh-9rem)] overflow-y-auto overscroll-contain pr-1 space-y-3">
            <CostingHoneyVariantGroups
              honeyProducts={honeyProducts}
              isExec={isExec}
              expandedId={expandedId}
              onToggleProduct={toggleProduct}
              renderDetail={renderProductDetail}
              onRequestCreate={(variant, size) => openCreateProduct({ variant, size })}
              parcelMode={viewMode === "parcel" && parcelReady}
            />

            {deptProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border bg-[#C8900A]/[0.04]">
                  <h4 className="text-sm font-semibold text-foreground">Dept products (PT / BRC / Cacao)</h4>
                </div>
                {viewMode === "parcel" && parcelReady ? (
                  <CostingParcelRowColumnHeaders isExec={isExec} labelCol="Product" />
                ) : (
                  <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                )}
                {deptProducts.map(p => {
                  const isExpanded = expandedId === p.id;
                  const current = parseFloat(p.current_cost);
                  const baseline = parseFloat(p.baseline_cost);
                  const selling = parseFloat(p.selling_price);
                  const parcelAddon = p.parcel_addon_typical ?? null;
                  const landed = p.landed_typical ?? null;
                  return (
                    <div
                      key={p.id}
                      className={cn(
                        "border-t border-border transition-colors duration-200",
                        isExpanded && "bg-[#C8900A]/[0.04]",
                      )}
                    >
                      <button
                        type="button"
                        onClick={() => toggleProduct(p.id)}
                        aria-expanded={isExpanded}
                        className={cn(
                          "w-full px-4 py-2.5 text-left transition-colors duration-200",
                          isExpanded
                            ? "bg-[#C8900A]/[0.06] hover:bg-[#C8900A]/[0.08]"
                            : "hover:bg-secondary/30",
                        )}
                      >
                        <div className={viewMode === "parcel" && parcelReady ? costingParcelRowGridClass : costingRowGridClass}>
                          <div className="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span className="font-semibold text-foreground text-sm">{p.name}</span>
                            <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">{p.dept_name}</span>
                            {p.formula_count > 0 && (
                              <span className="text-[10px] text-muted-foreground">{p.formula_count} lines</span>
                            )}
                          </div>
                          {viewMode === "parcel" && parcelReady ? (
                            <CostingParcelProductMetrics
                              cogs={current}
                              parcelAddon={parcelAddon}
                              landed={landed}
                              selling={selling}
                              baseline={baseline}
                              isExec={isExec}
                              chevron={{ expanded: isExpanded }}
                            />
                          ) : (
                            <CostingProductMetrics
                              current={current}
                              selling={selling}
                              baseline={baseline}
                              isExec={isExec}
                              chevron={{ expanded: isExpanded }}
                            />
                          )}
                        </div>
                      </button>
                      <CostingDetailReveal open={isExpanded} className="border-t border-border bg-secondary/10">
                        {renderProductDetail(p)}
                      </CostingDetailReveal>
                    </div>
                  );
                })}
              </div>
            )}

            {otherProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border">
                  <h4 className="text-sm font-semibold text-foreground">Other products</h4>
                </div>
                <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                {otherProducts.map(p => {
                  const isExpanded = expandedId === p.id;
                  const current = parseFloat(p.current_cost);
                  const baseline = parseFloat(p.baseline_cost);
                  const selling = parseFloat(p.selling_price);
                  return (
                    <div
                      key={p.id}
                      className={cn(
                        "border-t border-border transition-colors duration-200",
                        isExpanded && "bg-[#C8900A]/[0.04]",
                      )}
                    >
                      <button
                        type="button"
                        onClick={() => toggleProduct(p.id)}
                        aria-expanded={isExpanded}
                        className={cn(
                          "w-full px-4 py-2.5 text-left transition-colors duration-200",
                          isExpanded
                            ? "bg-[#C8900A]/[0.06] hover:bg-[#C8900A]/[0.08]"
                            : "hover:bg-secondary/30",
                        )}
                      >
                        <div className={costingRowGridClass}>
                          <div className="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span className="font-semibold text-foreground text-sm">{p.name}</span>
                            {p.size && (
                              <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground">{p.size}</span>
                            )}
                            {p.variant && (
                              <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground">{p.variant}</span>
                            )}
                            <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">{p.dept_name}</span>
                            <span className="text-[10px] text-muted-foreground">{p.formula_count} lines</span>
                          </div>
                          <CostingProductMetrics
                            current={current}
                            selling={selling}
                            baseline={baseline}
                            isExec={isExec}
                            chevron={{ expanded: isExpanded }}
                          />
                        </div>
                      </button>
                      <CostingDetailReveal open={isExpanded} className="border-t border-border bg-secondary/10">
                        {renderProductDetail(p)}
                      </CostingDetailReveal>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Modals */}
      <CostingGuideModal open={guideOpen} onClose={() => setGuideOpen(false)} />
      <ParcelPackTiersModal
        open={parcelTiersModalOpen}
        onClose={() => setParcelTiersModalOpen(false)}
        onNotice={flash}
        onTiersChanged={refresh}
      />
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
        <div className="px-4 py-3 border-b border-border bg-secondary/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div className="flex flex-wrap items-center gap-4">
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
        <table className="w-full text-xs sm:text-sm min-w-[36rem]">
          <thead>
            <tr className="border-b border-border bg-secondary/40">
              {["Stock", "Qty", "Base ₱", "Curr ₱", "Line ₱", "Δ", ""].map(h => (
                <th key={h || "act"} className="text-left text-[10px] font-semibold text-muted-foreground uppercase tracking-wide px-3 py-2 first:pl-4 whitespace-nowrap">{h || "Act"}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {formula_rows.length === 0 && (
              <tr><td colSpan={7} className="px-4 py-5 text-center text-sm text-muted-foreground">No formula lines yet. Add one to start costing.</td></tr>
            )}
            {formula_rows.map(f => (
              <tr key={f.id} className="border-b border-border last:border-0 hover:bg-secondary/20 transition-colors">
                <td className="px-4 py-2 font-medium text-foreground max-w-[9rem] truncate" title={f.stock_name}>
                  {f.stock_name}
                  {f.is_custom && (
                    <span className="ml-1.5 text-[10px] px-1 py-0.5 rounded-full bg-secondary text-muted-foreground">custom</span>
                  )}
                  {f.is_pool_packaging && (
                    <span className="ml-1.5 text-[10px] px-1 py-0.5 rounded-full bg-secondary text-muted-foreground">pool</span>
                  )}
                </td>
                <td className="px-3 py-2 text-muted-foreground whitespace-nowrap tabular-nums">
                  {f.is_custom
                    ? `${f.qty_per_unit} × ${peso(f.current_price)}`
                    : f.is_pool_packaging && f.pool_yield_qty
                      ? `${f.qty_per_unit} ${f.pool_yield_unit ?? f.unit} ÷ ${f.pool_yield_qty.toLocaleString()}`
                      : `${f.qty_per_unit} ${f.unit}`}
                </td>
                <td className="px-3 py-2 text-muted-foreground whitespace-nowrap tabular-nums">{peso(f.price_at_creation)}</td>
                <td className="px-3 py-2 font-medium text-foreground whitespace-nowrap tabular-nums">{peso(f.current_price)}</td>
                <td className="px-3 py-2 font-semibold text-foreground whitespace-nowrap tabular-nums">{peso(f.current_line_cost)}</td>
                <td className="px-3 py-2 whitespace-nowrap"><CostBadge current={f.current_line_cost} baseline={f.baseline_line_cost} /></td>
                <td className="px-3 py-2 whitespace-nowrap">
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
              <td className="px-4 py-2 font-bold text-foreground" colSpan={4}>Total per unit</td>
              <td className="px-3 py-2 font-bold text-foreground tabular-nums">{peso(product.current_cost)}</td>
              <td className="px-3 py-2"><CostBadge current={product.current_cost} baseline={product.baseline_cost} /></td>
              <td className="px-3 py-2"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div className="px-4 py-2.5 border-t border-border flex flex-wrap justify-between items-center gap-2">
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

type FormulaLineMode = "stock" | "custom";

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
  const [mode, setMode] = useState<FormulaLineMode>("stock");
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
  const [customLabel, setCustomLabel] = useState("");
  const [customPrice, setCustomPrice] = useState("");
  const [customQty, setCustomQty] = useState("1");
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
      const payload =
        mode === "custom"
          ? {
              product_id: productId,
              is_custom: true,
              line_label: customLabel.trim(),
              unit_price: customPrice,
              qty_per_unit: customQty,
            }
          : {
              product_id: productId,
              stock_id: Number(stockId),
              qty_per_unit: qty,
            };
      const detail = await apiPost<ProductDetail>("costing.php", "formula_add", payload);
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
        <div className="flex rounded-lg border border-border p-0.5 bg-secondary/30">
          {(["stock", "custom"] as const).map(m => (
            <button
              key={m}
              type="button"
              onClick={() => setMode(m)}
              className={cn(
                "flex-1 text-xs font-semibold px-3 py-2 rounded-md transition-colors",
                mode === m ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground",
              )}
            >
              {m === "stock" ? "From stock" : "Custom cost"}
            </button>
          ))}
        </div>
        {mode === "custom" ? (
          <>
            <p className="text-xs text-muted-foreground">
              Add overhead that is not tied to inventory — e.g. labor, shipping, utilities. Price is fixed per sellable unit unless you edit the line later.
            </p>
            <Field label="What is it?">
              <input
                value={customLabel}
                onChange={e => setCustomLabel(e.target.value)}
                required
                maxLength={160}
                placeholder="e.g. Labor, Shipping fee"
                className={inputCls}
              />
            </Field>
            <Field label="₱ per unit">
              <input
                value={customPrice}
                onChange={e => setCustomPrice(e.target.value)}
                required
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                className={inputCls}
              />
            </Field>
            <Field label="Quantity per sellable unit">
              <input
                value={customQty}
                onChange={e => setCustomQty(e.target.value)}
                required
                type="number"
                step="any"
                min="0.00000001"
                className={inputCls}
              />
              <p className="text-[10px] text-muted-foreground mt-1">Usually 1 for flat fees (labor per jar, shipping per pouch).</p>
            </Field>
          </>
        ) : (
          <>
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
        {selectedStock?.category === "packaging" && (
          <p className="text-xs text-muted-foreground">
            Packaging unit cost comes from weighted-average stock-in on Packaging Materials. Quantity here is{" "}
            <strong>per sellable unit</strong> (e.g. 1 bottle per 250ml jar), not warehouse on-hand.
          </p>
        )}
        <Field label="Stock">
          <select value={stockId} onChange={e => handleStockChange(e.target.value)} required={mode === "stock"} className={inputCls}>
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
          </>
        )}
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
