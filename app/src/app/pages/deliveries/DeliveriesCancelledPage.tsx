import { useCallback, useEffect, useId, useRef, useState } from "react";
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
import { packCancelRestockApi, type PackCancelRestockSession } from "@/app/lib/packCancelRestock";
import { playScanBeep } from "@/app/lib/scanBeep";
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

function itemLabel(g: { kind?: string; product_label?: string; variant: string; size_label: string }) {
  if (g.kind === "dept") return g.product_label || g.variant;
  return `${g.variant} ${g.size_label}`.trim();
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
      subtitle="Scan packed stickers back to finished stock after courier cancel. Sealed-box re-waybill stays on Hold."
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
  const inputRef = useRef<HTMLInputElement>(null);
  const [session, setSession] = useState<PackCancelRestockSession | null>(null);
  const [code, setCode] = useState("");
  const [flash, setFlash] = useState<"ok" | "dup" | "bad" | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const cameraTitleId = useId();

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

  useEffect(() => {
    inputRef.current?.focus();
  }, [session?.id]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Session not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const res = await packCancelRestockApi.scan(session.id, value);
      setSession(res.session);
      if (res.result === "ok") {
        playScanBeep("ok");
        setFlash("ok");
        const label =
          res.lookup?.kind === "dept"
            ? res.lookup.product_label || "Dept"
            : `${res.lookup?.variant ?? ""} ${res.lookup?.size_label ?? ""}`.trim() || "Honey";
        setMsg(label);
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
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

  const confirm = async () => {
    if (!session) return;
    setBusy(true);
    setError(null);
    try {
      const res = await packCancelRestockApi.confirm(session.id);
      setOk(`Restored ${res.session.item_count} item${res.session.item_count === 1 ? "" : "s"} to finished stock.`);
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
        Only stickers marked <strong className="text-foreground">packed</strong> (from Pack scan) can be returned.
        Confirm restores finished inventory.
      </p>
      <div className="flex gap-2">
        <input
          ref={inputRef}
          className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide font-mono"
          placeholder="Scan packed barcode…"
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
          <div key={`${g.kind}-${g.product_key}-${g.size_label}`} className="bg-card border rounded-xl px-3 py-2">
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
          <p className="text-sm text-muted-foreground">These return to finished stock now.</p>
          {(session?.grouped ?? []).map(g => (
            <p key={`${g.kind}-${g.product_key}`} className="text-sm font-medium capitalize">
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
      {cameraOpen ? (
        <BarcodeCameraScannerModal
          title="Scan packed barcode"
          titleId={cameraTitleId}
          continuous
          scanPrompt="Scan each packed sticker returning from courier cancel."
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
        Confirmed restock sessions — how many units returned to inventory after courier cancel.
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
                          <th className="text-left px-2 py-1">Batch</th>
                        </tr>
                      </thead>
                      <tbody>
                        {r.items.map(it => (
                          <tr key={`${it.kind}-${it.id}`} className="border-t">
                            <td className="px-2 py-1 font-mono text-xs">{it.code}</td>
                            <td className="px-2 py-1 capitalize">{itemLabel(it)}</td>
                            <td className="px-2 py-1 font-mono text-xs">{it.batch_no || "—"}</td>
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
