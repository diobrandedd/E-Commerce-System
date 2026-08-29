import { useCallback, useEffect, useId, useState } from "react";
import { BookOpen, Camera, CheckCircle2, ChevronDown, ChevronRight, Loader2, XCircle } from "lucide-react";
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
  packCancelRestockApi,
  type PackCancelRestockLookup,
  type PackCancelRestockSession,
} from "@/app/lib/packCancelRestock";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import { DeliveriesCancelledGuideModal } from "@/app/components/fulfillment/DeliveriesCancelledGuideModal";
import { runDeliveriesCancelledTour } from "@/app/components/fulfillment/deliveriesCancelledTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";

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

function itemLabel(g: {
  kind?: string;
  product_label?: string;
  variant: string;
  size_label: string;
  stock_label?: string;
}) {
  const base = g.kind === "dept" ? g.product_label || g.variant : `${g.variant} ${g.size_label}`.trim();
  return g.stock_label ? `${base} · ${g.stock_label}` : base;
}

const PRODUCT_FILTERS = [
  { value: "all", label: "All products" },
  { value: "honey", label: "Honey" },
  { value: "pt", label: "Paragis" },
  { value: "brc", label: "Black Rice" },
  { value: "jar_cacao", label: "Cacao" },
  { value: "honey_comb", label: "Honey Comb" },
];

export function DeliveriesCancelledPage() {
  const [tab, setTab] = useState<"restock" | "history">("restock");
  const [guideOpen, setGuideOpen] = useState(false);

  return (
    <PageLayout
      title="Cancelled restock"
      subtitle="Scan cancelled stickers or static SKU barcodes back to packaging, finished, or RTS stock."
      actions={
        <button
          type="button"
          onClick={() => setGuideOpen(true)}
          className={appHeaderActionClass}
        >
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <div className="flex gap-1 mb-4 p-1 rounded-xl bg-secondary/40 w-fit max-w-full">
        <button
          type="button"
          onClick={() => setTab("restock")}
          className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
            tab === "restock" ? "bg-card text-foreground shadow-sm" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          Restock
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
        {tab === "restock" ? <RestockLivePanel /> : <RestockHistoryPanel />}
      </AppStepSwitch>

      <DeliveriesCancelledGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runDeliveriesCancelledTour());
        }}
      />
    </PageLayout>
  );
}

function RestockLivePanel() {
  const [session, setSession] = useState<PackCancelRestockSession | null>(null);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "dup" | "bad" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [pendingLookup, setPendingLookup] = useState<PackCancelRestockLookup | null>(null);
  const [pendingCode, setPendingCode] = useState("");
  const [pendingQty, setPendingQty] = useState("1");
  const [pendingPool, setPendingPool] = useState<"packaging" | "finished" | "rts_sellable">("packaging");
  const [busy, setBusy] = useState(false);
  const cameraTitleId = useId();
  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, session?.id);

  const start = useCallback(async () => {
    setError(null);
    try {
      const res = await packCancelRestockApi.start();
      setSession(res.session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start restock session.");
    }
  }, []);

  useEffect(() => {
    void start();
  }, [start]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Session not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const res = await packCancelRestockApi.scan(session.id, value);
      setSession(res.session);
      if (res.result === "sku_requires_details" && res.lookup) {
        setPendingLookup(res.lookup);
        setPendingCode(res.lookup.code || value);
        setPendingQty("1");
        setPendingPool(res.lookup.kind === "honey" ? "packaging" : "finished");
        setCameraOpen(false);
        setMsg(`${res.lookup.label || res.lookup.product_label || "SKU"} — enter quantity and destination`);
        return { kind: "success", message: "SKU found. Enter quantity and destination." };
      }
      if (res.result === "ok") {
        playScanBeep("ok");
        setFlash("ok");
        const stock = res.lookup?.stock_label ? ` → ${res.lookup.stock_label}` : "";
        const label =
          res.lookup?.kind === "dept"
            ? `${res.lookup.product_label || "Dept"}${stock}`
            : `${`${res.lookup?.variant ?? ""} ${res.lookup?.size_label ?? ""}`.trim() || "Honey"}${stock}`;
        setMsg(label);
        window.setTimeout(() => setFlash(null), 650);
        refocus();
        return { kind: "success", message: `Restock queued: ${label}` };
      }
      if (res.result === "duplicate_session") {
        playScanBeep("dup");
        setFlash("dup");
        setMsg("Already in this session");
        window.setTimeout(() => setFlash(null), 650);
        return { kind: "duplicate", message: "Already in this session." };
      }
      playScanBeep("bad");
      setFlash("bad");
      const reason = res.result.replace(/_/g, " ");
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      return { kind: "error", message: reason };
    } catch (e) {
      playScanBeep("bad");
      setFlash("bad");
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      return { kind: "error", message: reason };
    }
  };

  const queueSku = async () => {
    if (!session || !pendingLookup || !pendingCode) return;
    const qty = Number.parseInt(pendingQty, 10);
    if (!Number.isInteger(qty) || qty < 1 || qty > 5000) {
      setError("Quantity must be a whole number from 1 to 5000.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await packCancelRestockApi.scan(session.id, pendingCode, { qty, pool: pendingPool });
      setSession(res.session);
      setPendingLookup(null);
      setPendingCode("");
      setFlash("ok");
      setMsg(`Restock queued: ${res.lookup?.label || res.lookup?.product_label || "SKU"} × ${qty}`);
      window.setTimeout(() => setFlash(null), 650);
      refocus();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not queue SKU return.");
    } finally {
      setBusy(false);
    }
  };

  const confirm = async () => {
    if (!session) return;
    setBusy(true);
    setError(null);
    try {
      const res = await packCancelRestockApi.confirm(session.id);
      setOk(
          `Restored ${res.session.item_count} item${res.session.item_count === 1 ? "" : "s"} to stock (packaging / finished / RTS by pool).`,
        );
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
    await packCancelRestockApi.discard(session.id);
    setOk("Session discarded.");
    await start();
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
      <p className="text-xs text-muted-foreground">
        Only stickers that are <strong className="text-foreground">packed</strong>,{" "}
        <strong className="text-foreground">shipped</strong>, or POS walk-in{" "}
        <strong className="text-foreground">sold</strong> can be returned. Stock goes back to packaging,
        finished, or RTS by the unit&apos;s original pool.
        Confirm restores packaging, finished, or RTS inventory by each unit&apos;s pool.
        Static SKU barcodes ask for a quantity and destination before they are queued.
      </p>
      <div className="flex gap-2">
        <input
          ref={inputRef}
          {...inputProps}
          className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide font-mono"
          placeholder="Scan packed / sold barcode…"
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
      <div className={`h-20 rounded-xl border flex items-center justify-center gap-2 text-sm font-semibold ${flashClass}`}>
        {flash === "ok" && <CheckCircle2 size={22} />}
        {flash === "bad" && <XCircle size={22} />}
        {flash === "ok" ? "Queued" : flash === "dup" ? "Already in session" : flash === "bad" ? "Not accepted" : "Ready"}
      </div>
      {msg && <p className="text-sm">{msg}</p>}
      <p className="text-xl font-bold">{session?.item_count ?? 0} items to restore</p>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
        {(session?.grouped ?? []).map(g => (
          <div key={`${g.kind}-${g.product_key}-${g.size_label}-${g.unit_pool ?? "default"}`} className="bg-card border rounded-xl px-3 py-2">
            <p className="text-xs text-muted-foreground capitalize">{itemLabel(g)}</p>
            <p className="text-lg font-bold">{g.qty}</p>
          </div>
        ))}
      </div>
      <div className="flex flex-wrap gap-2">
        <Button type="button" onClick={() => setConfirmOpen(true)} disabled={!session || session.item_count < 1}>
          Confirm restock
        </Button>
        <Button type="button" variant="outline" onClick={() => void discard()} disabled={!session || session.item_count < 1}>
          Discard session
        </Button>
      </div>
      <AppModal open={confirmOpen} onClose={() => setConfirmOpen(false)}>
        <AppModalHeader title="Confirm inventory restore" onClose={() => setConfirmOpen(false)} />
        <AppModalBody className="space-y-2">
          <p className="text-sm text-muted-foreground">
            These return to packaging, finished, or RTS stock (by pool).
          </p>
          {(session?.grouped ?? []).map(g => (
            <p key={`${g.kind}-${g.product_key}-${g.size_label}-${g.unit_pool ?? "default"}`} className="text-sm font-medium capitalize">
              {g.qty} × {itemLabel(g)}
            </p>
          ))}
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setConfirmOpen(false)}>
            Keep scanning
          </Button>
          <Button type="button" onClick={() => void confirm()} disabled={busy}>
            {busy ? <Loader2 className="animate-spin" size={16} /> : null}
            Confirm
          </Button>
        </AppModalFooter>
      </AppModal>
      <AppModal open={pendingLookup !== null} onClose={() => setPendingLookup(null)}>
        <AppModalHeader title="Return static SKU to stock" onClose={() => setPendingLookup(null)} />
        <AppModalBody className="space-y-4">
          <div className="rounded-lg border bg-secondary/20 px-3 py-2">
            <p className="text-sm font-semibold">
              {pendingLookup?.label || pendingLookup?.product_label || "SKU barcode"}
            </p>
            <p className="text-xs font-mono text-muted-foreground">{pendingCode || "—"}</p>
          </div>
          <label className="block text-sm font-medium">
            Quantity
            <input
              type="number"
              min={1}
              max={5000}
              step={1}
              className="mt-1 block w-full border rounded-lg px-3 py-2 bg-background"
              value={pendingQty}
              onChange={e => setPendingQty(e.target.value)}
              autoFocus
            />
          </label>
          <label className="block text-sm font-medium">
            Destination pool
            <select
              className="mt-1 block w-full border rounded-lg px-3 py-2 bg-background"
              value={pendingPool}
              onChange={e => setPendingPool(e.target.value as typeof pendingPool)}
            >
              {(pendingLookup?.kind === "honey"
                ? [
                    { value: "packaging", label: "Packaging" },
                    { value: "rts_sellable", label: "RTS sellable" },
                  ]
                : [
                    { value: "finished", label: "Finished" },
                    { value: "rts_sellable", label: "RTS sellable" },
                  ]
              ).map(option => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </label>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={() => setPendingLookup(null)}>
            Cancel
          </Button>
          <Button type="button" onClick={() => void queueSku()} disabled={busy}>
            {busy ? <Loader2 className="animate-spin" size={16} /> : null}
            Queue return
          </Button>
        </AppModalFooter>
      </AppModal>
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan barcode to restock"
          titleId={cameraTitleId}
          continuous
          scanPrompt="Scan each packed, shipped, or POS sold sticker to return to stock."
          onClose={() => setCameraOpen(false)}
          onDecoded={decoded => onScan(decoded)}
        />
      ) : null}
    </AppSectionEnter>
  );
}

function RestockHistoryPanel() {
  const [from, setFrom] = useState(() => daysAgoIso(13));
  const [to, setTo] = useState(() => todayIso());
  const [productKey, setProductKey] = useState("all");
  const [applied, setApplied] = useState({ from: daysAgoIso(13), to: todayIso(), product_key: "all" });
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<PackCancelRestockSession[]>([]);
  const [total, setTotal] = useState(0);
  const [perPage, setPerPage] = useState(30);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expanded, setExpanded] = useState<number | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await packCancelRestockApi.list({
        from: applied.from,
        to: applied.to,
        product_key: applied.product_key !== "all" ? applied.product_key : undefined,
        page,
      });
      setRows(res.rows);
      setTotal(res.total);
      setPerPage(res.per_page);
    } catch (e) {
      setRows([]);
      setError(e instanceof ApiError ? e.message : "Could not load restock history.");
    } finally {
      setLoading(false);
    }
  }, [applied, page]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const totalPages = Math.max(1, Math.ceil(total / perPage));

  return (
    <AppSectionEnter className="space-y-4">
      <p className="text-xs text-muted-foreground">
        Confirmed restock sessions — units and quantity-based SKU returns after courier cancel or POS walk-in cancel.
      </p>
      <div className="flex flex-wrap gap-2 items-end bg-card border rounded-xl p-3">
        <label className="text-xs text-muted-foreground">
          From
          <input type="date" className="mt-1 block border rounded-lg px-2 py-1.5 text-sm" value={from} onChange={e => setFrom(e.target.value)} />
        </label>
        <label className="text-xs text-muted-foreground">
          To
          <input type="date" className="mt-1 block border rounded-lg px-2 py-1.5 text-sm" value={to} onChange={e => setTo(e.target.value)} />
        </label>
        <label className="text-xs text-muted-foreground">
          Product
          <select
            className="mt-1 block border rounded-lg px-2 py-1.5 text-sm bg-background min-w-[9rem]"
            value={productKey}
            onChange={e => setProductKey(e.target.value)}
          >
            {PRODUCT_FILTERS.map(opt => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
        </label>
        <Button
          type="button"
          size="sm"
          onClick={() => {
            setPage(1);
            setExpanded(null);
            setApplied({ from: from || daysAgoIso(13), to: to || todayIso(), product_key: productKey });
          }}
        >
          Apply
        </Button>
      </div>
      {error && <AppErrorBanner message={error} />}
      {loading ? (
        <p className="text-sm text-muted-foreground py-8 text-center">
          <Loader2 className="inline animate-spin" size={16} /> Loading…
        </p>
      ) : rows.length === 0 ? (
        <p className="text-sm text-muted-foreground py-8 text-center">No confirmed restocks in this range.</p>
      ) : (
        <div className="space-y-2">
          {rows.map(r => {
            const open = expanded === r.id;
            const summary = (r.grouped ?? []).map(g => `${g.qty}× ${itemLabel(g)}`).join(" · ");
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
                      {r.confirmed_by_name || "—"} · {r.item_count} returned
                    </p>
                    {summary ? <p className="text-xs capitalize text-muted-foreground truncate">{summary}</p> : null}
                  </div>
                </button>
                {open ? (
                  <div className="border-t px-3 py-3 space-y-2 bg-secondary/20 max-h-56 overflow-y-auto">
                    <table className="w-full text-sm">
                      <thead className="text-xs uppercase text-muted-foreground">
                        <tr>
                          <th className="text-left px-2 py-1">Code</th>
                          <th className="text-left px-2 py-1">Item</th>
                          <th className="text-left px-2 py-1">Qty</th>
                          <th className="text-left px-2 py-1">Batch</th>
                          <th className="text-left px-2 py-1">Scanned by</th>
                        </tr>
                      </thead>
                      <tbody>
                        {r.items.map(it => (
                          <tr key={`${it.kind}-${it.id}`} className="border-t">
                            <td className="px-2 py-1 font-mono text-xs">{it.code}</td>
                            <td className="px-2 py-1 capitalize">{itemLabel(it)}</td>
                            <td className="px-2 py-1">{it.qty ?? 1}</td>
                            <td className="px-2 py-1 font-mono text-xs">{it.batch_no || "—"}</td>
                            <td className="px-2 py-1 text-xs">
                              {it.scanned_by_name || "—"}
                              <span className="block text-muted-foreground">{formatWhen(it.scanned_at)}</span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      )}
      {!loading && total > 0 && (
        <div className="flex items-center justify-between gap-3">
          <p className="text-xs text-muted-foreground">
            {total} session{total === 1 ? "" : "s"} · page {page} of {totalPages}
          </p>
          <div className="flex gap-2">
            <Button type="button" size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>
              Previous
            </Button>
            <Button type="button" size="sm" variant="outline" disabled={page >= totalPages} onClick={() => setPage(p => p + 1)}>
              Next
            </Button>
          </div>
        </div>
      )}
    </AppSectionEnter>
  );
}
