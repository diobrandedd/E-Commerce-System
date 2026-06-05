import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { useLocation } from "react-router";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost } from "@/app/lib/api";
import { onLiveRefresh } from "@/app/lib/liveRefresh";

export interface UnreadCorrection {
  id: number;
  stage: string;
  correction_type?: string;
  summary?: string;
  reason: string;
  requested_by_name: string;
  created_at: string;
  status: string;
}

export interface UnreadDiscountApproval {
  id: number;
  request_no: string;
  buyer_name: string;
  discount_percent: number;
  subtotal_amount: number;
  discount_amount: number;
  total_amount: number;
  cashier_label: string;
  created_at: string;
  status: "pending" | "approved" | "declined";
}

export interface UnreadEditRequest {
  id: number;
  table_name: string;
  record_id: number;
  field_name: string;
  reason: string;
  requested_by_name: string;
  created_at: string;
  status: "pending" | "approved" | "rejected";
}

export type NotificationItem =
  | ({ kind: "correction" } & UnreadCorrection)
  | ({ kind: "discount_approval" } & UnreadDiscountApproval)
  | ({ kind: "edit_request" } & UnreadEditRequest);

interface CorrectionNotificationsContextValue {
  unreadCount: number;
  unreadList: NotificationItem[];
  refresh: () => Promise<void>;
  markViewed: (id: number) => Promise<void>;
}

const POLL_MS = 15000;

const CorrectionNotificationsContext = createContext<CorrectionNotificationsContextValue | null>(null);

export function CorrectionNotificationsProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const location = useLocation();
  const isExec = user?.role === "executive";
  const [unreadCount, setUnreadCount] = useState(0);
  const [unreadList, setUnreadList] = useState<NotificationItem[]>([]);

  const refresh = useCallback(async () => {
    if (!isExec) {
      setUnreadCount(0);
      setUnreadList([]);
      return;
    }
    try {
      const [countRes, listRes, pendingDiscounts, pendingEdits] = await Promise.all([
        apiGet<{ count: number }>("honey.php", "correction_unread_count"),
        apiGet<{ requests: UnreadCorrection[] }>("honey.php", "correction_unread_list", { limit: 10 }),
        apiGet<{ rows: UnreadDiscountApproval[]; pending_count: number }>("pos_discounts.php", "list", {
          status: "pending",
          limit: "10",
        }),
        apiGet<{ requests: UnreadEditRequest[] }>("edit_requests.php", "list", { status: "pending" }),
      ]);

      const correctionItems: NotificationItem[] = listRes.requests.map(r => ({ kind: "correction", ...r }));
      const discountItems: NotificationItem[] = pendingDiscounts.rows.map(r => ({ kind: "discount_approval", ...r }));
      const editItems: NotificationItem[] = pendingEdits.requests
        .filter(r => r.status === "pending")
        .slice(0, 10)
        .map(r => ({ kind: "edit_request", ...r }));
      const merged = [...correctionItems, ...discountItems, ...editItems].sort((a, b) =>
        String(b.created_at).localeCompare(String(a.created_at)),
      );

      const editPendingCount = pendingEdits.requests.filter(r => r.status === "pending").length;
      setUnreadCount((countRes.count ?? 0) + (pendingDiscounts.pending_count ?? 0) + editPendingCount);
      setUnreadList(merged);
    } catch {
      setUnreadCount(0);
      setUnreadList([]);
    }
  }, [isExec]);

  useEffect(() => {
    void refresh();
  }, [refresh, location.pathname]);

  useEffect(() => {
    if (!isExec) return;
    const t = setInterval(() => void refresh(), POLL_MS);
    return () => clearInterval(t);
  }, [refresh, isExec]);

  useEffect(() => {
    if (!isExec) return;
    const onVisible = () => {
      if (document.visibilityState === "visible") void refresh();
    };
    const onFocus = () => void refresh();
    document.addEventListener("visibilitychange", onVisible);
    window.addEventListener("focus", onFocus);
    return () => {
      document.removeEventListener("visibilitychange", onVisible);
      window.removeEventListener("focus", onFocus);
    };
  }, [refresh, isExec]);

  useEffect(() => onLiveRefresh(() => void refresh()), [refresh]);

  const markViewed = useCallback(
    async (id: number) => {
      if (!isExec) return;
      await apiPost("honey.php", "correction_mark_viewed", { id });
      setUnreadList(prev => prev.filter(item => !(item.kind === "correction" && item.id === id)));
      setUnreadCount(c => Math.max(0, c - 1));
    },
    [isExec],
  );

  return (
    <CorrectionNotificationsContext.Provider value={{ unreadCount, unreadList, refresh, markViewed }}>
      {children}
    </CorrectionNotificationsContext.Provider>
  );
}

export function useCorrectionNotifications(): CorrectionNotificationsContextValue {
  const ctx = useContext(CorrectionNotificationsContext);
  if (!ctx) {
    return {
      unreadCount: 0,
      unreadList: [],
      refresh: async () => {},
      markViewed: async () => {},
    };
  }
  return ctx;
}
