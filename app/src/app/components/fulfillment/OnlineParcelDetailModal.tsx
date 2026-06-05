import type { ReactNode } from "react";
import { Package } from "lucide-react";
import { FulfillmentModal, FulfillmentPressable } from "@/app/components/csr/onlineFulfillmentMotion";
import { pipelineLabel } from "@/app/lib/duplicateTrackingUtils";
import type { OnlineParcel } from "@/app/lib/parcelsApi";

function DetailRow({ label, value, mono }: { label: string; value: ReactNode; mono?: boolean }) {
  if (value === null || value === undefined || value === "") return null;
  return (
    <div className="flex justify-between gap-4 text-sm">
      <dt className="text-muted-foreground shrink-0">{label}</dt>
      <dd className={`font-medium text-right ${mono ? "font-mono" : ""}`}>{value}</dd>
    </div>
  );
}

function ParcelStatusBadge({ status }: { status: OnlineParcel["status"] }) {
  const styles: Record<OnlineParcel["status"], string> = {
    packing: "bg-amber-100 text-amber-900 border-amber-200",
    shipped: "bg-green-100 text-green-900 border-green-200",
    cancelled: "bg-red-100 text-red-900 border-red-200",
    returned: "bg-secondary text-muted-foreground border-border",
  };
  return (
    <span className={`inline-flex text-xs font-semibold capitalize px-2.5 py-0.5 rounded-md border ${styles[status]}`}>
      {status}
    </span>
  );
}

export function OnlineParcelDetailModal({
  parcel,
  onClose,
}: {
  parcel: OnlineParcel | null;
  onClose: () => void;
}) {
  const createdAt = parcel?.created_at
    ? new Date(parcel.created_at.replace(" ", "T")).toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      })
    : null;

  return (
    <FulfillmentModal
      open={parcel !== null}
      onClose={onClose}
      labelledBy="parcel-detail-title"
      panelClassName="max-w-lg"
    >
      {parcel && (
        <div className="overflow-hidden">
          <div className="px-6 pt-6 pb-4 border-b border-border">
            <div className="flex items-start justify-between gap-3">
              <div>
                <h3 id="parcel-detail-title" className="font-bold text-lg">
                  Parcel #{parcel.id}
                </h3>
                <p className="font-mono text-sm font-semibold text-[#C8900A] mt-1 break-all">{parcel.tracking_number}</p>
              </div>
              <ParcelStatusBadge status={parcel.status} />
            </div>
          </div>

          <div className="px-6 py-4 space-y-4">
            <div className="rounded-lg border border-border bg-secondary/20 overflow-hidden">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground px-4 pt-4 pb-2">
                Packing photo
              </p>
              {parcel.packing_photo?.photo_url ? (
                <div className="px-4 pb-4 space-y-2">
                  <img
                    src={parcel.packing_photo.photo_url}
                    alt="Parcel packing photo from deliveries"
                    className="w-full max-h-64 object-contain rounded-md border border-border bg-background"
                  />
                  {parcel.packing_photo.delivery_date && (
                    <p className="text-xs text-muted-foreground">
                      Captured on delivery batch {parcel.packing_photo.delivery_date}
                    </p>
                  )}
                </div>
              ) : (
                <div className="mx-4 mb-4 flex flex-col items-center justify-center gap-2 rounded-md border border-dashed border-border bg-background/80 px-4 py-10 text-center">
                  <Package className="h-8 w-8 text-muted-foreground/60" strokeWidth={1.5} />
                  <p className="text-sm font-medium text-muted-foreground">Not yet packed</p>
                  <p className="text-xs text-muted-foreground/80">Photo will appear after deliveries scan</p>
                </div>
              )}
            </div>

            <dl className="space-y-2">
              <DetailRow label="Recipient" value={parcel.recipient_name} />
              <DetailRow label="Courier" value={parcel.courier} />
              <DetailRow label="Pipeline" value={pipelineLabel(parcel.source_pipeline)} />
              <DetailRow label="Order no." value={parcel.order_no} mono />
              <DetailRow label="CSR batch" value={parcel.csr_batch_id ? `#${parcel.csr_batch_id}` : null} />
              <DetailRow label="Ship date" value={parcel.ship_date} />
              <DetailRow label="Inventory deducted" value={parcel.deducted_at} />
              <DetailRow label="Created" value={createdAt} />
            </dl>

            <div className="rounded-lg border border-border bg-secondary/30 p-4 space-y-2">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Items (raw)</p>
              <p className="text-sm whitespace-pre-wrap break-words">{parcel.items_raw || "—"}</p>
            </div>

            {parcel.items_parsed?.deltas && parcel.items_parsed.deltas.length > 0 && (
              <div className="rounded-lg border border-amber-200 bg-amber-50/80 p-4 space-y-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-amber-900">Parsed for inventory (honey)</p>
                <ul className="text-sm text-amber-950 space-y-1">
                  {parcel.items_parsed.deltas.map((d, i) => (
                    <li key={i}>
                      {d.qty}× {d.variant} {d.size}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {parcel.items_parsed?.other_items && parcel.items_parsed.other_items.length > 0 && (
              <div className="rounded-lg border border-sky-200 bg-sky-50/80 p-4 space-y-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-sky-900">Other products (noted)</p>
                <p className="text-[10px] text-sky-800/80">Inventory tracking for these items is coming later.</p>
                <ul className="text-sm text-sky-950 space-y-1">
                  {parcel.items_parsed.other_items.map((o, i) => (
                    <li key={i}>
                      {o.qty}× {o.label}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>

          <div className="px-6 py-4 border-t border-border flex justify-end">
            <FulfillmentPressable variant="primary" onClick={onClose}>
              Close
            </FulfillmentPressable>
          </div>
        </div>
      )}
    </FulfillmentModal>
  );
}

export function ParcelStatusBadgeInline({ status }: { status: OnlineParcel["status"] }) {
  return <ParcelStatusBadge status={status} />;
}
