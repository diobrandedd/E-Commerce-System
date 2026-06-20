import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { type ParcelPackTier } from "@/app/lib/parcelPackCosting";
import { costingCostBand } from "@/app/components/costing/costingParcelDisplay";
import { CostingMaterialLinesTable } from "@/app/components/costing/CostingMaterialLinesTable";
import { CostingParcelLandedBreakdown } from "@/app/components/costing/CostingParcelLandedBreakdown";
import { CostingOrderCalculator } from "@/app/components/costing/CostingOrderCalculator";
import { AppSectionEnter } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

export function CostingParcelRecipeDetailModal({
  tier,
  allTiers,
  onClose,
}: {
  tier: ParcelPackTier;
  allTiers: ParcelPackTier[];
  onClose: () => void;
}) {
  const placeholder = !tier.is_active;

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-3xl w-full max-h-[90vh] flex flex-col">
      <AppModalHeader
        title={tier.label}
        subtitle={placeholder ? "Tier not active yet" : `Key: ${tier.tier_key}`}
        onClose={onClose}
      />
      <AppModalBody className="overflow-y-auto space-y-5">
        <AppSectionEnter>
          {placeholder ? (
            <p className="text-sm text-muted-foreground">Rules and materials are not defined for this tier yet.</p>
          ) : (
            <>
              <div className="rounded-lg border border-border bg-[#C8900A]/[0.06] px-4 py-3">
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Packaging cost per parcel</p>
                <p className="text-xl font-bold text-foreground tabular-nums mt-1">
                  {costingCostBand(tier.cost_min, tier.cost_typical, tier.cost_max)}
                </p>
                <p className="text-xs text-muted-foreground mt-2">
                  Fulfillment materials only — see landed total below for products + packaging.
                </p>
              </div>
              <div>
                <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
                  Packaging materials
                </h3>
                <CostingMaterialLinesTable lines={tier.lines} showTotal />
              </div>
              <CostingParcelLandedBreakdown productCogs={tier.product_cogs} packagingTypical={tier.cost_typical} />
            </>
          )}
        </AppSectionEnter>

        {!placeholder && (
          <AppSectionEnter className={cn("border-t border-border pt-4")}>
            <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">
              Try an order
            </h3>
            <CostingOrderCalculator
              tiers={allTiers}
              initialTierKey={tier.tier_key}
              compact
            />
          </AppSectionEnter>
        )}
      </AppModalBody>
    </AppModal>
  );
}
