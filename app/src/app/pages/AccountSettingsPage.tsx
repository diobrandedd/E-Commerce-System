import { useState } from "react";
import { Bell, KeyRound, Loader2, ShieldCheck } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { Switch } from "@/app/components/ui/switch";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
} from "@/app/components/layout/appFeedbackMotion";
import { useBarcodePending } from "@/app/components/notifications/BarcodePendingContext";
import { useAuth } from "@/app/context/AuthContext";
import { changePassword } from "@/app/lib/account";
import { ApiError } from "@/app/lib/api";
import {
  desktopBarcodeAlertsWanted,
  desktopNotificationsSupported,
  requestDesktopBarcodeAlerts,
  setDesktopBarcodeAlertsWanted,
} from "@/app/lib/desktopBarcodeAlerts";

const FIELD_CLASS =
  "rounded-lg border-2 border-border bg-card text-sm px-3 py-2.5 w-full " +
  "focus:border-[#C8900A] focus:outline-none focus:ring-4 focus:ring-[#C8900A]/10";
const LABEL_CLASS = "text-xs font-medium text-muted-foreground uppercase tracking-wide";

export function AccountSettingsPage() {
  const { user } = useAuth();
  const { canWatchBarcodes } = useBarcodePending();
  const [current, setCurrent] = useState("");
  const [next, setNext] = useState("");
  const [confirm, setConfirm] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [alertsOn, setAlertsOn] = useState(() => desktopBarcodeAlertsWanted());
  const [notifyPerm, setNotifyPerm] = useState<NotificationPermission | "unsupported">(() =>
    desktopNotificationsSupported() ? Notification.permission : "unsupported",
  );

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    if (!current || !next || !confirm) {
      setError("Please fill in all password fields.");
      return;
    }
    if (next.length < 6) {
      setError("New password must be at least 6 characters.");
      return;
    }
    if (next !== confirm) {
      setError("New password and confirmation do not match.");
      return;
    }
    if (next === current) {
      setError("New password must be different from your current password.");
      return;
    }

    setBusy(true);
    try {
      await changePassword(current, next);
      setSuccess("Password updated. Use your new password the next time you sign in.");
      setCurrent("");
      setNext("");
      setConfirm("");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not change password.");
    } finally {
      setBusy(false);
    }
  };

  const toggleDesktopAlerts = async (on: boolean) => {
    if (!on) {
      setDesktopBarcodeAlertsWanted(false);
      setAlertsOn(false);
      return;
    }
    if (!desktopNotificationsSupported()) {
      setNotifyPerm("unsupported");
      setAlertsOn(false);
      return;
    }
    if (Notification.permission === "granted") {
      setDesktopBarcodeAlertsWanted(true);
      setNotifyPerm("granted");
      setAlertsOn(true);
      return;
    }
    const perm = await requestDesktopBarcodeAlerts();
    setNotifyPerm(perm);
    setAlertsOn(perm === "granted");
  };

  return (
    <PageLayout title="My Account" subtitle="Manage your sign-in details">
      <div className="max-w-xl mx-auto space-y-5">
        <AppSectionEnter index={0}>
          <div className="rounded-2xl border border-border bg-card p-5">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-[#C8900A] flex items-center justify-center text-white text-sm font-bold">
                {(user?.name ?? "")
                  .split(" ")
                  .map((w) => w[0])
                  .join("")
                  .toUpperCase()
                  .slice(0, 2)}
              </div>
              <div className="min-w-0">
                <p className="text-sm font-semibold text-foreground truncate">{user?.name}</p>
                <p className="text-xs text-muted-foreground truncate">
                  {user?.username}
                  {user?.dept_name ? ` · ${user.dept_name}` : ""}
                </p>
              </div>
            </div>
          </div>
        </AppSectionEnter>

        {canWatchBarcodes && (
          <AppSectionEnter index={1}>
            <div className="rounded-2xl border border-border bg-card p-5 space-y-3">
              <div className="flex items-center gap-2">
                <Bell size={18} className="text-primary" />
                <h2 className="text-sm font-semibold text-foreground">Barcode request alerts</h2>
              </div>
              <div className="flex items-start justify-between gap-4">
                <p className="text-sm text-muted-foreground">
                  Send a Windows notification when someone requests honey bottle or pouch/jar
                  barcodes. Keep this tab open (minimized is fine).
                </p>
                <Switch
                  checked={alertsOn && notifyPerm === "granted"}
                  onCheckedChange={checked => void toggleDesktopAlerts(checked)}
                  disabled={notifyPerm === "unsupported"}
                  aria-label="Windows notifications for barcode requests"
                />
              </div>
              {notifyPerm === "unsupported" && (
                <p className="text-xs text-muted-foreground">
                  This browser does not support desktop notifications.
                </p>
              )}
              {notifyPerm === "denied" && (
                <p className="text-xs text-destructive">
                  Notifications are blocked for this site. Allow them in Edge or Chrome settings,
                  then refresh.
                </p>
              )}
            </div>
          </AppSectionEnter>
        )}

        <AppSectionEnter index={canWatchBarcodes ? 2 : 1}>
          <form onSubmit={submit} className="rounded-2xl border border-border bg-card p-5 space-y-4">
            <div className="flex items-center gap-2">
              <KeyRound size={18} className="text-[#C8900A]" />
              <h2 className="text-sm font-semibold text-foreground">Change password</h2>
            </div>

            <AppSuccessNotice message={success} />
            <AppErrorBanner message={error} />

            <div className="space-y-1.5">
              <label htmlFor="current-password" className={LABEL_CLASS}>
                Current password
              </label>
              <input
                id="current-password"
                type="password"
                autoComplete="current-password"
                className={FIELD_CLASS}
                value={current}
                onChange={(e) => setCurrent(e.target.value)}
                disabled={busy}
              />
            </div>

            <div className="space-y-1.5">
              <label htmlFor="new-password" className={LABEL_CLASS}>
                New password
              </label>
              <input
                id="new-password"
                type="password"
                autoComplete="new-password"
                className={FIELD_CLASS}
                value={next}
                onChange={(e) => setNext(e.target.value)}
                disabled={busy}
              />
              <p className="text-xs text-muted-foreground">At least 6 characters.</p>
            </div>

            <div className="space-y-1.5">
              <label htmlFor="confirm-password" className={LABEL_CLASS}>
                Confirm new password
              </label>
              <input
                id="confirm-password"
                type="password"
                autoComplete="new-password"
                className={FIELD_CLASS}
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                disabled={busy}
              />
            </div>

            <div className="flex items-center justify-between gap-3 pt-1">
              <p className="text-xs text-muted-foreground inline-flex items-center gap-1.5">
                <ShieldCheck size={14} /> Only you can change your password.
              </p>
              <Button type="submit" disabled={busy}>
                {busy ? (
                  <>
                    <Loader2 size={16} className="animate-spin" /> Updating…
                  </>
                ) : (
                  "Update password"
                )}
              </Button>
            </div>
          </form>
        </AppSectionEnter>
      </div>
    </PageLayout>
  );
}
