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

/** Exact constant — match PHP HONEY_ML_PER_US_GALLON */
const ML_PER_US_GALLON = 3785.411784;

const ML_PER_BOTTLE: Record<HoneyBottleSize, number> = {
  "250ml": 250,
  "350ml": 350,
  liter: 1000,
  gallon: ML_PER_US_GALLON,
};

export function mlPerBottle(size: HoneyBottleSize): number {
  return ML_PER_BOTTLE[size];
}

export interface BottleLine {
  size: HoneyBottleSize;
  qty: string;
}

export function linesTotalMl(lines: BottleLine[]): number {
  let total = 0;
  for (const line of lines) {
    const qty = parseFloat(line.qty);
    if (!Number.isFinite(qty) || qty <= 0) continue;
    total += qty * mlPerBottle(line.size);
  }
  return total;
}

export function mlToGallons(ml: number): number {
  if (ml <= 0) return 0;
  return ml / ML_PER_US_GALLON;
}

export function linesToGallons(lines: BottleLine[]): number {
  return mlToGallons(linesTotalMl(lines));
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
