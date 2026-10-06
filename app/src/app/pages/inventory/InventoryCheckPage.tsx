import { useCallback, useEffect, useId, useRef, useState } from "react";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import { Camera, ClipboardCheck, Loader2, Plus, Scale, Trash2, XCircle } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { InventoryCheckBatchDocs } from "@/app/components/inventory/InventoryCheckBatchDocs";
import { InventoryCheckAppliedRunDetailModal } from "@/app/components/inventory/InventoryCheckAppliedRunDetailModal";
import { InventoryCheckHistoryDetailModal } from "@/app/components/inventory/InventoryCheckHistoryDetailModal";
import { AdminOverridePasswordModal } from "@/app/components/inventory/AdminOverridePasswordModal";
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
  type InventoryCheckAppliedRun,
  type InventoryCheckCompareResult,
  type InventoryCheckContext,
  type InventoryCheckEvent,
  type InventoryCheckScan,
  type InventoryCheckSummary,
} from "@/app/lib/inventoryCheck";

const SCAN_COOLDOWN_MS = 500;
const HISTORY_PAGE_SIZE = 10;
const APPLIED_PAGE_SIZE = 10;

function varianceTone(variance: number): string {
  if (Math.abs(variance) < 0.001) return "text-muted-foreground";
  return variance > 0 ? "text-emerald-600" : "text-destructive";
}

function formatQty(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

function ListPager({
  page,
  perPage,
  total,
  loading,
  onPrev,
  onNext,
  label,
}: {
  page: number;
  perPage: number;
  total: number;
  loading?: boolean;
  onPrev: () => void;
  onNext: () => void;
  label: string;
}) {
  if (total <= 0) return null;
  const from = (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);
  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-1">
      <p className="text-xs text-muted-foreground">
        Showing {from}–{to} of {total} {label}
        {loading ? "…" : ""}
      </p>
      {total > perPage ? (
        <div className="flex gap-2">
          <Button
            type="button"
            size="sm"
            variant="outline"
            className="min-h-11"
            disabled={page <= 1 || loading}
            onClick={onPrev}
          >
            Previous
          </Button>
          <Button
            type="button"
            size="sm"
            variant="outline"
            className="min-h-11"
            disabled={page * perPage >= total || loading}
            onClick={onNext}
          >
            Next
          </Button>
        </div>
      ) : null}
    </div>
  );
}

export function InventoryCheckPage() {
  const cameraTitleId = useId();
  const addCameraTitleId = useId();
  const reduceMotion = useReducedMotion();
  const [ctx, setCtx] = useState<InventoryCheckContext | null>(null);
  const [summary, setSummary] = useState<InventoryCheckSummary | null>(null);
  const [lines, setLines] = useState<InventoryCheckScan[]>([]);
  const [history, setHistory] = useState<InventoryCheckEvent[]>([]);
  const [historyPage, setHistoryPage] = useState(1);
  const [historyTotal, setHistoryTotal] = useState(0);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [ok, setOk] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [showHistory, setShowHistory] = useState(false);

  const [addOpen, setAddOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [code, setCode] = useState("");
  /** Last successful scan — close camera and set qty here for SKUs. */
  const [lastItem, setLastItem] = useState<{
    scanId: number;
    code: string;
    label: string;
    isSku: boolean;
    countedQty: number;
  } | null>(null);
  const [lastQty, setLastQty] = useState("1");
  const [qtyBusy, setQtyBusy] = useState(false);
  const [scanFlash, setScanFlash] = useState<"ok" | "err" | "dup" | null>(null);
  const [scanFlashMsg, setScanFlashMsg] = useState<string | null>(null);
  const [scanPopup, setScanPopup] = useState<string | null>(null);
  const [pending, setPending] = useState<{ code: string; label: string } | null>(null);
  const [pendingQty, setPendingQty] = useState("1");
  const [docBusy, setDocBusy] = useState(false);
  const qtyInputRef = useRef<HTMLInputElement>(null);
  const lastItemRef = useRef(lastItem);
  lastItemRef.current = lastItem;
  const pendingRef = useRef(pending);
  pendingRef.current = pending;
  const lastAcceptedScanAtRef = useRef(0);

  const [compareOpen, setCompareOpen] = useState(false);
  const [compare, setCompare] = useState<InventoryCheckCompareResult | null>(null);
  const [compareMode, setCompareMode] = useState<"session" | "merged">("session");
  const [pwOpen, setPwOpen] = useState(false);
  const [pwBusy, setPwBusy] = useState(false);
  const [pwError, setPwError] = useState<string | null>(null);
  const [historyDetail, setHistoryDetail] = useState<InventoryCheckEvent | null>(null);
  const [appliedRuns, setAppliedRuns] = useState<InventoryCheckAppliedRun[]>([]);
  const [appliedRunsTotal, setAppliedRunsTotal] = useState(0);
  const [appliedRunsPage, setAppliedRunsPage] = useState(1);
  const [appliedRunsLoading, setAppliedRunsLoading] = useState(false);
  const [appliedRunDetail, setAppliedRunDetail] = useState<InventoryCheckAppliedRun | null>(null);

  const [editId, setEditId] = useState<number | null>(null);
  const [editQty, setEditQty] = useState("");
  const [selectedHistoryIds, setSelectedHistoryIds] = useState<number[]>([]);
  const [mergeBusy, setMergeBusy] = useState(false);

  const { inputRef, refocus, inputProps } = useScanWedgeField(cameraOpen, addOpen);

  const event = ctx?.event ?? null;
  const canViewAll = ctx?.can_view_all ?? false;
  const canManage = ctx?.can_manage ?? false;
  const userId = ctx?.user_id ?? 0;
  const sessionOpen = event !== null && event.status === "open";
  const isSessionOwner = event !== null && event.opened_by === userId;
  const canControlSession = sessionOpen && (isSessionOwner || canManage);

  const flash = (kind: "ok" | "err" | "dup", message: string, opts?: { popup?: boolean }) => {
    setScanFlash(kind);
    setScanFlashMsg(message);
    if (kind === "ok" && opts?.popup !== false) {
      setScanPopup(message);
      window.setTimeout(() => setScanPopup(null), 1400);
    }
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
    if (!showHistory) return;
    void (async () => {
      setHistoryLoading(true);
      try {
        const res = await inventoryCheckApi.eventList(historyPage, HISTORY_PAGE_SIZE);
        setHistory(res.rows);
        setHistoryTotal(res.total);
        setHistoryPage(res.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load audit history.");
      } finally {
        setHistoryLoading(false);
      }
    })();
  }, [showHistory, historyPage]);

  useEffect(() => {
    if (!showHistory || !canViewAll) {
      setAppliedRuns([]);
      setAppliedRunsTotal(0);
      return;
    }
    void (async () => {
      setAppliedRunsLoading(true);
      try {
        const applied = await inventoryCheckApi.appliedRunsList(appliedRunsPage, APPLIED_PAGE_SIZE);
        setAppliedRuns(applied.rows);
        setAppliedRunsTotal(applied.total);
        setAppliedRunsPage(applied.page);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load confirmed updates.");
      } finally {
        setAppliedRunsLoading(false);
      }
    })();
  }, [showHistory, canViewAll, appliedRunsPage]);

  const openCount = async () => {
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.openEvent();
      setOk("Your private audit session started — add products to the list.");
      await loadContext({ silent: true });
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start the audit.");
    } finally {
      setBusy(false);
    }
  };

  const compareSelectedHistory = async () => {
    // Selection may span pages — only closed audits can be checked in the UI.
    const closedIds = [...selectedHistoryIds];
    if (closedIds.length < 1) {
      setError("Select at least one closed audit to compare.");
      return;
    }
    setMergeBusy(true);
    setError(null);
    try {
      const preview = await inventoryCheckApi.comparePreviewMerged(closedIds);
      setCompareMode("merged");
      setCompare(preview);
      setCompareOpen(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not compare selected audits.");
    } finally {
      setMergeBusy(false);
    }
  };

  const toggleHistorySelect = (id: number, closed: boolean) => {
    if (!closed) return;
    setSelectedHistoryIds(prev => (prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]));
  };

  const resetAddForm = () => {
    setCode("");
    setLastItem(null);
    setLastQty("1");
    setQtyBusy(false);
    setPending(null);
    setPendingQty("1");
    setDocBusy(false);
    setScanFlash(null);
    setScanFlashMsg(null);
    setScanPopup(null);
    lastAcceptedScanAtRef.current = 0;
  };

  const closeAddModal = () => {
    setAddOpen(false);
    setCameraOpen(false);
    resetAddForm();
  };

  const closeCamera = () => {
    setCameraOpen(false);
    window.setTimeout(() => {
      if (pendingRef.current) {
        qtyInputRef.current?.focus();
        qtyInputRef.current?.select();
      } else if (lastItemRef.current?.isSku) {
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

  /** Commit scan (SKU with qty, or unique sticker as +1). */
  const commitScan = async (
    value: string,
    opts: { qty: number; label: string; isSku: boolean },
  ): Promise<boolean> => {
    if (!event) return false;
    try {
      const res = await inventoryCheckApi.scan(event.id, value, {
        qty: opts.qty,
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
      lastAcceptedScanAtRef.current = Date.now();
      const total = res.scan?.counted_qty ?? opts.qty;
      const msg = opts.isSku
        ? `${opts.label} · +${opts.qty} (total ${total})`
        : `${opts.label} · counted`;
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

  /** SKU only: confirm quantity after scan. */
  const confirmPending = async () => {
    if (!pending || !event) return;
    const qty = Math.max(1, parseInt(pendingQty, 10) || 1);
    setDocBusy(true);
    try {
      const okAdd = await commitScan(pending.code, {
        qty,
        label: pending.label,
        isSku: true,
      });
      if (okAdd) {
        setPending(null);
        setPendingQty("1");
        setCameraOpen(false);
        window.setTimeout(() => {
          qtyInputRef.current?.focus();
          qtyInputRef.current?.select();
        }, 40);
      }
    } finally {
      setDocBusy(false);
    }
  };

  /** Unique stickers auto-commit; SKU barcodes pause for quantity. */
  const onAddScan = async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
    const value = (raw ?? code).trim();
    if (!value || !event) return;
    setCode("");

    if (pending) {
      return { kind: "error", message: "Enter quantity for the current product first." };
    }

    const now = Date.now();
    if (now - lastAcceptedScanAtRef.current < SCAN_COOLDOWN_MS) {
      return { kind: "error", message: "Wait a moment before the next scan." };
    }

    try {
      const looked = await skuBarcodesApi.lookup(value);
      const lookup = looked.lookup;

      if (lookup.lookup_kind === "sku") {
        const label = lookup.label ?? lookup.product_label ?? value;
        setPending({ code: value, label });
        setPendingQty("1");
        setCameraOpen(false);
        playScanBeep("ok");
        flash("ok", `${label} — enter quantity`, { popup: false });
        window.setTimeout(() => {
          qtyInputRef.current?.focus();
          qtyInputRef.current?.select();
        }, 40);
        return { kind: "success", message: `${label} · enter qty` };
      }

      if (lookup.lookup_kind === "unique_honey" || lookup.lookup_kind === "unique_dept") {
        const label =
          lookup.label ??
          ([lookup.product_label, lookup.size_label, lookup.variant].filter(Boolean).join(" · ") ||
            value);
        const okAdd = await commitScan(value, { qty: 1, label, isSku: false });
        if (!okAdd) {
          return { kind: "error", message: "Not accepted" };
        }
        return { kind: "success", message: `${label} · counted` };
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
  };

  const saveEdit = async () => {
    if (!event || editId == null) return;
    const n = Math.max(1, parseInt(editQty, 10) || 1);
    setBusy(true);
    setError(null);
    try {
      const res = await inventoryCheckApi.scanUpdate(event.id, editId, {
        qty: n,
      });
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

  /** Close the private session into history — does not change stock. */
  const openComplete = async () => {
    if (!event) return;
    if (
      !window.confirm(
        "Complete this audit? It will be closed and saved to history. Inventory will not change — an executive can compare selected sessions later and update stock.",
      )
    ) {
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await inventoryCheckApi.closeEvent(event.id);
      setOk("Audit completed and saved to history. Stock was not changed.");
      closeAddModal();
      await loadContext({ silent: true });
      setHistoryPage(1);
      setAppliedRunsPage(1);
      setSelectedHistoryIds([]);
      setShowHistory(true);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not complete the audit.");
    } finally {
      setBusy(false);
    }
  };

  const requestFinalize = () => {
    if (!canViewAll || compareMode !== "merged") return;
    setPwError(null);
    setPwOpen(true);
  };

  const finalizeWithPassword = async (adminPassword: string) => {
    if (!canViewAll) {
      setPwError("Only full-report accounts can update inventory from audits.");
      return;
    }
    setPwBusy(true);
    setPwError(null);
    setBusy(true);
    setError(null);
    try {
      const closedIds = [...selectedHistoryIds];
      if (closedIds.length < 1) throw new Error("Select at least one closed audit.");
      const res = await inventoryCheckApi.finalizeMerged(closedIds, adminPassword);
      setCompare(res.compare);
      const voided = res.compare.run.barcodes_voided_count ?? 0;
      setOk(
        voided > 0
          ? `Inventory updated to audited counts. ${voided} unique barcode(s) voided. Variance saved to history.`
          : "Inventory updated to audited counts. Variance saved to history.",
      );
      setSelectedHistoryIds([]);
      setPwOpen(false);
      setCompareOpen(false);
      await loadContext({ silent: true });
      try {
        setHistoryPage(1);
        setAppliedRunsPage(1);
        const applied = await inventoryCheckApi.appliedRunsList(1, APPLIED_PAGE_SIZE);
        setAppliedRuns(applied.rows);
        setAppliedRunsTotal(applied.total);
        const hist = await inventoryCheckApi.eventList(1, HISTORY_PAGE_SIZE);
        setHistory(hist.rows);
        setHistoryTotal(hist.total);
      } catch {
        /* history refresh is best-effort */
      }
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : e instanceof Error ? e.message : "Could not update inventory.";
      setPwError(msg);
    } finally {
      setPwBusy(false);
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
      subtitle="Private audits of finished products. Completing saves to history without changing stock — executives compare selected sessions to update inventory."
    >
      <AppSectionEnter className="space-y-4">
        <div className="rounded-xl border border-border bg-card px-4 py-3 flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-3">
          <div className="min-w-0">
            {event === null ? (
              <p className="text-sm text-muted-foreground">
                No audit session open for you. Start one to begin counting — only you can see this session.
              </p>
            ) : (
              <>
                <p className="text-sm font-semibold">
                  Your audit · {event.count_date}
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
                  Opened {event.opened_at ? new Date(event.opened_at.replace(" ", "T")).toLocaleString() : ""}
                  {event.closed_by_name ? ` · Closed by ${event.closed_by_name}` : ""} · {event.scan_count}{" "}
                  line{event.scan_count === 1 ? "" : "s"}
                </p>
              </>
            )}
          </div>
          <div className="flex flex-wrap gap-2 w-full sm:w-auto">
            <Button
              type="button"
              size="sm"
              variant="outline"
              className="min-h-11 flex-1 sm:flex-none"
              onClick={() =>
                setShowHistory(h => {
                  if (!h) {
                    setHistoryPage(1);
                    setAppliedRunsPage(1);
                    setSelectedHistoryIds([]);
                  }
                  return !h;
                })
              }
            >
              {showHistory ? "My session" : "History"}
            </Button>
            {(event === null || event.status === "closed") && (
              <Button type="button" className="min-h-11 flex-1 sm:flex-none" onClick={() => void openCount()} disabled={busy}>
                <ClipboardCheck size={16} className="mr-1" /> Start audit
              </Button>
            )}
            {canControlSession && (
              <>
                <Button
                  type="button"
                  variant="outline"
                  className="min-h-11 flex-1 sm:flex-none text-destructive border-destructive/40"
                  onClick={() => void cancelSession()}
                  disabled={busy}
                >
                  <XCircle size={16} className="mr-1" /> Cancel
                </Button>
                <Button
                  type="button"
                  className="min-h-11 flex-1 sm:flex-none"
                  onClick={() => void openComplete()}
                  disabled={busy || lines.length === 0}
                >
                  <Scale size={16} className="mr-1" /> Complete
                </Button>
              </>
            )}
          </div>
        </div>

        {ok && <AppSuccessNotice message={ok} />}
        {error && <AppErrorBanner message={error} />}

        {showHistory ? (
          <div className="space-y-3">
            <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
              <div>
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  {canViewAll ? "All audit history" : "Your audit history"}
                </p>
                {canViewAll ? (
                  <p className="text-xs text-muted-foreground mt-1">
                    Select closed sessions (any dates), compare against system inventory, then confirm to update
                    stock (administrator password required).
                  </p>
                ) : (
                  <p className="text-xs text-muted-foreground mt-1">
                    Completed audits stay here for review. Updating stock is done by an executive after they
                    compare selected sessions.
                  </p>
                )}
              </div>
              {canViewAll ? (
                <Button
                  type="button"
                  className="min-h-11 w-full sm:w-auto"
                  disabled={mergeBusy || selectedHistoryIds.length < 1}
                  onClick={() => void compareSelectedHistory()}
                >
                  <Scale size={16} className="mr-1" />
                  Compare selected ({selectedHistoryIds.length})
                </Button>
              ) : null}
            </div>
            {history.length === 0 ? (
              <p className="text-sm text-muted-foreground">No audits recorded yet.</p>
            ) : (
              <>
                <div className="sm:hidden space-y-3">
                  {history.map(h => {
                    const closed = h.status === "closed";
                    const selected = selectedHistoryIds.includes(h.id);
                    return (
                      <article
                        key={h.id}
                        className={`rounded-xl border bg-card p-4 space-y-3 ${
                          selected ? "border-[#C8900A] ring-1 ring-[#C8900A]/40" : "border-border"
                        }`}
                      >
                        <div className="flex items-start gap-3">
                          {canViewAll ? (
                            <input
                              type="checkbox"
                              className="mt-1 h-5 w-5 accent-[#C8900A]"
                              checked={selected}
                              disabled={!closed}
                              onChange={() => toggleHistorySelect(h.id, closed)}
                              aria-label={`Select audit ${h.count_date}`}
                            />
                          ) : null}
                          <div className="flex-1 min-w-0 flex items-start justify-between gap-3">
                            <div>
                              <p className="text-sm font-semibold">{h.count_date}</p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {closed ? "Closed" : "Open"} · {h.opened_by_name}
                              </p>
                            </div>
                            <p className="text-xs text-muted-foreground text-right shrink-0">
                              {h.scan_count} line{h.scan_count === 1 ? "" : "s"}
                            </p>
                          </div>
                        </div>
                        <Button
                          type="button"
                          variant="outline"
                          className="min-h-11 w-full"
                          onClick={() => setHistoryDetail(h)}
                        >
                          View session
                        </Button>
                      </article>
                    );
                  })}
                </div>
                <div className="hidden sm:block overflow-x-auto rounded-xl border border-border">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                        {canViewAll ? <th className="px-3 py-2 w-10" /> : null}
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
                      {history.map(h => {
                        const closed = h.status === "closed";
                        const selected = selectedHistoryIds.includes(h.id);
                        return (
                          <tr key={h.id} className={appHoverRow}>
                            {canViewAll ? (
                              <td className="px-3 py-2">
                                <input
                                  type="checkbox"
                                  className="h-4 w-4 accent-[#C8900A]"
                                  checked={selected}
                                  disabled={!closed}
                                  onChange={() => toggleHistorySelect(h.id, closed)}
                                  aria-label={`Select audit ${h.count_date}`}
                                />
                              </td>
                            ) : null}
                            <td className="px-3 py-2 whitespace-nowrap">{h.count_date}</td>
                            <td className="px-3 py-2">{closed ? "Closed" : "Open"}</td>
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
                        );
                      })}
                    </tbody>
                  </table>
                </div>
                <ListPager
                  page={historyPage}
                  perPage={HISTORY_PAGE_SIZE}
                  total={historyTotal}
                  loading={historyLoading}
                  label="audits"
                  onPrev={() => setHistoryPage(p => Math.max(1, p - 1))}
                  onNext={() => setHistoryPage(p => p + 1)}
                />
              </>
            )}
            {canViewAll ? (
              <div className="space-y-3 pt-4 border-t border-border">
                <div>
                  <p className="text-xs uppercase tracking-wide text-muted-foreground">
                    Confirmed inventory updates
                  </p>
                  <p className="text-xs text-muted-foreground mt-1">
                    Stock applied when an executive confirms after Compare selected — frozen mismatch lines.
                  </p>
                </div>
                {appliedRuns.length === 0 ? (
                  <p className="text-sm text-muted-foreground">No confirmed inventory updates yet.</p>
                ) : (
                  <>
                    <div className="sm:hidden space-y-3">
                      {appliedRuns.map(run => (
                        <article
                          key={run.id}
                          className="rounded-xl border border-border bg-card p-4 space-y-3"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <p className="text-sm font-semibold">
                                {run.is_merged ? "Merged confirm" : "Session confirm"}
                              </p>
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {run.stock_applied_at
                                  ? new Date(run.stock_applied_at.replace(" ", "T")).toLocaleString()
                                  : "—"}
                                {run.stock_applied_by_name ? ` · ${run.stock_applied_by_name}` : ""}
                              </p>
                              <p className="text-xs text-muted-foreground mt-1">
                                {run.sessions.map(s => s.count_date).join(" · ") || run.primary_count_date}
                              </p>
                            </div>
                            <p className="text-xs text-muted-foreground text-right shrink-0">
                              {run.mismatch_count} mismatch
                              {run.mismatch_count === 1 ? "" : "es"}
                            </p>
                          </div>
                          <Button
                            type="button"
                            variant="outline"
                            className="min-h-11 w-full"
                            onClick={() => setAppliedRunDetail(run)}
                          >
                            View mismatches
                          </Button>
                        </article>
                      ))}
                    </div>
                    <div className="hidden sm:block overflow-x-auto rounded-xl border border-border">
                      <table className="w-full text-sm">
                        <thead>
                          <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                            <th className="px-3 py-2">Applied</th>
                            <th className="px-3 py-2">Type</th>
                            <th className="px-3 py-2">Sessions</th>
                            <th className="px-3 py-2">By</th>
                            <th className="px-3 py-2 text-right">Audited</th>
                            <th className="px-3 py-2 text-right">Mismatches</th>
                            <th className="px-3 py-2" />
                          </tr>
                        </thead>
                        <tbody>
                          {appliedRuns.map(run => (
                            <tr key={run.id} className={appHoverRow}>
                              <td className="px-3 py-2 text-xs whitespace-nowrap">
                                {run.stock_applied_at
                                  ? new Date(run.stock_applied_at.replace(" ", "T")).toLocaleString()
                                  : "—"}
                              </td>
                              <td className="px-3 py-2">{run.is_merged ? "Merged" : "Single"}</td>
                              <td className="px-3 py-2 text-xs">
                                {run.sessions.map(s => s.count_date).join(", ") || run.primary_count_date || "—"}
                              </td>
                              <td className="px-3 py-2">{run.stock_applied_by_name || "—"}</td>
                              <td className="px-3 py-2 text-right tabular-nums">{run.counted_total}</td>
                              <td
                                className={`px-3 py-2 text-right tabular-nums font-semibold ${
                                  run.mismatch_count > 0 ? "text-destructive" : ""
                                }`}
                              >
                                {run.mismatch_count}
                              </td>
                              <td className="px-3 py-2 text-right">
                                <Button
                                  type="button"
                                  size="sm"
                                  variant="outline"
                                  className="min-h-11"
                                  onClick={() => setAppliedRunDetail(run)}
                                >
                                  View
                                </Button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                    <ListPager
                      page={appliedRunsPage}
                      perPage={APPLIED_PAGE_SIZE}
                      total={appliedRunsTotal}
                      loading={appliedRunsLoading}
                      label="confirmed updates"
                      onPrev={() => setAppliedRunsPage(p => Math.max(1, p - 1))}
                      onNext={() => setAppliedRunsPage(p => p + 1)}
                    />
                  </>
                )}
              </div>
            ) : null}
          </div>
        ) : (
          <div className="space-y-4">
            {sessionOpen && (
              <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">
                  Scan a product barcode, then enter how many you counted. Unique stickers count as 1
                  automatically.
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
        <AppModalBody className="space-y-4 relative">
          <p className="text-xs text-muted-foreground">
            Scan a product barcode — variant and size come from the code. For SKU stickers, enter how many
            you counted; unique unit stickers count as 1 right away.
          </p>
          <AnimatePresence>
            {scanPopup ? (
              <motion.div
                key={scanPopup}
                initial={reduceMotion ? false : { opacity: 0, y: -8, scale: 0.98 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={reduceMotion ? undefined : { opacity: 0, y: -6 }}
                transition={{ duration: 0.2 }}
                className="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 shadow-sm"
                role="status"
                data-testid="inventory-check-scan-success"
              >
                {scanPopup}
              </motion.div>
            ) : null}
          </AnimatePresence>
          {pending ? (
            <div
              className="rounded-xl border border-border bg-secondary/20 p-3 space-y-3"
              data-testid="inventory-check-pending-qty-panel"
            >
              <div>
                <p className="text-xs text-muted-foreground uppercase tracking-wide">Just scanned</p>
                <p className="text-sm font-semibold mt-0.5">{pending.label}</p>
                <p className="font-mono text-xs text-muted-foreground">{pending.code}</p>
              </div>
              <div className="w-28">
                <label className="text-xs text-muted-foreground" htmlFor="inv-check-pending-qty">
                  How many
                </label>
                <input
                  ref={qtyInputRef}
                  id="inv-check-pending-qty"
                  className="mt-1 w-full border rounded-lg px-2 py-2 text-sm"
                  type="number"
                  min={1}
                  value={pendingQty}
                  onChange={e => setPendingQty(e.target.value)}
                  data-testid="inventory-check-pending-qty"
                  onKeyDown={e => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void confirmPending();
                    }
                  }}
                />
              </div>
              <div className="flex flex-wrap gap-2 justify-end">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => {
                    setPending(null);
                    refocus();
                  }}
                  disabled={docBusy}
                >
                  Discard scan
                </Button>
                <Button type="button" onClick={() => void confirmPending()} disabled={docBusy}>
                  {docBusy ? <Loader2 className="animate-spin" size={16} /> : "Confirm quantity"}
                </Button>
              </div>
            </div>
          ) : (
            <>
              <div className="flex gap-2">
                <input
                  ref={inputRef}
                  {...inputProps}
                  className="flex-1 border rounded-lg px-3 py-3 text-base tracking-wide"
                  placeholder="Scan barcode…"
                  value={code}
                  onChange={e => setCode(e.target.value)}
                  onKeyDown={e => {
                    if (e.key === "Enter") {
                      e.preventDefault();
                      void onAddScan();
                    }
                  }}
                  data-testid="inventory-check-scan-input"
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
                      : "Ready — scan a barcode, then enter quantity"}
              </div>
              {lastItem ? (
                <div className="rounded-lg border border-border bg-secondary/20 p-3 space-y-2">
                  <p className="text-xs text-muted-foreground uppercase tracking-wide">Last confirmed</p>
                  <p className="text-sm font-semibold">{lastItem.label}</p>
                  <p className="font-mono text-xs text-muted-foreground">
                    {lastItem.code} · now {lastItem.countedQty}
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
            </>
          )}
          <div className="flex justify-end">
            <Button type="button" variant="outline" onClick={closeAddModal} disabled={!!pending}>
              Done
            </Button>
          </div>
        </AppModalBody>
      </AppModal>

      {cameraOpen && addOpen && !pending ? (
        <BarcodeCameraScannerModal
          titleId={addCameraTitleId || cameraTitleId}
          title="Scan barcode"
          continuous
          sameCodeCooldownMs={SCAN_COOLDOWN_MS}
          scanPrompt="Point at a barcode. For SKU codes you’ll enter quantity next."
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
              <div className="overflow-x-auto rounded-xl border border-border max-h-[40vh]">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                      <th className="px-3 py-2">Product</th>
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
              <InventoryCheckBatchDocs batches={compare.batches ?? []} />
              {canViewAll && compareMode === "merged" ? (
                <>
                  <p className="text-xs text-muted-foreground">
                    Confirm sets finished stock to the audited totals for these lines (+ adds, − deducts). Unique
                    stickers in this audit stay; other in-stock uniques for those same products are voided. Requires
                    the administrator password from System Settings.
                  </p>
                  <div className="flex flex-wrap justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => setCompareOpen(false)} disabled={busy}>
                      Go back
                    </Button>
                    <Button type="button" onClick={() => requestFinalize()} disabled={busy}>
                      {busy ? <Loader2 className="animate-spin" size={16} /> : "Confirm and update inventory"}
                    </Button>
                  </div>
                </>
              ) : (
                <div className="flex flex-wrap justify-end gap-2">
                  <Button type="button" variant="outline" onClick={() => setCompareOpen(false)} disabled={busy}>
                    Close
                  </Button>
                </div>
              )}
            </>
          ) : (
            <p className="text-sm text-muted-foreground flex items-center gap-2">
              <Loader2 className="animate-spin" size={16} /> Building comparison…
            </p>
          )}
        </AppModalBody>
      </AppModal>

      <AdminOverridePasswordModal
        open={pwOpen}
        busy={pwBusy}
        error={pwError}
        description="Updating inventory from this audit needs the administrator password from System Settings."
        onClose={() => {
          if (pwBusy) return;
          setPwOpen(false);
          setPwError(null);
        }}
        onConfirm={finalizeWithPassword}
      />

      {historyDetail ? (
        <InventoryCheckHistoryDetailModal event={historyDetail} onClose={() => setHistoryDetail(null)} />
      ) : null}

      {appliedRunDetail ? (
        <InventoryCheckAppliedRunDetailModal
          run={appliedRunDetail}
          onClose={() => setAppliedRunDetail(null)}
        />
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
