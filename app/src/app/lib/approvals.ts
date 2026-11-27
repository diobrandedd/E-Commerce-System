import { apiGet, apiPost } from "@/app/lib/api";

export type OverrideActionType =
  | "inventory_check_finalize"
  | "dept_raw_pool_set"
  | "packaging_scan_undo"
  | "pack_session_undo";

export type OverrideApprovalStatus = "pending" | "approved" | "rejected" | "failed";

export interface OverrideApprovalRequest {
  id: number;
  action_type: OverrideActionType;
  payload: Record<string, unknown>;
  summary: string;
  requested_by: number;
  requested_by_name: string;
  status: OverrideApprovalStatus;
  reviewed_by: number | null;
  reviewed_by_name: string;
  reviewed_at: string | null;
  rejection_note: string | null;
  result: Record<string, unknown> | null;
  error_message: string | null;
  hr_viewed_at: string | null;
  created_at: string;
}

export type OverrideMutationResult = {
  applied?: boolean;
  pending?: boolean;
  request_id?: number;
  request?: OverrideApprovalRequest;
  email_sent?: boolean;
  email_error?: string | null;
  summary?: string;
  undo?: Record<string, unknown>;
};

export const approvalsApi = {
  list: (status = "all") =>
    apiGet<{
      rows: OverrideApprovalRequest[];
      is_hr_manager: boolean;
      hr_manager: { user_id: number | null; email: string; name: string; configured: boolean };
    }>("approvals.php", "list", { status }),
  get: (id: number) =>
    apiGet<{ request: OverrideApprovalRequest; is_hr_manager: boolean }>("approvals.php", "get", {
      id,
    }),
  approve: (id: number) =>
    apiPost<{ request: OverrideApprovalRequest; applied: boolean }>("approvals.php", "approve", {
      id,
    }),
  reject: (id: number, rejection_note: string) =>
    apiPost<{ request: OverrideApprovalRequest }>("approvals.php", "reject", {
      id,
      rejection_note,
    }),
  pendingCount: () => apiGet<{ count: number }>("approvals.php", "pending_count"),
  unreadList: () => apiGet<{ rows: OverrideApprovalRequest[] }>("approvals.php", "unread_list"),
  markViewed: (id?: number) =>
    apiPost<{ ok: boolean }>("approvals.php", "mark_viewed", id ? { id } : {}),
};
