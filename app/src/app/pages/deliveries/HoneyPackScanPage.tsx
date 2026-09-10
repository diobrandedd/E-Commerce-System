import { useCallback, useEffect, useId, useState } from "react";
import { BookOpen, Camera, CheckCircle2, ChevronDown, ChevronRight, Loader2, Pencil, Trash2, XCircle } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSectionEnter,
  AppSuccessNotice,
  AppStepSwitch,
  appHeaderActionClass,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { ApiError } from "@/app/lib/api";
import {
  bottleBarcodesApi,
  type HoneyPackSession,
  type HoneyPackSessionGroup,
  type HoneyPackSessionItem,
  type HoneyPackSessionListRow,
} from "@/app/lib/bottleBarcodes";
import { skuBarcodesApi } from "@/app/lib/skuBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

function localTodayIso() {
  const now = new Date();
  const offset = now.getTimezoneOffset() * 60_000;
  return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

function todayIso() {
  return localTodayIso();
}

function daysAgoIso(days: number) {
  const d = new Date();
  d.setDate(d.getDate() - days);
  const offset = d.getTimezoneOffset() * 60_000;
  return new Date(d.getTime() - offset).toISOString().slice(0, 10);
}

/** Units confirmed today from Packaging or RTS sellable (excludes finished-only dept lines). */
function sumPackagingOrRtsUnits(rows: HoneyPackSessionListRow[]): number {
  let total = 0;
  for (const row of rows) {
    for (const it of row.items ?? []) {
      const pool = (it.pool ?? "packaging").toLowerCase();
      if (pool === "finished") continue;
      if (pool !== "packaging" && pool !== "rts_sellable") continue;
      total += Math.max(1, Number(it.qty ?? 1));
    }
  }
  return total;
}

async function fetchTodayPackagingOrRtsTotal(): Promise<number> {
  const day = localTodayIso();
  let page = 1;
  let totalPages = 1;
  let sum = 0;
  while (page <= totalPages && page <= 20) {
    const res = await bottleBarcodesApi.packSessionList({ from: day, to: day, page });
    sum += sumPackagingOrRtsUnits(res.rows);
    totalPages = Math.max(1, Math.ceil(res.total / res.per_page));
    page += 1;
  }
  return sum;
}

function formatWhen(raw: string | null | undefined) {
  if (!raw) return "—";
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function packItemLabel(item: {
  kind?: string;
  product_label?: string;
  variant: string;
  size_label: string;
}) {
  if (item.kind === "dept") {
    return item.product_label || item.variant;
  }
  return `${item.variant} ${item.size_label}`.trim();
}

function packGroupKey(g: Pick<HoneyPackSessionGroup, "kind" | "product_key" | "variant" | "size">): string {
  if (g.kind === "dept") {
    return `dept|${g.product_key ?? g.variant}`;
  }
  return `honey|${g.variant}|${g.size}`;
}

function packItemGroupKey(item: HoneyPackSessionItem): string {
  if (item.kind === "dept") {
    return `dept|${item.product_key ?? item.variant}`;
  }
  return `honey|${item.variant}|${item.size}`;
}

const PACK_PRODUCT_FILTERS: { value: string; label: string }[] = [
  { value: "all", label: "All products" },
  { value: "honey", label: "Honey" },
  { value: "pt", label: "Paragis" },
  { value: "brc", label: "Black Rice" },
  { value: "jar_cacao", label: "Cacao" },
  { value: "honey_comb", label: "Honey Comb" },
];

export function HoneyPackScanPage() {
  const [tab, setTab] = useState<"scan" | "history">("scan");
  const [guideOpen, setGuideOpen] = useState(false);

  return (
    <PageLayout
      title="Pack scan"
      subtitle="Scan honey and dept barcodes to deduct stock before wrapping, or review past pack sessions."
      actions={
        <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <div className="flex gap-1 mb-4 p-1 rounded-xl bg-secondary/40 w-fit max-w-full">
        <button
          type="button"
          onClick={() => setTab("scan")}
          className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
            tab === "scan" ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          Scan
        </button>
        <button
          type="button"
          onClick={() => setTab("history")}
          className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
            tab === "history" ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          History
        </button>
      </div>

      <AppStepSwitch stepKey={tab}>
        {tab === "scan" ? <PackScanLivePanel /> : <PackScanHistoryPanel />}
      </AppStepSwitch>

      <AppModal open={guideOpen} onClose={() => setGuideOpen(false)}>
        <AppModalHeader title="Pack scan" onClose={() => setGuideOpen(false)} />
        <AppModalBody className="space-y-3 text-sm text-muted-foreground">
          <p>
            <strong className="text-foreground">Scan</strong> — scan every sticker going into the parcel:
            honey bottles plus Paragis, Black Rice, Cacao, or Honey Comb. Mix them in one session in any
            order. Permanent SKU barcodes can be scanned repeatedly (+1 each time) or use Quantity + Add for a
            bulk amount. Unique leftover stickers stay one per scan. Tap a product row to see its barcodes.
            Remove a mistaken scan before you confirm. Duplicate unique stickers in this session are ignored.
          </p>
          <p>
            <strong className="text-foreground">Confirm</strong> deducts honey packaging stock and finished
            dept stock. Unique stickers are applied first, then SKU quantity lines (so mixed sessions do not
            empty a batch a leftover sticker still needs).
          </p>
          <p>
            <strong className="text-foreground">Today packed</strong> — after you confirm, the Scan tab shows
            how many Packaging and RTS sellable units were packed today (all confirmed sessions). Finished-only
            dept lines are not included in that total.
          </p>
          <p>
            <strong className="text-foreground">History</strong> — read-only list of confirmed pack sessions.
            Filter by date, product, or batch code. Expanding a row shows items, barcodes, and batches.
          </p>
        </AppModalBody>
      </AppModal>
    </PageLayout>
  );
}

function PackScanLivePanel() {
  const [session, setSession] = useState<HoneyPackSession | null>(null);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "dup" | "bad" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [expandedGroups, setExpandedGroups] = useState<string[]>([]);
  const [removingId, setRemovingId] = useState<string | null>(null);
  const [editItem, setEditItem] = useState<HoneyPackSessionItem | null>(null);
  const [editQty, setEditQty] = useState("1");
  const [skuQty, setSkuQty] = useState("1");
  const [skuPool, setSkuPool] = useState<"packaging" | "rts_sellable" | "finished">("packaging");
  const [lastSku, setLastSku] = useState<{ code: string; kind: "honey" | "dept"; label: string } | null>(null);
  const [todayPacked, setTodayPacked] = useState<number | null>(null);
  const cameraTitleId = useId();
  const editQtyTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, session?.id);

  const refreshTodayPacked = useCallback(async () => {
    try {
      setTodayPacked(await fetchTodayPackagingOrRtsTotal());
    } catch {
      // Keep last known total; do not block scanning.
    }
  }, []);

  const start = useCallback(async () => {
    setError(null);
    try {
      const res = await bottleBarcodesApi.packStart();
      setSession(res.session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start pack session.");
    }
  }, []);

  useEffect(() => {
    void start();
    void refreshTodayPacked();
  }, [start, refreshTodayPacked]);

  const applyPackResult = (
    res: Awaited<ReturnType<typeof bottleBarcodesApi.packScan>>,
  ): ScannerDecodeFeedback => {
    setSession(res.session);
    if (res.result === "ok") {
      playScanBeep("ok");
      setFlash("ok");
      const lookup = res.lookup;
      const label =
        lookup?.kind === "dept"
          ? lookup.product_label || lookup.variant || "Dept item"
          : `${lookup?.variant ?? ""} ${lookup?.size_label ?? ""}`.trim() || "Honey";
      const added =
        lookup && typeof (lookup as { added_qty?: number }).added_qty === "number"
          ? (lookup as { added_qty: number }).added_qty
          : null;
      const total = lookup?.qty != null ? lookup.qty : null;
      const qtyNote =
        added != null && total != null && total !== added
          ? ` +${added} (×${total})`
          : total != null && total !== 1
            ? ` ×${total}`
            : "";
      setMsg(`${label}${qtyNote}`);
      if (lookup?.lookup_kind === "sku" && lookup.code) {
        const kind = lookup.kind === "dept" ? "dept" : "honey";
        setLastSku({ code: String(lookup.code), kind, label });
        if (kind === "dept" && skuPool === "packaging") {
          setSkuPool("finished");
        } else if (kind === "honey" && skuPool === "finished") {
          setSkuPool("packaging");
        }
      }
      const last = res.session.items[res.session.items.length - 1];
      if (last) {
        const groupKey = packItemGroupKey(last);
        setExpandedGroups(prev => (prev.includes(groupKey) ? prev : [...prev, groupKey]));
      }
      window.setTimeout(() => setFlash(null), 650);
      refocus();
      return { kind: "success", message: `Scanned: ${label}${qtyNote}` };
    }
    if (res.result === "duplicate_session") {
      playScanBeep("dup");
      setFlash("dup");
      setMsg("Already scanned in this session");
      window.setTimeout(() => setFlash(null), 650);
      refocus();
      return { kind: "duplicate", message: "Duplicate: already scanned in this session." };
    }
    if (res.result === "already_consumed") {
      playScanBeep("bad");
      setFlash("bad");
      setMsg("Already packed or sold earlier");
      window.setTimeout(() => setFlash(null), 650);
      refocus();
      return { kind: "error", message: "Failed: bottle was already packed or sold." };
    }
    playScanBeep("bad");
    setFlash("bad");
    const reason = res.result === "unknown" ? "Unknown barcode" : res.result.replace(/_/g, " ");
    setMsg(reason);
    window.setTimeout(() => setFlash(null), 650);
    refocus();
    return { kind: "error", message: `Failed: ${reason}.` };
  };

  const packSkuDelta = async (
    skuCode: string,
    qty: number,
    poolOverride?: "packaging" | "rts_sellable" | "finished",
  ): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Pack session is not ready." };
    const pool = poolOverride ?? skuPool;
    try {
      const res = await bottleBarcodesApi.packScan(session.id, skuCode, {
        qty,
        pool,
      });
      return applyPackResult(res);
    } catch (e) {
      playScanBeep("bad");
      setFlash("bad");
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      refocus();
      return { kind: "error", message: reason };
    }
  };

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Pack session is not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const looked = await skuBarcodesApi.lookup(value);
      const lookup = looked.lookup;
      if (lookup.lookup_kind === "sku" || lookup.requires_qty) {
        const kind = lookup.kind === "dept" ? "dept" : "honey";
        let pool: "packaging" | "rts_sellable" | "finished" = skuPool;
        if (kind === "dept" && pool === "packaging") {
          pool = "finished";
        } else if (kind === "honey" && pool === "finished") {
          pool = "packaging";
        }
        setSkuPool(pool);
        return await packSkuDelta(value, 1, pool);
      }
      const res = await bottleBarcodesApi.packScan(session.id, value);
      return applyPackResult(res);
    } catch (e) {
      // Unknown to shared lookup — still try pack_scan for unique leftovers / clearer errors
      try {
        const res = await bottleBarcodesApi.packScan(session.id, value);
        return applyPackResult(res);
      } catch (inner) {
        playScanBeep("bad");
        setFlash("bad");
        const reason = inner instanceof ApiError ? inner.message : e instanceof ApiError ? e.message : "Scan failed.";
        setMsg(reason);
        window.setTimeout(() => setFlash(null), 650);
        refocus();
        return { kind: "error", message: reason };
      }
    }
  };

  const addSkuBulk = async () => {
    if (!lastSku) {
      setMsg("Scan a SKU barcode first, then use Quantity + Add for bulk.");
      refocus();
      return;
    }
    const qty = Math.max(1, parseInt(skuQty, 10) || 1);
    await packSkuDelta(lastSku.code, qty);
  };

  const confirm = async () => {
    if (!session) return;
    setBusy(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packConfirm(session.id);
      const packedUnits = res.session.total_qty ?? res.session.grouped.reduce((sum, g) => sum + g.qty, 0);
      setOk(`Packed ${packedUnits} unit${packedUnits === 1 ? "" : "s"}.`);
      setConfirmOpen(false);
      setLastSku(null);
      await start();
      await refreshTodayPacked();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Confirm failed.");
    } finally {
      setBusy(false);
    }
  };

  const discard = async () => {
    if (!session) return;
    await bottleBarcodesApi.packDiscard(session.id);
    setOk("Session discarded.");
    setExpandedGroups([]);
    setLastSku(null);
    await start();
  };

  const removeItem = async (item: HoneyPackSessionItem) => {
    if (!session) return;
    const rowKey = `${item.kind ?? "honey"}-${item.id}`;
    setRemovingId(rowKey);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packRemoveItem(session.id, item.kind === "dept" ? "dept" : "honey", item.id);
      setSession(res.session);
      setMsg(`Removed ${item.code}`);
      const leftover = res.session.items.some(it => packItemGroupKey(it) === packItemGroupKey(item));
      if (!leftover) {
        const key = packItemGroupKey(item);
        setExpandedGroups(prev => prev.filter(k => k !== key));
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not remove barcode.");
    } finally {
      setRemovingId(null);
      refocus();
    }
  };

  const startEditQty = (item: HoneyPackSessionItem) => {
    if (item.lookup_kind !== "sku") return;
    setEditItem(item);
    setEditQty(String(item.qty ?? 1));
    setError(null);
  };

  const saveEditQty = async () => {
    if (!session || !editItem) return;
    const qty = parseInt(editQty, 10);
    if (!Number.isFinite(qty) || qty < 1 || qty > 5000) {
      setError("Quantity must be between 1 and 5000.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packSetItemQty(
        session.id,
        editItem.kind === "dept" ? "dept" : "honey",
        editItem.id,
        qty,
      );
      setSession(res.session);
      setMsg(`${editItem.code} set to ×${qty}`);
      setEditItem(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not update quantity.");
    } finally {
      setBusy(false);
      refocus();
    }
  };

  const toggleGroup = (key: string) => {
    setExpandedGroups(prev => (prev.includes(key) ? prev.filter(k => k !== key) : [...prev, key]));
  };

  const sessionUnits = session?.total_qty ?? session?.grouped.reduce((sum, g) => sum + g.qty, 0) ?? 0;

  const flashClass =
    flash === "ok"
      ? "bg-green-100 border-green-300 text-green-800"
      : flash === "dup"
        ? "bg-amber-100 border-amber-300 text-amber-800"
        : flash === "bad"
          ? "bg-red-100 border-red-300 text-red-800"
          : "bg-secondary/30 border-border text-muted-foreground";

  return (
    <AppSectionEnter className="space-y-4">
      {error && <AppErrorBanner message={error} />}
      {ok && <AppSuccessNotice message={ok} />}
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
      <div className="rounded-lg border border-border bg-card p-3 space-y-2">
        <p className="text-xs text-muted-foreground">
          SKU barcodes: each scan adds +1. Or type a quantity and Add (uses the last SKU scanned
          {lastSku ? `: ${lastSku.label}` : ""}).
        </p>
        <div className="flex flex-wrap gap-2 items-end">
          <label className="text-xs text-muted-foreground block min-w-[7rem] flex-1">
            Stock pool
            <select
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={skuPool}
              onChange={e =>
                setSkuPool(e.target.value as "packaging" | "rts_sellable" | "finished")
              }
            >
              {lastSku?.kind === "dept" ? (
                <>
                  <option value="finished">Finished</option>
                  <option value="rts_sellable">RTS sellable</option>
                </>
              ) : (
                <>
                  <option value="packaging">Packaging</option>
                  <option value="rts_sellable">RTS sellable</option>
                  <option value="finished">Finished (dept)</option>
                </>
              )}
            </select>
          </label>
          <label className="text-xs text-muted-foreground block w-24">
            Quantity
            <input
              type="number"
              min={1}
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={skuQty}
              onChange={e => setSkuQty(e.target.value)}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  void addSkuBulk();
                }
              }}
            />
          </label>
          <Button type="button" variant="outline" onClick={() => void addSkuBulk()} disabled={!lastSku}>
            Add
          </Button>
        </div>
      </div>
      <p className="text-xs text-muted-foreground">
        Honey bottles and Paragis / Black Rice / Cacao / Honey Comb stickers can be mixed in one session.
      </p>
      <div className={`h-20 rounded-xl border flex items-center justify-center gap-2 text-sm font-semibold ${flashClass}`}>
        {flash === "ok" && <CheckCircle2 size={22} />}
        {flash === "bad" && <XCircle size={22} />}
        {flash === "ok" ? "Scanned" : flash === "dup" ? "Already in this session" : flash === "bad" ? "Not accepted" : "Ready"}
      </div>
      {msg && <p className="text-sm">{msg}</p>}
      <div className="grid grid-cols-2 gap-3">
        <div className="rounded-xl border border-border bg-card px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            In this session
          </p>
          <p className="text-2xl font-bold mt-1 tabular-nums">{sessionUnits}</p>
          <p className="text-[10px] text-muted-foreground mt-0.5">
            {sessionUnits === 1 ? "unit" : "units"} not confirmed yet
          </p>
        </div>
        <div className="rounded-xl border border-border bg-card px-4 py-3">
          <p className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            Today packed
          </p>
          <p className="text-2xl font-bold mt-1 tabular-nums">
            {todayPacked == null ? "…" : todayPacked.toLocaleString()}
          </p>
          <p className="text-[10px] text-muted-foreground mt-0.5">
            Packaging &amp; RTS sellable · confirmed today
          </p>
        </div>
      </div>
      <div className="space-y-2">
        {(session?.grouped ?? []).map(g => {
          const key = packGroupKey(g);
          const open = expandedGroups.includes(key);
          const barcodes = (session?.items ?? []).filter(it => packItemGroupKey(it) === key);
          return (
            <div key={key} className="bg-card border rounded-xl overflow-hidden">
              <div className={`flex items-center gap-2 px-3 py-2.5 ${appHoverRow}`}>
                <button
                  type="button"
                  className="min-h-11 flex-1 text-left flex items-center gap-2"
                  onClick={() => toggleGroup(key)}
                  aria-expanded={open}
                >
                  <span className="text-muted-foreground shrink-0">
                    {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block text-xs text-muted-foreground capitalize">{packItemLabel(g)}</span>
                    <span className="text-lg font-bold leading-tight tabular-nums">×{g.qty}</span>
                  </span>
                </button>
                {barcodes.length === 1 && barcodes[0]?.lookup_kind === "sku" ? (
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="min-h-11 shrink-0"
                    onClick={() => startEditQty(barcodes[0])}
                  >
                    <Pencil size={14} /> Edit qty
                  </Button>
                ) : null}
              </div>
              {open ? (
                <div className="border-t bg-secondary/20 px-3 py-2 space-y-1">
                  {barcodes.length === 0 ? (
                    <p className="text-xs text-muted-foreground py-1">No barcodes.</p>
                  ) : (
                    barcodes.map(it => {
                      const rowKey = `${it.kind ?? "honey"}-${it.id}`;
                      const removing = removingId === rowKey;
                      const isSku = it.lookup_kind === "sku";
                      return (
                        <div key={rowKey} className="flex items-center gap-2 py-1">
                          <div className="min-w-0 flex-1">
                            <p className="font-mono text-xs">{it.code}</p>
                            {it.batch_no ? (
                              <p className="text-[10px] text-muted-foreground font-mono">{it.batch_no}</p>
                            ) : null}
                            {isSku ? (
                              <p className="text-[10px] text-muted-foreground">SKU · ×{it.qty ?? 1}</p>
                            ) : (
                              <p className="text-[10px] text-muted-foreground">Unique sticker</p>
                            )}
                          </div>
                          {isSku ? (
                            <Button
                              type="button"
                              size="sm"
                              variant="outline"
                              className="min-h-11 shrink-0"
                              disabled={busy}
                              onClick={e => {
                                e.stopPropagation();
                                startEditQty(it);
                              }}
                            >
                              <Pencil size={14} /> Edit
                            </Button>
                          ) : null}
                          <button
                            type="button"
                            className="text-red-400 hover:text-red-600 p-1 transition-colors active:scale-90 disabled:opacity-40"
                            aria-label={`Remove ${it.code}`}
                            disabled={removing || busy}
                            onClick={e => {
                              e.stopPropagation();
                              void removeItem(it);
                            }}
                          >
                            {removing ? <Loader2 size={14} className="animate-spin" /> : <Trash2 size={14} />}
                          </button>
                        </div>
                      );
                    })
                  )}
                </div>
              ) : null}
            </div>
          );
        })}
      </div>
      <div className="flex flex-wrap gap-2">
        <Button type="button" onClick={() => setConfirmOpen(true)} disabled={!session || sessionUnits < 1}>
          Done scanning
        </Button>
        <Button type="button" variant="outline" onClick={() => void discard()} disabled={!session || sessionUnits < 1}>
          Discard session
        </Button>
      </div>
      <AppModal
        open={editItem !== null}
        onClose={() => !busy && setEditItem(null)}
        panelClassName="max-w-sm w-full"
        labelledBy={editQtyTitleId}
      >
        <AppModalHeader
          title="Edit pack quantity"
          titleId={editQtyTitleId}
          subtitle={editItem ? `${packItemLabel(editItem)} · ${editItem.code}` : undefined}
          onClose={() => !busy && setEditItem(null)}
        />
        <AppModalBody className="space-y-3">
          <p className="text-xs text-muted-foreground">
            Non-unique SKU lines only. Unique stickers stay one scan each — remove them individually.
          </p>
          <label htmlFor="pack-session-edit-qty" className="block text-xs font-medium uppercase tracking-wide text-muted-foreground">
            Quantity
            <input
              id="pack-session-edit-qty"
              type="number"
              min={1}
              max={5000}
              value={editQty}
              onChange={e => setEditQty(e.target.value)}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  void saveEditQty();
                }
              }}
              className="mt-1 w-full rounded-lg border-2 border-border bg-card px-3 py-2.5 text-sm tabular-nums"
              autoFocus
            />
          </label>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" disabled={busy} onClick={() => setEditItem(null)}>
            Cancel
          </Button>
          <Button type="button" disabled={busy} onClick={() => void saveEditQty()}>
            {busy ? <Loader2 className="animate-spin" size={16} /> : null}
            Save quantity
          </Button>
        </AppModalFooter>
      </AppModal>
      <AppModal open={confirmOpen} onClose={() => setConfirmOpen(false)}>
        <AppModalHeader title="Confirm pack deduction" onClose={() => setConfirmOpen(false)} />
        <AppModalBody className="space-y-2">
          <p className="text-sm text-muted-foreground">
            These leave finished stock now (honey packaging and/or dept finished qty).
          </p>
          {(session?.grouped ?? []).map(g => (
            <p
              key={`${g.kind ?? "honey"}-${g.product_key ?? g.variant}-${g.size}`}
              className="text-sm font-medium capitalize"
            >
              {g.qty} × {packItemLabel(g)}
            </p>
          ))}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setConfirmOpen(false)}>Keep scanning</Button>
          <Button type="button" onClick={() => void confirm()} disabled={busy}>
            {busy ? <Loader2 className="animate-spin" size={16} /> : null}
            Confirm
          </Button>
        </AppModalFooter>
      </AppModal>
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan pack barcode"
          titleId={cameraTitleId}
          continuous
          scanPrompt="Point at each barcode. Mix honey and dept stickers until the parcel is complete."
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </AppSectionEnter>
  );
}


function PackScanHistoryPanel() {
  const [from, setFrom] = useState(() => daysAgoIso(13));
  const [to, setTo] = useState(() => todayIso());
  const [batchNo, setBatchNo] = useState("");
  const [productKey, setProductKey] = useState("all");
  const [applied, setApplied] = useState({
    from: daysAgoIso(13),
    to: todayIso(),
    batch_no: "",
    product_key: "all",
  });
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<HoneyPackSessionListRow[]>([]);
  const [total, setTotal] = useState(0);
  const [perPage, setPerPage] = useState(30);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expanded, setExpanded] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packSessionList({
        from: applied.from,
        to: applied.to,
        batch_no: applied.batch_no || undefined,
        product_key: applied.product_key !== "all" ? applied.product_key : undefined,
        page,
      });
      setRows(res.rows);
      setTotal(res.total);
      setPerPage(res.per_page);
    } catch (e) {
      setRows([]);
      setError(e instanceof ApiError ? e.message : "Could not load pack history.");
    } finally {
      setLoading(false);
    }
  }, [applied, page]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const applyFilters = () => {
    setPage(1);
    setExpanded(null);
    setApplied({
      from: from || daysAgoIso(13),
      to: to || todayIso(),
      batch_no: batchNo.trim().toUpperCase(),
      product_key: productKey,
    });
  };

  const totalPages = Math.max(1, Math.ceil(total / perPage));

  return (
    <AppSectionEnter className="space-y-4">
      <p className="text-xs text-muted-foreground">
        Confirmed pack sessions only. Read-only — does not change stock or past scans.
      </p>
      <div className="flex flex-wrap gap-2 items-end bg-card border rounded-xl p-3">
        <label className="text-xs text-muted-foreground">
          From
          <input
            type="date"
            className="mt-1 block border rounded-lg px-2 py-1.5 text-sm"
            value={from}
            onChange={e => setFrom(e.target.value)}
          />
        </label>
        <label className="text-xs text-muted-foreground">
          To
          <input
            type="date"
            className="mt-1 block border rounded-lg px-2 py-1.5 text-sm"
            value={to}
            onChange={e => setTo(e.target.value)}
          />
        </label>
        <label className="text-xs text-muted-foreground">
          Product
          <select
            className="mt-1 block border rounded-lg px-2 py-1.5 text-sm bg-background min-w-[9rem]"
            value={productKey}
            onChange={e => setProductKey(e.target.value)}
          >
            {PACK_PRODUCT_FILTERS.map(opt => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </label>
        <label className="text-xs text-muted-foreground flex-1 min-w-[10rem]">
          Batch code
          <input
            className="mt-1 block w-full border rounded-lg px-2 py-1.5 font-mono text-sm uppercase"
            placeholder="e.g. JR063026"
            value={batchNo}
            onChange={e => setBatchNo(e.target.value.toUpperCase())}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                applyFilters();
              }
            }}
          />
        </label>
        <Button type="button" size="sm" onClick={applyFilters}>
          Apply
        </Button>
      </div>

      {error && <AppErrorBanner message={error} />}
      {loading ? (
        <p className="text-sm text-muted-foreground py-8 text-center">
          <Loader2 className="inline animate-spin" size={16} /> Loading…
        </p>
      ) : rows.length === 0 ? (
        <p className="text-sm text-muted-foreground py-8 text-center">No confirmed pack sessions in this range.</p>
      ) : (
        <div className="space-y-2">
          {rows.map(r => {
            const open = expanded === r.id;
            const summary = (r.grouped ?? []).map(g => `${g.qty}× ${packItemLabel(g)}`).join(" · ");
            return (
              <div key={r.id} className="bg-card border rounded-xl overflow-hidden">
                <button
                  type="button"
                  className={`w-full text-left px-3 py-3 flex gap-2 items-start ${appHoverRow}`}
                  onClick={() => setExpanded(open ? null : r.id)}
                >
                  <span className="mt-0.5 text-muted-foreground shrink-0">
                    {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                  </span>
                  <div className="min-w-0 flex-1 space-y-0.5">
                    <p className="text-sm font-semibold">{formatWhen(r.confirmed_at)}</p>
                    <p className="text-xs text-muted-foreground">
                      {r.confirmed_by_name || "—"} · {r.item_count} item{r.item_count === 1 ? "" : "s"}
                    </p>
                    {summary ? <p className="text-xs capitalize text-muted-foreground truncate">{summary}</p> : null}
                  </div>
                </button>
                {open ? (
                  <div className="border-t px-3 py-3 space-y-3 bg-secondary/20">
                    <div>
                      <p className="text-[10px] uppercase tracking-wide text-muted-foreground mb-1">By batch</p>
                      <div className="overflow-x-auto rounded-lg border bg-card">
                        <table className="w-full text-sm">
                          <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
                            <tr>
                              <th className="text-left px-2 py-1.5">Batch</th>
                              <th className="text-left px-2 py-1.5">Item</th>
                              <th className="text-right px-2 py-1.5">Qty</th>
                            </tr>
                          </thead>
                          <tbody>
                            {(r.by_batch ?? []).map((b, i) => (
                              <tr
                                key={`${b.kind ?? "honey"}-${b.batch_no}-${b.product_key ?? b.variant}-${b.size}-${i}`}
                                className="border-t"
                              >
                                <td className="px-2 py-1.5 font-mono text-xs">{b.batch_no || "—"}</td>
                                <td className="px-2 py-1.5 capitalize">{packItemLabel(b)}</td>
                                <td className="px-2 py-1.5 text-right tabular-nums">{b.qty}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <div>
                      <p className="text-[10px] uppercase tracking-wide text-muted-foreground mb-1">Items</p>
                      <div className="overflow-x-auto rounded-lg border bg-card max-h-56 overflow-y-auto">
                        <table className="w-full text-sm">
                          <thead className="sticky top-0 bg-secondary/40 text-xs uppercase text-muted-foreground">
                            <tr>
                              <th className="text-left px-2 py-1.5">Barcode</th>
                              <th className="text-left px-2 py-1.5">Batch</th>
                              <th className="text-left px-2 py-1.5">Item</th>
                              <th className="text-left px-2 py-1.5">Scanned</th>
                            </tr>
                          </thead>
                          <tbody>
                            {r.items.map(it => (
                              <tr key={`${it.kind ?? "honey"}-${it.id}`} className="border-t">
                                <td className="px-2 py-1.5 font-mono text-xs">{it.code}</td>
                                <td className="px-2 py-1.5 font-mono text-xs">{it.batch_no || "—"}</td>
                                <td className="px-2 py-1.5 capitalize">{packItemLabel(it)}</td>
                                <td className="px-2 py-1.5 text-xs whitespace-nowrap">{formatWhen(it.scanned_at)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      )}

      {!loading && total > 0 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
          <p className="text-xs text-muted-foreground">
            {total} session{total === 1 ? "" : "s"} · page {page} of {totalPages}
          </p>
          <div className="flex gap-2">
            <Button type="button" size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
              Previous
            </Button>
            <Button
              type="button"
              size="sm"
              variant="outline"
              disabled={page >= totalPages}
              onClick={() => setPage(p => p + 1)}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </AppSectionEnter>
  );
}
