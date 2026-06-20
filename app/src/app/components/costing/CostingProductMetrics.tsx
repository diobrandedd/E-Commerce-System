import { ChevronDown } from "lucide-react";
import { CostBadge, peso } from "@/app/lib/costingDisplay";
import { cn } from "@/app/components/ui/utils";
import { costingRowHeaderClass } from "@/app/components/costing/costingRowLayout";

function MetricValue({
  value,
  className,
}: {
  value: string;
  className?: string;
}) {
  return <p className={cn("font-bold text-sm tabular-nums leading-tight", className)}>{value}</p>;
}

/** Column headers — place once per section; matches row grid columns. */
export function CostingRowColumnHeaders({
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
          ? "grid w-full items-center gap-x-2 sm:gap-x-3 px-4 py-1.5 border-t border-border bg-secondary/25 [grid-template-columns:0.25rem_minmax(0,1fr)_auto_1.25rem] sm:[grid-template-columns:0.25rem_minmax(6rem,1fr)_4.25rem_4.25rem_minmax(4.5rem,5.25rem)_4rem_1.25rem]"
          : "grid w-full items-center gap-x-2 sm:gap-x-3 px-4 py-1.5 border-t border-border bg-secondary/25 [grid-template-columns:minmax(0,1fr)_auto_1.25rem] sm:[grid-template-columns:minmax(6rem,1fr)_4.25rem_4.25rem_minmax(4.5rem,5.25rem)_4rem_1.25rem]",
      )}
    >
      {withColorBar && <span />}
      <span className={costingRowHeaderClass}>{labelCol}</span>
      <span className={cn(costingRowHeaderClass, "sm:hidden text-right")}>Cost / margin</span>
      <span className="sm:hidden" />
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Prod. cost</span>
      {isExec && <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Sell</span>}
      {isExec && <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Margin</span>}
      <span className={cn(costingRowHeaderClass, "hidden sm:block text-right")}>Baseline</span>
      <span />
    </div>
  );
}

/** Metric cells — direct children of the row grid (after label column). */
export function CostingProductMetrics({
  current,
  selling,
  baseline,
  isExec,
  chevron,
}: {
  current: number;
  selling: number;
  baseline: number;
  isExec: boolean;
  chevron?: { expanded: boolean };
}) {
  const margin = selling - current;
  const marginPct = selling > 0 ? ((margin / selling) * 100).toFixed(1) : "0.0";

  return (
    <>
      <div className="flex flex-col items-end justify-center min-w-0 sm:contents">
        <div className="sm:hidden text-right tabular-nums">
          <MetricValue value={peso(current)} />
          {isExec && (
            <p className="text-[10px] text-muted-foreground leading-tight">
              Sell {peso(selling)} ·{" "}
              <span className={margin > 0 ? "text-green-600" : "text-red-500"}>{marginPct}%</span>
            </p>
          )}
        </div>
        <div className="hidden sm:block text-right tabular-nums">
          <MetricValue value={peso(current)} />
        </div>
        {isExec && (
          <div className="hidden sm:block text-right tabular-nums">
            <MetricValue value={peso(selling)} />
          </div>
        )}
        {isExec && (
          <div className="hidden sm:block text-right tabular-nums">
            <MetricValue
              value={peso(margin)}
              className={margin > 0 ? "text-green-600" : "text-red-500"}
            />
            <p className="text-[10px] text-muted-foreground leading-tight">({marginPct}%)</p>
          </div>
        )}
        <div className="hidden sm:flex sm:justify-end">
          <CostBadge current={current} baseline={baseline} />
        </div>
      </div>
      {chevron ? (
        <ChevronDown
          size={16}
          className={cn(
            "text-muted-foreground flex-shrink-0 justify-self-end transition-transform duration-300 ease-out",
            chevron.expanded ? "rotate-180 text-[#C8900A]" : "",
          )}
        />
      ) : (
        <span />
      )}
    </>
  );
}
