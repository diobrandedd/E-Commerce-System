import { useState } from "react";
import { Calculator, Loader2 } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { ApiError } from "@/app/lib/api";
import {
  previewParcelCost,
  type ParcelCostPreview,
  type ParcelPackTier,
} from "@/app/lib/parcelPackCosting";
import { costingCostBand } from "@/app/components/costing/costingParcelDisplay";
import { CostingMaterialLinesTable } from "@/app/components/costing/CostingMaterialLinesTable";
import { CostingParcelLandedBreakdown } from "@/app/components/costing/CostingParcelLandedBreakdown";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

export function CostingOrderCalculator({
  tiers = [],
  initialItemsRaw = "",
  initialTierKey = "",
  compact = false,
}: {
  tiers?: ParcelPackTier[];
  initialItemsRaw?: string;
  initialTierKey?: string;
  compact?: boolean;
}) {
  const [itemsRaw, setItemsRaw] = useState(initialItemsRaw);
  const [tierKey, setTierKey] = useState(initialTierKey);
  const [preview, setPreview] = useState<ParcelCostPreview | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const activeTiers = tiers.filter(t => t.is_active);

  const run = async () => {
    setBusy(true);
    setError(null);
    try {
      const res = tierKey
        ? await previewParcelCost({ tier_key: tierKey })
        : await previewParcelCost({ items_raw: itemsRaw });
      setPreview(res);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Preview failed.");
      setPreview(null);
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className={cn("space-y-3", compact && "space-y-2")}>
      <p className="text-xs text-muted-foreground">
        Paste a real <strong className="text-foreground">Item/s</strong> string from an online parcel, or pick a
        parcel type to preview packaging cost for that order shape.
      </p>
      <textarea
        value={itemsRaw}
        onChange={e => {
          setItemsRaw(e.target.value);
          setTierKey("");
        }}
        rows={compact ? 2 : 3}
        disabled={!!tierKey}
        className={cn(
          "w-full rounded-lg border-2 border-border bg-background px-3 py-2.5 text-sm font-mono",
          "focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 focus:outline-none",
          tierKey && "opacity-50",
        )}
        placeholder="e.g. 2x 1L Amber Honey; 3x Paragis Tea"
      />
      <div className="flex flex-wrap gap-2 items-center">
        <select
          value={tierKey}
          onChange={e => setTierKey(e.target.value)}
          className="flex-1 min-w-[10rem] rounded-lg border-2 border-border bg-background px-3 py-2 text-sm focus:border-[#C8900A] focus:outline-none"
        >
          <option value="">Parse Item/s above</option>
          {activeTiers.map(t => (
            <option key={t.tier_key} value={t.tier_key}>
              {t.label}
            </option>
          ))}
        </select>
        <Button type="button" onClick={() => void run()} disabled={busy || (!tierKey && !itemsRaw.trim())}>
          {busy ? <Loader2 size={14} className="animate-spin" /> : <Calculator size={14} />}
          Calculate
        </Button>
      </div>
      {error && <AppErrorBanner message={error} />}
      {preview && (
        <div className="rounded-lg border border-border bg-secondary/15 p-3 space-y-3">
          <div>
            <p className="text-lg font-bold text-foreground tabular-nums">
              {costingCostBand(preview.cost_min, preview.cost_typical, preview.cost_max)}
            </p>
            {(preview.tier_key ?? preview.tier_keys?.[0]) && (
              <p className="text-xs text-muted-foreground mt-1">
                Matched: <span className="font-mono">{preview.tier_key ?? preview.tier_keys?.join(", ")}</span>
              </p>
            )}
          </div>
          {preview.tiers.map(t => (
            <div key={t.tier_key}>
              <p className="text-xs font-semibold text-foreground mb-2">Packaging — {t.label}</p>
              <CostingMaterialLinesTable lines={t.lines} showTotal />
            </div>
          ))}
          <CostingParcelLandedBreakdown
            productCogs={preview.product_cogs}
            packagingTypical={preview.cost_typical}
            compact
          />
          {preview.warnings?.map(w => (
            <p key={w} className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1">
              {w}
            </p>
          ))}
        </div>
      )}
    </div>
  );
}
