import type { DeptProductKey } from "@/app/lib/deptProducts";

export type HoneySizeProduct = "1L" | "2L" | "3L" | "250ml" | "350ml" | "gallon" | "other";

export type BuilderProduct = HoneySizeProduct | DeptProductKey;

export interface BuilderLine {
  product: BuilderProduct;
  /** L/A/D code for honey sizes (L, LA, LAD, etc.) */
  variant?: string;
  qty: number;
}

export interface BuilderProductOption {
  value: BuilderProduct;
  label: string;
  isHoney: boolean;
}

export const BUILDER_PRODUCT_OPTIONS: BuilderProductOption[] = [
  { value: "250ml", label: "250ml Honey", isHoney: true },
  { value: "350ml", label: "350ml Honey", isHoney: true },
  { value: "1L", label: "1L Honey", isHoney: true },
  { value: "2L", label: "2L Honey", isHoney: true },
  { value: "3L", label: "3L Honey", isHoney: true },
  { value: "gallon", label: "Gallon Honey", isHoney: true },
  { value: "jar_cacao", label: "Jar Cacao", isHoney: false },
  { value: "pt", label: "Paragis Tea", isHoney: false },
  { value: "brc", label: "Black Rice Coffee", isHoney: false },
  { value: "honey_comb", label: "Honey Comb", isHoney: false },
];

const VALID_3L = new Set(["LAD", "LLL", "AAA", "DDD", "LLA", "LAA", "LLD", "ADD", "AAD"]);
const VALID_2L = new Set(["LA", "LD", "AD", "LL", "AA", "DD"]);
const VALID_1L = new Set(["L", "A", "D"]);
const VALID_BULK_ML = new Set(["L", "A", "D", "LAD"]);

/** "1 amber 2dark" → "1 amber 2 dark" so count + color tokens parse reliably. */
export function normalizeVariantCountText(text: string): string {
  return text
    .replace(/(\d+)(dark|darks?|amber|ambers?|light|lights?)/gi, "$1 $2")
    .replace(/\s+/g, " ")
    .trim();
}

function isBulkMlProduct(product: BuilderProduct): product is "250ml" | "350ml" {
  return product === "250ml" || product === "350ml";
}

export function isHoneyBuilderProduct(product: BuilderProduct): boolean {
  return BUILDER_PRODUCT_OPTIONS.find(p => p.value === product)?.isHoney ?? false;
}

export function variantOptionsForProduct(product: BuilderProduct): { value: string; label: string }[] {
  if (product === "3L") {
    return ["LAD", "LLL", "AAA", "DDD", "LLA", "LAA", "LLD", "ADD", "AAD"].map(v => ({ value: v, label: v }));
  }
  if (product === "2L") {
    return ["LA", "LD", "AD", "LL", "AA", "DD"].map(v => ({ value: v, label: v }));
  }
  if (product === "1L" || product === "gallon" || isBulkMlProduct(product)) {
    return ["L", "A", "D"].map(v => ({ value: v, label: v }));
  }
  return [];
}

export function builderLineIsValid(line: BuilderLine): boolean {
  if (line.qty < 1) return false;
  if (!isHoneyBuilderProduct(line.product)) return true;
  if (line.product === "other") return false;
  const v = (line.variant ?? "").toUpperCase();
  if (!v) return false;
  if (line.product === "3L") return VALID_3L.has(v);
  if (line.product === "2L") return VALID_2L.has(v);
  if (line.product === "1L" || line.product === "gallon") return VALID_1L.has(v);
  if (isBulkMlProduct(line.product)) return VALID_BULK_ML.has(v);
  return false;
}

export function defaultBuilderLine(product: BuilderProduct = "1L"): BuilderLine {
  const opts = variantOptionsForProduct(product);
  return {
    product,
    variant: opts[0]?.value,
    qty: 1,
  };
}

export function deptLabelForKey(key: DeptProductKey): string {
  const map: Record<DeptProductKey, string> = {
    jar_cacao: "Jar Cacao",
    pt: "Paragis Tea",
    brc: "Black Rice Coffee",
    honey_comb: "Honey Comb",
  };
  return map[key];
}

export function formatBuilderLine(line: BuilderLine): string {
  const q = Math.max(1, Math.floor(line.qty));
  const v = (line.variant ?? "").toUpperCase();

  if (isBulkMlProduct(line.product)) {
    const ml = line.product;
    return v ? `${q}× ${ml} (${v})` : `${q}× ${ml}`;
  }
  if (line.product === "1L") {
    return `${q} x 1L Honey (${v})`;
  }
  if (line.product === "2L") {
    return `${q} x 2L Honey (${v})`;
  }
  if (line.product === "3L") {
    let out = `${q} x 3L Honey (${v})`;
    return out;
  }
  if (line.product === "gallon") {
    const word = v === "L" ? "LIGHT" : v === "A" ? "AMBER" : v === "D" ? "DARK" : v;
    return `${q} Gallon Honey ${word}`;
  }
  if (line.product === "jar_cacao") return `${q} Jar Cacao`;
  if (line.product === "pt") return `${q} Paragis Tea`;
  if (line.product === "brc") return `${q} Black Rice Coffee`;
  if (line.product === "honey_comb") return `${q} Honey Comb`;
  return "";
}

export function composePackerLine(lines: BuilderLine[]): string {
  return lines.map(formatPackerLinePart).filter(Boolean).join(" + ");
}

function formatPackerLinePart(line: BuilderLine): string {
  if (isHoneyBuilderProduct(line.product) && line.product !== "other") {
    if (builderLineIsValid(line)) return formatBuilderLine(line);
    const q = Math.max(1, Math.floor(line.qty));
    if (isBulkMlProduct(line.product)) return `${q}× ${line.product} (pick variant)`;
    if (line.product === "gallon") return `${q} Gallon Honey (pick variant)`;
    return `${q} x ${line.product} Honey (pick variant)`;
  }
  return formatBuilderLine(line);
}

/** Remove bottle-size tokens so "1 L HONEY" is not read as Light (L). */
export function stripHoneySizeTokens(text: string): string {
  return text
    .replace(/\d+\s*[x×]\s*\d+\s*L[\s.-]*Honey/gi, " ")
    .replace(/\d+\s*L[\s.-]*Honey/gi, " ")
    .replace(/\d+\s*L[\s.-]*HONEY/gi, " ")
    .replace(/1L[\s.-]*HONEY/gi, " ")
    .replace(/2L[\s.-]*HONEY/gi, " ")
    .replace(/3L[\s.-]*HONEY/gi, " ")
    .replace(/250\s*ml/gi, " ")
    .replace(/350\s*ml/gi, " ")
    .replace(/g\s*honey/gi, " ")
    .replace(/\bgallon\b/gi, " ");
}

const CACAO_JAR_COUNT_RE = /(\d+)\s*JARS?\s*(?:FINEST\s*)?CACAO/i;
const CACAO_LEADING_JAR_RE = /(\d+)\s*JAR\s+(?:FINEST\s+)?CACAO/i;
const CACAO_SIMPLE_JAR_RE = /\b(\d+)\s*Jar\s+Cacao\b/i;

function isCacaoDeptSegment(segment: string): boolean {
  return detectDeptProduct(segment) === "jar_cacao";
}

/** VIP cacao lines: `4JARS FINEST CACAO`, `1JAR … WITH HONEY`, promo Buy 3 + FREE 1. */
export function extractCacaoJarQty(segment: string, promoText = ""): number {
  const jars = segment.match(CACAO_JAR_COUNT_RE);
  if (jars) return Math.max(1, parseInt(jars[1]!, 10));

  const oneJar = segment.match(CACAO_LEADING_JAR_RE);
  if (oneJar) return Math.max(1, parseInt(oneJar[1]!, 10));

  const simple = segment.match(CACAO_SIMPLE_JAR_RE);
  if (simple) return Math.max(1, parseInt(simple[1]!, 10));

  const dup = segment.match(/(\d+)\s*JARS?\s*CACAO\s*-\s*\1\s*JARS?\s*CACAO/i);
  if (dup) return Math.max(1, parseInt(dup[1]!, 10));

  const combined = `${segment} ${promoText}`;
  const buyFree = combined.match(/BUY\s*(\d+)\s*\+\s*FREE\s*(\d+)/i);
  if (buyFree) {
    return Math.max(1, parseInt(buyFree[1]!, 10) + parseInt(buyFree[2]!, 10));
  }
  const buyPcs = combined.match(/BUY\s*(\d+)\s*PCS?\s+(?:OF\s+)?(?:FINEST\s+)?CACAO/i);
  if (buyPcs) return Math.max(1, parseInt(buyPcs[1]!, 10));

  if (DEPT_QTY_LEAD_RE.test(segment.trim())) {
    return 1;
  }

  return extractQty(segment);
}

export function detectDeptProduct(segment: string): DeptProductKey | null {
  const s = segment.toLowerCase();
  if (
    CACAO_JAR_COUNT_RE.test(s) ||
    CACAO_LEADING_JAR_RE.test(s) ||
    CACAO_SIMPLE_JAR_RE.test(segment) ||
    /jar\s*cacao|cacao\s*jar|finest\s*cacao|cacao\s*tableya|\bcacao\b/i.test(s)
  ) {
    return "jar_cacao";
  }
  if (/paragis|pito[\s-]*pito/i.test(s)) return "pt";
  if (/black\s*rice|brc\b/i.test(s)) return "brc";
  if (/honey\s*comb|honeycomb/i.test(s)) return "honey_comb";
  return null;
}

const DEPT_QTY_LEAD_RE =
  /^(?:paragis|\d+\s*jars?\s*(?:finest\s*)?cacao|jar\s*cacao|finest\s*cacao|cacao|black\s*rice|honey\s*comb|\bbrc\b|pito[\s-]*pito)/i;
const DEPT_QTY_TRAIL_RE =
  /\b(?:paragis(?:\s*tea)?|jar\s*cacao|finest\s*cacao|cacao(?:\s*tableya)?|black\s*rice\s*coffee|honey\s*comb|\bbrc\b)\s*[x×]\s*(\d+)\s*$/i;

function stripCacaoTokens(text: string): string {
  return text
    .replace(/\d+\s*jars?\s*(?:finest\s*)?cacao/gi, " ")
    .replace(/\d+\s*jar\s+(?:finest\s+)?cacao/gi, " ")
    .replace(/jar\s*cacao/gi, " ")
    .replace(/finest\s*cacao/gi, " ")
    .replace(/cacao\s*tableya/gi, " ")
    .replace(/\bcacao\b/gi, " ");
}

function extractRadioPromoText(raw: string): string {
  const parts = raw.match(/radio_input_\d+:\s*([^\n]*)/gi);
  if (!parts?.length) return "";
  return parts
    .map(p => p.replace(/^radio_input_\d+:\s*/i, "").trim())
    .join(" ");
}

/** Cacao bundle includes honey — CSR must add honey size + variant in the item builder card. */
export function detectCacaoHoneyCombo(segment: string, promoText: string): boolean {
  const blob = `${segment} ${promoText}`;
  return /with\s*honey|raw\s*honey|\d+\s*ml\s*(?:raw\s*)?honey/i.test(blob);
}

/** Qty for dept catalog lines — VIP often uses `1 x 2 Paragis Tea` (order prefix + real qty). */
export function extractDeptQty(segment: string): number {
  if (isCacaoDeptSegment(segment)) {
    return extractCacaoJarQty(segment);
  }

  const trail = segment.match(DEPT_QTY_TRAIL_RE);
  if (trail) return Math.max(1, parseInt(trail[1]!, 10));

  const oneXN = segment.match(
    /^1\s*[x×]\s*(\d+)\s+(?=paragis|\d+\s*jars?\s*(?:finest\s*)?cacao|jar\s*cacao|finest\s*cacao|cacao|black\s*rice|honey\s*comb|\bbrc\b|pito)/i,
  );
  if (oneXN) return Math.max(1, parseInt(oneXN[1]!, 10));

  const lead = segment.match(
    /^(\d+)\s+(?=paragis|\d+\s*jars?\s*(?:finest\s*)?cacao|jar\s*cacao|finest\s*cacao|cacao|black\s*rice|honey\s*comb|\bbrc\b|pito)/i,
  );
  if (lead) return Math.max(1, parseInt(lead[1]!, 10));

  if (DEPT_QTY_LEAD_RE.test(segment.trim())) {
    return 1;
  }

  return extractQty(segment);
}

export function isDeptBuilderProduct(product: BuilderProduct): boolean {
  return !isHoneyBuilderProduct(product) && product !== "other";
}

/** J&T Item Name: "3L HONEY(ALL DARK)1 PC 350ML(DARK)" — no separator before trailing bulk ml. */
function splitJntConcatenatedBulkMl(part: string): string[] {
  const m = part.match(/^(.*?)(\d+\s*PC\s*(?:250|350)\s*ML.*)$/i);
  if (!m) return [part];
  const head = m[1]!.trim();
  const tail = m[2]!.trim();
  if (!head || !tail) return [part];
  if (!/\bhoney|3\s*L|2\s*L|1\s*L|3L|2L|1L/i.test(head)) return [part];
  return [head, tail];
}

export function splitItemSegments(raw: string): string[] {
  const text = raw.trim();
  if (!text) return [];

  const parts = text.split(/\s*[;+]\s*/).flatMap(chunk => {
    const c = chunk.trim();
    if (!c) return [];
    const colonParts =
      /\bHONEY\s*:/i.test(c) && /\b(?:JAR|PARAGIS|BLACK|CACAO|COMB)\b/i.test(c)
        ? c.split(/\s*:\s*/).map(s => s.trim()).filter(Boolean)
        : [c];
    return colonParts.flatMap(part => {
      const jntSplit = splitJntConcatenatedBulkMl(part);
      return jntSplit.flatMap(p => {
        if (/,\s*(?:\d+\s*[x×]\s*|\d+\s*(?:PC|JAR|G|GALLON|250|350|L[\s-]?HONEY|HONEY))/i.test(p)) {
          return p
            .split(/\s*,\s*(?=\d+\s*[x×]\s*|\d+\s*(?:PC|JAR|G\s|GALLON|250|350|L[\s-]?HONEY|HONEY|PARAGIS|BLACK|CACAO))/i)
            .map(s => s.trim())
            .filter(Boolean);
        }
        return [p];
      });
    });
  });

  return parts.filter(Boolean);
}

export function promoBottleCount(segment: string): number | null {
  if (/B2T1|BUY\s*2\s*TAKE\s*1/i.test(segment)) return 3;
  if (/B1T2|BUY\s*1\s*TAKE\s*2/i.test(segment)) return 3;
  const m250x = segment.match(/(?:^|\s)(\d+)\s*[x×]\s*250/i);
  if (m250x) return Math.max(1, parseInt(m250x[1]!, 10));
  const m250 = segment.match(/(?:^|\s)(\d+)\s*250\s*ml/i);
  if (m250) return Math.max(1, parseInt(m250[1]!, 10));
  const mPc350 = segment.match(/(?:^|\s)(\d+)\s*PC\s*350\s*ml/i);
  if (mPc350) return Math.max(1, parseInt(mPc350[1]!, 10));
  const mPc250 = segment.match(/(?:^|\s)(\d+)\s*PC\s*250\s*ml/i);
  if (mPc250) return Math.max(1, parseInt(mPc250[1]!, 10));
  const m = segment.match(/(?:^|\s)(\d+)\s*[x×]\s*350/i);
  if (m) return Math.max(1, parseInt(m[1]!, 10));
  const m2 = segment.match(/(?:^|\s)(\d+)\s*350\s*ml/i);
  if (m2) return Math.max(1, parseInt(m2[1]!, 10));
  return null;
}

export type LineConfidence = "complete" | "assumed" | "needs_pick";

export interface InterpretResult {
  lines: BuilderLine[];
  packerLine: string;
  needsMainPick: boolean;
  needs350Pick: boolean;
  needsBuilder: boolean;
  needsDeptConfirm: boolean;
  /** Jar cacao + honey promo — honey line is not auto-filled; card builder required. */
  needsCacaoHoneyManual: boolean;
  mainProduct: HoneySizeProduct;
}

function extractQty(segment: string): number {
  const promo = promoBottleCount(segment);
  if (promo) return promo;
  const mx = segment.match(/^(\d+)\s*[x×]\s*/i);
  if (mx) return Math.max(1, parseInt(mx[1]!, 10));
  const m = segment.match(/^(\d+)\s+/i);
  if (m) return Math.max(1, parseInt(m[1]!, 10));
  return 1;
}

function detectHoneySize(segment: string): HoneySizeProduct {
  if (/g\s*honey|gallon/i.test(segment)) return "gallon";
  if (/3L-HONEY|3\s*L\s*Honey|3L HONEY/i.test(segment)) return "3L";
  if (/2L-HONEY|2\s*L[\s.-]*Honey|2L HONEY|2\s*L\s*HONEY/i.test(segment)) return "2L";
  if (/1L HONEY|1\s*L\s*HONEY|1\s*Liter/i.test(segment)) return "1L";
  if (/250\s*ml/i.test(segment)) return "250ml";
  if (/350\s*ml|B2T1|B1T2/i.test(segment)) return "350ml";
  return "other";
}

function extractCodeFromSegment(segment: string, size: HoneySizeProduct): string | null {
  if (size === "3L") {
    const p = segment.match(/3\s*L\s*Honey\s*\(\s*([LAD]{3})\s*\)/i);
    if (p && VALID_3L.has(p[1]!.toUpperCase())) return p[1]!.toUpperCase();
    const c = segment.match(/3L-HONEY\s*[:\s/]*([LAD]{3})/i);
    if (c && VALID_3L.has(c[1]!.toUpperCase())) return c[1]!.toUpperCase();
    if (/\bLAD\b/i.test(segment)) return "LAD";
    const allDark = /ALL\s*DARK|DDD/i.test(segment);
    if (allDark) return "DDD";
    const allAmber = /ALL\s*AMBER|AAA|ALLAMBER/i.test(segment);
    if (allAmber) return "AAA";
    const allLight = /ALL\s*LIGHT|LLL/i.test(segment);
    if (allLight) return "LLL";
  }
  if (size === "2L") {
    const p = segment.match(/2\s*L\s*Honey\s*\(\s*([LAD]{2})\s*\)/i);
    if (p && VALID_2L.has(p[1]!.toUpperCase())) return p[1]!.toUpperCase();
    const c = segment.match(/2L-HONEY\s*[:\s/]*([LAD]{2})/i);
    if (c && VALID_2L.has(c[1]!.toUpperCase())) return c[1]!.toUpperCase();
    if (/ALL\s*LIGHT/i.test(segment)) return "LL";
    if (/ALL\s*AMBER|ALLAMBER/i.test(segment)) return "AA";
    if (/ALL\s*DARK/i.test(segment)) return "DD";
  }
  if (size === "1L") {
    const p = segment.match(/1\s*L\s*Honey\s*\(\s*([LAD])\s*\)/i);
    if (p) return p[1]!.toUpperCase();
    const inlineWord = segment.match(/1\s*L\s*HONEY\s*:\s*(Light|Amber|Dark)\b/i);
    if (inlineWord) return inlineWord[1]![0]!.toUpperCase();
    const inlineCode = segment.match(/1\s*L\s*HONEY\s*:\s*([LAD])\b/i);
    if (inlineCode) return inlineCode[1]!.toUpperCase();
    const w = segment.match(/\b(Light|Amber|Dark)\b/i);
    if (w) return w[1]![0]!.toUpperCase();
  }
  if (size === "250ml" || size === "350ml") {
    const ml = size === "250ml" ? "250" : "350";
    const p = segment.match(new RegExp(`${ml}\\s*ml\\s*\\(\\s*([LAD]+)\\s*\\)`, "i"));
    if (p && /^[LAD]+$/i.test(p[1]!)) return p[1]!.toUpperCase();
    const wordInParen = segment.match(new RegExp(`${ml}\\s*ml\\s*\\(\\s*(Light|Amber|Dark)\\s*\\)`, "i"));
    if (wordInParen) return wordInParen[1]![0]!.toUpperCase();
    if (/\bLAD\b/i.test(segment)) return "LAD";
    const w = segment.match(/\b(Light|Amber|Dark)\b/i);
    if (w) return w[1]![0]!.toUpperCase();
  }
  if (size === "gallon") {
    const w = segment.match(/\b(Light|Amber|Dark)\b/i);
    if (w) return w[1]![0]!.toUpperCase();
  }
  return null;
}

function detectVariantsFromText(text: string, size: HoneySizeProduct): string | null {
  const t = stripHoneySizeTokens(normalizeVariantCountText(text)).toLowerCase();
  const hasLight = /\blights?\b/.test(t);
  const hasAmber = /\bambers?\b/.test(t);
  const hasDark = /\bdarks?\b/.test(t);
  if (size === "3L") {
    if (hasLight && hasAmber && hasDark) return "LAD";
    if (/1\s*amber.*2\s*dark|1\s*a.*2\s*d/i.test(t)) return "ADD";
    if (/2\s*amber.*1\s*dark|2\s*a.*1\s*d/i.test(t)) return "AAD";
    if (/2\s*dark.*1\s*amber|2\s*d.*1\s*a/i.test(t)) return "ADD";
    if (/2\s*light.*1\s*amber|2\s*lights.*1\s*amber/i.test(t)) return "LLA";
    if (/1\s*light.*2\s*amber|1\s*light.*2\s*ambers/i.test(t)) return "LAA";
    if (/2\s*light.*1\s*dark|2\s*lights.*1\s*dark/i.test(t)) return "LLD";
    if (hasAmber && !hasLight && !hasDark) return "AAA";
    if (hasLight && !hasAmber && !hasDark) return "LLL";
    if (hasDark && !hasLight && !hasAmber) return "DDD";
    if (hasAmber && hasDark) return null;
    if (/color\s*:\s*mix|\/\s*color\s*:\s*mix/i.test(t)) return null;
  }
  if (size === "2L") {
    if (hasLight && hasAmber && !hasDark) return "LA";
    if (hasLight && hasDark && !hasAmber) return "LD";
    if (hasAmber && hasDark && !hasLight) return "AD";
    if (/color\s*:\s*mix/i.test(t)) return null;
    if (hasLight && !hasAmber && !hasDark) return "LL";
  }
  if (size === "1L" || size === "gallon" || size === "250ml" || size === "350ml") {
    if (hasLight) return "L";
    if (hasAmber) return "A";
    if (hasDark) return "D";
  }
  return null;
}

function parseSegment(segment: string, variantHint = "", promoText = ""): BuilderLine | null {
  const dept = detectDeptProduct(segment);
  if (dept === "jar_cacao") {
    return { product: "jar_cacao", qty: extractCacaoJarQty(segment, promoText) };
  }
  if (dept && !/honey/i.test(stripCacaoTokens(segment))) {
    return { product: dept, qty: extractDeptQty(segment) };
  }

  const size = detectHoneySize(segment);
  if (size === "other") {
    if (dept) return { product: dept, qty: extractDeptQty(segment) };
    return null;
  }

  let variant = extractCodeFromSegment(segment, size);
  if (!variant && variantHint) {
    variant = detectVariantsFromText(variantHint, size);
  }
  if (!variant) {
    variant = detectVariantsFromText(segment, size);
  }

  const qty =
    isBulkMlProduct(size)
      ? extractQty(segment)
      : Math.max(1, extractQty(segment) === 3 && /B2T1|B1T2/i.test(segment) ? 1 : extractQty(segment));

  if (isBulkMlProduct(size)) {
    const bottleQty =
      promoBottleCount(segment) ??
      (/\d+\s*PC\s*(?:250|350)\s*ml/i.test(segment) ? extractQty(segment) : 1);
    return { product: size, variant: variant ?? undefined, qty: bottleQty };
  }

  if (dept && /jar\s*cacao|paragis|black\s*rice|honey\s*comb/i.test(segment)) {
    // combo segment handled at interpret level
  }

  return { product: size, variant: variant ?? undefined, qty: 1 };
}

export function interpretRawText(raw: string): InterpretResult {
  const empty: InterpretResult = {
    lines: [],
    packerLine: "",
    needsMainPick: false,
    needs350Pick: false,
    needsBuilder: false,
    needsDeptConfirm: false,
    needsCacaoHoneyManual: false,
    mainProduct: "other",
  };
  if (!raw?.trim()) return empty;

  let variantHint = "";
  const m1 = raw.match(/textarea_input_1:\s*([^\n]+)/i);
  if (m1) variantHint = m1[1]!.trim();

  const radioPromo = extractRadioPromoText(raw);

  const scanText = raw
    .replace(/<[^>]*>/g, "")
    .replace(/textarea_input_\d+:[^,\n]*/gi, "")
    .replace(/radio_input_\d+:[^,\n]*/gi, "");

  const segments = splitItemSegments(scanText);
  const lines: BuilderLine[] = [];
  let needsCacaoHoneyManual = false;

  for (const seg of segments) {
    const deptOnly = detectDeptProduct(seg);
    if (deptOnly === "jar_cacao") {
      lines.push({ product: "jar_cacao", qty: extractCacaoJarQty(seg, radioPromo) });
      if (detectCacaoHoneyCombo(seg, radioPromo)) {
        needsCacaoHoneyManual = true;
      }
      continue;
    }
    const hasHoney = /honey|250\s*ml|350\s*ml|gallon|g\s*honey|B2T1|B1T2|\d\s*L/i.test(
      stripCacaoTokens(seg),
    );
    if (deptOnly && !hasHoney) {
      lines.push({ product: deptOnly, qty: extractDeptQty(seg) });
      continue;
    }
    const line = parseSegment(seg, variantHint, radioPromo);
    if (line) lines.push(line);
  }

  const mainHoney = lines.find(l => l.product === "3L" || l.product === "2L" || l.product === "1L" || l.product === "gallon");
  const ml350 = lines.filter(l => l.product === "350ml");
  const mainProduct = (mainHoney?.product ?? lines.find(l => isHoneyBuilderProduct(l.product))?.product ?? "other") as HoneySizeProduct;

  let needsMainPick = false;
  let needs350Pick = false;
  for (const line of lines) {
    if (isHoneyBuilderProduct(line.product) && line.product !== "other") {
      if (!line.variant || !builderLineIsValid(line)) {
        if (isBulkMlProduct(line.product)) needs350Pick = true;
        else needsMainPick = true;
      }
    }
  }

  const packerLine = composePackerLine(lines);
  const needsDeptConfirm = lines.some(l => isDeptBuilderProduct(l.product));

  return {
    lines,
    packerLine,
    needsMainPick,
    needs350Pick,
    needsBuilder:
      needsMainPick ||
      needs350Pick ||
      lines.length === 0 ||
      needsDeptConfirm ||
      needsCacaoHoneyManual,
    needsDeptConfirm,
    needsCacaoHoneyManual,
    mainProduct,
  };
}

/** Scan raw VIP remark for cacao / dept lines (even when structured parse is empty). */
export function rawRemarkDeptFlags(raw: string): {
  needsDeptConfirm: boolean;
  needsCacaoHoneyManual: boolean;
} {
  if (!raw?.trim()) {
    return { needsDeptConfirm: false, needsCacaoHoneyManual: false };
  }
  const radioPromo = extractRadioPromoText(raw);
  const scanText = raw
    .replace(/<[^>]*>/g, "")
    .replace(/textarea_input_\d+:[^,\n]*/gi, "")
    .replace(/radio_input_\d+:[^,\n]*/gi, "");
  let needsDeptConfirm = false;
  let needsCacaoHoneyManual = false;
  for (const seg of splitItemSegments(scanText)) {
    if (detectDeptProduct(seg) === "jar_cacao") {
      needsDeptConfirm = true;
      if (detectCacaoHoneyCombo(seg, radioPromo)) {
        needsCacaoHoneyManual = true;
      }
    }
  }
  return { needsDeptConfirm, needsCacaoHoneyManual };
}
