import { useMemo, useState, type ReactNode } from "react";
import { ChevronDown } from "lucide-react";
import {
  groupHoneyByVariant,
  HONEY_COSTING_SIZES,
  variantGroupStats,
  type CostingProductLike,
  type HoneyVariantGroup,
} from "@/app/lib/costingProducts";
import { CostingProductMetrics, CostingRowColumnHeaders } from "@/app/components/costing/CostingProductMetrics";
import {
  CostingParcelProductMetrics,
  CostingParcelRowColumnHeaders,
  costingParcelHoneyRowGridClass,
} from "@/app/components/costing/CostingParcelMetrics";
import { costingHoneyRowGridClass } from "@/app/components/costing/costingRowLayout";
import type { HoneyPricingVariant } from "@/app/lib/honeyPricing";
import { cn } from "@/app/components/ui/utils";
import {
  CostingCollapsible,
  CostingDetailReveal,
  CostingSectionEnter,
} from "@/app/components/costing/costingMotion";

const SIZE_COLORS: Record<string, string> = {
  "250ml": "#C8900A",
  "350ml": "#2a8a4a",
  liter: "#4a7fc8",
  gallon: "#6b4c2a",
};

function sizeLabel(size: string): string {
  if (size === "liter") return "Liter";
  if (size === "gallon") return "Gallon";
  return size;
}

function HoneySizeRow<T extends CostingProductLike>({
  product,
  isExpanded,
  isExec,
  onToggle,
  detailPanel,
  parcelMode,
}: {
  product: T;
  isExpanded: boolean;
  isExec: boolean;
  onToggle: () => void;
  detailPanel: ReactNode;
  parcelMode?: boolean;
}) {
  const current = parseFloat(product.current_cost);
  const baseline = parseFloat(product.baseline_cost);
  const selling = parseFloat(product.selling_price);
  const size = product.size ?? "";
  const parcelAddon =
    "parcel_addon_typical" in product && product.parcel_addon_typical != null
      ? Number(product.parcel_addon_typical)
      : null;
  const landed =
    "landed_typical" in product && product.landed_typical != null ? Number(product.landed_typical) : null;

  return (
    <div
      className={cn(
        "border-t border-border first:border-t-0 transition-colors duration-200",
        isExpanded && "bg-[#C8900A]/[0.04]",
      )}
    >
      <button
        type="button"
        onClick={onToggle}
        aria-expanded={isExpanded}
        className={cn(
          "w-full px-4 py-2.5 text-left transition-colors duration-200",
          isExpanded
            ? "bg-[#C8900A]/[0.06] hover:bg-[#C8900A]/[0.08]"
            : "hover:bg-secondary/30",
        )}
      >
        <div className={parcelMode ? costingParcelHoneyRowGridClass : costingHoneyRowGridClass}>
          <div
            className={cn(
              "w-1 self-stretch rounded-full flex-shrink-0 min-h-[1.5rem] transition-all duration-200",
              isExpanded && "w-1.5",
            )}
            style={{ background: SIZE_COLORS[size] ?? "#C8900A" }}
            aria-hidden
          />
          <div className="min-w-0 flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span className="font-semibold text-foreground text-sm">{sizeLabel(size)}</span>
            {product.formula_count === 0 && (
              <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                Needs formula
              </span>
            )}
            {product.formula_count > 0 && (
              <span className="text-[10px] text-muted-foreground">{product.formula_count} lines</span>
            )}
          </div>
          {parcelMode ? (
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
        {detailPanel}
      </CostingDetailReveal>
    </div>
  );
}

function VariantSection<T extends CostingProductLike>({
  group,
  isExec,
  expandedId,
  onToggleProduct,
  renderDetail,
  onRequestCreate,
  defaultOpen,
  parcelMode,
}: {
  group: HoneyVariantGroup<T>;
  isExec: boolean;
  expandedId: number | null;
  onToggleProduct: (id: number) => void;
  renderDetail: (product: T) => ReactNode;
  onRequestCreate?: (variant: HoneyPricingVariant, size: string) => void;
  defaultOpen: boolean;
  parcelMode?: boolean;
}) {
  const [sectionOpen, setSectionOpen] = useState(defaultOpen);
  const stats = variantGroupStats(group, isExec);

  return (
    <div
      className={cn(
        "bg-card rounded-xl border overflow-hidden transition-shadow duration-200",
        sectionOpen ? "border-[#C8900A]/35 shadow-md" : "border-border",
      )}
      style={{ boxShadow: sectionOpen ? undefined : "0 1px 4px rgba(0, 0, 0, 0.05)" }}
    >
      <button
        type="button"
        onClick={() => setSectionOpen(o => !o)}
        aria-expanded={sectionOpen}
        className={cn(
          "w-full flex items-center gap-3 px-4 py-3 text-left transition-colors duration-200",
          sectionOpen ? "bg-[#C8900A]/[0.05] hover:bg-[#C8900A]/[0.08]" : "hover:bg-secondary/30",
        )}
      >
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-base font-semibold text-foreground">Honey — {group.label}</span>
            <span className="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">honey</span>
          </div>
          <div className="flex flex-wrap items-center gap-2 mt-1.5">
            <span className="text-xs text-muted-foreground">
              {stats.sizeCount} of {HONEY_COSTING_SIZES.length} sizes
            </span>
            {stats.needsFormula > 0 && (
              <span className="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                {stats.needsFormula} need formula
              </span>
            )}
            {isExec && stats.lowestMarginPct !== null && (
              <span className="text-xs text-muted-foreground">
                Lowest margin:{" "}
                <span
                  className={
                    stats.lowestMarginPct >= 0 ? "font-semibold text-green-600" : "font-semibold text-red-500"
                  }
                >
                  {stats.lowestMarginPct.toFixed(1)}%
                </span>
              </span>
            )}
          </div>
        </div>
        <ChevronDown
          size={18}
          className={cn(
            "flex-shrink-0 transition-transform duration-300 ease-out",
            sectionOpen ? "rotate-180 text-[#C8900A]" : "text-muted-foreground",
          )}
        />
      </button>

      <CostingCollapsible open={sectionOpen} className="border-t border-border">
          {parcelMode ? (
            <CostingParcelRowColumnHeaders isExec={isExec} labelCol="Size" withColorBar />
          ) : (
            <CostingRowColumnHeaders isExec={isExec} labelCol="Size" withColorBar />
          )}
          {HONEY_COSTING_SIZES.map(size => {
            const product = group.bySize.get(size);
            if (!product) {
              return (
                <div
                  key={size}
                  className="border-t border-border first:border-t-0 px-5 py-3 flex items-center justify-between gap-3 bg-secondary/10"
                >
                  <div className="flex items-center gap-2">
                    <div
                      className="w-1 h-8 rounded-full flex-shrink-0 opacity-40"
                      style={{ background: SIZE_COLORS[size] ?? "#ccc" }}
                    />
                    <span className="text-sm text-muted-foreground">{sizeLabel(size)}</span>
                    <span className="text-xs text-muted-foreground">— Not set up</span>
                  </div>
                  {onRequestCreate && (
                    <button
                      type="button"
                      onClick={() => onRequestCreate(group.variant, size)}
                      className="text-xs font-semibold text-[#C8900A] hover:underline"
                    >
                      New Product
                    </button>
                  )}
                </div>
              );
            }
            const isExpanded = expandedId === product.id;
            return (
              <HoneySizeRow
                key={product.id}
                product={product}
                isExpanded={isExpanded}
                isExec={isExec}
                onToggle={() => onToggleProduct(product.id)}
                detailPanel={isExpanded ? renderDetail(product) : null}
                parcelMode={parcelMode}
              />
            );
          })}
      </CostingCollapsible>
    </div>
  );
}

export function CostingHoneyVariantGroups<T extends CostingProductLike>({
  honeyProducts,
  isExec,
  expandedId,
  onToggleProduct,
  renderDetail,
  onRequestCreate,
  parcelMode,
}: {
  honeyProducts: T[];
  isExec: boolean;
  expandedId: number | null;
  onToggleProduct: (id: number) => void;
  renderDetail: (product: T) => ReactNode;
  onRequestCreate?: (variant: HoneyPricingVariant, size: string) => void;
  parcelMode?: boolean;
}) {
  const groups = useMemo(() => groupHoneyByVariant(honeyProducts), [honeyProducts]);

  if (groups.every(g => g.products.length === 0)) {
    return (
      <p className="text-sm text-muted-foreground py-4 text-center">
        No honey products yet. Use New Product with a variant and bottle size.
      </p>
    );
  }

  return (
    <div className="space-y-3">
      {groups.map((group, idx) => (
        <CostingSectionEnter key={group.variant} index={idx}>
          <VariantSection
            group={group}
            isExec={isExec}
            expandedId={expandedId}
            onToggleProduct={onToggleProduct}
            renderDetail={renderDetail}
            onRequestCreate={onRequestCreate}
            defaultOpen={idx === 0}
            parcelMode={parcelMode}
          />
        </CostingSectionEnter>
      ))}
    </div>
  );
}
