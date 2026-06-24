import { useEffect, useState } from "react";
import type { ReactNode } from "react";
import { Loader2, Package, Pencil } from "lucide-react";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { FulfillmentModal, FulfillmentPressable } from "@/app/components/csr/onlineFulfillmentMotion";
import type { Role } from "@/app/context/AuthContext";
import { partnerSellerLabel, pipelineLabel } from "@/app/lib/duplicateTrackingUtils";
import {
  parcelsParsePreview,
  parcelsUpdateItems,
  type OnlineParcel,
} from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

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
    on_hold: "bg-violet-100 text-violet-900 border-violet-200",
    cancelled: "bg-red-100 text-red-900 border-red-200",
    returned: "bg-secondary text-muted-foreground border-border",
    rts: "bg-orange-100 text-orange-900 border-orange-200",
  };
  const labels: Record<OnlineParcel["status"], string> = {
    packing: "Packing",
    shipped: "Shipped",
    on_hold: "On hold",
    cancelled: "Cancelled",
    returned: "Returned",
    rts: "RTS",
  };
  return (
    <span className={`inline-flex text-xs font-semibold px-2.5 py-0.5 rounded-md border ${styles[status] ?? "bg-secondary text-muted-foreground border-border"}`}>
      {labels[status] ?? status.replace(/_/g, " ")}
    </span>
  );
}

export function canEditParcelItems(role: Role | undefined, parcel: OnlineParcel): boolean {
  if (parcel.status !== "packing" || parcel.deducted_at) {
    return false;
  }
  return role === "executive" || role === "csr_tl" || role === "csr_staff";
}

function ParsedPreview({ parcel }: { parcel: OnlineParcel }) {
  return (
    <>
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
          <ul className="text-sm text-sky-950 space-y-1">
            {parcel.items_parsed.other_items.map((o, i) => (
              <li key={i}>
                {o.qty}× {o.label}
              </li>
            ))}
          </ul>
        </div>
      )}
    </>
  );
}

export function OnlineParcelDetailModal({
  parcel,
  onClose,
  onUpdated,
  role,
}: {
  parcel: OnlineParcel | null;
  onClose: () => void;
  onUpdated?: (parcel: OnlineParcel) => void;
  role?: Role;
}) {
  const [detail, setDetail] = useState<OnlineParcel | null>(parcel);
  const [editing, setEditing] = useState(false);
  const [itemsDraft, setItemsDraft] = useState("");
  const [previewBusy, setPreviewBusy] = useState(false);
  const [saveBusy, setSaveBusy] = useState(false);
  const [preview, setPreview] = useState<OnlineParcel["items_parsed"] | null>(null);
  const [previewErrors, setPreviewErrors] = useState<string[]>([]);
  const [modalError, setModalError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    setDetail(parcel);
    setEditing(false);
    setItemsDraft(parcel?.items_raw ?? "");
    setPreview(null);
    setPreviewErrors([]);
    setModalError(null);
    setSuccess(null);
  }, [parcel]);

  const canEdit = detail ? canEditParcelItems(role, detail) : false;

  const createdAt = detail?.created_at
    ? new Date(detail.created_at.replace(" ", "T")).toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      })
    : null;

  const runPreview = async () => {
    if (!itemsDraft.trim()) {
      setPreview(null);
      setPreviewErrors(["Items text is required."]);
      return;
    }
    setPreviewBusy(true);
    setModalError(null);
    try {
      const res = await parcelsParsePreview(itemsDraft);
      setPreviewErrors(res.errors ?? []);
      setPreview({ deltas: res.deltas, other_items: res.other_items });
    } catch (e) {
      setPreview(null);
      setPreviewErrors([e instanceof ApiError ? e.message : "Preview failed."]);
    } finally {
      setPreviewBusy(false);
    }
  };

  const saveItems = async () => {
    if (!detail) return;
    setSaveBusy(true);
    setModalError(null);
    try {
      const res = await parcelsUpdateItems(detail.id, itemsDraft);
      setDetail(res.parcel);
      setEditing(false);
      setSuccess("Parcel contents updated.");
      onUpdated?.(res.parcel);
      window.setTimeout(() => setSuccess(null), 3000);
    } catch (e) {
      setModalError(e instanceof ApiError ? e.message : "Could not save items.");
    } finally {
      setSaveBusy(false);
    }
  };

  return (
    <FulfillmentModal
      open={parcel !== null}
      onClose={onClose}
      labelledBy="parcel-detail-title"
      panelClassName="max-w-lg"
    >
      {detail && (
        <div className="overflow-hidden">
          <div className="px-6 pt-6 pb-4 border-b border-border">
            <div className="flex items-start justify-between gap-3">
              <div>
                <h3 id="parcel-detail-title" className="font-bold text-lg">
                  Parcel #{detail.id}
                </h3>
                <p className="font-mono text-sm font-semibold text-[#C8900A] mt-1 break-all">{detail.tracking_number}</p>
              </div>
              <ParcelStatusBadge status={detail.status} />
            </div>
          </div>

          <div className="px-6 py-4 space-y-4 max-h-[min(70vh,32rem)] overflow-y-auto">
            <AppSuccessNotice message={success} />
            <AppErrorBanner message={modalError} />

            <div className="rounded-lg border border-border bg-secondary/20 overflow-hidden">
              <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground px-4 pt-4 pb-2">
                Packing photo
              </p>
              {detail.packing_photo?.photo_url ? (
                <div className="px-4 pb-4 space-y-2">
                  <img
                    src={detail.packing_photo.photo_url}
                    alt="Parcel packing photo from deliveries"
                    className="w-full max-h-64 object-contain rounded-md border border-border bg-background"
                  />
                  {detail.packing_photo.delivery_date && (
                    <p className="text-xs text-muted-foreground">
                      Captured on delivery batch {detail.packing_photo.delivery_date}
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
              <DetailRow label="Recipient" value={detail.recipient_name} />
              <DetailRow label="Courier" value={detail.courier} />
              <DetailRow label="Pipeline" value={pipelineLabel(detail.source_pipeline)} />
              <DetailRow label="Partner seller" value={partnerSellerLabel(detail.partner_seller)} />
              <DetailRow label="Order no." value={detail.order_no} mono />
              <DetailRow label="CSR batch" value={detail.csr_batch_id ? `#${detail.csr_batch_id}` : null} />
              <DetailRow label="Ship date" value={detail.ship_date} />
              <DetailRow label="RTS recorded" value={detail.rts_recorded_at} />
              <DetailRow label="RTS case" value={detail.rts_case_id ? `#${detail.rts_case_id}` : null} mono />
              <DetailRow label="Inventory deducted" value={detail.deducted_at} />
              <DetailRow label="Created" value={createdAt} />
            </dl>

            <div className="rounded-lg border border-border bg-secondary/30 p-4 space-y-3">
              <div className="flex items-center justify-between gap-2">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Items</p>
                {canEdit && !editing && (
                  <FulfillmentPressable
                    variant="outline"
                    className="text-xs px-2.5 py-1 h-auto"
                    onClick={() => {
                      setItemsDraft(detail.items_raw);
                      setPreview(null);
                      setPreviewErrors([]);
                      setEditing(true);
                    }}
                  >
                    <Pencil className="h-3 w-3 mr-1 inline" />
                    Edit contents
                  </FulfillmentPressable>
                )}
              </div>

              {editing ? (
                <div className="space-y-3">
                  <p className="text-xs text-muted-foreground">
                    Fix the item line if the packing photo or label does not match what packers should deduct.
                  </p>
                  <textarea
                    value={itemsDraft}
                    onChange={e => {
                      setItemsDraft(e.target.value);
                      setPreview(null);
                      setPreviewErrors([]);
                    }}
                    rows={4}
                    className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"
                    placeholder="e.g. 1 Liter Honey LIGHT ; 2 Paragis Tea"
                  />
                  <div className="flex flex-wrap gap-2">
                    <FulfillmentPressable
                      variant="outline"
                      className="text-xs"
                      disabled={previewBusy}
                      onClick={() => void runPreview()}
                    >
                      {previewBusy ? <Loader2 className="h-3.5 w-3.5 animate-spin mr-1 inline" /> : null}
                      Preview parse
                    </FulfillmentPressable>
                    <FulfillmentPressable
                      variant="primary"
                      className="text-xs"
                      disabled={saveBusy || previewBusy}
                      onClick={() => void saveItems()}
                    >
                      {saveBusy ? <Loader2 className="h-3.5 w-3.5 animate-spin mr-1 inline" /> : null}
                      Save contents
                    </FulfillmentPressable>
                    <FulfillmentPressable
                      variant="outline"
                      className="text-xs"
                      disabled={saveBusy}
                      onClick={() => {
                        setEditing(false);
                        setItemsDraft(detail.items_raw);
                        setPreview(null);
                        setPreviewErrors([]);
                      }}
                    >
                      Cancel
                    </FulfillmentPressable>
                  </div>
                  {previewErrors.length > 0 && (
                    <ul className="text-xs text-red-700 list-disc ml-4 space-y-0.5">
                      {previewErrors.map((err, i) => (
                        <li key={i}>{err}</li>
                      ))}
                    </ul>
                  )}
                  {preview && previewErrors.length === 0 && (
                    <ParsedPreview parcel={{ ...detail, items_parsed: preview }} />
                  )}
                </div>
              ) : (
                <>
                  <p className="text-sm whitespace-pre-wrap break-words">{detail.items_raw || "—"}</p>
                  <ParsedPreview parcel={detail} />
                </>
              )}
            </div>
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
