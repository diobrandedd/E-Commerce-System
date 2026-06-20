import type { ParcelPackTierLine } from "@/app/lib/parcelPackCosting";
import { peso } from "@/app/lib/costingDisplay";
import { cn } from "@/app/components/ui/utils";

const thCls =
  "text-[10px] font-semibold uppercase tracking-wide text-muted-foreground text-left py-2 px-3";
const thRightCls = cn(thCls, "text-right");

export function CostingMaterialLinesTable({
  lines,
  showVariableQty = true,
  showTotal = false,
}: {
  lines: ParcelPackTierLine[];
  showVariableQty?: boolean;
  showTotal?: boolean;
}) {
  const hasVariableQty =
    showVariableQty && lines.some(l => l.qty_min !== null || l.qty_max !== null);
  const packagingTotal = lines.reduce(
    (sum, line) => sum + Math.round(line.qty_typical * (line.unit_cost ?? 0) * 100) / 100,
    0,
  );

  if (lines.length === 0) {
    return <p className="text-sm text-muted-foreground py-4 text-center">No materials in this parcel tier.</p>;
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm border-collapse min-w-[28rem]">
        <thead>
          <tr className="border-b border-border bg-secondary/30">
            <th className={thCls}>Material</th>
            <th className={thRightCls}>Unit cost</th>
            {hasVariableQty && <th className={thRightCls}>Min</th>}
            <th className={thRightCls}>Typical qty</th>
            {hasVariableQty && <th className={thRightCls}>Max</th>}
            <th className={thRightCls}>Line ₱</th>
          </tr>
        </thead>
        <tbody>
          {lines.map(line => {
            const lineTyp = Math.round(line.qty_typical * (line.unit_cost ?? 0) * 100) / 100;
            return (
              <tr key={line.id} className="border-b border-border last:border-0 hover:bg-secondary/15">
                <td className="px-3 py-2 font-medium text-foreground">
                  {line.stock_name ?? line.line_label ?? "—"}
                  <span className="ml-1 text-[10px] text-muted-foreground">({line.unit})</span>
                </td>
                <td className="px-3 py-2 text-right tabular-nums text-muted-foreground">
                  {peso(line.unit_cost ?? 0)}
                </td>
                {hasVariableQty && (
                  <td className="px-3 py-2 text-right tabular-nums text-muted-foreground">
                    {line.qty_min !== null ? line.qty_min : "—"}
                  </td>
                )}
                <td className="px-3 py-2 text-right tabular-nums">{line.qty_typical}</td>
                {hasVariableQty && (
                  <td className="px-3 py-2 text-right tabular-nums text-muted-foreground">
                    {line.qty_max !== null ? line.qty_max : "—"}
                  </td>
                )}
                <td className="px-3 py-2 text-right tabular-nums font-semibold">{peso(lineTyp)}</td>
              </tr>
            );
          })}
          {showTotal && (
            <tr className="bg-secondary/20 font-semibold border-t border-border">
              <td className="px-3 py-2" colSpan={hasVariableQty ? 5 : 3}>
                Packaging total (typical)
              </td>
              <td className="px-3 py-2 text-right tabular-nums">{peso(packagingTotal)}</td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
