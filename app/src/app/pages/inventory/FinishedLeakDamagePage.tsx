import { useCallback, useEffect, useId, useRef, useState } from "react";
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
  type LeakDamagePendingSku,
  type LeakDamageReason,
  type LeakDamageRow,
  type LeakDamageScanResult,
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
  const qtyInputRef = useRef<HTMLInputElement>(null);
  const [tab, setTab] = useState<Tab>("scan");
  const [reason, setReason] = useState<LeakDamageReason>("leaked");
  const [code, setCode] = useState("");
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [pendingSku, setPendingSku] = useState<LeakDamagePendingSku | null>(null);
  const [pendingQty, setPendingQty] = useState("1");
  const [qtyBusy, setQtyBusy] = useState(false);
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

  const applySuccess = (row: LeakDamageScanResult) => {
    const qty = row.qty ?? 1;
    const qtyBit = qty > 1 ? ` ×${qty}` : "";
    setOk(`${row.reason_label}: ${row.product_label}${qtyBit} (${row.code}) removed from inventory.`);
    triggerLiveRefresh("inventory");
    setPendingSku(null);
    setPendingQty("1");
    refocus();
  };

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    setOk(null);
    setError(null);
    try {
      const res = await finishedLeakDamageApi.scan(value, reason);
      if (res.requires_qty && res.sku) {
        setPendingSku(res.sku);
        setPendingQty("1");
        setCameraOpen(false);
        playScanBeep("ok");
        window.setTimeout(() => {
          qtyInputRef.current?.focus();
          qtyInputRef.current?.select();
        }, 40);
        return { kind: "success", message: `${res.sku.label} · enter qty` };
      }
      if (!res.row) {
        throw new Error("Unexpected scan response.");
      }
      playScanBeep("ok");
      applySuccess(res.row);
      return { kind: "success", message: res.row.product_label };
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Scan failed.";
      playScanBeep("bad");
      setError(msg);
      refocus();
      return { kind: "error", message: msg };
    }
  };

  const confirmSkuQty = async () => {
    if (!pendingSku) return;
    const n = Math.max(1, parseInt(pendingQty, 10) || 1);
    setQtyBusy(true);
    setError(null);
    setOk(null);
    try {
      const res = await finishedLeakDamageApi.scan(pendingSku.code, reason, { qty: n });
      if (res.requires_qty) {
        setError("Enter a quantity of at least 1.");
        return;
      }
      if (!res.row) {
        throw new Error("Unexpected scan response.");
      }
      playScanBeep("ok");
      applySuccess(res.row);
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "Could not record leak/damage.";
      playScanBeep("bad");
      setError(msg);
    } finally {
      setQtyBusy(false);
    }
  };

  return (
    <PageLayout
      title="Leak / damage scan"
      subtitle="Scan a finished sticker or SKU barcode — removes from inventory and logs Leaked or Damaged (not RTS)."
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
                      disabled={!!pendingSku}
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
                  placeholder="Scan sticker or SKU barcode"
                  value={code}
                  onChange={e => setCode(e.target.value)}
                  disabled={!!pendingSku}
                  onKeyDown={e => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void onScan();
                    }
                  }}
                />
                <Button
                  type="button"
                  variant="outline"
                  disabled={!!pendingSku}
                  onClick={() => setCameraOpen(true)}
                >
                  <Camera size={16} className="mr-1" /> Camera
                </Button>
              </div>

              {pendingSku && (
                <div className="rounded-xl border border-border bg-card p-4 space-y-3 max-w-xl">
                  <p className="text-sm font-medium">{pendingSku.label}</p>
                  <p className="text-xs text-muted-foreground font-mono">{pendingSku.code}</p>
                  <label className="block text-xs text-muted-foreground space-y-1.5">
                    Quantity
                    <input
                      ref={qtyInputRef}
                      type="number"
                      min={1}
                      step={1}
                      value={pendingQty}
                      onChange={e => setPendingQty(e.target.value)}
                      onKeyDown={e => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          void confirmSkuQty();
                        }
                      }}
                      className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-background text-foreground"
                    />
                  </label>
                  <div className="flex flex-wrap gap-2">
                    <Button type="button" disabled={qtyBusy} onClick={() => void confirmSkuQty()}>
                      {qtyBusy ? <Loader2 size={16} className="animate-spin mr-1" /> : null}
                      Confirm {reason === "leaked" ? "Leaked" : "Damaged"}
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      disabled={qtyBusy}
                      onClick={() => {
                        setPendingSku(null);
                        setPendingQty("1");
                        refocus();
                      }}
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              )}

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
                        <th className="px-3 py-2">Qty</th>
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
                          <td className="px-3 py-2 tabular-nums">{r.qty ?? 1}</td>
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
          scanPrompt="Point at a sticker or SKU barcode."
          onClose={() => setCameraOpen(false)}
          onDecoded={onScan}
        />
      ) : null}
    </PageLayout>
  );
}
