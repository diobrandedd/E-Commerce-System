import { FileEdit } from "lucide-react";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { EditRequestRow, fmtDate } from "@/app/components/dashboard/dashboardTypes";
import { cn } from "@/app/components/ui/utils";

interface DashboardEditRequestsPanelProps {
  isExec: boolean;
  editRequests: EditRequestRow[];
  loading: boolean;
  pendingCount: number;
}

export function DashboardEditRequestsPanel({
  isExec,
  editRequests,
  loading,
  pendingCount,
}: DashboardEditRequestsPanelProps) {
  return (
    <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
      <div className="flex items-center justify-between px-5 py-4 border-b border-border">
        <div className="flex items-center gap-2">
          <h3 className="text-sm font-semibold text-foreground">
            {isExec ? "Pending edit requests" : "Your edit requests"}
          </h3>
          {pendingCount > 0 && (
            <span className="w-5 h-5 rounded-full bg-[#C8900A] text-white text-[10px] font-bold flex items-center justify-center">
              {pendingCount}
            </span>
          )}
        </div>
        <FileEdit size={15} className="text-muted-foreground" />
      </div>
      <div className="divide-y divide-border">
        {editRequests.map(r => (
          <div key={r.id} className={cn("px-5 py-4", appHoverRow)}>
            <div className="flex items-start justify-between gap-2 mb-1">
              <p className="text-sm font-medium text-foreground">
                {isExec ? (r.requested_by_name ?? "—") : `#${r.id}`}
              </p>
              <span className="text-[10px] text-muted-foreground whitespace-nowrap">{fmtDate(r.created_at)}</span>
            </div>
            <p className="text-xs text-muted-foreground mb-2">
              {(r.dept_name ?? r.table_name)} · {r.field_name}:{" "}
              <span className="line-through">{r.old_value ?? "—"}</span> →{" "}
              <span className="text-[#C8900A] font-semibold">{r.new_value ?? "—"}</span>
            </p>
            <p className="text-xs text-muted-foreground italic">"{r.reason}"</p>
            {!isExec && r.status === "rejected" && r.rejection_note && (
              <p className="text-xs text-red-600 mt-2">
                <strong>Rejected:</strong> {r.rejection_note}
              </p>
            )}
          </div>
        ))}
        {!loading && editRequests.length === 0 && (
          <p className="px-5 py-6 text-center text-sm text-muted-foreground">No edit requests yet.</p>
        )}
      </div>
    </div>
  );
}
