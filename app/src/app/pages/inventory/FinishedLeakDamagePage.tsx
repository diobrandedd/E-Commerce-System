import { useCallback, useEffect, useId, useState } from "react";
import { Camera, Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
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
  finishedLeakDamageApi,
  type LeakDamageReason,
  type LeakDamageRow,
} from "@/app/lib/finishedLeakDamage";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

type Tab = "scan" | "history";

function localToday(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

export function FinishedLeakDamagePage() {
  const cameraTitleId = useId();
  const [tab, setTab] = useState<Tab>("scan");
  const [reason, setReason] = useState<LeakDamageReason>("leaked");
  const [code, setCode] = useState("");
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [rows, setRows] = useState<LeakDamageRow[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [loadingHistory, setLoadingHistory] = useState(false);
  const [historyDate, setHistoryDate] = useState(localToday());
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen && tab === "scan", tab);

  const loadHistory = useCallback(
    async (pageNum = 1) => {
      setLoadingHistory(true);
      setError(null);
      try {
        const res = await finishedLeakDamageApi.history(pageNum, { scan_date: historyDate });
        setRows(res.rows);
        setTotal(res.total);
        setPage(res.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load history.");
      } finally {
        setLoadingHistory(false);
      }
    },
    [historyDate],
  );

  useEffect(() => {
    if (tab === "history") {
      void loadHistory(page);
    }
  }, [tab, page, loadHistory]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    setOk(null);
    setError(null);
    try {
      const res = await finishedLeakDamageApi.scan(value, reason);
      playScanBeep("ok");
      setOk(`${res.row.reason_label}: ${res.row.product_label} (${res.row.code}) removed from inventory.`);
      triggerLiveRefresh("inventory");
      refocus();
      return { kind: "success", message: res.row.product_label };
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Scan failed.";
      playScanBeep("bad");
      setError(msg);
      refocus();
      return { kind: "error", message: msg };
    }
  };

  return (
    <PageLayout
      title="Leak / damage scan"
      subtitle="Scan any finished sticker — removes from inventory and logs Leaked or Damaged (not RTS)."
    >
      <AppSectionEnter className="space-y-4">
        <div className="flex gap-2">
          {(["scan", "history"] as const).map(t => (
            <Button
              key={t}
              type="button"
              size="sm"
              variant={tab === t ? "default" : "outline"}
              onClick={() => setTab(t)}
            >
              {t === "scan" ? "Scan" : "History"}
            </Button>
          ))}
        </div>

        <AppStepSwitch stepKey={tab}>
          {tab === "scan" ? (
            <div className="space-y-4">
              <div>
                <p className="text-xs text-muted-foreground mb-2">Reason before scan</p>
                <div className="flex gap-2">
                  {(["leaked", "damaged"] as const).map(r => (
                    <Button
                      key={r}
                      type="button"
                      variant={reason === r ? "default" : "outline"}
                      onClick={() => setReason(r)}
                    >
                      {r === "leaked" ? "Leaked" : "Damaged"}
                    </Button>
                  ))}
                </div>
              </div>

              <div className="flex gap-2 max-w-xl">
                <input
                  ref={inputRef}
                  {...inputProps}
                  className="flex-1 border rounded-lg px-3 py-2 text-sm"
                  placeholder="Scan honey or dept barcode"
                  value={code}
                  onChange={e => setCode(e.target.value)}
                  onKeyDown={e => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void onScan();
                    }
                  }}
                />
                <Button type="button" variant="outline" onClick={() => setCameraOpen(true)}>
                  <Camera size={16} className="mr-1" /> Camera
                </Button>
              </div>

              {ok && <AppSuccessNotice message={ok} />}
              {error && <AppErrorBanner message={error} />}
            </div>
          ) : (
            <div className="space-y-3">
              <label className="text-xs text-muted-foreground block">
                Date
                <input
                  type="date"
                  value={historyDate}
                  onChange={e => {
                    setHistoryDate(e.target.value);
                    setPage(1);
                  }}
                  className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card"
                />
              </label>

              {loadingHistory ? (
                <p className="text-sm text-muted-foreground flex items-center gap-2">
                  <Loader2 className="animate-spin" size={16} /> Loading…
                </p>
              ) : rows.length === 0 ? (
                <p className="text-sm text-muted-foreground">No leak/damage scans on this date.</p>
              ) : (
                <div className="overflow-x-auto rounded-xl border border-border">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                        <th className="px-3 py-2">When</th>
                        <th className="px-3 py-2">Product</th>
                        <th className="px-3 py-2">Code</th>
                        <th className="px-3 py-2">Reason</th>
                        <th className="px-3 py-2">By</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rows.map(r => (
                        <tr key={r.id} className={appHoverRow}>
                          <td className="px-3 py-2 whitespace-nowrap">{new Date(r.scanned_at).toLocaleString()}</td>
                          <td className="px-3 py-2">{r.product_label}</td>
                          <td className="px-3 py-2 font-mono text-xs">{r.code}</td>
                          <td className="px-3 py-2">{r.reason_label}</td>
                          <td className="px-3 py-2">{r.scanned_by_name}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {total > rows.length && (
                <div className="flex gap-2">
                  <Button type="button" size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
                    Previous
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    disabled={page * 25 >= total}
                    onClick={() => setPage(p => p + 1)}
                  >
                    Next
                  </Button>
                </div>
              )}

              {error && <AppErrorBanner message={error} />}
            </div>
          )}
        </AppStepSwitch>
      </AppSectionEnter>

      {cameraOpen ? (
        <BarcodeCameraScannerModal
          titleId={cameraTitleId}
          title="Scan leak / damage barcode"
          continuous
          scanPrompt="Point at a honey or pouch/jar sticker."
          onClose={() => setCameraOpen(false)}
          onDecoded={onScan}
        />
      ) : null}
    </PageLayout>
  );
}
