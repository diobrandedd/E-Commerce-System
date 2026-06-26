/** Lazy-load html5-qrcode from CDN (shared across scan modals). */

export type Html5QrcodeCamera = { id: string; label: string };

export type Html5QrcodeInstance = {
  start: (
    cameraIdOrConfig: string | MediaTrackConstraints,
    configuration: { fps?: number; qrbox?: number | { width: number; height: number }; aspectRatio?: number },
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
  new (elementId: string): Html5QrcodeInstance;
  getCameras: () => Promise<Html5QrcodeCamera[]>;
};

declare global {
  interface Window {
    Html5Qrcode?: Html5QrcodeCtor;
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
