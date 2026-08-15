import { useCallback, useEffect, useId, useRef, useState } from "react";
import { Camera, Loader2 } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  AppStepSwitch,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { triggerLiveRefresh } from "@/app/lib/liveRefresh";
import {
  deptPouchBarcodesApi,
  deptPouchUnitLabel,
  type DeptPouchDefectRow,
} from "@/app/lib/deptPouchBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";

interface Props {
  productKey: string;
  productLabel: string;
}

type Tab = "scan" | "history";

export function DeptPouchDefectPanel({ productKey, productLabel }: Props) {
  const unit = deptPouchUnitLabel(productKey);
  const inputRef = useRef<HTMLInputElement>(null);
  const [tab, setTab] = useState<Tab>("scan");
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "err" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [rows, setRows] = useState<DeptPouchDefectRow[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loadingHistory, setLoadingHistory] = useState(false);
  const cameraTitleId = useId();

  const loadHistory = useCallback(
    async (pageNum = 1) => {
      setLoadingHistory(true);
      setError(null);
      try {
        const res = await deptPouchBarcodesApi.defectHistory(productKey, pageNum);
        setRows(res.rows);
        setTotal(res.total);
        setPage(res.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load defect history.");
      } finally {
        setLoadingHistory(false);
      }
    },
    [productKey],
  );

  useEffect(() => {
    if (tab === "history") {
      void loadHistory(page);
    }
  }, [tab, page, loadHistory]);

  useEffect(() => {
    if (tab === "scan") {
      inputRef.current?.focus();
    }
  }, [tab]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    setOk(null);
    setError(null);
    try {
      const res = await deptPouchBarcodesApi.defectScan(productKey, value);
      playScanBeep("ok");
      setFlash("ok");
      const success = `Defect recorded · ${res.code} · removed from inventory`;
      setMsg(success);
      setOk(success);
      window.setTimeout(() => setFlash(null), 700);
      triggerLiveRefresh();
      return { kind: "success", message: success };
    } catch (e) {
      const message = e instanceof ApiError ? e.message : "Scan failed.";
      const notInStock = /not in inventory/i.test(message);
      playScanBeep(notInStock ? "dup" : "bad");
      setFlash("err");
      setMsg(message);
      setError(message);
      window.setTimeout(() => setFlash(null), 1200);
      return { kind: notInStock ? "duplicate" : "error", message };
    } finally {
      inputRef.current?.focus();
    }
  };

  const perPage = 25;
  const totalPages = total > 0 ? Math.ceil(total / perPage) : 0;

  return (
    <AppSectionEnter className="space-y-4">
      <div className="flex flex-wrap gap-2">
        {(
          [
            ["scan", "Scan defect"],
            ["history", "History"],
          ] as const
        ).map(([id, label]) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            className={`rounded-md border px-3 py-1.5 text-[11px] font-medium ${
              tab === id
                ? "border-red-400 bg-red-50 text-red-700"
                : "border-border text-muted-foreground hover:bg-secondary/50"
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      <AppStepSwitch stepKey={tab}>
        {tab === "scan" ? (
          <div className="space-y-3">
            <p className="text-sm text-muted-foreground">
              Scan a finished {productLabel} {unit.singular} sticker that is still in inventory. It is
              marked damaged and removed from finished stock — not sent to the RTS pool.
            </p>
            {ok && <AppSuccessNotice message={ok} />}
            {error && <AppErrorBanner message={error} />}
            <div className="flex flex-wrap gap-2">
              <input
                ref={inputRef}
                className={`flex-1 min-w-[12rem] border rounded-lg px-3 py-2 font-mono text-sm outline-none focus:ring-4 focus:ring-red-200/60 ${
                  flash === "ok"
                    ? "border-green-500"
                    : flash === "err"
                      ? "border-red-500"
                      : "border-border"
                }`}
                placeholder="Scan or type barcode"
                value={code}
                onChange={e => setCode(e.target.value)}
                onKeyDown={e => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    void onScan();
                  }
                }}
              />
              <Button type="button" variant="outline" className="gap-1.5" onClick={() => setCameraOpen(true)}>
                <Camera size={16} /> Camera
              </Button>
            </div>
            {msg && !ok && !error ? <p className="text-sm">{msg}</p> : null}
          </div>
        ) : (
          <div className="space-y-3">
            {error && <AppErrorBanner message={error} />}
            <p className="text-xs text-muted-foreground">
              Who scanned each defective {unit.singular} out of finished stock, and when.
            </p>
            {loadingHistory ? (
              <div className="flex justify-center py-8 text-muted-foreground">
                <Loader2 className="animate-spin" size={20} />
              </div>
            ) : rows.length === 0 ? (
              <p className="text-sm text-muted-foreground text-center py-8">No defects recorded yet.</p>
            ) : (
              <>
                <div className="overflow-x-auto rounded-xl border border-border">
                  <table className="w-full text-sm">
                    <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
                      <tr>
                        <th className="text-left px-3 py-2">When</th>
                        <th className="text-left px-3 py-2">Code</th>
                        <th className="text-left px-3 py-2">Batch</th>
                        <th className="text-left px-3 py-2">By</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rows.map(r => (
                        <tr key={r.id} className={appHoverRow}>
                          <td className="px-3 py-2 whitespace-nowrap text-muted-foreground">
                            {new Date(r.scanned_at).toLocaleString("en-PH", {
                              month: "short",
                              day: "numeric",
                              year: "numeric",
                              hour: "2-digit",
                              minute: "2-digit",
                            })}
                          </td>
                          <td className="px-3 py-2 font-mono text-xs">{r.code}</td>
                          <td className="px-3 py-2 font-mono text-xs">{r.batch_no || "—"}</td>
                          <td className="px-3 py-2">{r.scanned_by_name}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {totalPages > 1 && (
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-xs text-muted-foreground">
                      Page {page} of {totalPages} · {total} total
                    </p>
                    <div className="flex gap-2">
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={page <= 1 || loadingHistory}
                        onClick={() => setPage(p => Math.max(1, p - 1))}
                      >
                        Previous
                      </Button>
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={page >= totalPages || loadingHistory}
                        onClick={() => setPage(p => p + 1)}
                      >
                        Next
                      </Button>
                    </div>
                  </div>
                )}
              </>
            )}
          </div>
        )}
      </AppStepSwitch>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title={`Defect scan — ${productLabel}`}
          titleId={cameraTitleId}
          continuous
          scanPrompt={`Point at each defective ${unit.singular} barcode.`}
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </AppSectionEnter>
  );
}
