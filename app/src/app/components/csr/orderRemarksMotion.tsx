import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import type { ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

export const remarksSpring = { type: "spring" as const, stiffness: 440, damping: 32 };
export const remarksEase = { duration: 0.28, ease: [0.22, 1, 0.36, 1] as const };

export function OrderRemarksSectionEnter({
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
      transition={{ ...remarksEase, delay: index * 0.07 }}
    >
      {children}
    </motion.div>
  );
}

export function OrderRemarksCarouselCard({
  children,
  cardKey,
  className,
}: {
  children: ReactNode;
  cardKey: number;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.div
      layout
      layoutId={`order-remarks-card-${cardKey}`}
      initial={reduce ? false : { opacity: 0, scale: 0.94, x: 28 }}
      animate={{ opacity: 1, scale: 1, x: 0 }}
      exit={reduce ? undefined : { opacity: 0, scale: 0.9, x: -32, filter: "blur(2px)" }}
      transition={reduce ? { duration: 0 } : remarksSpring}
      className={className}
    >
      {children}
    </motion.div>
  );
}

export function OrderRemarksStepReveal({
  show,
  children,
  className,
}: {
  show: boolean;
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {show && (
        <motion.div
          key="step"
          initial={reduce ? false : { opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={reduce ? undefined : { opacity: 0, height: 0 }}
          transition={reduce ? { duration: 0 } : remarksSpring}
          className={cn("overflow-hidden", className)}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function OrderRemarksDoneBadge({ show, children }: { show: boolean; children: ReactNode }) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {show && (
        <motion.div
          initial={reduce ? false : { opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={reduce ? undefined : { opacity: 0, height: 0 }}
          transition={remarksEase}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function VariantPresetButton({
  active,
  disabled,
  onClick,
  label,
}: {
  active: boolean;
  disabled?: boolean;
  onClick: () => void;
  label: string;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.button
      type="button"
      disabled={disabled}
      onClick={onClick}
      layout
      whileTap={disabled || reduce ? undefined : { scale: 0.92 }}
      whileHover={disabled || reduce ? undefined : { scale: 1.04, y: -2 }}
      transition={remarksSpring}
      className={cn(
        "flex-shrink-0 snap-start min-w-[4.5rem] px-3 py-3 rounded-xl border-2 text-center",
        active
          ? "border-[#C8900A] bg-[#C8900A]/15 shadow-sm"
          : "border-border bg-card hover:border-[#C8900A]/60 hover:bg-[#C8900A]/5",
        disabled && "opacity-50 pointer-events-none",
      )}
    >
      <motion.span
        className="block text-lg font-bold text-[#C8900A]"
        animate={active && !reduce ? { scale: [1, 1.12, 1] } : { scale: 1 }}
        transition={{ duration: 0.35 }}
      >
        {label}
      </motion.span>
    </motion.button>
  );
}

export function OrderRemarksScrollButton({
  onClick,
  disabled,
  ariaLabel,
  children,
  side,
}: {
  onClick: () => void;
  disabled?: boolean;
  ariaLabel: string;
  children: ReactNode;
  side: "left" | "right";
}) {
  const reduce = useReducedMotion();

  return (
    <motion.button
      type="button"
      aria-label={ariaLabel}
      disabled={disabled}
      onClick={onClick}
      whileTap={disabled || reduce ? undefined : { scale: 0.88 }}
      whileHover={disabled || reduce ? undefined : { scale: 1.08 }}
      transition={remarksSpring}
      className={cn(
        "absolute top-1/2 -translate-y-1/2 z-10 w-7 h-7 rounded-full bg-card border border-border shadow flex items-center justify-center text-muted-foreground hover:text-foreground disabled:opacity-40",
        side === "left" ? "left-0" : "right-0",
      )}
    >
      {children}
    </motion.button>
  );
}

export function OrderRemarksToast({ message }: { message: string | null }) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence>
      {message && (
        <motion.div
          role="status"
          aria-live="polite"
          className="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 px-4 py-2.5 rounded-full bg-foreground text-background text-sm font-medium shadow-lg pointer-events-none"
          initial={reduce ? false : { opacity: 0, y: 16, scale: 0.92 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={reduce ? undefined : { opacity: 0, y: 10, scale: 0.95 }}
          transition={remarksEase}
        >
          {message}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function OrderRemarksAnimatedCount({ value, className }: { value: number; className?: string }) {
  const reduce = useReducedMotion();
  if (reduce) {
    return <span className={className}>{value}</span>;
  }

  return (
    <span className={cn("inline-flex tabular-nums overflow-hidden", className)}>
      <AnimatePresence mode="popLayout" initial={false}>
        <motion.span
          key={value}
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          exit={{ opacity: 0, y: 8 }}
          transition={{ duration: 0.18, ease: "easeOut" }}
        >
          {value}
        </motion.span>
      </AnimatePresence>
    </span>
  );
}

export function OrderRemarksAllDonePanel({ children }: { children: ReactNode }) {
  const reduce = useReducedMotion();

  return (
    <motion.div
      initial={reduce ? false : { opacity: 0, scale: 0.96 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={remarksSpring}
    >
      {children}
    </motion.div>
  );
}

export function OrderRemarksCollapsible({
  open,
  children,
}: {
  open: boolean;
  children: ReactNode;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {open && (
        <motion.div
          initial={reduce ? false : { height: 0, opacity: 0 }}
          animate={{ height: "auto", opacity: 1 }}
          exit={reduce ? undefined : { height: 0, opacity: 0 }}
          transition={reduce ? { duration: 0 } : remarksSpring}
          className="overflow-hidden"
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}
