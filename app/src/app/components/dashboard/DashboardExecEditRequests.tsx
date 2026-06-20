import { Link } from "react-router";
import { ChevronRight, FileEdit } from "lucide-react";
import { AppSectionEnter, appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { EditRequestRow, fmtDate } from "@/app/components/dashboard/dashboardTypes";
import { cn } from "@/app/components/ui/utils";

interface DashboardExecEditRequestsProps {
  editRequests: EditRequestRow[];
  pendingCount: number;
  loading: boolean;
}

export function DashboardExecEditRequests({ editRequests, pendingCount, loading }: DashboardExecEditRequestsProps) {
  if (!loading && pendingCount === 0) return null;

  const preview = editRequests.slice(0, 3);

  return (
    <AppSectionEnter index={1}>
      <Link
        to="/inventory/requests#edit-requests-section"
        className={cn(
          "block rounded-2xl border-2 border-[#C8900A]/35 bg-[#C8900A]/8 overflow-hidden",
          "transition-all duration-200 hover:border-[#C8900A]/55 hover:shadow-md hover:shadow-[#C8900A]/10",
        )}
      >
        <div className="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-[#C8900A]/20">
          <div className="flex items-center gap-2 min-w-0">
            <FileEdit size={16} className="text-[#C8900A] shrink-0" />
            <span className="text-sm font-semibold text-foreground">Pending edit requests</span>
            {pendingCount > 0 && (
              <span className="rounded-full bg-[#C8900A] text-white text-[10px] font-bold px-2 py-0.5 tabular-nums">
                {pendingCount}
              </span>
            )}
          </div>
          <span className="text-xs font-medium text-[#C8900A] inline-flex items-center gap-0.5 shrink-0">
            Review <ChevronRight size={14} />
          </span>
        </div>

        {loading ? (
          <p className="px-5 py-4 text-sm text-muted-foreground text-center">Loading approvals…</p>
        ) : (
          <div className="divide-y divide-[#C8900A]/15">
            {preview.map(r => (
              <div key={r.id} className={cn("px-5 py-3", appHoverRow)}>
                <div className="flex items-start justify-between gap-2">
                  <p className="text-sm font-medium text-foreground truncate">{r.requested_by_name ?? "—"}</p>
                  <span className="text-[10px] text-muted-foreground whitespace-nowrap">{fmtDate(r.created_at)}</span>
                </div>
                <p className="text-xs text-muted-foreground mt-0.5 line-clamp-1">
                  {(r.dept_name ?? r.table_name)} · {r.field_name}: {r.old_value ?? "—"} → {r.new_value ?? "—"}
                </p>
              </div>
            ))}
          </div>
        )}
      </Link>
    </AppSectionEnter>
  );
}
