import { AnimatePresence } from "motion/react";
import { Link } from "react-router";
import { PackageCheck, RotateCcw, Truck } from "lucide-react";
import { AppSectionEnter, appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { DashboardStatCard } from "@/app/components/dashboard/DashboardStatCard";
import { DashboardQuickActions } from "@/app/components/dashboard/DashboardQuickActions";
import { CsrMetrics, fmtDate } from "@/app/components/dashboard/dashboardTypes";
import { cn } from "@/app/components/ui/utils";

const STATUS_LABELS: Record<string, string> = {
  packing: "For packing",
  shipped: "Shipped",
  on_hold: "On hold",
  cancelled: "Cancelled",
  returned: "Returned",
};

const STATUS_COLORS: Record<string, string> = {
  packing: "bg-amber-100 text-amber-800",
  shipped: "bg-green-100 text-green-800",
  on_hold: "bg-orange-100 text-orange-800",
  cancelled: "bg-red-100 text-red-800",
  returned: "bg-purple-100 text-purple-800",
};

interface DashboardCsrSectionProps {
  csr: CsrMetrics;
  loading: boolean;
  isTl: boolean;
}

export function DashboardCsrSection({ csr, loading, isTl }: DashboardCsrSectionProps) {
  const quickActions = [
    {
      icon: PackageCheck,
      label: "Online Orders",
      description: "Prep and stamp batches",
      to: "/waybill/online-orders",
    },
    {
      icon: Truck,
      label: "Online parcels",
      description: "Track packing and shipping",
      to: "/fulfillment/online-parcels",
    },
    {
      icon: PackageCheck,
      label: "For packing",
      description: "Staging materials queue",
      to: "/fulfillment/packing-materials",
    },
    {
      icon: RotateCcw,
      label: isTl ? "RTS overview" : "Record RTS",
      description: isTl ? "Pool, records, dashboard" : "Log a return case",
      to: isTl ? "/rts" : "/rts/record",
    },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <DashboardStatCard
          icon={PackageCheck}
          label="For packing"
          value={loading ? "…" : String(csr.parcels_packing)}
          hint="Parcels awaiting ship"
          index={0}
        />
        <DashboardStatCard
          icon={Truck}
          label="Shipped today"
          value={loading ? "…" : String(csr.parcels_shipped_today)}
          hint="Marked shipped today"
          index={1}
        />
        <DashboardStatCard
          icon={RotateCcw}
          label="RTS pool"
          value={loading ? "…" : String(csr.rts_pool_count)}
          hint="Units in return pool"
          index={2}
        />
        <DashboardStatCard
          icon={PackageCheck}
          label="On hold"
          value={loading ? "…" : String(csr.parcels_by_status.on_hold ?? 0)}
          hint="Parcels paused"
          index={3}
        />
      </div>

      <DashboardQuickActions actions={quickActions} index={1} />

      <AppSectionEnter index={2}>
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Recent online parcels</h3>
            <Link
              to="/fulfillment/online-parcels"
              className="text-xs font-medium text-[#C8900A] hover:underline"
            >
              View all
            </Link>
          </div>
          <div className="divide-y divide-border">
            <AnimatePresence initial={false}>
              {csr.recent_parcels.map(r => (
                <div
                  key={r.id}
                  className={cn("px-6 py-4", appHoverRow)}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="text-sm font-medium text-foreground truncate">
                        {r.recipient_name || r.tracking_number}
                      </p>
                      <p className="text-xs text-muted-foreground mt-0.5 truncate">
                        {r.tracking_number}
                        {r.courier ? ` · ${r.courier}` : ""}
                      </p>
                    </div>
                    <span
                      className={cn(
                        "text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full flex-shrink-0",
                        STATUS_COLORS[r.status] ?? "bg-secondary text-muted-foreground",
                      )}
                    >
                      {STATUS_LABELS[r.status] ?? r.status}
                    </span>
                  </div>
                  <p className="text-[11px] text-muted-foreground mt-1.5">
                    {r.ship_date ? `Shipped ${fmtDate(r.ship_date)}` : `Created ${fmtDate(r.created_at)}`}
                  </p>
                </div>
              ))}
            </AnimatePresence>
            {!loading && csr.recent_parcels.length === 0 && (
              <p className="px-6 py-8 text-center text-sm text-muted-foreground">No online parcels yet.</p>
            )}
          </div>
        </div>
      </AppSectionEnter>
    </div>
  );
}
