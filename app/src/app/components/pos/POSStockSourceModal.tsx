import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { AppPressable } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";

export type PosStockSource = "packaging" | "rts";

/** Pre-checkout: choose RTS vs Non-RTS (packaging / dept FG) stock pool. */
export function POSStockSourceModal({
  open,
  busy,
  onClose,
  onPick,
}: {
  open: boolean;
  busy?: boolean;
  onClose: () => void;
  onPick: (source: PosStockSource) => void;
}) {
  return (
    <AppModal open={open} onClose={busy ? () => undefined : onClose} panelClassName="max-w-sm w-full" labelledBy="pos-stock-source-title">
      <AppModalHeader title="Stock source" titleId="pos-stock-source-title" onClose={busy ? () => undefined : onClose} />
      <AppModalBody>
        <p className="text-sm text-muted-foreground mb-4">
          Are these RTS products or Non-RTS products?
        </p>
        <div className="flex flex-col gap-2">
          <Button
            type="button"
            disabled={busy}
            className="w-full font-semibold"
            style={{ background: "#C8900A" }}
            onClick={() => onPick("rts")}
          >
            RTS products
          </Button>
          <AppPressable
            type="button"
            disabled={busy}
            className="w-full rounded-lg border border-border px-4 py-2.5 text-sm font-semibold hover:border-[#C8900A]/50"
            onClick={() => onPick("packaging")}
          >
            Non-RTS products
          </AppPressable>
        </div>
      </AppModalBody>
    </AppModal>
  );
}
