import { useCallback, useEffect, useMemo, useState } from "react";
import { CheckCircle2, XCircle, AlertCircle, Loader2, RefreshCcw, FileEdit } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";

interface EditRequest {
  id: number;
  table_name: string;
  record_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  reason: string;
  status: "pending" | "approved" | "rejected";
  rejection_note: string | null;
  created_at: string;
  reviewed_at: string | null;
  requested_by_name: string;
  dept_name: string | null;
  reviewer_name: string | null;
}

interface ListPayload {
  requests: EditRequest[];
  is_reviewer: boolean;
}

const STATUS_COLORS: Record<string, string> = {
  pending: "bg-amber-100 text-amber-700",
  approved: "bg-green-100 text-green-700",
  rejected: "bg-red-100 text-red-600",
};

const fmtDate = (s: string | null) => {
  if (!s) return "—";
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

export function EditRequestsPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const [statusFilter, setStatusFilter] = useState<"pending" | "approved" | "rejected" | "all">("pending");
  const [requests, setRequests] = useState<EditRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [actingOn, setActingOn] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<ListPayload>(
        "edit_requests.php",
        "list",
        isExec ? { status: statusFilter } : undefined
      );
      setRequests(payload.requests);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load requests.");
    } finally {
      setLoading(false);
    }
  }, [isExec, statusFilter]);

  useEffect(() => { refresh(); }, [refresh]);

  const flash = (msg: string) => {
    setNotice(msg);
    setTimeout(() => setNotice(null), 3000);
  };

  const approve = async (id: number) => {
    if (!confirm("Approve and apply this change?")) return;
    setActingOn(id);
    try {
      await apiPost("edit_requests.php", "approve", { id });
      flash("Request approved.");
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not approve.");
    } finally {
      setActingOn(null);
    }
  };

  const reject = async (id: number) => {
    const note = prompt("Reason for rejection (required):") ?? "";
    if (!note.trim()) return;
    setActingOn(id);
    try {
      await apiPost("edit_requests.php", "reject", { id, rejection_note: note });
      flash("Request rejected.");
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reject.");
    } finally {
      setActingOn(null);
    }
  };

  const totals = useMemo(() => ({
    pending: requests.filter(r => r.status === "pending").length,
    approved: requests.filter(r => r.status === "approved").length,
    rejected: requests.filter(r => r.status === "rejected").length,
  }), [requests]);

  return (
    <PageLayout
      title="Edit Requests"
      subtitle={isExec ? "Review and apply staff-submitted edits" : "Track the status of your edit requests"}
      notificationCount={isExec ? totals.pending : 0}
      actions={
        <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
          <RefreshCcw size={13} /> Refresh
        </button>
      }
    >
      <div className="space-y-5">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        {notice && (
          <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{notice}</div>
        )}

        {isExec && (
          <div className="flex items-center gap-2">
            {(["pending", "approved", "rejected", "all"] as const).map(s => (
              <button
                key={s}
                onClick={() => setStatusFilter(s)}
                className={`text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors ${
                  statusFilter === s
                    ? "border-[#C8900A] text-[#C8900A] bg-[#fff7e8]"
                    : "border-border text-muted-foreground hover:text-foreground"
                }`}
              >
                {s.toUpperCase()}
              </button>
            ))}
          </div>
        )}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center gap-2 px-6 py-4 border-b border-border">
            <FileEdit size={15} className="text-muted-foreground" />
            <h3 className="text-sm font-semibold text-foreground">{requests.length} request{requests.length === 1 ? "" : "s"}</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["#", "Requester", "Table", "Field", "Old → New", "Reason", "Status", "When", "Actions"].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={9} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && requests.length === 0 && (
                  <tr><td colSpan={9} className="px-6 py-8 text-center text-sm text-muted-foreground">No requests.</td></tr>
                )}
                {requests.map(r => (
                  <tr key={r.id} className="border-b border-border last:border-0 hover:bg-secondary/30 transition-colors align-top">
                    <td className="px-6 py-3 font-mono text-xs text-muted-foreground">#{r.id}</td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-foreground">{r.requested_by_name}</p>
                      {r.dept_name && <p className="text-xs text-muted-foreground">{r.dept_name}</p>}
                    </td>
                    <td className="px-4 py-3 text-xs text-muted-foreground">
                      <p>{r.table_name}</p>
                      <p>#{r.record_id}</p>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{r.field_name}</td>
                    <td className="px-4 py-3 text-xs">
                      <span className="line-through text-muted-foreground">{r.old_value ?? "—"}</span>
                      {" → "}
                      <span className="text-[#C8900A] font-semibold">{r.new_value ?? "—"}</span>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground italic max-w-xs truncate">“{r.reason}”</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full ${STATUS_COLORS[r.status] ?? "bg-gray-100"}`}>
                        {r.status}
                      </span>
                      {r.status === "rejected" && r.rejection_note && (
                        <p className="text-[11px] text-red-600 mt-1 max-w-[200px]"><strong>Note:</strong> {r.rejection_note}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.created_at)}</td>
                    <td className="px-4 py-3">
                      {isExec && r.status === "pending" && (
                        <div className="flex items-center gap-1">
                          <button
                            disabled={actingOn === r.id}
                            onClick={() => approve(r.id)}
                            className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 disabled:opacity-50"
                          >
                            <CheckCircle2 size={12} /> Approve
                          </button>
                          <button
                            disabled={actingOn === r.id}
                            onClick={() => reject(r.id)}
                            className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 disabled:opacity-50"
                          >
                            <XCircle size={12} /> Reject
                          </button>
                        </div>
                      )}
                      {(!isExec || r.status !== "pending") && r.reviewer_name && (
                        <p className="text-xs text-muted-foreground">By {r.reviewer_name}</p>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
