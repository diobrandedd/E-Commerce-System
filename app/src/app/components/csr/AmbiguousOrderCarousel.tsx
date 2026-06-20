import { useMemo, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { CheckCircle2, Pencil } from "lucide-react";
import { VariantCardStrip } from "@/app/components/csr/VariantCardStrip";
import {
  OrderRemarksAllDonePanel,
  OrderRemarksAnimatedCount,
  OrderRemarksCarouselCard,
  OrderRemarksDoneBadge,
  OrderRemarksStepReveal,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";
import { getOrderCardDisplay } from "@/app/lib/orderRowDisplay";
import {
  buildFinalRemark,
  buildFree350Presets,
  buildMainPresets,
  cardSubtitle,
  getCardSteps,
  isRowFullyResolved,
  needsFree350Pick,
  productLabel,
  type ParsedRemark,
  type RowResolution,
} from "@/app/lib/orderRemarksParse";
import type { OrderRow } from "@/app/lib/orderRemarksApi";
import { enrichVipPresets } from "@/app/lib/honeyPackagingStock";
import type { PackagingStockSnapshot } from "@/app/lib/honeyPackagingStock";
import { useHoneyPackagingStock } from "@/app/hooks/useHoneyPackagingStock";
import { ItemLineBuilder } from "@/app/components/csr/onlineOrders/ItemLineBuilder";
import { cn } from "@/app/components/ui/utils";

interface AmbiguousOrderCarouselProps {
  rows: { order: OrderRow; parsed: ParsedRemark }[];
  resolutions: Record<number, RowResolution>;
  onResolve: (idx: number, resolution: RowResolution) => void;
  onClearResolution?: (idx: number) => void;
  disabled?: boolean;
  showCompleted: boolean;
  onShowCompletedChange: (value: boolean) => void;
  onCardCompleted?: (remaining: number) => void;
}

function mainStepTitle(product: ParsedRemark["product"]): string {
  if (product === "3L") return "3 Liter bottles";
  if (product === "2L") return "2 Liter bottles";
  if (product === "1L") return "1 Liter bottle";
  if (product === "gallon") return "Gallon";
  return "Pick variant";
}

function OrderCard({
  order,
  parsed,
  resolution,
  onResolve,
  onClearResolution,
  disabled,
  packagingStock,
}: {
  order: OrderRow;
  parsed: ParsedRemark;
  resolution?: RowResolution;
  onResolve: (resolution: RowResolution) => void;
  onClearResolution?: () => void;
  disabled?: boolean;
  packagingStock: PackagingStockSnapshot | null;
}) {
  const raw = order.remarks;
  const steps = getCardSteps(raw, parsed, resolution);
  const done = isRowFullyResolved(raw, parsed, resolution);
  const subtitle = cardSubtitle(raw, parsed, steps);
  const display = getOrderCardDisplay(order, parsed);
  const reduceMotion = useReducedMotion();
  const [showBuilder, setShowBuilder] = useState(false);
  const builderOpen = showBuilder || (parsed.needsBuilder && !done);

  const handleBuilderSave = (packerLine: string) => {
    onResolve({ finalRemark: packerLine });
    setShowBuilder(false);
  };

  const handleMainSelect = (mainVariant: string) => {
    if (needsFree350Pick(raw, parsed)) {
      onResolve({ mainVariant });
      return;
    }
    const finalRemark = buildFinalRemark(raw, parsed, mainVariant);
    onResolve({ mainVariant, finalRemark });
  };

  const handleFree350Select = (free350Variant: string) => {
    const mainVariant = resolution?.mainVariant ?? steps.mainLabel ?? "";
    const finalRemark = buildFinalRemark(raw, parsed, mainVariant, free350Variant);
    onResolve({ mainVariant, free350Variant, finalRemark });
  };

  return (
    <motion.div
      layout
      animate={
        reduceMotion
          ? undefined
          : { borderColor: done ? "rgb(134 239 172)" : "rgb(252 211 77)" }
      }
      transition={remarksSpring}
      className={cn(
        "flex-shrink-0 snap-start bg-card rounded-xl border p-4 flex flex-col max-h-[min(88vh,620px)] overflow-hidden",
        builderOpen ? "w-[min(100%,340px)] sm:w-[340px]" : "w-[min(100%,300px)] sm:w-[300px]",
        done ? "border-green-300" : "border-amber-300",
      )}
    >
      <div className="flex-shrink-0 flex items-start justify-between gap-2 mb-2">
        <div className="min-w-0 flex-1">
          <p className="text-[10px] text-muted-foreground uppercase tracking-wide">
            Row {display.rowNum}
          </p>
          {display.receiver && (
            <p className="text-sm mt-0.5">
              <span className="text-muted-foreground font-medium">Receiver: </span>
              <span className="font-semibold text-foreground">{display.receiver}</span>
            </p>
          )}
          {display.phone && (
            <p className="text-xs text-muted-foreground mt-0.5">{display.phone}</p>
          )}
          {display.place && (
            <p className="text-sm mt-1">
              <span className="text-muted-foreground font-medium">Place: </span>
              <span className="font-semibold text-foreground">{display.place}</span>
              {display.province ? (
                <span className="text-muted-foreground font-normal">, {display.province}</span>
              ) : null}
            </p>
          )}
          <p className="text-xs text-muted-foreground mt-1">{productLabel(parsed.product)}</p>
          {subtitle && (
            <p className="text-[10px] text-[#8a6500] mt-1 font-medium">{subtitle}</p>
          )}
        </div>
        {done && (
          <motion.span
            initial={reduceMotion ? false : { scale: 0, rotate: -25 }}
            animate={{ scale: 1, rotate: 0 }}
            transition={remarksSpring}
            className="flex-shrink-0"
          >
            <CheckCircle2 size={16} className="text-green-600" />
          </motion.span>
        )}
      </div>

      <div className="flex-1 min-h-0 overflow-y-auto overflow-x-hidden scrollbar-thin space-y-2 pr-0.5">
        {display.address && (
          <p className="text-[10px] text-muted-foreground line-clamp-2" title={display.address}>
            <span className="font-medium text-foreground/80">Address: </span>
            {display.address}
          </p>
        )}

        <div className="rounded-lg border border-border/80 bg-muted/30 p-2 max-h-28 overflow-y-auto">
          <p className="text-[10px] font-semibold text-muted-foreground uppercase mb-1">Remarks</p>
          <p className="text-[11px] text-foreground whitespace-pre-wrap break-words">{raw}</p>
        </div>

        {!showBuilder && !done && !parsed.needsBuilder && (
          <>
            <OrderRemarksStepReveal show={steps.showMain}>
              <VariantCardStrip
                title={mainStepTitle(parsed.product)}
                presets={enrichVipPresets(buildMainPresets(parsed.product, raw), parsed.product, "main", packagingStock)}
                selected={resolution?.mainVariant}
                onSelect={handleMainSelect}
                disabled={disabled}
              />
            </OrderRemarksStepReveal>

            <OrderRemarksStepReveal show={steps.showFree350}>
              <VariantCardStrip
                title="Free 350ml bottle"
                presets={enrichVipPresets(buildFree350Presets(), parsed.product, "free350", packagingStock)}
                selected={resolution?.free350Variant}
                onSelect={handleFree350Select}
                disabled={disabled}
              />
            </OrderRemarksStepReveal>
          </>
        )}

        {(showBuilder || parsed.needsBuilder) && !done && (
          <ItemLineBuilder
            initialLines={parsed.builderLines}
            packagingStock={packagingStock}
            disabled={disabled}
            onSave={handleBuilderSave}
            onCancel={() => setShowBuilder(false)}
          />
        )}
      </div>

      <div className="flex-shrink-0 pt-2 space-y-2">
        {!done && !showBuilder && !parsed.needsBuilder && (
          <button
            type="button"
            disabled={disabled}
            onClick={() => setShowBuilder(true)}
            className="w-full text-xs font-medium py-1.5 rounded-lg border border-border flex items-center justify-center gap-1 hover:bg-muted/40"
          >
            <Pencil size={12} /> Edit items
          </button>
        )}

        <OrderRemarksDoneBadge show={Boolean(done && resolution?.finalRemark)}>
          <p className="text-[10px] p-2 rounded-lg bg-green-50 text-green-800 border border-green-200 break-words">
            {resolution?.finalRemark}
          </p>
        </OrderRemarksDoneBadge>

        {done && onClearResolution && (
          <button
            type="button"
            disabled={disabled}
            onClick={() => {
              onClearResolution();
              setShowBuilder(false);
            }}
            className="w-full text-xs font-medium py-1.5 rounded-lg border border-border flex items-center justify-center gap-1 hover:bg-muted/40"
          >
            <Pencil size={12} /> Edit choice
          </button>
        )}
      </div>
    </motion.div>
  );
}

export function AmbiguousOrderCarousel({
  rows,
  resolutions,
  onResolve,
  onClearResolution,
  disabled,
  showCompleted,
  onShowCompletedChange,
  onCardCompleted,
}: AmbiguousOrderCarouselProps) {
  const { snapshot: packagingStock } = useHoneyPackagingStock();
  const pendingRows = useMemo(
    () =>
      rows.filter(
        r => !isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx]),
      ),
    [rows, resolutions],
  );

  const completedRows = useMemo(
    () =>
      rows.filter(r =>
        isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx]),
      ),
    [rows, resolutions],
  );

  const visibleRows = showCompleted ? rows : pendingRows;
  const doneCount = completedRows.length;

  const handleResolve = (idx: number, resolution: RowResolution) => {
    const wasPending = rows.some(
      r =>
        r.order.idx === idx &&
        !isRowFullyResolved(r.order.remarks, r.parsed, resolutions[idx]),
    );
    onResolve(idx, resolution);
    if (wasPending && resolution.finalRemark) {
      const next = { ...resolutions, [idx]: resolution };
      const remaining = rows.filter(
        r => !isRowFullyResolved(r.order.remarks, r.parsed, next[r.order.idx]),
      ).length;
      onCardCompleted?.(remaining);
    }
  };

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-xs text-muted-foreground">
          <OrderRemarksAnimatedCount value={pendingRows.length} className="font-semibold text-foreground" />{" "}
          remaining
          {doneCount > 0 ? (
            <>
              {" · "}
              <OrderRemarksAnimatedCount value={doneCount} className="font-semibold text-foreground" /> completed
            </>
          ) : null}
        </p>
        {doneCount > 0 && (
          <label className="flex items-center gap-2 text-xs cursor-pointer select-none">
            <input
              type="checkbox"
              checked={showCompleted}
              onChange={e => onShowCompletedChange(e.target.checked)}
              className="rounded border-border"
            />
            Show completed
          </label>
        )}
      </div>

      {visibleRows.length === 0 ? (
        <OrderRemarksAllDonePanel>
          <div className="rounded-xl border border-green-200 bg-green-50 p-6 text-center text-sm text-green-800">
            <motion.div
              initial={{ scale: 0.8, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={remarksSpring}
            >
              <CheckCircle2 size={24} className="mx-auto mb-2 text-green-600" />
            </motion.div>
            All orders in this batch are resolved. You can export now.
          </div>
        </OrderRemarksAllDonePanel>
      ) : (
        <div className="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-2 -mx-1 px-1 scrollbar-thin">
          <AnimatePresence mode="popLayout">
            {visibleRows.map(({ order, parsed }) => (
              <OrderRemarksCarouselCard key={order.idx} cardKey={order.idx}>
                <OrderCard
                  order={order}
                  parsed={parsed}
                  resolution={resolutions[order.idx]}
                  onResolve={res => handleResolve(order.idx, res)}
                  onClearResolution={
                    onClearResolution ? () => onClearResolution(order.idx) : undefined
                  }
                  disabled={disabled}
                  packagingStock={packagingStock}
                />
              </OrderRemarksCarouselCard>
            ))}
          </AnimatePresence>
        </div>
      )}

      {visibleRows.length > 1 && pendingRows.length > 0 && (
        <p className="text-[10px] text-muted-foreground text-center">
          Scroll sideways for more orders
        </p>
      )}
    </div>
  );
}
