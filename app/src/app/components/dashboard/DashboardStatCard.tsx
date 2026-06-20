import type { ElementType } from "react";
import { AppSectionEnter, appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

interface DashboardStatCardProps {
  icon: ElementType;
  label: string;
  value: string;
  hint?: string;
  index?: number;
  centered?: boolean;
}

export function DashboardStatCard({ icon: Icon, label, value, hint, index = 0, centered = false }: DashboardStatCardProps) {
  return (
    <AppSectionEnter index={index}>
      <div
        className={cn(
          "bg-card rounded-xl border border-border p-5 h-full",
          appHoverCard,
          centered && "text-center flex flex-col items-center",
        )}
        style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
      >
        <div className={cn("mb-3 w-full", centered ? "text-center" : "flex items-start justify-between")}>
          <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">{label}</p>
          {!centered && <Icon size={16} className="text-muted-foreground shrink-0" />}
        </div>
        {centered && (
          <div className="w-9 h-9 rounded-lg bg-[#C8900A]/10 flex items-center justify-center mb-2">
            <Icon size={18} className="text-[#C8900A]" />
          </div>
        )}
        <p className="text-2xl sm:text-3xl font-bold text-foreground tabular-nums">{value}</p>
        {hint && <p className="text-xs text-muted-foreground mt-1.5 max-w-[12rem]">{hint}</p>}
      </div>
    </AppSectionEnter>
  );
}
