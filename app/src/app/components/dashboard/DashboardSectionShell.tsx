import type { ReactNode } from "react";
import { AppSectionEnter } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

interface DashboardSectionShellProps {
  title: string;
  description?: string;
  action?: ReactNode;
  children: ReactNode;
  index?: number;
  className?: string;
}

/** Shared executive dashboard section — centered header + card body. */
export function DashboardSectionShell({
  title,
  description,
  action,
  children,
  index = 0,
  className,
}: DashboardSectionShellProps) {
  return (
    <AppSectionEnter index={index}>
      <section className={cn("w-full", className)}>
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4 text-center sm:text-left">
          <div className="mx-auto sm:mx-0">
            <div className="inline-flex items-center gap-2 justify-center sm:justify-start">
              <span className="w-1 h-4 rounded-full bg-[#C8900A]" aria-hidden />
              <h2 className="text-sm font-semibold text-foreground tracking-tight">{title}</h2>
            </div>
            {description && (
              <p className="text-xs text-muted-foreground mt-1 max-w-md">{description}</p>
            )}
          </div>
          {action && <div className="flex justify-center sm:justify-end shrink-0">{action}</div>}
        </div>
        <div
          className="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
          style={{ boxShadow: "0 2px 12px rgba(42,31,0,0.06)" }}
        >
          {children}
        </div>
      </section>
    </AppSectionEnter>
  );
}
