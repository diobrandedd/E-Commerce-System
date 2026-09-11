import { ScanLine, X, XCircle } from "lucide-react";
import { FulfillmentModal } from "@/app/components/csr/onlineFulfillmentMotion";
import type { DeliveryExistingRecording } from "@/app/lib/deliveriesApi";

export function DeliveryDuplicateScanModal({
  open,
  tracking,
  recordings,
  onStop,
  onScanDifferent,
  onContinueAnyway,
}: {
  open: boolean;
  tracking: string;
  recordings: DeliveryExistingRecording[];
  onStop: () => void;
  onScanDifferent: () => void;
  /** Optional: allow another documentary photo for the same TN */
  onContinueAnyway?: () => void;
}) {
  const latest = recordings[0];

  return (
    <FulfillmentModal
      open={open}
      onClose={onStop}
      panelClassName="max-w-md w-full"
      labelledBy="delivery-duplicate-title"
    >
      <div className="flex flex-col">
        <div className="flex items-start justify-between gap-3 px-5 pt-5 pb-4 border-b border-border">
          <div className="flex items-start gap-3 min-w-0">
            <XCircle className="h-7 w-7 text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <h2 id="delivery-duplicate-title" className="text-lg font-semibold text-foreground">
                Already recorded
              </h2>
              <p className="text-sm text-muted-foreground mt-1">
                <span className="font-mono font-semibold text-foreground break-all">{tracking}</span> already has a
                delivery photo on file{latest?.delivery_date ? ` (${latest.delivery_date})` : ""}.
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={onStop}
            aria-label="Close"
            className="flex-shrink-0 rounded-lg p-1.5 text-muted-foreground hover:bg-muted/60 hover:text-foreground"
          >
            <X size={18} />
          </button>
        </div>

        <div className="px-5 py-4 space-y-4">
          {recordings.length > 0 && (
            <ul className="text-xs text-muted-foreground space-y-1 rounded-lg bg-muted/30 border border-border px-3 py-2">
              {recordings.slice(0, 3).map(r => (
                <li key={r.delivery_item_id}>
                  Record #{r.delivery_item_id} · {r.delivery_date} · {r.item_status}
                  {r.tracking_number ? ` · ${r.tracking_number}` : ""}
                </li>
              ))}
            </ul>
          )}

          <p className="text-sm text-muted-foreground">
            Usually one photo is enough for RTS. Add another only if you need a second angle or the prior shot is
            wrong.
          </p>
        </div>

        <div className="flex flex-col gap-2 px-5 py-4 border-t border-border bg-muted/20">
          <button
            type="button"
            className="w-full flex items-center justify-center gap-2 rounded-xl bg-primary py-3 font-bold text-primary-foreground"
            onClick={onScanDifferent}
          >
            <ScanLine className="h-5 w-5" />
            Scan a different parcel
          </button>
          {onContinueAnyway ? (
            <button
              type="button"
              className="w-full rounded-xl border border-border bg-background py-3 text-sm font-semibold hover:bg-secondary/40 transition-colors"
              onClick={onContinueAnyway}
            >
              Add another photo anyway
            </button>
          ) : null}
          <button
            type="button"
            className="w-full rounded-xl border border-border bg-background py-3 text-sm font-semibold hover:bg-secondary/40 transition-colors"
            onClick={onStop}
          >
            Stop scanning
          </button>
        </div>
      </div>
    </FulfillmentModal>
  );
}
