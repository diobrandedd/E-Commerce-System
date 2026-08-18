import pyxLogo from "@/imports/pyxfoodpr.png";

export const DESKTOP_BARCODE_ALERTS_KEY = "pyx.desktopBarcodeAlerts";
export const PYX_NAVIGATE_EVENT = "pyx:navigate";
export const DESKTOP_BARCODE_ALERTS_CHANGED = "pyx:desktopBarcodeAlertsChanged";
export const DESKTOP_BARCODE_BANNER_DISMISS_KEY = "pyx.desktopBarcodeAlerts.bannerDismissed";

export function desktopNotificationsSupported(): boolean {
  return typeof window !== "undefined" && "Notification" in window;
}

export function desktopBarcodeAlertsWanted(): boolean {
  try {
    return localStorage.getItem(DESKTOP_BARCODE_ALERTS_KEY) !== "off";
  } catch {
    return true;
  }
}

export function setDesktopBarcodeAlertsWanted(on: boolean): void {
  try {
    localStorage.setItem(DESKTOP_BARCODE_ALERTS_KEY, on ? "on" : "off");
  } catch {
    /* ignore quota */
  }
  if (typeof window !== "undefined") {
    window.dispatchEvent(new Event(DESKTOP_BARCODE_ALERTS_CHANGED));
  }
}

export async function requestDesktopBarcodeAlerts(): Promise<NotificationPermission | "unsupported"> {
  if (!desktopNotificationsSupported()) return "unsupported";
  const perm = await Notification.requestPermission();
  if (perm === "granted") setDesktopBarcodeAlertsWanted(true);
  return perm;
}

export function showBarcodeRequestNotification(body: string, href: string): void {
  if (!desktopNotificationsSupported()) return;
  if (Notification.permission !== "granted") return;
  if (!desktopBarcodeAlertsWanted()) return;
  try {
    const n = new Notification("Pyx Tracker — barcode request", {
      body,
      icon: typeof pyxLogo === "string" ? pyxLogo : undefined,
      tag: "pyx-barcode-pending",
      renotify: true,
    });
    n.onclick = () => {
      window.focus();
      n.close();
      window.dispatchEvent(new CustomEvent(PYX_NAVIGATE_EVENT, { detail: href }));
    };
  } catch {
    /* some Windows policies block Notification construction */
  }
}
