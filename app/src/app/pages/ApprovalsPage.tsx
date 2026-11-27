import { useCallback, useEffect, useId, useMemo, useState } from "react";
import { useSearchParams } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { ApiError } from "@/app/lib/api";
import {
  approvalsApi,
  type OverrideApprovalRequest,
  type OverrideApprovalStatus,
} from "@/app/lib/approvals";
import { cn } from "@/app/components/ui/utils";

function statusLabel(s: OverrideApprovalStatus): string {
  if (s === "pending") return "Pending";
  if (s === "approved") return "Approved";
  if (s === "rejected") return "Rejected";
  return "Failed";
}

function actionTypeLabel(t: string): string {
  if (t === "inventory_check_finalize") return "Check Inventory";
  if (t === "dept_raw_pool_set") return "Leaf pool";
  if (t === "packaging_scan_undo") return "Packaging undo";
  if (t === "pack_session_undo") return "Pack session undo";
  return t;
}

export function ApprovalsPage() {
  const { user } = useAuth();
  const isHr = !!user?.is_hr_manager;
  const [params, setParams] = useSearchParams();
  const focusId = Number(params.get("id") || 0);
  const [status, setStatus] = useState<"pending" | "all">(isHr ? "pending" : "all");
  const [rows, setRows] = useState<OverrideApprovalRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [selected, setSelected] = useState<OverrideApprovalRequest | null>(null);
  const [busy, setBusy] = useState(false);
  const [confirmKind, setConfirmKind] = useState<"approve" | "reject" | null>(null);
  const [rejectNote, setRejectNote] = useState("");
  const confirmTitleId = useId();
  const rejectId = useId();

  const refresh = useCallback(async () => {
    setLoading(true);
    setErr(null);
    try {
      const data = await approvalsApi.list(status);
      setRows(data.rows);
      if (focusId > 0) {
        const hit = data.rows.find(r => r.id === focusId) ?? (await approvalsApi.get(focusId)).request;
        setSelected(hit);
        if (data.is_hr_manager && hit.status === "pending") {
          void approvalsApi.markViewed(hit.id).catch(() => undefined);
        }
      }
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Could not load approvals.");
    } finally {
      setLoading(false);
    }
  }, [focusId, status]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const subtitle = useMemo(
    () =>
      isHr
        ? "Approve or reject override requests (Check Inventory, leaf pool, Packaging undo)."
        : "Your override requests and their status.",
    [isHr],
  );

  const runApprove = async () => {
    if (!selected) return;
    setBusy(true);
    setErr(null);
    try {
      const res = await approvalsApi.approve(selected.id);
      setOk(`Approved #${res.request.id}.`);
      setConfirmKind(null);
      setSelected(res.request);
      await refresh();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Approve failed.");
      setConfirmKind(null);
      await refresh();
    } finally {
      setBusy(false);
    }
  };

  const runReject = async () => {
    if (!selected) return;
    if (rejectNote.trim() === "") {
      setErr("A rejection note is required.");
      return;
    }
    setBusy(true);
    setErr(null);
    try {
      const res = await approvalsApi.reject(selected.id, rejectNote.trim());
      setOk(`Rejected #${res.request.id}.`);
      setConfirmKind(null);
      setRejectNote("");
      setSelected(res.request);
      await refresh();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Reject failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <PageLayout title="Approvals" subtitle={subtitle}>
      <AppSectionEnter className="space-y-4">
        <AppSuccessNotice message={ok} />
        <AppErrorBanner message={err} />
        <div className="flex flex-wrap items-center gap-2">
          {isHr ? (
            <>
              <Button
                type="button"
                variant={status === "pending" ? "default" : "outline"}
                className="min-h-10"
                onClick={() => setStatus("pending")}
              >
                Pending
              </Button>
              <Button
                type="button"
                variant={status === "all" ? "default" : "outline"}
                className="min-h-10"
                onClick={() => setStatus("all")}
              >
                All
              </Button>
            </>
          ) : null}
          <Button type="button" variant="outline" className="min-h-10" onClick={() => void refresh()}>
            Refresh
          </Button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div className="rounded-xl border border-border bg-card overflow-hidden">
            {loading ? (
              <div className="p-8 text-center text-muted-foreground">
                <Loader2 className="inline animate-spin" size={18} />
              </div>
            ) : rows.length === 0 ? (
              <p className="p-6 text-sm text-muted-foreground">No requests.</p>
            ) : (
              <ul className="divide-y divide-border">
                {rows.map(row => (
                  <li key={row.id}>
                    <button
                      type="button"
                      className={cn(
                        "w-full text-left px-4 py-3",
                        appHoverRow,
                        selected?.id === row.id && "bg-secondary/40",
                      )}
                      onClick={() => {
                        setSelected(row);
                        setParams(row.id ? { id: String(row.id) } : {});
                        if (isHr && row.status === "pending") {
                          void approvalsApi.markViewed(row.id).catch(() => undefined);
                        }
                      }}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <p className="text-sm font-medium text-foreground line-clamp-2">{row.summary}</p>
                        <span className="text-[10px] uppercase tracking-wide text-muted-foreground shrink-0">
                          {statusLabel(row.status)}
                        </span>
                      </div>
                      <p className="mt-1 text-[11px] text-muted-foreground">
                        {actionTypeLabel(row.action_type)} · {row.requested_by_name} ·{" "}
                        {new Date(row.created_at).toLocaleString()}
                      </p>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="rounded-xl border border-border bg-card p-4 space-y-3 min-h-[200px]">
            {!selected ? (
              <p className="text-sm text-muted-foreground">Select a request.</p>
            ) : (
              <>
                <div>
                  <p className="text-xs uppercase tracking-wide text-muted-foreground">
                    #{selected.id} · {actionTypeLabel(selected.action_type)}
                  </p>
                  <h2 className="text-base font-semibold text-foreground mt-1">{selected.summary}</h2>
                  <p className="text-xs text-muted-foreground mt-1">
                    Status: <span className="font-medium text-foreground">{statusLabel(selected.status)}</span>
                  </p>
                </div>
                <dl className="text-xs space-y-1.5">
                  <div className="flex justify-between gap-2">
                    <dt className="text-muted-foreground">Requested by</dt>
                    <dd className="font-medium">{selected.requested_by_name}</dd>
                  </div>
                  <div className="flex justify-between gap-2">
                    <dt className="text-muted-foreground">When</dt>
                    <dd className="font-medium">{new Date(selected.created_at).toLocaleString()}</dd>
                  </div>
                  {selected.reviewed_at ? (
                    <div className="flex justify-between gap-2">
                      <dt className="text-muted-foreground">Reviewed</dt>
                      <dd className="font-medium">
                        {selected.reviewed_by_name} · {new Date(selected.reviewed_at).toLocaleString()}
                      </dd>
                    </div>
                  ) : null}
                  {selected.rejection_note ? (
                    <div>
                      <dt className="text-muted-foreground">Rejection note</dt>
                      <dd className="font-medium mt-0.5">{selected.rejection_note}</dd>
                    </div>
                  ) : null}
                  {selected.error_message ? (
                    <div>
                      <dt className="text-muted-foreground">Error</dt>
                      <dd className="font-medium mt-0.5 text-red-700">{selected.error_message}</dd>
                    </div>
                  ) : null}
                </dl>
                {isHr && selected.status === "pending" ? (
                  <div className="flex flex-wrap gap-2 pt-2">
                    <Button
                      type="button"
                      className="min-h-11 bg-[#C8900A] hover:bg-[#b07f09] text-white"
                      onClick={() => setConfirmKind("approve")}
                    >
                      Approve
                    </Button>
                    <Button type="button" variant="outline" className="min-h-11" onClick={() => setConfirmKind("reject")}>
                      Reject
                    </Button>
                  </div>
                ) : null}
              </>
            )}
          </div>
        </div>
      </AppSectionEnter>

      {confirmKind === "approve" && selected ? (
        <AppModal open onClose={() => !busy && setConfirmKind(null)} panelClassName="max-w-md w-full" labelledBy={confirmTitleId}>
          <AppModalHeader
            title="Approve this request?"
            titleId={confirmTitleId}
            subtitle="The change will be applied now after re-checking that it is still valid."
            onClose={() => !busy && setConfirmKind(null)}
          />
          <AppModalBody>
            <p className="text-sm">{selected.summary}</p>
          </AppModalBody>
          <AppModalFooter>
            <Button type="button" variant="outline" disabled={busy} onClick={() => setConfirmKind(null)} className="min-h-11 flex-1">
              Cancel
            </Button>
            <Button type="button" disabled={busy} onClick={() => void runApprove()} className="min-h-11 flex-1">
              {busy ? <Loader2 className="animate-spin" size={14} /> : "Yes, approve"}
            </Button>
          </AppModalFooter>
        </AppModal>
      ) : null}

      {confirmKind === "reject" && selected ? (
        <AppModal open onClose={() => !busy && setConfirmKind(null)} panelClassName="max-w-md w-full" labelledBy={confirmTitleId}>
          <AppModalHeader
            title="Reject this request?"
            titleId={confirmTitleId}
            subtitle="Nothing will change in inventory. A note is required."
            onClose={() => !busy && setConfirmKind(null)}
          />
          <AppModalBody className="space-y-3">
            <p className="text-sm">{selected.summary}</p>
            <div>
              <label htmlFor={rejectId} className="text-xs text-muted-foreground block mb-1">
                Rejection note
              </label>
              <textarea
                id={rejectId}
                value={rejectNote}
                onChange={e => setRejectNote(e.target.value)}
                rows={3}
                className="w-full rounded-lg border border-border px-3 py-2 text-sm"
              />
            </div>
          </AppModalBody>
          <AppModalFooter>
            <Button type="button" variant="outline" disabled={busy} onClick={() => setConfirmKind(null)} className="min-h-11 flex-1">
              Cancel
            </Button>
            <Button type="button" disabled={busy} onClick={() => void runReject()} className="min-h-11 flex-1">
              {busy ? <Loader2 className="animate-spin" size={14} /> : "Reject"}
            </Button>
          </AppModalFooter>
        </AppModal>
      ) : null}
    </PageLayout>
  );
}
