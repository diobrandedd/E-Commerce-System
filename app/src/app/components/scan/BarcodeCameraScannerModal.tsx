import { useCallback, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { Camera, FlipHorizontal, Loader2, ScanLine, Zap, ZapOff } from "lucide-react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { cn } from "@/app/components/ui/utils";
import { decodeBarcodeFrame, preloadBarcodeDecoder } from "@/app/lib/barcodeDecoder";
import { useDismissSoftwareKeyboard } from "@/app/lib/scanWedgeField";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";

type ScanPhase = "idle" | "starting" | "scanning" | "error";

const SAME_CODE_COOLDOWN_MS = 2500;
const DECODE_INTERVAL_MS = 120;
const PRESENTATION_CLEAR_FRAMES = 3;
/** Fraction of frame height sampled around the aiming guide. */
const BAND_RATIO = 0.5;

function describeCameraError(e: unknown): string {
  if (e instanceof Error) {
    if (e.name === "NotAllowedError") return "Camera permission denied. Allow camera access and try again.";
    if (e.name === "NotFoundError") return "No camera found on this device.";
    if (e.name === "NotReadableError") return "Camera is in use by another app. Close it and retry.";
    if (e.message) return e.message;
  }
  if (typeof e === "string" && e.trim()) return e.trim();
  return "Could not start camera.";
}

/**
 * Camera scanner for 1D bottle stickers.
 *
 * Kept separate from `TrackingQrScannerModal` because it drives `getUserMedia`
 * directly and decodes with the native detector or WASM ZXing, which reads
 * Code 128 on iOS Safari where html5-qrcode's JS decoder does not.
 */
export function BarcodeCameraScannerModal({
  open = true,
  onClose,
  title,
  titleId,
  onDecoded,
  onDecode,
  continuous = false,
  duplicateMode = "cooldown",
  sameCodeCooldownMs = SAME_CODE_COOLDOWN_MS,
  scanPrompt = "Point at one barcode",
  zIndex,
}: {
  open?: boolean;
  onClose: () => void;
  title: string;
  titleId: string;
  onDecoded?: (decoded: string) => void | ScannerDecodeFeedback | Promise<void | ScannerDecodeFeedback>;
  /** @deprecated use `onDecoded` */
  onDecode?: (decoded: string) => void | ScannerDecodeFeedback | Promise<void | ScannerDecodeFeedback>;
  continuous?: boolean;
  /** `presentationEdge` accepts once while visible, then rearms after the code leaves the frame. */
  duplicateMode?: "cooldown" | "presentationEdge";
  /** Min ms between accepting the same code again in cooldown mode. */
  sameCodeCooldownMs?: number;
  scanPrompt?: string;
  zIndex?: string;
}) {
  const decodeHandler = onDecoded ?? onDecode;
  const reduceMotion = useReducedMotion();
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const timerRef = useRef<number | null>(null);
  const aliveRef = useRef(true);
  const decodedRef = useRef(false);
  const processingRef = useRef(false);
  const decodingRef = useRef(false);
  const lastDecodeRef = useRef<{ code: string; at: number } | null>(null);
  const presentedCodeRef = useRef<string | null>(null);
  const missingFrameCountRef = useRef(0);
  const facingRef = useRef<"environment" | "user">("environment");
  const onDecodedRef = useRef(decodeHandler);
  onDecodedRef.current = decodeHandler;

  const [phase, setPhase] = useState<ScanPhase>("idle");
  const [error, setError] = useState<string | null>(null);
  const [decodeFeedback, setDecodeFeedback] = useState<ScannerDecodeFeedback | null>(null);
  const [processing, setProcessing] = useState(false);
  const [torchOn, setTorchOn] = useState(false);
  const [torchSupported, setTorchSupported] = useState(false);
  const [multiCamera, setMultiCamera] = useState(false);
  const focusSinkRef = useRef<HTMLDivElement>(null);
  useDismissSoftwareKeyboard(open, focusSinkRef);

  const stopStream = useCallback(() => {
    if (timerRef.current !== null) {
      window.clearTimeout(timerRef.current);
      timerRef.current = null;
    }
    const stream = streamRef.current;
    streamRef.current = null;
    if (stream) {
      for (const track of stream.getTracks()) {
        try {
          track.stop();
        } catch {
          /* already stopped */
        }
      }
    }
    const video = videoRef.current;
    if (video) {
      try {
        video.pause();
      } catch {
        /* ignore */
      }
      video.srcObject = null;
    }
    setTorchOn(false);
    setTorchSupported(false);
  }, []);

  const handleDecoded = useCallback(
    (decoded: string) => {
      const text = decoded.trim();
      if (!text) return;

      if (continuous) {
        const now = Date.now();
        const last = lastDecodeRef.current;
        if (processingRef.current) return;
        if (duplicateMode === "presentationEdge") {
          missingFrameCountRef.current = 0;
          if (presentedCodeRef.current === text) return;
          presentedCodeRef.current = text;
        } else if (last?.code === text && now - last.at < sameCodeCooldownMs) {
          return;
        }

        processingRef.current = true;
        lastDecodeRef.current = { code: text, at: now };
        setProcessing(true);
        void Promise.resolve(onDecodedRef.current?.(text))
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
      stopStream();
      onClose();
      void onDecodedRef.current?.(text);
    },
    [continuous, duplicateMode, onClose, sameCodeCooldownMs, stopStream],
  );

  /** Sample the band under the aiming guide and hand it to the decoder. */
  const decodeCurrentFrame = useCallback(async (): Promise<string | null> => {
    const video = videoRef.current;
    if (!video || video.readyState < 2) return null;
    const width = video.videoWidth;
    const height = video.videoHeight;
    if (!width || !height) return null;

    let canvas = canvasRef.current;
    if (!canvas) {
      canvas = document.createElement("canvas");
      canvasRef.current = canvas;
    }
    const bandHeight = Math.max(1, Math.round(height * BAND_RATIO));
    const bandTop = Math.round((height - bandHeight) / 2);
    if (canvas.width !== width || canvas.height !== bandHeight) {
      canvas.width = width;
      canvas.height = bandHeight;
    }
    const ctx = canvas.getContext("2d", { willReadFrequently: true });
    if (!ctx) return null;
    ctx.drawImage(video, 0, bandTop, width, bandHeight, 0, 0, width, bandHeight);
    const imageData = ctx.getImageData(0, 0, width, bandHeight);
    return decodeBarcodeFrame(canvas, imageData);
  }, []);

  const scheduleTick = useCallback(() => {
    if (!aliveRef.current) return;
    timerRef.current = window.setTimeout(() => {
      void (async () => {
        if (!aliveRef.current) return;
        if (!decodingRef.current && !processingRef.current) {
          decodingRef.current = true;
          try {
            const code = await decodeCurrentFrame();
            if (code && aliveRef.current) {
              missingFrameCountRef.current = 0;
              handleDecoded(code);
            } else if (duplicateMode === "presentationEdge" && presentedCodeRef.current !== null) {
              missingFrameCountRef.current += 1;
              if (missingFrameCountRef.current >= PRESENTATION_CLEAR_FRAMES) {
                presentedCodeRef.current = null;
                missingFrameCountRef.current = 0;
              }
            }
          } catch {
            /* transient decode failure — keep scanning */
          } finally {
            decodingRef.current = false;
          }
        }
        scheduleTick();
      })();
    }, DECODE_INTERVAL_MS);
  }, [decodeCurrentFrame, duplicateMode, handleDecoded]);

  const start = useCallback(
    async (facing: "environment" | "user") => {
      if (!aliveRef.current) return;
      setPhase("starting");
      setError(null);
      stopStream();
      facingRef.current = facing;
      preloadBarcodeDecoder();

      try {
        if (!navigator.mediaDevices?.getUserMedia) {
          throw new Error("This browser cannot open the camera. Use a hardware scanner or type the code.");
        }
        const stream = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: {
            facingMode: { ideal: facing },
            width: { ideal: 1920 },
            height: { ideal: 1080 },
          },
        });
        if (!aliveRef.current) {
          for (const track of stream.getTracks()) track.stop();
          return;
        }
        streamRef.current = stream;

        const video = videoRef.current;
        if (!video) throw new Error("Camera preview unavailable.");
        video.srcObject = stream;
        video.setAttribute("playsinline", "true");
        await video.play();
        if (!aliveRef.current) {
          stopStream();
          return;
        }

        const track = stream.getVideoTracks()[0];
        if (track) {
          try {
            await track.applyConstraints({
              advanced: [{ focusMode: "continuous" } as MediaTrackConstraintSet],
            } as MediaTrackConstraints);
          } catch {
            /* focus control is optional */
          }
          const caps = track.getCapabilities?.();
          setTorchSupported(Boolean(caps && "torch" in caps && (caps as { torch?: boolean }).torch));
        }

        try {
          const devices = await navigator.mediaDevices.enumerateDevices();
          setMultiCamera(devices.filter(d => d.kind === "videoinput").length > 1);
        } catch {
          setMultiCamera(false);
        }

        setPhase("scanning");
        scheduleTick();
      } catch (e) {
        if (!aliveRef.current) return;
        setError(describeCameraError(e));
        setPhase("error");
      }
    },
    [scheduleTick, stopStream],
  );

  const handleClose = useCallback(() => {
    aliveRef.current = false;
    stopStream();
    onClose();
  }, [onClose, stopStream]);

  useEffect(() => {
    if (!open) {
      aliveRef.current = false;
      stopStream();
      return;
    }
    aliveRef.current = true;
    decodedRef.current = false;
    lastDecodeRef.current = null;
    presentedCodeRef.current = null;
    missingFrameCountRef.current = 0;
    void start("environment");
    return () => {
      aliveRef.current = false;
      stopStream();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  const flipCamera = () => {
    void start(facingRef.current === "environment" ? "user" : "environment");
  };

  const toggleTorch = async () => {
    const track = streamRef.current?.getVideoTracks()[0];
    if (!track || !torchSupported) return;
    const next = !torchOn;
    try {
      await track.applyConstraints({ advanced: [{ torch: next } as MediaTrackConstraintSet] } as MediaTrackConstraints);
      setTorchOn(next);
    } catch {
      setError("Torch not available on this device.");
    }
  };

  const live = phase === "scanning" || phase === "starting";

  if (!open) return null;

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
        <div ref={focusSinkRef} tabIndex={-1} className="h-0 w-0 overflow-hidden outline-none" />
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
            live ? "min-h-[260px]" : "min-h-[140px] flex items-center justify-center",
          )}
        >
          <video
            ref={videoRef}
            muted
            playsInline
            autoPlay
            className={cn("w-full max-h-[320px] object-cover", !live && "hidden")}
          />
          {live ? (
            <div
              className="pointer-events-none absolute inset-x-[6%] top-1/2 -translate-y-1/2 rounded-lg border-2 border-white/80"
              style={{ height: `${BAND_RATIO * 100}%` }}
            />
          ) : null}
          {!live && (
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
          {phase === "scanning" ? (
            processing ? (
              <div className="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-black/65 px-3 py-2 text-xs font-medium text-white">
                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                Checking bottle…
              </div>
            ) : (
              <div className="absolute inset-x-0 bottom-0 bg-black/65 px-3 py-2 text-center text-xs font-medium text-white">
                Hold a barcode inside the box
              </div>
            )
          ) : null}
        </div>

        <div className="flex flex-wrap gap-2">
          {phase === "idle" || phase === "error" ? (
            <>
              <Button
                type="button"
                className="flex-1 min-w-[10rem] gap-2"
                onClick={() => void start(facingRef.current)}
              >
                <Camera size={16} />
                {phase === "error" ? "Retry camera" : "Start camera"}
              </Button>
              <Button type="button" variant="outline" className="min-w-[6rem]" onClick={handleClose}>
                Close
              </Button>
            </>
          ) : null}
          {phase === "scanning" ? (
            <>
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="gap-1.5"
                disabled={!multiCamera}
                onClick={flipCamera}
                title={multiCamera ? "Switch camera" : "Only one camera available"}
              >
                <FlipHorizontal size={15} />
                Flip
              </Button>
              {torchSupported ? (
                <Button type="button" variant="outline" size="sm" className="gap-1.5" onClick={() => void toggleTorch()}>
                  {torchOn ? <ZapOff size={15} /> : <Zap size={15} />}
                  {torchOn ? "Torch off" : "Torch"}
                </Button>
              ) : null}
            </>
          ) : null}
        </div>
      </AppModalBody>
    </AppModal>,
    document.body,
  );
}
