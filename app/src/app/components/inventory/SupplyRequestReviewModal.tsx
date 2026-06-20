import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  approveMaterialRequest,
  rejectMaterialRequest,
  type MaterialAdjustmentRequest,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface SupplyRequestReviewModalProps {
  request: MaterialAdjustmentRequest;
  onClose: () => void;
  onDone: () => void;
}

export function SupplyRequestReviewModal({ request, onClose, onDone }: SupplyRequestReviewModalProps) {
  const [rejectNote, setRejectNote] = useState("");
  const [showReject, setShowReject] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const approve = async () => {
    setBusy(true);
    setError(null);
    try {
      await approveMaterialRequest(request.id);
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not approve request.");
    } finally {
      setBusy(false);
    }
  };

  const deny = async () => {
    if (!rejectNote.trim()) {
      setError("Rejection note is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await rejectMaterialRequest(request.id, rejectNote.trim());
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reject request.");
    } finally {
      setBusy(false);
    }
  };

  const isPending = request.status === "pending";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full">
      <AppModalHeader
        title="Supply issue request"
        subtitle={request.stock_name}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}

        <div className="rounded-lg border bg-secondary/30 px-4 py-3 space-y-2 text-sm">
          <div className="flex justify-between gap-2">
            <span className="text-muted-foreground">Quantity</span>
            <span className="font-medium">{request.amount} {request.unit}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-muted-foreground">Requester</span>
            <span className="font-medium">{request.requester_name ?? "—"}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-muted-foreground">Submitted by</span>
            <span className="font-medium">{request.requested_by_name}</span>
          </div>
          <div className="flex justify-between gap-2">
            <span className="text-muted-foreground">Status</span>
            <span className="font-medium capitalize">{request.status}</span>
          </div>
        </div>

        <div>
          <p className="text-xs font-medium text-muted-foreground mb-1">Reason</p>
          <p className="text-sm leading-relaxed">{request.reason}</p>
        </div>

        {request.rejection_note && (
          <div>
            <p className="text-xs font-medium text-muted-foreground mb-1">Rejection note</p>
            <p className="text-sm text-red-700">{request.rejection_note}</p>
          </div>
        )}

        {isPending && showReject && (
          <div>
            <label className="text-xs font-medium text-muted-foreground">Rejection note (required)</label>
            <textarea
              value={rejectNote}
              onChange={e => setRejectNote(e.target.value)}
              rows={3}
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm resize-none"
              placeholder="Explain why this request is denied"
            />
          </div>
        )}
      </AppModalBody>
      <AppModalFooter className="justify-end gap-2">
        <button type="button" onClick={onClose} className="text-sm px-4 py-2 rounded-lg border border-border">
          Close
        </button>
        {isPending && !showReject && (
          <>
            <button
              type="button"
              disabled={busy}
              onClick={() => setShowReject(true)}
              className="text-sm px-4 py-2 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 disabled:opacity-50"
            >
              Deny
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => void approve()}
              className="text-sm font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-60"
              style={{ background: "#C8900A" }}
            >
              {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Approve"}
            </button>
          </>
        )}
        {isPending && showReject && (
          <button
            type="button"
            disabled={busy}
            onClick={() => void deny()}
            className="text-sm font-semibold px-4 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700 disabled:opacity-60"
          >
            {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Confirm deny"}
          </button>
        )}
      </AppModalFooter>
    </AppModal>
  );
}
