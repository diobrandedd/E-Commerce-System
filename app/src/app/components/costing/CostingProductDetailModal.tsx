import { useEffect, useState } from "react";
import { History, Loader2, Pencil, Plus, Trash2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { Button } from "@/app/components/ui/button";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { CostBadge, peso } from "@/app/lib/costingDisplay";
import {
  previewParcelCost,
  type ParcelCostPreview,
  type ParcelProductAddon,
} from "@/app/lib/parcelPackCosting";
import { costingCostBand } from "@/app/components/costing/costingParcelDisplay";
import { CostingMaterialLinesTable } from "@/app/components/costing/CostingMaterialLinesTable";
import { CostingOrderCalculator } from "@/app/components/costing/CostingOrderCalculator";
import { AppErrorBanner, AppSectionEnter, AppStepSwitch } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

export interface CostingProductSummary {
  id: number;
  name: string;
  dept_name: string;
  size: string | null;
  variant: string | null;
  selling_price: string;
  baseline_cost: string;
  current_cost: string;
  parcel_addon_typical?: number | null;
  landed_typical?: number | null;
  parcel_addon?: ParcelProductAddon | null;
}

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

interface ProductDetail {
  product: {
    id: number;
    dept_name: string;
    name: string;
    selling_price: number;
    baseline_cost: number;
    current_cost: number;
  };
  formula_rows: FormulaRow[];
  cost_history: Array<{ old_cost: string; new_cost: string; recalculated_at: string }>;
  stock_history: Array<{ stock_name: string; old_price: string; new_price: string; changed_at: string }>;
  show_pricing: boolean;
}

type DetailTab = "cogs" | "packaging" | "calculator";

const fmtDate = (s: string) => {
  const d = new Date(s.replace(" ", "T"));
  return Number.isNaN(d.getTime()) ? s : d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

export function CostingProductDetailModal({
  product,
  parcelReady,
  onClose,
  onAddFormula,
  onEditSellingPrice,
  onUpdated,
}: {
  product: CostingProductSummary;
  parcelReady: boolean;
  onClose: () => void;
  onAddFormula: () => void;
  onEditSellingPrice?: () => void;
  onUpdated: (detail: ProductDetail, message: string) => void;
}) {
  const [tab, setTab] = useState<DetailTab>("cogs");
  const [detail, setDetail] = useState<ProductDetail | null>(null);
  const [packPreview, setPackPreview] = useState<ParcelCostPreview | null>(null);
  const [loading, setLoading] = useState(true);
  const [packLoading, setPackLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busyRow, setBusyRow] = useState<number | null>(null);

  const addon = product.parcel_addon;
  const cogs = parseFloat(product.current_cost);
  const landed = product.landed_typical ?? null;
  const selling = parseFloat(product.selling_price);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
    void apiGet<ProductDetail>("costing.php", "product_view", { product_id: product.id })
      .then(d => {
        if (!cancelled) setDetail(d);
      })
      .catch(e => {
        if (!cancelled) setError(e instanceof ApiError ? e.message : "Could not load product.");
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [product.id]);

  useEffect(() => {
    if (!parcelReady || !addon?.tier_key || tab !== "packaging") return;
    let cancelled = false;
    setPackLoading(true);
    void previewParcelCost({ tier_key: addon.tier_key })
      .then(p => {
        if (!cancelled) setPackPreview(p);
      })
      .catch(() => {
        if (!cancelled) setPackPreview(null);
      })
      .finally(() => {
        if (!cancelled) setPackLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, [addon?.tier_key, parcelReady, tab]);

  const deleteRow = async (formulaId: number) => {
    if (!confirm("Delete this formula line?")) return;
    setBusyRow(formulaId);
    try {
      const result = await apiPost<ProductDetail>("costing.php", "formula_delete", {
        product_id: product.id,
        formula_id: formulaId,
      });
      setDetail(result);
      onUpdated(result, "Formula row deleted.");
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Delete failed.");
    } finally {
      setBusyRow(null);
    }
  };

  const tabs: { id: DetailTab; label: string }[] = [
    { id: "cogs", label: "Manufacturing (COGS)" },
    ...(parcelReady
      ? [
          { id: "packaging" as const, label: "Reference packaging" },
          { id: "calculator" as const, label: "Try an order" },
        ]
      : []),
  ];

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-3xl w-full max-h-[92vh] flex flex-col">
      <AppModalHeader
        title={product.name}
        subtitle={`${product.dept_name}${product.size ? ` · ${product.size}` : ""}${product.variant ? ` · ${product.variant}` : ""}`}
        onClose={onClose}
      />
      <AppModalBody className="overflow-y-auto space-y-4">
        {error && <AppErrorBanner message={error} />}

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
          <div className="rounded-lg border border-border bg-secondary/20 px-2 py-2">
            <p className="text-[10px] uppercase text-muted-foreground">COGS / unit</p>
            <p className="font-bold tabular-nums">{peso(cogs)}</p>
          </div>
          {parcelReady && (
            <div className="rounded-lg border border-border bg-secondary/20 px-2 py-2">
              <p className="text-[10px] uppercase text-muted-foreground">Ref. pack</p>
              <p className="font-bold tabular-nums">
                {product.parcel_addon_typical != null ? peso(product.parcel_addon_typical) : "—"}
              </p>
            </div>
          )}
          {parcelReady && landed !== null && (
            <div className="rounded-lg border border-border bg-[#C8900A]/[0.08] px-2 py-2">
              <p className="text-[10px] uppercase text-muted-foreground">Landed (ref.)</p>
              <p className="font-bold tabular-nums">{peso(landed)}</p>
            </div>
          )}
          <div className="rounded-lg border border-border bg-secondary/20 px-2 py-2">
            <p className="text-[10px] uppercase text-muted-foreground">Selling</p>
            <p className="font-bold tabular-nums">{peso(selling)}</p>
          </div>
        </div>

        <div className="flex rounded-lg border border-border p-0.5 bg-secondary/30 flex-wrap">
          {tabs.map(t => (
            <button
              key={t.id}
              type="button"
              onClick={() => setTab(t.id)}
              className={cn(
                "flex-1 min-w-[7rem] text-xs font-semibold px-2 py-2 rounded-md transition-colors",
                tab === t.id ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground",
              )}
            >
              {t.label}
            </button>
          ))}
        </div>

        {loading ? (
          <div className="flex justify-center py-10 text-muted-foreground gap-2">
            <Loader2 className="animate-spin" size={18} /> Loading…
          </div>
        ) : (
          <AppStepSwitch stepKey={tab}>
            {tab === "cogs" && detail && (
              <AppSectionEnter className="space-y-3">
                {detail.show_pricing && onEditSellingPrice && (
                  <div className="flex justify-end">
                    <Button type="button" variant="outline" size="sm" onClick={onEditSellingPrice}>
                      <Pencil size={12} /> Edit selling price
                    </Button>
                  </div>
                )}
                <div className="overflow-x-auto rounded-lg border border-border">
                  <table className="w-full text-xs sm:text-sm min-w-[32rem]">
                    <thead>
                      <tr className="border-b border-border bg-secondary/40">
                        {["Stock", "Qty", "Curr ₱", "Line ₱", ""].map(h => (
                          <th
                            key={h || "a"}
                            className="text-left text-[10px] font-semibold text-muted-foreground uppercase px-3 py-2"
                          >
                            {h || "Act"}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {detail.formula_rows.length === 0 && (
                        <tr>
                          <td colSpan={5} className="px-4 py-6 text-center text-muted-foreground">
                            No formula lines yet.
                          </td>
                        </tr>
                      )}
                      {detail.formula_rows.map(f => (
                        <tr key={f.id} className="border-b border-border last:border-0">
                          <td className="px-3 py-2 font-medium">{f.stock_name}</td>
                          <td className="px-3 py-2 text-muted-foreground tabular-nums">
                            {f.qty_per_unit} {f.unit}
                          </td>
                          <td className="px-3 py-2 tabular-nums">{peso(f.current_price)}</td>
                          <td className="px-3 py-2 font-semibold tabular-nums">{peso(f.current_line_cost)}</td>
                          <td className="px-3 py-2">
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              className="text-red-600"
                              disabled={busyRow === f.id}
                              onClick={() => void deleteRow(f.id)}
                            >
                              <Trash2 size={11} />
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot>
                      <tr className="bg-secondary/30 font-bold">
                        <td colSpan={3} className="px-3 py-2">
                          Total per unit
                        </td>
                        <td className="px-3 py-2 tabular-nums">{peso(detail.product.current_cost)}</td>
                        <td />
                      </tr>
                    </tfoot>
                  </table>
                </div>
                <div className="flex justify-between items-center gap-2">
                  <p className="text-xs text-muted-foreground">
                    Production packaging & raw materials — per manufactured unit.
                  </p>
                  <Button type="button" size="sm" onClick={onAddFormula}>
                    <Plus size={12} /> Add formula line
                  </Button>
                </div>
                {detail.show_pricing && detail.cost_history.length > 0 && (
                  <div className="border-t border-border pt-3">
                    <h4 className="text-xs font-semibold text-muted-foreground uppercase mb-2 flex items-center gap-1">
                      <History size={12} /> Cost history
                    </h4>
                    <ul className="space-y-1 text-xs text-muted-foreground">
                      {detail.cost_history.map((h, i) => (
                        <li key={i}>
                          {fmtDate(h.recalculated_at)}: {peso(h.old_cost)} → {peso(h.new_cost)}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </AppSectionEnter>
            )}

            {tab === "packaging" && (
              <AppSectionEnter className="space-y-3">
                {!addon?.tier_key ? (
                  <p className="text-sm text-muted-foreground">
                    No reference solo-parcel tier for this SKU. Use <strong>Try an order</strong> with a full
                    Item/s string, or check <strong>Parcel packaging</strong> for combo tiers.
                  </p>
                ) : (
                  <>
                    <p className="text-xs text-muted-foreground">
                      Reference scenario: <strong className="text-foreground">{addon.scenario}</strong> — actual pack
                      cost depends on the full order in one box.
                    </p>
                    {packLoading ? (
                      <div className="flex justify-center py-6">
                        <Loader2 className="animate-spin text-muted-foreground" />
                      </div>
                    ) : packPreview?.tiers[0] ? (
                      <>
                        <p className="text-lg font-bold tabular-nums">
                          {costingCostBand(packPreview.cost_min, packPreview.cost_typical, packPreview.cost_max)}
                        </p>
                        <CostingMaterialLinesTable lines={packPreview.tiers[0].lines} />
                      </>
                    ) : (
                      <p className="text-sm text-muted-foreground">Could not load packaging breakdown.</p>
                    )}
                  </>
                )}
              </AppSectionEnter>
            )}

            {tab === "calculator" && parcelReady && (
              <AppSectionEnter>
                <CostingOrderCalculator initialItemsRaw="" />
              </AppSectionEnter>
            )}
          </AppStepSwitch>
        )}
      </AppModalBody>
    </AppModal>
  );
}
