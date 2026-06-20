import { ArrowLeft } from "lucide-react";
import { Button } from "@/app/components/ui/button";

export function OnlineOrdersBackToToolsButton({
  onClick,
  disabled,
  className,
}: {
  onClick: () => void;
  disabled?: boolean;
  className?: string;
}) {
  return (
    <Button
      type="button"
      variant="secondary"
      disabled={disabled}
      onClick={onClick}
      data-tour="online-orders-tool-back"
      className={className ?? "font-semibold shadow-sm border border-border"}
    >
      <ArrowLeft size={16} />
      Back to tools
    </Button>
  );
}

export function OnlineOrdersCancelBatchButton({
  onClick,
  disabled,
}: {
  onClick: () => void;
  disabled?: boolean;
}) {
  return (
    <Button type="button" variant="destructive" disabled={disabled} onClick={onClick}>
      Cancel batch
    </Button>
  );
}
