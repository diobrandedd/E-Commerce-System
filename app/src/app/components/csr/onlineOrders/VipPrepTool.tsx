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
import { onlineOrdersToolVipPrepare } from "@/app/lib/onlineOrdersApi";
import type { OnlineOrdersPrepResult } from "@/app/lib/onlineOrdersTypes";
import { triggerWaybillStashDownload } from "@/app/lib/waybillApi";
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

export function VipPrepTool({
  onDone,
  onBack,
}: {
  onDone: () => void;
  onBack: () => void;
}) {
  const [courierExcelFile, setCourierExcelFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [parseResult, setParseResult] = useState<ParseOrderRemarksResult | null>(null);
  const [resolutions, setResolutions] = useState<Record<number, RowResolution>>({});
  const [showCompleted, setShowCompleted] = useState(false);
  const [remarksToken, setRemarksToken] = useState<string | null>(null);
  const [prepResult, setPrepResult] = useState<OnlineOrdersPrepResult | null>(null);

  const rows = useMemo(() => {
    if (!parseResult) return [];
    return parseResult.orders.map(order => {
      const parsed = parseRemarks(order.remarks);
      return { order, parsed, category: categorizeRow(order.remarks, parsed) };
    });
  }, [parseResult]);

  const ambiguousRows = useMemo(() => rows.filter(r => r.category === "ambiguous"), [rows]);
  const canExport =
    parseResult !== null &&
    (ambiguousRows.length === 0 ||
      ambiguousRows.every(r => isRowFullyResolved(r.order.remarks, r.parsed, resolutions[r.order.idx])));

  const uploadRemarks = async (file: File) => {
    setBusy(true);
    setError(null);
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
      setToast("Workbook loaded — resolve any ambiguous orders");
      window.setTimeout(() => setToast(null), 2800);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not parse workbook.");
    } finally {
      setBusy(false);
    }
  };

  const runPrepare = async () => {
    if (!remarksToken || !parseResult) return;
    setBusy(true);
    setError(null);
    try {
      const remarks = parseResult.orders.map(o =>
        finalRemarkForRow(o.remarks, parseRemarks(o.remarks), resolutions[o.idx]),
      );
      const exp = await exportOrderRemarks(remarksToken, remarks);
      triggerOrderRemarksDownload(exp.download_token, exp.download_filename);

      const payload = parseResult.orders.map((o, i) => {
        const final = remarks[i] ?? o.remarks;
        const tn = o.phone?.trim() || `VIP-${o.idx}`;
        return {
          order_no: null,
          name: o.receiver,
          items: final,
          courier: parseResult.layout.profileLabel,
          tracking_number: tn,
        };
      });
      const prep = await onlineOrdersToolVipPrepare(payload);
      setPrepResult(prep);
      setToast(`${prep.row_count} rows ready — download your arranged Excel`);
      window.setTimeout(() => setToast(null), 3200);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not prepare VIP Excel.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <OrderRemarksToast message={toast} />
      <button type="button" onClick={onBack} className="text-sm text-muted-foreground hover:text-foreground">
        ← Back to tools
      </button>
      <p className="text-sm text-muted-foreground">
        Upload VIP courier Excel (J&T / SPX), resolve variants, then download the arranged iPYX sheet. When ready to
        stamp, go back to tools and open PDF + Excel stamper.
      </p>
      <FileZone
        label="VIP courier Excel"
        hint="Drop J&T or SPX workbook (.xlsx)"
        accept=".xlsx,.xls"
        icon={FileSpreadsheet}
        files={courierExcelFile ? [courierExcelFile] : []}
        disabled={busy}
        multiple={false}
        onFiles={fs => {
          const f = fs[0] ?? null;
          setCourierExcelFile(f);
          if (f) {
            void uploadRemarks(f);
          } else {
            setParseResult(null);
            setRemarksToken(null);
            setResolutions({});
          }
        }}
      />
      {busy && !parseResult && (
        <p className="text-sm text-muted-foreground flex items-center gap-2">
          <Loader2 size={16} className="animate-spin" /> Loading workbook…
        </p>
      )}

      {ambiguousRows.length > 0 && (
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
      )}

      {canExport && !prepResult && (
        <button
          type="button"
          disabled={busy}
          onClick={() => void runPrepare()}
          className="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-lg text-primary-foreground bg-primary"
        >
          {busy ? <Loader2 size={16} className="animate-spin" /> : <Download size={16} />}
          Build arranged Excel
        </button>
      )}

      {prepResult && (
        <div className="rounded-xl border border-green-200 bg-green-50/80 p-5 space-y-4">
          <p className="text-sm text-green-800 flex items-center gap-2 font-medium">
            <CheckCircle2 size={18} />
            Arranged Excel ready ({prepResult.row_count} rows)
          </p>
          <p className="text-sm text-muted-foreground">
            Download the remarks export and iPYX sheet. Go back to tools, then open PDF + Excel stamper when waybills are ready.
          </p>
          <div className="flex flex-wrap gap-3">
            <FulfillmentPressable
              variant="outline"
              className="inline-flex items-center gap-2 px-4 py-2.5"
              onClick={() => triggerWaybillStashDownload(prepResult.excel_token)}
            >
              <Download size={16} /> Download iPYX Excel
            </FulfillmentPressable>
            <FulfillmentPressable variant="primary" className="px-4 py-2.5" onClick={onDone}>
              Back to tools
            </FulfillmentPressable>
          </div>
        </div>
      )}

      <FulfillmentErrorBanner message={error} />
    </FulfillmentSectionEnter>
  );
}
