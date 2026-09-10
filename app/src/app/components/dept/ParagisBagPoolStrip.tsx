import { motion, useReducedMotion } from "motion/react";
import { Progress } from "@/app/components/ui/progress";
import type { DeptBagPool } from "@/app/lib/deptRawInventory";
import { cn } from "@/app/components/ui/utils";

function formatBags(n: number): string {
  return n.toLocaleString(undefined, { maximumFractionDigits: 3 });
}

function formatGrams(n: number): string {
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

export function ParagisBagPoolStrip({ pool }: { pool: DeptBagPool }) {
  const reduceMotion = useReducedMotion();
  const capacity = Math.max(pool.grams_capacity, 0);
  const remaining = pool.grams_remaining;
  const overdrawn = pool.overdrawn || remaining < -0.0001;
  const fillPct =
    overdrawn || capacity <= 0
      ? 0
      : Math.max(0, Math.min(100, (remaining / capacity) * 100));
  const pouchesLeft =
    pool.grams_per_pouch > 0 ? Math.floor(Math.max(0, remaining) / pool.grams_per_pouch) : 0;

  return (
    <motion.section
      layout={!reduceMotion}
      data-testid="paragis-bag-pool"
      aria-label="Paragis bag pool"
      className={cn(
        "rounded-xl border bg-card px-4 py-3.5 shadow-sm shadow-black/[0.03]",
        overdrawn ? "border-amber-500/70 bg-amber-50/50" : "border-border",
      )}
    >
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <h2 className="text-sm font-semibold text-foreground">Bag pool</h2>
        <p className="text-xs text-muted-foreground">
          {formatGrams(pool.grams_per_pouch)} g per pouch · {pool.tea_bags_per_pouch} ×{" "}
          {pool.grams_per_tea_bag} g
        </p>
      </div>
      <p className="mt-2 text-2xl font-semibold tabular-nums tracking-tight">
        {overdrawn ? (
          <span className="text-amber-900">
            −{formatGrams(Math.abs(remaining))} g overdrawn
          </span>
        ) : (
          <>
            {formatGrams(remaining)} g
            <span className="ml-2 text-sm font-medium text-muted-foreground">
              · {formatBags(pool.bags_on_hand)} bag{Math.abs(pool.bags_on_hand) === 1 ? "" : "s"}
            </span>
          </>
        )}
      </p>
      <div className="mt-3.5">
        <div className="relative">
          <Progress
            value={fillPct}
            className={cn(
              "h-3.5 bg-[#C8900A]/12 [&_[data-slot=progress-indicator]]:bg-[#C8900A] [&_[data-slot=progress-indicator]]:duration-500",
              overdrawn && "bg-amber-200/60 [&_[data-slot=progress-indicator]]:bg-amber-500",
            )}
            aria-valuetext={
              overdrawn
                ? `Overdrawn by ${formatGrams(Math.abs(remaining))} grams`
                : `${Math.round(fillPct)} percent of stocked leaf remaining`
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
        <div className="mt-1.5 flex flex-wrap justify-between gap-2 text-[11px] text-muted-foreground">
          <span>
            {capacity > 0
              ? `${Math.round(fillPct)}% of ${formatGrams(capacity)} g stocked`
              : "Stock in raw bags to fill the pool"}
          </span>
          {!overdrawn && pouchesLeft > 0 ? (
            <span>~{pouchesLeft.toLocaleString()} pouch{pouchesLeft === 1 ? "" : "es"} left</span>
          ) : null}
        </div>
      </div>
      {overdrawn ? (
        <p className="mt-2.5 text-xs font-medium text-amber-950" role="status">
          Scans can continue while overdrawn. Stock in more raw bags to recover the pool.
        </p>
      ) : null}
    </motion.section>
  );
}
