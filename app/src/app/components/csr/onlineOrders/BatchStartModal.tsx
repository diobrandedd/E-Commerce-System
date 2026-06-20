import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";

export function BatchStartModal({
  open,
  onConfirm,
  onCancel,
  busy,
}: {
  open: boolean;
  onConfirm: () => void;
  onCancel: () => void;
  busy?: boolean;
}) {
  return (
    <AppModal open={open} onClose={onCancel} panelClassName="max-w-md w-full" labelledBy="batch-start-title">
      <AppModalHeader title="Start a new order batch?" titleId="batch-start-title" onClose={onCancel} />
      <AppModalBody>
        <p className="text-sm text-muted-foreground">
          You can process partner seller orders, VIP orders, and Shopee SPX waybills in one session. When finished,
          download a single master Excel for packers and printing.
        </p>
      </AppModalBody>
      <AppModalFooter>
        <button
          type="button"
          disabled={busy}
          onClick={onCancel}
          className="flex-1 py-2 rounded-lg border border-border bg-background text-sm"
        >
          Cancel
        </button>
        <button
          type="button"
          disabled={busy}
          onClick={onConfirm}
          className="flex-1 py-2 rounded-lg text-sm font-bold text-primary-foreground bg-primary disabled:opacity-50"
        >
          {busy ? "Starting…" : "Confirm"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
