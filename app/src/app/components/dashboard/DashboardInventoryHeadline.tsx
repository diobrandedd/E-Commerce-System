import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2, Package } from "lucide-react";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { DashboardSectionShell } from "@/app/components/dashboard/DashboardSectionShell";
import { apiGet, ApiError } from "@/app/lib/api";

interface InventorySnapshot {
  honey_packaging: { total_bottles_on_hand: number };
  dept_products: { products: { key: string; label: string; total_qty: number }[] };
}

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

export function DashboardInventoryHeadline() {
  const [data, setData] = useState<InventorySnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<InventorySnapshot>("dashboard.php", "inventory_snapshot", { period: "all" });
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load inventory summary.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const honeyTotal = data?.honey_packaging.total_bottles_on_hand ?? 0;
  const products = data?.dept_products.products ?? [];

  return (
    <DashboardSectionShell
      title="Display stock"
      description="Packaged honey and finished goods on hand — open Inventory for full breakdown"
      index={2}
      action={
        <Link to="/inventory/products/honey" className="text-xs font-medium text-[#C8900A] hover:underline">
          Open inventory →
        </Link>
      }
    >
      <div className="px-5 sm:px-8 py-6 sm:py-7">
        <AppErrorBanner message={error} className="mb-4" />

        {loading ? (
          <div className="flex justify-center py-6 text-muted-foreground">
            <Loader2 size={22} className="animate-spin" />
          </div>
        ) : (
          <div className="flex flex-col items-center text-center gap-5">
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-xl bg-[#C8900A]/12 flex items-center justify-center">
                <Package size={20} className="text-[#C8900A]" />
              </div>
              <div>
                <p className="text-3xl sm:text-4xl font-bold text-foreground tabular-nums leading-none">
                  {fmtQty(honeyTotal)}
                </p>
                <p className="text-xs text-muted-foreground mt-1">Honey bottles on display</p>
              </div>
            </div>

            {products.length > 0 && (
              <div className="flex flex-wrap justify-center gap-2 max-w-lg">
                {products.map(p => (
                  <span
                    key={p.key}
                    className="inline-flex items-center gap-1.5 text-xs rounded-full border border-border bg-secondary/30 px-3 py-1.5"
                  >
                    <span className="text-muted-foreground">{p.label}</span>
                    <span className="font-semibold text-foreground tabular-nums">{fmtQty(p.total_qty)}</span>
                  </span>
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </DashboardSectionShell>
  );
}
