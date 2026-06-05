import type { VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import type { HoneyProduct, PresetOption } from "@/app/lib/orderRemarksParse";

export interface PackagingStockSnapshot {
  variants: VariantChart[];
}

const LETTER_VARIANT: Record<string, string> = {
  L: "light",
  A: "amber",
  D: "dark",
};

export function formatStockLeft(qty: number): string {
  const n = Math.max(0, Math.floor(qty));
  return `${n.toLocaleString()} left`;
}

function qtyFromSnapshot(snapshot: PackagingStockSnapshot, variant: string, size: string): number {
  const row = snapshot.variants.find(v => v.variant === variant);
  if (!row) return 0;
  const slice = row.sizes.find(s => s.size === size);
  return slice?.qty ?? 0;
}

export function stockLeftForShopeeLabel(label: string, snapshot: PackagingStockSnapshot | null): number | null {
  if (!snapshot) return null;
  const m = label.match(/^(Light|Amber|Dark)\s+(350ml|1L|Gallon)$/i);
  if (!m) return null;
  const variant = m[1]!.toLowerCase();
  const sizeToken = m[2]!.toLowerCase();
  const size = sizeToken === "1l" ? "liter" : sizeToken === "gallon" ? "gallon" : "350ml";
  return qtyFromSnapshot(snapshot, variant, size);
}

export function stockLeftForVipPreset(
  value: string,
  product: HoneyProduct,
  step: "main" | "free350",
  snapshot: PackagingStockSnapshot | null,
): number | null {
  if (!snapshot) return null;

  if (step === "free350") {
    const variant = LETTER_VARIANT[value];
    return variant ? qtyFromSnapshot(snapshot, variant, "350ml") : null;
  }

  if (product === "1L") {
    const variant = LETTER_VARIANT[value];
    return variant ? qtyFromSnapshot(snapshot, variant, "liter") : null;
  }

  if (product === "2L" || product === "3L") {
    const qtys: number[] = [];
    for (const ch of value) {
      const variant = LETTER_VARIANT[ch];
      if (!variant) return null;
      qtys.push(qtyFromSnapshot(snapshot, variant, "liter"));
    }
    if (qtys.length === 0) return null;
    return Math.min(...qtys);
  }

  return null;
}

export function enrichShopeePresets(
  presets: PresetOption[],
  snapshot: PackagingStockSnapshot | null,
): PresetOption[] {
  return presets.map(p => ({
    ...p,
    stockLeft: stockLeftForShopeeLabel(p.label, snapshot),
  }));
}

export function enrichVipPresets(
  presets: PresetOption[],
  product: HoneyProduct,
  step: "main" | "free350",
  snapshot: PackagingStockSnapshot | null,
): PresetOption[] {
  return presets.map(p => ({
    ...p,
    stockLeft: stockLeftForVipPreset(p.value, product, step, snapshot),
  }));
}
