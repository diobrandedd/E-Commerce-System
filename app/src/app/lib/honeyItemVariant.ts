/**
 * Shopee / marketplace waybill PDFs often list size only (e.g. "1 x 1L HONEY w/SF")
 * without Light / Amber / Dark. Packers need an exact variant before stamp & master Excel.
 */

export type HoneySizeHint = "350ml" | "1L" | "2L" | "3L" | "gallon" | null;

/** API rows may send items as string, number, or null — always normalize before .trim(). */
export function honeyItemLineText(value: unknown): string {
  if (value == null) return "";
  if (typeof value === "string") return value.trim();
  return String(value).trim();
}

export function honeySizeFromItemLine(items: unknown): HoneySizeHint {
  const s = honeyItemLineText(items);
  if (!s) return null;
  if (
    /\bLAD\b/i.test(s) &&
    /350\s*ml/i.test(s) &&
    !/350\s*ml\s*\(\s*[LAD]\s*\)/i.test(s)
  ) {
    return "350ml";
  }
  if (/350\s*ml|B2T1/i.test(s)) return "350ml";
  if (/3\s*L/i.test(s)) return "3L";
  if (/2\s*L/i.test(s)) return "2L";
  if (/gallon/i.test(s)) return "gallon";
  if (/1\s*L|liter/i.test(s)) return "1L";
  return null;
}

/** True when the line looks like honey with a size but no packer-ready variant. */
export function honeyItemNeedsVariantResolution(items: unknown): boolean {
  const raw = honeyItemLineText(items);
  if (!raw) return false;
  if (!/honey|350\s*ml|B2T1/i.test(raw)) return false;

  if (/\d+\s+Gallon\s+Honey\s+(LIGHT|AMBER|DARK)\b/i.test(raw)) return false;
  if (/\d+\s*[×x]\s*350\s*ml\s*\(\s*LAD\s*\)/i.test(raw)) return false;
  if (/\d+\s*[×x]\s*350\s*ml\s*\(\s*[LAD]\s*\)/i.test(raw)) return false;
  if (/1LITER[-\s]*(Light|Amber|Dark)/i.test(raw)) return false;
  if (/1\s*Liter\s+Honey\s+(LIGHT|AMBER|DARK)/i.test(raw)) return false;
  if (/\b(light|amber|dark)\b/i.test(raw) && !/Light\s*&\s*Amber\s*&\s*Dark/i.test(raw)) {
    return false;
  }
  if (/1L\s*HONEY\s+[LAD]\b/i.test(raw)) return false;
  if (/2L-HONEY\s+[LAD]{2}/i.test(raw)) return false;
  if (/3L-HONEY\s+[LAD]{3}/i.test(raw)) return false;
  if (/\bLAD\b/i.test(raw) && /350\s*ml/i.test(raw) && !/350\s*ml\s*\(\s*[LAD]\s*\)/i.test(raw)) {
    return true;
  }
  if (/\bLAD\b/i.test(raw)) return false;
  if (/350\s*ml\s*\(\s*[LAD]\s*\)/i.test(raw)) return false;
  if (/\bLiter\s+Honey\s+(LIGHT|AMBER|DARK)\b/i.test(raw)) return false;

  const size = honeySizeFromItemLine(raw);
  if (size) return true;

  return /honey/i.test(raw);
}

export function shopeeRowNeedsItemResolution(
  row: { complete: boolean; items: unknown },
  resolvedItems?: unknown,
): boolean {
  const resolved = honeyItemLineText(resolvedItems);
  if (resolved) return false;
  const pdfItems = honeyItemLineText(row.items);
  if (!row.complete || !pdfItems) return true;
  return honeyItemNeedsVariantResolution(pdfItems);
}

/** Row is ready for Shopee resolve/stamp (PDF variant OK, or user confirmed a fix). */
export function shopeeRowIsReadyForExport(
  row: { complete: boolean; items: unknown },
  resolution?: { items?: unknown; confirmed?: boolean },
): boolean {
  const pdfNeedsFix = shopeeRowNeedsItemResolution(row, undefined);
  const pdfItems = honeyItemLineText(row.items);
  const resolvedItems = honeyItemLineText(resolution?.items);
  if (!pdfNeedsFix) {
    return Boolean(pdfItems) && !honeyItemNeedsVariantResolution(pdfItems);
  }
  if (!resolution?.confirmed || !resolvedItems) return false;
  return !honeyItemNeedsVariantResolution(resolvedItems);
}

/** Merge a variant preset into a B2T1 line that still needs a free 350ml pick. */
export function shopeePresetToResolvedItemsLine(baseItems: unknown, preset: string): string {
  const base = honeyItemLineText(baseItems);
  const pick = preset.trim();
  if (!pick) return pick;
  if (
    /\bLAD\b/i.test(base) &&
    /\+\s*350\s*ml\s*$/i.test(base) &&
    !/350\s*ml\s*\(\s*[LAD]\s*\)/i.test(base)
  ) {
    const m = pick.match(/^(Light|Amber|Dark)\s+350ml$/i);
    if (m) {
      const letter = m[1].charAt(0).toUpperCase();
      return base.replace(/\+\s*350\s*ml\s*$/i, `+ 350ml (${letter})`);
    }
  }
  const gallonQty = base.match(/^(\d+)\s+Gallon\s+Honey\b/i);
  const gallonVariant = pick.match(/^(Light|Amber|Dark)\s+Gallon$/i);
  if (gallonQty && gallonVariant) {
    return `${gallonQty[1]} Gallon Honey ${gallonVariant[1]!.toUpperCase()}`;
  }
  return pick;
}

export function variantPickTitle(items: unknown): string {
  const s = honeyItemLineText(items);
  if (
    /\bLAD\b/i.test(s) &&
    /350\s*ml/i.test(s) &&
    !/350\s*ml\s*\(\s*[LAD]\s*\)/i.test(s)
  ) {
    return "Pick Light / Amber / Dark for free 350ml";
  }
  return "Pick Light / Amber / Dark for this size";
}

export function shopeeFinalItemsForRow(
  row: { complete: boolean; items: unknown },
  resolution?: { items?: unknown; confirmed?: boolean },
): string {
  const resolvedItems = honeyItemLineText(resolution?.items);
  if (resolution?.confirmed && resolvedItems) return resolvedItems;
  return honeyItemLineText(row.items);
}
