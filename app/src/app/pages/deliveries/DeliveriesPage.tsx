import { useCallback, useEffect, useRef, useState, type Dispatch, type SetStateAction } from "react";
import { Camera, CheckCircle2, Loader2, ScanLine } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { DeliveryDuplicateScanModal } from "@/app/components/fulfillment/DeliveryDuplicateScanModal";
import { PageLayout } from "@/app/components/PageLayout";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
  FulfillmentStepSwitch,
  FulfillmentSuccessPanel,
  FulfillmentToast,
} from "@/app/components/csr/onlineFulfillmentMotion";
import {
  deliveriesBatchForDate,
  deliveriesParcelByTracking,
  deliveriesShipParcel,
  deliveriesUploadPhoto,
  type DeliveryExistingRecording,
} from "@/app/lib/deliveriesApi";
import type { OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";
import { compressDeliveryPhoto } from "@/app/lib/compressDeliveryPhoto";

type Step = "idle" | "preview" | "saving" | "done";
type PhotoUploadState = "idle" | "compressing" | "uploading" | "ready" | "error";

function showToast(setter: (v: string | null) => void, message: string, ms = 2800) {
  setter(message);
  window.setTimeout(() => setter(null), ms);
}

function applyBatchCountFromUpload(
  setBatchCount: Dispatch<SetStateAction<number>>,
  batchItemCount: number | undefined,
) {
  if (batchItemCount != null && batchItemCount >= 0) {
    setBatchCount(batchItemCount);
  } else {
    setBatchCount((c) => c + 1);
  }
}

export function DeliveriesPage() {
  const today = new Date().toISOString().slice(0, 10);
  const [deliveryDate, setDeliveryDate] = useState(today);
  const [batchCount, setBatchCount] = useState(0);
  const [step, setStep] = useState<Step>("idle");
  const [tracking, setTracking] = useState("");
  const [parcel, setParcel] = useState<OnlineParcel | null>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [photoUploadState, setPhotoUploadState] = useState<PhotoUploadState>("idle");
  const [deliveryItemId, setDeliveryItemId] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [doneMessage, setDoneMessage] = useState<string | null>(null);
  const [scannerOpen, setScannerOpen] = useState(false);
  const [duplicateOpen, setDuplicateOpen] = useState(false);
  const [duplicateRecordings, setDuplicateRecordings] = useState<DeliveryExistingRecording[]>([]);
  const readerRef = useRef<HTMLDivElement>(null);
  const scannerRef = useRef<{ clear: () => Promise<void> } | null>(null);
  const photoInputRef = useRef<HTMLInputElement>(null);
  const uploadGenRef = useRef(0);
  const uploadPromiseRef = useRef<Promise<number | null> | null>(null);

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

  const uploadPhoto = useCallback(
    async (file: File, tn: string, date: string, gen: number): Promise<number | null> => {
      try {
        setPhotoUploadState("compressing");
        const compressed = await compressDeliveryPhoto(file);
        if (uploadGenRef.current !== gen) return null;

        setPhotoFile(compressed);
        setPhotoUploadState("uploading");
        const res = await deliveriesUploadPhoto(date, compressed, tn);
        if (uploadGenRef.current !== gen) return null;

        const itemId = res.items[0]?.delivery_item_id ?? null;
        if (itemId) {
          setDeliveryItemId(itemId);
          applyBatchCountFromUpload(setBatchCount, res.batch_item_count);
          setPhotoUploadState("ready");
          return itemId;
        }
        setPhotoUploadState("error");
        setError("Photo upload did not return a delivery item.");
        return null;
      } catch (e) {
        if (uploadGenRef.current !== gen) return null;
        setPhotoUploadState("error");
        setError(e instanceof ApiError ? e.message : "Photo upload failed.");
        return null;
      }
    },
    [],
  );

  const startEagerUpload = useCallback(
    (file: File, tn: string) => {
      const gen = ++uploadGenRef.current;
      setDeliveryItemId(null);
      setPhotoUploadState("compressing");
      setError(null);
      const promise = uploadPhoto(file, tn, deliveryDate, gen);
      uploadPromiseRef.current = promise;
      void promise;
    },
    [deliveryDate, uploadPhoto],
  );

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
      if (res.already_recorded && res.existing_recordings.length > 0 && res.parcel.status === "packing") {
        setDuplicateRecordings(res.existing_recordings);
        setDuplicateOpen(true);
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
    setDoneMessage(null);
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
    if (!file || !tracking) return;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoPreview(URL.createObjectURL(file));
    startEagerUpload(file, tracking);
  };

  const ensurePhotoUploaded = async (): Promise<number | null> => {
    if (deliveryItemId) return deliveryItemId;
    if (uploadPromiseRef.current) {
      const itemId = await uploadPromiseRef.current;
      if (itemId) return itemId;
    }
    if (!photoFile || !tracking) {
      setError("Capture a parcel photo first.");
      return null;
    }
    const gen = ++uploadGenRef.current;
    const promise = uploadPhoto(photoFile, tracking, deliveryDate, gen);
    uploadPromiseRef.current = promise;
    return promise;
  };

  const resetFlow = (keepScanning: boolean) => {
    uploadGenRef.current += 1;
    uploadPromiseRef.current = null;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoFile(null);
    setPhotoPreview(null);
    setPhotoUploadState("idle");
    setDeliveryItemId(null);
    setParcel(null);
    setTracking("");
    setDoneMessage(null);
    setStep("idle");
    if (keepScanning) startScanner();
  };

  const finishParcel = async (scanNext: boolean) => {
    if (!parcel || !tracking) return;
    if (!photoFile || !photoPreview) {
      setError("Take a parcel photo before continuing.");
      return;
    }
    setStep("saving");
    setBusy(true);
    setError(null);
    try {
      const itemId = await ensurePhotoUploaded();
      if (!itemId) {
        setStep("preview");
        return;
      }

      const ship = await deliveriesShipParcel(tracking, itemId, deliveryDate);
      const message = ship.already_shipped
        ? "Already shipped — no extra deduct"
        : "Parcel shipped — inventory updated";

      uploadGenRef.current += 1;
      uploadPromiseRef.current = null;
      if (photoPreview) URL.revokeObjectURL(photoPreview);
      setPhotoFile(null);
      setPhotoPreview(null);
      setPhotoUploadState("idle");
      setDeliveryItemId(null);
      setParcel(null);
      setTracking("");

      if (scanNext) {
        setStep("idle");
        showToast(setToast, message);
        startScanner();
      } else {
        setDoneMessage(message);
        setStep("done");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Ship failed.");
      setStep("preview");
    } finally {
      setBusy(false);
    }
  };

  const photoUploading = photoUploadState === "compressing" || photoUploadState === "uploading";
  const savingLabel =
    photoUploadState === "compressing"
      ? "Preparing photo…"
      : photoUploadState === "uploading"
        ? "Saving photo…"
        : "Shipping parcel…";

  return (
    <PageLayout
      title="Deliveries"
      subtitle="Pre-dispatch photos · scan label · ship deducts packaging stock"
    >
      <FulfillmentToast message={toast} />

      <FulfillmentSectionEnter className="max-w-lg space-y-4">
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

        <FulfillmentErrorBanner message={error} />

        <FulfillmentStepSwitch stepKey={step}>
          {step === "idle" && (
            <FulfillmentPressable
              variant="primary"
              className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
              onClick={startScanner}
              disabled={busy}
            >
              <ScanLine className="h-5 w-5" />
              Add deliveries
            </FulfillmentPressable>
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
                  parcel.status === "packing"
                    ? "bg-amber-100 text-amber-900"
                    : parcel.status === "shipped"
                      ? "bg-green-100 text-green-900"
                      : "bg-red-100 text-red-900"
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
                <div className="space-y-2">
                  <img src={photoPreview} alt="Parcel" className="rounded-lg max-h-48 w-full object-cover" />
                  {photoUploading && (
                    <p className="flex items-center gap-2 text-xs text-muted-foreground">
                      <Loader2 className="h-3.5 w-3.5 animate-spin" />
                      {photoUploadState === "compressing" ? "Preparing photo…" : "Saving photo in background…"}
                    </p>
                  )}
                  {photoUploadState === "ready" && (
                    <p className="flex items-center gap-1.5 text-xs text-green-700">
                      <CheckCircle2 className="h-3.5 w-3.5" />
                      Photo saved — tap Done or Scan next when ready
                    </p>
                  )}
                  <button
                    type="button"
                    className="text-xs font-semibold text-[#C8900A]"
                    onClick={() => photoInputRef.current?.click()}
                    disabled={photoUploading || busy}
                  >
                    Retake photo
                  </button>
                  <input
                    ref={photoInputRef}
                    type="file"
                    accept="image/*"
                    capture="environment"
                    className="hidden"
                    onChange={(e) => onPhotoSelected(e.target.files?.[0] ?? null)}
                  />
                </div>
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
                  disabled={busy || !photoPreview || parcel.status !== "packing"}
                >
                  {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Scan next parcel"}
                </button>
                <FulfillmentPressable
                  variant="primary"
                  className="flex-1 rounded-lg py-2 text-sm font-bold"
                  onClick={() => void finishParcel(false)}
                  disabled={busy || !photoPreview || parcel.status !== "packing"}
                >
                  {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Done"}
                </FulfillmentPressable>
              </div>
            </div>
          )}

          {step === "saving" && (
            <div className="flex items-center justify-center gap-2 py-12 text-muted-foreground">
              <Loader2 className="h-6 w-6 animate-spin" />
              <span className="text-sm font-medium">{savingLabel}</span>
            </div>
          )}

          {step === "done" && doneMessage && (
            <FulfillmentSuccessPanel>
              <p className="flex items-center gap-2 font-semibold text-green-800">
                <CheckCircle2 size={20} />
                Parcel recorded
              </p>
              <p className="text-sm text-green-700">{doneMessage}</p>
              <p className="text-sm text-green-700">
                {batchCount} parcel photo{batchCount === 1 ? "" : "s"} saved for {deliveryDate}.
              </p>
              <FulfillmentPressable
                variant="primary"
                className="inline-flex items-center gap-2 px-4 py-2"
                onClick={() => resetFlow(false)}
              >
                <ScanLine size={16} />
                Add more deliveries
              </FulfillmentPressable>
            </FulfillmentSuccessPanel>
          )}
        </FulfillmentStepSwitch>
      </FulfillmentSectionEnter>

      <AppModal
        open={scannerOpen}
        onClose={() => {
          void scannerRef.current?.clear();
          setScannerOpen(false);
        }}
        panelClassName="max-w-md w-full"
        labelledBy="deliveries-scan-modal-title"
        zIndex="z-50"
      >
        <AppModalHeader
          title="Scan label"
          titleId="deliveries-scan-modal-title"
          onClose={() => {
            void scannerRef.current?.clear();
            setScannerOpen(false);
          }}
        />
        <AppModalBody>
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
        </AppModalBody>
      </AppModal>

      <DeliveryDuplicateScanModal
        open={duplicateOpen}
        tracking={tracking}
        recordings={duplicateRecordings}
        onStop={() => {
          setDuplicateOpen(false);
          setDuplicateRecordings([]);
          setParcel(null);
          setTracking("");
          setStep("idle");
        }}
        onScanDifferent={() => {
          setDuplicateOpen(false);
          setDuplicateRecordings([]);
          setParcel(null);
          setTracking("");
          setError(null);
          startScanner();
        }}
      />
    </PageLayout>
  );
}
