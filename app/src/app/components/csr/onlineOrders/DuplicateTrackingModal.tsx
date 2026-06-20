import { FulfillmentModal, FulfillmentPressable } from "@/app/components/csr/onlineFulfillmentMotion";
import type { DuplicateTrackingConflict } from "@/app/lib/onlineOrdersTypes";
import { existingSourceLabel, pipelineLabel } from "@/app/lib/duplicateTrackingUtils";

function DetailBlock({
  title,
  name,
  items,
  courier,
  pipeline,
  extra,
}: {
  title: string;
  name?: string;
  items?: string;
  courier?: string;
  pipeline?: string;
  extra?: string;
}) {
  return (
    <div className="rounded-lg border border-border bg-muted/30 p-3 text-sm space-y-1.5">
      <p className="font-semibold text-foreground">{title}</p>
      {name ? (
        <p>
          <span className="text-muted-foreground">Recipient:</span> {name}
        </p>
      ) : null}
      {items ? (
        <p className="whitespace-pre-wrap break-words">
          <span className="text-muted-foreground">Items:</span> {items}
        </p>
      ) : null}
      {courier ? (
        <p>
          <span className="text-muted-foreground">Courier:</span> {courier}
        </p>
      ) : null}
      {pipeline ? (
        <p>
          <span className="text-muted-foreground">Pipeline:</span> {pipelineLabel(pipeline)}
        </p>
      ) : null}
      {extra ? <p className="text-muted-foreground text-xs pt-1">{extra}</p> : null}
    </div>
  );
}

function conflictExtra(conflict: DuplicateTrackingConflict): string {
  const ex = conflict.existing;
  const parts: string[] = [existingSourceLabel(ex.source)];
  if (ex.batch_id) {
    parts.push(`batch #${ex.batch_id}${ex.batch_status ? ` (${ex.batch_status})` : ""}`);
  }
  if (ex.parcel_id) {
    parts.push(`parcel #${ex.parcel_id}${ex.parcel_status ? ` — ${ex.parcel_status}` : ""}`);
  }
  if (ex.ship_date) {
    parts.push(`shipped ${ex.ship_date}`);
  }
  return parts.join(" · ");
}

export function DuplicateTrackingModal({
  open,
  conflicts,
  onClose,
}: {
  open: boolean;
  conflicts: DuplicateTrackingConflict[];
  onClose: () => void;
}) {
  return (
    <FulfillmentModal
      open={open && conflicts.length > 0}
      onClose={onClose}
      labelledBy="duplicate-tracking-title"
      panelClassName="max-w-2xl"
    >
      <div className="max-h-[85vh] flex flex-col overflow-hidden">
        <div className="px-6 pt-6 pb-4 border-b border-border">
          <h2 id="duplicate-tracking-title" className="text-lg font-semibold text-foreground">
            Tracking number already exists
          </h2>
          <p className="text-sm text-muted-foreground mt-1">
            {conflicts.length === 1
              ? "This tracking number is already saved. Details below."
              : `${conflicts.length} tracking numbers are already saved.`}
          </p>
        </div>

        <div className="overflow-y-auto px-6 py-4 space-y-5 flex-1">
          {conflicts.map((c, i) => (
            <div key={`${c.tracking_number}-${i}`} className="space-y-3">
              <p className="font-mono text-sm font-semibold break-all text-foreground bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                {c.tracking_raw ?? c.tracking_number}
              </p>
              <DetailBlock
                title="Existing record"
                name={c.existing.recipient_name}
                items={c.existing.items}
                courier={c.existing.courier}
                pipeline={c.existing.source_pipeline}
                extra={conflictExtra(c)}
              />
              {(c.attempted.recipient_name || c.attempted.items) && (
                <DetailBlock
                  title="What you tried to add"
                  name={c.attempted.recipient_name}
                  items={c.attempted.items}
                  courier={c.attempted.courier}
                  pipeline={c.attempted.source_pipeline}
                />
              )}
            </div>
          ))}
        </div>

        <div className="px-6 py-4 border-t border-border flex justify-end">
          <FulfillmentPressable variant="primary" onClick={onClose}>
            OK
          </FulfillmentPressable>
        </div>
      </div>
    </FulfillmentModal>
  );
}
