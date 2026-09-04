import { useCallback, useEffect, useId, useState } from "react";
import { BookOpen, Camera, CheckCircle2, ChevronDown, ChevronRight, Loader2, Trash2, XCircle } from "lucide-react";
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
import { skuBarcodesApi, type SkuLookup } from "@/app/lib/skuBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

function todayIso() {
  return new Date().toISOString().slice(0, 10);
}

function daysAgoIso(days: number) {
  const d = new Date();
  d.setDate(d.getDate() - days);
  return d.toISOString().slice(0, 10);
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
            order. Tap a product row to see its barcodes. Remove a mistaken scan before you confirm.
            Duplicates in this session are ignored. Units already packed or sold show a distinct warning.
          </p>
          <p>
            <strong className="text-foreground">Confirm</strong> deducts honey packaging stock and finished
            dept stock. Unique stickers are applied first, then SKU quantity lines (so mixed sessions do not
            empty a batch a leftover sticker still needs).
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
  const [pendingSku, setPendingSku] = useState<{ code: string; lookup: SkuLookup } | null>(null);
  const [skuQty, setSkuQty] = useState("1");
  const [skuPool, setSkuPool] = useState<"packaging" | "rts_sellable" | "finished">("packaging");
  const cameraTitleId = useId();
  const qtyTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, session?.id);

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
  }, [start]);

  const applyPackResult = (
    res: Awaited<ReturnType<typeof bottleBarcodesApi.packScan>>,
  ): ScannerDecodeFeedback => {
    setSession(res.session);
    if (res.result === "ok") {
      playScanBeep("ok");
      setFlash("ok");
      const label =
        res.lookup?.kind === "dept"
          ? res.lookup.product_label || res.lookup.variant || "Dept item"
          : `${res.lookup?.variant ?? ""} ${res.lookup?.size_label ?? ""}`.trim() || "Honey";
      const qtyNote = res.lookup?.qty != null && res.lookup.qty !== 1 ? ` ×${res.lookup.qty}` : "";
      setMsg(`${label}${qtyNote}`);
      const last = res.session.items[res.session.items.length - 1];
      if (last) {
        const groupKey = packItemGroupKey(last);
        setExpandedGroups(prev => (prev.includes(groupKey) ? prev : [...prev, groupKey]));
      }
      window.setTimeout(() => setFlash(null), 650);
      refocus();
      return { kind: "success", message: `Scanned: ${label}` };
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

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Pack session is not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const looked = await skuBarcodesApi.lookup(value);
      const lookup = looked.lookup;
      if (lookup.lookup_kind === "sku" || lookup.requires_qty) {
        setPendingSku({ code: value, lookup });
        setSkuQty("1");
        setSkuPool(lookup.kind === "dept" ? "finished" : "packaging");
        return;
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

  const confirmSkuPack = async () => {
    if (!session || !pendingSku) return;
    const qty = Math.max(1, parseInt(skuQty, 10) || 1);
    const pending = pendingSku;
    setPendingSku(null);
    try {
      const res = await bottleBarcodesApi.packScan(session.id, pending.code, {
        qty,
        pool: skuPool,
      });
      applyPackResult(res);
    } catch (e) {
      playScanBeep("bad");
      setFlash("bad");
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      refocus();
    }
  };

  const confirm = async () => {
    if (!session) return;
    setBusy(true);
    setError(null);
    try {
      const res = await bottleBarcodesApi.packConfirm(session.id);
      setOk(`Packed ${res.session.item_count} item${res.session.item_count === 1 ? "" : "s"}.`);
      setConfirmOpen(false);
      await start();
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

  const toggleGroup = (key: string) => {
    setExpandedGroups(prev => (prev.includes(key) ? prev.filter(k => k !== key) : [...prev, key]));
  };

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
      <p className="text-xs text-muted-foreground">
        Honey bottles and Paragis / Black Rice / Cacao / Honey Comb stickers can be mixed in one session.
      </p>
      <div className={`h-20 rounded-xl border flex items-center justify-center gap-2 text-sm font-semibold ${flashClass}`}>
        {flash === "ok" && <CheckCircle2 size={22} />}
        {flash === "bad" && <XCircle size={22} />}
        {flash === "ok" ? "Scanned" : flash === "dup" ? "Already in this session" : flash === "bad" ? "Not accepted" : "Ready"}
      </div>
      {msg && <p className="text-sm">{msg}</p>}
      <p className="text-xl font-bold">{session?.item_count ?? 0} items in session</p>
      <div className="space-y-2">
        {(session?.grouped ?? []).map(g => {
          const key = packGroupKey(g);
          const open = expandedGroups.includes(key);
          const barcodes = (session?.items ?? []).filter(it => packItemGroupKey(it) === key);
          return (
            <div key={key} className="bg-card border rounded-xl overflow-hidden">
              <button
                type="button"
                className={`w-full text-left px-3 py-2.5 flex items-center gap-2 ${appHoverRow}`}
                onClick={() => toggleGroup(key)}
              >
                <span className="text-muted-foreground shrink-0">
                  {open ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block text-xs text-muted-foreground capitalize">{packItemLabel(g)}</span>
                  <span className="text-lg font-bold leading-tight">{g.qty}</span>
                </span>
              </button>
              {open ? (
                <div className="border-t bg-secondary/20 px-3 py-2 space-y-1">
                  {barcodes.length === 0 ? (
                    <p className="text-xs text-muted-foreground py-1">No barcodes.</p>
                  ) : (
                    barcodes.map(it => {
                      const rowKey = `${it.kind ?? "honey"}-${it.id}`;
                      const removing = removingId === rowKey;
                      return (
                        <div key={rowKey} className="flex items-center gap-2 py-1">
                          <div className="min-w-0 flex-1">
                            <p className="font-mono text-xs">{it.code}</p>
                            {it.batch_no ? (
                              <p className="text-[10px] text-muted-foreground font-mono">{it.batch_no}</p>
                            ) : null}
                          </div>
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
        <Button type="button" onClick={() => setConfirmOpen(true)} disabled={!session || session.item_count < 1}>
          Done scanning
        </Button>
        <Button type="button" variant="outline" onClick={() => void discard()} disabled={!session || session.item_count < 1}>
          Discard session
        </Button>
      </div>
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
      <AppModal
        open={pendingSku !== null}
        onClose={() => {
          setPendingSku(null);
          refocus();
        }}
        labelledBy={qtyTitleId}
        panelClassName="max-w-sm w-full"
      >
        <AppModalHeader
          titleId={qtyTitleId}
          title="How many to pack?"
          subtitle={
            pendingSku
              ? pendingSku.lookup.label ??
                pendingSku.lookup.product_label ??
                pendingSku.code
              : undefined
          }
          onClose={() => {
            setPendingSku(null);
            refocus();
          }}
        />
        <AppModalBody className="space-y-3">
          <label className="text-xs text-muted-foreground block">
            Quantity
            <input
              type="number"
              min={1}
              autoFocus
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={skuQty}
              onChange={e => setSkuQty(e.target.value)}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  void confirmSkuPack();
                }
              }}
            />
          </label>
          <label className="text-xs text-muted-foreground block">
            Stock pool
            <select
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
              value={skuPool}
              onChange={e =>
                setSkuPool(e.target.value as "packaging" | "rts_sellable" | "finished")
              }
            >
              {pendingSku?.lookup.kind === "dept" ? (
                <>
                  <option value="finished">Finished</option>
                  <option value="rts_sellable">RTS sellable</option>
                </>
              ) : (
                <>
                  <option value="packaging">Packaging</option>
                  <option value="rts_sellable">RTS sellable</option>
                </>
              )}
            </select>
          </label>
        </AppModalBody>
        <AppModalFooter>
          <Button
            type="button"
            variant="outline"
            onClick={() => {
              setPendingSku(null);
              refocus();
            }}
          >
            Cancel
          </Button>
          <Button type="button" onClick={() => void confirmSkuPack()}>
            Add to pack
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
