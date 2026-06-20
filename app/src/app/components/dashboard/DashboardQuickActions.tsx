import type { ElementType } from "react";
import { Link } from "react-router";
import { AppSectionEnter, appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

export interface QuickAction {
  icon: ElementType;
  label: string;
  description: string;
  to: string;
}

interface DashboardQuickActionsProps {
  actions: QuickAction[];
  title?: string;
  index?: number;
}

export function DashboardQuickActions({ actions, title = "Quick actions", index = 0 }: DashboardQuickActionsProps) {
  if (actions.length === 0) return null;

  return (
    <AppSectionEnter index={index}>
      <div className="bg-card rounded-xl border border-border p-5 sm:p-6" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
        <h3 className="text-sm font-semibold text-foreground mb-4">{title}</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          {actions.map(({ icon: Icon, label, description, to }) => (
            <Link
              key={to}
              to={to}
              className={cn(
                "flex items-start gap-3 rounded-xl border border-border p-4 bg-background/50",
                appHoverCard,
              )}
            >
              <div className="w-9 h-9 rounded-lg bg-[#C8900A]/15 flex items-center justify-center flex-shrink-0">
                <Icon size={18} className="text-[#C8900A]" />
              </div>
              <div className="min-w-0">
                <p className="text-sm font-medium text-foreground">{label}</p>
                <p className="text-xs text-muted-foreground mt-0.5">{description}</p>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </AppSectionEnter>
  );
}
