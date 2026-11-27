import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { useLocation } from "react-router";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost } from "@/app/lib/api";
import { approvalsApi, type OverrideApprovalRequest } from "@/app/lib/approvals";
import { listUnreadMaterialRequests, markMaterialRequestViewed, type UnreadMaterialRequest } from "@/app/lib/materials";
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
  | ({ kind: "edit_request" } & UnreadEditRequest)
  | ({ kind: "supply_request" } & UnreadMaterialRequest)
  | ({ kind: "override_approval" } & OverrideApprovalRequest);

interface CorrectionNotificationsContextValue {
  unreadCount: number;
  unreadList: NotificationItem[];
  refresh: () => Promise<void>;
  markViewed: (id: number) => Promise<void>;
  markMaterialViewed: (id: number) => Promise<void>;
  markOverrideViewed: (id: number) => Promise<void>;
}

const POLL_MS = 15000;

const CorrectionNotificationsContext = createContext<CorrectionNotificationsContextValue | null>(null);

export function CorrectionNotificationsProvider({ children }: { children: ReactNode }) {
  const { can, user } = useAuth();
  const location = useLocation();
  const isExec = can("inventory.requests.approve") || can("honey.executive_edit");
  const isHr = !!user?.is_hr_manager;
  const watchBell = isExec || isHr;
  const [unreadCount, setUnreadCount] = useState(0);
  const [unreadList, setUnreadList] = useState<NotificationItem[]>([]);

  const refresh = useCallback(async () => {
    if (!watchBell) {
      setUnreadCount(0);
      setUnreadList([]);
      return;
    }
    const settled = async <T,>(promise: Promise<T>, fallback: T): Promise<T> => {
      try {
        return await promise;
      } catch {
        return fallback;
      }
    };

    const execBundle = isExec
      ? await Promise.all([
          settled(apiGet<{ count: number }>("honey.php", "correction_unread_count"), { count: 0 }),
          settled(apiGet<{ requests: UnreadCorrection[] }>("honey.php", "correction_unread_list", { limit: 10 }), {
            requests: [],
          }),
          settled(
            apiGet<{ rows: UnreadDiscountApproval[]; pending_count: number }>("pos_discounts.php", "list", {
              status: "pending",
              limit: "10",
            }),
            { rows: [], pending_count: 0 },
          ),
          settled(apiGet<{ requests: UnreadEditRequest[] }>("edit_requests.php", "list", { status: "pending" }), {
            requests: [],
          }),
          settled(listUnreadMaterialRequests(10), { requests: [] }),
        ])
      : null;

    const overrideUnread = isHr
      ? await settled(approvalsApi.unreadList(), { rows: [] as OverrideApprovalRequest[] })
      : { rows: [] as OverrideApprovalRequest[] };

    const correctionItems: NotificationItem[] = execBundle
      ? execBundle[1].requests.map(r => ({ kind: "correction", ...r }))
      : [];
    const discountItems: NotificationItem[] = execBundle
      ? execBundle[2].rows.map(r => ({ kind: "discount_approval", ...r }))
      : [];
    const editItems: NotificationItem[] = execBundle
      ? execBundle[3].requests
          .filter(r => r.status === "pending")
          .slice(0, 10)
          .map(r => ({ kind: "edit_request", ...r }))
      : [];
    const supplyItems: NotificationItem[] = execBundle
      ? execBundle[4].requests.map(r => ({ kind: "supply_request", ...r }))
      : [];
    const overrideItems: NotificationItem[] = overrideUnread.rows.map(r => ({
      kind: "override_approval",
      ...r,
    }));

    const merged = [
      ...overrideItems,
      ...correctionItems,
      ...supplyItems,
      ...discountItems,
      ...editItems,
    ].sort((a, b) => String(b.created_at).localeCompare(String(a.created_at)));

    const editPendingCount = execBundle
      ? execBundle[3].requests.filter(r => r.status === "pending").length
      : 0;
    setUnreadCount(
      (execBundle?.[0].count ?? 0) +
        (execBundle?.[2].pending_count ?? 0) +
        editPendingCount +
        supplyItems.length +
        overrideItems.length,
    );
    setUnreadList(merged);
  }, [isExec, isHr, watchBell]);

  useEffect(() => {
    void refresh();
  }, [refresh, location.pathname]);

  useEffect(() => {
    if (!watchBell) return;
    const t = setInterval(() => void refresh(), POLL_MS);
    return () => clearInterval(t);
  }, [refresh, watchBell]);

  useEffect(() => {
    if (!watchBell) return;
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
  }, [refresh, watchBell]);

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

  const markMaterialViewed = useCallback(
    async (id: number) => {
      if (!isExec) return;
      await markMaterialRequestViewed(id);
      setUnreadList(prev => prev.filter(item => !(item.kind === "supply_request" && item.id === id)));
      setUnreadCount(c => Math.max(0, c - 1));
    },
    [isExec],
  );

  const markOverrideViewed = useCallback(
    async (id: number) => {
      if (!isHr) return;
      await approvalsApi.markViewed(id);
      setUnreadList(prev => prev.filter(item => !(item.kind === "override_approval" && item.id === id)));
      setUnreadCount(c => Math.max(0, c - 1));
    },
    [isHr],
  );

  return (
    <CorrectionNotificationsContext.Provider
      value={{ unreadCount, unreadList, refresh, markViewed, markMaterialViewed, markOverrideViewed }}
    >
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
      markMaterialViewed: async () => {},
      markOverrideViewed: async () => {},
    };
  }
  return ctx;
}
