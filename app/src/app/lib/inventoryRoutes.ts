import type { DeptProductKey } from "@/app/lib/deptProducts";

export interface ProductInventoryRoute {
  deptKey: string;
  productKey?: DeptProductKey;
  title: string;
  subtitle?: string;
}

/** Maps URL slug under /inventory/products/:slug */
export const PRODUCT_INVENTORY_ROUTES: Record<string, ProductInventoryRoute> = {
  "paragis-tea": {
    deptKey: "paragis",
    productKey: "pt",
    title: "Paragis Tea",
    subtitle: "Finished sellable pouches",
  },
  "black-rice-coffee": {
    deptKey: "cacao_brc",
    productKey: "brc",
    title: "Black Rice Coffee",
    subtitle: "Finished sellable pouches",
  },
  "cacao-tablets": {
    deptKey: "cacao_brc",
    productKey: "jar_cacao",
    title: "Cacao Tablets",
    subtitle: "Finished sellable jars",
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
