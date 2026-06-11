import type { DeptProductKey } from "@/app/lib/deptProducts";

export interface RawMaterialRoute {
  slug: string;
  productKey: DeptProductKey;
  title: string;
  subtitle: string;
  finishedProductPath: string;
  finishedTitle: string;
}

/** Maps URL slug under /inventory/raw-materials/:slug (dept leaf inputs) */
export const RAW_MATERIAL_ROUTES: Record<string, RawMaterialRoute> = {
  paragis: {
    slug: "paragis",
    productKey: "pt",
    title: "Dried Paragis",
    subtitle: "Raw bags, tea bag WIP, and production — stock in, make tea bags, pouch",
    finishedProductPath: "/inventory/products/paragis-tea",
    finishedTitle: "Paragis Tea",
  },
  "black-rice": {
    slug: "black-rice",
    productKey: "brc",
    title: "Black Rice",
    subtitle: "Raw bag inventory — stock in and produce finished pouches",
    finishedProductPath: "/inventory/products/black-rice-coffee",
    finishedTitle: "Black Rice Coffee",
  },
  cacao: {
    slug: "cacao",
    productKey: "jar_cacao",
    title: "Cacao Beans",
    subtitle: "Raw bag inventory — stock in and produce finished jars",
    finishedProductPath: "/inventory/products/cacao-tablets",
    finishedTitle: "Cacao Tablets",
  },
};

export interface ProductInventoryRoute {
  deptKey: string;
  productKey?: DeptProductKey;
  title: string;
  subtitle?: string;
  rawMaterialsPath?: string;
}

/** Maps URL slug under /inventory/products/:slug */
export const PRODUCT_INVENTORY_ROUTES: Record<string, ProductInventoryRoute> = {
  "paragis-tea": {
    deptKey: "paragis",
    productKey: "pt",
    title: "Paragis Tea",
    subtitle: "Finished sellable pouches on hand",
    rawMaterialsPath: "/inventory/raw-materials/paragis",
  },
  "black-rice-coffee": {
    deptKey: "cacao_brc",
    productKey: "brc",
    title: "Black Rice Coffee",
    subtitle: "Finished sellable pouches on hand",
    rawMaterialsPath: "/inventory/raw-materials/black-rice",
  },
  "cacao-tablets": {
    deptKey: "cacao_brc",
    productKey: "jar_cacao",
    title: "Cacao Tablets",
    subtitle: "Finished sellable jars on hand",
    rawMaterialsPath: "/inventory/raw-materials/cacao",
  },
};

/** Legacy dept slugs still routed to DeptInventoryPage */
export const LEGACY_DEPT_INVENTORY_ROUTES: Record<string, ProductInventoryRoute> = {
  paragis: { deptKey: "paragis", title: "Paragis" },
  "cacao-brc": { deptKey: "cacao_brc", title: "Cacao / BRC" },
  cacao_brc: { deptKey: "cacao_brc", title: "Cacao / BRC" },
};

export function resolveProductInventoryRoute(slug: string): ProductInventoryRoute | undefined {
  return PRODUCT_INVENTORY_ROUTES[slug] ?? LEGACY_DEPT_INVENTORY_ROUTES[slug];
}
