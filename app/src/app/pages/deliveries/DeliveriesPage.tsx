import { useCallback, useEffect, useRef, useState } from "react";
import { Camera, CheckCircle2, Loader2, ScanLine } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  deliveriesBatchForDate,
  deliveriesParcelByTracking,
  deliveriesShipParcel,
  deliveriesUploadPhoto,
} from "@/app/lib/deliveriesApi";
import type { OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

type Step = "idle" | "scan" | "preview" | "saving";

export function DeliveriesPage() {
  const today = new Date().toISOString().slice(0, 10);
  const [deliveryDate, setDeliveryDate] = useState(today);
  const [batchCount, setBatchCount] = useState(0);
  const [step, setStep] = useState<Step>("idle");
  const [tracking, setTracking] = useState("");
  const [parcel, setParcel] = useState<OnlineParcel | null>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [deliveryItemId, setDeliveryItemId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [scannerOpen, setScannerOpen] = useState(false);
  const readerRef = useRef<HTMLDivElement>(null);
  const scannerRef = useRef<{ clear: () => Promise<void> } | null>(null);
  const photoInputRef = useRef<HTMLInputElement>(null);

  const refreshBatch = useCallback(async () => {
    const b = await deliveriesBatchForDate(deliveryDate);
    setBatchCount(b.item_count);
  }, [deliveryDate]);

  useEffect(() => {
    void refreshBatch().catch(() => setBatchCount(0));
  }, [refreshBatch]);

  useEffect(() => {
    return () => {
      if (photoPreview) URL.revokeObjectURL(photoPreview);
    };
  }, [photoPreview]);

  const loadParcel = async (raw: string) => {
    const tn = raw.trim();
    if (!tn) return;
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesParcelByTracking(tn);
      setTracking(res.tracking_normalized);
      setParcel(res.parcel);
      if (!res.parcel) {
        setError("No online order parcel for this tracking. Finalize CSR batch first.");
        setStep("idle");
        return;
      }
      if (res.parcel.status === "shipped") {
        setError("This parcel is already shipped.");
      }
      setStep("preview");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Lookup failed.");
      setParcel(null);
      setStep("idle");
    } finally {
      setBusy(false);
    }
  };

  const startScanner = () => {
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
      const Html5QrcodeScanner = (window as unknown as { Html5QrcodeScanner: new (
        id: string,
        config: object,
        verbose: boolean,
      ) => { render: (ok: (t: string) => void) => void; clear: () => Promise<void> } }).Html5QrcodeScanner;
      if (!Html5QrcodeScanner) return;
      const scanner = new Html5QrcodeScanner("deliveries-qr-reader", { fps: 10, qrbox: 220 }, false);
      scannerRef.current = scanner;
      scanner.render((decoded) => {
        void scanner.clear();
        scannerRef.current = null;
        setScannerOpen(false);
        void loadParcel(decoded);
      });
    };
    document.body.appendChild(script);

    return () => {
      cancelled = true;
      void scannerRef.current?.clear();
      scannerRef.current = null;
    };
  }, [scannerOpen]);

  const onPhotoSelected = (file: File | null) => {
    if (!file) return;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoFile(file);
    setPhotoPreview(URL.createObjectURL(file));
  };

  const savePhoto = async (): Promise<number | null> => {
    if (!photoFile || !tracking) {
      setError("Capture a parcel photo first.");
      return null;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesUploadPhoto(deliveryDate, photoFile, tracking);
      const itemId = res.items[0]?.delivery_item_id ?? null;
      setDeliveryItemId(itemId);
      await refreshBatch();
      return itemId;
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Photo upload failed.");
      return null;
    } finally {
      setBusy(false);
    }
  };

  const finishParcel = async (scanNext: boolean) => {
    if (!parcel || !tracking) return;
    let itemId = deliveryItemId;
    if (!itemId) {
      itemId = await savePhoto();
      if (!itemId) return;
    }
    setBusy(true);
    setError(null);
    try {
      const ship = await deliveriesShipParcel(tracking, itemId, deliveryDate);
      setToast(
        ship.already_shipped
          ? "Already shipped — no extra deduct"
          : "Parcel shipped — inventory updated",
      );
      window.setTimeout(() => setToast(null), 3000);
      resetFlow(scanNext);
      await refreshBatch();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Ship failed.");
    } finally {
      setBusy(false);
    }
  };

  const resetFlow = (keepScanning: boolean) => {
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoFile(null);
    setPhotoPreview(null);
    setDeliveryItemId(null);
    setParcel(null);
    setTracking("");
    setStep(keepScanning ? "scan" : "idle");
    if (keepScanning) startScanner();
  };

  return (
    <PageLayout
      title="Deliveries"
      subtitle="Pre-dispatch photos · scan label · ship deducts packaging stock"
    >
      <div className="max-w-lg space-y-4">
        <label className="block text-sm text-muted-foreground">
          Business date
          <input
            type="date"
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card"
            value={deliveryDate}
            onChange={(e) => setDeliveryDate(e.target.value)}
          />
        </label>
        <p className="text-sm text-muted-foreground">
          {batchCount} parcel photo{batchCount === 1 ? "" : "s"} saved for this date.
        </p>

        {error && (
          <div className="rounded-lg border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {error}
          </div>
        )}

        {step === "idle" && (
          <button
            type="button"
            className="w-full flex items-center justify-center gap-2 rounded-xl bg-primary py-3 font-bold text-primary-foreground"
            onClick={startScanner}
            disabled={busy}
          >
            <ScanLine className="h-5 w-5" />
            Add deliveries
          </button>
        )}

        {scannerOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4">
            <div className="w-full max-w-md rounded-2xl bg-card border border-border p-4 shadow-xl">
              <div className="flex justify-between items-center mb-2">
                <h3 className="font-semibold">Scan label</h3>
                <button
                  type="button"
                  className="text-muted-foreground text-xl"
                  onClick={() => {
                    void scannerRef.current?.clear();
                    setScannerOpen(false);
                  }}
                >
                  ×
                </button>
              </div>
              <div id="deliveries-qr-reader" ref={readerRef} />
              <label className="block mt-3 text-sm text-muted-foreground">
                Or enter tracking manually
                <input
                  className="mt-1 w-full rounded-lg border border-border px-3 py-2"
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      const v = (e.target as HTMLInputElement).value;
                      setScannerOpen(false);
                      void loadParcel(v);
                    }
                  }}
                />
              </label>
            </div>
          </div>
        )}

        {step === "preview" && parcel && (
          <div className="rounded-2xl border border-border bg-card p-4 space-y-3">
            <div>
              <p className="text-xs text-muted-foreground">Tracking</p>
              <p className="font-mono font-semibold text-lg">{tracking}</p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Contents</p>
              <p className="text-sm whitespace-pre-wrap">{parcel.items_raw}</p>
            </div>
            <span
              className={`inline-block text-xs font-semibold px-2 py-0.5 rounded ${
                parcel.status === "packing" ? "bg-amber-100 text-amber-900" : "bg-green-100 text-green-900"
              }`}
            >
              {parcel.status}
            </span>

            {!photoPreview ? (
              <>
                <p className="text-sm text-muted-foreground">Photograph the wrapped parcel (required).</p>
                <input
                  ref={photoInputRef}
                  type="file"
                  accept="image/*"
                  capture="environment"
                  className="hidden"
                  onChange={(e) => onPhotoSelected(e.target.files?.[0] ?? null)}
                />
                <button
                  type="button"
                  className="w-full flex items-center justify-center gap-2 rounded-xl border border-border py-3 font-semibold"
                  onClick={() => photoInputRef.current?.click()}
                >
                  <Camera className="h-5 w-5" />
                  Take parcel photo
                </button>
              </>
            ) : (
              <img src={photoPreview} alt="Parcel" className="rounded-lg max-h-48 w-full object-cover" />
            )}

            <div className="flex flex-wrap gap-2 pt-2">
              <button
                type="button"
                className="flex-1 rounded-lg border border-border py-2 text-sm"
                onClick={() => resetFlow(false)}
                disabled={busy}
              >
                Cancel
              </button>
              <button
                type="button"
                className="flex-1 rounded-lg border border-border py-2 text-sm font-semibold"
                onClick={() => void finishParcel(true)}
                disabled={busy || !photoPreview}
              >
                {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Scan next parcel"}
              </button>
              <button
                type="button"
                className="flex-1 rounded-lg bg-primary py-2 text-sm font-bold text-primary-foreground"
                onClick={() => void finishParcel(false)}
                disabled={busy || !photoPreview}
              >
                Done
              </button>
            </div>
          </div>
        )}
      </div>

      {toast && <OrderRemarksToast message={toast} />}
      {!toast && busy && step === "saving" && (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/30">
          <CheckCircle2 className="h-10 w-10 text-primary animate-pulse" />
        </div>
      )}
    </PageLayout>
  );
}
