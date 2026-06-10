export const DEPT_PRODUCT_KEYS = ["pt", "brc", "jar_cacao"] as const;

export type DeptProductKey = (typeof DEPT_PRODUCT_KEYS)[number];

/** Supplier bag = 1 kg leaf. */
export const PT_GRAMS_PER_BAG = 1000;
export const PT_GRAMS_PER_TEA_BAG = 2;
export const PT_TEA_BAGS_PER_POUCH = 24;

export const PT_LEAF_STOCK_NAME = "Paragis leaf — raw";
export const PT_PACKAGING_STOCK_NAME = "PT pouch packaging";

export const BRC_GRAMS_PER_BAG = 20000;
export const BRC_GRAMS_PER_POUCH = 100;

export const BRC_RAW_STOCK_NAME = "Black Rice Coffee — raw";
export const BRC_PACKAGING_STOCK_NAME = "BRC pouch packaging";

/** Supplier bag = 1 kg; one bag → one sellable jar. */
export const JAR_CACAO_GRAMS_PER_BAG = 1000;
export const JAR_CACAO_GRAMS_PER_JAR = 1000;

export const JAR_CACAO_RAW_STOCK_NAME = "Jar Cacao — raw";
export const JAR_CACAO_PACKAGING_STOCK_NAME = "Jar Cacao jar packaging";

export function ptTeaBagsPerBag(): number {
  return PT_GRAMS_PER_BAG / PT_GRAMS_PER_TEA_BAG;
}

export function ptPouchesPerBag(): number {
  return ptTeaBagsPerBag() / PT_TEA_BAGS_PER_POUCH;
}

export function ptBagsPerPouch(): number {
  return PT_TEA_BAGS_PER_POUCH / ptTeaBagsPerBag();
}

export function brcPouchesPerBag(): number {
  return BRC_GRAMS_PER_BAG / BRC_GRAMS_PER_POUCH;
}

export function brcBagsPerPouch(): number {
  return BRC_GRAMS_PER_POUCH / BRC_GRAMS_PER_BAG;
}

export function jarCacaoJarsPerBag(): number {
  return JAR_CACAO_GRAMS_PER_BAG / JAR_CACAO_GRAMS_PER_JAR;
}

export function jarCacaoBagsPerJar(): number {
  return JAR_CACAO_GRAMS_PER_JAR / JAR_CACAO_GRAMS_PER_BAG;
}

export function deptSellUnitsPerBag(key: DeptProductKey): number {
  if (key === "pt") return ptPouchesPerBag();
  if (key === "brc") return brcPouchesPerBag();
  return jarCacaoJarsPerBag();
}

/** @deprecated use deptSellUnitsPerBag */
export function deptPouchesPerBag(key: DeptProductKey): number {
  return deptSellUnitsPerBag(key);
}

export function deptBagsPerSellUnit(key: DeptProductKey): number {
  if (key === "pt") return ptBagsPerPouch();
  if (key === "brc") return brcBagsPerPouch();
  return jarCacaoBagsPerJar();
}

export interface DeptProductDef {
  key: DeptProductKey;
  label: string;
  code: string;
  dept: string;
  unit: string;
  stockInUnit: string;
  inventoryUnit: string;
  usesSupplierPricing: boolean;
}

export const DEPT_PRODUCT_CATALOG: DeptProductDef[] = [
  {
    key: "pt",
    label: "Paragis Tea",
    code: "PT",
    dept: "paragis",
    unit: "pouch",
    stockInUnit: "bag",
    inventoryUnit: "pouch",
    usesSupplierPricing: true,
  },
  {
    key: "brc",
    label: "Black Rice Coffee",
    code: "BRC",
    dept: "cacao_brc",
    unit: "pouch",
    stockInUnit: "bag",
    inventoryUnit: "pouch",
    usesSupplierPricing: true,
  },
  {
    key: "jar_cacao",
    label: "Jar Cacao",
    code: "",
    dept: "cacao_brc",
    unit: "pcs",
    stockInUnit: "bag",
    inventoryUnit: "pcs",
    usesSupplierPricing: true,
  },
];

export const DEPT_LABELS: Record<string, string> = {
  paragis: "Paragis",
  cacao_brc: "Cacao / BRC",
};

export function deptProductByKey(key: string): DeptProductDef | undefined {
  return DEPT_PRODUCT_CATALOG.find(p => p.key === key);
}

export function deptProductsForRoute(deptRoute: string): DeptProductDef[] {
  const dept =
    deptRoute === "cacao-brc" || deptRoute === "cacao_brc" ? "cacao_brc" : deptRoute;
  return DEPT_PRODUCT_CATALOG.filter(p => p.dept === dept);
}

export function deptProductUsesSupplierPricing(key: string): boolean {
  return deptProductByKey(key)?.usesSupplierPricing ?? false;
}

export type DeptSupplierProductKey = "pt" | "brc" | "jar_cacao";

export function deptSupplierCategoryForProduct(
  key: DeptSupplierProductKey,
): "paragis" | "black_rice" | "cacao" {
  if (key === "pt") return "paragis";
  if (key === "brc") return "black_rice";
  return "cacao";
}

export function formatDeptFormulaQty(n: number): string {
  return n.toFixed(8).replace(/\.?0+$/, "");
}

export function deptSuggestedFormulaQty(
  productKey: string | null | undefined,
  stockName: string,
): string | null {
  if (!productKey) return null;
  if (productKey === "pt") {
    if (stockName === PT_LEAF_STOCK_NAME) return formatDeptFormulaQty(ptBagsPerPouch());
    if (stockName === PT_PACKAGING_STOCK_NAME) return "1";
  }
  if (productKey === "brc") {
    if (stockName === BRC_RAW_STOCK_NAME) return formatDeptFormulaQty(brcBagsPerPouch());
    if (stockName === BRC_PACKAGING_STOCK_NAME) return "1";
  }
  if (productKey === "jar_cacao") {
    if (stockName === JAR_CACAO_RAW_STOCK_NAME) return formatDeptFormulaQty(jarCacaoBagsPerJar());
    if (stockName === JAR_CACAO_PACKAGING_STOCK_NAME) return "1";
  }
  return null;
}

export function deptDefaultFormulaStockName(productKey: string | null | undefined): string | null {
  if (productKey === "pt") return PT_LEAF_STOCK_NAME;
  if (productKey === "brc") return BRC_RAW_STOCK_NAME;
  if (productKey === "jar_cacao") return JAR_CACAO_RAW_STOCK_NAME;
  return null;
}

export function deptStockInYieldHint(key: DeptSupplierProductKey): string {
  if (key === "pt") {
    return `1 bag (1 kg) → ${ptTeaBagsPerBag().toLocaleString()} tea bags @ 2 g → ~${ptPouchesPerBag().toFixed(2)} pouches (${PT_TEA_BAGS_PER_POUCH} tea bags per pouch).`;
  }
  if (key === "brc") {
    return `1 bag (20 kg) → ${brcPouchesPerBag().toLocaleString()} sellable pouches (${BRC_GRAMS_PER_POUCH} g per pouch).`;
  }
  return `1 bag (1 kg) → ${jarCacaoJarsPerBag().toLocaleString()} sellable jar(s) (${JAR_CACAO_GRAMS_PER_JAR} g per jar).`;
}
