import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import type { ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

export {
  OrderRemarksToast as FulfillmentToast,
  OrderRemarksAnimatedCount as AnimatedStat,
  remarksEase,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";

import { remarksEase, remarksSpring } from "@/app/components/csr/orderRemarksMotion";

/** Tailwind hover tokens — reuse on cards, rows, and pressables. */
export const fulfillmentHoverCard =
  "transition-all duration-200 ease-out hover:border-[#C8900A]/55 hover:shadow-lg hover:shadow-[#C8900A]/12 hover:ring-2 hover:ring-[#C8900A]/15";

export const fulfillmentHoverRow =
  "transition-[background-color,box-shadow] duration-200 ease-out hover:bg-secondary/50 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.14)]";

export const fulfillmentHoverPressableOutline =
  "transition-all duration-200 ease-out hover:bg-secondary/60 hover:border-[#C8900A]/40 hover:shadow-md hover:shadow-black/5";

export const fulfillmentHoverPressablePrimary =
  "transition-all duration-200 ease-out hover:brightness-105 hover:shadow-lg hover:shadow-[#C8900A]/25";

export function FulfillmentSectionEnter({
  children,
  index = 0,
  className,
}: {
  children: ReactNode;
  index?: number;
  className?: string;
}) {
  const reduce = useReducedMotion();
  if (reduce) return <div className={className}>{children}</div>;

  return (
    <motion.div
      className={className}
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ ...remarksEase, delay: index * 0.06 }}
    >
      {children}
    </motion.div>
  );
}

export function FulfillmentStepSwitch({
  stepKey,
  children,
  className,
}: {
  stepKey: string;
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence mode="wait">
      <motion.div
        key={stepKey}
        className={className}
        initial={reduce ? false : { opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        exit={reduce ? undefined : { opacity: 0, y: -8 }}
        transition={reduce ? { duration: 0 } : remarksEase}
      >
        {children}
      </motion.div>
    </AnimatePresence>
  );
}

export function FulfillmentErrorBanner({ message }: { message: string | null }) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {message && (
        <motion.div
          role="alert"
          initial={reduce ? false : { opacity: 0, y: -6, scale: 0.98 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={reduce ? undefined : { opacity: 0, y: -4, scale: 0.98 }}
          transition={remarksEase}
          className="mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800"
        >
          {message}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function FulfillmentSuccessPanel({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.div
      className={cn("bg-green-50 border border-green-200 rounded-xl p-6 max-w-lg space-y-3", className)}
      initial={reduce ? false : { opacity: 0, scale: 0.97 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={remarksSpring}
    >
      {children}
    </motion.div>
  );
}

export function FulfillmentModal({
  open,
  onClose,
  children,
  panelClassName,
  labelledBy,
}: {
  open: boolean;
  onClose: () => void;
  children: ReactNode;
  panelClassName?: string;
  labelledBy?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence>
      {open && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto">
          <motion.button
            type="button"
            aria-label="Close"
            className="absolute inset-0 bg-black/40"
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
            className={cn("relative w-full max-w-lg my-4 max-h-[90vh] overflow-y-auto", panelClassName)}
            initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.92, y: 16 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.96, y: 10 }}
            transition={reduce ? { duration: 0.15 } : remarksSpring}
            onClick={e => e.stopPropagation()}
          >
            {children}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

export function FulfillmentPickerCard({
  index,
  onClick,
  children,
  className,
}: {
  index: number;
  onClick: () => void;
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.button
      type="button"
      onClick={onClick}
      initial={reduce ? false : { opacity: 0, y: 14 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ ...remarksEase, delay: index * 0.08 }}
      whileTap={reduce ? undefined : { scale: 0.97 }}
      whileHover={reduce ? undefined : { y: -4 }}
      className={cn(
        "text-left bg-card rounded-xl border border-border p-5",
        fulfillmentHoverCard,
        className,
      )}
    >
      {children}
    </motion.button>
  );
}

export function FulfillmentTableRow({
  index,
  children,
  className,
  onClick,
}: {
  index: number;
  children: ReactNode;
  className?: string;
  onClick?: () => void;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.tr
      className={cn(onClick && fulfillmentHoverRow, className)}
      onClick={onClick}
      initial={reduce ? false : { opacity: 0, y: 6 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ ...remarksEase, delay: Math.min(index * 0.03, 0.24) }}
    >
      {children}
    </motion.tr>
  );
}

export function FulfillmentPressable({
  variant = "outline",
  disabled,
  onClick,
  children,
  className,
  type = "button",
}: {
  variant?: "outline" | "primary";
  disabled?: boolean;
  onClick?: () => void;
  children: ReactNode;
  className?: string;
  type?: "button" | "submit";
}) {
  const reduce = useReducedMotion();

  return (
    <motion.button
      type={type}
      disabled={disabled}
      onClick={onClick}
      whileTap={disabled || reduce ? undefined : { scale: 0.97 }}
      whileHover={disabled || reduce ? undefined : { y: -1 }}
      className={cn(
        "text-sm font-semibold px-5 py-2.5 rounded-lg disabled:opacity-50 disabled:pointer-events-none",
        variant === "primary"
          ? cn("text-primary-foreground bg-primary", fulfillmentHoverPressablePrimary)
          : cn("border border-border", fulfillmentHoverPressableOutline),
        className,
      )}
    >
      {children}
    </motion.button>
  );
}

export function FulfillmentPanelEnter({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.div
      className={className}
      initial={reduce ? false : { opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      transition={remarksSpring}
    >
      {children}
    </motion.div>
  );
}
