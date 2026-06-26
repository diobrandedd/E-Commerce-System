import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { BookOpen, Loader2, ScanLine } from "lucide-react";
import { TrackingQrScannerModal } from "@/app/components/scan/TrackingQrScannerModal";
import { DeliveriesCancelledGuideModal } from "@/app/components/fulfillment/DeliveriesCancelledGuideModal";
import { runDeliveriesCancelledTour } from "@/app/components/fulfillment/deliveriesCancelledTour";
import { PageLayout } from "@/app/components/PageLayout";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import {
  deliveriesCancelLogList,
  deliveriesCancelParcelScan,
  deliveriesParcelByTracking,
  type DeliveryCancelLogRow,
} from "@/app/lib/deliveriesApi";
import { ApiError } from "@/app/lib/api";
import { AppErrorBanner, AppSuccessNotice, appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";

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

export function DeliveriesCancelledPage() {
  const { user } = useAuth();
  const isExecutive = user?.role === "executive";
  const [q, setQ] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [cancelRows, setCancelRows] = useState<DeliveryCancelLogRow[]>([]);
  const [cancelTotal, setCancelTotal] = useState(0);
  const [cancelPage, setCancelPage] = useState(1);
  const [busy, setBusy] = useState(false);
  const [cancelActionBusy, setCancelActionBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [trackingInput, setTrackingInput] = useState("");
  const [scannerOpen, setScannerOpen] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const loadCancelLogs = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesCancelLogList({ q, from, to, page: cancelPage, per_page: 10 });
      setCancelRows(res.rows);
      setCancelTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Cancelled log load failed.");
    } finally {
      setBusy(false);
    }
  }, [q, from, to, cancelPage]);

  useEffect(() => {
    void loadCancelLogs();
  }, [loadCancelLogs]);

  const flash = (msg: string) => {
    setNotice(msg);
    window.setTimeout(() => setNotice(null), 3200);
  };

  const runCancelByTracking = useCallback(
    async (rawTracking: string) => {
      const input = rawTracking.trim();
      if (!input) return;
      setCancelActionBusy(true);
      setError(null);
      try {
        const lookup = await deliveriesParcelByTracking(input);
        if (!lookup.parcel) {
          setError("No parcel found for this tracking.");
          return;
        }
        const tracking = lookup.parcel.tracking_number;
        const status = lookup.parcel.status;
        const prompt =
          status === "shipped"
            ? `Mark ${tracking} as courier cancelled? The sealed box can be re-waybilled — inventory will not be restored.`
            : `Mark ${tracking} as courier cancelled? Box can be re-waybilled later.`;
        if (!window.confirm(prompt)) return;

        const result = await deliveriesCancelParcelScan(tracking);
        flash(result.message || "Parcel cancelled.");
        setTrackingInput("");
        await loadCancelLogs();
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Cancel failed.");
      } finally {
        setCancelActionBusy(false);
      }
    },
    [loadCancelLogs],
  );

  return (
    <PageLayout
      title="Deliveries cancelled"
      subtitle="Courier-cancelled orders — sealed boxes stay packed; re-waybill on Add deliveries"
      actions={
        <div className="flex items-center gap-2">
          <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
            <BookOpen size={13} /> Guide
          </button>
          <Link to="/deliveries/review" className="sm:hidden text-xs font-semibold rounded-lg border border-border px-2.5 py-1.5 text-muted-foreground">
            Review
          </Link>
          <div className="hidden sm:flex items-center gap-3">
            <Link to="/deliveries/review" className="text-xs font-semibold rounded-lg border border-border px-3 py-1.5 text-muted-foreground hover:text-foreground hover:bg-secondary/40">
              Review batches
            </Link>
            <Link to="/deliveries" className="text-sm font-semibold text-[#C8900A]">
              ← Add deliveries
            </Link>
          </div>
        </div>
      }
    >
      <div className="rounded-xl border border-border bg-card p-4 space-y-3 mb-6" data-tour="deliveries-cancel-scanner">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-sm font-semibold text-foreground">Cancel parcel scanner</h2>
          <button
            type="button"
            className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-secondary/40"
            onClick={() => {
              setError(null);
              setScannerOpen(true);
            }}
            disabled={cancelActionBusy}
          >
            <ScanLine size={13} />
            Scan parcel
          </button>
        </div>
        <p className="text-xs text-muted-foreground">
          Use after courier cancel feedback. Inventory stays deducted — re-waybill the sealed box on Add deliveries.
        </p>
        <div className="flex flex-wrap items-center gap-2">
          <input
            className="min-w-[14rem] flex-1 rounded-lg border border-border px-3 py-2 text-sm font-mono"
            placeholder="Scan or type tracking number"
            value={trackingInput}
            onChange={(e) => setTrackingInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                e.preventDefault();
                void runCancelByTracking(trackingInput);
              }
            }}
          />
          <button
            type="button"
            className="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"
            onClick={() => void runCancelByTracking(trackingInput)}
            disabled={cancelActionBusy || !trackingInput.trim()}
          >
            {cancelActionBusy ? "Cancelling..." : "Cancel parcel"}
          </button>
        </div>
      </div>

      <AppSuccessNotice message={notice} />
      <AppErrorBanner message={error} />

      <div className="flex flex-wrap gap-2 mb-4" data-tour="deliveries-cancel-filters">
        <input
          className="rounded-lg border border-border px-3 py-2 text-sm"
          placeholder="Search tracking, name, items…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={from} onChange={(e) => setFrom(e.target.value)} />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={to} onChange={(e) => setTo(e.target.value)} />
        <button type="button" className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground" onClick={() => void loadCancelLogs()}>
          Search
        </button>
      </div>

      {busy ? <Loader2 className="h-5 w-5 animate-spin text-muted-foreground mb-2" /> : null}
      <div className="overflow-x-auto rounded-xl border border-border" data-tour="deliveries-cancel-table">
        <table className="w-full text-sm">
          <thead className="bg-muted/40">
            <tr>
              <th className="px-3 py-2 text-left">When</th>
              <th className="px-3 py-2 text-left">Tracking</th>
              <th className="px-3 py-2 text-left">Prev status</th>
              <th className="px-3 py-2 text-left">Box</th>
              <th className="px-3 py-2 text-left">Cancelled by</th>
              <th className="px-3 py-2 text-left">Order</th>
            </tr>
          </thead>
          <tbody>
            {cancelRows.map((r) => (
              <tr key={r.id} className="border-t border-border align-top">
                <td className="px-3 py-2 whitespace-nowrap">{r.created_at.replace("T", " ")}</td>
                <td className="px-3 py-2">
                  <p className="font-mono">{r.tracking_number}</p>
                  {r.items_raw ? <p className="text-xs text-muted-foreground max-w-72 truncate">{r.items_raw}</p> : null}
                </td>
                <td className="px-3 py-2">
                  <ParcelStatusBadge status={r.previous_status} />
                </td>
                <td className="px-3 py-2">
                  <span className="text-xs text-muted-foreground">Sealed — re-waybill</span>
                </td>
                <td className="px-3 py-2">
                  {r.cancelled_by_name || r.cancelled_by_username ? (
                    <span>{r.cancelled_by_name ?? r.cancelled_by_username}</span>
                  ) : r.cancelled_by_user_id ? (
                    <span>User #{r.cancelled_by_user_id}</span>
                  ) : (
                    <span className="text-muted-foreground">System</span>
                  )}
                </td>
                <td className="px-3 py-2">
                  {r.order_no ? <p>{r.order_no}</p> : <p className="text-muted-foreground">—</p>}
                  {r.recipient_name ? <p className="text-xs text-muted-foreground">{r.recipient_name}</p> : null}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {!busy && cancelRows.length === 0 ? (
        <p className="text-sm text-muted-foreground text-center py-4">No cancelled deliveries yet.</p>
      ) : null}

      <p className="text-xs text-muted-foreground mt-2">
        {cancelTotal} cancelled order{cancelTotal === 1 ? "" : "s"} · page {cancelPage}
      </p>
      <div className="flex gap-2 mt-2" data-tour="deliveries-cancel-pagination">
        <button
          type="button"
          disabled={cancelPage <= 1}
          className="text-sm"
          onClick={() => setCancelPage((p) => Math.max(1, p - 1))}
        >
          Prev
        </button>
        <button
          type="button"
          disabled={cancelRows.length < 10}
          className="text-sm"
          onClick={() => setCancelPage((p) => p + 1)}
        >
          Next
        </button>
      </div>

      <TrackingQrScannerModal
        open={scannerOpen}
        onClose={() => setScannerOpen(false)}
        title="Scan parcel to cancel"
        titleId="deliveries-cancel-scan-modal-title"
        onDecoded={decoded => void runCancelByTracking(decoded)}
        manualEntryLabel="Or type tracking to cancel"
      />

      <DeliveriesCancelledGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        isExecutive={isExecutive}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runDeliveriesCancelledTour({ isExecutive }));
        }}
      />
    </PageLayout>
  );
}
