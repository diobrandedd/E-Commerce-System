import type { ParcelTierProductCogs } from "@/app/lib/parcelPackCosting";
import { peso } from "@/app/lib/costingDisplay";
import { cn } from "@/app/components/ui/utils";

const thCls =
  "text-[10px] font-semibold uppercase tracking-wide text-muted-foreground text-left py-2 px-3";
const thRightCls = cn(thCls, "text-right");

export function CostingParcelLandedBreakdown({
  productCogs,
  packagingTypical,
  compact = false,
}: {
  productCogs: ParcelTierProductCogs | null | undefined;
  packagingTypical?: number;
  compact?: boolean;
}) {
  const packaging =
    productCogs?.packaging_typical ??
    packagingTypical ??
    0;
  const productsTotal = productCogs?.products_total ?? 0;
  const landed = productCogs?.landed_typical ?? productsTotal + packaging;
  const lines = productCogs?.lines ?? [];

  return (
    <div className={cn("space-y-4", compact && "space-y-3")}>
      {lines.length > 0 && (
        <div>
          <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
            Product COGS in this parcel
          </h3>
          {productCogs?.honey_variant_ref && lines.some(l => l.label.toLowerCase().includes("honey")) && (
            <p className="text-[11px] text-muted-foreground mb-2">
              Honey lines use <strong className="text-foreground">{productCogs.honey_variant_ref}</strong> variant
              costing (same qty as tier match rule).
            </p>
          )}
          <div className="overflow-x-auto">
            <table className="w-full text-sm border-collapse min-w-[24rem]">
              <thead>
                <tr className="border-b border-border bg-secondary/30">
                  <th className={thCls}>Product</th>
                  <th className={thRightCls}>Qty</th>
                  <th className={thRightCls}>Unit COGS</th>
                  <th className={thRightCls}>Line ₱</th>
                </tr>
              </thead>
              <tbody>
                {lines.map((line, i) => (
                  <tr key={`${line.label}-${i}`} className="border-b border-border last:border-0">
                    <td className="px-3 py-2 font-medium text-foreground">
                      {line.label}
                      {line.note && (
                        <span className="block text-[10px] font-normal text-amber-700">{line.note}</span>
                      )}
                    </td>
                    <td className="px-3 py-2 text-right tabular-nums">{line.qty}</td>
                    <td className="px-3 py-2 text-right tabular-nums text-muted-foreground">
                      {line.unit_cogs != null ? peso(line.unit_cogs) : "—"}
                    </td>
                    <td className="px-3 py-2 text-right tabular-nums font-semibold">
                      {line.line_cogs != null ? peso(line.line_cogs) : "—"}
                    </td>
                  </tr>
                ))}
                <tr className="bg-secondary/20 font-semibold">
                  <td className="px-3 py-2" colSpan={3}>
                    Products subtotal
                  </td>
                  <td className="px-3 py-2 text-right tabular-nums">{peso(productsTotal)}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      )}

      <div className="rounded-lg border border-border overflow-hidden">
        <table className="w-full text-sm">
          <tbody>
            <tr className="border-b border-border">
              <td className="px-3 py-2 text-muted-foreground">Packaging (typical)</td>
              <td className="px-3 py-2 text-right tabular-nums font-medium">{peso(packaging)}</td>
            </tr>
            {lines.length > 0 && (
              <tr className="border-b border-border">
                <td className="px-3 py-2 text-muted-foreground">Products (manufacturing COGS)</td>
                <td className="px-3 py-2 text-right tabular-nums font-medium">{peso(productsTotal)}</td>
              </tr>
            )}
            <tr className="bg-[#C8900A]/[0.08]">
              <td className="px-3 py-2.5 font-semibold text-foreground">Landed total (typical)</td>
              <td className="px-3 py-2.5 text-right tabular-nums font-bold text-foreground">{peso(landed)}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}
