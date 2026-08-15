import { useCallback, useEffect, useId, useMemo, useRef, useState } from "react";
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
import { ApiError, apiGet } from "@/app/lib/api";
import { HONEY_BOTTLE_OPTIONS, HONEY_VARIANTS } from "@/app/lib/honey";
import { triggerLiveRefresh } from "@/app/lib/liveRefresh";
import {
  bottleBarcodesApi,
  bottlePrintSheetUrl,
  type HoneyBarcodeBatch,
  type HoneyBottleRequest,
  type HoneyPackagingOption,
} from "@/app/lib/bottleBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";

interface ProductionLotOption {
  id: number;
  supplier_code: string;
  variant: string;
  arrival_date: string;
  current_qty: string;
}

export function HoneyBottleRequestsPanel({ canAdmin }: { canAdmin: boolean }) {
  const [variant, setVariant] = useState("light");
  const [size, setSize] = useState("liter");
  const [qty, setQty] = useState("50");
  const [packId, setPackId] = useState<number | "">("");
  const [packMode, setPackMode] = useState<"pick" | "custom">("pick");
  const [customCode, setCustomCode] = useState("");
  const [customSupplier, setCustomSupplier] = useState("");
  const [customBottlingDate, setCustomBottlingDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [notes, setNotes] = useState("");
  const [packs, setPacks] = useState<HoneyPackagingOption[]>([]);
  const [rows, setRows] = useState<HoneyBottleRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const [list, opts] = await Promise.all([
        bottleBarcodesApi.requestList({ status: "all", page: 1 }),
        bottleBarcodesApi.packagingOptions(),
      ]);
      setRows(list.rows);
      setPacks(opts.rows);
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
      let packagingRowId: number | null = packMode === "pick" && packId !== "" ? packId : null;
      let packLabel = "";
      let packReused = false;
      if (packMode === "custom") {
        const created = await bottleBarcodesApi.createCustomPackaging({
          code: customCode.trim() || undefined,
          supplier_name: customSupplier.trim() || undefined,
          variant,
          bottling_date: customBottlingDate,
        });
        packagingRowId = created.packaging.id;
        packLabel = created.packaging.batch_no;
        packReused = created.reused;
        setPacks(prev => [created.packaging, ...prev.filter(p => p.id !== created.packaging.id)]);
        setPackId(created.packaging.id);
        setPackMode("pick");
        setCustomCode("");
        setCustomSupplier("");
      }
      await bottleBarcodesApi.requestCreate({
        variant,
        size,
        quantity: Number(qty),
        packaging_row_id: packagingRowId,
        notes: notes.trim() || undefined,
      });
      setOk(
        packReused
          ? `Request submitted — added to existing batch ${packLabel}.`
          : packagingRowId
            ? "Request submitted with packaging batch."
            : "Request submitted.",
      );
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
            Quantity
            <input className="mt-1 w-full border rounded-lg px-2 py-2 text-sm" type="number" min={1} value={qty} onChange={e => setQty(e.target.value)} />
          </label>
          <label className="text-xs text-muted-foreground">
            Packaging batch (optional)
            <select
              className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
              value={packMode === "custom" ? "__custom__" : packId === "" ? "" : String(packId)}
              onChange={e => {
                const v = e.target.value;
                if (v === "__custom__") {
                  setPackMode("custom");
                  setPackId("");
                  return;
                }
                setPackMode("pick");
                setPackId(v === "" ? "" : Number(v));
              }}
            >
              <option value="">Pick later</option>
              <option value="__custom__">+ Create custom batch / supplier…</option>
              {packs.filter(p => p.variant === variant).map(p => (
                <option key={p.id} value={p.id}>{p.label}</option>
              ))}
            </select>
          </label>
        </div>
        {packMode === "custom" ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-lg border border-dashed border-border bg-secondary/20 p-3">
            <label className="text-xs text-muted-foreground sm:col-span-2">
              Batch code (type it directly)
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 font-mono text-sm uppercase"
                placeholder="e.g. JR073026"
                value={customCode}
                onChange={e => setCustomCode(e.target.value.toUpperCase())}
              />
              <span className="mt-1 block text-[11px] text-muted-foreground">
                Or leave blank and fill supplier + date below to build one (JR073026). The bottling date you pick is what gets recorded. Re-typing a batch you already have reuses it — one batch covers every size.
              </span>
            </label>
            <label className="text-xs text-muted-foreground">
              Supplier name
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                placeholder="e.g. Maria Santos Apiary"
                value={customSupplier}
                onChange={e => setCustomSupplier(e.target.value)}
                disabled={customCode.trim() !== ""}
              />
            </label>
            <label className="text-xs text-muted-foreground">
              Bottling date
              <input
                className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                type="date"
                value={customBottlingDate}
                onChange={e => setCustomBottlingDate(e.target.value)}
              />
            </label>
          </div>
        ) : null}
        <textarea className="w-full border rounded-lg px-2 py-2 text-sm" rows={2} placeholder="Notes" value={notes} onChange={e => setNotes(e.target.value)} />
        <Button type="button" onClick={() => void submit()} disabled={saving || (packMode === "custom" && !customCode.trim() && !customSupplier.trim())}>
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
      const res = await bottleBarcodesApi.scanStockIn(value);
      playScanBeep("ok");
      setFlash("ok");
      setMsg(`${res.variant} ${res.size_label} · ${res.code}`);
      if (res.request) {
        setProgress(`${res.request.scanned_count} / ${res.request.generated_count || res.request.quantity} scanned`);
      }
      window.setTimeout(() => setFlash(null), 700);
      inputRef.current?.focus();
      return { kind: "success", message: `Stocked in: ${res.variant} ${res.size_label} · ${res.code}` };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      const isDup = /already|duplicate/i.test(reason);
      playScanBeep(isDup ? "dup" : "bad");
      setFlash("err");
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 700);
      inputRef.current?.focus();
      return {
        kind: isDup ? "duplicate" : "error",
        message: reason,
      };
    }
  };

  return (
    <AppSectionEnter className="space-y-4">
      <div className="bg-card rounded-xl border border-border p-4 space-y-3">
        <h3 className="text-sm font-semibold">Scan bottle into packaging stock</h3>
        <div className="flex gap-2">
          <input
            ref={inputRef}
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
        <p className="text-xs text-muted-foreground">
          2D imager scanner, tap Camera to use this device, or type the code printed under the barcode.
        </p>
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
  const [packs, setPacks] = useState<HoneyPackagingOption[]>([]);
  const [lots, setLots] = useState<ProductionLotOption[]>([]);
  const [packPick, setPackPick] = useState<Record<number, number>>({});
  const [lotPick, setLotPick] = useState<Record<number, number>>({});
  const [customOpenFor, setCustomOpenFor] = useState<number | null>(null);
  const [customCode, setCustomCode] = useState("");
  const [customSupplier, setCustomSupplier] = useState("");
  const [customBottlingDate, setCustomBottlingDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [drift, setDrift] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    try {
      const [list, batchList, opts, production] = await Promise.all([
        bottleBarcodesApi.requestList({ status: "pending" }),
        bottleBarcodesApi.batchList(),
        bottleBarcodesApi.packagingOptions(),
        apiGet<{ rows: ProductionLotOption[] }>("honey.php", "production_list"),
      ]);
      setPending(list.rows);
      setBatches(batchList.rows);
      setPacks(opts.rows);
      setLots(production.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load queue.");
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const generate = async (req: HoneyBottleRequest) => {
    setBusy(true);
    setError(null);
    try {
      const packId = packPick[req.id] ?? req.packaging_row_id;
      const productionRowId = lotPick[req.id];
      const res = await bottleBarcodesApi.generate(
        req.id,
        packId,
        packId ? null : productionRowId ?? null,
      );
      setOk(`Generated ${res.batch.quantity_generated} barcodes · print as A4 (68/page).`);
      window.open(bottlePrintSheetUrl(res.batch.id), "_blank");
      await bottleBarcodesApi.markPrinted(res.batch.id);
      triggerLiveRefresh();
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Generate failed.");
    } finally {
      setBusy(false);
    }
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

  const createCustomForRequest = async (req: HoneyBottleRequest) => {
    setBusy(true);
    setError(null);
    try {
      const created = await bottleBarcodesApi.createCustomPackaging({
        code: customCode.trim() || undefined,
        supplier_name: customSupplier.trim() || undefined,
        variant: req.variant,
        bottling_date: customBottlingDate,
      });
      setPacks(prev => [created.packaging, ...prev.filter(p => p.id !== created.packaging.id)]);
      setPackPick(p => ({ ...p, [req.id]: created.packaging.id }));
      setLotPick(p => {
        const next = { ...p };
        delete next[req.id];
        return next;
      });
      setCustomOpenFor(null);
      setCustomCode("");
      setCustomSupplier("");
      setOk(created.reused ? `Using existing ${created.packaging.label}` : `Created ${created.packaging.label}`);
      triggerLiveRefresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not create custom batch.");
    } finally {
      setBusy(false);
    }
  };

  const packOptions = useMemo(() => packs, [packs]);

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
        Labels print on A4 at 68 per page (4×17) — barcode plus the code text underneath (type it in if a sticker won&rsquo;t scan), dashed cut guides. Print at 100% scale (not &ldquo;fit to page&rdquo;) or the bars get too thin to scan. If no packaging batch exists, pick a production lot or create a custom supplier batch (no gallon deduct).
      </p>
      <div className="overflow-x-auto rounded-xl border border-border">
        <table className="w-full text-sm">
          <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
            <tr>
              <th className="text-left px-3 py-2">Request</th>
              <th className="text-left px-3 py-2">Batch / lot</th>
              <th className="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            {pending.map(r => (
              <tr key={r.id} className={appHoverRow}>
                <td className="px-3 py-2 capitalize">{r.variant} {r.size_label} ×{r.quantity}</td>
                <td className="px-3 py-2 space-y-1">
                  <select
                    className="border rounded-lg px-2 py-1 text-xs w-full max-w-xs"
                    value={
                      customOpenFor === r.id
                        ? "__custom__"
                        : String(packPick[r.id] ?? r.packaging_row_id ?? "")
                    }
                    onChange={e => {
                      const v = e.target.value;
                      if (v === "__custom__") {
                        setCustomOpenFor(r.id);
                        setCustomCode("");
                        setCustomSupplier("");
                        setCustomBottlingDate(new Date().toISOString().slice(0, 10));
                        return;
                      }
                      setCustomOpenFor(null);
                      setPackPick(p => {
                        const next = { ...p };
                        if (v === "") delete next[r.id];
                        else next[r.id] = Number(v);
                        return next;
                      });
                    }}
                  >
                    <option value="">Select packaging batch</option>
                    <option value="__custom__">+ Create custom batch / supplier…</option>
                    {packOptions.filter(p => p.variant === r.variant).map(p => (
                      <option key={p.id} value={p.id}>{p.label}</option>
                    ))}
                  </select>
                  {customOpenFor === r.id ? (
                    <div className="max-w-xs space-y-1 rounded-lg border border-dashed border-border bg-secondary/20 p-2">
                      <input
                        className="w-full border rounded-lg px-2 py-1 font-mono text-xs uppercase"
                        placeholder="Batch code, e.g. JR073026"
                        value={customCode}
                        onChange={e => setCustomCode(e.target.value.toUpperCase())}
                      />
                      <p className="text-[11px] text-muted-foreground">…or build it from a supplier name:</p>
                      <input
                        className="w-full border rounded-lg px-2 py-1 text-xs"
                        placeholder="Supplier name"
                        value={customSupplier}
                        onChange={e => setCustomSupplier(e.target.value)}
                        disabled={customCode.trim() !== ""}
                      />
                      <label className="block text-[11px] text-muted-foreground">
                        Bottling date
                        <input
                          className="mt-0.5 w-full border rounded-lg px-2 py-1 text-xs"
                          type="date"
                          value={customBottlingDate}
                          onChange={e => setCustomBottlingDate(e.target.value)}
                        />
                      </label>
                      <div className="flex gap-1">
                        <Button
                          type="button"
                          size="sm"
                          disabled={busy || (!customCode.trim() && !customSupplier.trim())}
                          onClick={() => void createCustomForRequest(r)}
                        >
                          Create batch
                        </Button>
                        <Button type="button" size="sm" variant="outline" onClick={() => setCustomOpenFor(null)}>
                          Cancel
                        </Button>
                      </div>
                    </div>
                  ) : !(packPick[r.id] ?? r.packaging_row_id) ? (
                    <select
                      className="border rounded-lg px-2 py-1 text-xs w-full max-w-xs"
                      value={lotPick[r.id] ?? ""}
                      onChange={e => setLotPick(p => ({ ...p, [r.id]: Number(e.target.value) }))}
                    >
                      <option value="">Or create from production lot</option>
                      {lots.filter(l => l.variant === r.variant).map(l => (
                        <option key={l.id} value={l.id}>
                          {l.supplier_code} · {l.arrival_date}
                        </option>
                      ))}
                    </select>
                  ) : null}
                </td>
                <td className="px-3 py-2">
                  <Button type="button" size="sm" disabled={busy} onClick={() => void generate(r)}>
                    Generate + print
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
      <h3 className="text-sm font-semibold">Recent batches</h3>
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
