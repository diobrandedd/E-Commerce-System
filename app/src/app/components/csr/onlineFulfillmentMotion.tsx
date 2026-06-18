import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import type { ButtonHTMLAttributes, ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";
import {
  AppPressable,
  AppErrorBanner,
  appFeedbackEase,
  appFeedbackSpring,
  appHoverCard,
  appHoverPressableOutline,
  appHoverPressablePrimary,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";

export {
  OrderRemarksToast as FulfillmentToast,
  OrderRemarksAnimatedCount as AnimatedStat,
  remarksEase,
  remarksSpring,
} from "@/app/components/csr/orderRemarksMotion";

import { remarksEase, remarksSpring } from "@/app/components/csr/orderRemarksMotion";

/** @deprecated Use `appHoverCard` from `appFeedbackMotion` in new code. */
export const fulfillmentHoverCard = appHoverCard;
/** @deprecated Use `appHoverRow` from `appFeedbackMotion`. */
export const fulfillmentHoverRow = appHoverRow;
/** @deprecated Use `appHoverPressableOutline` from `appFeedbackMotion`. */
export const fulfillmentHoverPressableOutline = appHoverPressableOutline;
/** @deprecated Use `appHoverPressablePrimary` from `appFeedbackMotion`. */
export const fulfillmentHoverPressablePrimary = appHoverPressablePrimary;

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
  return <AppErrorBanner message={message} />;
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

/** @deprecated Import `AppModal` from `@/app/components/AppModal` for new code. */
export { AppModal as FulfillmentModal } from "@/app/components/AppModal";

export function FulfillmentPickerCard({
  index,
  onClick,
  children,
  className,
  ...rest
}: {
  index: number;
  onClick: () => void;
  children: ReactNode;
  className?: string;
} & ButtonHTMLAttributes<HTMLButtonElement>) {
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
      {...rest}
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
  return (
    <AppPressable
      variant={variant}
      disabled={disabled}
      onClick={onClick}
      className={className}
      type={type}
    >
      {children}
    </AppPressable>
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
