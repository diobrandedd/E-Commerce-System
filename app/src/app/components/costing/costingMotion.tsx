import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { Loader2 } from "lucide-react";
import type { ReactNode } from "react";
import { cn } from "@/app/components/ui/utils";

export const costingSpring = { type: "spring" as const, stiffness: 420, damping: 34 };
export const costingEase = { duration: 0.28, ease: [0.22, 1, 0.36, 1] as const };

export function CostingCollapsible({
  open,
  children,
  className,
}: {
  open: boolean;
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {open && (
        <motion.div
          key="panel"
          initial={reduce ? false : { height: 0, opacity: 0 }}
          animate={{ height: "auto", opacity: 1 }}
          exit={reduce ? undefined : { height: 0, opacity: 0 }}
          transition={reduce ? { duration: 0 } : costingSpring}
          className={cn("overflow-hidden", className)}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function CostingDetailReveal({
  open,
  children,
  className,
}: {
  open: boolean;
  children: ReactNode;
  className?: string;
}) {
  const reduce = useReducedMotion();

  return (
    <AnimatePresence initial={false}>
      {open && (
        <motion.div
          key="detail"
          initial={reduce ? false : { opacity: 0, y: -6 }}
          animate={{ opacity: 1, y: 0 }}
          exit={reduce ? undefined : { opacity: 0, y: -4 }}
          transition={reduce ? { duration: 0 } : costingEase}
          className={className}
        >
          {children}
        </motion.div>
      )}
    </AnimatePresence>
  );
}

export function CostingDetailLoading({ label = "Loading formula…" }: { label?: string }) {
  const reduce = useReducedMotion();

  return (
    <div className="px-6 py-8" role="status" aria-live="polite">
      <div className="flex flex-col items-center gap-3">
        <Loader2 className="animate-spin text-[#C8900A]" size={22} aria-hidden />
        <p className="text-sm text-muted-foreground">{label}</p>
        {!reduce && (
          <div className="w-full max-w-md space-y-2 mt-1">
            {[72, 88, 64].map((w, i) => (
              <motion.div
                key={i}
                className="h-2.5 rounded-full bg-secondary"
                initial={{ opacity: 0.4, scaleX: 0.6 }}
                animate={{ opacity: [0.4, 0.85, 0.4], scaleX: [0.6, 1, 0.6] }}
                transition={{
                  duration: 1.2,
                  repeat: Infinity,
                  delay: i * 0.15,
                  ease: "easeInOut",
                }}
                style={{ width: `${w}%`, transformOrigin: "left" }}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

export function CostingSectionEnter({ children, index = 0 }: { children: ReactNode; index?: number }) {
  const reduce = useReducedMotion();
  if (reduce) return <>{children}</>;

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ ...costingEase, delay: index * 0.06 }}
    >
      {children}
    </motion.div>
  );
}
