import { useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { ChevronDown } from "lucide-react";
import type { InventoryCheckBatchDoc } from "@/app/lib/inventoryCheck";

type Props = {
  batches: InventoryCheckBatchDoc[];
  /** Extra class on the section wrapper */
  className?: string;
};

/**
 * Expandable documentation groups: one row per batch number, nested bottling dates / variants.
 * Does not participate in inventory matching.
 */
export function InventoryCheckBatchDocs({ batches, className = "" }: Props) {
  const reduceMotion = useReducedMotion();
  const [open, setOpen] = useState<Record<string, boolean>>({});

  if (batches.length === 0) {
    return (
      <p className={`text-sm text-muted-foreground ${className}`}>
        No batch documentation recorded for this audit.
      </p>
    );
  }

  return (
    <div className={`space-y-2 ${className}`} data-testid="inventory-check-batch-docs">
      <div>
        <h3 className="text-sm font-semibold text-foreground">Batch documentation</h3>
        <p className="text-xs text-muted-foreground mt-0.5">
          Recorded for the audit trail only — stock is still matched by product and size counts.
        </p>
      </div>
      <ul className="rounded-xl border border-border divide-y divide-border overflow-hidden bg-card">
        {batches.map(group => {
          const isOpen = !!open[group.batch_no];
          return (
            <li key={group.batch_no}>
              <motion.button
                type="button"
                whileTap={reduceMotion ? undefined : { scale: 0.99 }}
                className="w-full flex items-center gap-3 px-3 py-3 text-left hover:bg-secondary/40 transition-colors"
                onClick={() => setOpen(prev => ({ ...prev, [group.batch_no]: !isOpen }))}
                aria-expanded={isOpen}
                data-testid={`inventory-check-batch-${group.batch_no}`}
              >
                <ChevronDown
                  size={16}
                  className={`shrink-0 text-muted-foreground transition-transform ${isOpen ? "rotate-0" : "-rotate-90"}`}
                  aria-hidden
                />
                <span className="font-mono text-sm font-semibold tracking-wide flex-1 min-w-0 truncate">
                  {group.batch_no}
                </span>
                <span className="text-xs text-muted-foreground tabular-nums shrink-0">
                  {group.lines.length} date{group.lines.length === 1 ? "" : "s"}
                </span>
                <span className="text-sm font-semibold tabular-nums shrink-0">{group.total_qty}</span>
              </motion.button>
              <AnimatePresence initial={false}>
                {isOpen ? (
                  <motion.div
                    key="body"
                    initial={reduceMotion ? false : { height: 0, opacity: 0 }}
                    animate={{ height: "auto", opacity: 1 }}
                    exit={reduceMotion ? undefined : { height: 0, opacity: 0 }}
                    transition={{ duration: 0.2, ease: [0.22, 1, 0.36, 1] }}
                    className="overflow-hidden border-t border-border/80 bg-secondary/15"
                  >
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="text-left text-[11px] uppercase tracking-wide text-muted-foreground">
                          <th className="px-3 py-2 font-medium">Bottled</th>
                          <th className="px-3 py-2 font-medium">Product</th>
                          <th className="px-3 py-2 font-medium">Size</th>
                          <th className="px-3 py-2 font-medium text-right">Qty</th>
                        </tr>
                      </thead>
                      <tbody>
                        {group.lines.map((line, idx) => (
                          <tr
                            key={`${line.bottling_date ?? ""}-${line.variant ?? ""}-${line.size ?? ""}-${idx}`}
                            className="border-t border-border/50"
                          >
                            <td className="px-3 py-2 tabular-nums whitespace-nowrap">
                              {line.bottling_date ?? "—"}
                            </td>
                            <td className="px-3 py-2">
                              {line.product_label}
                              {line.variant ? ` · ${line.variant}` : ""}
                            </td>
                            <td className="px-3 py-2">{line.size_label ?? "—"}</td>
                            <td className="px-3 py-2 text-right font-semibold tabular-nums">{line.qty}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </motion.div>
                ) : null}
              </AnimatePresence>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
