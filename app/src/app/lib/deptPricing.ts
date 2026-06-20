import { apiGet, apiPost } from "@/app/lib/api";
import type { DeptProductKey } from "@/app/lib/deptProducts";

export interface SupplierDeptPrice {
  supplier_id: number;
  product_key: DeptProductKey;
  price_per_unit: number;
  effective_from: string;
  id?: number;
}

export function fetchSupplierDeptPrices(
  supplierId?: number,
  productKey?: Extract<DeptProductKey, "pt" | "brc" | "jar_cacao">,
) {
  return apiGet<{
    prices: SupplierDeptPrice[];
    history: Array<SupplierDeptPrice & { supplier_name: string }>;
  }>("honey.php", "supplier_dept_prices", {
    supplier_id: supplierId ? String(supplierId) : undefined,
    product_key: productKey,
  });
}

export function saveSupplierDeptPrices(payload: {
  supplier_id: number;
  effective_from: string;
  products: Array<{ product_key: DeptProductKey; price_per_unit: string | number }>;
}) {
  return apiPost<{ saved: SupplierDeptPrice[] }>("honey.php", "supplier_dept_prices_save", payload);
}
