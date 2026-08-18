import { useEffect, useState } from "react";
import { ClipboardEdit, Layers, Loader2, Package, RefreshCcw, Users } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { DashboardInventorySection } from "@/app/components/dashboard/DashboardInventorySection";
import { DashboardStatCard } from "@/app/components/dashboard/DashboardStatCard";
import { DashboardCsrSection } from "@/app/components/dashboard/DashboardCsrSection";
import { DashboardStaffSection } from "@/app/components/dashboard/DashboardStaffSection";
import { DashboardEditRequestsPanel } from "@/app/components/dashboard/DashboardEditRequestsPanel";
import { DashboardExecView } from "@/app/components/dashboard/DashboardExecView";
import { AppErrorBanner, AppSectionEnter, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, ApiError } from "@/app/lib/api";
import type { DashboardData } from "@/app/components/dashboard/dashboardTypes";

export function DashboardPage() {
  const { user, can } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<DashboardData>("dashboard.php", "index");
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load dashboard.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    refresh();
  }, []);

  const isExec = can("reports.view");

  if (isExec) {
    return (
      <DashboardExecView
        data={data}
        loading={loading}
        error={error}
        userName={user?.name ?? ""}
        onRefresh={refresh}
      />
    );
  }

  const isCrossDept = can("inventory.entries.view");
  const showPackagingInventory = can("packaging.view") && isCrossDept;
  const view = data?.view ?? "inventory";

  return (
    <PageLayout
      title="Dashboard"
      subtitle={`Welcome back, ${user?.name ?? ""}`}
      notificationCount={data?.pending_edit_requests_count ?? 0}
      actions={
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={refresh}
          disabled={loading}
          className={appHeaderActionClass}
        >
          {loading ? <Loader2 size={13} className="animate-spin" /> : <RefreshCcw size={13} />}
          Refresh
        </Button>
      }
    >
      <div className="space-y-6">
        <AppErrorBanner message={error} />

        {view === "csr" && data?.csr && (
          <DashboardCsrSection
            csr={data.csr}
            loading={loading}
            isTl={can("csr.parcels.edit_items")}
          />
        )}

        {view === "staff" && (
          <DashboardStaffSection
            pendingCount={data?.pending_edit_requests_count ?? 0}
            editRequests={data?.pending_edit_requests ?? []}
            loading={loading}
          />
        )}

        {view === "inventory" && (
          <>
            {showPackagingInventory && (
              <AppSectionEnter index={0}>
                <div
                  className="bg-card rounded-xl border border-border p-5 sm:p-6"
                  style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
                >
                  <DashboardInventorySection />
                </div>
              </AppSectionEnter>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
              <DashboardStatCard
                icon={Package}
                label="Stocks"
                value={loading ? "…" : String(data?.stats.stocks ?? 0)}
                hint={isCrossDept ? "Across all departments" : "In your department"}
                index={1}
              />
              <DashboardStatCard
                icon={Layers}
                label="Products"
                value={loading ? "…" : String(data?.stats.products ?? 0)}
                hint={isCrossDept ? "Across all departments" : "In your department"}
                index={2}
              />
              <DashboardStatCard
                icon={Users}
                label="Active staff"
                value={loading ? "…" : String(data?.stats.active_users ?? 0)}
                hint="Login enabled"
                index={3}
              />
              <DashboardStatCard
                icon={ClipboardEdit}
                label="Entries (30d)"
                value={loading ? "…" : String(data?.stats.stock_entries_30d ?? 0)}
                hint="Stock entries in last 30 days"
                index={4}
              />
            </div>

            <AppSectionEnter index={3}>
              <DashboardEditRequestsPanel
                isExec={false}
                editRequests={data?.pending_edit_requests ?? []}
                loading={loading}
                pendingCount={data?.pending_edit_requests_count ?? 0}
              />
            </AppSectionEnter>
          </>
        )}
      </div>
    </PageLayout>
  );
}
