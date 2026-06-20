import type { ParcelPackTier } from "@/app/lib/parcelPackCosting";

/** UI department bucket for parcel recipes (ops: PT separate; BRC + Jar Cacao = cacao_brc dept). */
export type ParcelRecipeDeptBucket = "honey" | "paragis" | "cacao_brc";

export const PARCEL_RECIPE_DEPT_LABELS: Record<ParcelRecipeDeptBucket, string> = {
  honey: "Honey",
  paragis: "Paragis Tea",
  cacao_brc: "BRC & Jar Cacao",
};

export function parcelRecipeDeptBucket(tier: ParcelPackTier): ParcelRecipeDeptBucket {
  const key = tier.tier_key;
  if (key.startsWith("combo_pt_")) return "paragis";
  if (key.startsWith("combo_brc_") || key.startsWith("combo_cacao_")) return "cacao_brc";
  if (key.startsWith("dept_pt_")) return "paragis";
  if (key.startsWith("dept_brc_") || tier.family === "jar_cacao" || key.startsWith("cacao_")) {
    return "cacao_brc";
  }
  return "honey";
}

export function parcelRecipeDeptLabel(tier: ParcelPackTier): string {
  return PARCEL_RECIPE_DEPT_LABELS[parcelRecipeDeptBucket(tier)];
}
