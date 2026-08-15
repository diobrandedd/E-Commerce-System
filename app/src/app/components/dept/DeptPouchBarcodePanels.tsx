import { useCallback, useEffect, useId, useRef, useState } from "react";
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

interface DeptPouchPanelProps {
  productKey: string;
  productLabel: string;
}

export function DeptPouchRequestsPanel({ productKey }: DeptPouchPanelProps) {
  const unit = deptPouchUnitLabel(productKey);
  const [batchNo, setBatchNo] = useState("");
  const [qty, setQty] = useState("50");
  const [notes, setNotes] = useState("");
  const [rows, setRows] = useState<DeptPouchRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const list = await deptPouchBarcodesApi.requestList(productKey, { status: "all", page: 1 });
      setRows(list.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load requests.");
    } finally {
      setLoading(false);
    }
  }, [productKey]);

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
      {loading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : (
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
                <th className="px-3 py-2" />
              </tr>
            </thead>
            <tbody>
              {rows.map(r => (
                <tr key={r.id} className={appHoverRow}>
                  <td className="px-3 py-2 whitespace-nowrap">{r.created_at.slice(0, 16)}</td>
                  <td className="px-3 py-2">{r.requested_by_name}</td>
                  <td className="px-3 py-2 font-mono text-xs">{r.batch_no}</td>
                  <td className="px-3 py-2">{r.quantity}</td>
                  <td className="px-3 py-2">
                    {r.scanned_count}/{r.generated_count || r.quantity}
                  </td>
                  <td className="px-3 py-2">{r.status.replace(/_/g, " ")}</td>
                  <td className="px-3 py-2">
                    {r.status === "pending" && (
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => void deptPouchBarcodesApi.requestCancel(r.id).then(refresh)}
                      >
                        Cancel
                      </Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </AppSectionEnter>
  );
}

export function DeptPouchScanInPanel({ productKey, productLabel }: DeptPouchPanelProps) {
  const unit = deptPouchUnitLabel(productKey);
  const inputRef = useRef<HTMLInputElement>(null);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "err" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [progress, setProgress] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const cameraTitleId = useId();

  useEffect(() => {
    inputRef.current?.focus();
  }, []);

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
      inputRef.current?.focus();
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
