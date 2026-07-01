import { useEffect, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { ChevronDown, Plus, Trash2 } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { AppPressable } from "@/app/components/layout/appFeedbackMotion";
import { remarksSpring } from "@/app/components/csr/orderRemarksMotion";
import {
  BUILDER_PRODUCT_OPTIONS,
  builderLineIsValid,
  composePackerLine,
  defaultBuilderLine,
  isHoneyBuilderProduct,
  type BuilderLine,
  type BuilderProduct,
  variantOptionsForProduct,
} from "@/app/lib/itemLineBuilder";
import {
  formatBuilderVariantStockLabel,
  formatStockBreakdownCompact,
  stockBreakdownForBuilderVariant,
} from "@/app/lib/honeyPackagingStock";
import type { PackagingStockSnapshot } from "@/app/lib/honeyPackagingStock";
import { cn } from "@/app/components/ui/utils";

interface ItemLineBuilderProps {
  initialLines?: BuilderLine[];
  packagingStock?: PackagingStockSnapshot | null;
  disabled?: boolean;
  /** When true (cacao + honey bundles), Save requires at least one valid honey line. */
  requireHoneyLine?: boolean;
  /** compact = VIP carousel cards; comfortable = parcel modal / wider surfaces */
  density?: "compact" | "comfortable";
  onSave: (packerLine: string, lines: BuilderLine[]) => void;
  onCancel?: () => void;
}

function lineSummary(line: BuilderLine): string {
  const prod = BUILDER_PRODUCT_OPTIONS.find(p => p.value === line.product)?.label ?? line.product;
  const parts = [prod];
  if (line.variant) parts.push(line.variant);
  parts.push(`×${line.qty}`);
  return parts.join(" · ");
}

function VariantStockChip({
  product,
  variant,
  snapshot,
}: {
  product: string;
  variant: string;
  snapshot: PackagingStockSnapshot | null;
}) {
  const breakdown = stockBreakdownForBuilderVariant(product, variant, snapshot);
  if (!breakdown?.length) return null;

  const minQty = Math.min(...breakdown.map(b => b.qty));
  const compact = formatStockBreakdownCompact(breakdown);
  const multiColor = breakdown.length > 1 && !breakdown.every(b => b.letter === breakdown[0]!.letter);

  if (!multiColor) {
    return (
      <p className="text-[10px] text-muted-foreground tabular-nums mt-1.5 px-2 py-1 rounded-md bg-muted/50 border border-border/60">
        Stock: <span className="font-medium text-foreground">{compact}</span>
      </p>
    );
  }

  return (
    <div className="mt-1.5 flex flex-wrap gap-1">
      {breakdown.map((e, i) => (
        <span
          key={`${e.letter}-${i}`}
          className={cn(
            "inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] tabular-nums border",
            e.qty === minQty
              ? "border-amber-300 bg-amber-50 text-amber-900 font-semibold"
              : "border-border/70 bg-muted/40 text-muted-foreground",
          )}
        >
          {e.letter} {Math.max(0, Math.floor(e.qty)).toLocaleString()}
        </span>
      ))}
    </div>
  );
}

function defaultExpandedMap(count: number): Record<number, boolean> {
  const map: Record<number, boolean> = {};
  for (let i = 0; i < count; i += 1) {
    map[i] = i === 0;
  }
  return map;
}

export function ItemLineBuilder({
  initialLines,
  packagingStock,
  disabled,
  requireHoneyLine = false,
  density = "compact",
  onSave,
  onCancel,
}: ItemLineBuilderProps) {
  const comfortable = density === "comfortable";
  const reduceMotion = useReducedMotion();
  const [lines, setLines] = useState<BuilderLine[]>(
    initialLines?.length ? initialLines.map(l => ({ ...l })) : [defaultBuilderLine("1L")],
  );
  const [expanded, setExpanded] = useState<Record<number, boolean>>(() =>
    defaultExpandedMap(initialLines?.length ? initialLines.length : 1),
  );

  const updateLine = (idx: number, patch: Partial<BuilderLine>) => {
    setLines(prev => prev.map((l, i) => (i === idx ? { ...l, ...patch } : l)));
  };

  const toggleLine = (idx: number) => {
    setExpanded(prev => ({ ...prev, [idx]: !prev[idx] }));
  };

  const addLine = () => {
    setLines(prev => {
      const next = [...prev, defaultBuilderLine("1L")];
      setExpanded(e => {
        const collapsed: Record<number, boolean> = {};
        prev.forEach((_, i) => {
          collapsed[i] = false;
        });
        collapsed[next.length - 1] = true;
        return collapsed;
      });
      return next;
    });
  };

  const removeLine = (idx: number) => {
    setLines(prev => {
      if (prev.length <= 1) return prev;
      const next = prev.filter((_, i) => i !== idx);
      setExpanded(() => defaultExpandedMap(next.length));
      return next;
    });
  };

  useEffect(() => {
    setExpanded(prev => {
      const next: Record<number, boolean> = {};
      lines.forEach((_, i) => {
        next[i] = prev[i] ?? i === 0;
      });
      return next;
    });
  }, [lines.length]);

  const hasValidHoneyLine = lines.some(
    l => isHoneyBuilderProduct(l.product) && l.product !== "other" && builderLineIsValid(l),
  );

  const canSave =
    lines.every(l => {
      if (!isHoneyBuilderProduct(l.product)) return l.qty >= 1;
      return builderLineIsValid(l);
    }) && (!requireHoneyLine || hasValidHoneyLine);

  const snapshot = packagingStock ?? null;

  return (
    <div
      className={cn(
        "flex flex-col overflow-hidden",
        comfortable
          ? "rounded-xl border border-border/60 bg-background"
          : "max-h-[min(52vh,320px)] rounded-xl border border-border/80 bg-card/80 shadow-sm",
      )}
    >
      <div
        className={cn(
          "flex-1 min-h-0 overflow-y-auto overflow-x-hidden scrollbar-thin",
          comfortable ? "p-3 space-y-3 max-h-[min(52vh,420px)]" : "p-2.5 space-y-2",
        )}
      >
        {lines.map((line, idx) => {
          const honey = isHoneyBuilderProduct(line.product);
          const variants = variantOptionsForProduct(line.product);
          const isOpen = expanded[idx] ?? false;
          const valid = !honey || builderLineIsValid(line);

          return (
            <div
              key={idx}
              className="rounded-lg border border-border/70 bg-background/80 overflow-hidden"
            >
              <div
                className={cn(
                  "flex items-center gap-1.5",
                  comfortable ? "px-2.5 py-2" : "px-1 py-1",
                  isOpen ? "border-b border-border/60 bg-muted/30" : "",
                )}
              >
                <AppPressable
                  type="button"
                  disabled={disabled}
                  onClick={() => toggleLine(idx)}
                  className={cn(
                    "min-w-0 flex-1 flex items-center gap-2 text-left rounded-md",
                    comfortable ? "px-1 py-1.5" : "px-1.5 py-1",
                    !isOpen && "hover:bg-muted/20",
                  )}
                >
                  <motion.span
                    animate={{ rotate: isOpen ? 180 : 0 }}
                    transition={reduceMotion ? { duration: 0 } : remarksSpring}
                    className="flex-shrink-0 text-muted-foreground"
                  >
                    <ChevronDown size={14} />
                  </motion.span>
                  <div className="min-w-0 flex-1">
                    <p className="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                      Line {idx + 1}
                    </p>
                    {!isOpen && (
                      <p
                        className={cn(
                          "text-foreground truncate mt-0.5",
                          comfortable ? "text-sm font-medium" : "text-[11px]",
                        )}
                      >
                        {lineSummary(line)}
                      </p>
                    )}
                  </div>
                  {!valid && !isOpen && (
                    <span className="text-[9px] font-medium text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded flex-shrink-0">
                      Pick variant
                    </span>
                  )}
                </AppPressable>
                {lines.length > 1 && (
                  <button
                    type="button"
                    disabled={disabled}
                    onClick={() => removeLine(idx)}
                    className="p-1 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/5 flex-shrink-0"
                    aria-label="Remove line"
                  >
                    <Trash2 size={14} />
                  </button>
                )}
              </div>

              <AnimatePresence initial={false}>
                {isOpen && (
                  <motion.div
                    initial={reduceMotion ? false : { height: 0, opacity: 0 }}
                    animate={{ height: "auto", opacity: 1 }}
                    exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
                    transition={remarksSpring}
                    className="overflow-hidden"
                  >
                    <div className={cn("space-y-3", comfortable ? "p-3.5" : "p-2.5 space-y-2")}>
                      <label className="block space-y-1.5">
                        <span className={cn("font-medium text-muted-foreground", comfortable ? "text-xs" : "text-[10px]")}>
                          Product
                        </span>
                        <select
                          value={line.product}
                          disabled={disabled}
                          onChange={e => {
                            const product = e.target.value as BuilderProduct;
                            const next = defaultBuilderLine(product);
                            updateLine(idx, { product, variant: next.variant, qty: next.qty });
                          }}
                          className={cn(
                            "w-full border border-border rounded-lg px-3 bg-card",
                            comfortable ? "text-sm py-2.5" : "text-xs py-2",
                          )}
                        >
                          {BUILDER_PRODUCT_OPTIONS.map(o => (
                            <option key={o.value} value={o.value}>
                              {o.label}
                            </option>
                          ))}
                        </select>
                      </label>

                      {honey && (
                        <label className="block space-y-1.5">
                          <span className={cn("font-medium text-muted-foreground", comfortable ? "text-xs" : "text-[10px]")}>
                            Variant
                          </span>
                          <select
                            value={line.variant ?? ""}
                            disabled={disabled}
                            onChange={e => updateLine(idx, { variant: e.target.value })}
                            className={cn(
                              "w-full border border-border rounded-lg px-3 bg-card",
                              comfortable ? "text-sm py-2.5" : "text-xs py-2",
                            )}
                          >
                            <option value="">Pick variant…</option>
                            {variants.map(v => {
                              const stockLabel = formatBuilderVariantStockLabel(
                                line.product,
                                v.value,
                                snapshot,
                              );
                              const suffix = stockLabel ? ` · ${stockLabel}` : "";
                              return (
                                <option key={v.value} value={v.value}>
                                  {v.label}
                                  {suffix}
                                </option>
                              );
                            })}
                          </select>
                          {line.variant ? (
                            <VariantStockChip
                              product={line.product}
                              variant={line.variant}
                              snapshot={snapshot}
                            />
                          ) : null}
                        </label>
                      )}

                      <label className={cn("block space-y-1.5", comfortable ? "w-28" : "w-24")}>
                        <span className={cn("font-medium text-muted-foreground", comfortable ? "text-xs" : "text-[10px]")}>
                          Qty
                        </span>
                        <input
                          type="number"
                          min={1}
                          value={line.qty}
                          disabled={disabled}
                          onChange={e =>
                            updateLine(idx, { qty: Math.max(1, parseInt(e.target.value, 10) || 1) })
                          }
                          className={cn(
                            "w-full border border-border rounded-lg px-3 bg-card tabular-nums",
                            comfortable ? "text-sm py-2.5" : "text-xs py-2",
                          )}
                        />
                      </label>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          );
        })}
      </div>

      <div
        className={cn(
          "flex-shrink-0 border-t border-border/60 bg-card/95",
          comfortable ? "p-4 space-y-3" : "p-2.5 space-y-2",
        )}
      >
        <AppPressable
          type="button"
          disabled={disabled}
          onClick={addLine}
          className={cn(
            "w-full inline-flex items-center justify-center gap-1.5 font-medium rounded-lg border border-dashed border-border text-muted-foreground hover:text-foreground hover:border-primary/40 hover:bg-primary/5",
            comfortable ? "text-sm px-4 py-3" : "text-xs px-3 py-2",
          )}
        >
          <Plus size={14} /> Add another product
        </AppPressable>
        {requireHoneyLine && !hasValidHoneyLine && (
          <p className="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1.5">
            This bundle includes honey — use Add another product, pick size (e.g. 250ml) and Light/Amber/Dark.
          </p>
        )}
        <Button
          type="button"
          size={comfortable ? "default" : "sm"}
          disabled={disabled || !canSave}
          onClick={() => onSave(composePackerLine(lines), lines)}
          className="w-full"
        >
          Save items
        </Button>
        {onCancel && (
          <Button
            type="button"
            size={comfortable ? "default" : "sm"}
            variant="outline"
            disabled={disabled}
            onClick={onCancel}
            className="w-full"
          >
            Cancel
          </Button>
        )}
      </div>
    </div>
  );
}
