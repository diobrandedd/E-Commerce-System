import { useCallback, useEffect, useState } from "react";
import { CheckCircle2, Download, FileSpreadsheet, Loader2 } from "lucide-react";
import { FileZone } from "@/app/components/csr/FileZone";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
} from "@/app/components/csr/onlineFulfillmentMotion";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  onlineOrdersToolVipArrangerGet,
  onlineOrdersToolVipMerge,
} from "@/app/lib/onlineOrdersApi";
import type { VipMergeResult } from "@/app/lib/onlineOrdersTypes";
import {
  orderRemarksUploadTooLarge,
  stashOrderRemarksFile,
} from "@/app/lib/orderRemarksApi";
import { triggerWaybillStashDownload } from "@/app/lib/waybillApi";
import { ApiError } from "@/app/lib/api";
import { OnlineOrdersBackToToolsButton } from "@/app/components/csr/onlineOrders/OnlineOrdersNavButtons";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";

const VIP_ARRANGER_TOKEN_KEY = "vip_arranger_token";

export function VipMergeTool({
  onDone,
  onBack,
}: {
  onDone: () => void;
  onBack: () => void;
}) {
  const [trackingFile, setTrackingFile] = useState<File | null>(null);
  const [arrangerToken, setArrangerToken] = useState<string | null>(null);
  const [arrangerCount, setArrangerCount] = useState(0);
  const [busy, setBusy] = useState(false);
  const [loadingArranger, setLoadingArranger] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [mergeResult, setMergeResult] = useState<VipMergeResult | null>(null);

  const loadArranger = useCallback(async () => {
    setLoadingArranger(true);
    setError(null);
    try {
      const stored = sessionStorage.getItem(VIP_ARRANGER_TOKEN_KEY);
      const res = await onlineOrdersToolVipArrangerGet(stored ?? undefined);
      if (res.arranger_token && res.row_count > 0) {
        setArrangerToken(res.arranger_token);
        setArrangerCount(res.row_count);
      } else {
        setArrangerToken(null);
        setArrangerCount(0);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load arranged rows.");
    } finally {
      setLoadingArranger(false);
    }
  }, []);

  useEffect(() => {
    void loadArranger();
  }, [loadArranger]);

  const runMerge = async () => {
    if (!trackingFile) {
      setError("Upload the courier tracking export first.");
      return;
    }
    if (!arrangerToken) {
      setError("No arranged rows found. Run VIP Excel arranger first.");
      return;
    }
    setBusy(true);
    setError(null);
    setMergeResult(null);
    const sizeErr = orderRemarksUploadTooLarge(trackingFile);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const trackingToken = await stashOrderRemarksFile(trackingFile);
      const result = await onlineOrdersToolVipMerge({
        arranger_token: arrangerToken,
        tracking_excel_token: trackingToken,
      });
      setMergeResult(result);
      const unmatched = result.unmatched.length;
      setToast(
        unmatched > 0
          ? `${result.matched} matched, ${unmatched} unmatched — review below`
          : `${result.matched} rows merged — iPYX ready`,
      );
      window.setTimeout(() => setToast(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Merge failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <OnlineOrdersBackToToolsButton onClick={onBack} />
      <p className="text-sm text-muted-foreground">
        Attach waybill tracking from the courier export. We match{" "}
        <strong className="font-semibold text-foreground">Parcel Name</strong> to{" "}
        <strong className="font-semibold text-foreground">Order Number</strong> (J&amp;T, strips PKE) or{" "}
        <strong className="font-semibold text-foreground">Customer Reference No.</strong> (SPX).
      </p>

      {loadingArranger ? (
        <p className="text-sm text-muted-foreground flex items-center gap-2">
          <Loader2 size={16} className="animate-spin" /> Checking arranged rows…
        </p>
      ) : arrangerToken ? (
        <p className="text-sm rounded-lg border border-border bg-secondary/30 px-3 py-2">
          <span className="font-semibold text-foreground">{arrangerCount} arranged row{arrangerCount === 1 ? "" : "s"}</span>{" "}
          ready to merge
        </p>
      ) : (
        <AppErrorBanner message="No arranged rows in this session. Open VIP Excel arranger first, save arranged rows, then return here." />
      )}

      <div data-tour="online-orders-vip-merge-upload">
        <FileZone
          label="Courier tracking export"
          hint="J&T (Order Number + Waybill) or SPX (Customer Reference + Tracking No.)"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={trackingFile ? [trackingFile] : []}
          disabled={busy || !arrangerToken}
          multiple={false}
          onFiles={fs => {
            setTrackingFile(fs[0] ?? null);
            setMergeResult(null);
          }}
        />
      </div>

      {trackingFile && arrangerToken && !mergeResult && (
        <FulfillmentPressable
          className="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-primary-foreground bg-primary"
          disabled={busy}
          onClick={() => void runMerge()}
          data-tour="online-orders-vip-merge-run"
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <Download size={16} />}
          Merge tracking → iPYX Excel
        </FulfillmentPressable>
      )}

      {mergeResult && (
        <div className="rounded-xl border border-green-200 bg-green-50/80 p-5 space-y-4" data-tour="online-orders-vip-merge-done">
          <p className="text-sm text-green-800 flex items-center gap-2 font-medium">
            <CheckCircle2 size={18} />
            {mergeResult.matched} of {mergeResult.matched + mergeResult.unmatched.length} rows matched
            {mergeResult.tracking_profile_label ? ` (${mergeResult.tracking_profile_label})` : ""}
          </p>
          {mergeResult.unmatched.length > 0 && (
            <div className="rounded-lg border border-amber-200 bg-amber-50/80 p-3 space-y-2">
              <p className="text-xs font-semibold text-amber-900">Unmatched — not in iPYX export</p>
              <ul className="text-xs text-amber-950 space-y-1 max-h-40 overflow-y-auto">
                {mergeResult.unmatched.map(row => (
                  <li key={`${row.parcel_ref}-${row.receiver}`}>
                    <span className="font-mono">{row.parcel_ref}</span> · {row.receiver}
                  </li>
                ))}
              </ul>
            </div>
          )}
          <p className="text-sm text-muted-foreground">
            Download the iPYX sheet, then open PDF + Excel stamper when waybills are ready.
          </p>
          <div className="flex flex-wrap gap-3">
            <FulfillmentPressable
              variant="outline"
              className="inline-flex items-center gap-2 px-4 py-2.5"
              onClick={() => triggerWaybillStashDownload(mergeResult.excel_token)}
            >
              <Download size={16} /> Download iPYX Excel
            </FulfillmentPressable>
            <OnlineOrdersBackToToolsButton onClick={onDone} />
          </div>
        </div>
      )}

      <FulfillmentErrorBanner message={error} />
    </FulfillmentSectionEnter>
  );
}

export { VIP_ARRANGER_TOKEN_KEY };
