import { useCallback, useEffect, useId, useRef, useState } from "react";
import { Camera, FlipHorizontal, ImageUp, Loader2, ScanLine, Zap, ZapOff } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { cn } from "@/app/components/ui/utils";
import {
  loadHtml5Qrcode,
  pickPreferredCameraIndex,
  type Html5QrcodeCamera,
  type Html5QrcodeInstance,
} from "@/app/lib/html5QrcodeLoader";

type ScanPhase = "idle" | "starting" | "scanning" | "error";

export function TrackingQrScannerModal({
  open,
  onClose,
  title,
  titleId,
  onDecoded,
  manualEntryLabel = "Or enter tracking manually",
  showManualEntry = true,
  zIndex,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  titleId: string;
  onDecoded: (decoded: string) => void;
  manualEntryLabel?: string;
  showManualEntry?: boolean;
  zIndex?: string;
}) {
  const reactId = useId();
  const readerId = `qr-reader-${reactId.replace(/[^a-zA-Z0-9]/g, "")}`;
  const scannerRef = useRef<Html5QrcodeInstance | null>(null);
  const decodedRef = useRef(false);
  const cameraIndexRef = useRef(0);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const manualRef = useRef<HTMLInputElement>(null);

  const [phase, setPhase] = useState<ScanPhase>("idle");
  const [error, setError] = useState<string | null>(null);
  const [cameras, setCameras] = useState<Html5QrcodeCamera[]>([]);
  const [torchOn, setTorchOn] = useState(false);
  const [torchSupported, setTorchSupported] = useState(false);

  const stopScanner = useCallback(async () => {
    const instance = scannerRef.current;
    scannerRef.current = null;
    if (instance) {
      try {
        await instance.stop();
      } catch {
        /* already stopped */
      }
    }
    setTorchOn(false);
    setTorchSupported(false);
  }, []);

  const handleDecoded = useCallback(
    (decoded: string) => {
      const text = decoded.trim();
      if (!text || decodedRef.current) return;
      decodedRef.current = true;
      void stopScanner().then(() => {
        onClose();
        onDecoded(text);
      });
    },
    [onClose, onDecoded, stopScanner],
  );

  const refreshTorchSupport = useCallback((instance: Html5QrcodeInstance) => {
    try {
      const caps = instance.getRunningTrackCameraCapabilities();
      setTorchSupported(Boolean(caps && "torch" in caps && caps.torch));
    } catch {
      setTorchSupported(false);
    }
  }, []);

  const startCameraAt = useCallback(
    async (index: number) => {
      setPhase("starting");
      setError(null);
      try {
        const Html5Qrcode = await loadHtml5Qrcode();
        let list = cameras;
        if (list.length === 0) {
          list = await Html5Qrcode.getCameras();
          setCameras(list);
        }
        if (list.length === 0) {
          setError("No camera found on this device.");
          setPhase("error");
          return;
        }
        const safeIndex = index >= 0 && index < list.length ? index : pickPreferredCameraIndex(list);
        cameraIndexRef.current = safeIndex;

        if (!scannerRef.current) {
          scannerRef.current = new Html5Qrcode(readerId);
        }
        await scannerRef.current.start(
          list[safeIndex].id,
          { fps: 12, qrbox: { width: 240, height: 240 }, aspectRatio: 1 },
          handleDecoded,
          () => {},
        );
        refreshTorchSupport(scannerRef.current);
        setPhase("scanning");
      } catch (e) {
        setError(e instanceof Error ? e.message : "Could not start camera.");
        setPhase("error");
      }
    },
    [cameras, handleDecoded, readerId, refreshTorchSupport],
  );

  const handleClose = useCallback(() => {
    void stopScanner();
    setPhase("idle");
    setError(null);
    onClose();
  }, [onClose, stopScanner]);

  useEffect(() => {
    if (!open) {
      decodedRef.current = false;
      void stopScanner();
      setPhase("idle");
      setError(null);
      setCameras([]);
      cameraIndexRef.current = 0;
      if (manualRef.current) manualRef.current.value = "";
    }
  }, [open, stopScanner]);

  const flipCamera = async () => {
    if (cameras.length < 2) return;
    const next = (cameraIndexRef.current + 1) % cameras.length;
    await stopScanner();
    scannerRef.current = null;
    await startCameraAt(next);
  };

  const toggleTorch = async () => {
    const instance = scannerRef.current;
    if (!instance || !torchSupported) return;
    const next = !torchOn;
    try {
      await instance.applyVideoConstraints({
        advanced: [{ torch: next } as MediaTrackConstraintSet],
      } as MediaTrackConstraints);
      setTorchOn(next);
    } catch {
      setError("Torch not available on this device.");
    }
  };

  const onFilePicked = async (file: File | null) => {
    if (!file) return;
    setError(null);
    try {
      await stopScanner();
      const Html5Qrcode = await loadHtml5Qrcode();
      if (!scannerRef.current) {
        scannerRef.current = new Html5Qrcode(readerId);
      }
      const result = await scannerRef.current.scanFile(file, false);
      handleDecoded(result);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Could not read barcode from image.");
      setPhase("idle");
    }
  };

  const submitManual = () => {
    const v = manualRef.current?.value.trim() ?? "";
    if (!v) return;
    handleDecoded(v);
  };

  return (
    <AppModal
      open={open}
      onClose={handleClose}
      panelClassName="max-w-md w-full"
      labelledBy={titleId}
      zIndex={zIndex}
    >
      <AppModalHeader title={title} titleId={titleId} onClose={handleClose} />
      <AppModalBody className="space-y-4">
        <AppErrorBanner message={error} />

        <div
          className={cn(
            "relative w-full overflow-hidden rounded-xl border border-border bg-black",
            phase === "scanning" ? "min-h-[260px]" : "min-h-[140px] flex items-center justify-center",
          )}
        >
          <div
            id={readerId}
            className={cn(
              "w-full [&_video]:!w-full [&_video]:!max-h-[320px] [&_video]:object-cover",
              phase !== "scanning" && "hidden",
            )}
          />
          {phase !== "scanning" && (
            <div className="flex flex-col items-center gap-2 px-6 py-8 text-center">
              <ScanLine className="h-10 w-10 text-white/70" strokeWidth={1.5} />
              <p className="text-sm text-white/80">
                {phase === "starting" ? "Starting camera…" : "Point at the tracking barcode or QR label"}
              </p>
            </div>
          )}
          {phase === "starting" && (
            <div className="absolute inset-0 flex items-center justify-center bg-black/40">
              <Loader2 className="h-8 w-8 animate-spin text-white" />
            </div>
          )}
        </div>

        <div className="flex flex-wrap gap-2">
          {phase === "idle" || phase === "error" ? (
            <Button
              type="button"
              className="flex-1 min-w-[10rem] gap-2"
              onClick={() => void startCameraAt(pickPreferredCameraIndex(cameras))}
            >
              <Camera size={16} />
              Start camera
            </Button>
          ) : null}
          {phase === "scanning" ? (
            <>
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="gap-1.5"
                disabled={cameras.length < 2}
                onClick={() => void flipCamera()}
                title={cameras.length < 2 ? "Only one camera available" : "Switch camera"}
              >
                <FlipHorizontal size={15} />
                Flip
              </Button>
              {torchSupported ? (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="gap-1.5"
                  onClick={() => void toggleTorch()}
                >
                  {torchOn ? <ZapOff size={15} /> : <Zap size={15} />}
                  {torchOn ? "Torch off" : "Torch"}
                </Button>
              ) : null}
            </>
          ) : null}
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="gap-1.5"
            onClick={() => fileInputRef.current?.click()}
          >
            <ImageUp size={15} />
            Upload image
          </Button>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={e => void onFilePicked(e.target.files?.[0] ?? null)}
          />
        </div>

        {showManualEntry ? (
          <label className="block text-sm text-muted-foreground">
            {manualEntryLabel}
            <input
              ref={manualRef}
              className="mt-1.5 w-full rounded-lg border border-border bg-background px-3 py-2 font-mono text-sm focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 outline-none"
              placeholder="Tracking number"
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  submitManual();
                }
              }}
            />
            <Button type="button" variant="secondary" size="sm" className="mt-2" onClick={submitManual}>
              Use tracking
            </Button>
          </label>
        ) : null}
      </AppModalBody>
    </AppModal>
  );
}
