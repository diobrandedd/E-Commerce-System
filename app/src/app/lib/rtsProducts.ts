import type { RtsLineItem, RtsProductCatalog } from "@/app/lib/rtsApi";
import { rtsLineDisplay } from "@/app/lib/rtsApi";

/** Product types that require honey size + variant on the main line. */
export const RTS_TYPES_WITH_HONEY_MAIN = new Set(["honey", "honey_with_other"]);

/** Combo lines that pair with another honey size/variant. */
export const RTS_TYPES_WITH_COMBO_HONEY = new Set(["pouch_pt_with_honey", "jar_cacao_with_honey"]);

/** Combo lines that pair with a non-honey product. */
export const RTS_TYPES_WITH_COMBO_OTHER = new Set(["honey_with_other"]);

export function rtsFormatLine(line: RtsLineItem, catalog: RtsProductCatalog): string {
  return rtsLineDisplay(line, catalog.product_labels);
}

export function rtsFormatLines(lines: RtsLineItem[], catalog: RtsProductCatalog): string[] {
  return lines.map(l => rtsFormatLine(l, catalog));
}
