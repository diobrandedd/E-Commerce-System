import { AlertCircle, CheckCircle2, Download } from "lucide-react";
import { AnimatedStat, FulfillmentPanelEnter, FulfillmentPressable } from "@/app/components/csr/onlineFulfillmentMotion";
import { triggerWaybillDownload } from "@/app/lib/waybillApi";
import type { PartnerStampResult } from "@/app/lib/onlineOrdersTypes";

/** After stamp succeeds: download PDF first, then choose next batch action. */
export function StampResultPanel({
  result,
  onProcessAnother,
  onFinishBatch,
  busy,
}: {
  result: PartnerStampResult;
  onProcessAnother: () => void;
  onFinishBatch: () => void;
  busy?: boolean;
}) {
  const s = result.stamp;
  const canDownload = Boolean(s.download_token);

  return (
    <FulfillmentPanelEnter className="bg-card rounded-xl border border-border overflow-hidden max-w-4xl">
      <div className="px-5 py-4 border-b border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div className="flex items-center gap-2 text-green-700">
          <CheckCircle2 size={20} />
          <span className="font-semibold text-foreground">Stamping complete</span>
        </div>
        {canDownload ? (
          <FulfillmentPressable
            variant="primary"
            className="inline-flex items-center gap-2 px-4 py-2"
            onClick={() => triggerWaybillDownload(s.download_token, s.download_filename)}
          >
            <Download size={16} /> Download stamped PDF
          </FulfillmentPressable>
        ) : (
          <p className="text-sm text-amber-800 flex items-center gap-2">
            <AlertCircle size={16} /> Stamped file link unavailable — check server logs.
          </p>
        )}
      </div>
      <div className="grid grid-cols-3 divide-x divide-border border-b border-border">
        <div className="px-5 py-4 text-center">
          <p className="text-xs text-muted-foreground uppercase tracking-wide">Total pages</p>
          <p className="text-2xl font-bold text-foreground mt-1"><AnimatedStat value={s.total} /></p>
        </div>
        <div className="px-5 py-4 text-center">
          <p className="text-xs text-muted-foreground uppercase tracking-wide">Matched</p>
          <p className="text-2xl font-bold text-green-600 mt-1"><AnimatedStat value={s.matched} /></p>
        </div>
        <div className="px-5 py-4 text-center">
          <p className="text-xs text-muted-foreground uppercase tracking-wide">Lines saved</p>
          <p className="text-2xl font-bold text-foreground mt-1"><AnimatedStat value={result.lines_added} /></p>
        </div>
      </div>
      <div className="px-5 py-4 space-y-3">
        <p className="text-sm text-muted-foreground">
          Download the stamped PDF for printing before you continue. Nothing else happens until you pick an option below.
        </p>
        <div className="flex flex-wrap gap-3">
          <FulfillmentPressable variant="outline" disabled={busy} onClick={onProcessAnother}>
            Process another pipeline
          </FulfillmentPressable>
          <FulfillmentPressable variant="primary" disabled={busy} onClick={onFinishBatch}>
            Done — master Excel for packers
          </FulfillmentPressable>
        </div>
      </div>
    </FulfillmentPanelEnter>
  );
}
