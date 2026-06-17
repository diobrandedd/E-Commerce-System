import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { BookOpen, Eye, Loader2, Pencil } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { DeliveriesReviewGuideModal } from "@/app/components/fulfillment/DeliveriesReviewGuideModal";
import { runDeliveriesReviewTour } from "@/app/components/fulfillment/deliveriesReviewTour";
import { DeliveryReviewEditModal } from "@/app/components/fulfillment/DeliveryReviewEditModal";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { AppErrorBanner, AppSuccessNotice, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { deliveriesReviewList, type DeliveryReviewRow } from "@/app/lib/deliveriesApi";
import { ApiError } from "@/app/lib/api";

function ParcelStatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    packing: "bg-amber-100 text-amber-900",
    shipped: "bg-green-100 text-green-900",
    cancelled: "bg-red-100 text-red-900",
    returned: "bg-muted text-muted-foreground",
  };
  return (
    <span className={`inline-block text-xs font-semibold px-2 py-0.5 rounded capitalize ${styles[status] ?? "bg-muted text-muted-foreground"}`}>
      {status}
    </span>
  );
}

function displayTracking(row: DeliveryReviewRow): string {
  return row.parcel?.tracking_number || row.tracking_number || "—";
}

export function DeliveriesReviewPage() {
  const { user } = useAuth();
  const isExecutive = user?.role === "executive";
  const [q, setQ] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<DeliveryReviewRow[]>([]);
  const [total, setTotal] = useState(0);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [editRow, setEditRow] = useState<DeliveryReviewRow | null>(null);
  const [viewRow, setViewRow] = useState<DeliveryReviewRow | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);

  const load = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesReviewList({ q, from, to, page, per_page: 10 });
      setRows(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setBusy(false);
    }
  }, [q, from, to, page]);

  useEffect(() => {
    void load();
  }, [load]);

  const flash = (msg: string) => {
    setNotice(msg);
    window.setTimeout(() => setNotice(null), 3200);
  };

  return (
    <PageLayout
      title="Deliveries review"
      subtitle={
        isExecutive
          ? "View delivery photos — edit content or delete rows"
          : "View delivery photos — submit edit or delete requests for executives"
      }
      actions={
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setGuideOpen(true)}
            className={appHeaderActionClass}
          >
            <BookOpen size={13} /> Guide
          </button>
          <Link
            to="/deliveries/cancelled"
            className="sm:hidden text-xs font-semibold rounded-lg border border-border px-2.5 py-1.5 text-muted-foreground"
          >
            Cancelled
          </Link>
          <div className="hidden sm:flex flex-wrap items-center gap-3">
            <Link to="/deliveries/cancelled" className="text-xs font-semibold rounded-lg border border-border px-3 py-1.5 text-muted-foreground hover:text-foreground hover:bg-secondary/40">
              Cancelled page
            </Link>
            <Link to="/deliveries" className="text-sm font-semibold text-[#C8900A]">
              ← Add deliveries
            </Link>
          </div>
          {!isExecutive && (
            <Link to="/inventory/requests#edit-requests-section" className="text-sm font-semibold text-[#C8900A]">
              My requests
            </Link>
          )}
        </div>
      }
    >
      <div className="flex flex-wrap gap-2 mb-4" data-tour="deliveries-review-filters">
        <input
          className="rounded-lg border border-border px-3 py-2 text-sm"
          placeholder="Search tracking, name, items…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={from} onChange={(e) => setFrom(e.target.value)} />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={to} onChange={(e) => setTo(e.target.value)} />
        <Button type="button" onClick={() => void load()}>
          Search
        </Button>
      </div>

      <AppSuccessNotice message={notice} />
      <AppErrorBanner message={error} />
      {busy && <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />}

      <div className="overflow-x-auto rounded-xl border border-border" data-tour="deliveries-review-table">
        <table className="w-full text-sm">
          <thead className="bg-muted/40">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">Date</th>
              <th className="px-3 py-2 text-left">Tracking</th>
              <th className="px-3 py-2 text-left" data-tour="deliveries-review-photo">
                Photo
              </th>
              <th className="px-3 py-2 text-left">Status</th>
              <th className="px-3 py-2 text-left w-28" data-tour="deliveries-review-actions">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.item_id} className="border-t border-border">
                <td className="px-3 py-2">{r.item_id}</td>
                <td className="px-3 py-2">{r.delivery_date}</td>
                <td className="px-3 py-2 font-mono">{displayTracking(r)}</td>
                <td className="px-3 py-2">
                  {r.photo_url ? (
                    <button
                      type="button"
                      className="block rounded-lg overflow-hidden border border-border hover:ring-2 hover:ring-[#C8900A]/40"
                      onClick={() => setViewRow(r)}
                      aria-label={`View ${displayTracking(r)}`}
                    >
                      <img src={r.photo_url} alt="" className="h-12 w-12 object-cover" />
                    </button>
                  ) : (
                    <span className="text-xs text-muted-foreground">—</span>
                  )}
                </td>
                <td className="px-3 py-2">{r.parcel?.status ?? r.item_status}</td>
                <td className="px-3 py-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      className="inline-flex items-center gap-1 text-muted-foreground font-semibold text-xs"
                      onClick={() => setViewRow(r)}
                    >
                      <Eye size={12} /> View
                    </button>
                    <button
                      type="button"
                      className="inline-flex items-center gap-1 text-[#C8900A] font-semibold text-xs"
                      onClick={() => setEditRow(r)}
                    >
                      <Pencil size={12} /> Edit
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {!busy && rows.length === 0 && (
        <p className="text-sm text-muted-foreground text-center py-6">No delivery records match your filters.</p>
      )}

      <div data-tour="deliveries-review-pagination">
      <p className="text-xs text-muted-foreground mt-2">
        {total} row{total === 1 ? "" : "s"} · page {page}
        {!isExecutive ? " · Executives approve on Requests → Edit requests" : null}
      </p>
      <div className="flex gap-2 mt-2">
        <button type="button" disabled={page <= 1} className="text-sm" onClick={() => setPage((p) => Math.max(1, p - 1))}>
          Prev
        </button>
        <button type="button" disabled={rows.length < 10} className="text-sm" onClick={() => setPage((p) => p + 1)}>
          Next
        </button>
      </div>
      </div>

      <DeliveriesReviewGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        isExecutive={isExecutive}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runDeliveriesReviewTour({ isExecutive }));
        }}
      />

      <AppModal
        open={viewRow !== null}
        onClose={() => setViewRow(null)}
        panelClassName="max-w-lg w-full"
        labelledBy="delivery-view-title"
      >
        {viewRow && (
          <>
            <AppModalHeader
              title="Delivery record"
              subtitle={`#${viewRow.item_id} · ${viewRow.delivery_date}`}
              titleId="delivery-view-title"
              onClose={() => setViewRow(null)}
            />
            <AppModalBody className="space-y-4">
              <div>
                <p className="text-xs text-muted-foreground">Tracking</p>
                <p className="font-mono font-semibold break-all">{displayTracking(viewRow)}</p>
              </div>
              {viewRow.parcel?.recipient_name ? (
                <div>
                  <p className="text-xs text-muted-foreground">Name</p>
                  <p className="text-sm font-medium">{viewRow.parcel.recipient_name}</p>
                </div>
              ) : null}
              {viewRow.parcel?.items_raw ? (
                <div>
                  <p className="text-xs text-muted-foreground">Item/s</p>
                  <p className="text-sm whitespace-pre-wrap">{viewRow.parcel.items_raw}</p>
                </div>
              ) : null}
              {viewRow.parcel ? (
                <div className="flex flex-wrap items-center gap-2 text-xs">
                  {viewRow.parcel.order_no ? (
                    <span className="text-muted-foreground">Order: {viewRow.parcel.order_no}</span>
                  ) : null}
                  {viewRow.parcel.courier ? (
                    <span className="text-muted-foreground">Courier: {viewRow.parcel.courier}</span>
                  ) : null}
                  <ParcelStatusBadge status={viewRow.parcel.status} />
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  No linked online order parcel — CSR batch may not be finalized yet.
                </p>
              )}
              <div>
                <p className="text-xs text-muted-foreground mb-2">Photo</p>
                {viewRow.photo_url ? (
                  <img
                    src={viewRow.photo_url}
                    alt={`Parcel ${displayTracking(viewRow)}`}
                    className="w-full rounded-lg border border-border object-contain max-h-[50vh]"
                  />
                ) : (
                  <p className="text-sm text-muted-foreground">No photo on file.</p>
                )}
              </div>
            </AppModalBody>
          </>
        )}
      </AppModal>

      <DeliveryReviewEditModal
        open={editRow !== null}
        row={editRow}
        isExecutive={isExecutive}
        onClose={() => setEditRow(null)}
        onDone={(msg) => {
          flash(msg);
          void load();
        }}
      />

    </PageLayout>
  );
}
