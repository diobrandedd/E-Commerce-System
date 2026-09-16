import { useCallback, useEffect, useId, useRef, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { Camera, Loader2, Printer, X } from "lucide-react";
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
import { ApiError, apiGet } from "@/app/lib/api";
import { HONEY_BOTTLE_OPTIONS, HONEY_VARIANTS, DEFAULT_ML_PER_US_GALLON, formatGallons, linesToGallons, type HoneyBottleSize } from "@/app/lib/honey";
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
          When bottles are ready, stock them in from Production → scan stock-in (deducts production gallons).
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
        <Button type="button" className="min-h-11" onClick={() => void submit()} disabled={saving}>
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

export type ProductionLotOption = {
  id: number;
  supplier_code: string;
  variant: string;
  current_qty: string | number;
  initial_qty?: string | number;
  arrival_date?: string;
  stock_out_date?: string;
  is_active?: number | boolean;
};

type PendingScan = {
  code: string;
  label: string;
  variant: string;
  size: HoneyBottleSize;
  kind: "sku" | "unique";
};

function isScanSelectableProductionLot(lot: ProductionLotOption): boolean {
  if (lot.is_active === 0 || lot.is_active === false) return false;
  const qty = Number(lot.current_qty);
  if (!Number.isFinite(qty)) return false;
  // Hide exact OOS (0). Overdrawn (negative) stays selectable so ops can finish bottling.
  return Math.abs(qty) > 1e-9;
}

export function HoneyProductionScanStockInPanel({
  lots,
  onStocked,
}: {
  lots: ProductionLotOption[];
  onStocked?: () => void;
}) {
  const [productionRowId, setProductionRowId] = useState<number>(0);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "err" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [warn, setWarn] = useState<string | null>(null);
  const [progress, setProgress] = useState<string | null>(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [qty, setQty] = useState("1");
  const [pending, setPending] = useState<PendingScan | null>(null);
  const [saving, setSaving] = useState(false);
  const [mlPerGallon, setMlPerGallon] = useState(DEFAULT_ML_PER_US_GALLON);
  const cameraTitleId = useId();
  const qtyInputRef = useRef<HTMLInputElement>(null);
  const reduceMotion = useReducedMotion();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen);

  const openLots = lots.filter(isScanSelectableProductionLot);
  const selected = openLots.find(l => l.id === productionRowId) ?? null;

  useEffect(() => {
    if (productionRowId > 0 && !openLots.some(l => l.id === productionRowId)) {
      setProductionRowId(0);
    }
  }, [openLots, productionRowId]);

  useEffect(() => {
    void (async () => {
      try {
        const data = await apiGet<{ ml_per_gallon: number }>("settings.php", "honey_volume_get");
        if (Number.isFinite(data.ml_per_gallon) && data.ml_per_gallon > 0) {
          setMlPerGallon(data.ml_per_gallon);
        }
      } catch {
        /* keep default */
      }
    })();
  }, []);

  useEffect(() => {
    if (!pending) return;
    const t = window.setTimeout(() => {
      qtyInputRef.current?.focus();
      qtyInputRef.current?.select();
    }, 40);
    return () => window.clearTimeout(t);
  }, [pending]);

  const previewGallons = (() => {
    if (!pending || pending.kind !== "sku") return 0;
    const n = Number(qty);
    if (!Number.isFinite(n) || n < 1) return 0;
    return linesToGallons([{ size: pending.size, qty: String(n) }], mlPerGallon);
  })();

  const clearPending = () => {
    setPending(null);
    setQty("1");
  };

  const commitStockIn = async (
    value: string,
    opts: { qty: number },
  ): Promise<ScannerDecodeFeedback | void> => {
    if (productionRowId < 1) {
      playScanBeep("bad");
      setFlash("err");
      setWarn(null);
      setMsg("Select a production lot before stocking in.");
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return { kind: "error", message: "Select a production lot first." };
    }
    setSaving(true);
    try {
      const res = await bottleBarcodesApi.scanStockIn(value, {
        qty: opts.qty,
        production_row_id: productionRowId,
      });
      playScanBeep("ok");
      setFlash("ok");
      clearPending();
      const qtyBit = res.qty ? ` ×${res.qty}` : "";
      const remain = res.remaining_production_gallons;
      const galBit =
        res.used_gallons != null
          ? ` · used ${formatGallons(res.used_gallons)} gal (${formatGallons(remain ?? 0)} remain)`
          : "";
      setMsg(`${res.variant} ${res.size_label} · ${res.code}${qtyBit}${galBit}`);
      setWarn(
        res.production_overdrawn || res.warning
          ? res.warning ||
              `Lot overdrawn — ${formatGallons(Math.abs(remain ?? 0))} gal short.`
          : null,
      );
      if (res.request) {
        setProgress(`${res.request.scanned_count} / ${res.request.generated_count || res.request.quantity} scanned`);
      }
      if (res.ml_per_gallon != null && Number.isFinite(res.ml_per_gallon)) {
        setMlPerGallon(res.ml_per_gallon);
      }
      window.setTimeout(() => setFlash(null), 700);
      onStocked?.();
      triggerLiveRefresh();
      refocus();
      return {
        kind: "success",
        message: `Stocked in: ${res.variant} ${res.size_label} · ${res.code}${qtyBit}`,
      };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      const isDup = /already|duplicate/i.test(reason);
      playScanBeep(isDup ? "dup" : "bad");
      setFlash("err");
      setWarn(null);
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return { kind: isDup ? "duplicate" : "error", message: reason };
    } finally {
      setSaving(false);
    }
  };

  const confirmPending = async (): Promise<ScannerDecodeFeedback | void> => {
    if (!pending) {
      setMsg("Scan a barcode first.");
      refocus();
      return;
    }
    if (pending.kind === "unique") {
      return commitStockIn(pending.code, { qty: 1 });
    }
    const n = Number(qty);
    if (!Number.isFinite(n) || n < 1) {
      setFlash("err");
      setMsg("Enter how many bottles to stock in.");
      window.setTimeout(() => setFlash(null), 700);
      qtyInputRef.current?.focus();
      return { kind: "error", message: "Enter quantity." };
    }
    return commitStockIn(pending.code, { qty: Math.floor(n) });
  };

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    if (productionRowId < 1) {
      playScanBeep("bad");
      setFlash("err");
      setMsg("Select a production lot before scanning.");
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return { kind: "error", message: "Select a production lot first." };
    }
    try {
      const { lookup } = await skuBarcodesApi.lookup(value);
      if (lookup.lookup_kind === "sku" && lookup.kind === "honey") {
        const variant = String(lookup.variant ?? "");
        const size = String(lookup.size ?? "") as HoneyBottleSize;
        if (selected && variant && variant !== selected.variant) {
          playScanBeep("bad");
          setFlash("err");
          setMsg(`SKU is ${variant}; selected lot is ${selected.variant}.`);
          window.setTimeout(() => setFlash(null), 700);
          refocus();
          return { kind: "error", message: "Variant mismatch." };
        }
        playScanBeep("ok");
        setFlash("ok");
        setWarn(null);
        setMsg(null);
        setQty("1");
        setPending({
          code: value,
          label: lookup.label ?? "Honey SKU",
          variant,
          size,
          kind: "sku",
        });
        setCameraOpen(false);
        window.setTimeout(() => setFlash(null), 700);
        return { kind: "success", message: "Enter quantity, then Stock in." };
      }
    } catch {
      // fall through to unique sticker
    }
    try {
      const { barcode } = await bottleBarcodesApi.lookup(value);
      playScanBeep("ok");
      setFlash("ok");
      setWarn(null);
      setMsg(null);
      setQty("1");
      setPending({
        code: value,
        label: `${barcode.variant} ${barcode.size_label ?? barcode.size}`,
        variant: String(barcode.variant ?? ""),
        size: String(barcode.size ?? "liter") as HoneyBottleSize,
        kind: "unique",
      });
      setCameraOpen(false);
      window.setTimeout(() => setFlash(null), 700);
      return { kind: "success", message: "Confirm Stock in for this bottle." };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Unknown barcode.";
      playScanBeep("bad");
      setFlash("err");
      setWarn(null);
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 700);
      refocus();
      return { kind: "error", message: reason };
    }
  };

  const scanDisabled = openLots.length === 0 || saving || !!pending;

  return (
    <AppSectionEnter className="space-y-4">
      <div className="bg-card rounded-xl border border-border p-4 space-y-3 shadow-sm shadow-black/[0.03]">
        <h3 className="text-sm font-semibold">Scan bottles into packaging (from production)</h3>
        <p className="text-xs text-muted-foreground">
          Pick an active lot with gallons left (or overdrawn), scan a bottle or SKU barcode, enter how many, then Stock
          in. Out-of-stock and inactive lots are hidden here. Deducts production gallons using Settings → Honey volume.
          Same supplier code + arrival + variant share one production lot — later transfers refill that lot.
        </p>
        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide block">
          Production lot
          <select
            className="mt-1 w-full border rounded-lg px-3 py-2 text-sm bg-background"
            value={productionRowId || ""}
            onChange={e => {
              setProductionRowId(Number(e.target.value) || 0);
              clearPending();
            }}
            disabled={saving}
          >
            <option value="">Select lot…</option>
            {openLots.map(l => {
              const lotQty = Number(l.current_qty);
              const over = Number.isFinite(lotQty) && lotQty < -1e-9;
              const when = l.stock_out_date || l.arrival_date || "";
              return (
                <option key={l.id} value={l.id}>
                  {l.supplier_code} · {l.variant}
                  {when ? ` · ${when}` : ""} ·{" "}
                  {over
                    ? `${formatGallons(Math.abs(lotQty))} gal overdrawn`
                    : `${formatGallons(lotQty)} gal left`}
                </option>
              );
            })}
          </select>
        </label>
        {openLots.length === 0 && (
          <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            {lots.length === 0
              ? "No production lots yet. Stock out from Warehouse first."
              : "No active lots with gallons available. Out-of-stock and inactive lots are hidden from this list."}
          </p>
        )}
        <div className="flex gap-2">
          <input
            ref={inputRef}
            {...inputProps}
            className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide disabled:opacity-60"
            placeholder={pending ? "Clear below to scan another…" : "Scan barcode or type it…"}
            value={code}
            onChange={e => setCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void onScan();
              }
            }}
            disabled={scanDisabled || openLots.length === 0}
          />
          <Button
            type="button"
            variant="outline"
            className="min-h-11 gap-1.5 shrink-0"
            onClick={() => setCameraOpen(true)}
            disabled={scanDisabled || openLots.length === 0}
          >
            <Camera size={16} /> Camera
          </Button>
        </div>

        <AnimatePresence initial={false} mode="wait">
          {pending ? (
            <motion.div
              key={pending.code}
              initial={reduceMotion ? false : { opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              exit={reduceMotion ? undefined : { opacity: 0, y: -6 }}
              transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
              className="rounded-xl border border-[#C8900A]/35 bg-[#C8900A]/[0.06] px-3 py-3 space-y-3"
              data-testid="honey-production-qty-step"
            >
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                  <p className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                    {pending.kind === "sku" ? "Scanned SKU" : "Scanned bottle"}
                  </p>
                  <p className="text-sm font-semibold text-foreground truncate">{pending.label}</p>
                  <p className="text-xs font-mono text-muted-foreground truncate">{pending.code}</p>
                </div>
                <AppPressable
                  type="button"
                  variant="ghost"
                  className="shrink-0 min-h-9 px-2 text-muted-foreground"
                  onClick={() => {
                    clearPending();
                    refocus();
                  }}
                  disabled={saving}
                >
                  <X size={16} aria-hidden />
                  <span className="sr-only">Clear scan</span>
                </AppPressable>
              </div>
              {pending.kind === "sku" ? (
                <div className="flex flex-wrap items-end gap-2">
                  <div className="w-28">
                    <label htmlFor="honey-prod-scan-qty" className="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                      Quantity
                    </label>
                    <input
                      id="honey-prod-scan-qty"
                      ref={qtyInputRef}
                      className="mt-1 w-full border border-border rounded-lg px-3 py-2.5 text-base tabular-nums bg-background"
                      type="number"
                      min={1}
                      step={1}
                      inputMode="numeric"
                      value={qty}
                      onChange={e => setQty(e.target.value)}
                      onKeyDown={e => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          void confirmPending();
                        }
                      }}
                      disabled={saving}
                    />
                  </div>
                  <Button
                    type="button"
                    className="min-h-11 flex-1 sm:flex-none sm:min-w-[8.5rem]"
                    onClick={() => void confirmPending()}
                    disabled={saving || productionRowId < 1}
                  >
                    {saving ? (
                      <>
                        <Loader2 size={16} className="animate-spin" /> Saving…
                      </>
                    ) : (
                      "Stock in"
                    )}
                  </Button>
                </div>
              ) : (
                <Button
                  type="button"
                  className="min-h-11 w-full"
                  onClick={() => void confirmPending()}
                  disabled={saving || productionRowId < 1}
                >
                  {saving ? (
                    <>
                      <Loader2 size={16} className="animate-spin" /> Saving…
                    </>
                  ) : (
                    "Stock in this bottle"
                  )}
                </Button>
              )}
              {pending.kind === "sku" && selected && previewGallons > 0 ? (
                <p className="text-xs text-muted-foreground">
                  Uses ~{formatGallons(previewGallons)} gal ·{" "}
                  {formatGallons(Number(selected.current_qty) - previewGallons)} remain after
                  {previewGallons > Number(selected.current_qty) + 1e-9 ? " (will overdraw — still allowed)" : ""}
                </p>
              ) : null}
            </motion.div>
          ) : null}
        </AnimatePresence>

        <div
          className={`h-14 rounded-xl border flex items-center justify-center text-sm font-medium transition-colors ${
            flash === "ok"
              ? "bg-green-100 border-green-300 text-green-800"
              : flash === "err"
                ? "bg-red-100 border-red-300 text-red-800"
                : "bg-secondary/30 border-border text-muted-foreground"
          }`}
        >
          {flash === "ok"
            ? pending
              ? "Scanned — enter quantity"
              : "Stocked in"
            : flash === "err"
              ? "Not accepted"
              : pending
                ? "Enter quantity"
                : "Ready"}
        </div>
        {flash === "err" ? (
          <AppErrorBanner message={msg ?? "Not accepted."} />
        ) : (
          <AppSuccessNotice message={msg} />
        )}
        {warn && (
          <p
            className="text-xs font-medium text-amber-950 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"
            role="status"
            data-testid="honey-production-overdraw-warn"
          >
            {warn}
          </p>
        )}
        {progress && <p className="text-lg font-bold tabular-nums">{progress}</p>}
      </div>
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan bottle barcode"
          titleId={cameraTitleId}
          continuous={false}
          scanPrompt="Scan one barcode, then enter quantity on the page."
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
