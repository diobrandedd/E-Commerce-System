import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2, Package } from "lucide-react";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { DashboardSectionShell } from "@/app/components/dashboard/DashboardSectionShell";
import { StockInsTrendChart } from "@/app/components/dashboard/StockInsTrendChart";
import { apiGet, ApiError } from "@/app/lib/api";

interface InventorySnapshot {
  honey_packaging: { total_bottles_on_hand: number };
}

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

/**
 * Exec dashboard stock strip: compact on-hand headline + daily stock-ins quick chart.
 * Full pool / size mix stays on Inventory product pages — not duplicated here.
 */
export function DashboardInventoryHeadline() {
  const [honeyTotal, setHoneyTotal] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<InventorySnapshot>("dashboard.php", "inventory_snapshot", {
        period: "all",
      });
      setHoneyTotal(payload.honey_packaging.total_bottles_on_hand ?? 0);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load inventory summary.");
      setHoneyTotal(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <DashboardSectionShell
      title="Stock"
      description="On-hand honey + daily finished stock-ins — open Inventory for pool detail"
      index={2}
      action={
        <Link
          to="/inventory/products/honey"
          className="text-xs font-medium text-[#C8900A] hover:underline"
        >
          Open inventory →
        </Link>
      }
    >
      <div className="px-4 sm:px-6 py-5 sm:py-6 space-y-5">
        <AppErrorBanner message={error} />

        <div className="flex items-center gap-3 rounded-xl border border-border bg-secondary/30 px-4 py-3">
          <div className="w-9 h-9 rounded-lg bg-[#C8900A]/12 flex items-center justify-center shrink-0">
            <Package size={18} className="text-[#C8900A]" />
          </div>
          <div className="min-w-0">
            {loading && honeyTotal == null ? (
              <Loader2 size={18} className="animate-spin text-muted-foreground" />
            ) : (
              <>
                <p className="text-2xl font-bold text-foreground tabular-nums leading-none">
                  {fmtQty(honeyTotal ?? 0)}
                </p>
                <p className="text-xs text-muted-foreground mt-1">Honey bottles on display</p>
              </>
            )}
          </div>
          <Link
            to="/inventory/products/honey"
            className="ml-auto text-xs font-medium text-[#C8900A] hover:underline shrink-0"
          >
            Honey pool →
          </Link>
        </div>

        <StockInsTrendChart mode="quick" defaultFamily="all" defaultDays={7} />
      </div>
    </DashboardSectionShell>
  );
}
