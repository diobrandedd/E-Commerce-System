import { useCallback, useEffect, useRef, useState, type Dispatch, type SetStateAction } from "react";
import { BookOpen, Camera, CheckCircle2, Loader2, ScanLine } from "lucide-react";
import { Link } from "react-router";
import { TrackingQrScannerModal } from "@/app/components/scan/TrackingQrScannerModal";
import { usePreloadQrScanner } from "@/app/hooks/usePreloadQrScanner";
import { DeliveryDuplicateScanModal } from "@/app/components/fulfillment/DeliveryDuplicateScanModal";
import { DeliveriesAddGuideModal } from "@/app/components/fulfillment/DeliveriesAddGuideModal";
import { runDeliveriesAddTour } from "@/app/components/fulfillment/deliveriesAddTour";
import { DeliveriesOpsPanel } from "@/app/components/fulfillment/DeliveriesOpsPanel";
import { PageLayout } from "@/app/components/PageLayout";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
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
  deliveriesUploadPhoto,
  type DeliveryExistingRecording,
} from "@/app/lib/deliveriesApi";
import { ApiError } from "@/app/lib/api";
import { compressDeliveryPhoto } from "@/app/lib/compressDeliveryPhoto";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useAuth } from "@/app/context/AuthContext";
import { cn } from "@/app/components/ui/utils";

type Step = "idle" | "preview" | "saving" | "done";
type PhotoUploadState = "idle" | "compressing" | "uploading" | "ready" | "error";
type DeliveryMode = "ship" | "hold" | "rewaybill";

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
    setBatchCount(c => c + 1);
  }
}

export function DeliveriesPage() {
  usePreloadQrScanner();
  const { can } = useAuth();
  const isExecutive = can("deliveries.delete");
  const today = new Date().toISOString().slice(0, 10);
  const [mode, setMode] = useState<DeliveryMode>("ship");
  const [deliveryDate, setDeliveryDate] = useState(today);
  const [batchCount, setBatchCount] = useState(0);
  const [step, setStep] = useState<Step>("idle");
  const [tracking, setTracking] = useState("");
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
  const [guideOpen, setGuideOpen] = useState(false);
  const photoInputRef = useRef<HTMLInputElement>(null);
  const autoPhotoForTnRef = useRef<string | null>(null);
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

  /** After waybill scan → preview, open parcel camera once per TN (wait for QR modal to release camera). */
  useEffect(() => {
    if (mode !== "ship" || step !== "preview" || !tracking || photoPreview || scannerOpen) return;
    if (autoPhotoForTnRef.current === tracking) return;
    autoPhotoForTnRef.current = tracking;
    const id = window.setTimeout(() => {
      photoInputRef.current?.click();
    }, 220);
    return () => window.clearTimeout(id);
  }, [mode, step, tracking, photoPreview, scannerOpen]);

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

  const enterPreview = (tn: string) => {
    setTracking(tn);
    setError(null);
    playScanBeep("ok");
    setStep("preview");
    setScannerOpen(false);
  };

  const beginTracking = async (raw: string) => {
    const tn = raw.trim();
    if (!tn) return;
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesParcelByTracking(tn);
      const normalized = res.tracking_normalized || tn;
      setTracking(normalized);
      if (res.already_recorded && res.existing_recordings.length > 0) {
        playScanBeep("dup");
        setDuplicateRecordings(res.existing_recordings);
        setDuplicateOpen(true);
        setStep("idle");
        setScannerOpen(false);
        return;
      }
      enterPreview(normalized);
    } catch (e) {
      playScanBeep("ok");
      setTracking(tn);
      setStep("preview");
      setScannerOpen(false);
      setError(e instanceof ApiError ? `${e.message} — you can still save a photo.` : null);
    } finally {
      setBusy(false);
    }
  };

  const startScanner = () => {
    setScannerOpen(true);
    setError(null);
    setDoneMessage(null);
  };

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
      setError("Capture a before-shipment photo first.");
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
    autoPhotoForTnRef.current = null;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoFile(null);
    setPhotoPreview(null);
    setPhotoUploadState("idle");
    setDeliveryItemId(null);
    setTracking("");
    setDoneMessage(null);
    setError(null);
    setStep("idle");
    if (keepScanning) startScanner();
  };

  const selectMode = (next: DeliveryMode) => {
    if (next === mode) return;
    setMode(next);
    setScannerOpen(false);
    setDuplicateOpen(false);
    if (mode === "ship" || next === "ship") {
      resetFlow(false);
    }
  };

  const finishPhoto = async (scanNext: boolean) => {
    if (!tracking) return;
    if (!photoFile || !photoPreview) {
      setError("Take a before-shipment photo before continuing.");
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

      const message = "Before-shipment photo saved for RTS";

      uploadGenRef.current += 1;
      uploadPromiseRef.current = null;
      if (photoPreview) URL.revokeObjectURL(photoPreview);
      setPhotoFile(null);
      setPhotoPreview(null);
      setPhotoUploadState("idle");
      setDeliveryItemId(null);
      setTracking("");
      autoPhotoForTnRef.current = null;

      if (scanNext) {
        setStep("idle");
        showToast(setToast, message);
        startScanner();
      } else {
        setDoneMessage(message);
        setStep("done");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save photo.");
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
        : "Saving…";

  const photoCountLabel =
    batchCount === 0
      ? "No photos yet for this date."
      : `${batchCount} before-shipment photo${batchCount === 1 ? "" : "s"} saved for this date.`;

  return (
    <PageLayout
      title="Deliveries"
      subtitle={
        mode === "ship"
          ? "Scan tracking · save before-shipment photo · no inventory change"
          : mode === "hold"
            ? "Sealed box, wrong label — not the same as Cancelled"
            : "Old QR → new waybill → photo (parcel tools)"
      }
      actions={
        <div className="flex items-center gap-2 flex-wrap justify-end">
          <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
            <BookOpen size={13} /> Guide
          </button>
          <Link to="/deliveries/review" className={cn(appHeaderActionClass, "sm:hidden")}>
            Review
          </Link>
          <Link to="/deliveries/review" className={cn(appHeaderActionClass, "hidden sm:inline-flex")}>
            Review batches
          </Link>
          <Link to="/deliveries/cancelled" className={cn(appHeaderActionClass, "hidden sm:inline-flex")}>
            Cancelled
          </Link>
        </div>
      }
    >
      <div
        className="inline-flex w-full max-w-lg rounded-xl border border-border bg-card p-1 gap-1 mb-8"
        role="tablist"
        aria-label="Deliveries mode"
        data-tour="deliveries-add-tabs"
      >
        {(
          [
            ["ship", "Photo"],
            ["hold", "On hold"],
            ["rewaybill", "Re-waybill"],
          ] as const
        ).map(([id, label]) => {
          const active = mode === id;
          return (
            <button
              key={id}
              type="button"
              role="tab"
              aria-selected={active}
              onClick={() => selectMode(id)}
              className={cn(
                "flex-1 min-h-11 rounded-lg px-3 text-sm font-semibold transition-colors",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2",
                active
                  ? "bg-primary text-primary-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground hover:bg-secondary/50",
              )}
            >
              {label}
            </button>
          );
        })}
      </div>

      {(mode === "rewaybill" || mode === "ship") && (
        <div className="max-w-lg mb-6 space-y-1.5" data-tour="deliveries-add-date">
          <label htmlFor="deliveries-business-date" className="block text-sm font-medium text-foreground">
            Business date
          </label>
          <input
            id="deliveries-business-date"
            type="date"
            className={cn(
              "w-full min-h-11 rounded-xl border border-border bg-card px-3 py-2 text-sm",
              "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-primary",
            )}
            value={deliveryDate}
            onChange={e => setDeliveryDate(e.target.value)}
          />
          {mode === "ship" ? (
            <p className="text-sm text-muted-foreground pt-1" aria-live="polite">
              {photoCountLabel}
            </p>
          ) : null}
        </div>
      )}

      {mode !== "ship" ? (
        <div data-tour="deliveries-ops-panel">
          <DeliveriesOpsPanel
            key={mode}
            mode={mode}
            deliveryDate={deliveryDate}
            onBatchCountChange={setBatchCount}
          />
        </div>
      ) : (
        <>
          <FulfillmentToast message={toast} />

          <input
            ref={photoInputRef}
            type="file"
            accept="image/*"
            capture="environment"
            className="hidden"
            data-testid="deliveries-photo-capture-input"
            onChange={e => {
              onPhotoSelected(e.target.files?.[0] ?? null);
              e.target.value = "";
            }}
          />

          <FulfillmentSectionEnter className="max-w-lg space-y-5">
            <FulfillmentErrorBanner message={error} />

            <FulfillmentStepSwitch stepKey={step}>
              {step === "idle" && (
                <div data-tour="deliveries-add-ship-scan" className="space-y-3">
                  <FulfillmentPressable
                    variant="primary"
                    className="w-full min-h-12 flex items-center justify-center gap-2 rounded-xl py-3.5 text-base font-bold"
                    onClick={startScanner}
                    disabled={busy}
                  >
                    {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
                    Scan tracking
                  </FulfillmentPressable>
                  <p className="text-sm text-muted-foreground text-center leading-relaxed px-1">
                    CSR waybill list not required. Photo is for RTS before-shipment reference only.
                  </p>
                </div>
              )}

              {step === "preview" && tracking && (
                <div
                  className="rounded-2xl border border-border bg-card p-4 sm:p-5 space-y-5"
                  data-testid="deliveries-photo-preview"
                >
                  <div className="space-y-1">
                    <p className="text-sm font-semibold text-foreground">Tracking</p>
                    <p className="font-mono font-semibold text-lg tracking-wide break-all">{tracking}</p>
                    <p className="text-sm text-muted-foreground">
                      Documentary only — no stock or packaging change.
                    </p>
                  </div>

                  {!photoPreview ? (
                    <div className="space-y-3">
                      <p className="text-sm text-muted-foreground leading-relaxed">
                        Photograph the wrapped parcel. This image shows on RTS when that tracking returns.
                      </p>
                      <FulfillmentPressable
                        variant="outline"
                        className="w-full min-h-12 flex items-center justify-center gap-2 rounded-xl py-3.5 font-semibold"
                        onClick={() => photoInputRef.current?.click()}
                      >
                        <Camera className="h-5 w-5" />
                        Take before-shipment photo
                      </FulfillmentPressable>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      <img
                        src={photoPreview}
                        alt="Before shipment"
                        className="rounded-xl max-h-56 w-full object-cover bg-secondary/30"
                      />
                      {photoUploading && (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                          <Loader2 className="h-4 w-4 animate-spin shrink-0" />
                          {photoUploadState === "compressing"
                            ? "Preparing photo…"
                            : "Saving photo in background…"}
                        </p>
                      )}
                      {photoUploadState === "ready" && (
                        <p className="flex items-center gap-1.5 text-sm font-medium text-green-800">
                          <CheckCircle2 className="h-4 w-4 shrink-0" />
                          Photo saved — tap Done or Scan next
                        </p>
                      )}
                      {photoUploadState === "error" && (
                        <p className="text-sm text-destructive">Upload failed — retake or try again.</p>
                      )}
                      <button
                        type="button"
                        className="text-sm font-semibold text-primary hover:underline min-h-11 px-1 -ml-1 disabled:opacity-50"
                        onClick={() => photoInputRef.current?.click()}
                        disabled={photoUploading || busy}
                      >
                        Retake photo
                      </button>
                    </div>
                  )}

                  <div className="flex flex-col-reverse sm:flex-row gap-2 pt-1">
                    <FulfillmentPressable
                      variant="outline"
                      className="flex-1 min-h-11 rounded-xl py-2.5 text-sm font-semibold"
                      onClick={() => resetFlow(false)}
                      disabled={busy}
                    >
                      Cancel
                    </FulfillmentPressable>
                    <FulfillmentPressable
                      variant="outline"
                      className="flex-1 min-h-11 rounded-xl py-2.5 text-sm font-semibold"
                      onClick={() => void finishPhoto(true)}
                      disabled={busy || !photoPreview || photoUploading}
                    >
                      {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Scan next"}
                    </FulfillmentPressable>
                    <FulfillmentPressable
                      variant="primary"
                      className="flex-1 min-h-11 rounded-xl py-2.5 text-sm font-bold"
                      onClick={() => void finishPhoto(false)}
                      disabled={busy || !photoPreview || photoUploading}
                    >
                      {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Done"}
                    </FulfillmentPressable>
                  </div>
                </div>
              )}

              {step === "saving" && (
                <div className="flex items-center justify-center gap-2 py-14 text-muted-foreground">
                  <Loader2 className="h-6 w-6 animate-spin" />
                  <span className="text-sm font-medium">{savingLabel}</span>
                </div>
              )}

              {step === "done" && doneMessage && (
                <FulfillmentSuccessPanel>
                  <p className="flex items-center gap-2 font-semibold text-green-800">
                    <CheckCircle2 size={20} />
                    Photo recorded
                  </p>
                  <p className="text-sm text-green-800/90">{doneMessage}</p>
                  <p className="text-sm text-green-800/90">
                    {batchCount === 0
                      ? `Ready for ${deliveryDate}.`
                      : `${batchCount} photo${batchCount === 1 ? "" : "s"} for ${deliveryDate}.`}
                  </p>
                  <FulfillmentPressable
                    variant="primary"
                    className="inline-flex items-center gap-2 min-h-11 px-4 py-2.5"
                    onClick={() => resetFlow(false)}
                  >
                    <ScanLine size={16} />
                    Scan another
                  </FulfillmentPressable>
                </FulfillmentSuccessPanel>
              )}
            </FulfillmentStepSwitch>
          </FulfillmentSectionEnter>

          {scannerOpen ? (
            <TrackingQrScannerModal
              onClose={() => setScannerOpen(false)}
              title="Scan tracking"
              titleId="deliveries-scan-modal-title"
              onDecoded={decoded => void beginTracking(decoded)}
              zIndex="z-50"
            />
          ) : null}

          <DeliveryDuplicateScanModal
            open={duplicateOpen}
            tracking={tracking}
            recordings={duplicateRecordings}
            onStop={() => {
              setDuplicateOpen(false);
              setDuplicateRecordings([]);
              setTracking("");
              autoPhotoForTnRef.current = null;
              setStep("idle");
            }}
            onScanDifferent={() => {
              setDuplicateOpen(false);
              setDuplicateRecordings([]);
              setTracking("");
              autoPhotoForTnRef.current = null;
              setError(null);
              startScanner();
            }}
            onContinueAnyway={() => {
              setDuplicateOpen(false);
              setDuplicateRecordings([]);
              if (tracking) enterPreview(tracking);
            }}
          />
        </>
      )}

      <DeliveriesAddGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        isExecutive={isExecutive}
        onStartTour={() => {
          setGuideOpen(false);
          setMode("ship");
          setStep("idle");
          startPageTourAfterModalClose(() => runDeliveriesAddTour({ isExecutive }));
        }}
      />
    </PageLayout>
  );
}
