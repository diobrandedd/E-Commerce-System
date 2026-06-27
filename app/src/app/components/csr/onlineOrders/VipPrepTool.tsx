import { useMemo, useState } from "react";
import { CheckCircle2, Download, FileSpreadsheet, Loader2 } from "lucide-react";
import { AmbiguousOrderCarousel } from "@/app/components/csr/AmbiguousOrderCarousel";
import { FileZone } from "@/app/components/csr/FileZone";
import { FulfillmentErrorBanner, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import { OrderRemarksToast } from "@/app/components/csr/orderRemarksMotion";
import {
  categorizeRow,
  finalRemarkForRow,
  isRowFullyResolved,
  parseRemarks,
  type RowResolution,
} from "@/app/lib/orderRemarksParse";
import { onlineOrdersToolVipArrangerSave } from "@/app/lib/onlineOrdersApi";
import { FulfillmentPressable } from "@/app/components/csr/onlineFulfillmentMotion";
import {
  exportOrderRemarks,
  parseOrderRemarks,
  stashOrderRemarksFile,
  triggerOrderRemarksDownload,
  orderRemarksUploadTooLarge,
  type ParseOrderRemarksResult,
} from "@/app/lib/orderRemarksApi";
import { ApiError } from "@/app/lib/api";
import { PracticeTap } from "@/app/components/tour/PracticeTap";
import {
  practiceStageAtLeast,
  type OnlineOrdersPracticeStage,
} from "@/app/components/csr/onlineOrders/onlineOrdersPractice";
import { OnlineOrdersBackToToolsButton } from "@/app/components/csr/onlineOrders/OnlineOrdersNavButtons";

const VIP_ARRANGER_TOKEN_KEY = "vip_arranger_token";

function courierHintFromProfile(profileId: string): string | null {
  if (profileId === "spx") return "SPX";
  if (profileId === "vip_order_list" || profileId === "jnt") return "J&T Express";
  return null;
}

export function VipPrepTool({
  onDone,
  onBack,
  onOpenMerger,
  practiceMode = false,
  practiceStage = "vip-upload",
}: {
  onDone: () => void;
  onBack: () => void;
  onOpenMerger?: () => void;
  practiceMode?: boolean;
  practiceStage?: OnlineOrdersPracticeStage;
}) {
  const [orderListFile, setOrderListFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [parseResult, setParseResult] = useState<ParseOrderRemarksResult | null>(null);
  const [resolutions, setResolutions] = useState<Record<number, RowResolution>>({});
  const [showCompleted, setShowCompleted] = useState(false);
  const [remarksToken, setRemarksToken] = useState<string | null>(null);
  const [arrangerSaved, setArrangerSaved] = useState<{ row_count: number; arranger_token: string } | null>(null);

  const rows = useMemo(() => {
    if (!parseResult) return [];
    return parseResult.orders.map(order => {
      const parsed = parseRemarks(order.remarks);
      return { order, parsed, category: categorizeRow(order.remarks, parsed) };
    });
  }, [parseResult]);

  const ambiguousRows = useMemo(() => rows.filter(r => r.category === "ambiguous"), [rows]);
  const canSave =
    parseResult !== null &&
    (ambiguousRows.length === 0 ||
      ambiguousRows.every(r => isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx])));

  const uploadRemarks = async (file: File) => {
    setBusy(true);
    setError(null);
    setArrangerSaved(null);
    const sizeErr = orderRemarksUploadTooLarge(file);
    if (sizeErr) {
      setError(sizeErr);
      setBusy(false);
      return;
    }
    try {
      const token = await stashOrderRemarksFile(file);
      setRemarksToken(token);
      const result = await parseOrderRemarks(token);
      setParseResult(result);
      setResolutions({});
      setShowCompleted(false);
      setToast("Order list loaded — resolve any ambiguous orders");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not parse workbook.");
    } finally {
      setBusy(false);
    }
  };

  const runArrange = async () => {
    if (!remarksToken || !parseResult) return;
    setBusy(true);
    setError(null);
    try {
      const remarks = parseResult.orders.map(o =>
        finalRemarkForRow(o.remarks, parseRemarks(o.remarks), resolutions[o.idx]),
      );
      const exp = await exportOrderRemarks(remarksToken, remarks, parseResult.layout);
      triggerOrderRemarksDownload(exp.download_token, exp.download_filename);

      const courierHint = courierHintFromProfile(parseResult.layout.profileId);
      const payload = parseResult.orders.map((o, i) => {
        const final = remarks[i] ?? o.remarks;
        const parcelRef = (o.parcel_ref ?? "").trim();
        if (!parcelRef) {
          throw new Error(`Row ${i + 1} (${o.receiver}) is missing Parcel Name — cannot save for merge.`);
        }
        return {
          parcel_ref: parcelRef,
          receiver: o.receiver,
          phone: o.phone,
          items: final,
          courier_hint: courierHint,
        };
      });

      const saved = await onlineOrdersToolVipArrangerSave(payload);
      sessionStorage.setItem(VIP_ARRANGER_TOKEN_KEY, saved.arranger_token);
      setArrangerSaved(saved);
      setToast(`${saved.row_count} rows arranged — open Excel merger when tracking export is ready`);
      window.setTimeout(() => setToast(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Could not save arranged rows.");
    } finally {
      setBusy(false);
    }
  };

  if (practiceMode) {
    return (
      <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
        <OnlineOrdersBackToToolsButton onClick={onBack} />
        <p className="text-xs font-medium text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          Practice mode — no files uploaded
        </p>
        {practiceStageAtLeast(practiceStage, "vip-upload") && (
          <div data-tour="online-orders-vip-upload">
            <PracticeTap label="Practice: upload VIP order list Excel" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "vip-resolve") && (
          <div data-tour="online-orders-vip-resolve" className="rounded-xl border bg-card p-4 space-y-2">
            <p className="text-sm font-medium">Ambiguous order — pick variant</p>
            <p className="text-xs text-muted-foreground">Sample: &quot;1 pc honey light 250ml&quot; → select Light · 250ml</p>
            <PracticeTap label="Practice: confirm variant pick" className="py-3" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "vip-build") && (
          <div data-tour="online-orders-vip-build">
            <PracticeTap label="Practice: save arranged rows" />
          </div>
        )}
        {practiceStageAtLeast(practiceStage, "vip-done") && (
          <div className="rounded-xl border border-green-200 bg-green-50/80 p-5 space-y-3" data-tour="online-orders-vip-done">
            <p className="text-sm text-green-800 font-medium">Arranged rows saved (12 — sample)</p>
            <PracticeTap label="Practice: back to tools" />
          </div>
        )}
      </FulfillmentSectionEnter>
    );
  }

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <OnlineOrdersBackToToolsButton onClick={onBack} />
      <p className="text-sm text-muted-foreground">
        Upload the VIP order list (Parcel Name + Remarks). Resolve variants on the cards, then save arranged rows.
        When the courier sends tracking, open <strong className="font-semibold text-foreground">VIP Excel merger</strong> to
        build the iPYX sheet.
      </p>
      <div data-tour="online-orders-vip-upload">
        <FileZone
          label="VIP order list Excel"
          hint="J&T bulk upload — Receiver, Parcel Name, Remarks (.xlsx)"
          accept=".xlsx,.xls"
          icon={FileSpreadsheet}
          files={orderListFile ? [orderListFile] : []}
          disabled={busy}
          multiple={false}
          onFiles={fs => {
            const f = fs[0] ?? null;
            setOrderListFile(f);
            setArrangerSaved(null);
            if (f) {
              void uploadRemarks(f);
            } else {
              setParseResult(null);
              setRemarksToken(null);
              setResolutions({});
            }
          }}
        />
      </div>
      {busy && !parseResult && (
        <p className="text-sm text-muted-foreground flex items-center gap-2">
          <Loader2 size={16} className="animate-spin" /> Loading workbook…
        </p>
      )}

      {ambiguousRows.length > 0 && (
        <div data-tour="online-orders-vip-resolve">
          <AmbiguousOrderCarousel
            rows={ambiguousRows}
            resolutions={resolutions}
            showCompleted={showCompleted}
            onShowCompletedChange={setShowCompleted}
            onResolve={(idx, resolution) => setResolutions(prev => ({ ...prev, [idx]: resolution }))}
            onClearResolution={idx => {
              setResolutions(prev => {
                const next = { ...prev };
                delete next[idx];
                return next;
              });
              setShowCompleted(false);
            }}
            disabled={busy}
            onCardCompleted={remaining => {
              setToast(remaining > 0 ? `${remaining} remaining` : "All orders resolved");
              window.setTimeout(() => setToast(null), 2800);
            }}
          />
        </div>
      )}

      {canSave && !arrangerSaved && (
        <button
          type="button"
          disabled={busy}
          onClick={() => void runArrange()}
          data-tour="online-orders-vip-build"
          className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary"
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <Download size={16} />}
          Save arranged rows
        </button>
      )}

      {arrangerSaved && (
        <div className="rounded-xl border border-green-200 bg-green-50/80 p-5 space-y-4" data-tour="online-orders-vip-done">
          <p className="text-sm text-green-800 flex items-center gap-2 font-medium">
            <CheckCircle2 size={18} />
            {arrangerSaved.row_count} rows arranged — remarks export downloaded
          </p>
          <p className="text-sm text-muted-foreground">
            Parcel refs are saved for merge. When J&amp;T or SPX sends the tracking export, open{" "}
            <strong className="font-semibold text-foreground">VIP Excel merger</strong> and upload it there.
          </p>
          <div className="flex flex-wrap gap-3">
            {onOpenMerger ? (
              <FulfillmentPressable
                className="inline-flex items-center gap-2 px-4 py-2.5 font-semibold text-primary-foreground bg-primary"
                onClick={onOpenMerger}
              >
                Open Excel merger
              </FulfillmentPressable>
            ) : null}
            <OnlineOrdersBackToToolsButton onClick={onDone} />
          </div>
        </div>
      )}

      <FulfillmentErrorBanner message={error} />
    </FulfillmentSectionEnter>
  );
}