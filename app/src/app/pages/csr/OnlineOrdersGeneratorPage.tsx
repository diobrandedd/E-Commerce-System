import { useCallback, useEffect, useState } from "react";
import { Loader2, Download, CheckCircle2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { BatchStartModal } from "@/app/components/csr/onlineOrders/BatchStartModal";
import { PipelinePicker } from "@/app/components/csr/onlineOrders/PipelinePicker";
import { PartnerRunStep } from "@/app/components/csr/onlineOrders/PartnerRunStep";
import { StampResultPanel } from "@/app/components/csr/onlineOrders/StampResultPanel";
import { VipRunStep } from "@/app/components/csr/onlineOrders/VipRunStep";
import { ShopeeSpxRunStep } from "@/app/components/csr/onlineOrders/ShopeeSpxRunStep";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  FulfillmentErrorBanner,
  FulfillmentPressable,
  FulfillmentSectionEnter,
  FulfillmentStepSwitch,
  FulfillmentSuccessPanel,
} from "@/app/components/csr/onlineFulfillmentMotion";
import {
  onlineOrdersBatchCancel,
  onlineOrdersBatchFinalize,
  onlineOrdersBatchGet,
  onlineOrdersBatchStart,
  triggerOnlineOrdersDownload,
} from "@/app/lib/onlineOrdersApi";
import type { OnlineOrdersBatch, OnlineOrdersPipeline, PartnerStampResult } from "@/app/lib/onlineOrdersTypes";
import { ApiError } from "@/app/lib/api";
import { triggerWaybillDownload } from "@/app/lib/waybillApi";

type Step = "loading" | "start" | "picker" | "pipeline" | "post_run" | "done";

export function OnlineOrdersGeneratorPage() {
  const [step, setStep] = useState<Step>("loading");
  const [batch, setBatch] = useState<OnlineOrdersBatch | null>(null);
  const [pipeline, setPipeline] = useState<OnlineOrdersPipeline | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [showStartModal, setShowStartModal] = useState(false);
  const [lastStampResult, setLastStampResult] = useState<PartnerStampResult | null>(null);
  /** Shown after stamp; blocks pipeline UI until user picks next action. */
  const [stampResult, setStampResult] = useState<PartnerStampResult | null>(null);

  const refreshBatch = useCallback(async () => {
    const b = await onlineOrdersBatchGet();
    setBatch(b);
    return b;
  }, []);

  useEffect(() => {
    void (async () => {
      try {
        const b = await refreshBatch();
        if (b?.status === "active") {
          setStep("picker");
        } else if (b?.status === "processed") {
          setStep("done");
        } else {
          setShowStartModal(true);
          setStep("start");
        }
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load batch.");
        setShowStartModal(true);
        setStep("start");
      }
    })();
  }, [refreshBatch]);

  const startBatch = async () => {
    setBusy(true);
    setError(null);
    try {
      const b = await onlineOrdersBatchStart();
      setBatch(b);
      setShowStartModal(false);
      setStep("picker");
      setToast("Batch started");
      window.setTimeout(() => setToast(null), 2500);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start batch.");
    } finally {
      setBusy(false);
    }
  };

  const cancelBatch = async () => {
    if (!batch) return;
    setBusy(true);
    try {
      await onlineOrdersBatchCancel(batch.id);
      setBatch(null);
      setShowStartModal(true);
      setStep("start");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Cancel failed.");
    } finally {
      setBusy(false);
    }
  };

  const finalizeBatch = async () => {
    if (!batch) return;
    setBusy(true);
    setError(null);
    try {
      const res = await onlineOrdersBatchFinalize(batch.id);
      setBatch(res.batch);
      setStep("done");
      triggerOnlineOrdersDownload(res.download_token, res.download_filename);
      const promoted = res.parcels_promoted ?? 0;
      const errN = res.parcels_errors?.length ?? 0;
      setToast(
        promoted > 0
          ? `Master Excel ready · ${promoted} parcel${promoted === 1 ? "" : "s"} queued for packing`
          : errN > 0
            ? `Master Excel ready · ${errN} parcel line(s) need attention`
            : "Master print Excel ready",
      );
      window.setTimeout(() => setToast(null), 4500);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Finalize failed.");
    } finally {
      setBusy(false);
    }
  };

  const handleStampSuccess = useCallback(
    async (result: PartnerStampResult) => {
      setStampResult(result);
      setLastStampResult(result);
      const b = await refreshBatch();
      setBatch(b);
      setToast("Stamping complete — download your PDF below");
      window.setTimeout(() => setToast(null), 4000);
    },
    [refreshBatch],
  );

  const goProcessAnother = () => {
    setStampResult(null);
    setPipeline(null);
    setStep("picker");
  };

  const goFinishBatch = async () => {
    setStampResult(null);
    setPipeline(null);
    const b = await refreshBatch();
    setBatch(b);
    setStep("post_run");
  };

  return (
    <PageLayout
      title="Online Orders"
      subtitle="Stamp waybills, organize VIP & Shopee orders, export one master print sheet"
    >
      <OrderRemarksToast message={toast} />

      <BatchStartModal
        open={showStartModal && step === "start"}
        onConfirm={() => void startBatch()}
        onCancel={() => setShowStartModal(false)}
        busy={busy}
      />

      {step === "loading" && (
        <div className="flex items-center gap-2 text-muted-foreground text-sm">
          <Loader2 size={18} className="animate-spin" /> Loading…
        </div>
      )}

      {error && <FulfillmentErrorBanner message={error} />}

      {batch && step !== "loading" && step !== "start" && (
        <FulfillmentSectionEnter index={0} className="mb-4 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
          <span>
            Batch #{batch.id} · {batch.line_count} lines · {batch.runs.length} run(s)
          </span>
          {batch.status === "active" && (
            <button type="button" onClick={() => void cancelBatch()} className="text-red-600 hover:underline">
              Cancel batch
            </button>
          )}
        </FulfillmentSectionEnter>
      )}

      {stampResult && (
        <FulfillmentStepSwitch stepKey="stamp-result">
          <StampResultPanel
            result={stampResult}
            busy={busy}
            onProcessAnother={goProcessAnother}
            onFinishBatch={() => void goFinishBatch()}
          />
        </FulfillmentStepSwitch>
      )}

      {!stampResult && step === "picker" && batch && (
        <FulfillmentStepSwitch stepKey="picker">
          <PipelinePicker
            lineCount={batch.line_count}
            onSelect={p => {
              setStampResult(null);
              setPipeline(p);
              setStep("pipeline");
            }}
          />
        </FulfillmentStepSwitch>
      )}

      {!stampResult && step === "pipeline" && batch && pipeline === "partner" && (
        <FulfillmentStepSwitch stepKey="pipeline-partner">
          <PartnerRunStep
            batchId={batch.id}
            onStampSuccess={r => void handleStampSuccess(r)}
            onBack={() => setStep("picker")}
          />
        </FulfillmentStepSwitch>
      )}
      {!stampResult && step === "pipeline" && batch && pipeline === "vip" && (
        <FulfillmentStepSwitch stepKey="pipeline-vip">
          <VipRunStep
            batchId={batch.id}
            onStampSuccess={r => void handleStampSuccess(r)}
            onBack={() => setStep("picker")}
          />
        </FulfillmentStepSwitch>
      )}
      {!stampResult && step === "pipeline" && batch && pipeline === "shopee_spx" && (
        <FulfillmentStepSwitch stepKey="pipeline-shopee">
          <ShopeeSpxRunStep
            batchId={batch.id}
            onStampSuccess={r => void handleStampSuccess(r)}
            onBack={() => setStep("picker")}
          />
        </FulfillmentStepSwitch>
      )}

      {step === "post_run" && batch && (
        <FulfillmentStepSwitch stepKey="post-run">
          <FulfillmentSectionEnter className="max-w-lg space-y-4 bg-card rounded-xl border p-6">
          <p className="font-semibold text-foreground">Pipeline step complete</p>
          <p className="text-sm text-muted-foreground">
            Process another type of orders, or finish and download the combined master Excel for packers.
          </p>
          {lastStampResult?.stamp?.download_token && (
            <button
              type="button"
              onClick={() =>
                triggerWaybillDownload(
                  lastStampResult.stamp.download_token,
                  lastStampResult.stamp.download_filename,
                )
              }
              className="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-primary-foreground bg-primary"
            >
              <Download size={16} /> Download stamped PDF (last run)
            </button>
          )}
          <div className="flex flex-wrap gap-3">
            <FulfillmentPressable variant="outline" className="px-4 py-2.5" onClick={() => setStep("picker")}>
              Process another
            </FulfillmentPressable>
            <FulfillmentPressable
              variant="primary"
              disabled={busy || batch.line_count < 1}
              onClick={() => void finalizeBatch()}
              className="inline-flex items-center gap-2"
            >
              {busy ? <Loader2 size={16} className="animate-spin" /> : null}
              Done — download master Excel
            </FulfillmentPressable>
          </div>
          </FulfillmentSectionEnter>
        </FulfillmentStepSwitch>
      )}

      {step === "done" && batch && (
        <FulfillmentStepSwitch stepKey="done">
          <FulfillmentSuccessPanel>
          <p className="flex items-center gap-2 font-semibold text-green-800">
            <CheckCircle2 size={20} /> Batch processed
          </p>
          <p className="text-sm text-green-700">
            {batch.line_count} lines saved. Master file: {batch.master_filename ?? "download"}
          </p>
          {batch.master_download_token && (
            <FulfillmentPressable
              variant="primary"
              className="inline-flex items-center gap-2 px-4 py-2"
              onClick={() => triggerOnlineOrdersDownload(batch.master_download_token!, batch.master_filename ?? undefined)}
            >
              <Download size={16} /> Download again
            </FulfillmentPressable>
          )}
          <button
            type="button"
            onClick={() => {
              setShowStartModal(true);
              setStep("start");
              setBatch(null);
            }}
            className="text-sm text-muted-foreground hover:text-foreground block"
          >
            Start new batch
          </button>
          </FulfillmentSuccessPanel>
        </FulfillmentStepSwitch>
      )}

      {step === "start" && !showStartModal && (
        <FulfillmentPressable variant="primary" onClick={() => setShowStartModal(true)}>
          Start new batch
        </FulfillmentPressable>
      )}
    </PageLayout>
  );
}
