import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import type { ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

/** Shared easing — matches AppModal and CSR fulfillment feel. */
export const appFeedbackEase = { duration: 0.28, ease: [0.22, 1, 0.36, 1] as const };
export const appFeedbackSpring = { type: "spring" as const, stiffness: 420, damping: 32 };

/** Tailwind hover tokens — use on cards, rows, and header action buttons. */
export const appHoverCard =
  "transition-all duration-200 ease-out hover:border-[#C8900A]/55 hover:shadow-lg hover:shadow-[#C8900A]/12 hover:ring-2 hover:ring-[#C8900A]/15";

export const appHoverRow =
  "transition-[background-color,box-shadow] duration-200 ease-out hover:bg-secondary/50 hover:shadow-[0_4px_14px_-4px_rgba(0,0,0,0.14)]";

export const appHoverPressableOutline =
  "transition-all duration-200 ease-out hover:bg-secondary/60 hover:border-[#C8900A]/40 hover:shadow-md hover:shadow-black/5";

export const appHoverPressablePrimary =
  "transition-all duration-200 ease-out hover:brightness-105 hover:shadow-lg hover:shadow-[#C8900A]/25";

/** PageLayout header — Guide, secondary links, muted actions. */
export const appHeaderActionClass =
  "inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground px-2.5 py-1.5 sm:px-3 rounded-lg border border-border transition-all duration-200 ease-out hover:text-foreground hover:bg-secondary/40 hover:border-[#C8900A]/35 hover:shadow-sm motion-safe:active:scale-[0.97]";

export function AppSectionEnter({
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
      transition={{ ...appFeedbackEase, delay: index * 0.06 }}
    >
      {children}
    </motion.div>
  );
}

export function AppStepSwitch({
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
        transition={reduce ? { duration: 0 } : appFeedbackEase}
      >
        {children}
      </motion.div>
    </AnimatePresence>
  );
}

export function AppSuccessNotice({
  message,
  className,
}: {
  message: string | null;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {message ? (
        <motion.p
          role="status"
          initial={reduce ? false : { opacity: 0, y: -8, scale: 0.98 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={reduce ? undefined : { opacity: 0, y: -6, scale: 0.98 }}
          transition={appFeedbackEase}
          className={cn(
            "text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg px-3 py-2 mb-3",
            className,
          )}
        >
          {message}
        </motion.p>
      ) : null}
    </AnimatePresence>
  );
}

export function AppErrorNotice({
  message,
  className,
}: {
  message: string | null;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {message ? (
        <motion.p
          role="alert"
          initial={reduce ? false : { opacity: 0, y: -6, scale: 0.98 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={reduce ? undefined : { opacity: 0, y: -4, scale: 0.98 }}
          transition={appFeedbackEase}
          className={cn("text-sm text-destructive mb-3", className)}
        >
          {message}
        </motion.p>
      ) : null}
    </AnimatePresence>
  );
}

/** Bordered error panel — use for load failures and validation errors. */
export function AppErrorBanner({
  message,
  className,
}: {
  message: string | null;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {message ? (
        <motion.div
          role="alert"
          initial={reduce ? false : { opacity: 0, y: -6, scale: 0.98 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          exit={reduce ? undefined : { opacity: 0, y: -4, scale: 0.98 }}
          transition={appFeedbackEase}
          className={cn(
            "mb-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800",
            className,
          )}
        >
          {message}
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}

export function AppTaskCompletePanel({
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
      transition={appFeedbackSpring}
    >
      {children}
    </motion.div>
  );
}

/** Primary / outline button with hover lift + tap scale — prefer over raw `<button>`. */
export function AppPressable({
  variant = "outline",
  disabled,
  onClick,
  children,
  className,
  type = "button",
}: {
  variant?: "outline" | "primary" | "ghost";
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
        variant === "primary" && cn("text-primary-foreground bg-primary", appHoverPressablePrimary),
        variant === "outline" && cn("border border-border", appHoverPressableOutline),
        variant === "ghost" &&
          "text-muted-foreground hover:text-foreground hover:bg-secondary/50 transition-colors duration-200",
        className,
      )}
    >
      {children}
    </motion.button>
  );
}
