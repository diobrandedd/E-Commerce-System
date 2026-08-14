import { useCallback, useEffect, useId, useLayoutEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { Camera, FlipHorizontal, ImageUp, Loader2, ScanLine, Zap, ZapOff } from "lucide-react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { cn } from "@/app/components/ui/utils";
import {
  applyContinuousFocus,
  buildScanConfig,
  forceReleaseAllScannerResources,
  loadHtml5Qrcode,
  pickPreferredCameraIndex,
  pickRearCamera,
  camerasNeedPermissionPrime,
  requestRearCameraAccess,
  releaseScannerElement,
  PREFERRED_REAR_CAMERA,
  type Html5QrcodeCamera,
  type Html5QrcodeInstance,
} from "@/app/lib/html5QrcodeLoader";

type ScanPhase = "idle" | "starting" | "scanning" | "error";
export type ScannerDecodeFeedback = {
  kind: "success" | "duplicate" | "error";
  message: string;
};

const STOP_TIMEOUT_MS = 500;
const SAME_CODE_COOLDOWN_MS = 2500;

export function TrackingQrScannerModal({
  onClose,
  title,
  titleId,
  onDecoded,
  manualEntryLabel = "Or enter tracking manually",
  showManualEntry = true,
  autoStart = true,
  continuous = false,
  scanMode = "qr",
  scanPrompt = "Point at the tracking barcode or QR label",
  zIndex,
}: {
  onClose: () => void;
  title: string;
  titleId: string;
  onDecoded: (decoded: string) => void | ScannerDecodeFeedback | Promise<void | ScannerDecodeFeedback>;
  manualEntryLabel?: string;
  showManualEntry?: boolean;
  autoStart?: boolean;
  continuous?: boolean;
  scanMode?: "qr" | "barcode";
  scanPrompt?: string;
  zIndex?: string;
}) {
  const reduceMotion = useReducedMotion();
  const scanConfig = useMemo(() => buildScanConfig(scanMode), [scanMode]);
  const reactId = useId();
  const readerId = `qr-reader-${reactId.replace(/[^a-zA-Z0-9]/g, "")}`;
  const scannerRef = useRef<Html5QrcodeInstance | null>(null);
  const decodedRef = useRef(false);
  const onDecodedRef = useRef(onDecoded);
  onDecodedRef.current = onDecoded;
  const cameraIndexRef = useRef(0);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const manualRef = useRef<HTMLInputElement>(null);
  const aliveRef = useRef(true);
  const processingRef = useRef(false);
  const lastDecodeRef = useRef<{ code: string; at: number } | null>(null);

  const [phase, setPhase] = useState<ScanPhase>("idle");
  const [error, setError] = useState<string | null>(null);
  const [decodeFeedback, setDecodeFeedback] = useState<ScannerDecodeFeedback | null>(null);
  const [processing, setProcessing] = useState(false);
  const [cameras, setCameras] = useState<Html5QrcodeCamera[]>([]);
  const [torchOn, setTorchOn] = useState(false);
  const [torchSupported, setTorchSupported] = useState(false);

  const stopScanner = useCallback(async () => {
    const instance = scannerRef.current;
    const elementId = readerId;
    scannerRef.current = null;
    if (instance) {
      try {
        await Promise.race([
          instance.stop(),
          new Promise<void>(resolve => window.setTimeout(resolve, STOP_TIMEOUT_MS)),
        ]);
      } catch {
        /* already stopped */
      }
      try {
        instance.clear?.();
      } catch {
        /* ignore */
      }
    }
    releaseScannerElement(elementId);
    setTorchOn(false);
    setTorchSupported(false);
  }, [readerId]);

  const teardown = useCallback(() => {
    aliveRef.current = false;
    void stopScanner().finally(() => {
      forceReleaseAllScannerResources();
    });
  }, [stopScanner]);

  const safeSetPhase = useCallback((next: ScanPhase) => {
    if (aliveRef.current) setPhase(next);
  }, []);

  const safeSetError = useCallback((next: string | null) => {
    if (aliveRef.current) setError(next);
  }, []);

  const handleDecoded = useCallback(
    (decoded: string) => {
      const text = decoded.trim();
      if (!text) return;

      if (continuous) {
        const now = Date.now();
        const last = lastDecodeRef.current;
        if (processingRef.current || (last?.code === text && now - last.at < SAME_CODE_COOLDOWN_MS)) return;

        processingRef.current = true;
        lastDecodeRef.current = { code: text, at: now };
        setProcessing(true);
        void Promise.resolve(onDecodedRef.current(text))
          .then(result => {
            if (aliveRef.current && result) setDecodeFeedback(result);
          })
          .catch(() => {
            if (aliveRef.current) setDecodeFeedback({ kind: "error", message: "Scan failed. Try again." });
          })
          .finally(() => {
            processingRef.current = false;
            if (aliveRef.current) setProcessing(false);
          });
        return;
      }

      if (decodedRef.current) return;
      decodedRef.current = true;
      void stopScanner().then(() => {
        onClose();
        onDecodedRef.current(text);
      });
    },
    [continuous, onClose, stopScanner],
  );

  const refreshTorchSupport = useCallback((instance: Html5QrcodeInstance) => {
    try {
      const caps = instance.getRunningTrackCameraCapabilities();
      setTorchSupported(Boolean(caps && "torch" in caps && caps.torch));
    } catch {
      setTorchSupported(false);
    }
  }, []);

  const readActiveFacingMode = (): string | undefined => {
    const video = document.getElementById(readerId)?.querySelector("video");
    const stream = video?.srcObject;
    if (!(stream instanceof MediaStream)) return undefined;
    return stream.getVideoTracks()[0]?.getSettings().facingMode;
  };

  const startCameraAt = useCallback(
    async (index: number) => {
      if (!aliveRef.current) return;
      safeSetPhase("starting");
      safeSetError(null);
      try {
        const Html5Qrcode = await loadHtml5Qrcode();
        if (!aliveRef.current) return;
        let list = cameras;
        if (list.length === 0) {
          list = await Html5Qrcode.getCameras();
          if (!aliveRef.current) return;
          setCameras(list);
        }
        if (list.length === 0) {
          safeSetError("No camera found on this device.");
          safeSetPhase("error");
          return;
        }
        const safeIndex = index >= 0 && index < list.length ? index : pickPreferredCameraIndex(list);
        cameraIndexRef.current = safeIndex;

        if (!scannerRef.current) {
          scannerRef.current = new Html5Qrcode(readerId);
        }
        await scannerRef.current.start(
          list[safeIndex].id,
          scanConfig,
          handleDecoded,
          () => {},
        );
        if (!aliveRef.current) {
          await stopScanner();
          return;
        }
        await applyContinuousFocus(scannerRef.current);
        refreshTorchSupport(scannerRef.current);
        safeSetPhase("scanning");
      } catch (e) {
        if (!aliveRef.current) return;
        safeSetError(e instanceof Error ? e.message : "Could not start camera.");
        safeSetPhase("error");
      }
    },
    [cameras, handleDecoded, readerId, refreshTorchSupport, safeSetError, safeSetPhase, scanConfig, stopScanner],
  );

  const phaseRef = useRef<ScanPhase>("idle");
  phaseRef.current = phase;

  const startPreferredCamera = useCallback(async () => {
    if (!aliveRef.current || phaseRef.current === "starting" || phaseRef.current === "scanning") return;
    safeSetPhase("starting");
    safeSetError(null);
    try {
      const Html5Qrcode = await loadHtml5Qrcode();
      if (!aliveRef.current) return;

      let list = await Html5Qrcode.getCameras();
      if (!aliveRef.current) return;
      if (camerasNeedPermissionPrime(list)) {
        await requestRearCameraAccess();
        if (!aliveRef.current) return;
        list = await Html5Qrcode.getCameras();
      }
      setCameras(list);

      if (!scannerRef.current) {
        scannerRef.current = new Html5Qrcode(readerId);
      }

      const rear = pickRearCamera(list);
      if (rear) {
        const rearIndex = list.findIndex(c => c.id === rear.id);
        cameraIndexRef.current = rearIndex >= 0 ? rearIndex : pickPreferredCameraIndex(list);
        await scannerRef.current.start(rear.id, scanConfig, handleDecoded, () => {});
      } else {
        await scannerRef.current.start(PREFERRED_REAR_CAMERA, scanConfig, handleDecoded, () => {});
        cameraIndexRef.current = 0;
      }
      if (!aliveRef.current) {
        await stopScanner();
        return;
      }

      const facing = readActiveFacingMode();
      if (facing === "user" && list.length > 1) {
        await stopScanner();
        scannerRef.current = null;
        await startCameraAt(pickPreferredCameraIndex(list));
        return;
      }

      await applyContinuousFocus(scannerRef.current);
      refreshTorchSupport(scannerRef.current);
      safeSetPhase("scanning");
    } catch (e) {
      if (!aliveRef.current) return;
      safeSetError(e instanceof Error ? e.message : "Could not start camera.");
      safeSetPhase("error");
    }
  }, [handleDecoded, readerId, refreshTorchSupport, safeSetError, safeSetPhase, scanConfig, startCameraAt, stopScanner]);

  const handleClose = useCallback(() => {
    teardown();
    setPhase("idle");
    setError(null);
    onClose();
  }, [onClose, teardown]);

  useLayoutEffect(() => {
    aliveRef.current = true;
    decodedRef.current = false;
    return () => {
      teardown();
    };
  }, [teardown]);

  useEffect(() => {
    if (!autoStart) return;
    const frame = window.requestAnimationFrame(() => {
      if (aliveRef.current) void startPreferredCamera();
    });
    return () => {
      window.cancelAnimationFrame(frame);
    };
  }, [autoStart, startPreferredCamera]);

  const flipCamera = async () => {
    const Html5Qrcode = await loadHtml5Qrcode();
    const list = cameras.length > 0 ? cameras : await Html5Qrcode.getCameras();
    if (list.length < 2) return;
    setCameras(list);
    const rearIdx = pickPreferredCameraIndex(list);
    const frontIdx = list.findIndex(c => /front|user|face|integrated|selfie/i.test(c.label));
    const altIdx = frontIdx >= 0 ? frontIdx : (rearIdx + 1) % list.length;
    const next = cameraIndexRef.current === rearIdx ? altIdx : rearIdx;
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

  return createPortal(
    <AppModal
      open
      instant
      onClose={handleClose}
      panelClassName="max-w-md w-full"
      shellClassName="lg:left-64"
      labelledBy={titleId}
      zIndex={zIndex ?? "z-[100]"}
    >
      <AppModalHeader title={title} titleId={titleId} onClose={handleClose} />
      <AppModalBody className="space-y-4">
        <AppErrorBanner message={error} />
        <AnimatePresence mode="wait">
          {decodeFeedback ? (
            <motion.div
              key={`${decodeFeedback.kind}-${decodeFeedback.message}`}
              initial={reduceMotion ? false : { opacity: 0, y: -8, scale: 0.98 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={reduceMotion ? undefined : { opacity: 0, y: -5 }}
              className={cn(
                "rounded-xl border px-4 py-3 text-sm font-semibold",
                decodeFeedback.kind === "success" && "border-green-300 bg-green-100 text-green-800",
                decodeFeedback.kind === "duplicate" && "border-amber-300 bg-amber-100 text-amber-800",
                decodeFeedback.kind === "error" && "border-red-300 bg-red-100 text-red-800",
              )}
            >
              {decodeFeedback.message}
            </motion.div>
          ) : null}
        </AnimatePresence>

        <div
          className={cn(
            "relative w-full overflow-hidden rounded-xl border border-border bg-black",
            phase === "scanning" || phase === "starting" ? "min-h-[260px]" : "min-h-[140px] flex items-center justify-center",
          )}
        >
          <div
            id={readerId}
            className={cn(
              "w-full [&_video]:!w-full [&_video]:!max-h-[320px]",
              // A cropped preview hides the ends of a wide 1D barcode, so users
              // aim at a symbol the decoder is only seeing part of.
              scanMode === "barcode" ? "[&_video]:object-contain" : "[&_video]:object-cover",
              phase !== "scanning" && phase !== "starting" && "hidden",
            )}
          />
          {phase !== "scanning" && phase !== "starting" && (
            <div className="flex flex-col items-center gap-2 px-6 py-8 text-center">
              <ScanLine className="h-10 w-10 text-white/70" strokeWidth={1.5} />
              <p className="text-sm text-white/80">{scanPrompt}</p>
            </div>
          )}
          {phase === "starting" && (
            <div className="absolute inset-0 flex items-center justify-center bg-black/40">
              <Loader2 className="h-8 w-8 animate-spin text-white" />
            </div>
          )}
          {continuous && processing && phase === "scanning" ? (
            <div className="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-black/65 px-3 py-2 text-xs font-medium text-white">
              <Loader2 className="h-3.5 w-3.5 animate-spin" />
              Checking bottle…
            </div>
          ) : null}
        </div>

        <div className="flex flex-wrap gap-2">
          {phase === "idle" || phase === "error" ? (
            <Button
              type="button"
              className="flex-1 min-w-[10rem] gap-2"
              onClick={() => void startPreferredCamera()}
            >
              <Camera size={16} />
              {phase === "error" ? "Retry camera" : "Start camera"}
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
          {!continuous ? (
            <>
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
            </>
          ) : null}
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
    </AppModal>,
    document.body,
  );
}
