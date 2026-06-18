import {
  HONEY_FORMULA_LITERS_BY_SIZE,
  type HoneyPricingVariant,
} from "@/app/lib/honeyPricing";

export const HONEY_COSTING_VARIANTS: HoneyPricingVariant[] = ["light", "amber", "dark"];

export const HONEY_COSTING_SIZES = Object.keys(HONEY_FORMULA_LITERS_BY_SIZE) as Array<
  keyof typeof HONEY_FORMULA_LITERS_BY_SIZE
>;

const VARIANT_SET = new Set<string>(HONEY_COSTING_VARIANTS);
const SIZE_SET = new Set<string>(HONEY_COSTING_SIZES);

export interface CostingProductLike {
  id: number;
  name: string;
  dept_product_key?: string | null;
  dept_id: number;
  dept_name: string;
  size: string | null;
  variant: string | null;
  selling_price: string;
  baseline_cost: string;
  current_cost: string;
  formula_count: number;
}

export function isHoneySku(p: CostingProductLike): boolean {
  const v = (p.variant ?? "").toLowerCase();
  const s = p.size ?? "";
  return VARIANT_SET.has(v) && SIZE_SET.has(s);
}

export function variantLabel(variant: string): string {
  return variant.charAt(0).toUpperCase() + variant.slice(1);
}

export function isDeptCatalogProduct(p: CostingProductLike): boolean {
  const key = p.dept_product_key ?? "";
  return key === "pt" || key === "brc" || key === "jar_cacao";
}

export function splitDeptCatalogProducts<T extends CostingProductLike>(deptProducts: T[]): {
  paragis: T[];
  cacaoBrc: T[];
} {
  const paragis: T[] = [];
  const cacaoBrc: T[] = [];
  for (const p of deptProducts) {
    const key = p.dept_product_key ?? "";
    if (key === "pt") paragis.push(p);
    else if (key === "brc" || key === "jar_cacao") cacaoBrc.push(p);
  }
  return { paragis, cacaoBrc };
}

export function splitCostingProducts<T extends CostingProductLike>(products: T[]): {
  honey: T[];
  dept: T[];
  other: T[];
} {
  const honey: T[] = [];
  const dept: T[] = [];
  const other: T[] = [];
  for (const p of products) {
    if (isHoneySku(p)) honey.push(p);
    else if (isDeptCatalogProduct(p)) dept.push(p);
    else other.push(p);
  }
  return { honey, dept, other };
}

export type HoneyVariantGroup<T extends CostingProductLike> = {
  variant: HoneyPricingVariant;
  label: string;
  bySize: Map<string, T>;
  products: T[];
};

export function groupHoneyByVariant<T extends CostingProductLike>(
  honeyProducts: T[],
): HoneyVariantGroup<T>[] {
  const buckets = new Map<HoneyPricingVariant, Map<string, T>>();
  for (const v of HONEY_COSTING_VARIANTS) {
    buckets.set(v, new Map());
  }
  for (const p of honeyProducts) {
    const v = (p.variant ?? "").toLowerCase() as HoneyPricingVariant;
    const s = p.size ?? "";
    if (!VARIANT_SET.has(v) || !SIZE_SET.has(s)) continue;
    buckets.get(v)!.set(s, p);
  }
  return HONEY_COSTING_VARIANTS.map(variant => {
    const bySize = buckets.get(variant)!;
    const products = HONEY_COSTING_SIZES.map(size => bySize.get(size)).filter(
      (p): p is T => p != null,
    );
    return { variant, label: variantLabel(variant), bySize, products };
  });
}

export function variantGroupStats(
  group: HoneyVariantGroup<CostingProductLike>,
  isExec: boolean,
): {
  sizeCount: number;
  needsFormula: number;
  lowestMarginPct: number | null;
} {
  let needsFormula = 0;
  let lowestMarginPct: number | null = null;
  for (const p of group.products) {
    if (p.formula_count === 0) needsFormula += 1;
    if (!isExec) continue;
    const selling = parseFloat(p.selling_price);
    const current = parseFloat(p.current_cost);
    if (!Number.isFinite(selling) || selling <= 0) continue;
    const pct = ((selling - current) / selling) * 100;
    if (lowestMarginPct === null || pct < lowestMarginPct) lowestMarginPct = pct;
  }
  return {
    sizeCount: group.products.length,
    needsFormula,
    lowestMarginPct,
  };
}
