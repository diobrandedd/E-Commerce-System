import { apiGet, apiPost } from "@/app/lib/api";

export type HoneyPricingVariant = "light" | "amber" | "dark";

/** US fluid gallon in liters — match PHP HONEY_LITERS_PER_US_GALLON */
export const HONEY_LITERS_PER_US_GALLON = 3785.411784 / 1000;

/** Raw honey liters per finished bottle (formula qty on Raw Honey — {Variant}). */
export const HONEY_FORMULA_LITERS_BY_SIZE: Record<string, number> = {
  "250ml": 0.25,
  "350ml": 0.35,
  liter: 1.0,
  gallon: HONEY_LITERS_PER_US_GALLON,
};

export function gallonPriceToLiter(pricePerGallon: number): number {
  return pricePerGallon / HONEY_LITERS_PER_US_GALLON;
}

export interface SupplierHoneyPrice {
  supplier_id: number;
  variant: HoneyPricingVariant;
  price_per_gallon: number;
  effective_from: string;
  id?: number;
}

export interface WarehousePricingRow {
  id: number;
  supplier_id?: number;
  supplier_name: string;
  supplier_code: string;
  arrival_date: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
  unit_cost_per_gallon?: number | null;
  pricing_status?: "priced" | "pending";
  cost_override?: boolean;
  inventory_value?: number | null;
}

export function fetchSupplierHoneyPrices(supplierId?: number) {
  return apiGet<{
    prices: SupplierHoneyPrice[];
    history: Array<SupplierHoneyPrice & { supplier_name: string }>;
    pending_count: number;
  }>("honey.php", "supplier_honey_prices", supplierId ? { supplier_id: String(supplierId) } : undefined);
}

export function saveSupplierHoneyPrices(payload: {
  supplier_id: number;
  effective_from: string;
  variants: Array<{ variant: HoneyPricingVariant; price_per_gallon: string | number }>;
}) {
  return apiPost<{
    saved: SupplierHoneyPrice[];
    repriced_rows: number;
    pending_count: number;
  }>("honey.php", "supplier_honey_prices_save", payload);
}

export function fetchPendingWarehousePrices() {
  return apiGet<{ rows: WarehousePricingRow[]; pending_count: number }>(
    "honey.php",
    "warehouse_pending_prices",
  );
}

export function setWarehouseUnitCost(warehouseRowId: number, unitCostPerGallon: number) {
  return apiPost<{ row: WarehousePricingRow; pending_count: number }>(
    "honey.php",
    "warehouse_set_unit_cost",
    { warehouse_row_id: warehouseRowId, unit_cost_per_gallon: unitCostPerGallon },
  );
}

export function syncHoneyStockCosts() {
  return apiPost<{ synced: Record<string, number> }>("honey.php", "pricing_sync_stocks", {});
}
