import { useCallback, useEffect, useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { deptPouchBarcodesApi, deptPouchUnitLabel } from "@/app/lib/deptPouchBarcodes";

interface DeptPouchScanLogModalProps {
  requestId: number;
  batchNo: string;
  productKey: string;
  onClose: () => void;
}

function formatWhen(raw: string) {
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

export function DeptPouchScanLogModal({
  requestId,
  batchNo,
  productKey,
  onClose,
}: DeptPouchScanLogModalProps) {
  const titleId = useId();
  const unit = deptPouchUnitLabel(productKey);
  const [rows, setRows] = useState<
    {
      code: string;
      batch_no: string;
      scanned_at: string;
      scanned_by_name: string;
      unit_status: string | null;
    }[]
  >([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (p: number) => {
      setLoading(true);
      setError(null);
      try {
        const res = await deptPouchBarcodesApi.requestScanLog(requestId, p);
        setRows(res.rows);
        setTotal(res.total);
        setPage(res.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load scan history.");
        setRows([]);
        setTotal(0);
      } finally {
        setLoading(false);
      }
    },
    [requestId],
  );

  useEffect(() => {
    void load(1);
  }, [load]);

  const perPage = 50;
  const totalPages = Math.max(1, Math.ceil(total / perPage));

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-lg w-full" labelledBy={titleId}>
      <AppModalHeader title={`Batch ${batchNo}`} titleId={titleId} onClose={onClose} />
      <AppModalBody className="space-y-3 max-h-[75vh] overflow-y-auto">
        <AppSectionEnter>
          <p className="text-sm text-muted-foreground">
            {unit.plural.charAt(0).toUpperCase() + unit.plural.slice(1)} scanned into finished stock — who
            scanned and when.
          </p>
          {error && <AppErrorBanner message={error} />}
          {loading ? (
            <div className="py-10 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : total === 0 ? (
            <p className="text-sm text-muted-foreground py-6 text-center">
              No {unit.plural} scanned into this request yet.
            </p>
          ) : (
            <>
              <p className="text-xs text-muted-foreground">
                {total} scan{total === 1 ? "" : "s"}
              </p>
              <ul className="divide-y rounded-xl border border-border overflow-hidden">
                {rows.map(r => (
                  <li key={`${r.code}-${r.scanned_at}`} className={`px-3 py-2.5 ${appHoverRow}`}>
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <p className="font-mono text-sm font-semibold tracking-wide">{r.code}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">
                          {r.batch_no}
                          {r.unit_status ? ` · ${r.unit_status.replace(/_/g, " ")}` : ""}
                        </p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-sm font-medium">{r.scanned_by_name || "—"}</p>
                        <p className="text-[11px] text-muted-foreground mt-0.5 whitespace-nowrap">
                          {formatWhen(r.scanned_at)}
                        </p>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
              {totalPages > 1 && (
                <div className="flex items-center justify-between gap-2 pt-1">
                  <button
                    type="button"
                    disabled={page <= 1 || loading}
                    onClick={() => void load(page - 1)}
                    className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40"
                  >
                    Previous
                  </button>
                  <span className="text-xs text-muted-foreground">
                    Page {page} of {totalPages}
                  </span>
                  <button
                    type="button"
                    disabled={page >= totalPages || loading}
                    onClick={() => void load(page + 1)}
                    className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40"
                  >
                    Next
                  </button>
                </div>
              )}
            </>
          )}
        </AppSectionEnter>
      </AppModalBody>
    </AppModal>
  );
}
