import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { Check, X } from "lucide-react";
import { useEffect, useState, type ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

export const springSnappy = { type: "spring" as const, stiffness: 520, damping: 32 };
export const springSoft = { type: "spring" as const, stiffness: 400, damping: 30 };

export function POSPageTransition({
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
      initial={reduce ? false : { opacity: 0, y: 14 }}
      animate={{ opacity: 1, y: 0 }}
      exit={reduce ? undefined : { opacity: 0, y: -10 }}
      transition={{ duration: reduce ? 0 : 0.32, ease: [0.22, 1, 0.36, 1] }}
    >
      {children}
    </motion.div>
  );
}

export function POSModal({
  open,
  onClose,
  children,
  panelClassName,
}: {
  open: boolean;
  onClose: () => void;
  children: ReactNode;
  panelClassName?: string;
}) {
  const reduce = useReducedMotion();
  return (
    <AnimatePresence>
      {open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <motion.button
            type="button"
            aria-label="Close"
            className="absolute inset-0 bg-black/50 backdrop-blur-[2px]"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            onClick={onClose}
          />
          <motion.div
            role="dialog"
            aria-modal="true"
            className={cn(
              "relative w-full max-w-md overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-2xl ring-1 ring-black/5",
              panelClassName,
            )}
            initial={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.9, y: 20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={reduce ? { opacity: 0 } : { opacity: 0, scale: 0.96, y: 12 }}
            transition={reduce ? { duration: 0.15 } : springSoft}
            onClick={e => e.stopPropagation()}
          >
            {children}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

export function PressableRoundButton({
  onClick,
  disabled,
  children,
  className,
  ariaLabel,
}: {
  onClick: () => void;
  disabled?: boolean;
  children: ReactNode;
  className?: string;
  ariaLabel?: string;
}) {
  const reduce = useReducedMotion();
  return (
    <motion.button
      type="button"
      aria-label={ariaLabel}
      disabled={disabled}
      onClick={onClick}
      whileTap={disabled || reduce ? undefined : { scale: 0.86 }}
      whileHover={disabled || reduce ? undefined : { scale: 1.06 }}
      transition={springSnappy}
      className={cn(
        "w-10 h-10 rounded-full border flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed",
        className,
      )}
    >
      {children}
    </motion.button>
  );
}

export function AnimatedQuantity({ value, className }: { value: number; className?: string }) {
  const reduce = useReducedMotion();
  if (reduce) {
    return <span className={className}>{value}</span>;
  }
  return (
    <span className={cn("relative inline-flex w-12 justify-center overflow-hidden", className)}>
      <AnimatePresence mode="popLayout" initial={false}>
        <motion.span
          key={value}
          initial={{ opacity: 0, y: -10, scale: 0.75 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={{ opacity: 0, y: 10, scale: 0.75 }}
          transition={{ duration: 0.16, ease: "easeOut" }}
          className="text-2xl font-bold tabular-nums"
        >
          {value}
        </motion.span>
      </AnimatePresence>
    </span>
  );
}

export function VariantCardMotion({
  children,
  onClick,
  className,
}: {
  children: ReactNode;
  onClick: () => void;
  className?: string;
}) {
  const reduce = useReducedMotion();
  return (
    <motion.button
      type="button"
      onClick={onClick}
      whileTap={reduce ? undefined : { scale: 0.94 }}
      whileHover={reduce ? undefined : { scale: 1.02, y: -2 }}
      transition={springSnappy}
      className={className}
    >
      {children}
    </motion.button>
  );
}

export function FeedbackBurst({
  kind,
  show,
  onDone,
}: {
  kind: "success" | "error";
  show: boolean;
  onDone?: () => void;
}) {
  const reduce = useReducedMotion();
  const isSuccess = kind === "success";

  useEffect(() => {
    if (!show || !onDone) return;
    const t = window.setTimeout(onDone, reduce ? 400 : 700);
    return () => window.clearTimeout(t);
  }, [show, onDone, reduce]);

  return (
    <AnimatePresence>
      {show && (
        <motion.div
          className="fixed inset-0 z-[60] flex items-center justify-center pointer-events-none"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.15 }}
        >
          <motion.div
            initial={reduce ? { opacity: 0 } : { scale: 0.4, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={reduce ? { opacity: 0 } : { scale: 1.15, opacity: 0 }}
            transition={springSoft}
            className={cn(
              "w-20 h-20 rounded-full flex items-center justify-center shadow-lg",
              isSuccess ? "bg-green-500 text-white" : "bg-red-500 text-white",
            )}
          >
            {isSuccess ? <Check size={40} strokeWidth={3} /> : <X size={40} strokeWidth={3} />}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function ActionFeedbackButton({
  variant,
  onClick,
  disabled,
  children,
  className,
  flashOnClick = true,
}: {
  variant: "confirm" | "cancel" | "primary";
  onClick: () => void;
  disabled?: boolean;
  children: ReactNode;
  className?: string;
  flashOnClick?: boolean;
}) {
  const reduce = useReducedMotion();
  const [flash, setFlash] = useState<"success" | "error" | null>(null);

  const handleClick = () => {
    if (disabled) return;
    if (flashOnClick && !reduce) {
      setFlash(variant === "cancel" ? "error" : "success");
      window.setTimeout(() => setFlash(null), 420);
    }
    onClick();
  };

  const base =
    variant === "cancel"
      ? "border border-border text-foreground hover:bg-secondary"
      : variant === "primary"
        ? "text-white"
        : "text-white";

  return (
    <motion.button
      type="button"
      disabled={disabled}
      onClick={handleClick}
      whileTap={disabled || reduce ? undefined : { scale: 0.96 }}
      transition={springSnappy}
      className={cn("relative overflow-hidden flex-1 py-2.5 rounded-lg text-sm font-medium", base, className)}
      style={variant !== "cancel" ? { background: "#C8900A" } : undefined}
    >
      <AnimatePresence mode="wait" initial={false}>
        {flash ? (
          <motion.span
            key="icon"
            initial={{ opacity: 0, scale: 0.5 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.5 }}
            transition={{ duration: 0.12 }}
            className="inline-flex items-center justify-center gap-1"
          >
            {flash === "success" ? <Check size={16} strokeWidth={3} /> : <X size={16} strokeWidth={3} />}
          </motion.span>
        ) : (
          <motion.span key="label" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}>
            {children}
          </motion.span>
        )}
      </AnimatePresence>
    </motion.button>
  );
}

export function CartLineMotion({ children }: { children: ReactNode }) {
  return (
    <motion.div
      layout
      initial={{ opacity: 0, x: -12, scale: 0.98 }}
      animate={{ opacity: 1, x: 0, scale: 1 }}
      exit={{ opacity: 0, x: 12, scale: 0.98 }}
      transition={springSoft}
    >
      {children}
    </motion.div>
  );
}
