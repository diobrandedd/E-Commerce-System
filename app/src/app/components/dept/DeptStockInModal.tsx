import { useEffect, useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  deptProductUsesSupplierPricing,
  deptSellUnitsPerBag,
  deptStockInYieldHint,
  deptSupplierCategoryForProduct,
  type DeptSupplierProductKey,
} from "@/app/lib/deptProducts";
import type { DeptInventoryRow } from "@/app/lib/deptInventory";
import { apiPost, ApiError } from "@/app/lib/api";
import { listSuppliers, type Supplier, type SupplierCategory } from "@/app/lib/suppliers";

function supplierCategoryForProductKey(productKey: string): SupplierCategory | null {
  if (productKey === "pt" || productKey === "brc" || productKey === "jar_cacao" || productKey === "honey_comb") {
    return deptSupplierCategoryForProduct(productKey as DeptSupplierProductKey);
  }
  return null;
}

interface DeptStockInModalProps {
  deptKey: string;
  products: DeptInventoryRow[];
  lockProductKey?: string;
  onClose: () => void;
  onDone: () => void;
}

export function DeptStockInModal({
  deptKey,
  products,
  lockProductKey,
  onClose,
  onDone,
}: DeptStockInModalProps) {
  const catalog = products.filter(p => p.dept_name === deptKey || deptKey === "");
  const [productKey, setProductKey] = useState(lockProductKey ?? catalog[0]?.product_key ?? "");
  const [qty, setQty] = useState("");
  const [unitCost, setUnitCost] = useState("");
  const [supplierId, setSupplierId] = useState("");
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [suppliersLoading, setSuppliersLoading] = useState(false);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [note, setNote] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selected = catalog.find(p => p.product_key === productKey);
  const usesSupplier = deptProductUsesSupplierPricing(productKey) || selected?.uses_supplier_pricing;
  const sellUnitsPerBag =
    selected?.sell_units_per_bag ?? selected?.pouches_per_bag ??
    (productKey === "pt" || productKey === "brc" || productKey === "jar_cacao" || productKey === "honey_comb"
      ? deptSellUnitsPerBag(productKey)
      : 1);
  const inventoryUnitLabel = selected?.inventory_unit ?? (usesSupplier ? "unit" : "pcs");
  const supplierCategory = supplierCategoryForProductKey(productKey);
  const bags = parseFloat(qty);
  const sellUnitPreview =
    usesSupplier && Number.isFinite(bags) && bags > 0
      ? (bags * Number(sellUnitsPerBag)).toFixed(2)
      : null;

  useEffect(() => {
    if (lockProductKey) setProductKey(lockProductKey);
  }, [lockProductKey]);

  useEffect(() => {
    if (!usesSupplier || !supplierCategory) {
      setSuppliers([]);
      setSupplierId("");
      return;
    }
    setSuppliersLoading(true);
    setSupplierId("");
    void listSuppliers({ category: supplierCategory, active: true })
      .then(data => {
        setSuppliers(data.suppliers);
        if (data.suppliers.length === 1) {
          setSupplierId(String(data.suppliers[0].id));
        }
      })
      .catch(() => setSuppliers([]))
      .finally(() => setSuppliersLoading(false));
  }, [usesSupplier, supplierCategory, productKey]);

  const stockInUnit = useMemo(() => {
    if (selected?.stock_in_unit) return selected.stock_in_unit;
    return usesSupplier ? "bag" : "pcs";
  }, [selected, usesSupplier]);

  const yieldHint =
    productKey === "pt" || productKey === "brc" || productKey === "jar_cacao" || productKey === "honey_comb"
      ? deptStockInYieldHint(productKey)
      : null;

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, unknown> = {
        product_key: productKey,
        qty: parseFloat(qty),
        arrival_date: arrival,
        note: note.trim() || undefined,
      };
      if (usesSupplier) {
        payload.supplier_id = parseInt(supplierId, 10);
      } else {
        payload.unit_cost = parseFloat(unitCost);
      }
      await apiPost("honey.php", "dept_stock_in", payload);
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not save stock-in.");
    } finally {
      setSaving(false);
    }
  };

  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";
  const supplierPlaceholder =
    supplierCategory === "black_rice"
      ? "Select black rice supplier…"
      : supplierCategory === "cacao"
        ? "Select cacao supplier…"
        : "Select paragis supplier…";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="dept-stock-in-title">
      <AppModalHeader title="Stock in" titleId="dept-stock-in-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <p className="text-xs text-red-600">{error}</p>}
          {!lockProductKey && (
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Product</label>
              <select value={productKey} onChange={e => setProductKey(e.target.value)} required className={inputCls}>
                {catalog.map(p => (
                  <option key={p.product_key} value={p.product_key}>{p.product_label}</option>
                ))}
              </select>
            </div>
          )}
          {usesSupplier ? (
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier</label>
              {suppliersLoading ? (
                <p className="text-xs text-muted-foreground flex items-center gap-1">
                  <Loader2 size={12} className="animate-spin" /> Loading suppliers…
                </p>
              ) : (
                <select
                  value={supplierId}
                  onChange={e => setSupplierId(e.target.value)}
                  required
                  className={inputCls}
                >
                  <option value="">{supplierPlaceholder}</option>
                  {suppliers.map(s => (
                    <option key={s.id} value={s.id}>{s.display_name}</option>
                  ))}
                </select>
              )}
              {yieldHint && (
                <p className="text-xs text-muted-foreground mt-1">
                  Price from Supplier Master (₱/bag). {yieldHint}
                </p>
              )}
            </div>
          ) : null}
          <div className={usesSupplier ? "" : "grid grid-cols-2 gap-2"}>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
                Quantity ({stockInUnit}{usesSupplier ? "s" : ""})
              </label>
              <input type="number" step={usesSupplier ? "0.01" : "1"} min="0.01" value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
              {sellUnitPreview && (
                <p className="text-xs text-muted-foreground mt-1">
                  ≈ {sellUnitPreview} {inventoryUnitLabel}(s) after Produce (raw bags only on stock-in)
                </p>
              )}
            </div>
            {!usesSupplier && (
              <div>
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Unit cost (₱)</label>
                <input type="number" step="0.01" min="0" value={unitCost} onChange={e => setUnitCost(e.target.value)} required className={inputCls} />
              </div>
            )}
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Arrival date</label>
            <input type="date" value={arrival} onChange={e => setArrival(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button
            type="submit"
            disabled={saving || (usesSupplier && !supplierId)}
            className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50"
            style={{ background: "#C8900A" }}
          >
            {saving ? "Saving…" : "Stock in"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}
