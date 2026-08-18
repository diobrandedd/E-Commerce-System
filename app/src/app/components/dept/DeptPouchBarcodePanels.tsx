import { useCallback, useEffect, useId, useState } from "react";
import { Camera, Loader2, Printer } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { triggerLiveRefresh } from "@/app/lib/liveRefresh";
import {
  deptPouchBarcodesApi,
  deptPouchPrintSheetUrl,
  deptPouchUnitLabel,
  type DeptPouchBatch,
  type DeptPouchRequest,
} from "@/app/lib/deptPouchBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import { DeptPouchScanLogModal } from "@/app/components/dept/DeptPouchScanLogModal";

interface DeptPouchPanelProps {
  productKey: string;
  productLabel: string;
  /** history = list only (no new-request form) for staff discoverability */
  mode?: "full" | "history";
}

function localToday(): string {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60_000;
  return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

function scanDateLabel(date: string): string {
  return new Date(`${date}T00:00:00`).toLocaleDateString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

export function DeptPouchRequestsPanel({ productKey, mode = "full" }: DeptPouchPanelProps) {
  const unit = deptPouchUnitLabel(productKey);
  const historyOnly = mode === "history";
  const [batchNo, setBatchNo] = useState("");
  const [qty, setQty] = useState("50");
  const [notes, setNotes] = useState("");
  const [rows, setRows] = useState<DeptPouchRequest[]>([]);
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [scannedTotal, setScannedTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [logRequest, setLogRequest] = useState<DeptPouchRequest | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const list = await deptPouchBarcodesApi.scanReport(productKey, scanDate, page);
      setRows(list.rows);
      setScanDates(list.dates);
      setTotal(list.total);
      setScannedTotal(list.scanned_total ?? 0);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load requests.");
    } finally {
      setLoading(false);
    }
  }, [page, productKey, scanDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const submit = async () => {
    setError(null);
    setOk(null);
    setSaving(true);
    try {
      await deptPouchBarcodesApi.requestCreate(productKey, {
        batch_no: batchNo.trim(),
        quantity: Number(qty),
        notes: notes.trim() || undefined,
      });
      setOk("Request submitted.");
      setNotes("");
      triggerLiveRefresh();
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not submit request.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
      {!historyOnly && (
        <div className="bg-card rounded-xl border border-border p-4 space-y-3">
          <h3 className="text-sm font-semibold">New {unit.singular} label request</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label className="text-xs text-muted-foreground">
              Batch code
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 font-mono text-sm uppercase"
                placeholder="e.g. JR073026"
                value={batchNo}
                onChange={e => setBatchNo(e.target.value.toUpperCase())}
              />
            </label>
            <label className="text-xs text-muted-foreground">
              Quantity ({unit.plural})
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                type="number"
                min={1}
                value={qty}
                onChange={e => setQty(e.target.value)}
              />
            </label>
          </div>
          <textarea
            className="w-full border rounded-lg px-2 py-2 text-sm"
            rows={2}
            placeholder="Notes"
            value={notes}
            onChange={e => setNotes(e.target.value)}
          />
          <Button type="button" onClick={() => void submit()} disabled={saving || !batchNo.trim()}>
            {saving ? <Loader2 className="animate-spin" size={16} /> : null}
            Submit request
          </Button>
        </div>
      )}
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h3 className="text-sm font-semibold">Scanned into inventory</h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            Gross scans only — later packing, sales, or defects do not reduce these totals.
          </p>
        </div>
        <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold shrink-0">
          Scan date
          <select
            value={scanDate}
            onChange={e => {
              setScanDate(e.target.value);
              setPage(1);
              setLogRequest(null);
            }}
            className="mt-1 block min-w-[10.5rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
          >
            <option value={localToday()}>Today · {scanDateLabel(localToday())}</option>
            {scanDates
              .filter(date => date !== localToday())
              .map(date => (
                <option key={date} value={date}>
                  {scanDateLabel(date)}
                </option>
              ))}
            <option value="all">All dates</option>
          </select>
        </label>
      </div>
      {scanDate !== "all" && !loading && (
        <div className="rounded-xl border border-border bg-card px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            Total scanned · {scanDate === localToday() ? "Today" : scanDateLabel(scanDate)}
          </p>
          <p className="text-2xl font-bold mt-1">
            {scannedTotal.toLocaleString()}{" "}
            <span className="text-sm font-medium text-muted-foreground">
              {scannedTotal === 1 ? unit.singular : unit.plural}
            </span>
          </p>
        </div>
      )}
      {loading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : rows.length === 0 ? (
        <p className="text-sm text-muted-foreground py-6 text-center">
          No {unit.plural} scanned into inventory {scanDate === "all" ? "yet." : `on ${scanDate}.`}
        </p>
      ) : (
        <div className="space-y-2">
          <p className="text-xs text-muted-foreground">
            Tap a batch to see who scanned each sticker into finished stock and when.
          </p>
          <div className="overflow-x-auto rounded-xl border border-border">
            <table className="w-full text-sm">
              <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
                <tr>
                  <th className="text-left px-3 py-2">When</th>
                  <th className="text-left px-3 py-2">Who</th>
                  <th className="text-left px-3 py-2">Batch</th>
                  <th className="text-left px-3 py-2">Qty</th>
                  <th className="text-left px-3 py-2">Scanned</th>
                  <th className="text-left px-3 py-2">Status</th>
                  {!historyOnly && <th className="px-3 py-2" />}
                </tr>
              </thead>
              <tbody>
                {rows.map(r => (
                  <tr
                    key={r.id}
                    className={`${appHoverRow} cursor-pointer`}
                    onClick={() => setLogRequest(r)}
                  >
                    <td className="px-3 py-2 whitespace-nowrap">{r.created_at.slice(0, 16)}</td>
                    <td className="px-3 py-2">{r.requested_by_name}</td>
                    <td className="px-3 py-2 font-mono text-xs">{r.batch_no}</td>
                    <td className="px-3 py-2">{r.quantity}</td>
                    <td className="px-3 py-2">
                      {r.scanned_count}/{r.generated_count || r.quantity}
                    </td>
                    <td className="px-3 py-2">{r.status.replace(/_/g, " ")}</td>
                    {!historyOnly && (
                      <td className="px-3 py-2">
                        {r.status === "pending" && (
                          <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={e => {
                              e.stopPropagation();
                              void deptPouchBarcodesApi.requestCancel(r.id).then(refresh);
                            }}
                          >
                            Cancel
                          </Button>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {total > 25 && (
            <div className="flex items-center justify-between gap-2 pt-1">
              <p className="text-xs text-muted-foreground">
                Page {page} of {Math.ceil(total / 25)}
              </p>
              <div className="flex gap-2">
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  disabled={page <= 1 || loading}
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                >
                  Previous
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  disabled={page >= Math.ceil(total / 25) || loading}
                  onClick={() => setPage(p => p + 1)}
                >
                  Next
                </Button>
              </div>
            </div>
          )}
        </div>
      )}
      {logRequest && (
        <DeptPouchScanLogModal
          requestId={logRequest.id}
          batchNo={logRequest.batch_no}
          productKey={productKey}
          scanDate={scanDate}
          onClose={() => setLogRequest(null)}
        />
      )}
    </AppSectionEnter>
  );
}

export function DeptPouchScanInPanel({ productKey, productLabel }: DeptPouchPanelProps) {
  const unit = deptPouchUnitLabel(productKey);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "err" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [progress, setProgress] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const cameraTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, productKey);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const res = await deptPouchBarcodesApi.scanStockIn(productKey, value);
      playScanBeep("ok");
      setFlash("ok");
      setMsg(`${res.batch_no} · ${res.code}`);
      if (res.request) {
        setProgress(
          `${res.request.scanned_count} / ${res.request.generated_count || res.request.quantity} scanned`,
        );
      }
      window.setTimeout(() => setFlash(null), 700);
      triggerLiveRefresh();
      return { kind: "success", message: `Stocked ${res.code}` };
    } catch (e) {
      const message = e instanceof ApiError ? e.message : "Scan failed.";
      const dup = /already scanned/i.test(message);
      playScanBeep(dup ? "dup" : "bad");
      setFlash("err");
      setMsg(message);
      window.setTimeout(() => setFlash(null), 1200);
      return { kind: dup ? "duplicate" : "error", message };
    } finally {
      refocus();
    }
  };

  return (
    <AppSectionEnter className="space-y-4">
      <p className="text-sm text-muted-foreground">
        Scan each {unit.singular} sticker into finished {productLabel} stock. WIP and packaging are not
        deducted in this flow.
      </p>
      <div className="flex flex-wrap gap-2">
        <input
          ref={inputRef}
          {...inputProps}
          className={`flex-1 min-w-[12rem] border rounded-lg px-3 py-2 font-mono text-sm outline-none focus:ring-4 focus:ring-[#C8900A]/15 ${
            flash === "ok" ? "border-green-500" : flash === "err" ? "border-red-500" : "border-border"
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
      {msg && <p className="text-sm">{msg}</p>}
      {progress && <p className="text-lg font-bold">{progress}</p>}
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title={`Scan ${unit.singular} barcode`}
          titleId={cameraTitleId}
          continuous
          scanPrompt={`Point at each ${unit.singular} barcode. Keep scanning until the batch is complete.`}
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </AppSectionEnter>
  );
}

export function DeptPouchAdminPanel({ productKey }: DeptPouchPanelProps) {
  const unit = deptPouchUnitLabel(productKey);
  const [pending, setPending] = useState<DeptPouchRequest[]>([]);
  const [batches, setBatches] = useState<DeptPouchBatch[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const refresh = useCallback(async () => {
    try {
      const [list, batchList] = await Promise.all([
        deptPouchBarcodesApi.requestList(productKey, { status: "pending" }),
        deptPouchBarcodesApi.batchList(productKey),
      ]);
      setPending(list.rows);
      setBatches(batchList.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load queue.");
    }
  }, [productKey]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const generate = async (req: DeptPouchRequest) => {
    setBusy(true);
    setError(null);
    try {
      const res = await deptPouchBarcodesApi.generate(req.id);
      setOk(`Generated ${res.batch.quantity_generated} barcodes · print as A4 (68/page).`);
      window.open(deptPouchPrintSheetUrl(res.batch.id), "_blank");
      await deptPouchBarcodesApi.markPrinted(res.batch.id);
      triggerLiveRefresh();
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Generate failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
      <p className="text-xs text-muted-foreground">
        Labels print on A4 at 68 per page (4×17). Print at 100% scale. Scanning stickers adds finished{" "}
        {unit.plural} without deducting WIP or packaging.
      </p>
      <div className="overflow-x-auto rounded-xl border border-border">
        <table className="w-full text-sm">
          <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
            <tr>
              <th className="text-left px-3 py-2">Request</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {pending.map(r => (
              <tr key={r.id} className={appHoverRow}>
                <td className="px-3 py-2">
                  <span className="font-mono text-xs">{r.batch_no}</span> ×{r.quantity}
                </td>
                <td className="px-3 py-2">
                  <Button type="button" size="sm" disabled={busy} onClick={() => void generate(r)}>
                    Generate + print
                  </Button>
                </td>
              </tr>
            ))}
            {pending.length === 0 && (
              <tr>
                <td className="px-3 py-4 text-muted-foreground" colSpan={2}>
                  No pending requests.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      <h3 className="text-sm font-semibold">Recent batches</h3>
      <ul className="space-y-2">
        {batches.slice(0, 20).map(b => (
          <li
            key={b.id}
            className="flex items-center justify-between gap-2 bg-card border rounded-xl px-3 py-2"
          >
            <span className="text-sm">
              {b.batch_no} · {b.scanned_count}/{b.quantity_generated}
            </span>
            <Button
              type="button"
              size="sm"
              variant="outline"
              onClick={() => window.open(deptPouchPrintSheetUrl(b.id), "_blank")}
            >
              <Printer size={14} /> Print A4
            </Button>
          </li>
        ))}
      </ul>
    </AppSectionEnter>
  );
}
