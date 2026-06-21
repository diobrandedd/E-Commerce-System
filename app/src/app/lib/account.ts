import { apiPost } from "@/app/lib/api";

/**
 * Self-service password change for the logged-in IMS user.
 * Backend: `auth.php?action=change_password` (validates current password,
 * requires CSRF, min 6 chars for the new password).
 */
export async function changePassword(current: string, next: string): Promise<void> {
  await apiPost<{ changed: boolean }>("auth.php", "change_password", { current, next });
}
