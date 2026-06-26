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

export function pickPreferredCameraIndex(cameras: Html5QrcodeCamera[]): number {
  const envIdx = cameras.findIndex(c => /back|rear|environment/i.test(c.label));
  if (envIdx >= 0) return envIdx;
  const frontIdx = cameras.findIndex(c => /front|user|face/i.test(c.label));
  if (frontIdx >= 0 && cameras.length > 1) {
    return frontIdx === 0 ? cameras.length - 1 : 0;
  }
  return cameras.length > 1 ? cameras.length - 1 : 0;
}

/** Prefer rear camera via facingMode; falls back to labeled device id. */
export const PREFERRED_REAR_CAMERA: MediaTrackConstraints = {
  facingMode: { ideal: "environment" },
};
