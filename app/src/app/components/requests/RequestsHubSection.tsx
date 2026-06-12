import { type ReactNode, useState } from "react";
import { ChevronDown } from "lucide-react";
import { motion, useReducedMotion } from "motion/react";
import { CostingCollapsible } from "@/app/components/costing/costingMotion";
import { cn } from "@/app/components/ui/utils";

interface RequestsHubSectionProps {
  id: string;
  title: string;
  subtitle?: string;
  /** Shown when > 0 — e.g. pending approval count */
  pendingCount?: number;
  totalCount?: number;
  open?: boolean;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  headerActions?: ReactNode;
  children: ReactNode;
  className?: string;
}

export function RequestsHubSection({
  id,
  title,
  subtitle,
  pendingCount = 0,
  totalCount,
  open: controlledOpen,
  defaultOpen = false,
  onOpenChange,
  headerActions,
  children,
  className,
}: RequestsHubSectionProps) {
  const [internalOpen, setInternalOpen] = useState(defaultOpen);
  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;
  const reduce = useReducedMotion();

  const countLabel =
    totalCount != null
      ? pendingCount > 0
        ? `${pendingCount} pending · ${totalCount} total`
        : `${totalCount} total`
      : pendingCount > 0
        ? `${pendingCount} pending`
        : null;

  return (
    <section id={id} className={cn("rounded-xl border border-border bg-card overflow-hidden", className)}>
      <div className="flex items-start gap-2 px-4 py-3.5">
        <button
          type="button"
          onClick={() => setOpen(!open)}
          aria-expanded={open}
          aria-controls={`${id}-panel`}
          className="flex-1 flex items-start gap-3 text-left min-w-0 rounded-lg hover:bg-secondary/50 active:bg-secondary/70 transition-colors -my-1 py-1"
        >
          <motion.span
            animate={{ rotate: open ? 180 : 0 }}
            transition={reduce ? { duration: 0 } : { duration: 0.22, ease: [0.4, 0, 0.2, 1] }}
            className="mt-0.5 shrink-0 text-muted-foreground"
          >
            <ChevronDown size={16} aria-hidden />
          </motion.span>
          <div className="flex-1 min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <h3 className="text-sm font-semibold text-foreground">{title}</h3>
              {pendingCount > 0 && (
                <span className="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-100 text-amber-900">
                  {pendingCount} pending
                </span>
              )}
              {countLabel && pendingCount === 0 && totalCount != null && totalCount > 0 && (
                <span className="text-[10px] font-medium px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                  {totalCount} total
                </span>
              )}
            </div>
            {subtitle && <p className="text-xs text-muted-foreground mt-0.5 leading-relaxed">{subtitle}</p>}
          </div>
        </button>
        {headerActions ? (
          <div className="shrink-0 flex flex-wrap items-center gap-2 pt-0.5" onClick={e => e.stopPropagation()}>
            {headerActions}
          </div>
        ) : null}
      </div>

      <CostingCollapsible open={open}>
        <div id={`${id}-panel`} className="border-t border-border px-4 py-4">
          {children}
        </div>
      </CostingCollapsible>
    </section>
  );
}
