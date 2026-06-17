import { useCallback, useEffect, useRef, useState } from "react";
import { CheckCircle2, Loader2, ScanLine } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSuccessPanel,
  FulfillmentToast,
} from "@/app/components/csr/onlineFulfillmentMotion";
import {
  deliveriesHoldParcel,
  deliveriesParcelByTracking,
  deliveriesRewaybill,
} from "@/app/lib/deliveriesApi";
import type { OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

type OpsMode = "hold" | "rewaybill";
type ScanIntent = "hold" | "rewaybill_old" | "rewaybill_new";
type RewaybillStep = "scan_old" | "scan_new" | "done";

interface DeliveriesOpsPanelProps {
  mode: OpsMode;
  /** After re-waybill, switch to Ship tab and open scanner for the new label. */
  onContinueToShip?: () => void;
}

export function DeliveriesOpsPanel({ mode, onContinueToShip }: DeliveriesOpsPanelProps) {
  const [oldTracking, setOldTracking] = useState("");
  const [newTracking, setNewTracking] = useState("");
  const [parcel, setParcel] = useState<OnlineParcel | null>(null);
  const [rewaybillStep, setRewaybillStep] = useState<RewaybillStep>("scan_old");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [scannerOpen, setScannerOpen] = useState(false);
  const readerRef = useRef<HTMLDivElement>(null);
  const scannerRef = useRef<{ clear: () => Promise<void> } | null>(null);

  const flash = (msg: string) => {
    setToast(msg);
    window.setTimeout(() => setToast(null), 3200);
  };

  const resetRewaybill = () => {
    setOldTracking("");
    setNewTracking("");
    setParcel(null);
    setRewaybillStep("scan_old");
    setError(null);
  };

  const handleOldScan = async (raw: string) => {
    const tn = raw.trim();
    if (!tn) return;
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesParcelByTracking(tn);
      if (!res.parcel) {
        setError("No parcel found for this tracking.");
        setParcel(null);
        return;
      }
      const normalized = res.tracking_normalized || tn;
      setOldTracking(normalized);
      setParcel(res.parcel);
      if (res.parcel.status !== "on_hold") {
        setError(`Parcel is "${res.parcel.status}" — mark it on hold first, then re-waybill.`);
        return;
      }
      setRewaybillStep("scan_new");
      flash("Old label OK — scan the new waybill.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Lookup failed.");
      setParcel(null);
    } finally {
      setBusy(false);
    }
  };

  const handleNewScan = async (raw: string) => {
    const newTn = raw.trim();
    const oldTn = oldTracking.trim();
    if (!newTn || !oldTn) {
      setError("Scan the old waybill first.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesRewaybill(oldTn, newTn);
      setParcel(res.parcel);
      setNewTracking(res.parcel.tracking_number);
      setRewaybillStep("done");
      flash(res.message ?? "Re-waybill linked.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Re-waybill failed.");
    } finally {
      setBusy(false);
    }
  };

  const handleHoldScan = async (raw: string) => {
    const tn = raw.trim();
    if (!tn) return;
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesHoldParcel(tn);
      setParcel(res.parcel);
      setOldTracking(res.parcel.tracking_number);
      flash(res.message ?? "On hold.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Hold failed.");
    } finally {
      setBusy(false);
    }
  };

  const onScanDecoded = useCallback(
    (decoded: string) => {
      void scannerRef.current?.clear();
      scannerRef.current = null;
      setScannerOpen(false);

      if (mode === "hold") {
        void handleHoldScan(decoded);
        return;
      }
      if (rewaybillStep === "scan_old") {
        void handleOldScan(decoded);
      } else if (rewaybillStep === "scan_new") {
        void handleNewScan(decoded);
      }
    },
    [mode, rewaybillStep, oldTracking],
  );

  const openScanner = (_intent: ScanIntent) => {
    setScannerOpen(true);
    setError(null);
  };

  useEffect(() => {
    if (!scannerOpen || !readerRef.current) return;

    let cancelled = false;
    const script = document.createElement("script");
    script.src = "https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js";
    script.async = true;
    script.onload = () => {
      if (cancelled || !readerRef.current) return;
      const Html5QrcodeScanner = (window as unknown as {
        Html5QrcodeScanner: new (
          id: string,
          config: object,
          verbose: boolean,
        ) => { render: (ok: (t: string) => void) => void; clear: () => Promise<void> };
      }).Html5QrcodeScanner;
      if (!Html5QrcodeScanner) return;
      const scanner = new Html5QrcodeScanner("deliveries-ops-qr-reader", { fps: 10, qrbox: 220 }, false);
      scannerRef.current = scanner;
      scanner.render((decoded) => {
        onScanDecoded(decoded);
      });
    };
    document.body.appendChild(script);

    return () => {
      cancelled = true;
      void scannerRef.current?.clear();
      scannerRef.current = null;
    };
  }, [scannerOpen, onScanDecoded]);

  const scannerTitle =
    mode === "hold"
      ? "Scan parcel to mark on hold"
      : rewaybillStep === "scan_old"
        ? "Scan old waybill"
        : "Scan new waybill";

  const manualSubmit = (value: string) => {
    setScannerOpen(false);
    onScanDecoded(value);
  };

  return (
    <div className="max-w-lg space-y-4">
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={error} />

      {mode === "hold" && (
        <>
          <p className="text-sm text-muted-foreground">
            Scan the label on the sealed box. Inventory stays deducted — CSR prints a replacement waybill next.
          </p>
          <FulfillmentPressable
            variant="primary"
            className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
            onClick={() => openScanner("hold")}
            disabled={busy}
          >
            {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
            Scan to mark on hold
          </FulfillmentPressable>
        </>
      )}

      {mode === "rewaybill" && rewaybillStep !== "done" && (
        <>
          <p className="text-sm text-muted-foreground">
            Scan the <strong className="text-foreground">old</strong> label on the box, then the{" "}
            <strong className="text-foreground">new</strong> waybill from CSR. Then ship again on the Ship tab.
          </p>
          <ol className="text-xs text-muted-foreground space-y-1 list-decimal list-inside">
            <li className={rewaybillStep === "scan_old" ? "text-foreground font-semibold" : ""}>
              Scan old waybill
            </li>
            <li className={rewaybillStep === "scan_new" ? "text-foreground font-semibold" : ""}>
              Scan new waybill
            </li>
            <li>Ship — scan new label again + photo</li>
          </ol>

          {rewaybillStep === "scan_old" ? (
            <FulfillmentPressable
              variant="primary"
              className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
              onClick={() => openScanner("rewaybill_old")}
              disabled={busy}
            >
              {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
              Scan old waybill
            </FulfillmentPressable>
          ) : (
            <div className="space-y-3">
              <div className="rounded-xl border border-border bg-secondary/20 px-3 py-2 text-sm">
                <p className="text-xs text-muted-foreground">Old tracking</p>
                <p className="font-mono font-semibold">{oldTracking}</p>
              </div>
              <FulfillmentPressable
                variant="primary"
                className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
                onClick={() => openScanner("rewaybill_new")}
                disabled={busy}
              >
                {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
                Scan new waybill
              </FulfillmentPressable>
              <button
                type="button"
                className="text-xs text-muted-foreground underline"
                onClick={resetRewaybill}
              >
                Start over
              </button>
            </div>
          )}
        </>
      )}

      {mode === "rewaybill" && rewaybillStep === "done" && parcel && (
        <FulfillmentSuccessPanel className="space-y-3">
          <div className="flex items-center gap-2 text-green-800">
            <CheckCircle2 className="h-5 w-5 shrink-0" />
            <p className="font-semibold">Re-waybill linked</p>
          </div>
          <p className="text-sm text-green-800">
            New tracking: <span className="font-mono font-bold">{parcel.tracking_number}</span>
          </p>
          <p className="text-sm text-green-700 whitespace-pre-wrap">{parcel.items_raw}</p>
          <div className="flex flex-wrap gap-2 pt-1">
            <FulfillmentPressable
              variant="primary"
              className="inline-flex items-center gap-2 px-4 py-2.5 font-bold"
              onClick={() => onContinueToShip?.()}
            >
              <ScanLine className="h-4 w-4" />
              Scan to ship
            </FulfillmentPressable>
            <FulfillmentPressable
              variant="secondary"
              className="px-4 py-2.5 text-sm"
              onClick={resetRewaybill}
            >
              Another re-waybill
            </FulfillmentPressable>
          </div>
        </FulfillmentSuccessPanel>
      )}

      {parcel && mode === "hold" && (
        <div className="rounded-2xl border border-border bg-card p-4 space-y-2 text-sm">
          <p className="font-mono font-semibold">{parcel.tracking_number}</p>
          <p className="whitespace-pre-wrap text-muted-foreground">{parcel.items_raw}</p>
          <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded bg-secondary capitalize">
            {parcel.status.replace(/_/g, " ")}
          </span>
        </div>
      )}

      {parcel && mode === "rewaybill" && rewaybillStep === "scan_new" && (
        <div className="rounded-2xl border border-border bg-card p-4 space-y-2 text-sm">
          <p className="text-xs text-muted-foreground">On-hold parcel</p>
          <p className="whitespace-pre-wrap text-muted-foreground">{parcel.items_raw}</p>
        </div>
      )}

      <AppModal
        open={scannerOpen}
        onClose={() => {
          void scannerRef.current?.clear();
          setScannerOpen(false);
        }}
        panelClassName="max-w-md w-full"
        labelledBy="deliveries-ops-scan-title"
        zIndex="z-50"
      >
        <AppModalHeader
          title={scannerTitle}
          titleId="deliveries-ops-scan-title"
          onClose={() => {
            void scannerRef.current?.clear();
            setScannerOpen(false);
          }}
        />
        <AppModalBody>
          <div id="deliveries-ops-qr-reader" ref={readerRef} />
          <label className="block mt-3 text-sm text-muted-foreground">
            Or type tracking manually
            <input
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 font-mono"
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  const v = (e.target as HTMLInputElement).value;
                  manualSubmit(v);
                }
              }}
            />
          </label>
        </AppModalBody>
      </AppModal>
    </div>
  );
}
