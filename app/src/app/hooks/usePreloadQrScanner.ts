import { useEffect } from "react";
import { preloadHtml5Qrcode } from "@/app/lib/html5QrcodeLoader";

/** Warm scanner CDN on pages that offer QR scan (faster first open). */
export function usePreloadQrScanner(): void {
  useEffect(() => {
    preloadHtml5Qrcode();
  }, []);
}
