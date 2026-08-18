import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useRef,
  useState,
  type ReactNode,
} from "react";
import { useLocation } from "react-router";
import { useAuth } from "@/app/context/AuthContext";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { deptPouchBarcodesApi } from "@/app/lib/deptPouchBarcodes";
import {
  EMPTY_LABEL_COUNTS,
  barcodePendingIncrease,
  type LabelPendingCounts,
} from "@/app/lib/barcodePendingCounts";
import {
  desktopBarcodeAlertsWanted,
  desktopNotificationsSupported,
  showBarcodeRequestNotification,
} from "@/app/lib/desktopBarcodeAlerts";
import { onLiveRefresh } from "@/app/lib/liveRefresh";

interface BarcodePendingContextValue {
  pendingLabels: LabelPendingCounts;
  canWatchBarcodes: boolean;
}

const BarcodePendingContext = createContext<BarcodePendingContextValue | null>(null);

const POLL_MS = 15000;

export function BarcodePendingProvider({ children }: { children: ReactNode }) {
  const { can, user } = useAuth();
  const location = useLocation();
  const [pendingLabels, setPendingLabels] = useState<LabelPendingCounts>(EMPTY_LABEL_COUNTS);
  const baselineRef = useRef<LabelPendingCounts | null>(null);
  const canSeeBottleBadge = can("honey.bottle.generate") || can("honey.bottle.request");
  const canSeePouchBadge = can([
    "dept.paragis.barcodes",
    "dept.brc.barcodes",
    "dept.cacao.barcodes",
    "dept.comb.barcodes",
    "dept.pouch_barcodes",
  ]);
  const canWatchBarcodes = canSeeBottleBadge || canSeePouchBadge;

  const load = useCallback(async () => {
    if (!canSeeBottleBadge && !canSeePouchBadge) {
      baselineRef.current = EMPTY_LABEL_COUNTS;
      setPendingLabels(EMPTY_LABEL_COUNTS);
      return;
    }
    const next: LabelPendingCounts = { ...EMPTY_LABEL_COUNTS };
    const tasks: Promise<void>[] = [];
    if (canSeeBottleBadge) {
      tasks.push(
        bottleBarcodesApi.pendingCount().then(
          res => {
            next.bottles = res.count ?? 0;
          },
          () => {
            next.bottles = 0;
          },
        ),
      );
    }
    if (canSeePouchBadge) {
      tasks.push(
        deptPouchBarcodesApi.pendingCounts().then(
          res => {
            next.pt = res.counts.pt ?? 0;
            next.brc = res.counts.brc ?? 0;
            next.jar_cacao = res.counts.jar_cacao ?? 0;
            next.honey_comb = res.counts.honey_comb ?? 0;
          },
          () => {
            /* keep zeros */
          },
        ),
      );
    }
    await Promise.all(tasks);

    const prev = baselineRef.current;
    if (prev) {
      const bump = barcodePendingIncrease(prev, next);
      if (bump && desktopNotificationsSupported() && desktopBarcodeAlertsWanted()) {
        showBarcodeRequestNotification(bump.body, bump.href);
      }
    }
    baselineRef.current = next;
    setPendingLabels(next);
  }, [canSeeBottleBadge, canSeePouchBadge]);

  useEffect(() => {
    baselineRef.current = null;
  }, [user?.id]);

  useEffect(() => {
    void load();
    const timer = window.setInterval(() => void load(), POLL_MS);
    const off = onLiveRefresh(() => void load());
    return () => {
      window.clearInterval(timer);
      off();
    };
  }, [load, location.pathname]);

  return (
    <BarcodePendingContext.Provider value={{ pendingLabels, canWatchBarcodes }}>
      {children}
    </BarcodePendingContext.Provider>
  );
}

export function useBarcodePending(): BarcodePendingContextValue {
  const ctx = useContext(BarcodePendingContext);
  if (!ctx) {
    return { pendingLabels: EMPTY_LABEL_COUNTS, canWatchBarcodes: false };
  }
  return ctx;
}
