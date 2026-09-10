import { motion, useReducedMotion } from "motion/react";
import { Progress } from "@/app/components/ui/progress";
import { formatGallons } from "@/app/lib/honey";
import { cn } from "@/app/components/ui/utils";

export function HoneyLotGallonProgress({
  current,
  initial,
  testId,
  className,
}: {
  current: string | number;
  initial: string | number;
  testId?: string;
  className?: string;
}) {
  const reduceMotion = useReducedMotion();
  const remaining = Number(current);
  const capacity = Number(initial);
  const overdrawn = Number.isFinite(remaining) && remaining < -1e-9;
  const capacityOk = Number.isFinite(capacity) && capacity > 0;
  const fillPct =
    overdrawn || !capacityOk
      ? 0
      : Math.max(0, Math.min(100, (Math.max(0, remaining) / capacity) * 100));

  const remainingLabel = Number.isFinite(remaining) ? formatGallons(remaining) : String(current);
  const capacityLabel = capacityOk ? formatGallons(capacity) : "—";

  return (
    <div
      data-testid={testId}
      className={cn("min-w-[7.5rem] max-w-[11rem]", className)}
      aria-label="Gallon progress"
    >
      <p
        className={cn(
          "text-sm font-semibold tabular-nums tracking-tight",
          overdrawn ? "text-amber-900" : "text-foreground",
        )}
      >
        {overdrawn ? `−${formatGallons(Math.abs(remaining))} over` : remainingLabel}
      </p>
      <div className="relative mt-1.5">
        <Progress
          value={fillPct}
          className={cn(
            "h-2 bg-[#C8900A]/12 [&_[data-slot=progress-indicator]]:bg-[#C8900A] [&_[data-slot=progress-indicator]]:duration-500",
            overdrawn && "bg-amber-200/60 [&_[data-slot=progress-indicator]]:bg-amber-500",
          )}
          aria-valuetext={
            overdrawn
              ? `Overdrawn by ${formatGallons(Math.abs(remaining))} gallons`
              : `${Math.round(fillPct)} percent of ${capacityLabel} gallons remaining`
          }
        />
        {!reduceMotion && !overdrawn && fillPct > 0 ? (
          <motion.div
            aria-hidden
            className="pointer-events-none absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-transparent via-white/25 to-transparent"
            style={{ width: `${fillPct}%` }}
            initial={{ opacity: 0 }}
            animate={{ opacity: [0, 0.55, 0] }}
            transition={{ duration: 1.8, ease: "easeOut" }}
          />
        ) : null}
      </div>
      <p className="mt-1 text-[10px] text-muted-foreground tabular-nums">
        {overdrawn
          ? `of ${capacityLabel} gal stocked`
          : capacityOk
            ? `${Math.round(fillPct)}% of ${capacityLabel} gal`
            : "No capacity recorded"}
      </p>
    </div>
  );
}
