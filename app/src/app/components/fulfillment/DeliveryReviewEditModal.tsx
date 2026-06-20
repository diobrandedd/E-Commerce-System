import { useEffect, useState } from "react";
import { Loader2, Pencil, Trash2, X } from "lucide-react";
import { FulfillmentModal } from "@/app/components/csr/onlineFulfillmentMotion";
import {
  deliveriesRequestDelete,
  deliveriesRequestEditContent,
  deliveriesDeleteItem,
  deliveriesResolveItem,
  type DeliveryReviewRow,
} from "@/app/lib/deliveriesApi";
import { ApiError } from "@/app/lib/api";

type EditAction = "edit_content" | "delete_row" | null;

export function DeliveryReviewEditModal({
  open,
  row,
  isExecutive,
  onClose,
  onDone,
}: {
  open: boolean;
  row: DeliveryReviewRow | null;
  isExecutive: boolean;
  onClose: () => void;
  onDone: (message: string) => void;
}) {
  const [action, setAction] = useState<EditAction>(null);
  const [tracking, setTracking] = useState("");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!open || !row) return;
    setAction(null);
    setTracking(row.tracking_number ?? "");
    setReason("");
    setError(null);
    setBusy(false);
  }, [open, row]);

  if (!row) return null;

  const submit = async () => {
    if (!action) return;
    setBusy(true);
    setError(null);
    try {
      if (action === "edit_content") {
        const tn = tracking.trim();
        if (!tn) {
          setError("Tracking number is required.");
          setBusy(false);
          return;
        }
        if (isExecutive) {
          await deliveriesResolveItem(row.item_id, tn);
          onDone("Record updated.");
        } else {
          if (!reason.trim()) {
            setError("Reason is required.");
            setBusy(false);
            return;
          }
          const res = await deliveriesRequestEditContent(row.item_id, tn, reason.trim());
          onDone(`Edit request #${res.id} sent for executive approval.`);
        }
      } else {
        if (isExecutive) {
          if (!window.confirm(`Delete delivery record #${row.item_id}?`)) {
            setBusy(false);
            return;
          }
          await deliveriesDeleteItem(row.item_id);
          onDone("Record deleted.");
        } else {
          if (!reason.trim()) {
            setError("Reason is required.");
            setBusy(false);
            return;
          }
          const res = await deliveriesRequestDelete(row.item_id, reason.trim());
          onDone(`Delete request #${res.id} sent for executive approval.`);
        }
      }
      onClose();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <FulfillmentModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-md w-full"
      labelledBy="delivery-edit-title"
    >
      <div className="flex flex-col max-h-[85vh]">
        <div className="flex items-start justify-between gap-3 px-5 pt-5 pb-4 border-b border-border">
          <div className="min-w-0">
            <h2 id="delivery-edit-title" className="text-lg font-semibold text-foreground">
              Edit delivery record
            </h2>
            <p className="text-xs text-muted-foreground mt-1 break-all">
              Item #{row.item_id} · {row.delivery_date}
              {row.tracking_number ? ` · ${row.tracking_number}` : ""}
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="flex-shrink-0 rounded-lg p-1.5 text-muted-foreground hover:bg-muted/60 hover:text-foreground"
          >
            <X size={18} />
          </button>
        </div>

        <div className="overflow-y-auto px-5 py-4 space-y-4">
          {row.photo_url ? (
            <button
              type="button"
              className="block w-full rounded-lg overflow-hidden border border-border"
              onClick={() => window.open(row.photo_url, "_blank", "noopener,noreferrer")}
            >
              <img
                src={row.photo_url}
                alt={`Delivery photo ${row.tracking_number ?? row.item_id}`}
                className="w-full max-h-40 object-cover"
              />
            </button>
          ) : null}
          {!action ? (
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground">
                {isExecutive ? "Choose what to change:" : "Choose what to request:"}
              </p>
              <button
                type="button"
                className="w-full flex items-center gap-3 rounded-xl border border-border bg-background px-4 py-3 text-left hover:bg-muted/40 transition-colors"
                onClick={() => setAction("edit_content")}
              >
                <Pencil className="h-5 w-5 text-[#C8900A]" />
                <div>
                  <p className="font-semibold text-sm">Edit content</p>
                  <p className="text-xs text-muted-foreground">Fix tracking or other recorded details</p>
                </div>
              </button>
              <button
                type="button"
                className="w-full flex items-center gap-3 rounded-xl border border-destructive/30 bg-background px-4 py-3 text-left hover:bg-destructive/5 transition-colors"
                onClick={() => setAction("delete_row")}
              >
                <Trash2 className="h-5 w-5 text-destructive" />
                <div>
                  <p className="font-semibold text-sm text-destructive">Delete row</p>
                  <p className="text-xs text-muted-foreground">Remove this delivery photo record</p>
                </div>
              </button>
            </div>
          ) : (
            <div className="space-y-3">
              <button
                type="button"
                className="text-xs text-muted-foreground hover:text-foreground"
                onClick={() => setAction(null)}
              >
                ← Back
              </button>
              {action === "edit_content" ? (
                <label className="block text-sm">
                  Tracking number
                  <input
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 font-mono"
                    value={tracking}
                    onChange={(e) => setTracking(e.target.value)}
                    disabled={busy}
                  />
                </label>
              ) : (
                <p className="text-sm text-muted-foreground">
                  {isExecutive
                    ? "This permanently deletes the delivery row and its photo file."
                    : "Executives must approve before this row is permanently removed."}
                </p>
              )}
              {!isExecutive && (
                <label className="block text-sm">
                  Reason (required)
                  <textarea
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 min-h-[72px]"
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    disabled={busy}
                    placeholder="Why is this change needed?"
                  />
                </label>
              )}
              {error && <p className="text-sm text-destructive">{error}</p>}
            </div>
          )}
        </div>

        {action ? (
          <div className="flex gap-2 px-5 py-4 border-t border-border bg-muted/20">
            <button
              type="button"
              className="flex-1 rounded-lg border border-border bg-background py-2 text-sm"
              onClick={onClose}
              disabled={busy}
            >
              Cancel
            </button>
            <button
              type="button"
              className={`flex-1 rounded-lg py-2 text-sm font-bold text-primary-foreground disabled:opacity-50 ${
                action === "delete_row" ? "bg-destructive" : "bg-primary"
              }`}
              onClick={() => void submit()}
              disabled={busy}
            >
              {busy ? (
                <Loader2 className="h-4 w-4 animate-spin mx-auto" />
              ) : isExecutive ? (
                action === "delete_row" ? "Delete row" : "Save"
              ) : (
                "Submit request"
              )}
            </button>
          </div>
        ) : null}
      </div>
    </FulfillmentModal>
  );
}
