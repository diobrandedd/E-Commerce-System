import { ChevronDown } from "lucide-react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { cn } from "@/app/components/ui/utils";

export const sidebarNavCollapseTransition = {
  duration: 0.22,
  ease: [0.4, 0, 0.2, 1] as const,
};

export function SidebarNavCollapse({
  open,
  children,
  className,
}: {
  open: boolean;
  children: React.ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  if (reduce) {
    return open ? <div className={className}>{children}</div> : null;
  }

  return (
    <AnimatePresence initial={false}>
      {open && (
        <motion.div
          initial={{ height: 0, opacity: 0 }}
          animate={{ height: "auto", opacity: 1 }}
          exit={{ height: 0, opacity: 0 }}
          transition={sidebarNavCollapseTransition}
          className={cn("overflow-hidden", className)}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function SidebarNavChevron({ expanded, size = 14 }: { expanded: boolean; size?: number }) {
  const reduce = useReducedMotion();

  return (
    <motion.span
      animate={{ rotate: expanded ? 180 : 0 }}
      transition={reduce ? { duration: 0 } : { duration: 0.22, ease: [0.4, 0, 0.2, 1] }}
      className="inline-flex shrink-0"
    >
      <ChevronDown size={size} aria-hidden />
    </motion.span>
  );
}

const sidebarNavTap = { scale: 0.98 };

export function SidebarNavToggleButton({
  children,
  className,
  onClick,
}: {
  children: React.ReactNode;
  className?: string;
  onClick: () => void;
}) {
  const reduce = useReducedMotion();

  return (
    <motion.button
      type="button"
      onClick={onClick}
      whileTap={reduce ? undefined : sidebarNavTap}
      transition={{ duration: 0.1 }}
      className={className}
    >
      {children}
    </motion.button>
  );
}
