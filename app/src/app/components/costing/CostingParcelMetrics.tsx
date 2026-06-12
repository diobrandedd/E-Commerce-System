import { ChevronDown } from "lucide-react";
import { peso } from "@/app/lib/costingDisplay";
import { cn } from "@/app/components/ui/utils";
import { costingRowHeaderClass } from "@/app/components/costing/costingRowLayout";

function MetricValue({ value, className }: { value: string; className?: string }) {
  return <p className={cn("font-bold text-sm tabular-nums leading-tight", className)}>{value}</p>;
}

export function CostingParcelRowColumnHeaders({
  isExec,
  labelCol = "Product",
  withColorBar = false,
}: {
  isExec: boolean;
  labelCol?: string;
  withColorBar?: boolean;
}) {
  return (
    <div
      className={cn(
        withColorBar
          ? "grid w-full items-center gap-x-2 sm:gap-x-3 px-4 py-1.5 border-t border-border bg-secondary/25 [grid-template-columns:0.25rem_minmax(0,1fr)_auto_1.25rem] sm:[grid-template-columns:0.25rem_minmax(5rem,1fr)_3.5rem_3.5rem_3.75rem_minmax(4rem,4.5rem)_4rem_1.25rem]"
          : "grid w-full items-center gap-x-2 sm:gap-x-3 px-4 py-1.5 border-t border-border bg-secondary/25 [grid-template-columns:minmax(0,1fr)_auto_1.25rem] sm:[grid-template-columns:minmax(5rem,1fr)_3.5rem_3.5rem_3.75rem_minmax(4rem,4.5rem)_4rem_1.25rem]",
      )}
    >
      {withColorBar && <span />}
      <span className={costingRowHeaderClass}>{labelCol}</span>
      <span className={cn(costingRowHeaderClass, "sm:hidden text-right")}>Landed / margin</span>
      <span className="sm:hidden" />
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Prod. COGS</span>
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Parcel</span>
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Landed</span>
      {isExec && <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Margin</span>}
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Baseline</span>
      <span />
    </div>
  );
}

export const costingParcelRowGridClass =
  "grid w-full items-center gap-x-2 sm:gap-x-3 " +
  "[grid-template-columns:minmax(0,1fr)_auto_1.25rem] " +
  "sm:[grid-template-columns:minmax(5rem,1fr)_3.5rem_3.5rem_3.75rem_minmax(4rem,4.5rem)_4rem_1.25rem]";

export const costingParcelHoneyRowGridClass =
  "grid w-full items-center gap-x-2 sm:gap-x-3 " +
  "[grid-template-columns:0.25rem_minmax(0,1fr)_auto_1.25rem] " +
  "sm:[grid-template-columns:0.25rem_minmax(5rem,1fr)_3.5rem_3.5rem_3.75rem_minmax(4rem,4.5rem)_4rem_1.25rem]";

export function CostingParcelProductMetrics({
  cogs,
  parcelAddon,
  landed,
  selling,
  baseline,
  isExec,
  chevron,
}: {
  cogs: number;
  parcelAddon: number | null;
  landed: number | null;
  selling: number;
  baseline: number;
  isExec: boolean;
  chevron?: { expanded: boolean };
}) {
  const margin = landed !== null ? selling - landed : selling - cogs;
  const marginPct = selling > 0 && landed !== null ? ((margin / selling) * 100).toFixed(1) : "—";

  return (
    <>
      <div className="flex flex-col items-end justify-center min-w-0 sm:contents">
        <div className="sm:hidden text-right tabular-nums">
          <MetricValue value={landed !== null ? peso(landed) : peso(cogs)} />
          {isExec && landed !== null && (
            <p className="text-[10px] text-muted-foreground leading-tight">
              +{peso(parcelAddon ?? 0)} pack ·{" "}
              <span className={margin > 0 ? "text-green-600" : "text-red-500"}>{marginPct}%</span>
            </p>
          )}
        </div>
        <div className="hidden sm:block text-right tabular-nums">
          <MetricValue value={peso(cogs)} />
        </div>
        <div className="hidden sm:block text-right tabular-nums">
          <MetricValue
            value={parcelAddon !== null ? peso(parcelAddon) : "—"}
            className={parcelAddon === null ? "text-muted-foreground font-normal" : undefined}
          />
        </div>
        <div className="hidden sm:block text-right tabular-nums">
          <MetricValue
            value={landed !== null ? peso(landed) : "—"}
            className={landed === null ? "text-muted-foreground font-normal" : undefined}
          />
        </div>
        {isExec && (
          <div className="hidden sm:block text-right tabular-nums">
            {landed !== null ? (
              <p className={cn("font-bold text-sm tabular-nums", margin > 0 ? "text-green-600" : "text-red-500")}>
                {marginPct}%
              </p>
            ) : (
              <p className="text-sm text-muted-foreground">—</p>
            )}
          </div>
        )}
        <div className="hidden sm:block text-right tabular-nums">
          <p className="text-xs text-muted-foreground tabular-nums">{peso(baseline)}</p>
        </div>
      </div>
      {chevron && (
        <ChevronDown
          size={16}
          className={cn(
            "flex-shrink-0 text-muted-foreground transition-transform duration-200",
            chevron.expanded && "rotate-180",
          )}
        />
      )}
    </>
  );
}
