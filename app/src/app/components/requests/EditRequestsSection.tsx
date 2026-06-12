import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { CheckCircle2, Loader2, XCircle } from "lucide-react";
import { RequestsHubSection } from "@/app/components/requests/RequestsHubSection";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { ApiError } from "@/app/lib/api";
import {
  approveEditRequest,
  formatEditRequestCategory,
  formatEditRequestChange,
  listEditRequests,
  rejectEditRequest,
  type EditRequest,
} from "@/app/lib/editRequestsApi";

const ROWS_PER_PAGE = 12;

function TablePagination({
  page,
  setPage,
  totalRows,
  noun,
}: {
  page: number;
  setPage: (fn: (p: number) => number) => void;
  totalRows: number;
  noun: string;
}) {
  if (totalRows <= 0) return null;
  const totalPages = Math.max(1, Math.ceil(totalRows / ROWS_PER_PAGE));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * ROWS_PER_PAGE;

  return (
    <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 pt-3">
      <p className="text-xs text-muted-foreground">
        Showing {start + 1}–{Math.min(start + ROWS_PER_PAGE, totalRows)} of {totalRows} {noun}
      </p>
      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={() => setPage((p) => Math.max(1, p - 1))}
          disabled={safePage <= 1}
          className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <span className="text-xs text-muted-foreground">
          Page {safePage} of {totalPages}
        </span>
        <button
          type="button"
          onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
          disabled={safePage >= totalPages}
          className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  );
}

interface EditRequestsSectionProps {
  isReviewer: boolean;
  readonly?: boolean;
  onChanged?: () => void;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function EditRequestsSection({
  isReviewer,
  readonly = false,
  onChanged,
  open: controlledOpen,
  onOpenChange,
}: EditRequestsSectionProps) {
  const [statusFilter, setStatusFilter] = useState<"pending" | "approved" | "rejected" | "all">("pending");
  const [requests, setRequests] = useState<EditRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actingOn, setActingOn] = useState<number | null>(null);
  const [page, setPage] = useState(1);
  const [internalOpen, setInternalOpen] = useState(false);
  const autoOpenRef = useRef(false);

  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await listEditRequests(isReviewer, isReviewer ? statusFilter : undefined);
      setRequests(payload.requests);
      setPage(1);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load edit requests.");
    } finally {
      setLoading(false);
    }
  }, [isReviewer, statusFilter]);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const pendingCount = useMemo(() => requests.filter((r) => r.status === "pending").length, [requests]);

  useEffect(() => {
    if (loading || autoOpenRef.current || controlledOpen != null) return;
    if (pendingCount > 0) {
      autoOpenRef.current = true;
      setOpen(true);
    }
  }, [loading, pendingCount, controlledOpen, setOpen]);

  const totalPages = Math.max(1, Math.ceil(requests.length / ROWS_PER_PAGE));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * ROWS_PER_PAGE;
  const paged = requests.slice(start, start + ROWS_PER_PAGE);

  const colSpan = 7;

  const approve = async (id: number) => {
    if (!confirm("Approve and apply this change?")) return;
    setActingOn(id);
    setError(null);
    try {
      await approveEditRequest(id);
      await refresh();
      onChanged?.();
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
    setError(null);
    try {
      await rejectEditRequest(id, note);
      await refresh();
      onChanged?.();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reject.");
    } finally {
      setActingOn(null);
    }
  };

  return (
    <RequestsHubSection
      id="edit-requests-section"
      title="Edit requests"
      subtitle={
        isReviewer
          ? "Deliveries review fixes, inventory row edits, and other staff-submitted changes"
          : "Track delivery delete/edit and other requests awaiting executive approval"
      }
      pendingCount={statusFilter === "pending" ? pendingCount : 0}
      totalCount={requests.length}
      open={open}
      onOpenChange={setOpen}
      headerActions={
        isReviewer ? (
          <div className="flex flex-wrap items-center gap-2">
            {(["pending", "approved", "rejected", "all"] as const).map((s) => (
              <button
                key={s}
                type="button"
                onClick={() => setStatusFilter(s)}
                className={`text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors ${
                  statusFilter === s
                    ? "border-[#C8900A] text-[#C8900A] bg-[#fff7e8]"
                    : "border-border text-muted-foreground hover:text-foreground"
                }`}
              >
                {s.toUpperCase()}
                {s === "pending" && pendingCount > 0 ? ` (${pendingCount})` : ""}
              </button>
            ))}
          </div>
        ) : undefined
      }
    >
      {error && (
        <div className="mb-3 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700">{error}</div>
      )}

      <div className="overflow-x-auto rounded-lg border border-border">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              {["Date", "Category", "Target", "Change", "Requester", "Reason", isReviewer && !readonly ? "Review" : "Status"].map((h) => (
                  <th key={h} className="text-left px-4 py-3">
                    {h}
                  </th>
                ))}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={colSpan} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {!loading && paged.length === 0 && (
              <tr>
                <td colSpan={colSpan} className="py-8 text-center text-sm text-muted-foreground">
                  No edit requests{isReviewer && statusFilter !== "all" ? ` (${statusFilter})` : ""}.
                </td>
              </tr>
            )}
            {!loading &&
              paged.map((r) => (
                <tr
                  key={r.id}
                  className={`border-b align-top ${
                    r.status === "pending"
                      ? "border-l-4 border-l-amber-400"
                      : r.status === "approved"
                        ? "border-l-4 border-l-green-500"
                        : "border-l-4 border-l-red-400"
                  }`}
                >
                  <td className="px-4 py-3 text-xs whitespace-nowrap">{r.created_at}</td>
                  <td className="px-4 py-3 text-xs font-medium">{formatEditRequestCategory(r.table_name)}</td>
                  <td className="px-4 py-3 text-xs text-muted-foreground">
                    {r.table_name === "delivery_batch_items" ? `#${r.record_id}` : `${r.table_name} #${r.record_id}`}
                  </td>
                  <td className="px-4 py-3 text-xs max-w-[200px]">{formatEditRequestChange(r)}</td>
                  <td className="px-4 py-3">
                    <p className="text-xs font-medium">{r.requested_by_name}</p>
                    {r.dept_name && <p className="text-[11px] text-muted-foreground">{r.dept_name}</p>}
                  </td>
                  <td className="px-4 py-3 text-xs max-w-xs truncate italic">"{r.reason}"</td>
                  <td className="px-4 py-3">
                    {isReviewer && !readonly && r.status === "pending" ? (
                      <div className="flex flex-wrap gap-1">
                        <button
                          type="button"
                          disabled={actingOn === r.id}
                          onClick={() => void approve(r.id)}
                          className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 disabled:opacity-50"
                        >
                          <CheckCircle2 size={12} /> Approve
                        </button>
                        <button
                          type="button"
                          disabled={actingOn === r.id}
                          onClick={() => void reject(r.id)}
                          className="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 disabled:opacity-50"
                        >
                          <XCircle size={12} /> Reject
                        </button>
                      </div>
                    ) : (
                      <div className="text-xs text-muted-foreground capitalize">
                        {r.status}
                        {r.reviewer_name ? ` · ${r.reviewer_name}` : ""}
                        {r.status === "rejected" && r.rejection_note ? (
                          <p className="text-[11px] text-red-600 mt-0.5">{r.rejection_note}</p>
                        ) : null}
                      </div>
                    )}
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
      {!loading && (
        <TablePagination page={page} setPage={setPage} totalRows={requests.length} noun="requests" />
      )}
    </RequestsHubSection>
  );
}
