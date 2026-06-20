import { useCallback, useEffect, useState } from "react";
import { AlertCircle, Loader2 } from "lucide-react";
import {
  fetchSupplierHoneyPrices,
  gallonPriceToLiter,
  HONEY_LITERS_PER_US_GALLON,
  saveSupplierHoneyPrices,
  type HoneyPricingVariant,
} from "@/app/lib/honeyPricing";
import { ApiError } from "@/app/lib/api";

const VARIANTS: HoneyPricingVariant[] = ["light", "amber", "dark"];

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export interface SupplierHoneyPricesInlineProps {
  supplierId: number;
  supplierName: string;
  onPendingCountChange?: (count: number) => void;
}

export function SupplierHoneyPricesInline({
  supplierId,
  supplierName,
  onPendingCountChange,
}: SupplierHoneyPricesInlineProps) {
  const [effectiveFrom, setEffectiveFrom] = useState(new Date().toISOString().slice(0, 10));
  const [prices, setPrices] = useState<Record<HoneyPricingVariant, string>>({
    light: "",
    amber: "",
    dark: "",
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadPrices = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchSupplierHoneyPrices(supplierId);
      onPendingCountChange?.(data.pending_count);
      const next: Record<HoneyPricingVariant, string> = { light: "", amber: "", dark: "" };
      for (const p of data.prices) {
        if (p.supplier_id === supplierId) {
          next[p.variant] = String(p.price_per_gallon);
        }
      }
      setPrices(next);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load prices.");
    } finally {
      setLoading(false);
    }
  }, [supplierId, onPendingCountChange]);

  useEffect(() => {
    void loadPrices();
  }, [loadPrices]);

  const save = async () => {
    setSaving(true);
    setError(null);
    setNotice(null);
    try {
      const result = await saveSupplierHoneyPrices({
        supplier_id: supplierId,
        effective_from: effectiveFrom,
        variants: VARIANTS.filter(v => prices[v].trim() !== "").map(v => ({
          variant: v,
          price_per_gallon: prices[v],
        })),
      });
      onPendingCountChange?.(result.pending_count);
      const msg =
        result.repriced_rows > 0
          ? `Prices saved. Costing stocks updated; ${result.repriced_rows} pending warehouse row(s) priced.`
          : "Prices saved. Costing stocks and linked product costs updated.";
      setNotice(msg);
      await loadPrices();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save prices.");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="py-4 text-center text-sm text-muted-foreground">
        <Loader2 className="inline animate-spin mr-2" size={14} />
        Loading prices…
      </div>
    );
  }

  return (
    <div className="space-y-3">
      <p className="text-xs text-muted-foreground">
        <span className="font-semibold text-foreground">{supplierName}</span> — purchase ₱/gallon per variant.
        Warehouse stock-in uses the rate effective on arrival date.
      </p>
      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex gap-2">
          <AlertCircle size={14} className="flex-shrink-0 mt-0.5" />
          <span>{error}</span>
        </div>
      )}
      {notice && (
        <div className="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-700">{notice}</div>
      )}
      <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold block max-w-xs">
        Effective from
        <input
          type="date"
          value={effectiveFrom}
          onChange={e => setEffectiveFrom(e.target.value)}
          className="mt-1 block w-full text-sm border rounded-lg px-2 py-1.5 bg-card"
        />
      </label>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        {VARIANTS.map(v => {
          const gal = parseFloat(prices[v]);
          const literHint = Number.isFinite(gal) && gal > 0
            ? `≈ ${peso(gallonPriceToLiter(gal))}/L · 250ml ≈ ${peso(gallonPriceToLiter(gal) * 0.25)}`
            : null;
          const bottlesPerGal = HONEY_LITERS_PER_US_GALLON / 0.25;
          return (
            <label key={v} className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
              {v} — ₱/gallon
              <input
                type="number"
                step="0.01"
                min="0"
                value={prices[v]}
                onChange={e => setPrices(p => ({ ...p, [v]: e.target.value }))}
                className="mt-1 block w-full text-sm border rounded-lg px-2 py-1.5 bg-card"
                placeholder="0.00"
              />
                    {literHint && (
                      <span className="block mt-0.5 text-[10px] font-normal normal-case text-muted-foreground">
                        {literHint}
                        <span className="block">1 gal ≈ {bottlesPerGal.toFixed(1)}× 250ml bottles</span>
                      </span>
                    )}
            </label>
          );
        })}
      </div>
      <button
        type="button"
        disabled={saving}
        onClick={() => void save()}
        className="text-xs font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-60"
        style={{ background: "#C8900A" }}
      >
        {saving ? "Saving…" : "Save prices"}
      </button>
    </div>
  );
}
