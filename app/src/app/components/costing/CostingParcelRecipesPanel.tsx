import { useCallback, useEffect, useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import {
  fetchParcelTiers,
  type ParcelPackTier,
} from "@/app/lib/parcelPackCosting";
import {
  parcelRecipeDeptBucket,
  parcelRecipeDeptLabel,
  PARCEL_RECIPE_DEPT_LABELS,
  type ParcelRecipeDeptBucket,
} from "@/app/lib/parcelRecipeBuckets";
import { peso } from "@/app/lib/costingDisplay";
import { CostingParcelRecipeDetailModal } from "@/app/components/costing/CostingParcelRecipeDetailModal";
import { CostingOrderCalculator } from "@/app/components/costing/CostingOrderCalculator";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

type DeptFilter = "all" | ParcelRecipeDeptBucket;

const DEPT_CHIPS: { id: DeptFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "honey", label: PARCEL_RECIPE_DEPT_LABELS.honey },
  { id: "paragis", label: PARCEL_RECIPE_DEPT_LABELS.paragis },
  { id: "cacao_brc", label: PARCEL_RECIPE_DEPT_LABELS.cacao_brc },
];

const thCls =
  "px-4 py-2.5 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground";
const tdCls = "px-4 py-3 align-middle";

export function CostingParcelRecipesPanel() {
  const [tiers, setTiers] = useState<ParcelPackTier[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [deptFilter, setDeptFilter] = useState<DeptFilter>("all");
  const [selectedTier, setSelectedTier] = useState<ParcelPackTier | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetchParcelTiers();
      if (!res.ready) {
        setError("Parcel pack costing is not installed. Run migration phase 30.");
        setTiers([]);
        return;
      }
      setTiers(res.tiers);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load parcel packaging tiers.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const filtered = useMemo(() => {
    const list = [...tiers].sort((a, b) => a.sort_order - b.sort_order || a.label.localeCompare(b.label));
    if (deptFilter === "all") return list;
    return list.filter(t => parcelRecipeDeptBucket(t) === deptFilter);
  }, [tiers, deptFilter]);

  return (
    <div className="space-y-4 min-w-0">
      <div className="rounded-lg border border-border bg-secondary/20 px-4 py-3 text-xs text-muted-foreground">
        <strong className="text-foreground">Parcel packaging</strong> — ship materials + product COGS for one
        online order box. Click a row for breakdown; <strong className="text-foreground">Landed</strong> = products +
        packaging.
      </div>

      <CostingOrderCalculator tiers={tiers} />

      <div className="flex flex-wrap gap-2">
        {DEPT_CHIPS.map(chip => (
          <button
            key={chip.id}
            type="button"
            onClick={() => setDeptFilter(chip.id)}
            className={cn(
              "text-xs font-semibold px-3 py-1.5 rounded-full border transition-colors cursor-pointer",
              deptFilter === chip.id
                ? "bg-[#C8900A] text-white border-[#C8900A]"
                : "border-border text-muted-foreground hover:text-foreground hover:bg-secondary/40",
            )}
          >
            {chip.label}
          </button>
        ))}
      </div>

      {error && <AppErrorBanner message={error} />}

      {loading ? (
        <div className="flex justify-center py-12 text-muted-foreground gap-2">
          <Loader2 size={18} className="animate-spin" /> Loading…
        </div>
      ) : (
        <div
          className="rounded-xl border border-border overflow-x-auto bg-card"
          style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
        >
          <table className="w-full text-sm min-w-[40rem]">
            <thead className="bg-secondary/40 text-left">
              <tr>
                <th className={cn(thCls, "text-left min-w-[12rem]")}>Parcel</th>
                <th className={cn(thCls, "text-left hidden sm:table-cell w-[7rem]")}>Dept</th>
                <th className={cn(thCls, "text-right w-[5.5rem]")}>Pack</th>
                <th className={cn(thCls, "text-right hidden md:table-cell w-[5.5rem]")}>Products</th>
                <th className={cn(thCls, "text-right w-[5.5rem]")}>Landed</th>
                <th className={cn(thCls, "text-right hidden sm:table-cell w-[4rem]")}>Mats</th>
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                    No parcels in this filter.
                  </td>
                </tr>
              )}
              {filtered.map(tier => (
                <tr
                  key={tier.id}
                  className={cn(
                    "border-t border-border cursor-pointer transition-colors",
                    appHoverRow,
                    !tier.is_active && "opacity-60",
                  )}
                  onClick={() => setSelectedTier(tier)}
                >
                  <td className={tdCls}>
                    <p className="font-semibold text-foreground">{tier.label}</p>
                    <p className="text-[10px] font-mono text-muted-foreground mt-0.5">{tier.tier_key}</p>
                  </td>
                  <td className={cn(tdCls, "hidden sm:table-cell text-xs text-muted-foreground")}>
                    {parcelRecipeDeptLabel(tier)}
                  </td>
                  <td className={cn(tdCls, "text-right tabular-nums font-medium whitespace-nowrap")}>
                    {tier.is_active ? peso(tier.cost_typical) : "TBD"}
                  </td>
                  <td className={cn(tdCls, "hidden md:table-cell text-right tabular-nums text-muted-foreground whitespace-nowrap")}>
                    {tier.is_active && tier.product_cogs
                      ? peso(tier.product_cogs.products_total)
                      : "—"}
                  </td>
                  <td className={cn(tdCls, "text-right tabular-nums font-semibold text-foreground whitespace-nowrap")}>
                    {tier.is_active && tier.product_cogs
                      ? peso(tier.product_cogs.landed_typical)
                      : tier.is_active
                        ? peso(tier.cost_typical)
                        : "—"}
                  </td>
                  <td className={cn(tdCls, "hidden sm:table-cell text-right text-xs text-muted-foreground tabular-nums")}>
                    {tier.lines.length}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {selectedTier && (
        <CostingParcelRecipeDetailModal
          tier={selectedTier}
          allTiers={tiers}
          onClose={() => setSelectedTier(null)}
        />
      )}
    </div>
  );
}
