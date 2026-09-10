export interface QtyRow {
  current_qty: string | number;
  initial_qty: string | number;
}

export function fmtCounter(current: string | number, initial: string | number): string {
  const c = Number(current);
  const i = Number(initial);
  return `${c.toLocaleString()} / ${i.toLocaleString()}`;
}

export function isDepleted(current: string | number): boolean {
  return Number(current) <= 0;
}

export type HoneyStockLevel = "full" | "medium" | "low" | "depleted" | "overdrawn";

export interface HoneyStockStatusResult {
  level: HoneyStockLevel;
  label: "Available" | "Out of stock" | "Overdrawn";
}

/** Stock level from current vs initial qty (warehouse / production rows). */
export function honeyStockStatus(
  current: string | number,
  initial: string | number,
): HoneyStockStatusResult {
  const c = Number(current);
  const i = Number(initial);
  if (Number.isFinite(c) && c < -1e-9) {
    return { level: "overdrawn", label: "Overdrawn" };
  }
  if (!Number.isFinite(c) || c <= 0 || !Number.isFinite(i) || i <= 0) {
    return { level: "depleted", label: "Out of stock" };
  }
  const pct = (c / i) * 100;
  if (pct <= 30) return { level: "low", label: "Available" };
  if (pct <= 50) return { level: "medium", label: "Available" };
  return { level: "full", label: "Available" };
}

export const HONEY_VARIANTS = ["light", "amber", "dark", "other"] as const;

/** API / DB keys */
export const HONEY_SIZES = ["liter", "250ml", "350ml", "gallon"] as const;

export type HoneyBottleSize = (typeof HONEY_SIZES)[number];

export const HONEY_BOTTLE_OPTIONS: { value: HoneyBottleSize; label: string }[] = [
  { value: "250ml", label: "250ml" },
  { value: "350ml", label: "350ml" },
  { value: "liter", label: "1L (1000 ml)" },
  { value: "gallon", label: "Gallon" },
];

/** Factory default — match PHP HONEY_ML_PER_US_GALLON (override via Settings). */
export const DEFAULT_ML_PER_US_GALLON = 3785.411784;

export function mlPerBottle(size: HoneyBottleSize, mlPerGallon: number = DEFAULT_ML_PER_US_GALLON): number {
  if (size === "gallon") return mlPerGallon > 0 ? mlPerGallon : DEFAULT_ML_PER_US_GALLON;
  if (size === "liter") return 1000;
  if (size === "350ml") return 350;
  return 250;
}

export interface BottleLine {
  size: HoneyBottleSize;
  qty: string;
}

export function linesTotalMl(lines: BottleLine[], mlPerGallon: number = DEFAULT_ML_PER_US_GALLON): number {
  let total = 0;
  for (const line of lines) {
    const qty = parseFloat(line.qty);
    if (!Number.isFinite(qty) || qty <= 0) continue;
    total += qty * mlPerBottle(line.size, mlPerGallon);
  }
  return total;
}

export function mlToGallons(ml: number, mlPerGallon: number = DEFAULT_ML_PER_US_GALLON): number {
  if (ml <= 0) return 0;
  const denom = mlPerGallon > 0 ? mlPerGallon : DEFAULT_ML_PER_US_GALLON;
  return ml / denom;
}

export function linesToGallons(lines: BottleLine[], mlPerGallon: number = DEFAULT_ML_PER_US_GALLON): number {
  return mlToGallons(linesTotalMl(lines, mlPerGallon), mlPerGallon);
}

/** Human-friendly gallon display (1 decimal). */
export function formatGallons(gallons: number): string {
  if (!Number.isFinite(gallons)) return "0";
  return gallons.toLocaleString(undefined, { maximumFractionDigits: 1, minimumFractionDigits: 0 });
}

/** Rounded ml for subtitle only (~12,500 ml). */
export function formatMlFriendly(ml: number): string {
  if (!Number.isFinite(ml) || ml <= 0) return "0 ml";
  if (ml >= 1000) {
    return `~${Math.round(ml).toLocaleString()} ml`;
  }
  return `${Math.round(ml)} ml`;
}

export function previewBatchNo(supplierCode: string, dateIso?: string): string {
  const d = dateIso ? new Date(dateIso + "T12:00:00") : new Date();
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  const dd = String(d.getDate()).padStart(2, "0");
  const yy = String(d.getFullYear()).slice(-2);
  return `${supplierCode}-${mm}${dd}${yy}`;
}
