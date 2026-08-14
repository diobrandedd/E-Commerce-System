/** Lazy-load html5-qrcode from CDN (shared across scan modals). */

export type Html5QrcodeCamera = { id: string; label: string };

export type Html5QrcodeBoxSize = { width: number; height: number };

export type Html5QrcodeScanConfig = {
  fps?: number;
  qrbox?: number | Html5QrcodeBoxSize | ((viewfinderWidth: number, viewfinderHeight: number) => Html5QrcodeBoxSize);
  aspectRatio?: number;
  disableFlip?: boolean;
  formatsToSupport?: number[];
  experimentalFeatures?: { useBarCodeDetectorIfSupported?: boolean };
  videoConstraints?: MediaTrackConstraints;
};

export type Html5QrcodeInstance = {
  start: (
    cameraIdOrConfig: string | MediaTrackConstraints,
    configuration: Html5QrcodeScanConfig,
    qrCodeSuccessCallback: (decodedText: string) => void,
    qrCodeErrorCallback?: (errorMessage: string) => void,
  ) => Promise<void>;
  stop: () => Promise<void>;
  clear?: () => void;
  scanFile: (imageFile: File, showImage?: boolean) => Promise<string>;
  applyVideoConstraints: (constraints: MediaTrackConstraints) => Promise<void>;
  getRunningTrackCameraCapabilities: () => MediaTrackCapabilities | null;
};

type Html5QrcodeCtor = {
  new (elementId: string, config?: { formatsToSupport?: number[]; verbose?: boolean }): Html5QrcodeInstance;
  getCameras: () => Promise<Html5QrcodeCamera[]>;
};

declare global {
  interface Window {
    Html5Qrcode?: Html5QrcodeCtor;
    Html5QrcodeSupportedFormats?: Record<string, number>;
  }
}

/** Numeric fallbacks match html5-qrcode's Html5QrcodeSupportedFormats enum. */
const FORMAT_FALLBACK: Record<string, number> = {
  QR_CODE: 0,
  CODE_39: 3,
  CODE_93: 4,
  CODE_128: 5,
  DATA_MATRIX: 6,
  ITF: 8,
  EAN_13: 9,
  EAN_8: 10,
  UPC_A: 14,
  UPC_E: 15,
};

function formatIds(names: string[]): number[] {
  const table = window.Html5QrcodeSupportedFormats ?? FORMAT_FALLBACK;
  return names
    .map(name => (typeof table[name] === "number" ? table[name] : FORMAT_FALLBACK[name]))
    .filter((v): v is number => typeof v === "number");
}

/**
 * Scan tuning. `barcode` targets wide 1D symbols (honey bottle Code 128):
 * a square QR-shaped box crops the ends off a linear barcode, so the decoder
 * never sees a complete symbol. Restricting formats and using the browser's
 * native BarcodeDetector (when present) is far faster than the ZXing fallback.
 */
export function buildScanConfig(mode: "qr" | "barcode"): Html5QrcodeScanConfig {
  const experimentalFeatures = { useBarCodeDetectorIfSupported: true };

  if (mode === "barcode") {
    return {
      fps: 15,
      experimentalFeatures,
      formatsToSupport: formatIds(["CODE_128", "CODE_39", "CODE_93", "ITF", "EAN_13", "UPC_A", "QR_CODE"]),
      // Shaped like one printed label (~4:1). A taller box invites users to
      // frame several stickers at once, which starves each symbol of pixels.
      qrbox: (viewfinderWidth: number, viewfinderHeight: number) => {
        const width = Math.max(120, Math.floor(Math.min(viewfinderWidth * 0.92, viewfinderWidth - 2)));
        const target = Math.max(64, Math.round(width / 4));
        const height = Math.max(56, Math.floor(Math.min(target, viewfinderHeight * 0.6, viewfinderHeight - 2)));
        return { width, height };
      },
    };
  }

  return {
    fps: 12,
    aspectRatio: 1,
    experimentalFeatures,
    qrbox: { width: 240, height: 240 },
  };
}

/** Best-effort continuous autofocus — narrow bars blur badly on fixed focus. */
export async function applyContinuousFocus(instance: Html5QrcodeInstance): Promise<void> {
  try {
    await instance.applyVideoConstraints({
      advanced: [{ focusMode: "continuous" } as unknown as MediaTrackConstraintSet],
    } as MediaTrackConstraints);
  } catch {
    /* Focus control is unsupported on most desktop webcams. */
  }
}

const SCRIPT_URL = "https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js";
let loadPromise: Promise<void> | null = null;

export function loadHtml5Qrcode(): Promise<Html5QrcodeCtor> {
  if (window.Html5Qrcode) {
    return Promise.resolve(window.Html5Qrcode);
  }
  if (!loadPromise) {
    loadPromise = new Promise<void>((resolve, reject) => {
      const existing = document.querySelector(`script[src="${SCRIPT_URL}"]`);
      if (existing) {
        if (window.Html5Qrcode) {
          resolve();
          return;
        }
        existing.addEventListener("load", () => resolve());
        existing.addEventListener("error", () => reject(new Error("Could not load scanner library.")));
        return;
      }
      const script = document.createElement("script");
      script.src = SCRIPT_URL;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error("Could not load scanner library."));
      document.body.appendChild(script);
    });
  }
  return loadPromise.then(() => {
    if (!window.Html5Qrcode) {
      throw new Error("Scanner library unavailable.");
    }
    return window.Html5Qrcode;
  });
}

/** Warm the CDN script on scanner pages so the first open is faster. */
export function preloadHtml5Qrcode(): void {
  void loadHtml5Qrcode();
}

export function camerasNeedPermissionPrime(cameras: Html5QrcodeCamera[]): boolean {
  return cameras.length === 0;
}

export function pickRearCamera(cameras: Html5QrcodeCamera[]): Html5QrcodeCamera | null {
  if (cameras.length === 0) return null;
  const byLabel = cameras.find(c => /back|rear|environment/i.test(c.label));
  if (byLabel) return byLabel;
  const frontIdx = cameras.findIndex(c => /front|user|face|integrated|selfie/i.test(c.label));
  if (frontIdx >= 0 && cameras.length > 1) {
    const nonFront = cameras.filter((_, i) => i !== frontIdx);
    return nonFront[nonFront.length - 1] ?? null;
  }
  // Unlabeled mobile browsers often list front first, rear last.
  return cameras.length > 1 ? cameras[cameras.length - 1] : cameras[0];
}

export function pickPreferredCameraIndex(cameras: Html5QrcodeCamera[]): number {
  const rear = pickRearCamera(cameras);
  if (!rear) return 0;
  const idx = cameras.findIndex(c => c.id === rear.id);
  return idx >= 0 ? idx : cameras.length > 1 ? cameras.length - 1 : 0;
}

/** One-shot permission prime when enumeration returns no devices (first visit). */
export async function requestRearCameraAccess(): Promise<void> {
  if (!navigator.mediaDevices?.getUserMedia) return;
  let stream: MediaStream | null = null;
  try {
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { exact: "environment" } },
        audio: false,
      });
    } catch {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: "environment" } },
        audio: false,
      });
    }
  } catch {
    /* Permission denied or no rear camera — enumeration may still work. */
  } finally {
    stream?.getTracks().forEach(track => track.stop());
  }
}

/** Prefer rear camera via facingMode when device ids are unavailable (single-cam desktop). */
export const PREFERRED_REAR_CAMERA: MediaTrackConstraints = {
  facingMode: "environment",
};

/**
 * Request a high-resolution stream. Default capture is often 640x480, which
 * leaves a 0.4mm bar barely a pixel wide once the label is an arm's length
 * away — not enough for the decoder to resolve narrow/wide bars.
 *
 * Must be passed as the config's `videoConstraints`, never as html5-qrcode's
 * first `start()` argument: that argument rejects objects with more than one
 * key, so a multi-key constraint there throws before the camera opens.
 */
export function cameraVideoConstraints(deviceId?: string): MediaTrackConstraints {
  const base: MediaTrackConstraints = {
    width: { ideal: 1920 },
    height: { ideal: 1080 },
  };
  return deviceId
    ? { ...base, deviceId: { exact: deviceId } }
    : { ...base, ...PREFERRED_REAR_CAMERA };
}

/** Stop any live video tracks under a scanner reader element (safe if library already stopped). */
export function releaseScannerElement(elementId: string): void {
  const root = document.getElementById(elementId);
  if (!root) return;
  root.querySelectorAll("video").forEach(video => {
    const stream = video.srcObject;
    if (stream instanceof MediaStream) {
      stream.getTracks().forEach(track => track.stop());
    }
    video.srcObject = null;
  });
  root.replaceChildren();
}

/**
 * Nuclear cleanup when leaving scanner routes — stops orphan streams/overlays
 * that can block sidebar navigation until a full page refresh.
 */
export function forceReleaseAllScannerResources(): void {
  document.querySelectorAll("video").forEach(video => {
    const stream = video.srcObject;
    if (stream instanceof MediaStream) {
      stream.getTracks().forEach(track => {
        if (track.readyState === "live") track.stop();
      });
    }
    video.srcObject = null;
  });
  document.querySelectorAll('[id^="qr-reader-"]').forEach(el => el.replaceChildren());
  document.querySelectorAll("#qr-shaded-region").forEach(el => el.remove());
  document.body.style.overflow = "";
  document.documentElement.style.overflow = "";
}
