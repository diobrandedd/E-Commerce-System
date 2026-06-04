/**
 * Shopee / marketplace waybill PDFs often list size only (e.g. "1 x 1L HONEY w/SF")
 * without Light / Amber / Dark. Packers need an exact variant before stamp & master Excel.
 */

export type HoneySizeHint = "350ml" | "1L" | "2L" | "3L" | "gallon" | null;

export function honeySizeFromItemLine(items: string): HoneySizeHint {
  const s = items.trim();
  if (!s) return null;
  if (/350\s*ml|B2T1/i.test(s)) return "350ml";
  if (/3\s*L/i.test(s)) return "3L";
  if (/2\s*L/i.test(s)) return "2L";
  if (/gallon/i.test(s)) return "gallon";
  if (/1\s*L|liter/i.test(s)) return "1L";
  return null;
}

/** True when the line looks like honey with a size but no packer-ready variant. */
export function honeyItemNeedsVariantResolution(items: string): boolean {
  const raw = items.trim();
  if (!raw) return false;
  if (!/honey|350\s*ml|B2T1/i.test(raw)) return false;

  if (/\b(light|amber|dark)\b/i.test(raw)) return false;
  if (/1L\s*HONEY\s+[LAD]\b/i.test(raw)) return false;
  if (/2L-HONEY\s+[LAD]{2}/i.test(raw)) return false;
  if (/3L-HONEY\s+[LAD]{3}/i.test(raw)) return false;
  if (/\bLAD\b/i.test(raw)) return false;
  if (/\bLiter\s+Honey\s+(LIGHT|AMBER|DARK)\b/i.test(raw)) return false;

  const size = honeySizeFromItemLine(raw);
  if (size) return true;

  return /honey/i.test(raw);
}

export function shopeeRowNeedsItemResolution(
  row: { complete: boolean; items: string },
  resolvedItems?: string,
): boolean {
  if (resolvedItems?.trim()) return false;
  if (!row.complete || !row.items?.trim()) return true;
  return honeyItemNeedsVariantResolution(row.items);
}
