import {
  interpretRawText,
  composePackerLine,
  builderLineIsValid,
  isHoneyBuilderProduct,
  stripHoneySizeTokens,
  normalizeVariantCountText,
  type BuilderLine,
} from "@/app/lib/itemLineBuilder";

export type HoneyProduct = "1L" | "2L" | "3L" | "250ml" | "350ml" | "gallon" | "other";

export interface StockBreakdownEntry {
  letter: string;
  variant: string;
  qty: number;
}

export interface ParsedRemark {
  product: HoneyProduct;
  variants: string[];
  address: string;
  clean: string;
  needsEdit: boolean;
  variantRaw: string;
  /** Structured lines from interpretRawText */
  builderLines?: BuilderLine[];
  needsBuilder?: boolean;
  /** Paragis / Jar Cacao / etc. — CSR must confirm qty in builder */
  needsDeptConfirm?: boolean;
}

export interface PresetOption {
  value: string;
  label: string;
  /** Bottled packaging stock for this preset (CSR variant picker). */
  stockLeft?: number | null;
  /** Per-bottle stock for 2L/3L/350ml LAD combos */
  stockBreakdown?: StockBreakdownEntry[];
}

export interface RowResolution {
  mainVariant?: string;
  free350Variant?: string;
  finalRemark?: string;
}

export interface CardSteps {
  showMain: boolean;
  showFree350: boolean;
  mainFromAuto: boolean;
  mainLabel?: string;
  mainProduct: HoneyProduct;
}

const HONEY_CODE_RE =
  /3L-HONEY|2L-HONEY|1L HONEY|3 L Honey|2 L.Honey|1 L HONEY|250ml|250ML|350ml|350ML|B2T1|B1T2|G HONEY|gallon/i;

const VALID_3L = new Set(["LAD", "LLL", "AAA", "DDD", "LLA", "LAA", "LLD", "ADD", "AAD"]);
const VALID_2L = new Set(["LA", "LD", "AD", "LL", "AA", "DD"]);
const VALID_1L = new Set(["L", "A", "D"]);

export function extractQty(raw: string): string {
  const m = raw.match(/^(\d+\s*x\s*)/i);
  return m ? m[1].trim() + " " : "1 x ";
}

export function hasFree350Promo(raw: string): boolean {
  return /FREE\s*1\s*350\s*mL|Set\s*B|1pc\.?\s*350\s*ml|350\s*ml\.?\s*Honey/i.test(raw);
}

export function extract350Variant(raw: string): string | null {
  const m = raw.match(/350\s*ml\s*\(\s*([LAD]+)\s*\)/i);
  if (m && /^[LAD]+$/i.test(m[1]!)) return m[1]!.toUpperCase();
  const mWord = raw.match(/350\s*ml\s*\(\s*(Light|Amber|Dark)\s*\)/i);
  if (mWord) return mWord[1]![0]!.toUpperCase();
  const m2 = raw.match(/350\s*ml\.?\s*Honey\s*\(\s*([LAD]+)\s*\)/i);
  if (m2) return m2[1]!.toUpperCase();
  return null;
}

export function extractParenVariant(raw: string, product: HoneyProduct): string | null {
  const honeyMatch = raw.match(
    /3\s*L\s*Honey\s*\(\s*([LAD]{3})\s*\)/i,
  );
  if (product === "3L" && honeyMatch) {
    const code = honeyMatch[1].toUpperCase();
    if (VALID_3L.has(code)) return code;
  }
  const honey2 = raw.match(/2\s*L\s*Honey\s*\(\s*([LAD]{2})\s*\)/i);
  if (product === "2L" && honey2) {
    const code = honey2[1].toUpperCase();
    if (VALID_2L.has(code)) return code;
  }
  const honey1 = raw.match(/1\s*L\s*Honey\s*\(\s*([LAD])\s*\)/i);
  if (product === "1L" && honey1) return honey1[1].toUpperCase();

  const generic = raw.match(/\(\s*([LAD]{1,3})\s*\)/i);
  if (!generic) return null;
  const code = generic[1].toUpperCase();
  if (product === "3L" && VALID_3L.has(code)) return code;
  if (product === "2L" && VALID_2L.has(code)) return code;
  if (product === "1L" && VALID_1L.has(code)) return code;
  return null;
}

export function extractCodeVariant(raw: string, product: HoneyProduct): string | null {
  if (product === "3L") {
    const m = raw.match(/3L-HONEY\s+([LAD]{3})/i);
    if (m && VALID_3L.has(m[1].toUpperCase())) return m[1].toUpperCase();
  }
  if (product === "2L") {
    const m = raw.match(/2L-HONEY\s+([LAD]{2})/i);
    if (m && VALID_2L.has(m[1].toUpperCase())) return m[1].toUpperCase();
  }
  if (product === "1L") {
    const m = raw.match(/1L HONEY\s+([LAD])/i);
    if (m && VALID_1L.has(m[1].toUpperCase())) return m[1].toUpperCase();
  }
  return null;
}

export function parseRemarks(raw: string): ParsedRemark {
  if (!raw?.trim()) {
    return {
      product: "other",
      variants: [],
      address: "",
      clean: "",
      needsEdit: false,
      variantRaw: "",
    };
  }

  const r = raw.trim().replace(/<[^>]*>/g, "");
  let product: HoneyProduct = "other";
  if (/3L-HONEY|3 L Honey|3L HONEY/i.test(r)) product = "3L";
  else if (/2L-HONEY|2 L.Honey|2L HONEY|2\s*L\s*Honey/i.test(r)) product = "2L";
  else if (/1L HONEY|1 L HONEY/i.test(r)) product = "1L";
  else if (/250ml|250\s*ml/i.test(r)) product = "250ml";
  else if (/350ml|350ML|B2T1|B1T2/i.test(r)) product = "350ml";
  else if (/G HONEY|gallon/i.test(r)) product = "gallon";

  const interpreted = interpretRawText(r);

  if (!/textarea_input|radio_input/i.test(r)) {
    const scanText = r;
    let variants: string[] = [];
    if (product === "1L" || product === "2L" || product === "3L") {
      variants = detectVariants(scanText, product);
    }
    return {
      product,
      variants,
      address: "",
      clean: interpreted.packerLine || r,
      needsEdit: false,
      variantRaw: "",
      builderLines: interpreted.lines,
      needsBuilder: interpreted.needsBuilder,
      needsDeptConfirm: interpreted.needsDeptConfirm,
    };
  }

  let variantRaw = "";
  const m1 = r.match(/textarea_input_1:\s*([^\n,]+(?:,\s*[^\n,]+)*)/i);
  if (m1) variantRaw = m1[1].trim();

  let address = "";
  const m2 = r.match(/textarea_input_2:\s*(.+)/i);
  if (m2) address = m2[1].replace(/\\n/g, " ").trim();

  let variants = detectVariants(variantRaw, product);
  if (variants.length === 0 && (product === "2L" || product === "3L")) {
    variants = detectVariants(r, product);
  }

  const clean = buildCleanRemarks(r, product, variants, variantRaw);

  return {
    product,
    variants,
    address,
    clean: interpreted.packerLine || clean || r,
    needsEdit: true,
    variantRaw,
    builderLines: interpreted.lines,
    needsBuilder: interpreted.needsBuilder || interpreted.needsMainPick || interpreted.needs350Pick,
    needsDeptConfirm: interpreted.needsDeptConfirm,
  };
}

export function detectVariants(text: string, product: HoneyProduct): string[] {
  if (!text) return [];

  const t = stripHoneySizeTokens(normalizeVariantCountText(text)).toLowerCase();
  const hasLight = /\blights?\b|(?:^|[\s,&])l(?:\s|$|[,&])/.test(t);
  const hasAmber = /\bambers?\b|(?:^|[\s,&])a(?:\s|$|[,&])/.test(t);
  const hasDark = /\bdarks?\b|(?:^|[\s,&])d(?:\s|$|[,&])/.test(t);
  const allColors = /all color|all colour|\blad\b/i.test(t);
  const allAmber = /all.?amber/i.test(t);
  const allLight = /all.?light/i.test(t);
  const allDark = /all.?dark/i.test(t);

  if (allColors || (hasLight && hasAmber && hasDark)) return ["L", "A", "D"];
  if (allAmber) return product === "3L" ? ["A", "A", "A"] : ["A", "A"];
  if (allLight) return product === "3L" ? ["L", "L", "L"] : ["L", "L"];
  if (allDark) return product === "3L" ? ["D", "D", "D"] : ["D", "D"];

  if (product === "2L") {
    const m2a1d = /2\s*amber.*1\s*dark|2a.*1d/i.test(t);
    const m1a1d = hasAmber && hasDark && !hasLight;
    const m1l1a = hasLight && hasAmber && !hasDark;
    const m1l1d = hasLight && hasDark && !hasAmber;
    if (m2a1d) return ["A", "A", "D"];
    if (m1a1d) return ["A", "D"];
    if (m1l1a) return ["L", "A"];
    if (m1l1d) return ["L", "D"];
    if (hasAmber) return ["A", "A"];
    if (hasLight) return ["L", "L"];
    if (hasDark) return ["D", "D"];
  }

  if (product === "3L") {
    if (/1\s*amber.*2\s*dark|1\s*a.*2\s*d/i.test(t)) return ["A", "D", "D"];
    if (/2\s*dark.*1\s*amber|2\s*d.*1\s*a/i.test(t)) return ["A", "D", "D"];
    if (/2\s*amber.*1\s*dark|2\s*a.*1\s*d/i.test(t)) return ["A", "A", "D"];
    if (/2\s*light.*1\s*amber|2\s*lights.*1\s*amber/i.test(t)) return ["L", "L", "A"];
    if (/1\s*light.*2\s*amber|1\s*light.*2\s*ambers/i.test(t)) return ["L", "A", "A"];
    if (/2\s*light.*1\s*dark|2\s*lights.*1\s*dark/i.test(t)) return ["L", "L", "D"];
    if (hasLight && hasAmber && hasDark) return ["L", "A", "D"];
    if (hasLight) return ["L", "L", "L"];
    if (hasAmber && hasDark) return [];
    if (hasAmber) return ["A", "A", "A"];
    if (hasDark) return ["D", "D", "D"];
  }

  if (product === "1L") {
    if (hasAmber) return ["A"];
    if (hasLight) return ["L"];
    if (hasDark) return ["D"];
  }

  return [];
}

/** Resolve main product variant code (LAD, AA, ADD, L, etc.) */
export function resolveMainVariant(raw: string, parsed: ParsedRemark): string | null {
  const { product } = parsed;
  if (product !== "1L" && product !== "2L" && product !== "3L") return null;

  const fromParen = extractParenVariant(raw, product);
  if (fromParen) return fromParen;

  const fromCode = extractCodeVariant(raw, product);
  if (fromCode) return fromCode;

  if (parsed.variants.length > 0) {
    const joined = parsed.variants.join("").toUpperCase();
    if (product === "3L" && VALID_3L.has(joined)) return joined;
    if (product === "2L" && VALID_2L.has(joined)) return joined;
    if (product === "1L" && VALID_1L.has(joined)) return joined;
  }

  if (!parsed.needsEdit) {
    const fromFull = detectVariants(raw, product);
    if (fromFull.length > 0) {
      const joined = fromFull.join("").toUpperCase();
      if (product === "3L" && VALID_3L.has(joined)) return joined;
      if (product === "2L" && VALID_2L.has(joined)) return joined;
      if (product === "1L" && VALID_1L.has(joined)) return joined;
    }
  }

  return null;
}

export function needsMainVariantPick(raw: string, parsed: ParsedRemark): boolean {
  if (
    parsed.needsBuilder &&
    parsed.builderLines?.some(
      l => isHoneyBuilderProduct(l.product) && l.product !== "other" && !builderLineIsValid(l),
    )
  ) {
    return true;
  }
  if (parsed.product !== "1L" && parsed.product !== "2L" && parsed.product !== "3L" && parsed.product !== "gallon") {
    return false;
  }
  return resolveMainVariant(raw, parsed) === null;
}

export function needsFree350Pick(raw: string, _parsed: ParsedRemark): boolean {
  if (!hasFree350Promo(raw)) return false;
  return extract350Variant(raw) === null;
}

export function isAmbiguousRemark(raw: string, parsed: ParsedRemark): boolean {
  if (parsed.needsBuilder) return true;
  return needsMainVariantPick(raw, parsed) || needsFree350Pick(raw, parsed);
}

export function getCardSteps(raw: string, parsed: ParsedRemark, resolution?: RowResolution): CardSteps {
  const autoMain = resolveMainVariant(raw, parsed);
  const mainResolved = resolution?.mainVariant ?? autoMain ?? undefined;
  const freeResolved = resolution?.free350Variant ?? extract350Variant(raw) ?? undefined;

  const needsMain = needsMainVariantPick(raw, parsed) && !mainResolved;
  const needsFree = needsFree350Pick(raw, parsed) && !freeResolved;

  return {
    showMain: needsMain,
    showFree350: !needsMain && needsFree,
    mainFromAuto: !!autoMain && !resolution?.mainVariant,
    mainLabel: mainResolved ?? undefined,
    mainProduct: parsed.product,
  };
}

export function isRowFullyResolved(raw: string, parsed: ParsedRemark, resolution?: RowResolution): boolean {
  if (resolution?.finalRemark) return true;
  if (parsed.needsBuilder) {
    const invalidHoney = parsed.builderLines?.some(
      l => isHoneyBuilderProduct(l.product) && l.product !== "other" && !builderLineIsValid(l),
    );
    if (invalidHoney) return false;
    if (parsed.needsDeptConfirm && !resolution?.finalRemark) return false;
    const multiItem = (parsed.builderLines?.length ?? 0) > 1;
    if (multiItem && !resolution?.finalRemark) return false;
  }
  if (!isAmbiguousRemark(raw, parsed)) return true;
  const main = resolution?.mainVariant ?? resolveMainVariant(raw, parsed);
  if (needsMainVariantPick(raw, parsed) && !main) return false;
  const free = resolution?.free350Variant ?? extract350Variant(raw);
  if (needsFree350Pick(raw, parsed) && !free) return false;
  return true;
}

export function buildFinalRemark(
  raw: string,
  parsed: ParsedRemark,
  mainVariant: string,
  free350Variant?: string,
): string {
  const qty = extractQty(raw);
  const product = parsed.product;

  if (product === "3L") {
    let out = `${qty}3L Honey (${mainVariant})`;
    if (hasFree350Promo(raw) && free350Variant) {
      out += ` + 350ml (${free350Variant})`;
    }
    return out;
  }
  if (product === "2L") {
    return `${qty}2L Honey (${mainVariant})`;
  }
  if (product === "1L") {
    return `${qty}1L Honey (${mainVariant})`;
  }
  return parsed.clean || raw;
}

export function buildResolutionFinalRemark(
  raw: string,
  parsed: ParsedRemark,
  resolution: RowResolution,
): string {
  if (resolution.finalRemark) return resolution.finalRemark;
  const main =
    resolution.mainVariant ?? resolveMainVariant(raw, parsed) ?? "";
  if (!main) return parsed.clean || raw;
  const free = resolution.free350Variant ?? extract350Variant(raw) ?? undefined;
  return buildFinalRemark(raw, parsed, main, free);
}

export function buildCleanRemarks(
  raw: string,
  product: HoneyProduct,
  variants: string[],
  variantRaw = "",
): string {
  const main = variants.length > 0 ? variants.join("") : "";
  if (product === "3L" && main) {
    return buildFinalRemark(raw, { product, variants, address: "", clean: "", needsEdit: true, variantRaw }, main);
  }
  if (product === "2L" && main) {
    return buildFinalRemark(raw, { product, variants, address: "", clean: "", needsEdit: true, variantRaw }, main);
  }
  if (product === "1L" && main) {
    return buildFinalRemark(raw, { product, variants, address: "", clean: "", needsEdit: true, variantRaw }, main);
  }
  return raw;
}

export function build3LPresets(_raw: string): PresetOption[] {
  return ["LAD", "LLL", "AAA", "DDD", "LLA", "LAA", "LLD", "ADD", "AAD"].map(label => ({
    label,
    value: label,
  }));
}

export function build2LPresets(_raw: string): PresetOption[] {
  return ["LA", "LD", "AD", "LL", "AA", "DD"].map(label => ({
    label,
    value: label,
  }));
}

export function build1LPresets(_raw: string): PresetOption[] {
  return ["L", "A", "D"].map(label => ({ label, value: label }));
}

export function buildFree350Presets(): PresetOption[] {
  return ["L", "A", "D"].map(label => ({ label, value: label }));
}

export function buildMainPresets(product: HoneyProduct, _raw: string): PresetOption[] {
  if (product === "3L") return build3LPresets(_raw);
  if (product === "2L") return build2LPresets(_raw);
  if (product === "1L") return build1LPresets(_raw);
  if (product === "gallon") return build1LPresets(_raw);
  return [];
}

/** @deprecated Use buildMainPresets / buildFree350Presets */
export function buildPresets(product: HoneyProduct, raw: string): PresetOption[] {
  return buildMainPresets(product, raw);
}

export function finalRemarkForRow(
  raw: string,
  parsed: ParsedRemark,
  resolution?: RowResolution,
): string {
  if (resolution?.finalRemark) return resolution.finalRemark;
  if (resolution && (resolution.mainVariant || resolution.free350Variant)) {
    return buildResolutionFinalRemark(raw, parsed, resolution);
  }

  const builderLines = parsed.builderLines ?? [];
  const allBuilderValid = builderLines.every(
    l => !isHoneyBuilderProduct(l.product) || l.product === "other" || builderLineIsValid(l),
  );
  if (builderLines.length > 1 && allBuilderValid) {
    return composePackerLine(builderLines);
  }

  if (!isAmbiguousRemark(raw, parsed)) {
    const main = resolveMainVariant(raw, parsed);
    if (main && hasFree350Promo(raw) && needsFree350Pick(raw, parsed)) {
      return parsed.clean || raw;
    }
    if (main && builderLines.length <= 1) {
      return buildFinalRemark(raw, parsed, main, extract350Variant(raw) ?? undefined);
    }
    if (allBuilderValid && builderLines.length > 0) {
      return composePackerLine(builderLines);
    }
    return parsed.clean || raw;
  }
  return raw;
}

export type RowCategory = "auto" | "ambiguous" | "passthrough" | "skipped";

export function categorizeRow(raw: string, parsed: ParsedRemark): RowCategory {
  if (!raw?.trim()) return "skipped";
  if (isAmbiguousRemark(raw, parsed)) return "ambiguous";
  if (parsed.product === "other" && !parsed.needsEdit && !parsed.needsDeptConfirm) return "passthrough";
  const main = resolveMainVariant(raw, parsed);
  if (main && !needsFree350Pick(raw, parsed)) return "auto";
  if (!parsed.needsEdit && main) return "auto";
  if (!parsed.needsEdit) return "passthrough";
  return "auto";
}

export function productLabel(product: HoneyProduct): string {
  const map: Record<HoneyProduct, string> = {
    "3L": "3 Liter",
    "2L": "2 Liter",
    "1L": "1 Liter",
    "250ml": "250 mL",
    "350ml": "350 mL",
    gallon: "Gallon",
    other: "Other",
  };
  return map[product] ?? product;
}

export function cardSubtitle(raw: string, parsed: ParsedRemark, steps: CardSteps): string {
  if (parsed.needsDeptConfirm) return "Confirm product lines and quantities";
  if (steps.showMain) return "Pick variant for main product";
  if (steps.showFree350 && steps.mainLabel) {
    return `3L: ${steps.mainLabel} (from order) · pick 350ml`;
  }
  if (steps.showFree350) return "Pick free 350ml bottle variant";
  return "";
}
