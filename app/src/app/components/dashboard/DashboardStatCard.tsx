import type { ElementType } from "react";
import { Link } from "react-router";
import { AppSectionEnter, appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { cn } from "@/app/components/ui/utils";

interface DashboardStatCardProps {
  icon: ElementType;
  label: string;
  value: string;
  hint?: string;
  index?: number;
  centered?: boolean;
  /** Deep link — card becomes a navigable action */
  to?: string;
  /** Emphasize as the primary ops metric (e.g. packing backlog) */
  emphasize?: boolean;
}

export function DashboardStatCard({
  icon: Icon,
  label,
  value,
  hint,
  index = 0,
  centered = false,
  to,
  emphasize = false,
}: DashboardStatCardProps) {
  const inner = (
    <>
      <div className={cn("mb-3 w-full", centered ? "text-center" : "flex items-start justify-between")}>
        <p
          className={cn(
            "text-xs font-medium uppercase tracking-wide",
            emphasize ? "text-[#C8900A]" : "text-muted-foreground",
          )}
        >
          {label}
        </p>
        {!centered && (
          <Icon
            size={16}
            className={cn("shrink-0", emphasize ? "text-[#C8900A]" : "text-muted-foreground")}
          />
        )}
      </div>
      {centered && (
        <div
          className={cn(
            "w-9 h-9 rounded-lg flex items-center justify-center mb-2",
            emphasize ? "bg-[#C8900A]/20" : "bg-[#C8900A]/10",
          )}
        >
          <Icon size={18} className="text-[#C8900A]" />
        </div>
      )}
      <p
        className={cn(
          "text-2xl sm:text-3xl font-bold tabular-nums",
          emphasize ? "text-[#C8900A]" : "text-foreground",
        )}
      >
        {value}
      </p>
      {hint && <p className="text-xs text-muted-foreground mt-1.5 max-w-[12rem]">{hint}</p>}
    </>
  );

  const shellClass = cn(
    "bg-card rounded-xl border p-5 h-full block",
    appHoverCard,
    centered && "text-center flex flex-col items-center",
    emphasize ? "border-[#C8900A]/45 shadow-sm shadow-[#C8900A]/10" : "border-border",
    to && "cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C8900A]/40",
  );

  return (
    <AppSectionEnter index={index}>
      {to ? (
        <Link to={to} className={shellClass} style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          {inner}
        </Link>
      ) : (
        <div className={shellClass} style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          {inner}
        </div>
      )}
    </AppSectionEnter>
  );
}
