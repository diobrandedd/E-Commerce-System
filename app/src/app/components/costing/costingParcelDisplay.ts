import { peso } from "@/app/lib/costingDisplay";

export function costingCostBand(min: number, typical: number, max: number): string {
  if (min === max && Math.abs(min - typical) < 0.0001) return peso(typical);
  return `${peso(min)} – ${peso(max)} · typ ${peso(typical)}`;
}
