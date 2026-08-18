import { useEffect, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { Bell } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { AppPressable, appFeedbackEase } from "@/app/components/layout/appFeedbackMotion";
import { useBarcodePending } from "@/app/components/notifications/BarcodePendingContext";
import { cn } from "@/app/components/ui/utils";
import { CONTENT_WIDTH_CLASS } from "@/app/context/PageChromeContext";
import {
  DESKTOP_BARCODE_ALERTS_CHANGED,
  DESKTOP_BARCODE_BANNER_DISMISS_KEY,
  desktopBarcodeAlertsWanted,
  desktopNotificationsSupported,
  requestDesktopBarcodeAlerts,
} from "@/app/lib/desktopBarcodeAlerts";

function bannerDismissedThisSession(): boolean {
  try {
    return sessionStorage.getItem(DESKTOP_BARCODE_BANNER_DISMISS_KEY) === "1";
  } catch {
    return false;
  }
}

export function BarcodeDesktopAlertBanner() {
  const { canWatchBarcodes } = useBarcodePending();
  const reduce = useReducedMotion();
  const [, setTick] = useState(0);
  const [dismissed, setDismissed] = useState(bannerDismissedThisSession);

  useEffect(() => {
    const bump = () => setTick(n => n + 1);
    window.addEventListener(DESKTOP_BARCODE_ALERTS_CHANGED, bump);
    return () => window.removeEventListener(DESKTOP_BARCODE_ALERTS_CHANGED, bump);
  }, []);

  const supported = desktopNotificationsSupported();
  const permission = supported ? Notification.permission : "denied";
  const show =
    canWatchBarcodes &&
    supported &&
    desktopBarcodeAlertsWanted() &&
    !dismissed &&
    permission !== "granted";

  const dismiss = () => {
    try {
      sessionStorage.setItem(DESKTOP_BARCODE_BANNER_DISMISS_KEY, "1");
    } catch {
      /* ignore */
    }
    setDismissed(true);
  };

  const enable = async () => {
    await requestDesktopBarcodeAlerts();
    setTick(n => n + 1);
  };

  return (
    <AnimatePresence initial={false}>
      {show ? (
        <motion.div
          role="status"
          initial={reduce ? false : { opacity: 0, height: 0 }}
          animate={{ opacity: 1, height: "auto" }}
          exit={reduce ? undefined : { opacity: 0, height: 0 }}
          transition={reduce ? { duration: 0 } : appFeedbackEase}
          className="shrink-0 overflow-hidden border-b border-primary/25 bg-primary/10"
        >
          <div
            className={cn(
              "w-full mx-auto px-4 sm:px-6 py-2.5 flex flex-wrap items-center gap-2",
              CONTENT_WIDTH_CLASS.default,
            )}
          >
            <Bell size={16} className="text-primary shrink-0" aria-hidden />
            <p className="text-xs sm:text-sm text-foreground flex-1 min-w-[12rem]">
              {permission === "denied"
                ? "Windows blocked Pyx notifications. Allow them for this site in Edge or Chrome settings, then refresh."
                : "Turn on Windows notifications to hear when someone requests barcodes — even if Pyx is minimized."}
            </p>
            {permission !== "denied" && (
              <Button type="button" size="sm" onClick={() => void enable()}>
                Turn on
              </Button>
            )}
            <AppPressable variant="ghost" className="text-xs px-3 py-1.5" onClick={dismiss}>
              Not now
            </AppPressable>
          </div>
        </motion.div>
      ) : null}
    </AnimatePresence>
  );
}
