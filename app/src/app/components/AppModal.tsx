import type { ReactNode } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { X } from "lucide-react";
import { cn } from "@/app/components/ui/utils";
import { appFeedbackEase } from "@/app/components/layout/appFeedbackMotion";

/** Shared motion spring — same feel as CSR fulfillment modals. */
export const appModalSpring = { type: "spring" as const, stiffness: 420, damping: 32 };

export const appModalBackdropClass = "bg-black/50 backdrop-blur-[2px]";

export const appModalPanelClass =
  "bg-card text-card-foreground rounded-2xl border border-border shadow-2xl ring-1 ring-black/5";

/**
 * Standard app modal shell — dimmed blurred backdrop + elevated card panel.
 * Use with AppModalHeader / AppModalBody / AppModalFooter for layout.
 */
export function AppModal({
  open,
  onClose,
  children,
  panelClassName,
  labelledBy,
  zIndex = "z-[100]",
}: {
  open: boolean;
  onClose: () => void;
  children: ReactNode;
  panelClassName?: string;
  labelledBy?: string;
  zIndex?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence>
      {open && (
        <div className={cn("fixed inset-0 flex items-center justify-center p-4 overflow-y-auto", zIndex)}>
          <motion.button
            type="button"
            aria-label="Close"
            className={cn("absolute inset-0", appModalBackdropClass)}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            onClick={onClose}
          />
          <motion.div
            role="dialog"
            aria-modal="true"
            aria-labelledby={labelledBy}
            className={cn(
              "relative w-full max-w-lg my-4 max-h-[90vh] overflow-hidden flex flex-col",
              appModalPanelClass,
              panelClassName,
            )}
            initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.92, y: 16 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.96, y: 10 }}
            transition={reduce ? { duration: 0.15 } : appModalSpring}
            onClick={(e) => e.stopPropagation()}
          >
            {children}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

export function AppModalHeader({
  title,
  subtitle,
  titleId,
  onClose,
  children,
}: {
  title: ReactNode;
  subtitle?: ReactNode;
  titleId?: string;
  onClose: () => void;
  children?: ReactNode;
}) {
  const reduce = useReducedMotion();

  return (
    <div className="flex items-start justify-between gap-3 px-5 pt-5 pb-4 border-b border-border flex-shrink-0">
      <div className="min-w-0">
        <h2 id={titleId} className="text-lg font-semibold text-foreground">
          {title}
        </h2>
        {subtitle ? <p className="text-xs text-muted-foreground mt-1">{subtitle}</p> : null}
      </div>
      <div className="flex items-center gap-1 flex-shrink-0">
        {children}
        <motion.button
          type="button"
          onClick={onClose}
          aria-label="Close"
          whileTap={reduce ? undefined : { scale: 0.9 }}
          whileHover={reduce ? undefined : { scale: 1.06 }}
          transition={appFeedbackEase}
          className="rounded-lg p-1.5 text-muted-foreground hover:bg-muted/60 hover:text-foreground transition-colors duration-200"
        >
          <X size={18} />
        </motion.button>
      </div>
    </div>
  );
}

export function AppModalBody({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn("overflow-y-auto px-5 py-4 flex-1", className)}>{children}</div>
  );
}

export function AppModalFooter({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "flex flex-wrap gap-2 px-5 py-4 border-t border-border bg-muted/20 flex-shrink-0",
        className,
      )}
    >
      {children}
    </div>
  );
}
