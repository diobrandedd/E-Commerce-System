import { useCallback, useEffect, useMemo, useState } from "react";
import { Plus, AlertCircle, RefreshCcw, BookOpen } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_FORMULA_LITERS_BY_SIZE, HONEY_LITERS_PER_US_GALLON } from "@/app/lib/honeyPricing";
import { splitCostingProducts, splitDeptCatalogProducts } from "@/app/lib/costingProducts";
import {
  deptDefaultFormulaStockName,
  deptSuggestedFormulaQty,
} from "@/app/lib/deptProducts";
import { peso } from "@/app/lib/costingDisplay";
import { CostingHoneyVariantGroups } from "@/app/components/costing/CostingHoneyVariantGroups";
import { CostingGuideModal } from "@/app/components/costing/CostingGuideModal";
import { runCostingTour } from "@/app/components/tour/guides/costingTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { ParcelPackTiersModal } from "@/app/components/costing/ParcelPackTiersModal";
import { CostingParcelRecipesPanel } from "@/app/components/costing/CostingParcelRecipesPanel";
import { CostingProductDetailModal, type CostingProductSummary } from "@/app/components/costing/CostingProductDetailModal";
import { CostingProductMetrics, CostingRowColumnHeaders } from "@/app/components/costing/CostingProductMetrics";
import {
  CostingParcelProductMetrics,
  CostingParcelRowColumnHeaders,
  costingParcelRowGridClass,
} from "@/app/components/costing/CostingParcelMetrics";
import { costingRowGridClass } from "@/app/components/costing/costingRowLayout";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
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
  parcel_addon?: { scenario: string; cost_typical: number; tier_key?: string } | null;
}

type CostingHubMode = "products" | "packaging";
type DeptFilter = "all" | "honey" | "paragis" | "cacao_brc" | "other";

const DEPT_FILTER_CHIPS: { id: DeptFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "honey", label: "Honey" },
  { id: "paragis", label: "Paragis Tea" },
  { id: "cacao_brc", label: "BRC & Cacao" },
  { id: "other", label: "Other" },
];

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

export function CostingPage() {
  const { can } = useAuth();
  const isExec = can("honey.pricing.view_cost");

  const [stocks, setStocks] = useState<StockRow[]>([]);
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);
  const [hubMode, setHubMode] = useState<CostingHubMode>("products");
  const [deptFilter, setDeptFilter] = useState<DeptFilter>("all");
  const [parcelReady, setParcelReady] = useState(false);
  const [parcelTiersModalOpen, setParcelTiersModalOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<ProductRow | null>(null);
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
      const [stocksRes, productsRes] = await Promise.all([
        apiGet<{ stocks: StockRow[] }>("costing.php", "stocks_list"),
        apiGet<{ products: ProductRow[]; parcel_ready?: boolean }>("costing.php", "products_list", { parcel: "1" }),
      ]);
      setStocks(stocksRes.stocks);
      setProducts(productsRes.products);
      setParcelReady(Boolean(productsRes.parcel_ready));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load costing data.");
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  const flash = (msg: string) => {
    setNotice(msg);
    setTimeout(() => setNotice(null), 3000);
  };

  const openProduct = (productId: number) => {
    const p = products.find(x => x.id === productId);
    if (p) setSelectedProduct(p);
  };

  const handleProductCreated = async () => {
    setCreatingProduct(false);
    flash("Product created.");
    await refresh();
  };

  const handleFormulaChanged = async (productId: number, _detail: ProductDetail, message: string) => {
    flash(message);
    await refresh();
    if (selectedProduct?.id === productId) {
      const updated = products.find(p => p.id === productId);
      if (updated) setSelectedProduct(updated);
    }
  };

  const handleSellingPriceSaved = async (productId: number, sellingPrice: number) => {
    setEditingSellingPrice(null);
    flash("Selling price updated.");
    await refresh();
    if (selectedProduct?.id === productId) {
      setSelectedProduct(prev => (prev ? { ...prev, selling_price: String(sellingPrice) } : null));
    }
  };

  const stocksForDept = useMemo(() => {
    const map: Record<number, StockRow[]> = {};
    for (const s of stocks) {
      (map[s.dept_id] ||= []).push(s);
    }
    return map;
  }, [stocks]);

  const { honey: honeyProducts, honeyComb: honeyCombProducts, dept: deptProducts, other: otherProducts } = useMemo(
    () => splitCostingProducts(products),
    [products],
  );

  const { paragis: paragisProducts, cacaoBrc: cacaoBrcProducts } = useMemo(
    () => splitDeptCatalogProducts(deptProducts),
    [deptProducts],
  );

  const productsSubtitle = useMemo(() => {
    if (hubMode === "packaging") {
      return "Packaging cost per shipped parcel — click a recipe for material breakdown.";
    }
    if (honeyProducts.length === 0 && honeyCombProducts.length === 0 && deptProducts.length === 0 && otherProducts.length === 0) {
      return "No products yet.";
    }
    return "Click a row for COGS formula, reference packaging, and order calculator.";
  }, [hubMode, honeyProducts.length, honeyCombProducts.length, deptProducts.length, otherProducts.length]);

  const showHoney = deptFilter === "all" || deptFilter === "honey";
  const showParagis = deptFilter === "all" || deptFilter === "paragis";
  const showCacaoBrc = deptFilter === "all" || deptFilter === "cacao_brc";
  const showOther = deptFilter === "all" || deptFilter === "other";
  const parcelTableMode = parcelReady;

  const renderDeptProductRow = (p: ProductRow) => {
    const current = parseFloat(p.current_cost);
    const baseline = parseFloat(p.baseline_cost);
    const selling = parseFloat(p.selling_price);
    const parcelAddon = p.parcel_addon_typical ?? null;
    const landed = p.landed_typical ?? null;
    return (
      <button
        key={p.id}
        type="button"
        onClick={() => openProduct(p.id)}
        className={cn(
          "w-full px-4 py-2.5 text-left border-t border-border transition-colors duration-200",
          appHoverRow,
        )}
      >
        <div className={parcelTableMode ? costingParcelRowGridClass : costingRowGridClass}>
          <div className="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span className="font-semibold text-foreground text-sm">{p.name}</span>
            <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">{p.dept_name}</span>
            {p.formula_count > 0 && (
              <span className="text-[10px] text-muted-foreground">{p.formula_count} lines</span>
            )}
          </div>
          {parcelTableMode ? (
            <CostingParcelProductMetrics
              cogs={current}
              parcelAddon={parcelAddon}
              landed={landed}
              selling={selling}
              baseline={baseline}
              isExec={isExec}
            />
          ) : (
            <CostingProductMetrics
              current={current}
              selling={selling}
              baseline={baseline}
              isExec={isExec}
            />
          )}
        </div>
      </button>
    );
  };

  const openCreateProduct = (defaults?: { variant: string; size: string }) => {
    setCreateDefaults(defaults ?? null);
    setCreatingProduct(true);
  };

  return (
    <PageLayout contentWidth="wide" title="Product Costing" subtitle="Manufacturing COGS, parcel packaging, and landed cost" actions={
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

        <div className="flex rounded-lg border border-border p-0.5 bg-secondary/30 max-w-lg" data-tour="costing-view-toggle">
          {(["products", "packaging"] as const).map(m => (
            <button
              key={m}
              type="button"
              onClick={() => setHubMode(m)}
              className={cn(
                "flex-1 text-xs font-semibold px-3 py-2 rounded-md transition-colors cursor-pointer",
                hubMode === m ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground",
              )}
            >
              {m === "products" ? "Products" : "Parcel packaging"}
            </button>
          ))}
        </div>

        {hubMode === "packaging" ? (
          <CostingParcelRecipesPanel />
        ) : (
        <div className="flex flex-col min-h-0 min-w-0">
          <div className="flex flex-wrap items-center justify-between gap-3 shrink-0" data-tour="costing-products-header">
            <div className="min-w-0">
              <h3 className="text-sm font-semibold text-foreground">Products by department</h3>
              <p className="text-xs text-muted-foreground">{productsSubtitle}</p>
            </div>
            <div className="flex flex-wrap items-center gap-2 shrink-0">
              <button
                type="button"
                onClick={() => setParcelTiersModalOpen(true)}
                className="flex items-center gap-2 text-sm font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/40"
              >
                Edit tier rules
              </button>
              <button
                onClick={() => openCreateProduct()}
                className="flex items-center gap-2 text-sm font-semibold px-3 py-2 rounded-lg text-white"
                style={{ background: "#C8900A" }}
              >
                <Plus size={15} /> New Product
              </button>
            </div>
          </div>

          <div className="flex flex-wrap gap-2 mt-3">
            {DEPT_FILTER_CHIPS.map(chip => (
              <button
                key={chip.id}
                type="button"
                onClick={() => setDeptFilter(chip.id)}
                className={cn(
                  "text-xs font-semibold px-3 py-1.5 rounded-full border transition-colors cursor-pointer",
                  deptFilter === chip.id
                    ? "bg-[#C8900A] text-white border-[#C8900A]"
                    : "border-border text-muted-foreground hover:text-foreground hover:bg-secondary/40",
                )}
              >
                {chip.label}
              </button>
            ))}
          </div>

          <div className="mt-3 min-h-0 max-h-[calc(100dvh-12rem)] overflow-y-auto overscroll-contain pr-1 space-y-3" data-tour="costing-products-list">
            {showHoney && (
            <CostingHoneyVariantGroups
              honeyProducts={honeyProducts}
              isExec={isExec}
              expandedId={null}
              onToggleProduct={() => {}}
              onProductClick={openProduct}
              renderDetail={() => null}
              onRequestCreate={(variant, size) => openCreateProduct({ variant, size })}
              parcelMode={parcelTableMode}
            />
            )}

            {showHoney && honeyCombProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border bg-[#C8900A]/[0.04]">
                  <h4 className="text-sm font-semibold text-foreground">Honey Comb</h4>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    Production packaging (jar w/ cap + sticker) in formula; ship materials in Parcel packaging (Excel row 13).
                  </p>
                </div>
                {parcelTableMode ? (
                  <CostingParcelRowColumnHeaders isExec={isExec} labelCol="Product" />
                ) : (
                  <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                )}
                {honeyCombProducts.map(renderDeptProductRow)}
              </div>
            )}

            {showParagis && paragisProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border bg-[#C8900A]/[0.04]">
                  <h4 className="text-sm font-semibold text-foreground">Paragis Tea</h4>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    Solo PT parcels and combos with 1L / 350ml / 250ml honey — see Parcel packaging.
                  </p>
                </div>
                {parcelTableMode ? (
                  <CostingParcelRowColumnHeaders isExec={isExec} labelCol="Product" />
                ) : (
                  <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                )}
                {paragisProducts.map(renderDeptProductRow)}
              </div>
            )}

            {showCacaoBrc && cacaoBrcProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border bg-[#C8900A]/[0.04]">
                  <h4 className="text-sm font-semibold text-foreground">Black Rice Coffee &amp; Jar Cacao</h4>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    Same department (cacao_brc). Solo jars/pouches and combos with 1L / 350ml / 250ml honey.
                  </p>
                </div>
                {parcelTableMode ? (
                  <CostingParcelRowColumnHeaders isExec={isExec} labelCol="Product" />
                ) : (
                  <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                )}
                {cacaoBrcProducts.map(renderDeptProductRow)}
              </div>
            )}

            {showOther && otherProducts.length > 0 && (
              <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0, 0, 0, 0.05)" }}>
                <div className="px-4 py-3 border-b border-border">
                  <h4 className="text-sm font-semibold text-foreground">Other products</h4>
                </div>
                <CostingRowColumnHeaders isExec={isExec} labelCol="Product" />
                {otherProducts.map(p => {
                  const current = parseFloat(p.current_cost);
                  const baseline = parseFloat(p.baseline_cost);
                  const selling = parseFloat(p.selling_price);
                  const parcelAddon = p.parcel_addon_typical ?? null;
                  const landed = p.landed_typical ?? null;
                  return (
                    <button
                      key={p.id}
                      type="button"
                      onClick={() => openProduct(p.id)}
                      className={cn(
                        "w-full px-4 py-2.5 text-left border-t border-border transition-colors duration-200",
                        appHoverRow,
                      )}
                    >
                      <div className={parcelTableMode ? costingParcelRowGridClass : costingRowGridClass}>
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
                        {parcelTableMode ? (
                          <CostingParcelProductMetrics
                            cogs={current}
                            parcelAddon={parcelAddon}
                            landed={landed}
                            selling={selling}
                            baseline={baseline}
                            isExec={isExec}
                          />
                        ) : (
                          <CostingProductMetrics
                            current={current}
                            selling={selling}
                            baseline={baseline}
                            isExec={isExec}
                          />
                        )}
                      </div>
                    </button>
                  );
                })}
              </div>
            )}
          </div>
        </div>
        )}
      </div>

      {selectedProduct && (
        <CostingProductDetailModal
          key={`${selectedProduct.id}-${selectedProduct.formula_count}-${selectedProduct.current_cost}`}
          product={selectedProduct as CostingProductSummary}
          parcelReady={parcelReady}
          onClose={() => setSelectedProduct(null)}
          onAddFormula={() => {
            setAddFormulaProductId(selectedProduct.id);
          }}
          onEditSellingPrice={
            isExec
              ? () =>
                  setEditingSellingPrice({
                    id: selectedProduct.id,
                    name: selectedProduct.name,
                    selling_price: selectedProduct.selling_price,
                  })
              : undefined
          }
          onUpdated={(detail, msg) => {
            void handleFormulaChanged(selectedProduct.id, detail, msg);
            setSelectedProduct(prev =>
              prev
                ? {
                    ...prev,
                    current_cost: String(detail.product.current_cost),
                    baseline_cost: String(detail.product.baseline_cost),
                    selling_price: String(detail.product.selling_price),
                  }
                : null,
            );
          }}
        />
      )}

      {/* Modals */}
      <CostingGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runCostingTour());
        }}
      />
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
