import { Trash2 } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import type { OnlineOrdersBatch } from "@/app/lib/onlineOrdersTypes";

export function OnlineOrdersBatchBar({
  batch,
  practiceMode,
  busy,
  onCancel,
}: {
  batch: OnlineOrdersBatch;
  practiceMode?: boolean;
  busy?: boolean;
  onCancel: () => void;
}) {
  return (
    <FulfillmentSectionEnter
      index={0}
      className="mx-auto mb-6 w-full max-w-3xl"
      data-tour="online-orders-batch-bar"
    >
      <div className="flex flex-col gap-3 rounded-2xl border border-border bg-card px-4 py-3.5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 min-w-0">
          <span className="inline-flex items-center rounded-full bg-[#C8900A]/12 px-3 py-1 text-xs font-bold tracking-wide text-[#8a6507]">
            {practiceMode ? "Practice batch" : `Batch #${batch.id}`}
          </span>
          <span className="text-sm font-medium text-foreground">{batch.line_count} lines</span>
          <span className="hidden text-muted-foreground sm:inline">·</span>
          <span className="text-sm text-muted-foreground">{batch.runs.length} stamp run{batch.runs.length === 1 ? "" : "s"}</span>
        </div>
        {!practiceMode && batch.status === "active" && (
          <Button
            type="button"
            variant="destructive"
            disabled={busy}
            onClick={onCancel}
            className="w-full shrink-0 sm:w-auto"
          >
            <Trash2 size={16} />
            Cancel batch
          </Button>
        )}
      </div>
    </FulfillmentSectionEnter>
  );
}
