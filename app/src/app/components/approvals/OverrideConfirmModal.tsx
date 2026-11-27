import { useId, useState, type ReactNode } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";

type Props = {
  open: boolean;
  title: string;
  summary: ReactNode;
  hrManagerName?: string | null;
  busy?: boolean;
  error?: string | null;
  onClose: () => void;
  /** Called when user confirms (HR applies now, or others send for approval). */
  onConfirm: () => void | Promise<void>;
};

export function OverrideConfirmModal({
  open,
  title,
  summary,
  hrManagerName,
  busy = false,
  error = null,
  onClose,
  onConfirm,
}: Props) {
  const { user } = useAuth();
  const titleId = useId();
  const [localBusy, setLocalBusy] = useState(false);
  const isHr = !!user?.is_hr_manager;
  const working = busy || localBusy;

  if (!open) return null;

  const handleConfirm = async () => {
    setLocalBusy(true);
    try {
      await onConfirm();
    } finally {
      setLocalBusy(false);
    }
  };

  return (
    <AppModal open onClose={working ? () => undefined : onClose} panelClassName="max-w-md w-full" labelledBy={titleId}>
      <AppModalHeader
        title={isHr ? "Are you sure?" : title}
        titleId={titleId}
        subtitle={
          isHr
            ? "You are the HR manager — this will apply immediately. No email will be sent."
            : `This will be sent to ${hrManagerName?.trim() || "the HR manager"} for approval by email.`
        }
        onClose={working ? undefined : onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {error ? <AppErrorBanner message={error} /> : null}
        <div className="rounded-lg border border-border bg-secondary/30 px-3 py-2 text-sm">{summary}</div>
        {!isHr ? (
          <p className="text-[11px] text-muted-foreground">
            Nothing changes until the HR manager logs in and Approves on the Approvals page.
          </p>
        ) : null}
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={working} onClick={onClose} className="min-h-11 flex-1">
          Cancel
        </Button>
        <Button
          type="button"
          disabled={working}
          onClick={() => void handleConfirm()}
          className="min-h-11 flex-1"
          data-testid="override-confirm-submit"
        >
          {working ? (
            <Loader2 className="inline animate-spin" size={14} />
          ) : isHr ? (
            "Yes, apply now"
          ) : (
            "Send for approval"
          )}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
