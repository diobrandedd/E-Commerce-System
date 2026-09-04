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
import { HONEY_BOTTLE_OPTIONS, HONEY_VARIANTS } from "@/app/lib/honey";
import { triggerLiveRefresh } from "@/app/lib/liveRefresh";
import {
  bottleBarcodesApi,
  bottlePrintSheetUrl,
  type HoneyBarcodeBatch,
  type HoneyBottleRequest,
  type SkuPrintJobRow,
} from "@/app/lib/bottleBarcodes";
import { skuBarcodesApi } from "@/app/lib/skuBarcodes";
import { RequestNotesCell, RequestNotesInline } from "@/app/components/barcode/RequestNotesCell";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

export function HoneyBottleRequestsPanel({ canAdmin }: { canAdmin: boolean }) {
  const [variant, setVariant] = useState("light");
  const [size, setSize] = useState("liter");
  const [qty, setQty] = useState("50");
  const [notes, setNotes] = useState("");
  const [rows, setRows] = useState<HoneyBottleRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const list = await bottleBarcodesApi.requestList({ status: "all", page: 1 });
      setRows(list.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load requests.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const submit = async () => {
    setError(null);
    setOk(null);
    setSaving(true);
    try {
      await bottleBarcodesApi.requestCreate({
        variant,
        size,
        quantity: Number(qty),
        notes: notes.trim() || undefined,
      });
      setOk("Label request submitted.");
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
        <h3 className="text-sm font-semibold">New label request</h3>
        <p className="text-xs text-muted-foreground">
          Pick product and how many identical stickers to print. The barcode itself is set in System Settings → Barcode Settings.
          When bottles are ready, stock them in under Scan into packaging stock and enter a custom batch number there.
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <label className="text-xs text-muted-foreground">
            Variant
            <select className="mt-1 w-full border rounded-lg px-2 py-2 text-sm" value={variant} onChange={e => setVariant(e.target.value)}>
              {HONEY_VARIANTS.filter(v => v !== "other").map(v => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          </label>
          <label className="text-xs text-muted-foreground">
            Size
            <select className="mt-1 w-full border rounded-lg px-2 py-2 text-sm" value={size} onChange={e => setSize(e.target.value)}>
              {HONEY_BOTTLE_OPTIONS.map(s => (
                <option key={s.value} value={s.value}>{s.label}</option>
              ))}
            </select>
          </label>
          <label className="text-xs text-muted-foreground">
            Quantity (identical stickers)
            <input className="mt-1 w-full border rounded-lg px-2 py-2 text-sm" type="number" min={1} value={qty} onChange={e => setQty(e.target.value)} />
          </label>
        </div>
        <textarea className="w-full border rounded-lg px-2 py-2 text-sm" rows={2} placeholder="Notes" value={notes} onChange={e => setNotes(e.target.value)} />
        <Button type="button" onClick={() => void submit()} disabled={saving}>
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
                <th className="text-left px-3 py-2">Item</th>
                <th className="text-left px-3 py-2">Qty</th>
                <th className="text-left px-3 py-2">Scanned</th>
                <th className="text-left px-3 py-2">Notes</th>
                <th className="text-left px-3 py-2">Status</th>
                {canAdmin && <th className="px-3 py-2" />}
              </tr>
            </thead>
            <tbody>
              {rows.map(r => (
                <tr key={r.id} className={appHoverRow}>
                  <td className="px-3 py-2 whitespace-nowrap">{r.created_at.slice(0, 16)}</td>
                  <td className="px-3 py-2">{r.requested_by_name}</td>
                  <td className="px-3 py-2 capitalize">{r.variant} {r.size_label}</td>
                  <td className="px-3 py-2">{r.quantity}</td>
                  <td className="px-3 py-2">{r.scanned_count}/{r.generated_count || r.quantity}</td>
                  <RequestNotesCell notes={r.notes} />
                  <td className="px-3 py-2">{r.status.replace(/_/g, " ")}</td>
                  {canAdmin && (
                    <td className="px-3 py-2">
                      {r.status === "pending" && (
                        <Button type="button" size="sm" variant="outline" onClick={() => void bottleBarcodesApi.requestCancel(r.id).then(refresh)}>
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
      )}
    </AppSectionEnter>
  );
}

export function HoneyBottleScanInPanel() {
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "err" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [progress, setProgress] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [skuQty, setSkuQty] = useState("1");
  const [customBatchNo, setCustomBatchNo] = useState("");
  const [lastSku, setLastSku] = useState<{ code: string; label: string } | null>(null);
  const cameraTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen);

  const commitStockIn = async (
    value: string,
    opts?: { qty?: number; packaging_row_id?: number; batch_no?: string },
  ): Promise<ScannerDecodeFeedback | void> => {
    try {
      const res = await bottleBarcodesApi.scanStockIn(value, opts);
      playScanBeep("ok");
      setFlash("ok");
      const qtyBit = res.qty ? ` ×${res.qty}` : "";
      setMsg(`${res.variant} ${res.size_label} · ${res.code}${qtyBit}`);
      if (res.request) {
        setProgress(`${res.request.scanned_count} / ${res.request.generated_count || res.request.quantity} scanned`);
      }
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return { kind: "success", message: `Stocked in: ${res.variant} ${res.size_label} · ${res.code}${qtyBit}` };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      const isDup = /already|duplicate/i.test(reason);
      playScanBeep(isDup ? "dup" : "bad");
      setFlash("err");
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return {
        kind: isDup ? "duplicate" : "error",
        message: reason,
      };
    }
  };

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const { lookup } = await skuBarcodesApi.lookup(value);
      if (lookup.lookup_kind === "sku" && lookup.kind === "honey") {
        if (!customBatchNo.trim()) {
          playScanBeep("bad");
          setFlash("err");
          setMsg("Enter a custom batch number before scanning SKU barcodes.");
          window.setTimeout(() => setFlash(null), 700);
          refocus();
          return { kind: "error", message: "Custom batch number required for SKU stock-in." };
        }
        const label = lookup.label ?? "Honey SKU";
        setLastSku({ code: value, label });
        return commitStockIn(value, { qty: 1, batch_no: customBatchNo.trim() });
      }
    } catch {
      // fall through to unique scan
    }
    return commitStockIn(value);
  };

  const addSkuBulk = async () => {
    if (!lastSku) {
      setMsg("Scan a SKU barcode first, then use Quantity + Stock in for bulk.");
      refocus();
      return;
    }
    const qty = Number(skuQty);
    if (!Number.isFinite(qty) || qty < 1) {
      setMsg("Enter a quantity of at least 1.");
      return;
    }
    if (!customBatchNo.trim()) {
      setMsg("Enter a custom batch number for SKU stock-in.");
      return;
    }
    await commitStockIn(lastSku.code, { qty, batch_no: customBatchNo.trim() });
  };

  return (
    <AppSectionEnter className="space-y-4">
      <div className="bg-card rounded-xl border border-border p-4 space-y-3">
        <h3 className="text-sm font-semibold">Scan into packaging stock</h3>
        <p className="text-xs text-muted-foreground">
          Set a custom batch number first. SKU barcodes: each scan stocks in +1, or type a quantity and Stock in
          for bulk. Leftover unique stickers still scan one at a time.
        </p>
        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
          Custom batch number (for SKU stock-in)
          <input
            className="mt-1 w-full border rounded-lg px-3 py-2 text-sm uppercase"
            value={customBatchNo}
            onChange={e => setCustomBatchNo(e.target.value.toUpperCase())}
            placeholder="e.g. NF082826"
            autoComplete="off"
          />
        </label>
        <div className="flex gap-2">
          <input
            ref={inputRef}
            {...inputProps}
            className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide"
            placeholder="Scan barcode or type it…"
            value={code}
            onChange={e => setCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void onScan();
              }
            }}
          />
          <Button type="button" variant="outline" className="gap-1.5 shrink-0" onClick={() => setCameraOpen(true)}>
            <Camera size={16} /> Camera
          </Button>
        </div>
        <div className="rounded-lg border border-border bg-secondary/20 p-3 space-y-2">
          <p className="text-xs text-muted-foreground">
            Bulk SKU add{lastSku ? ` — last: ${lastSku.label}` : " — scan a SKU first"}
          </p>
          <div className="flex gap-2 items-end">
            <div className="w-24">
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
                    void addSkuBulk();
                  }
                }}
              />
            </div>
            <Button type="button" onClick={() => void addSkuBulk()} disabled={!lastSku}>
              Stock in
            </Button>
          </div>
        </div>
        <div className={`h-16 rounded-xl border flex items-center justify-center text-sm font-medium ${
          flash === "ok" ? "bg-green-100 border-green-300 text-green-800" : flash === "err" ? "bg-red-100 border-red-300 text-red-800" : "bg-secondary/30 border-border text-muted-foreground"
        }`}>
          {flash === "ok" ? "Scanned" : flash === "err" ? "Not accepted" : "Ready"}
        </div>
        {msg && <p className="text-sm">{msg}</p>}
        {progress && <p className="text-lg font-bold">{progress}</p>}
      </div>
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan bottle barcode"
          titleId={cameraTitleId}
          continuous
          scanPrompt="Point at each bottle barcode. Keep scanning until stock-in is complete."
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </AppSectionEnter>
  );
}

export function HoneyBottleAdminPanel() {
  const [pending, setPending] = useState<HoneyBottleRequest[]>([]);
  const [batches, setBatches] = useState<HoneyBarcodeBatch[]>([]);
  const [skuPrints, setSkuPrints] = useState<SkuPrintJobRow[]>([]);
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [drift, setDrift] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    try {
      const [list, batchList, printList] = await Promise.all([
        bottleBarcodesApi.requestList({ status: "pending" }),
        bottleBarcodesApi.batchList(),
        bottleBarcodesApi.skuPrintList(),
      ]);
      setPending(list.rows);
      setBatches(batchList.rows);
      setSkuPrints(printList.rows);
      setSelected(new Set(list.rows.map(r => r.id)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load queue.");
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const generate = async (req: HoneyBottleRequest) => {
    await printBatch([req.id]);
  };

  const printBatch = async (ids: number[]) => {
    if (ids.length === 0) return;
    setBusy(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.generateBatch(ids);
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

  const backfill = async () => {
    setBusy(true);
    setError(null);
    try {
      const preview = await bottleBarcodesApi.backfillPreview();
      if ((preview.fractions as unknown[]).length > 0) {
        setError("Fractional bottle quantities exist — correct those before backfill.");
        return;
      }
      const res = await bottleBarcodesApi.backfillGenerate();
      setOk(`Created ${res.count} backfill batches. Print them, sticker, then scan-in (adopt).`);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Backfill failed.");
    } finally {
      setBusy(false);
    }
  };

  const reconcile = async () => {
    try {
      const res = await bottleBarcodesApi.reconcile();
      setDrift(res.drifted);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Reconcile failed.");
    }
  };

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="outline" size="sm" onClick={() => void backfill()} disabled={busy}>
          Backfill existing stock
        </Button>
        <Button type="button" variant="outline" size="sm" onClick={() => void reconcile()}>
          Reconcile units vs aggregate
        </Button>
        {drift !== null && <span className="text-sm self-center">{drift} drifted rows</span>}
      </div>
      <p className="text-xs text-muted-foreground">
        New prints use permanent SKU barcodes from Settings. Print at 100% scale on A4 (8×20). Legacy unique batches remain below for old stickers.
      </p>
      <h3 className="text-sm font-semibold">Pending label requests</h3>
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
            <span className="text-xs text-muted-foreground">8×20 grid · barcode only · BATCH headers</span>
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
                    aria-label={`Select ${r.variant} ${r.size_label}`}
                  />
                </td>
                <td className="px-3 py-2 capitalize">
                  {r.variant} {r.size_label} ×{r.quantity}
                  {r.batch_no ? (
                    <span className="ml-2 font-mono text-xs text-muted-foreground">· {r.batch_no}</span>
                  ) : null}
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
              <tr><td className="px-3 py-4 text-muted-foreground" colSpan={3}>No pending requests.</td></tr>
            )}
          </tbody>
        </table>
      </div>
      <h3 className="text-sm font-semibold">Recent SKU prints</h3>
      <ul className="space-y-2">
        {skuPrints.slice(0, 25).map(p => (
          <li key={p.id} className="flex items-center justify-between gap-2 bg-card border rounded-xl px-3 py-2">
            <span className="text-sm">
              {p.label} · <span className="font-mono text-xs">{p.sku_code}</span> ×{p.quantity}
              <span className="ml-2 text-xs text-muted-foreground">{p.printed_at.slice(0, 16)} · {p.printed_by_name}</span>
            </span>
            <Button type="button" size="sm" variant="outline" onClick={() => window.open(p.print_url, "_blank")}>
              <Printer size={14} /> Reprint
            </Button>
          </li>
        ))}
        {skuPrints.length === 0 && (
          <li className="text-sm text-muted-foreground px-3 py-2">No SKU print jobs yet.</li>
        )}
      </ul>
      <h3 className="text-sm font-semibold">Recent unique batches (legacy)</h3>
      <ul className="space-y-2">
        {batches.slice(0, 20).map(b => (
          <li key={b.id} className="flex items-center justify-between gap-2 bg-card border rounded-xl px-3 py-2">
            <span className="text-sm">{b.batch_no} · {b.variant} {b.size_label} · {b.scanned_count}/{b.quantity_generated}</span>
            <Button type="button" size="sm" variant="outline" onClick={() => window.open(bottlePrintSheetUrl(b.id), "_blank")}>
              <Printer size={14} /> Print A4
            </Button>
          </li>
        ))}
      </ul>
    </AppSectionEnter>
  );
}
