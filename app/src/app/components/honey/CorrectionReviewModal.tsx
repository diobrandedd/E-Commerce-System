import { useState } from "react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { apiPost, ApiError } from "@/app/lib/api";

export interface RowContext {
  stage_label: string;
  supplier_code?: string;
  supplier_name?: string;
  arrival_date?: string;
  batch_no?: string;
  bottling_date?: string;
  variant?: string;
  product_label?: string;
  current_qty?: number;
  initial_qty?: number;
  qty_unit?: string;
  sizes?: Record<string, number>;
  initial_sizes?: Record<string, number>;
}

export interface ChangePreview {
  headline: string;
  detail?: string | null;
  before_display?: string | null;
  after_display?: string | null;
  size?: string;
}

export interface CorrectionRequestDetail {
  id: number;
  stage: string;
  row_table: string;
  row_id: number;
  correction_type?: string;
  new_value?: string | null;
  action: string;
  amount: string;
  size: string | null;
  reason: string;
  status: string;
  rejection_note?: string | null;
  requested_by_name: string;
  reviewed_by_name?: string | null;
  created_at: string;
  executive_viewed_at?: string | null;
  row_context?: RowContext | null;
  change_preview?: ChangePreview | null;
  summary?: string;
}

function fmtQtyPair(current?: number, initial?: number, unit?: string): string {
  if (current === undefined) return "—";
  const c = Number.isFinite(current) ? (Math.abs(current - Math.round(current)) < 0.001 ? Math.round(current) : current.toFixed(1)) : "—";
  const i = initial !== undefined && Number.isFinite(initial) ? (Math.abs(initial - Math.round(initial)) < 0.001 ? Math.round(initial) : initial.toFixed(1)) : "—";
  return unit ? `${c} / ${i} ${unit}` : `${c} / ${i}`;
}

function InventoryContextCard({ ctx }: { ctx: RowContext }) {
  const isPackaging = Boolean(ctx.sizes);

  return (
    <div className="rounded-lg border border-border bg-secondary/30 p-4 space-y-2 text-sm">
      <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        Current inventory row — {ctx.stage_label}
      </p>
      {ctx.supplier_code && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Supplier code</span>
          <span className="font-mono font-semibold text-[#C8900A]">{ctx.supplier_code}</span>
        </div>
      )}
      {ctx.supplier_name && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Supplier name</span>
          <span className="font-medium text-right">{ctx.supplier_name}</span>
        </div>
      )}
      {ctx.arrival_date && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Arrival</span>
          <span>{ctx.arrival_date}</span>
        </div>
      )}
      {ctx.batch_no && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Batch</span>
          <span className="font-mono font-medium">{ctx.batch_no}</span>
        </div>
      )}
      {ctx.bottling_date && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Bottling date</span>
          <span>{ctx.bottling_date}</span>
        </div>
      )}
      {ctx.variant && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Variant</span>
          <span className="capitalize font-medium">{ctx.variant}</span>
        </div>
      )}
      {ctx.product_label && (
        <div className="flex justify-between gap-2">
          <span className="text-muted-foreground">Product</span>
          <span className="font-medium">{ctx.product_label}</span>
        </div>
      )}
      {!isPackaging && ctx.current_qty !== undefined && (
        <div className="flex justify-between gap-2 pt-1 border-t border-border/60">
          <span className="text-muted-foreground">Qty (current / initial)</span>
          <span className="font-semibold">{fmtQtyPair(ctx.current_qty, ctx.initial_qty, ctx.qty_unit)}</span>
        </div>
      )}
      {isPackaging && ctx.sizes && (
        <div className="pt-1 border-t border-border/60 space-y-1">
          <p className="text-xs text-muted-foreground">Bottles (current / initial)</p>
          {Object.entries(ctx.sizes).map(([size, cur]) => (
            <div key={size} className="flex justify-between text-xs">
              <span className="capitalize">{size}</span>
              <span className="font-medium">
                {fmtQtyPair(cur, ctx.initial_sizes?.[size])}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export function CorrectionReviewModal({
  request,
  onClose,
  onDone,
}: {
  request: CorrectionRequestDetail;
  onClose: () => void;
  onDone: () => void;
}) {
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const pending = request.status === "pending";
  const ctx = request.row_context;
  const preview = request.change_preview;

  const approve = async () => {
    setBusy(true);
    setErr(null);
    try {
      await apiPost("honey.php", "correction_approve", { id: request.id });
      onDone();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Failed");
    } finally {
      setBusy(false);
    }
  };

  const reject = async () => {
    const note = prompt("Rejection reason (required):");
    if (!note?.trim()) return;
    setBusy(true);
    setErr(null);
    try {
      await apiPost("honey.php", "correction_reject", { id: request.id, rejection_note: note });
      onDone();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Failed");
    } finally {
      setBusy(false);
    }
  };

  const submittedAt = request.created_at
    ? new Date(request.created_at.replace(" ", "T")).toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      })
    : "";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-lg w-full" labelledBy="correction-review-title">
      <AppModalHeader
        title={`Correction request #${request.id}`}
        subtitle={`${request.status} · ${request.stage}${submittedAt ? ` · ${submittedAt}` : ""}`}
        titleId="correction-review-title"
        onClose={onClose}
      />
      <AppModalBody>
        {ctx ? (
          <InventoryContextCard ctx={ctx} />
        ) : (
          <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Inventory row #{request.row_id} could not be loaded.
          </p>
        )}

        {preview && (
          <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50/80 p-4 space-y-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-amber-900">If approved</p>
            <p className="text-sm font-semibold text-amber-950">{preview.headline}</p>
            {preview.detail && <p className="text-xs text-amber-900/90">{preview.detail}</p>}
            {preview.before_display != null && preview.after_display != null && (
              <div className="flex items-center justify-center gap-3 pt-2 text-sm font-medium text-amber-950">
                <span className="px-3 py-1.5 rounded-lg bg-white/70 border border-amber-200 line-through decoration-amber-700">
                  {preview.before_display}
                </span>
                <span className="text-amber-700">→</span>
                <span className="px-3 py-1.5 rounded-lg bg-white border-2 border-[#C8900A] font-bold">
                  {preview.after_display}
                </span>
              </div>
            )}
          </div>
        )}

        <dl className="space-y-2 text-sm mt-4 mb-4">
          <div>
            <dt className="text-xs text-muted-foreground">Requested by</dt>
            <dd className="font-medium">{request.requested_by_name}</dd>
          </div>
          <div>
            <dt className="text-xs text-muted-foreground">PM reason</dt>
            <dd className="italic">“{request.reason}”</dd>
          </div>
          {request.rejection_note && (
            <div>
              <dt className="text-xs text-red-600">Rejection note</dt>
              <dd>{request.rejection_note}</dd>
            </div>
          )}
          {request.reviewed_by_name && (
            <div>
              <dt className="text-xs text-muted-foreground">Reviewed by</dt>
              <dd>{request.reviewed_by_name}</dd>
            </div>
          )}
        </dl>

        {err && <p className="text-xs text-red-600">{err}</p>}
      </AppModalBody>
      <AppModalFooter>
        <button type="button" onClick={onClose} className="flex-1 py-2 rounded-lg border border-border bg-background text-sm">
          Close
        </button>
        {pending && (
          <>
            <button
              type="button"
              disabled={busy}
              onClick={() => void reject()}
              className="flex-1 py-2 rounded-lg border border-border text-sm text-red-600 font-semibold"
            >
              Reject
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => void approve()}
              className="flex-1 py-2 rounded-lg text-sm font-bold text-primary-foreground bg-primary"
            >
              Approve
            </button>
          </>
        )}
      </AppModalFooter>
    </AppModal>
  );
}
