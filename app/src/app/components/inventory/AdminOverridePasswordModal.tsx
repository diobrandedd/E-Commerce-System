import { useEffect, useState } from "react";
import { Loader2, Shield } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { Button } from "@/app/components/ui/button";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";

type Props = {
  open: boolean;
  title?: string;
  description?: string;
  busy?: boolean;
  error?: string | null;
  onClose: () => void;
  onConfirm: (password: string) => void | Promise<void>;
};

/** Prompt for the System Settings administrator password before stock overrides. */
export function AdminOverridePasswordModal({
  open,
  title = "Administrator password required",
  description = "Overriding stock levels needs the administrator password from System Settings.",
  busy = false,
  error = null,
  onClose,
  onConfirm,
}: Props) {
  const [password, setPassword] = useState("");

  useEffect(() => {
    if (open) setPassword("");
  }, [open]);

  const close = () => {
    if (busy) return;
    setPassword("");
    onClose();
  };

  const submit = async () => {
    const pw = password.trim();
    if (!pw) return;
    await onConfirm(pw);
  };

  return (
    <AppModal open={open} onClose={close} panelClassName="max-w-md w-full">
      <AppModalHeader title={title} onClose={close} />
      <AppModalBody className="space-y-3">
        <div className="flex gap-2 text-sm text-muted-foreground">
          <Shield className="size-4 shrink-0 text-[#C8900A] mt-0.5" />
          <p>{description}</p>
        </div>
        {error ? <AppErrorBanner message={error} /> : null}
        <div>
          <label htmlFor="admin-override-pw" className="text-xs text-muted-foreground">
            Administrator password
          </label>
          <input
            id="admin-override-pw"
            type="password"
            autoComplete="current-password"
            className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"
            value={password}
            onChange={e => setPassword(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void submit();
              }
            }}
            disabled={busy}
            autoFocus
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={close} disabled={busy}>
          Cancel
        </Button>
        <Button type="button" onClick={() => void submit()} disabled={busy || !password.trim()}>
          {busy ? <Loader2 className="animate-spin size-4" /> : "Confirm"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
