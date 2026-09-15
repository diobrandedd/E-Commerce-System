import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { fetchSupplierDeptPrices, saveSupplierDeptPrices } from "@/app/lib/deptPricing";
import {
  type DeptSupplierProductKey,
  deptSellUnitsPerBag,
  deptStockInYieldHint,
  deptProductByKey,
} from "@/app/lib/deptProducts";
import { ApiError } from "@/app/lib/api";

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export interface SupplierDeptPricesInlineProps {
  supplierId: number;
  supplierName: string;
  productKey: DeptSupplierProductKey;
}

export function SupplierDeptPricesInline({
  supplierId,
  supplierName,
  productKey,
}: SupplierDeptPricesInlineProps) {
  const product = deptProductByKey(productKey);
  const [effectiveFrom, setEffectiveFrom] = useState(new Date().toISOString().slice(0, 10));
  const [pricePerBag, setPricePerBag] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadPrices = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchSupplierDeptPrices(supplierId, productKey);
      const row = data.prices.find(p => p.supplier_id === supplierId && p.product_key === productKey);
      setPricePerBag(row ? String(row.price_per_unit) : "");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load prices.");
    } finally {
      setLoading(false);
    }
  }, [supplierId, productKey]);

  useEffect(() => {
    void loadPrices();
  }, [loadPrices]);

  const save = async () => {
    setSaving(true);
    setError(null);
    setNotice(null);
    try {
      await saveSupplierDeptPrices({
        supplier_id: supplierId,
        effective_from: effectiveFrom,
        products: [{ product_key: productKey, price_per_unit: pricePerBag }],
      });
      setNotice("Price saved. Raw costing stock and linked product costs updated.");
      await loadPrices();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save prices.");
    } finally {
      setSaving(false);
    }
  };

  const bagPrice = parseFloat(pricePerBag);
  const sellUnitsPerBag = deptSellUnitsPerBag(productKey);
  const costPerSellUnit =
    Number.isFinite(bagPrice) && bagPrice >= 0 && sellUnitsPerBag > 0
      ? bagPrice / sellUnitsPerBag
      : null;

  if (loading) {
    return (
      <p className="text-sm text-muted-foreground flex items-center gap-2">
        <Loader2 size={14} className="animate-spin" /> Loading {product?.label ?? productKey} prices…
      </p>
    );
  }

  return (
    <div className="space-y-3 max-w-xl">
      <p className="text-xs text-muted-foreground">
        <strong>{supplierName}</strong> — {product?.label ?? productKey} purchase price per{" "}
        {productKey === "honey_comb" ? "supplier bag" : "kg of raw leaf"}.{" "}
        {deptStockInYieldHint(productKey)}
      </p>
      {error && <p className="text-xs text-red-600">{error}</p>}
      {notice && <p className="text-xs text-green-700">{notice}</p>}
      <div className="grid grid-cols-2 gap-3 items-end">
        <div>
          <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
            Effective from
          </label>
          <input
            type="date"
            value={effectiveFrom}
            onChange={e => setEffectiveFrom(e.target.value)}
            className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-background"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
            {productKey === "honey_comb" ? "₱ / bag" : "₱ / kg"}
          </label>
          <input
            type="number"
            step="0.01"
            min="0"
            value={pricePerBag}
            onChange={e => setPricePerBag(e.target.value)}
            placeholder="0.00"
            className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-background"
          />
        </div>
      </div>
      {costPerSellUnit !== null && (
        <p className="text-xs text-muted-foreground">
          Raw cost ≈ {peso(costPerSellUnit)} per sell unit (before packaging stock on Costing).
        </p>
      )}
      <button
        type="button"
        onClick={() => void save()}
        disabled={saving || pricePerBag.trim() === ""}
        className="text-xs font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-50"
        style={{ background: "#C8900A" }}
      >
        {saving ? "Saving…" : `Save ${product?.label ?? productKey} price`}
      </button>
    </div>
  );
}
