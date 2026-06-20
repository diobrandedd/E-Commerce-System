import { Loader2, RefreshCcw } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { DashboardShippedSection } from "@/app/components/dashboard/DashboardShippedSection";
import { DashboardExecEditRequests } from "@/app/components/dashboard/DashboardExecEditRequests";
import { DashboardInventoryHeadline } from "@/app/components/dashboard/DashboardInventoryHeadline";
import { DashboardFinancialAlerts } from "@/app/components/dashboard/DashboardFinancialAlerts";
import { AppErrorBanner, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import type { DashboardData } from "@/app/components/dashboard/dashboardTypes";

interface DashboardExecViewProps {
  data: DashboardData | null;
  loading: boolean;
  error: string | null;
  userName: string;
  onRefresh: () => void;
}

export function DashboardExecView({ data, loading, error, userName, onRefresh }: DashboardExecViewProps) {
  return (
    <PageLayout
      title="Dashboard"
      subtitle={`Welcome back, ${userName}`}
      notificationCount={data?.pending_edit_requests_count ?? 0}
      contentWidth="default"
      actions={
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={onRefresh}
          disabled={loading}
          className={appHeaderActionClass}
        >
          {loading ? <Loader2 size={13} className="animate-spin" /> : <RefreshCcw size={13} />}
          Refresh
        </Button>
      }
    >
      <div className="mx-auto w-full max-w-4xl space-y-7 sm:space-y-8">
        <AppErrorBanner message={error} />

        <DashboardShippedSection />

        <DashboardExecEditRequests
          editRequests={data?.pending_edit_requests ?? []}
          pendingCount={data?.pending_edit_requests_count ?? 0}
          loading={loading}
        />

        <DashboardInventoryHeadline />

        <DashboardFinancialAlerts
          stockChanges={data?.recent_stock_changes ?? []}
          costChanges={data?.recent_cost_changes ?? []}
          loading={loading}
        />
      </div>
    </PageLayout>
  );
}
