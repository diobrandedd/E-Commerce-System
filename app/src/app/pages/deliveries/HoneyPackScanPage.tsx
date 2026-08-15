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
import {
  bottleBarcodesApi,
  type HoneyPackSession,
  type HoneyPackSessionListRow,
} from "@/app/lib/bottleBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";

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
            order. Duplicates in this session are ignored. Units already packed or sold show a distinct
            warning.
          </p>
          <p>
            <strong className="text-foreground">Confirm</strong> deducts honey packaging stock and finished
            dept stock for each scanned unit.
          </p>
          <p>
            <strong className="text-foreground">History</strong> — read-only list of confirmed pack sessions.
            Filter by date, product, or batch code. Expanding a row shows items grouped by batch.
          </p>
        </AppModalBody>
      </AppModal>
    </PageLayout>
  );
}

function PackScanLivePanel() {
  const inputRef = useRef<HTMLInputElement>(null);
  const [session, setSession] = useState<HoneyPackSession | null>(null);
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
      const res = await bottleBarcodesApi.packStart();
      setSession(res.session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start pack session.");
    }
  }, []);

  useEffect(() => {
    void start();
  }, [start]);

  useEffect(() => {
    inputRef.current?.focus();
  }, [session?.id]);

  const onScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    if (!session) return { kind: "error", message: "Pack session is not ready." };
    const value = (raw ?? code).trim();
    if (!value) return;
    setCode("");
    try {
      const res = await bottleBarcodesApi.packScan(session.id, value);
      setSession(res.session);
      if (res.result === "ok") {
        playScanBeep("ok");
        setFlash("ok");
        const label =
          res.lookup?.kind === "dept"
            ? res.lookup.product_label || res.lookup.variant || "Dept item"
            : `${res.lookup?.variant ?? ""} ${res.lookup?.size_label ?? ""}`.trim() || "Honey";
        setMsg(label);
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "success", message: `Scanned: ${label}` };
      } else if (res.result === "duplicate_session") {
        playScanBeep("dup");
        setFlash("dup");
        setMsg("Already scanned in this session");
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "duplicate", message: "Duplicate: already scanned in this session." };
      } else if (res.result === "already_consumed") {
        playScanBeep("bad");
        setFlash("bad");
        setMsg("Already packed or sold earlier");
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "error", message: "Failed: bottle was already packed or sold." };
      } else {
        playScanBeep("bad");
        setFlash("bad");
        const reason = res.result === "unknown" ? "Unknown barcode" : res.result.replace(/_/g, " ");
        setMsg(reason);
        window.setTimeout(() => setFlash(null), 650);
        inputRef.current?.focus();
        return { kind: "error", message: `Failed: ${reason}.` };
      }
    } catch (e) {
      playScanBeep("bad");
      setFlash("bad");
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      setMsg(reason);
      window.setTimeout(() => setFlash(null), 650);
      inputRef.current?.focus();
      return { kind: "error", message: reason };
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
        Honey bottles and Paragis / Black Rice / Cacao / Honey Comb stickers can be mixed in one session.
      </p>
      <div className={`h-20 rounded-xl border flex items-center justify-center gap-2 text-sm font-semibold ${flashClass}`}>
        {flash === "ok" && <CheckCircle2 size={22} />}
        {flash === "bad" && <XCircle size={22} />}
        {flash === "ok" ? "Scanned" : flash === "dup" ? "Already in this session" : flash === "bad" ? "Not accepted" : "Ready"}
      </div>
      {msg && <p className="text-sm">{msg}</p>}
      <p className="text-xl font-bold">{session?.item_count ?? 0} items in session</p>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
        {(session?.grouped ?? []).map(g => (
          <div
            key={`${g.kind ?? "honey"}-${g.product_key ?? g.variant}-${g.size}`}
            className="bg-card border rounded-xl px-3 py-2"
          >
            <p className="text-xs text-muted-foreground capitalize">{packItemLabel(g)}</p>
            <p className="text-lg font-bold">{g.qty}</p>
          </div>
        ))}
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
                              <th className="text-left px-2 py-1.5">Code</th>
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
