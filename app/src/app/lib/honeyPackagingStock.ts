import type { VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import type { HoneyProduct, PresetOption, StockBreakdownEntry } from "@/app/lib/orderRemarksParse";

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

type PackagingMlSize = "250ml" | "350ml";

function breakdownForCode(
  code: string,
  size: "liter" | PackagingMlSize,
  snapshot: PackagingStockSnapshot,
): StockBreakdownEntry[] {
  const out: StockBreakdownEntry[] = [];
  for (const ch of code.toUpperCase()) {
    const variant = LETTER_VARIANT[ch];
    if (!variant) continue;
    out.push({
      letter: ch,
      variant,
      qty: qtyFromSnapshot(snapshot, variant, size),
    });
  }
  return out;
}

export function formatStockBreakdown(entries: StockBreakdownEntry[]): string {
  if (entries.length === 0) return "";
  return entries.map(e => `${e.letter} ${Math.max(0, Math.floor(e.qty)).toLocaleString()}`).join(" · ");
}

/** Compact label for dropdowns / chips — DDD → "D ×3 · 88 left", LAD → "L 95 · A 34 · D 88". */
export function formatStockBreakdownCompact(entries: StockBreakdownEntry[]): string {
  if (entries.length === 0) return "";
  if (entries.length === 1) {
    const e = entries[0]!;
    return `${e.letter} · ${Math.max(0, Math.floor(e.qty)).toLocaleString()} left`;
  }
  const allSameLetter = entries.every(e => e.letter === entries[0]!.letter);
  if (allSameLetter) {
    const e = entries[0]!;
    return `${e.letter} ×${entries.length} · ${Math.max(0, Math.floor(e.qty)).toLocaleString()} left`;
  }
  return formatStockBreakdown(entries);
}

export function stockLeftForShopeeLabel(label: string, snapshot: PackagingStockSnapshot | null): number | null {
  if (!snapshot) return null;
  const m = label.match(/^(Light|Amber|Dark)\s+(250ml|350ml|1L|Gallon)$/i);
  if (!m) return null;
  const variant = m[1]!.toLowerCase();
  const sizeToken = m[2]!.toLowerCase();
  const size =
    sizeToken === "1l" ? "liter" : sizeToken === "gallon" ? "gallon" : (sizeToken as PackagingMlSize);
  return qtyFromSnapshot(snapshot, variant, size);
}

export function stockBreakdownForShopeeLabel(
  label: string,
  snapshot: PackagingStockSnapshot | null,
): StockBreakdownEntry[] | undefined {
  if (!snapshot) return undefined;
  const m = label.match(/^(Light|Amber|Dark)\s+(250ml|350ml|1L|2L|3L|Gallon)$/i);
  if (!m) return undefined;
  const letter = m[1]![0]!.toUpperCase();
  const sizeToken = m[2]!.toLowerCase();
  const size = sizeToken === "250ml" || sizeToken === "350ml" ? (sizeToken as PackagingMlSize) : "liter";
  const variant = LETTER_VARIANT[letter];
  if (!variant) return undefined;
  return [{ letter, variant, qty: qtyFromSnapshot(snapshot, variant, size) }];
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

  if (product === "1L" || product === "gallon") {
    const variant = LETTER_VARIANT[value];
    const size = product === "gallon" ? "gallon" : "liter";
    return variant ? qtyFromSnapshot(snapshot, variant, size) : null;
  }

  if (product === "250ml" || product === "350ml") {
    const breakdown = breakdownForCode(value, product, snapshot);
    if (breakdown.length === 0) return null;
    return Math.min(...breakdown.map(b => b.qty));
  }

  if (product === "2L" || product === "3L") {
    const breakdown = breakdownForCode(value, "liter", snapshot);
    if (breakdown.length === 0) return null;
    return Math.min(...breakdown.map(b => b.qty));
  }

  return null;
}

export function stockBreakdownForVipPreset(
  value: string,
  product: HoneyProduct,
  step: "main" | "free350",
  snapshot: PackagingStockSnapshot | null,
): StockBreakdownEntry[] | undefined {
  if (!snapshot) return undefined;
  if (step === "free350") {
    const variant = LETTER_VARIANT[value];
    if (!variant) return undefined;
    return [{ letter: value, variant, qty: qtyFromSnapshot(snapshot, variant, "350ml") }];
  }
  if (product === "1L") {
    const variant = LETTER_VARIANT[value];
    if (!variant) return undefined;
    return [{ letter: value, variant, qty: qtyFromSnapshot(snapshot, variant, "liter") }];
  }
  if (product === "gallon") {
    const variant = LETTER_VARIANT[value];
    if (!variant) return undefined;
    return [{ letter: value, variant, qty: qtyFromSnapshot(snapshot, variant, "gallon") }];
  }
  if (product === "250ml" || product === "350ml") {
    const b = breakdownForCode(value, product, snapshot);
    return b.length > 1 ? b : undefined;
  }
  if (product === "2L" || product === "3L") {
    const b = breakdownForCode(value, "liter", snapshot);
    return b.length > 1 ? b : undefined;
  }
  return undefined;
}

export function enrichShopeePresets(
  presets: PresetOption[],
  snapshot: PackagingStockSnapshot | null,
): PresetOption[] {
  return presets.map(p => ({
    ...p,
    stockLeft: stockLeftForShopeeLabel(p.label, snapshot),
    stockBreakdown: stockBreakdownForShopeeLabel(p.label, snapshot),
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
    stockBreakdown: stockBreakdownForVipPreset(p.value, product, step, snapshot),
  }));
}

export function stockBreakdownForBuilderVariant(
  product: string,
  variant: string,
  snapshot: PackagingStockSnapshot | null,
): StockBreakdownEntry[] | undefined {
  if (!snapshot || !variant) return undefined;
  const code = variant.toUpperCase();
  if (product === "250ml" || product === "350ml") {
    const b = breakdownForCode(code, product as PackagingMlSize, snapshot);
    return b.length > 0 ? b : undefined;
  }
  if (product === "2L" || product === "3L") {
    const b = breakdownForCode(code, "liter", snapshot);
    return b.length > 0 ? b : undefined;
  }
  if (product === "1L") {
    const v = LETTER_VARIANT[code];
    if (!v) return undefined;
    return [{ letter: code, variant: v, qty: qtyFromSnapshot(snapshot, v, "liter") }];
  }
  if (product === "gallon") {
    const v = LETTER_VARIANT[code];
    if (!v) return undefined;
    return [{ letter: code, variant: v, qty: qtyFromSnapshot(snapshot, v, "gallon") }];
  }
  return undefined;
}

/** Dropdown / builder label: per-bottle color stock for 2L/3L/250ml/350ml LAD combos. */
export function formatBuilderVariantStockLabel(
  product: string,
  variant: string,
  snapshot: PackagingStockSnapshot | null,
): string {
  const breakdown = stockBreakdownForBuilderVariant(product, variant, snapshot);
  if (!breakdown || breakdown.length === 0) return "";
  if (breakdown.length === 1) {
    return formatStockBreakdownCompact(breakdown);
  }
  return formatStockBreakdownCompact(breakdown);
}

export function stockQtyForBuilderVariant(
  product: string,
  variant: string,
  snapshot: PackagingStockSnapshot | null,
): number | null {
  const breakdown = stockBreakdownForBuilderVariant(product, variant, snapshot);
  if (!breakdown || breakdown.length === 0) return null;
  return Math.min(...breakdown.map(b => b.qty));
}
