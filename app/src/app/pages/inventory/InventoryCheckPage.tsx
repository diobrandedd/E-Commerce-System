import { useCallback, useEffect, useId, useRef, useState } from "react";
import { Camera, ClipboardCheck, Loader2, Plus, Scale, Trash2, XCircle } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { InventoryCheckHistoryDetailModal } from "@/app/components/inventory/InventoryCheckHistoryDetailModal";
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
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import { skuBarcodesApi } from "@/app/lib/skuBarcodes";
import {
  inventoryCheckApi,
  type InventoryCheckCompareResult,
  type InventoryCheckContext,
  type InventoryCheckEvent,
  type InventoryCheckScan,
  type InventoryCheckSummary,
} from "@/app/lib/inventoryCheck";

function varianceTone(variance: number): string {
  if (Math.abs(variance) < 0.001) return "text-muted-foreground";
  return variance > 0 ? "text-emerald-600" : "text-destructive";
}

function formatQty(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

export function InventoryCheckPage() {
  const cameraTitleId = useId();
  const addCameraTitleId = useId();
  const [ctx, setCtx] = useState<InventoryCheckContext | null>(null);
  const [summary, setSummary] = useState<InventoryCheckSummary | null>(null);
  const [lines, setLines] = useState<InventoryCheckScan[]>([]);
  const [history, setHistory] = useState<InventoryCheckEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [showHistory, setShowHistory] = useState(false);

  const [addOpen, setAddOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [code, setCode] = useState("");
  /** Batch required for SKU continuous scan. */
  const [stickyBatch, setStickyBatch] = useState("");
  /** Last successful scan — close camera and set qty here for SKUs. */
  const [lastItem, setLastItem] = useState<{
    scanId: number;
    code: string;
    label: string;
    isSku: boolean;
    batch: string;
    countedQty: number;
  } | null>(null);
  const [lastQty, setLastQty] = useState("1");
  const [qtyBusy, setQtyBusy] = useState(false);
  const [scanFlash, setScanFlash] = useState<"ok" | "err" | "dup" | null>(null);
  const [scanFlashMsg, setScanFlashMsg] = useState<string | null>(null);
  const qtyInputRef = useRef<HTMLInputElement>(null);
  const lastItemRef = useRef(lastItem);
  lastItemRef.current = lastItem;

  const [compareOpen, setCompareOpen] = useState(false);
  const [compare, setCompare] = useState<InventoryCheckCompareResult | null>(null);
  const [historyDetail, setHistoryDetail] = useState<InventoryCheckEvent | null>(null);

  const [editId, setEditId] = useState<number | null>(null);
  const [editQty, setEditQty] = useState("");
  const [editBatch, setEditBatch] = useState("");

  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, addOpen);

  const event = ctx?.event ?? null;
  const canViewAll = ctx?.can_view_all ?? false;
  const canManage = ctx?.can_manage ?? false;
  const userId = ctx?.user_id ?? 0;
  const sessionOpen = event !== null && event.status === "open";

  const flash = (kind: "ok" | "err" | "dup", message: string) => {
    setScanFlash(kind);
    setScanFlashMsg(message);
    window.setTimeout(() => {
      setScanFlash(null);
    }, 900);
  };

  const loadLines = useCallback(async (eventId: number) => {
    const res = await inventoryCheckApi.scanLog(eventId, {}, 1, 500);
    setLines(res.rows);
  }, []);

  const loadContext = useCallback(async (opts?: { silent?: boolean }) => {
    if (!opts?.silent) setLoading(true);
    try {
      const res = await inventoryCheckApi.context();
      setCtx(res);
      setSummary(res.summary);
      if (res.event) {
        await loadLines(res.event.id);
      } else {
        setLines([]);
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load the inventory audit.");
    } finally {
      if (!opts?.silent) setLoading(false);
    }
  }, [loadLines]);

  useEffect(() => {
    void loadContext();
  }, [loadContext]);

  useEffect(() => {
    if (!showHistory || !canViewAll) return;
    void (async () => {
      try {
        const res = await inventoryCheckApi.eventList(1);
        setHistory(res.rows);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load audit history.");
      }
    })();
  }, [showHistory, canViewAll]);

  const openCount = async () => {
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.openEvent();
      setOk("Audit session started — add products to the list.");
      await loadContext({ silent: true });
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start the audit.");
    } finally {
      setBusy(false);
    }
  };

  const resetAddForm = () => {
    setCode("");
    setStickyBatch("");
    setLastItem(null);
    setLastQty("1");
    setQtyBusy(false);
    setScanFlash(null);
    setScanFlashMsg(null);
  };

  const closeAddModal = () => {
    setAddOpen(false);
    setCameraOpen(false);
    resetAddForm();
  };

  const closeCamera = () => {
    setCameraOpen(false);
    window.setTimeout(() => {
      if (lastItemRef.current?.isSku) {
        qtyInputRef.current?.focus();
        qtyInputRef.current?.select();
      } else {
        refocus();
      }
    }, 50);
  };

  const cancelSession = async () => {
    if (!event) return;
    if (
      !window.confirm(
        "Cancel this audit session? All scanned lines will be discarded and nothing is saved to history.",
      )
    ) {
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.cancelEvent(event.id);
      setOk("Audit session cancelled.");
      setCompareOpen(false);
      closeAddModal();
      await loadContext({ silent: true });
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not cancel the session.");
    } finally {
      setBusy(false);
    }
  };

  /** Continuous commit: SKU +1; unique once. No page reload. */
  const commitScan = async (
    value: string,
    opts: { qty: number; batch: string; label: string; isSku: boolean },
  ): Promise<boolean> => {
    if (!event) return false;
    try {
      const res = await inventoryCheckApi.scan(event.id, value, {
        qty: opts.qty,
        batch_no: opts.batch,
      });
      if (res.result === "unknown") {
        playScanBeep("bad");
        flash("err", res.message);
        return false;
      }
      if (res.result === "duplicate_event") {
        playScanBeep("dup");
        flash("dup", opts.isSku ? res.message : "Already counted (unique barcode).");
        return false;
      }
      playScanBeep("ok");
      const total = res.scan?.counted_qty ?? opts.qty;
      const msg = opts.isSku
        ? `${opts.label} · +${opts.qty} (total ${total}) · ${opts.batch}`
        : `${opts.label} · counted · ${opts.batch}`;
      flash("ok", msg);
      if (res.summary) {
        setSummary(res.summary);
        setCtx(prev =>
          prev
            ? {
                ...prev,
                event: res.summary.event ?? prev.event,
                summary: res.summary,
              }
            : prev,
        );
      }
      if (res.scan) {
        const scanned = res.scan;
        setLines(prev => {
          const idx = prev.findIndex(r => r.id === scanned.id);
          if (idx >= 0) {
            const next = [...prev];
            next[idx] = scanned;
            return next;
          }
          return [scanned, ...prev];
        });
        setLastItem({
          scanId: scanned.id,
          code: scanned.code,
          label: opts.label,
          isSku: opts.isSku,
          batch: opts.batch,
          countedQty: scanned.counted_qty ?? opts.qty,
        });
        setLastQty(String(scanned.counted_qty ?? opts.qty));
      }
      return true;
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Scan failed.";
      playScanBeep("bad");
      flash("err", reason);
      return false;
    }
  };

  /** Always continuous: each scan commits immediately. */
  const onAddScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value || !event) return;
    setCode("");

    try {
      const looked = await skuBarcodesApi.lookup(value);
      const lookup = looked.lookup;

      if (lookup.lookup_kind === "sku") {
        const label = lookup.label ?? lookup.product_label ?? value;
        const batch = stickyBatch.trim().toUpperCase();
        if (!batch) {
          playScanBeep("bad");
          flash("err", "Enter batch number first, then keep scanning.");
          refocus();
          return { kind: "error", message: "Batch number required." };
        }
        const okAdd = await commitScan(value, { qty: 1, batch, label, isSku: true });
        refocus();
        return okAdd
          ? { kind: "success", message: `${label} · +1` }
          : { kind: "error", message: "Not added." };
      }

      if (lookup.lookup_kind === "unique_honey" || lookup.lookup_kind === "unique_dept") {
        const label =
          lookup.label ??
          ([lookup.product_label, lookup.size_label, lookup.variant].filter(Boolean).join(" · ") ||
            value);
        const suggested = String(lookup.batch_no ?? "").toUpperCase();
        const batch = suggested || stickyBatch.trim().toUpperCase();
        if (!batch) {
          playScanBeep("bad");
          flash("err", "This barcode has no batch — enter batch number first.");
          refocus();
          return { kind: "error", message: "Batch required." };
        }
        if (suggested) setStickyBatch(suggested);
        const okAdd = await commitScan(value, { qty: 1, batch, label, isSku: false });
        refocus();
        return okAdd
          ? { kind: "success", message: `${label} · counted` }
          : { kind: "error", message: "Not added." };
      }

      playScanBeep("bad");
      flash("err", "Unknown barcode.");
      refocus();
      return { kind: "error", message: "Unknown barcode." };
    } catch (e) {
      playScanBeep("bad");
      const msg = e instanceof ApiError ? e.message : "Unknown barcode.";
      flash("err", msg);
      refocus();
      return { kind: "error", message: msg };
    }
  };

  /** After continuous scan: set how many for the SKU just scanned. */
  const applyLastQty = async () => {
    if (!event || !lastItem?.isSku) return;
    const n = Math.max(1, parseInt(lastQty, 10) || 1);
    setQtyBusy(true);
    try {
      const res = await inventoryCheckApi.scanUpdate(event.id, lastItem.scanId, {
        qty: n,
        batch_no: lastItem.batch,
      });
      if (res.summary) {
        setSummary(res.summary);
        setCtx(prev =>
          prev
            ? { ...prev, event: res.summary.event ?? prev.event, summary: res.summary }
            : prev,
        );
      }
      setLines(prev => prev.map(r => (r.id === lastItem.scanId ? res.scan : r)));
      setLastItem(prev => (prev ? { ...prev, countedQty: n } : prev));
      setLastQty(String(n));
      playScanBeep("ok");
      flash("ok", `${lastItem.label} · set to ${n}`);
      refocus();
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Could not update quantity.";
      playScanBeep("bad");
      flash("err", reason);
    } finally {
      setQtyBusy(false);
    }
  };

  const removeScan = async (scanId: number) => {
    if (!event) return;
    setError(null);
    try {
      const res = await inventoryCheckApi.scanRemove(event.id, scanId);
      if (res.summary) {
        setSummary(res.summary);
        setCtx(prev =>
          prev
            ? { ...prev, event: res.summary.event ?? prev.event, summary: res.summary }
            : prev,
        );
      }
      setLines(prev => prev.filter(r => r.id !== scanId));
      setOk("Line removed.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not remove that line.");
    }
  };

  const startEdit = (row: InventoryCheckScan) => {
    setEditId(row.id);
    setEditQty(String(row.counted_qty ?? 1));
    setEditBatch((row.batch_no ?? "").toUpperCase());
  };

  const saveEdit = async () => {
    if (!event || editId == null) return;
    const n = Math.max(1, parseInt(editQty, 10) || 1);
    const batch = editBatch.trim().toUpperCase();
    if (!batch) {
      setError("Batch number is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const res = await inventoryCheckApi.scanUpdate(event.id, editId, { qty: n, batch_no: batch });
      if (res.summary) {
        setSummary(res.summary);
        setCtx(prev =>
          prev
            ? { ...prev, event: res.summary.event ?? prev.event, summary: res.summary }
            : prev,
        );
      }
      setLines(prev => prev.map(r => (r.id === editId ? res.scan : r)));
      setEditId(null);
      setOk("Line updated.");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not update that line.");
    } finally {
      setBusy(false);
    }
  };

  const openComplete = async () => {
    if (!event) return;
    setBusy(true);
    setError(null);
    try {
      const preview = await inventoryCheckApi.comparePreview(event.id);
      setCompare(preview);
      setCompareOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not build comparison.");
    } finally {
      setBusy(false);
    }
  };

  const finalize = async () => {
    if (!event) return;
    setBusy(true);
    setError(null);
    try {
      const res = await inventoryCheckApi.finalize(event.id);
      setCompare(res.compare);
      setOk("Audit saved to history.");
      setCompareOpen(false);
      await loadContext();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save the audit.");
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <PageLayout title="Check inventory" subtitle="Audit finished products against system inventory.">
        <p className="text-sm text-muted-foreground flex items-center gap-2">
          <Loader2 className="animate-spin" size={16} /> Loading…
        </p>
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Check inventory"
      subtitle="Audit finished products on hand. Counting never changes stock."
    >
      <AppSectionEnter className="space-y-4">
        <div className="rounded-xl border border-border bg-card px-4 py-3 flex flex-wrap items-center justify-between gap-3">
          <div>
            {event === null ? (
              <p className="text-sm text-muted-foreground">
                No audit session is open.{" "}
                {canManage ? "Start one to begin counting." : "Ask a manager to start today's audit."}
              </p>
            ) : (
              <>
                <p className="text-sm font-semibold">
                  Audit for {event.count_date}
                  <span
                    className={`ml-2 text-xs font-medium rounded-full px-2 py-0.5 ${
                      event.status === "open"
                        ? "bg-emerald-100 text-emerald-700"
                        : "bg-secondary text-muted-foreground"
                    }`}
                  >
                    {event.status === "open" ? "Open" : "Closed"}
                  </span>
                </p>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Opened by {event.opened_by_name}
                  {event.closed_by_name ? ` · Closed by ${event.closed_by_name}` : ""} · {event.scan_count}{" "}
                  line{event.scan_count === 1 ? "" : "s"} · {event.scanner_count} staff
                </p>
              </>
            )}
          </div>
          <div className="flex flex-wrap gap-2">
            {canViewAll && (
              <Button type="button" size="sm" variant="outline" onClick={() => setShowHistory(h => !h)}>
                {showHistory ? "Session" : "History"}
              </Button>
            )}
            {canManage && (event === null || event.status === "closed") && (
              <Button type="button" onClick={() => void openCount()} disabled={busy}>
                <ClipboardCheck size={16} className="mr-1" /> Start audit
              </Button>
            )}
            {canManage && sessionOpen && (
              <>
                <Button
                  type="button"
                  variant="outline"
                  className="text-destructive border-destructive/40"
                  onClick={() => void cancelSession()}
                  disabled={busy}
                >
                  <XCircle size={16} className="mr-1" /> Cancel session
                </Button>
                <Button type="button" onClick={() => void openComplete()} disabled={busy || lines.length === 0}>
                  <Scale size={16} className="mr-1" /> Complete
                </Button>
              </>
            )}
          </div>
        </div>

        {ok && <AppSuccessNotice message={ok} />}
        {error && <AppErrorBanner message={error} />}

        {showHistory && canViewAll ? (
          <div className="space-y-2">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Audit history</p>
            {history.length === 0 ? (
              <p className="text-sm text-muted-foreground">No audits recorded yet.</p>
            ) : (
              <>
                <div className="sm:hidden space-y-3">
                  {history.map(h => (
                    <article key={h.id} className="rounded-xl border border-border bg-card p-4 space-y-3">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <p className="text-sm font-semibold">{h.count_date}</p>
                          <p className="text-xs text-muted-foreground mt-0.5">
                            {h.status === "open" ? "Open" : "Closed"} · {h.opened_by_name}
                          </p>
                        </div>
                        <p className="text-xs text-muted-foreground text-right">
                          {h.scan_count} line{h.scan_count === 1 ? "" : "s"}
                          <br />
                          {h.compare_run_count} compare{h.compare_run_count === 1 ? "" : "s"}
                        </p>
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {h.closed_at
                          ? `Closed by ${h.closed_by_name ?? "—"} · ${new Date(h.closed_at.replace(" ", "T")).toLocaleString()}`
                          : "Not closed yet"}
                      </p>
                      <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 w-full"
                        onClick={() => setHistoryDetail(h)}
                      >
                        View session
                      </Button>
                    </article>
                  ))}
                </div>
                <div className="hidden sm:block overflow-x-auto rounded-xl border border-border">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                        <th className="px-3 py-2">Date</th>
                        <th className="px-3 py-2">Status</th>
                        <th className="px-3 py-2">Opened by</th>
                        <th className="px-3 py-2">Closed</th>
                        <th className="px-3 py-2 text-right">Lines</th>
                        <th className="px-3 py-2 text-right">Comparisons</th>
                        <th className="px-3 py-2" />
                      </tr>
                    </thead>
                    <tbody>
                      {history.map(h => (
                        <tr key={h.id} className={appHoverRow}>
                          <td className="px-3 py-2 whitespace-nowrap">{h.count_date}</td>
                          <td className="px-3 py-2">{h.status === "open" ? "Open" : "Closed"}</td>
                          <td className="px-3 py-2">{h.opened_by_name}</td>
                          <td className="px-3 py-2 text-xs text-muted-foreground">
                            {h.closed_at
                              ? `${h.closed_by_name ?? "—"} · ${new Date(h.closed_at.replace(" ", "T")).toLocaleString()}`
                              : "—"}
                          </td>
                          <td className="px-3 py-2 text-right">{h.scan_count}</td>
                          <td className="px-3 py-2 text-right">{h.compare_run_count}</td>
                          <td className="px-3 py-2 text-right">
                            <Button
                              type="button"
                              size="sm"
                              variant="outline"
                              className="min-h-11"
                              onClick={() => setHistoryDetail(h)}
                            >
                              View
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </>
            )}
          </div>
        ) : (
          <div className="space-y-4">
            {sessionOpen && (
              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">
                  Scan continuously, or close the camera and set quantity for the SKU you just scanned.
                </p>
                <Button
                  type="button"
                  onClick={() => {
                    resetAddForm();
                    setAddOpen(true);
                  }}
                >
                  <Plus size={16} className="mr-1" /> Add to list
                </Button>
              </div>
            )}

            {!sessionOpen && event === null && (
              <p className="text-sm text-muted-foreground">Start an audit to build a count list.</p>
            )}

            {lines.length === 0 && sessionOpen ? (
              <p className="text-sm text-muted-foreground">No products added yet.</p>
            ) : lines.length > 0 ? (
              <div className="overflow-x-auto rounded-xl border border-border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                      <th className="px-3 py-2">Product</th>
                      <th className="px-3 py-2">Code</th>
                      <th className="px-3 py-2">Batch</th>
                      <th className="px-3 py-2 text-right">Qty</th>
                      <th className="px-3 py-2">By</th>
                      <th className="px-3 py-2 w-28" />
                    </tr>
                  </thead>
                  <tbody>
                    {lines.map(r => {
                      const canEditRow = sessionOpen && (canManage || r.scanned_by === userId);
                      const editing = editId === r.id;
                      return (
                        <tr key={r.id} className={appHoverRow}>
                          <td className="px-3 py-2">
                            {r.product_label}
                            {r.size_label ? ` · ${r.size_label}` : ""}
                            {r.variant ? ` · ${r.variant}` : ""}
                            {r.flag !== "ok" ? (
                              <span className="ml-1 text-xs text-destructive">{r.flag.replace(/_/g, " ")}</span>
                            ) : null}
                          </td>
                          <td className="px-3 py-2 font-mono text-xs">{r.code}</td>
                          <td className="px-3 py-2">
                            {editing ? (
                              <input
                                className="w-28 border rounded-lg px-2 py-1 text-sm uppercase"
                                value={editBatch}
                                onChange={e => setEditBatch(e.target.value.toUpperCase())}
                                readOnly={!r.sku_id}
                              />
                            ) : (
                              r.batch_no ?? "—"
                            )}
                          </td>
                          <td className="px-3 py-2 text-right">
                            {editing ? (
                              <input
                                type="number"
                                min={1}
                                className="w-20 border rounded-lg px-2 py-1 text-sm text-right ml-auto"
                                value={editQty}
                                onChange={e => setEditQty(e.target.value)}
                              />
                            ) : (
                              <span className="font-semibold">{r.counted_qty ?? 1}</span>
                            )}
                          </td>
                          <td className="px-3 py-2 text-xs text-muted-foreground">
                            {r.scanned_by_name}
                            <br />
                            {new Date(r.scanned_at.replace(" ", "T")).toLocaleString()}
                          </td>
                          <td className="px-3 py-2">
                            {canEditRow && (
                              <div className="flex justify-end gap-1">
                                {editing ? (
                                  <>
                                    <Button type="button" size="sm" onClick={() => void saveEdit()} disabled={busy}>
                                      Save
                                    </Button>
                                    <Button type="button" size="sm" variant="ghost" onClick={() => setEditId(null)}>
                                      Cancel
                                    </Button>
                                  </>
                                ) : (
                                  <>
                                    <Button type="button" size="sm" variant="outline" onClick={() => startEdit(r)}>
                                      Edit
                                    </Button>
                                    <Button
                                      type="button"
                                      size="sm"
                                      variant="ghost"
                                      onClick={() => void removeScan(r.id)}
                                      aria-label="Remove line"
                                    >
                                      <Trash2 size={15} />
                                    </Button>
                                  </>
                                )}
                              </div>
                            )}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            ) : null}

            {summary && summary.rows.length > 0 && (
              <p className="text-xs text-muted-foreground">
                Session total: {summary.total_units} unit{summary.total_units === 1 ? "" : "s"}
                {summary.flagged_units > 0 ? ` · ${summary.flagged_units} flagged` : ""}
              </p>
            )}
          </div>
        )}
      </AppSectionEnter>

      <AppModal open={addOpen} onClose={closeAddModal} panelClassName="max-w-lg w-full">
        <AppModalHeader title="Add to list" onClose={closeAddModal} />
        <AppModalBody className="space-y-4">
          <p className="text-xs text-muted-foreground">
            Open the camera and keep scanning. Each SKU is +1; unique stickers count once. Close the camera when you
            want to type how many for the SKU you just scanned.
          </p>
          <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide block">
            Batch number (required for SKU)
            <input
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm uppercase"
              value={stickyBatch}
              onChange={e => setStickyBatch(e.target.value.toUpperCase())}
              placeholder="e.g. NF082826"
              autoComplete="off"
            />
          </label>
          <div className="flex gap-2">
            <input
              ref={inputRef}
              {...inputProps}
              className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide"
              placeholder="Scan continuously…"
              value={code}
              onChange={e => setCode(e.target.value)}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  void onAddScan();
                }
              }}
            />
            <Button type="button" variant="outline" className="shrink-0" onClick={() => setCameraOpen(true)}>
              <Camera size={16} className="mr-1" /> Camera
            </Button>
          </div>
          <div
            className={`h-16 rounded-xl border flex items-center justify-center px-3 text-sm font-medium text-center transition-colors ${
              scanFlash === "ok"
                ? "bg-green-100 border-green-300 text-green-800"
                : scanFlash === "dup"
                  ? "bg-amber-100 border-amber-300 text-amber-900"
                  : scanFlash === "err"
                    ? "bg-red-100 border-red-300 text-red-800"
                    : "bg-secondary/30 border-border text-muted-foreground"
            }`}
          >
            {scanFlash === "ok"
              ? (scanFlashMsg ?? "Added")
              : scanFlash === "dup"
                ? (scanFlashMsg ?? "Already counted")
                : scanFlash === "err"
                  ? (scanFlashMsg ?? "Not accepted")
                  : "Ready — keep scanning"}
          </div>
          {lastItem ? (
            <div className="rounded-lg border border-border bg-secondary/20 p-3 space-y-2">
              <p className="text-xs text-muted-foreground uppercase tracking-wide">Just scanned</p>
              <p className="text-sm font-semibold">{lastItem.label}</p>
              <p className="font-mono text-xs text-muted-foreground">
                {lastItem.code} · {lastItem.batch} · now {lastItem.countedQty}
              </p>
              {lastItem.isSku ? (
                <div className="flex gap-2 items-end">
                  <div className="w-28">
                    <label className="text-xs text-muted-foreground">How many</label>
                    <input
                      ref={qtyInputRef}
                      className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                      type="number"
                      min={1}
                      value={lastQty}
                      onChange={e => setLastQty(e.target.value)}
                      onKeyDown={e => {
                        if (e.key === "Enter") {
                          e.preventDefault();
                          void applyLastQty();
                        }
                      }}
                    />
                  </div>
                  <Button type="button" onClick={() => void applyLastQty()} disabled={qtyBusy}>
                    {qtyBusy ? <Loader2 className="animate-spin" size={16} /> : "Set quantity"}
                  </Button>
                </div>
              ) : (
                <p className="text-xs text-muted-foreground">Unique sticker — already counted as 1.</p>
              )}
            </div>
          ) : null}
          <div className="flex justify-end">
            <Button type="button" variant="outline" onClick={closeAddModal}>
              Done
            </Button>
          </div>
        </AppModalBody>
      </AppModal>

      {cameraOpen && addOpen ? (
        <BarcodeCameraScannerModal
          titleId={addCameraTitleId || cameraTitleId}
          title="Continuous scan"
          continuous
          scanPrompt="Keep pointing at barcodes. Close camera to type quantity for the last SKU."
          onClose={closeCamera}
          onDecoded={decoded => onAddScan(decoded)}
        />
      ) : null}

      <AppModal open={compareOpen} onClose={() => setCompareOpen(false)} panelClassName="max-w-3xl w-full">
        <AppModalHeader title="Audit vs system inventory" onClose={() => setCompareOpen(false)} />
        <AppModalBody className="space-y-4">
          {compare ? (
            <>
              <div className="grid gap-3 sm:grid-cols-4">
                <StatCard label="Scope" value={compare.run.scope || "—"} />
                <StatCard label="Audited" value={String(compare.run.counted_total)} />
                <StatCard label="System" value={String(compare.run.system_total)} />
                <StatCard
                  label="Mismatched"
                  value={String(compare.run.mismatch_count)}
                  tone={compare.run.mismatch_count > 0 ? "warn" : "plain"}
                />
              </div>
              <div className="overflow-x-auto rounded-xl border border-border max-h-[50vh]">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                      <th className="px-3 py-2">Product</th>
                      <th className="px-3 py-2">Batch</th>
                      <th className="px-3 py-2">Size</th>
                      <th className="px-3 py-2 text-right">Audited</th>
                      <th className="px-3 py-2 text-right">System</th>
                      <th className="px-3 py-2 text-right">Variance</th>
                    </tr>
                  </thead>
                  <tbody>
                    {compare.lines.map(l => (
                      <tr key={l.id} className={appHoverRow}>
                        <td className="px-3 py-2">
                          {l.product_label}
                          {l.variant ? ` · ${l.variant}` : ""}
                        </td>
                        <td className="px-3 py-2">{l.batch_no ?? "—"}</td>
                        <td className="px-3 py-2">{l.size_label ?? "—"}</td>
                        <td className="px-3 py-2 text-right">{l.counted_qty}</td>
                        <td className="px-3 py-2 text-right">{formatQty(l.system_qty)}</td>
                        <td className={`px-3 py-2 text-right font-semibold ${varianceTone(l.variance)}`}>
                          {l.variance > 0 ? `+${formatQty(l.variance)}` : formatQty(l.variance)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="flex flex-wrap justify-end gap-2">
                <Button type="button" variant="outline" onClick={() => setCompareOpen(false)} disabled={busy}>
                  Go back
                </Button>
                <Button type="button" onClick={() => void finalize()} disabled={busy}>
                  {busy ? <Loader2 className="animate-spin" size={16} /> : "Confirm and save"}
                </Button>
              </div>
            </>
          ) : (
            <p className="text-sm text-muted-foreground flex items-center gap-2">
              <Loader2 className="animate-spin" size={16} /> Building comparison…
            </p>
          )}
        </AppModalBody>
      </AppModal>

      {historyDetail ? (
        <InventoryCheckHistoryDetailModal event={historyDetail} onClose={() => setHistoryDetail(null)} />
      ) : null}
    </PageLayout>
  );
}

function StatCard({
  label,
  value,
  tone = "plain",
}: {
  label: string;
  value: string;
  tone?: "plain" | "warn";
}) {
  return (
    <div className="rounded-xl border border-border bg-card px-4 py-3">
      <p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p>
      <p className={`text-lg font-semibold mt-1 ${tone === "warn" ? "text-destructive" : ""}`}>{value}</p>
    </div>
  );
}
