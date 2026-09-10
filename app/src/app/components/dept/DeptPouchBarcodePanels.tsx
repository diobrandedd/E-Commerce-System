import { useCallback, useEffect, useId, useState } from "react";
import { Camera, Loader2, Printer } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import {
  AppErrorBanner,
  AppPressable,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import { triggerLiveRefresh } from "@/app/lib/liveRefresh";
import {
  DEPT_POUCH_BARCODE_KEYS,
  deptPouchBarcodesApi,
  deptPouchHistoryLabel,
  deptPouchPrintSheetUrl,
  deptPouchUnitLabel,
  type DeptPouchBatch,
  type DeptPouchRequest,
  type DeptScanByScanner,
} from "@/app/lib/deptPouchBarcodes";
import { bottleBarcodesApi } from "@/app/lib/bottleBarcodes";
import { skuBarcodesApi } from "@/app/lib/skuBarcodes";
import { RequestNotesCell, RequestNotesInline } from "@/app/components/barcode/RequestNotesCell";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import { DeptPouchScanLogModal } from "@/app/components/dept/DeptPouchScanLogModal";
import { PackagingScanLogModal } from "@/app/components/honey/PackagingScanLogModal";
import { variantLabel } from "@/app/lib/posHoney";

interface DeptPouchPanelProps {
  productKey: string;
  productLabel: string;
  /** history = list only (no new-request form) for staff discoverability */
  mode?: "full" | "history";
}

type HistoryViewKey = (typeof DEPT_POUCH_BARCODE_KEYS)[number] | "honey_bottles";

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

function ByScannerList({
  rows,
  unitPlural,
}: {
  rows: DeptScanByScanner[];
  unitPlural: string;
}) {
  if (rows.length === 0) return null;
  return (
    <div className="rounded-xl border border-border bg-secondary/20 px-4 py-3">
      <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
        By person · all accounts
      </p>
      <ul className="mt-2 space-y-1.5">
        {rows.map(row => (
          <li
            key={`${row.user_id ?? "unk"}-${row.name}`}
            className="flex items-baseline justify-between gap-3 text-sm"
          >
            <span className="font-medium truncate">{row.name}</span>
            <span className="tabular-nums shrink-0 text-muted-foreground">
              {row.qty.toLocaleString()} {unitPlural}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function HoneyBottlesHistoryBlock({ scanDate, onScanDateChange }: {
  scanDate: string;
  onScanDateChange: (next: string) => void;
}) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dates, setDates] = useState<string[]>([]);
  const [total, setTotal] = useState(0);
  const [byScanner, setByScanner] = useState<DeptScanByScanner[]>([]);
  const [rows, setRows] = useState<
    Awaited<ReturnType<typeof bottleBarcodesApi.packagingScanReport>>["rows"]
  >([]);
  const [logRow, setLogRow] = useState<(typeof rows)[number] | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await bottleBarcodesApi.packagingScanReport(scanDate);
      setDates(data.dates);
      setTotal(data.totals?.total ?? 0);
      setByScanner(data.by_scanner ?? []);
      setRows(data.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load honey bottle scans.");
      setRows([]);
      setByScanner([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [scanDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return (
    <div className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h3 className="text-sm font-semibold">Scanned into inventory</h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            Honey bottles — all scanners. Gross scans only.
          </p>
        </div>
        <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold shrink-0">
          Scan date
          <select
            value={scanDate}
            onChange={e => onScanDateChange(e.target.value)}
            className="mt-1 block min-w-[10.5rem] rounded-lg border border-border bg-card px-2.5 py-2 text-sm normal-case"
          >
            <option value={localToday()}>Today · {scanDateLabel(localToday())}</option>
            {dates
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
            {total.toLocaleString()}{" "}
            <span className="text-sm font-medium text-muted-foreground">bottles</span>
          </p>
        </div>
      )}
      <ByScannerList rows={byScanner} unitPlural="bottles" />
      {loading ? (
        <p className="text-sm text-muted-foreground">Loading…</p>
      ) : rows.length === 0 ? (
        <p className="text-sm text-muted-foreground py-6 text-center">
          No bottles scanned into inventory {scanDate === "all" ? "yet." : `on ${scanDate}.`}
        </p>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-border">
          <table className="w-full text-sm">
            <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
              <tr>
                <th className="text-left px-3 py-2">Batch</th>
                <th className="text-left px-3 py-2">Variant</th>
                <th className="text-left px-3 py-2">1L</th>
                <th className="text-left px-3 py-2">250</th>
                <th className="text-left px-3 py-2">350</th>
                <th className="text-left px-3 py-2">Gal</th>
              </tr>
            </thead>
            <tbody>
              {rows.slice(0, 40).map(r => (
                <tr
                  key={r.id}
                  className={`${appHoverRow} cursor-pointer`}
                  onClick={() => setLogRow(r)}
                >
                  <td className="px-3 py-2 font-mono text-xs whitespace-nowrap">{r.batch_no}</td>
                  <td className="px-3 py-2 capitalize">{variantLabel(r.variant)}</td>
                  <td className="px-3 py-2">{Number(r.scanned_liter).toLocaleString()}</td>
                  <td className="px-3 py-2">{Number(r.scanned_250ml).toLocaleString()}</td>
                  <td className="px-3 py-2">{Number(r.scanned_350ml).toLocaleString()}</td>
                  <td className="px-3 py-2">{Number(r.scanned_gallon).toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      {logRow && (
        <PackagingScanLogModal
          packagingRowId={logRow.id}
          batchNo={logRow.batch_no}
          variant={logRow.variant}
          scanDate={scanDate}
          onClose={() => setLogRow(null)}
        />
      )}
    </div>
  );
}

export function DeptPouchRequestsPanel({ productKey, mode = "full" }: DeptPouchPanelProps) {
  const historyOnly = mode === "history";
  const [viewKey, setViewKey] = useState<HistoryViewKey>(
    () => (DEPT_POUCH_BARCODE_KEYS as readonly string[]).includes(productKey)
      ? (productKey as HistoryViewKey)
      : "pt",
  );
  const activeProductKey = viewKey === "honey_bottles" ? productKey : viewKey;
  const unit = deptPouchUnitLabel(viewKey === "honey_bottles" ? "pt" : viewKey);
  const [qty, setQty] = useState("50");
  const [notes, setNotes] = useState("");
  const [rows, setRows] = useState<DeptPouchRequest[]>([]);
  const [scanDate, setScanDate] = useState(localToday);
  const [scanDates, setScanDates] = useState<string[]>([]);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [scannedTotal, setScannedTotal] = useState(0);
  const [byScanner, setByScanner] = useState<DeptScanByScanner[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [logRequest, setLogRequest] = useState<DeptPouchRequest | null>(null);

  useEffect(() => {
    if (!historyOnly) {
      setViewKey(
        (DEPT_POUCH_BARCODE_KEYS as readonly string[]).includes(productKey)
          ? (productKey as HistoryViewKey)
          : "pt",
      );
    }
  }, [historyOnly, productKey]);

  const refresh = useCallback(async () => {
    if (viewKey === "honey_bottles") {
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const list = await deptPouchBarcodesApi.scanReport(viewKey, scanDate, page);
      setRows(list.rows);
      setScanDates(list.dates);
      setTotal(list.total);
      setScannedTotal(list.scanned_total ?? 0);
      setByScanner(list.by_scanner ?? []);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load requests.");
    } finally {
      setLoading(false);
    }
  }, [page, scanDate, viewKey]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const submit = async () => {
    setError(null);
    setOk(null);
    setSaving(true);
    try {
      await deptPouchBarcodesApi.requestCreate(productKey, {
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

  const switchHistoryView = (next: HistoryViewKey) => {
    setViewKey(next);
    setPage(1);
    setLogRequest(null);
    setError(null);
  };

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
      {historyOnly && (
        <div className="flex flex-wrap gap-1.5">
          {DEPT_POUCH_BARCODE_KEYS.map(key => (
            <AppPressable
              key={key}
              type="button"
              onClick={() => switchHistoryView(key)}
              className={`rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors ${
                viewKey === key
                  ? "border-foreground bg-foreground text-background"
                  : "border-border bg-card text-muted-foreground hover:text-foreground"
              }`}
            >
              {deptPouchHistoryLabel(key)}
            </AppPressable>
          ))}
          <AppPressable
            type="button"
            onClick={() => switchHistoryView("honey_bottles")}
            className={`rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors ${
              viewKey === "honey_bottles"
                ? "border-foreground bg-foreground text-background"
                : "border-border bg-card text-muted-foreground hover:text-foreground"
            }`}
          >
            Honey
          </AppPressable>
        </div>
      )}
      {historyOnly && viewKey === "honey_bottles" ? (
        <HoneyBottlesHistoryBlock
          scanDate={scanDate}
          onScanDateChange={next => {
            setScanDate(next);
            setPage(1);
          }}
        />
      ) : (
        <>
      {!historyOnly && (
        <div className="bg-card rounded-xl border border-border p-4 space-y-3">
          <h3 className="text-sm font-semibold">New {unit.singular} label request</h3>
          <p className="text-xs text-muted-foreground">
            Pick how many identical stickers to print. The barcode is set in System Settings → Barcode Settings.
            Stock-in uses the same SKU scan later — no batch code needed here.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label className="text-xs text-muted-foreground sm:col-span-2">
              Quantity ({unit.plural} / identical stickers)
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
          <Button type="button" className="min-h-11" onClick={() => void submit()} disabled={saving}>
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
            {historyOnly
              ? " Includes every account that scanned this product (not only yours)."
              : ""}
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
            {historyOnly ? ` · ${deptPouchHistoryLabel(viewKey)}` : ""}
          </p>
          <p className="text-2xl font-bold mt-1">
            {scannedTotal.toLocaleString()}{" "}
            <span className="text-sm font-medium text-muted-foreground">
              {scannedTotal === 1 ? unit.singular : unit.plural}
            </span>
          </p>
        </div>
      )}
      <ByScannerList rows={byScanner} unitPlural={unit.plural} />
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
                  <th className="text-left px-3 py-2">Requested by</th>
                  <th className="text-left px-3 py-2">Batch</th>
                  <th className="text-left px-3 py-2">Qty</th>
                  <th className="text-left px-3 py-2">Scanned</th>
                  <th className="text-left px-3 py-2">Notes</th>
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
                    <RequestNotesCell notes={r.notes} />
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
          productKey={activeProductKey}
          scanDate={scanDate}
          onClose={() => setLogRequest(null)}
        />
      )}
        </>
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
  const [pendingSku, setPendingSku] = useState<{ code: string; label: string } | null>(null);
  const [skuQty, setSkuQty] = useState("1");
  const cameraTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen || !!pendingSku, productKey);

  const commit = async (value: string, qty?: number): Promise<ScannerDecodeFeedback | void> => {
    try {
      const res = await deptPouchBarcodesApi.scanStockIn(productKey, value, qty);
      playScanBeep("ok");
      setFlash("ok");
      const qtyBit = res.qty ? ` ×${res.qty}` : "";
      const poolWarn = res.bag_pool?.warned ? ` · ${res.bag_pool.warning ?? "Bag pool short"}` : "";
      const poolLeft =
        res.bag_pool != null
          ? ` · pool ${res.bag_pool.grams_remaining.toLocaleString()} g`
          : "";
      setMsg(`${res.batch_no ?? productLabel} · ${res.code}${qtyBit}${poolLeft}${poolWarn}`);
      if (res.bag_pool?.warned) {
        setFlash("err");
      }
      if (res.request) {
        setProgress(
          `${res.request.scanned_count} / ${res.request.generated_count || res.request.quantity} scanned`,
        );
      }
      window.setTimeout(() => setFlash(null), res.bag_pool?.warned ? 1600 : 700);
      triggerLiveRefresh();
      return { kind: "success", message: `Stocked ${res.code}${qtyBit}` };
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

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const { lookup } = await skuBarcodesApi.lookup(value);
      if (lookup.lookup_kind === "sku" && lookup.kind === "dept") {
        setPendingSku({ code: value, label: lookup.label ?? productLabel });
        setSkuQty("1");
        return { kind: "success", message: `SKU — enter qty` };
      }
    } catch {
      // unique leftover
    }
    return commit(value);
  };

  const confirmSku = async () => {
    if (!pendingSku) return;
    const qty = Number(skuQty);
    if (!Number.isFinite(qty) || qty < 1) {
      setMsg("Enter a quantity of at least 1.");
      return;
    }
    const c = pendingSku.code;
    setPendingSku(null);
    await commit(c, qty);
  };

  return (
    <AppSectionEnter className="space-y-4">
      <p className="text-sm text-muted-foreground">
        {productKey === "pt"
          ? "Each pouch scan adds finished stock and deducts leaf from the bag pool (Settings BOM). Short pool warns but still stocks in."
          : `SKU barcodes: scan → enter how many into finished ${productLabel}. Leftover unique stickers still scan one at a time.`}
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
      {pendingSku ? (
        <div className="rounded-lg border border-[#C8900A]/40 bg-[#C8900A]/5 p-3 space-y-2">
          <p className="text-sm font-medium">{pendingSku.label}</p>
          <div className="flex gap-2 items-end">
            <div className="flex-1">
              <label className="text-xs text-muted-foreground">Quantity</label>
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                type="number"
                min={1}
                value={skuQty}
                onChange={e => setSkuQty(e.target.value)}
                onKeyDown={e => {
                  if (e.key === "Enter") {
                    e.preventDefault();
                    void confirmSku();
                  }
                }}
              />
            </div>
            <Button type="button" onClick={() => void confirmSku()}>Stock in</Button>
            <Button type="button" variant="outline" onClick={() => setPendingSku(null)}>Cancel</Button>
          </div>
        </div>
      ) : null}
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
  const [pending, setPending] = useState<DeptPouchRequest[]>([]);
  const [batches, setBatches] = useState<DeptPouchBatch[]>([]);
  const [selected, setSelected] = useState<Set<number>>(new Set());
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
      setSelected(new Set(list.rows.map(r => r.id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load queue.");
    }
  }, [productKey]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const generate = async (req: DeptPouchRequest) => {
    await printBatch([req.id]);
  };

  const printBatch = async (ids: number[]) => {
    if (ids.length === 0) return;
    setBusy(true);
    setError(null);
    try {
      const res = await deptPouchBarcodesApi.generateBatch(productKey, ids);
      setOk(res.message ?? `Combined sheet — ${res.batch_count} batch(es).`);
      if (res.print_url) {
        window.open(res.print_url, "_blank");
      }
      triggerLiveRefresh();
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Print failed.");
    } finally {
      setBusy(false);
    }
  };

  const toggleSelected = (id: number, on: boolean) => {
    setSelected(prev => {
      const next = new Set(prev);
      if (on) next.add(id);
      else next.delete(id);
      return next;
    });
  };

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
      <p className="text-xs text-muted-foreground">
        New prints use the permanent SKU barcode from Settings (identical copies). Set the code in System Settings → Barcode Settings before generating. Print at 100% scale. Leftover unique batches below can still be reprinted.
      </p>
      <div className="overflow-x-auto rounded-xl border border-border">
        {pending.length > 0 ? (
          <div className="px-3 py-2 border-b border-border flex flex-wrap gap-2 items-center bg-secondary/20">
            <Button
              type="button"
              size="sm"
              disabled={busy}
              onClick={() => void printBatch(pending.map(r => r.id))}
            >
              Print all pending (one sheet)
            </Button>
            <Button
              type="button"
              size="sm"
              variant="outline"
              disabled={busy || selected.size === 0}
              onClick={() => void printBatch([...selected])}
            >
              Print selected ({selected.size})
            </Button>
          </div>
        ) : null}
        <table className="w-full text-sm">
          <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
            <tr>
              <th className="px-3 py-2 w-8">
                <input
                  type="checkbox"
                  aria-label="Select all pending"
                  checked={pending.length > 0 && selected.size === pending.length}
                  onChange={e => {
                    if (e.target.checked) setSelected(new Set(pending.map(r => r.id)));
                    else setSelected(new Set());
                  }}
                />
              </th>
              <th className="text-left px-3 py-2">Request</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {pending.map(r => (
              <tr key={r.id} className={appHoverRow}>
                <td className="px-3 py-2">
                  <input
                    type="checkbox"
                    checked={selected.has(r.id)}
                    onChange={e => toggleSelected(r.id, e.target.checked)}
                    aria-label={`Select request ${r.id}`}
                  />
                </td>
                <td className="px-3 py-2">
                  ×{r.quantity} stickers
                  <RequestNotesInline notes={r.notes} />
                </td>
                <td className="px-3 py-2">
                  <Button type="button" size="sm" disabled={busy} onClick={() => void generate(r)}>
                    Print
                  </Button>
                </td>
              </tr>
            ))}
            {pending.length === 0 && (
              <tr>
                <td className="px-3 py-4 text-muted-foreground" colSpan={3}>
                  No pending requests.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      <h3 className="text-sm font-semibold">Recent unique batches (legacy)</h3>
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
