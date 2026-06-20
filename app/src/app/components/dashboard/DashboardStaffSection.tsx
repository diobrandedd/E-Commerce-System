import { CalendarDays, ClipboardList, FileEdit, PlusCircle } from "lucide-react";
import { AppSectionEnter } from "@/app/components/layout/appFeedbackMotion";
import { DashboardQuickActions } from "@/app/components/dashboard/DashboardQuickActions";
import { DashboardEditRequestsPanel } from "@/app/components/dashboard/DashboardEditRequestsPanel";
import { DashboardStatCard } from "@/app/components/dashboard/DashboardStatCard";
import type { EditRequestRow } from "@/app/components/dashboard/dashboardTypes";

interface DashboardStaffSectionProps {
  pendingCount: number;
  editRequests: EditRequestRow[];
  loading: boolean;
}

export function DashboardStaffSection({ pendingCount, editRequests, loading }: DashboardStaffSectionProps) {
  const quickActions = [
    {
      icon: PlusCircle,
      label: "Stock Input",
      description: "Log new stock entries",
      to: "/stock-input",
    },
    {
      icon: CalendarDays,
      label: "Daily Count",
      description: "Submit today's count",
      to: "/daily-count",
    },
    {
      icon: FileEdit,
      label: "Edit Requests",
      description: "Request inventory corrections",
      to: "/edit-requests",
    },
    {
      icon: ClipboardList,
      label: "My AR Credits",
      description: "View your AR credits",
      to: "/ar",
    },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-md">
        <DashboardStatCard
          icon={FileEdit}
          label="Pending requests"
          value={loading ? "…" : String(pendingCount)}
          hint="Awaiting executive review"
          index={0}
        />
      </div>

      <DashboardQuickActions actions={quickActions} index={1} />

      <AppSectionEnter index={2}>
        <DashboardEditRequestsPanel
          isExec={false}
          editRequests={editRequests}
          loading={loading}
          pendingCount={pendingCount}
        />
      </AppSectionEnter>
    </div>
  );
}
