import { useCallback, useEffect, useRef, useState, type Dispatch, type SetStateAction } from "react";
import { Camera, CheckCircle2, Loader2, ScanLine } from "lucide-react";
import { TrackingQrScannerModal } from "@/app/components/scan/TrackingQrScannerModal";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentStepSwitch,
  FulfillmentSuccessPanel,
  FulfillmentToast,
} from "@/app/components/csr/onlineFulfillmentMotion";
import {
  deliveriesHoldParcel,
  deliveriesParcelByTracking,
  deliveriesRewaybill,
  deliveriesShipParcel,
  deliveriesUploadPhoto,
} from "@/app/lib/deliveriesApi";
import type { OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";
import { compressDeliveryPhoto } from "@/app/lib/compressDeliveryPhoto";

type OpsMode = "hold" | "rewaybill";
type ScanIntent = "hold" | "rewaybill_old" | "rewaybill_new";
type RewaybillStep = "scan_old" | "scan_new" | "photo" | "saving" | "done";
type PhotoUploadState = "idle" | "compressing" | "uploading" | "ready" | "error";

function applyBatchCountFromUpload(
  setBatchCount: Dispatch<SetStateAction<number>> | undefined,
  batchItemCount: number | undefined,
) {
  if (!setBatchCount) return;
  if (batchItemCount != null && batchItemCount >= 0) {
    setBatchCount(batchItemCount);
  } else {
    setBatchCount((c) => c + 1);
  }
}

interface DeliveriesOpsPanelProps {
  mode: OpsMode;
  deliveryDate: string;
  onBatchCountChange?: Dispatch<SetStateAction<number>>;
}

export function DeliveriesOpsPanel({ mode, deliveryDate, onBatchCountChange }: DeliveriesOpsPanelProps) {
  const [oldTracking, setOldTracking] = useState("");
  const [newTracking, setNewTracking] = useState("");
  const [parcel, setParcel] = useState<OnlineParcel | null>(null);
  const [rewaybillStep, setRewaybillStep] = useState<RewaybillStep>("scan_old");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [scannerOpen, setScannerOpen] = useState(false);
  const [doneMessage, setDoneMessage] = useState<string | null>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [photoUploadState, setPhotoUploadState] = useState<PhotoUploadState>("idle");
  const [deliveryItemId, setDeliveryItemId] = useState<number | null>(null);
  const photoInputRef = useRef<HTMLInputElement>(null);
  const uploadGenRef = useRef(0);
  const uploadPromiseRef = useRef<Promise<number | null> | null>(null);

  const flash = (msg: string) => {
    setToast(msg);
    window.setTimeout(() => setToast(null), 3200);
  };

  useEffect(() => {
    return () => {
      if (photoPreview) URL.revokeObjectURL(photoPreview);
    };
  }, [photoPreview]);

  const resetRewaybill = () => {
    uploadGenRef.current += 1;
    uploadPromiseRef.current = null;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoFile(null);
    setPhotoPreview(null);
    setPhotoUploadState("idle");
    setDeliveryItemId(null);
    setOldTracking("");
    setNewTracking("");
    setParcel(null);
    setRewaybillStep("scan_old");
    setDoneMessage(null);
    setError(null);
  };

  const uploadPhoto = useCallback(
    async (file: File, tn: string, gen: number): Promise<number | null> => {
      try {
        setPhotoUploadState("compressing");
        const compressed = await compressDeliveryPhoto(file);
        if (uploadGenRef.current !== gen) return null;

        setPhotoFile(compressed);
        setPhotoUploadState("uploading");
        const res = await deliveriesUploadPhoto(deliveryDate, compressed, tn);
        if (uploadGenRef.current !== gen) return null;

        const itemId = res.items[0]?.delivery_item_id ?? null;
        if (itemId) {
          setDeliveryItemId(itemId);
          applyBatchCountFromUpload(onBatchCountChange, res.batch_item_count);
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
    [deliveryDate, onBatchCountChange],
  );

  const startEagerUpload = useCallback(
    (file: File, tn: string) => {
      const gen = ++uploadGenRef.current;
      setDeliveryItemId(null);
      setPhotoUploadState("compressing");
      setError(null);
      const promise = uploadPhoto(file, tn, gen);
      uploadPromiseRef.current = promise;
      void promise;
    },
    [uploadPhoto],
  );

  const ensurePhotoUploaded = async (): Promise<number | null> => {
    if (deliveryItemId) return deliveryItemId;
    if (uploadPromiseRef.current) {
      const itemId = await uploadPromiseRef.current;
      if (itemId) return itemId;
    }
    if (!photoFile || !newTracking) {
      setError("Capture a parcel photo first.");
      return null;
    }
    const gen = ++uploadGenRef.current;
    const promise = uploadPhoto(photoFile, newTracking, gen);
    uploadPromiseRef.current = promise;
    return promise;
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
      if (res.parcel.status !== "on_hold" && res.parcel.status !== "cancelled") {
        setError(
          `Parcel is "${res.parcel.status}" — mark on hold or scan as courier cancelled first, then re-waybill.`,
        );
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
      setRewaybillStep("photo");
      flash("New label linked — take a photo, then ship.");
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

  const onPhotoSelected = (file: File | null) => {
    if (!file || !newTracking) return;
    if (photoPreview) URL.revokeObjectURL(photoPreview);
    setPhotoPreview(URL.createObjectURL(file));
    startEagerUpload(file, newTracking);
  };

  const finishShip = async (another: boolean) => {
    if (!parcel || !newTracking) return;
    if (!photoFile || !photoPreview) {
      setError("Take a parcel photo before shipping.");
      return;
    }
    setRewaybillStep("saving");
    setBusy(true);
    setError(null);
    try {
      const itemId = await ensurePhotoUploaded();
      if (!itemId) {
        setRewaybillStep("photo");
        return;
      }

      const ship = await deliveriesShipParcel(newTracking, itemId, deliveryDate);
      const message = ship.already_shipped
        ? "Already shipped — no extra deduct"
        : "Re-waybill shipped — inventory updated";

      if (another) {
        resetRewaybill();
        flash(message);
      } else {
        setDoneMessage(message);
        setRewaybillStep("done");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Ship failed.");
      setRewaybillStep("photo");
    } finally {
      setBusy(false);
    }
  };

  const onScanDecoded = useCallback(
    (decoded: string) => {
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
    [mode, rewaybillStep],
  );

  const openScanner = (_intent: ScanIntent) => {
    setScannerOpen(true);
    setError(null);
  };

  const scannerTitle =
    mode === "hold"
      ? "Scan parcel to mark on hold"
      : rewaybillStep === "scan_old"
        ? "Scan old waybill on box"
        : "Scan new waybill from CSR";

  const photoUploading = photoUploadState === "compressing" || photoUploadState === "uploading";
  const savingLabel =
    photoUploadState === "compressing"
      ? "Preparing photo…"
      : photoUploadState === "uploading"
        ? "Saving photo…"
        : "Shipping parcel…";

  const rewaybillStepLabels: { id: RewaybillStep; label: string }[] = [
    { id: "scan_old", label: "Scan old QR on sealed box" },
    { id: "scan_new", label: "Scan new waybill (CSR label)" },
    { id: "photo", label: "Take parcel photo" },
    { id: "done", label: "Ship" },
  ];

  const rewaybillActiveIndex =
    rewaybillStep === "scan_old"
      ? 0
      : rewaybillStep === "scan_new"
        ? 1
        : rewaybillStep === "photo" || rewaybillStep === "saving"
          ? 2
          : 3;

  return (
    <div className="max-w-lg space-y-4">
      <FulfillmentToast message={toast} />
      <FulfillmentErrorBanner message={error} />

      {mode === "hold" && (
        <>
          <p className="text-sm text-muted-foreground">
            Wrong label on a <strong className="text-foreground">sealed</strong> box — scan to mark on hold, then re-waybill.
            Inventory stays deducted.
          </p>
          <p className="text-xs text-muted-foreground rounded-lg border border-border bg-secondary/30 px-3 py-2">
            <strong className="text-foreground">Courier cancelled</strong> (order dead, box still sealed): use sidebar →
            Deliveries → <strong className="text-foreground">Cancelled</strong>, then <strong className="text-foreground">Re-waybill</strong>.
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

      {mode === "rewaybill" && (
        <>
          <p className="text-sm text-muted-foreground">
            One flow: scan old label (on hold or courier cancelled) → new waybill → photo → ship.
          </p>
          <ol className="text-xs text-muted-foreground space-y-1.5 list-none">
            {rewaybillStepLabels.map((s, i) => (
              <li
                key={s.id}
                className={`flex items-center gap-2 rounded-lg px-2 py-1.5 ${
                  i === rewaybillActiveIndex ? "bg-primary/10 text-foreground font-semibold" : ""
                }`}
              >
                <span
                  className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold ${
                    i < rewaybillActiveIndex
                      ? "bg-green-600 text-white"
                      : i === rewaybillActiveIndex
                        ? "bg-primary text-primary-foreground"
                        : "bg-muted text-muted-foreground"
                  }`}
                >
                  {i < rewaybillActiveIndex ? "✓" : i + 1}
                </span>
                {s.label}
              </li>
            ))}
          </ol>

          <FulfillmentStepSwitch stepKey={rewaybillStep}>
            {rewaybillStep === "scan_old" && (
              <FulfillmentPressable
                variant="primary"
                className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
                onClick={() => openScanner("rewaybill_old")}
                disabled={busy}
              >
                {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
                Scan old QR / waybill
              </FulfillmentPressable>
            )}

            {rewaybillStep === "scan_new" && (
              <div className="space-y-3">
                <div className="rounded-xl border border-border bg-secondary/20 px-3 py-2 text-sm">
                  <p className="text-xs text-muted-foreground">Old tracking</p>
                  <p className="font-mono font-semibold">{oldTracking}</p>
                </div>
                {parcel && (
                  <p className="text-sm text-muted-foreground whitespace-pre-wrap">{parcel.items_raw}</p>
                )}
                <FulfillmentPressable
                  variant="primary"
                  className="w-full flex items-center justify-center gap-2 rounded-xl py-3 font-bold"
                  onClick={() => openScanner("rewaybill_new")}
                  disabled={busy}
                >
                  {busy ? <Loader2 className="h-5 w-5 animate-spin" /> : <ScanLine className="h-5 w-5" />}
                  Scan new waybill
                </FulfillmentPressable>
                <button type="button" className="text-xs text-muted-foreground underline" onClick={resetRewaybill}>
                  Start over
                </button>
              </div>
            )}

            {rewaybillStep === "photo" && parcel && (
              <div className="rounded-2xl border border-border bg-card p-4 space-y-3">
                <div>
                  <p className="text-xs text-muted-foreground">New tracking</p>
                  <p className="font-mono font-semibold text-lg">{newTracking}</p>
                </div>
                <p className="text-sm whitespace-pre-wrap text-muted-foreground">{parcel.items_raw}</p>

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
                        {photoUploadState === "compressing" ? "Preparing photo…" : "Saving photo…"}
                      </p>
                    )}
                    {photoUploadState === "ready" && (
                      <p className="flex items-center gap-1.5 text-xs text-green-700">
                        <CheckCircle2 className="h-3.5 w-3.5" />
                        Photo saved — ship when ready
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
                    onClick={resetRewaybill}
                    disabled={busy}
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    className="flex-1 rounded-lg border border-border py-2 text-sm font-semibold"
                    onClick={() => void finishShip(true)}
                    disabled={busy || !photoPreview}
                  >
                    {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Ship & next"}
                  </button>
                  <FulfillmentPressable
                    variant="primary"
                    className="flex-1 rounded-lg py-2 text-sm font-bold"
                    onClick={() => void finishShip(false)}
                    disabled={busy || !photoPreview}
                  >
                    {busy ? <Loader2 className="h-4 w-4 animate-spin mx-auto" /> : "Ship"}
                  </FulfillmentPressable>
                </div>
              </div>
            )}

            {rewaybillStep === "saving" && (
              <div className="flex items-center justify-center gap-2 py-12 text-muted-foreground">
                <Loader2 className="h-6 w-6 animate-spin" />
                <span className="text-sm font-medium">{savingLabel}</span>
              </div>
            )}

            {rewaybillStep === "done" && doneMessage && (
              <FulfillmentSuccessPanel className="space-y-3">
                <div className="flex items-center gap-2 text-green-800">
                  <CheckCircle2 className="h-5 w-5 shrink-0" />
                  <p className="font-semibold">Re-waybill complete</p>
                </div>
                <p className="text-sm text-green-700">{doneMessage}</p>
                <FulfillmentPressable
                  variant="primary"
                  className="inline-flex items-center gap-2 px-4 py-2.5 font-bold"
                  onClick={resetRewaybill}
                >
                  <ScanLine className="h-4 w-4" />
                  Another re-waybill
                </FulfillmentPressable>
              </FulfillmentSuccessPanel>
            )}
          </FulfillmentStepSwitch>
        </>
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

      <TrackingQrScannerModal
        open={scannerOpen}
        onClose={() => setScannerOpen(false)}
        title={scannerTitle}
        titleId="deliveries-ops-scan-title"
        onDecoded={onScanDecoded}
        manualEntryLabel="Or type tracking manually"
        zIndex="z-50"
      />
    </div>
  );
}
