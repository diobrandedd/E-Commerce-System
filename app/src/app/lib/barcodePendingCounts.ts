export interface LabelPendingCounts {
  bottles: number;
  pt: number;
  brc: number;
  jar_cacao: number;
  honey_comb: number;
}

export const EMPTY_LABEL_COUNTS: LabelPendingCounts = {
  bottles: 0,
  pt: 0,
  brc: 0,
  jar_cacao: 0,
  honey_comb: 0,
};

export function labelCountSum(counts: LabelPendingCounts): number {
  return counts.bottles + counts.pt + counts.brc + counts.jar_cacao + counts.honey_comb;
}

function deltaPart(n: number, singular: string, plural: string): string | null {
  if (n <= 0) return null;
  return `${n} ${n === 1 ? singular : plural}`;
}

/** Describe new pending barcode requests since the last snapshot. */
export function barcodePendingIncrease(
  prev: LabelPendingCounts,
  next: LabelPendingCounts,
): { body: string; href: string } | null {
  const parts = [
    { n: next.bottles - prev.bottles, one: "honey bottle", many: "honey bottles", href: "/inventory/raw-materials/honey/production?labels=1" },
    { n: next.pt - prev.pt, one: "Paragis pouch", many: "Paragis pouches", href: "/inventory/raw-materials/paragis" },
    { n: next.brc - prev.brc, one: "Black Rice pouch", many: "Black Rice pouches", href: "/inventory/raw-materials/black-rice" },
    { n: next.jar_cacao - prev.jar_cacao, one: "Cacao jar", many: "Cacao jars", href: "/inventory/raw-materials/cacao" },
    { n: next.honey_comb - prev.honey_comb, one: "Honey Comb jar", many: "Honey Comb jars", href: "/inventory/raw-materials/honey/packaging#honey-comb-barcodes" },
  ];
  const bits: string[] = [];
  let href = parts[0]!.href;
  for (const p of parts) {
    const label = deltaPart(p.n, p.one, p.many);
    if (!label) continue;
    if (bits.length === 0) href = p.href;
    bits.push(label);
  }
  if (bits.length === 0) return null;
  return { body: `New barcode request: ${bits.join(", ")}`, href };
}
