import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { Camera, Loader2, Pencil, Plus, RefreshCcw, ScanLine, Trash2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppPressable,
  AppSectionEnter,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import { variantLabel } from "@/app/lib/posHoney";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";
import {
  rtsStockInSessionCancel,
  rtsStockInSessionConfirm,
  rtsStockInSessionRemove,
  rtsStockInSessionScan,
  rtsStockInSessionStart,
  rtsStockInSessionUpdate,
  type RtsStockInSession,
  type RtsStockInSessionItem,
} from "@/app/lib/rtsApi";
import { cn } from "@/app/components/ui/utils";

const OTHER_PRODUCT_PRESETS = [
  "Paragis Tea",
  "Black Rice Coffee",
  "Jar Cacao",
  "Honey Comb",
] as const;

interface RtsInvRow {
  id?: number;
  first_row_id?: number;
  line_count?: number;
  category: string;
  product_label: string;
  variant: string | null;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
  current_qty: string;
  initial_qty: string;
}

interface UseLogRow {
  id: number;
  product_kind: string;
  variant: string | null;
  size: string | null;
  product_label: string | null;
  qty: string | number;
  used_where: string;
  created_at: string;
}

function whereLabel(w: string): string {
  if (w === "pos") return "POS";
  if (w === "packaging") return "Packaging";
  return w;
}

function stockInRequestKey(): string {
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }
  return `rts-${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export function RTSPage() {
  const { can } = useAuth();
  const canRecord = can("rts.record");
  const [canMutate, setCanMutate] = useState(false);
  const [canExecutiveEdit, setCanExecutiveEdit] = useState(false);
  const [canUseRtsQty, setCanUseRtsQty] = useState(true);
  const [barcodeBackedSellable, setBarcodeBackedSellable] = useState(false);
  const canViewOps = can("rts.admin");
  const readonly = can("rts.view") && !can("rts.record");

  const [honey, setHoney] = useState<RtsInvRow[]>([]);
  const [other, setOther] = useState<RtsInvRow[]>([]);
  const [useLog, setUseLog] = useState<UseLogRow[]>([]);
  const [sync, setSync] = useState<{ last_synced_at: string | null; last_error: string | null } | null>(null);
  const [diagnostic, setDiagnostic] = useState<{
    case_count: number;
    line_item_count: number;
    syncable_line_item_count: number;
    inventory_row_count: number;
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [useRow, setUseRow] = useState<RtsInvRow | null>(null);
  const [editHoney, setEditHoney] = useState<RtsInvRow | null>(null);
  const [editOther, setEditOther] = useState<RtsInvRow | null>(null);
  const [addHoneyOpen, setAddHoneyOpen] = useState(false);
  const [addOtherOpen, setAddOtherOpen] = useState(false);
  const [stockInCode, setStockInCode] = useState("");
  const [stockInBusy, setStockInBusy] = useState(false);
  const [stockSession, setStockSession] = useState<RtsStockInSession | null>(null);
  const [editSessionItem, setEditSessionItem] = useState<RtsStockInSessionItem | null>(null);
  const [editSessionQty, setEditSessionQty] = useState("1");
  const [confirmSessionOpen, setConfirmSessionOpen] = useState(false);
  const [cancelSessionOpen, setCancelSessionOpen] = useState(false);
  const [cameraOpen, setCameraOpen] = useState(false);
  const cameraTitleId = useId();
  const { inputRef: stockInRef, refocus: stockInRefocus, inputProps: stockInInputProps } =
    useScanWedgeField(cameraOpen);

  const canSync = can("rts.sync.run");
  const showUseRts = canMutate && canUseRtsQty;

  const loadUseLog = useCallback(async () => {
    try {
      const res = await apiGet<{ rows: UseLogRow[] }>("rts.php", "inventory_use_log", { limit: "100" });
      setUseLog(res.rows ?? []);
    } catch {
      setUseLog([]);
    }
  }, []);

  const loadInventory = useCallback(async (showLoader = true) => {
    if (showLoader) setLoading(true);
    setError(null);
    try {
      const inv = await apiGet<{
        honey: RtsInvRow[];
        other: RtsInvRow[];
        sync: typeof sync;
        diagnostic: typeof diagnostic;
        readonly: boolean;
        can_mutate: boolean;
        can_executive_edit: boolean;
        can_use_rts_qty?: boolean;
        barcode_backed_sellable?: boolean;
      }>("rts.php", "inventory_list");
      setHoney(inv.honey);
      setOther(inv.other);
      setSync(inv.sync);
      setDiagnostic(inv.diagnostic ?? null);
      setCanMutate(!!inv.can_mutate);
      setCanExecutiveEdit(!!inv.can_executive_edit);
      setCanUseRtsQty(inv.can_use_rts_qty !== false);
      setBarcodeBackedSellable(!!inv.barcode_backed_sellable);
      await loadUseLog();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS inventory.");
    } finally {
      if (showLoader) setLoading(false);
    }
  }, [loadUseLog]);

  const startStockSession = async () => {
    setStockInBusy(true);
    setError(null);
    try {
      const res = await rtsStockInSessionStart();
      setStockSession(res.session);
      setToast(res.session.line_count > 0 ? "Draft session resumed." : "RTS stock-in session started.");
      window.setTimeout(() => setToast(null), 3000);
      stockInRefocus();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not start the RTS stock-in session.");
    } finally {
      setStockInBusy(false);
    }
  };

  const runStockInScan = useCallback(
    async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
      const code = (raw ?? stockInCode).trim();
      if (!code) return { kind: "error", message: "Scan a barcode." };
      if (!stockSession || stockSession.status !== "draft") {
        return { kind: "error", message: "Start or resume a stock-in session first." };
      }
      setStockInCode("");
      setStockInBusy(true);
      setError(null);
      try {
        const res = await rtsStockInSessionScan(
          stockSession.id,
          code,
          1,
          stockInRequestKey(),
        );
        setStockSession(res.session);
        if (res.result === "duplicate_session") {
          playScanBeep("bad");
          return { kind: "error", message: "Unique sticker is already in this session." };
        }
        const label = res.lookup?.product_label ?? code;
        const qty = res.lookup?.qty ?? 1;
        playScanBeep("ok");
        return { kind: "success", message: `${label} · draft qty ${qty}` };
      } catch (e) {
        playScanBeep("bad");
        const msg = e instanceof ApiError ? e.message : "RTS stock-in scan failed.";
        setError(msg);
        return { kind: "error", message: msg };
      } finally {
        setStockInBusy(false);
        stockInRefocus();
      }
    },
    [stockInCode, stockSession, stockInRefocus],
  );

  const startSessionEdit = (item: RtsStockInSessionItem) => {
    setEditSessionItem(item);
    setEditSessionQty(String(item.qty));
  };

  const saveSessionQty = async () => {
    if (!stockSession || !editSessionItem) return;
    const qty = parseInt(editSessionQty, 10);
    if (!Number.isFinite(qty) || qty < 1 || qty > 5000) {
      setError("Quantity must be between 1 and 5000.");
      return;
    }
    setStockInBusy(true);
    setError(null);
    try {
      const res = await rtsStockInSessionUpdate(stockSession.id, editSessionItem.id, qty);
      setStockSession(res.session);
      setEditSessionItem(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not update the draft quantity.");
    } finally {
      setStockInBusy(false);
    }
  };

  const removeSessionItem = async (itemId: number) => {
    if (!stockSession) return;
    setStockInBusy(true);
    setError(null);
    try {
      const res = await rtsStockInSessionRemove(stockSession.id, itemId);
      setStockSession(res.session);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not remove the draft line.");
    } finally {
      setStockInBusy(false);
    }
  };

  const cancelStockSession = async () => {
    if (!stockSession) return;
    setStockInBusy(true);
    setError(null);
    try {
      await rtsStockInSessionCancel(stockSession.id);
      setStockSession(null);
      setCancelSessionOpen(false);
      setCameraOpen(false);
      setToast("RTS stock-in session cancelled. Inventory was not changed.");
      window.setTimeout(() => setToast(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not cancel the RTS stock-in session.");
    } finally {
      setStockInBusy(false);
    }
  };

  const confirmStockSession = async () => {
    if (!stockSession) return;
    setStockInBusy(true);
    setError(null);
    try {
      const res = await rtsStockInSessionConfirm(stockSession.id);
      setConfirmSessionOpen(false);
      setCameraOpen(false);
      setStockSession(null);
      setToast(`Confirmed ${res.session.total_qty} unit(s) into RTS inventory.`);
      window.setTimeout(() => setToast(null), 4000);
      await loadInventory(false);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not confirm the RTS stock-in session.");
      setConfirmSessionOpen(false);
    } finally {
      setStockInBusy(false);
    }
  };

  const syncNow = useCallback(async () => {
    if (!canSync) {
      setError("Your role cannot run RTS sync.");
      return;
    }
    setSyncing(true);
    setError(null);
    try {
      const result = await apiPost<{ synced: number; last_synced_at: string | null; last_error: string | null }>(
        "rts_sync.php",
        "run"
      );
      await loadInventory(false);
      if (result.synced > 0) {
        setToast(`Synced ${result.synced} product line(s) to inventory`);
      } else {
        setToast("Sync finished — no new product lines to add (cases need product lines first)");
      }
      window.setTimeout(() => setToast(null), 4000);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "RTS sync failed.");
    } finally {
      setSyncing(false);
    }
  }, [canSync, loadInventory]);

  useEffect(() => {
    void loadInventory();
    const t = setInterval(() => {
      if (!canSync) return;
      void apiPost("rts_sync.php", "run").catch(() => null).then(() => loadInventory(false));
    }, 60000);
    return () => clearInterval(t);
  }, [loadInventory, canSync]);

  return (
    <PageLayout
      title="RTS inventory"
      subtitle={
        barcodeBackedSellable
          ? "Sellable on-hand is barcode-backed — scan units here after Record RTS"
          : "Synced from operational RTS records"
      }
      actions={
        <div className="flex gap-2 flex-wrap">
          <AppPressable
            type="button"
            onClick={() => void syncNow()}
            disabled={syncing || !canSync}
            className="text-xs border rounded-lg px-3 py-1.5 text-muted-foreground hover:text-foreground disabled:opacity-50"
          >
            <RefreshCcw size={13} className={`inline mr-1 ${syncing ? "animate-spin" : ""}`} />
            {syncing ? "Syncing…" : "Sync now"}
          </AppPressable>
          {canRecord && (
            <Link to="/rts/record" className="text-xs font-semibold text-white px-3 py-1.5 rounded-lg inline-flex items-center gap-1" style={{ background: "#C8900A" }}>
              Record RTS
            </Link>
          )}
        </div>
      }
    >
      <AppSuccessNotice message={toast} />
      <AppErrorBanner message={error} />
      <div className="space-y-6">
        {canMutate && (
          <AppSectionEnter index={0}>
            <section className="rounded-xl border border-border bg-card p-4 space-y-3">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                  <ScanLine size={16} className="text-primary" />
                  <h3 className="text-sm font-semibold">RTS stock-in session</h3>
                </div>
                {stockSession ? (
                  <span className="rounded-full bg-secondary px-2.5 py-1 text-xs font-medium">
                    Draft #{stockSession.id} · {stockSession.total_qty} unit{stockSession.total_qty === 1 ? "" : "s"}
                  </span>
                ) : null}
              </div>
              <p className="text-xs text-muted-foreground">
                Scans stay in a draft until you review and confirm. SKU scans add +1 each time; unique stickers can
                appear once. Inventory does not change if you cancel.
              </p>
              {!stockSession ? (
                <Button
                  type="button"
                  disabled={stockInBusy}
                  onClick={() => void startStockSession()}
                  className="gap-1.5"
                >
                  {stockInBusy ? <Loader2 size={16} className="animate-spin" /> : <Plus size={16} />}
                  Start or resume session
                </Button>
              ) : (
                <>
                  <div className="flex flex-wrap gap-2 max-w-2xl">
                    <input
                      ref={stockInRef}
                      {...stockInInputProps}
                      className="flex-1 min-w-[12rem] border rounded-lg px-3 py-2 text-sm"
                      placeholder="Scan barcode and press Enter"
                      value={stockInCode}
                      disabled={stockInBusy}
                      onChange={e => setStockInCode(e.target.value)}
                      onKeyDown={e => {
                        if (e.key !== "Enter") return;
                        e.preventDefault();
                        void runStockInScan();
                      }}
                    />
                    <Button
                      type="button"
                      variant="outline"
                      className="gap-1.5 shrink-0"
                      disabled={stockInBusy}
                      onClick={() => setCameraOpen(true)}
                    >
                      <Camera size={16} /> Camera
                    </Button>
                    <Button
                      type="button"
                      disabled={stockInBusy || !stockInCode.trim()}
                      onClick={() => void runStockInScan()}
                      className="gap-1.5"
                    >
                      {stockInBusy ? <Loader2 size={14} className="animate-spin" /> : <ScanLine size={14} />}
                      Scan
                    </Button>
                  </div>

                  {stockSession.items.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border bg-secondary/20 p-5 text-center text-sm text-muted-foreground">
                      No products scanned yet. Open the camera or use a hardware scanner.
                    </div>
                  ) : (
                    <>
                      <div className="space-y-2 sm:hidden">
                        {stockSession.items.map(item => (
                          <div key={item.id} className={cn("rounded-xl border border-border p-3", appHoverRow)}>
                            <div className="flex items-start justify-between gap-3">
                              <div className="min-w-0">
                                <p className="text-sm font-semibold">{item.product_label}</p>
                                <p className="text-xs text-muted-foreground">
                                  {item.variant ? `${variantLabel(item.variant)} · ` : ""}
                                  {item.size_label ?? item.size ?? (item.lookup_kind === "sku" ? "SKU" : "Unique sticker")}
                                </p>
                                <p className="mt-1 truncate font-mono text-xs text-muted-foreground">{item.code}</p>
                              </div>
                              <span className="text-lg font-bold tabular-nums">×{item.qty}</span>
                            </div>
                            <div className="mt-3 flex justify-end gap-2">
                              {item.lookup_kind === "sku" ? (
                                <Button type="button" size="sm" variant="outline" onClick={() => startSessionEdit(item)}>
                                  <Pencil size={14} className="mr-1" /> Edit qty
                                </Button>
                              ) : null}
                              <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => void removeSessionItem(item.id)}
                                disabled={stockInBusy}
                                aria-label={`Remove ${item.product_label}`}
                              >
                                <Trash2 size={15} />
                              </Button>
                            </div>
                          </div>
                        ))}
                      </div>
                      <div className="hidden overflow-x-auto rounded-xl border border-border sm:block">
                        <table className="w-full text-sm">
                          <thead>
                            <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                              <th className="px-3 py-2">Product</th>
                              <th className="px-3 py-2">Code</th>
                              <th className="px-3 py-2 text-right">Qty</th>
                              <th className="w-32 px-3 py-2" />
                            </tr>
                          </thead>
                          <tbody>
                            {stockSession.items.map(item => (
                              <tr key={item.id} className={cn("border-b last:border-b-0", appHoverRow)}>
                                <td className="px-3 py-2">
                                  <span className="font-medium">{item.product_label}</span>
                                  <span className="block text-xs text-muted-foreground">
                                    {item.variant ? `${variantLabel(item.variant)} · ` : ""}
                                    {item.size_label ?? item.size ?? (item.lookup_kind === "sku" ? "SKU" : "Unique sticker")}
                                  </span>
                                </td>
                                <td className="px-3 py-2 font-mono text-xs">{item.code}</td>
                                <td className="px-3 py-2 text-right text-base font-bold tabular-nums">{item.qty}</td>
                                <td className="px-3 py-2">
                                  <div className="flex justify-end gap-1">
                                    {item.lookup_kind === "sku" ? (
                                      <Button type="button" size="sm" variant="outline" onClick={() => startSessionEdit(item)}>
                                        Edit
                                      </Button>
                                    ) : null}
                                    <Button
                                      type="button"
                                      size="sm"
                                      variant="ghost"
                                      onClick={() => void removeSessionItem(item.id)}
                                      disabled={stockInBusy}
                                      aria-label={`Remove ${item.product_label}`}
                                    >
                                      <Trash2 size={15} />
                                    </Button>
                                  </div>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </>
                  )}

                  <div className="flex flex-col-reverse gap-2 border-t border-border pt-3 sm:flex-row sm:justify-end">
                    <Button type="button" variant="outline" disabled={stockInBusy} onClick={() => setCancelSessionOpen(true)}>
                      Cancel session
                    </Button>
                    <Button
                      type="button"
                      disabled={stockInBusy || stockSession.items.length === 0}
                      onClick={() => setConfirmSessionOpen(true)}
                    >
                      Confirm {stockSession.total_qty} unit{stockSession.total_qty === 1 ? "" : "s"}
                    </Button>
                  </div>
                </>
              )}
            </section>
          </AppSectionEnter>
        )}
        {cameraOpen && stockSession ? (
          <BarcodeCameraScannerModal
            titleId={cameraTitleId}
            title={`RTS draft #${stockSession.id}`}
            continuous
            duplicateMode="presentationEdge"
            scanPrompt="Scan continuously. Move a barcode out of view, then present it again to add another +1."
            onClose={() => {
              setCameraOpen(false);
              stockInRefocus();
            }}
            onDecoded={runStockInScan}
          />
        ) : null}
        {barcodeBackedSellable && !canUseRtsQty && (
          <p className="text-xs rounded-lg border border-border bg-secondary/40 px-3 py-2 text-muted-foreground">
            Qty-only <span className="font-medium text-foreground">Use RTS</span> is off for sellable stock.
            Sell via POS/pack by scanning RTS barcodes. Damaged Use stays on the damaged page.
          </p>
        )}
        {diagnostic && diagnostic.case_count > 0 && diagnostic.line_item_count === 0 && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 space-y-2">
            <p>
              <strong>Sync ran, but inventory stays empty.</strong> You have {diagnostic.case_count} RTS case(s), but none have product lines yet.
              Inventory is built from <code className="text-xs">rts_line_items</code> (honey, pouches, etc. per case), not from tracking numbers alone.
            </p>
            <p className="flex flex-wrap gap-2">
              <Link to="/rts/record" className="inline-block text-xs font-semibold text-white px-3 py-1.5 rounded-lg" style={{ background: "#C8900A" }}>
                Record RTS — add product lines
              </Link>
              <Link to="/rts/records" className="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg border border-amber-400 text-[#8a5c00]">
                Open RTS Records
              </Link>
            </p>
          </div>
        )}
        {sync?.last_error && (
          <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Sync warning: {sync.last_error}
          </div>
        )}
        <p className="text-xs text-muted-foreground">
          Last synced: {sync?.last_synced_at ? new Date(sync.last_synced_at.replace(" ", "T")).toLocaleString() : "Never"}
          {readonly && " · Read-only for your role"}
          {barcodeBackedSellable && " · Sellable qty from barcode stock-in"}
        </p>

        {loading ? (
          <div className="text-sm text-muted-foreground py-8 text-center"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</div>
        ) : (
          <>
            <AppSectionEnter index={1}>
              <section>
                <div className="flex items-center justify-between gap-2 mb-3 flex-wrap">
                  <h3 className="text-sm font-semibold">Honey RTS</h3>
                  {canExecutiveEdit && (
                    <Button type="button" size="sm" variant="outline" onClick={() => setAddHoneyOpen(true)}>
                      <Plus size={14} className="mr-1" /> Add honey
                    </Button>
                  )}
                </div>
                <RtsTable
                  rows={honey}
                  honey
                  barcodeBacked={barcodeBackedSellable}
                  onUse={showUseRts ? setUseRow : undefined}
                  onEdit={canExecutiveEdit ? setEditHoney : undefined}
                />
              </section>
            </AppSectionEnter>
            <AppSectionEnter index={2}>
              <section>
                <div className="flex items-center justify-between gap-2 mb-3 flex-wrap">
                  <h3 className="text-sm font-semibold">Other products</h3>
                  {canExecutiveEdit && (
                    <Button type="button" size="sm" variant="outline" onClick={() => setAddOtherOpen(true)}>
                      <Plus size={14} className="mr-1" /> Add product
                    </Button>
                  )}
                </div>
                <RtsTable
                  rows={other}
                  honey={false}
                  barcodeBacked={barcodeBackedSellable}
                  onUse={showUseRts ? setUseRow : undefined}
                  onEdit={canExecutiveEdit ? setEditOther : undefined}
                />
              </section>
            </AppSectionEnter>
            <AppSectionEnter index={3}>
              <section>
                <h3 className="text-sm font-semibold mb-3">Used RTS</h3>
                <UsedRtsTable rows={useLog} />
              </section>
            </AppSectionEnter>
          </>
        )}
        {canViewOps && (
          <p className="text-xs text-muted-foreground">
            <Link to="/rts/records" className="text-[#C8900A] underline">View all RTS cases</Link>
            {" · "}
            <Link to="/rts/dashboard" className="text-[#C8900A] underline">RTS dashboard</Link>
          </p>
        )}
      </div>
      {useRow && (
        <UseRtsModal
          row={useRow}
          onClose={() => setUseRow(null)}
          onDone={() => {
            setUseRow(null);
            setToast("RTS stock used");
            window.setTimeout(() => setToast(null), 3500);
            void loadInventory(false);
          }}
          onError={msg => setError(msg)}
        />
      )}
      {(editHoney || addHoneyOpen) && (
        <EditHoneyModal
          row={editHoney}
          existingVariants={honey.map(r => (r.variant ?? "").toLowerCase()).filter(Boolean)}
          onClose={() => {
            setEditHoney(null);
            setAddHoneyOpen(false);
          }}
          onDone={msg => {
            setEditHoney(null);
            setAddHoneyOpen(false);
            setToast(msg);
            window.setTimeout(() => setToast(null), 3500);
            void loadInventory(false);
          }}
          onError={msg => setError(msg)}
        />
      )}
      {(editOther || addOtherOpen) && (
        <EditOtherModal
          row={editOther}
          onClose={() => {
            setEditOther(null);
            setAddOtherOpen(false);
          }}
          onDone={msg => {
            setEditOther(null);
            setAddOtherOpen(false);
            setToast(msg);
            window.setTimeout(() => setToast(null), 3500);
            void loadInventory(false);
          }}
          onError={msg => setError(msg)}
        />
      )}
      <AppModal
        open={editSessionItem !== null}
        onClose={() => setEditSessionItem(null)}
        panelClassName="max-w-sm w-full"
        labelledBy="rts-session-qty-title"
      >
        <AppModalHeader
          title="Edit draft quantity"
          titleId="rts-session-qty-title"
          onClose={() => setEditSessionItem(null)}
        />
        <AppModalBody className="space-y-3">
          <p className="text-sm font-medium">{editSessionItem?.product_label}</p>
          <p className="font-mono text-xs text-muted-foreground">{editSessionItem?.code}</p>
          <label className="block text-xs font-medium uppercase tracking-wide text-muted-foreground">
            Quantity
            <input
              type="number"
              min={1}
              max={5000}
              value={editSessionQty}
              onChange={e => setEditSessionQty(e.target.value)}
              onKeyDown={e => {
                if (e.key === "Enter") {
                  e.preventDefault();
                  void saveSessionQty();
                }
              }}
              className="mt-1 w-full rounded-lg border-2 border-border bg-card px-3 py-2.5 text-sm"
            />
          </label>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" disabled={stockInBusy} onClick={() => setEditSessionItem(null)}>
            Cancel
          </Button>
          <Button type="button" disabled={stockInBusy} onClick={() => void saveSessionQty()}>
            {stockInBusy ? "Saving…" : "Save quantity"}
          </Button>
        </AppModalFooter>
      </AppModal>

      <AppModal
        open={confirmSessionOpen}
        onClose={() => !stockInBusy && setConfirmSessionOpen(false)}
        panelClassName="max-w-md w-full"
        labelledBy="rts-session-confirm-title"
      >
        <AppModalHeader
          title="Confirm RTS stock-in?"
          titleId="rts-session-confirm-title"
          onClose={() => !stockInBusy && setConfirmSessionOpen(false)}
        />
        <AppModalBody className="space-y-2 text-sm">
          <p>
            Add <strong>{stockSession?.total_qty ?? 0} unit(s)</strong> across{" "}
            <strong>{stockSession?.line_count ?? 0} reviewed line(s)</strong> to sellable RTS inventory.
          </p>
          <p className="text-xs text-muted-foreground">
            All lines are rechecked and applied together. If one line is invalid, nothing is stocked in.
          </p>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" disabled={stockInBusy} onClick={() => setConfirmSessionOpen(false)}>
            Review again
          </Button>
          <Button type="button" disabled={stockInBusy} onClick={() => void confirmStockSession()}>
            {stockInBusy ? "Confirming…" : "Confirm session"}
          </Button>
        </AppModalFooter>
      </AppModal>

      <AppModal
        open={cancelSessionOpen}
        onClose={() => !stockInBusy && setCancelSessionOpen(false)}
        panelClassName="max-w-md w-full"
        labelledBy="rts-session-cancel-title"
      >
        <AppModalHeader
          title="Cancel this session?"
          titleId="rts-session-cancel-title"
          onClose={() => !stockInBusy && setCancelSessionOpen(false)}
        />
        <AppModalBody className="space-y-2 text-sm">
          <p>Discard all scanned draft lines?</p>
          <p className="text-xs text-muted-foreground">RTS inventory will not be changed.</p>
        </AppModalBody>
        <AppModalFooter>
          <Button type="button" variant="outline" disabled={stockInBusy} onClick={() => setCancelSessionOpen(false)}>
            Keep session
          </Button>
          <Button type="button" variant="destructive" disabled={stockInBusy} onClick={() => void cancelStockSession()}>
            {stockInBusy ? "Cancelling…" : "Cancel session"}
          </Button>
        </AppModalFooter>
      </AppModal>
    </PageLayout>
  );
}

function UsedRtsTable({ rows }: { rows: UseLogRow[] }) {
  if (!rows.length) {
    return <p className="text-sm text-muted-foreground">No used RTS entries yet.</p>;
  }
  return (
    <div className="bg-card rounded-xl border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
            <th className="px-3 py-2 text-left">Date</th>
            <th className="px-3 py-2 text-left">Product</th>
            <th className="px-3 py-2 text-left">Variant</th>
            <th className="px-3 py-2 text-left">Size</th>
            <th className="px-3 py-2 text-left">Qty</th>
            <th className="px-3 py-2 text-left">Where used</th>
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.id} className={cn("border-b", appHoverRow)}>
              <td className="px-3 py-2 whitespace-nowrap text-xs">
                {r.created_at ? new Date(r.created_at.replace(" ", "T")).toLocaleString() : "—"}
              </td>
              <td className="px-3 py-2">
                {r.product_kind === "honey"
                  ? "Honey"
                  : (r.product_label ?? "—")}
              </td>
              <td className="px-3 py-2 capitalize">
                {r.product_kind === "honey" && r.variant ? variantLabel(r.variant) : "—"}
              </td>
              <td className="px-3 py-2">{r.size ?? "—"}</td>
              <td className="px-3 py-2">{r.qty}</td>
              <td className="px-3 py-2 font-medium">{whereLabel(r.used_where)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RtsTable({
  rows,
  honey,
  barcodeBacked,
  onUse,
  onEdit,
}: {
  rows: RtsInvRow[];
  honey: boolean;
  barcodeBacked?: boolean;
  onUse?: (r: RtsInvRow) => void;
  onEdit?: (r: RtsInvRow) => void;
}) {
  const showActions = !!(onUse || onEdit);
  if (!rows.length) {
    return (
      <p className="text-sm text-muted-foreground">
        {barcodeBacked
          ? "No sellable qty yet — scan barcodes above after Record RTS (or print labels, stick, then scan here)."
          : onEdit
            ? "No rows yet. Use Add above to create inventory, or sync after RTS records."
            : "No rows yet. Run sync after new RTS records are saved."}
      </p>
    );
  }
  if (honey) {
    return (
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => <th key={s} className="px-3 py-2 text-left">{s}</th>)}
              {showActions && <th className="px-3 py-2" />}
            </tr>
          </thead>
          <tbody>
            {rows.map(r => (
              <tr key={r.variant ?? r.product_label} className={cn("border-b", appHoverRow)}>
                <td className="px-3 py-2 capitalize">
                  {r.variant ?? r.product_label}
                  {(r.line_count ?? 0) > 1 && (
                    <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                  )}
                </td>
                <td className="px-3 py-2"><QtyCounter current={r.current_liter} initial={r.initial_liter} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_250ml} initial={r.initial_250ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_350ml} initial={r.initial_350ml} /></td>
                <td className="px-3 py-2"><QtyCounter current={r.current_gallon} initial={r.initial_gallon} /></td>
                {showActions && (
                  <td className="px-3 py-2 whitespace-nowrap text-right space-x-2">
                    {onEdit && (
                      <AppPressable type="button" onClick={() => onEdit(r)} className="text-xs font-semibold text-foreground">
                        Edit
                      </AppPressable>
                    )}
                    {onUse && (
                      <AppPressable type="button" onClick={() => onUse(r)} className="text-xs font-semibold text-[#C8900A]">
                        Use RTS
                      </AppPressable>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }
  return (
    <div className="bg-card rounded-xl border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/40">
            <th className="px-4 py-2 text-left">Product</th>
            <th className="px-4 py-2 text-left">Qty</th>
            {showActions && <th />}
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.product_label} className={cn("border-b", appHoverRow)}>
              <td className="px-4 py-3">
                {r.product_label}
                {(r.line_count ?? 0) > 1 && (
                  <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                )}
              </td>
              <td className="px-4 py-3"><QtyCounter current={r.current_qty} initial={r.initial_qty} /></td>
              {showActions && (
                <td className="px-4 py-3 whitespace-nowrap text-right space-x-2">
                  {onEdit && (
                    <AppPressable type="button" onClick={() => onEdit(r)} className="text-xs font-semibold text-foreground">
                      Edit
                    </AppPressable>
                  )}
                  {onUse && (
                    <AppPressable type="button" onClick={() => onUse(r)} className="text-xs text-[#C8900A] font-semibold">
                      Use RTS
                    </AppPressable>
                  )}
                </td>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function UseRtsModal({
  row,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow;
  onClose: () => void;
  onDone: () => void;
  onError: (msg: string) => void;
}) {
  const [sizes, setSizes] = useState<Record<string, string>>({});
  const [qtyOther, setQtyOther] = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    try {
      if (row.category === "honey") {
        const size_qtys: Record<string, number> = {};
        HONEY_SIZES.forEach(s => {
          const v = parseFloat(sizes[s] || "0");
          if (v > 0) size_qtys[s] = v;
        });
        await apiPost("rts.php", "inventory_use_rts", {
          honey_variant: row.variant ?? "other",
          size_qtys,
          sale_type: "walk_in",
        });
      } else {
        await apiPost("rts.php", "inventory_use_rts", {
          product_label: row.product_label,
          qty_other: parseFloat(qtyOther),
          sale_type: "walk_in",
        });
      }
      onDone();
    } catch (err) {
      onError(err instanceof ApiError ? err.message : "Could not use RTS stock.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={busy ? () => undefined : onClose} panelClassName="max-w-sm w-full" labelledBy="rts-use-modal-title">
      <AppModalHeader
        title={`Use RTS — ${row.variant ? variantLabel(row.variant) : row.product_label}`}
        titleId="rts-use-modal-title"
        onClose={busy ? () => undefined : onClose}
      />
      <AppModalBody>
        <form onSubmit={e => void submit(e)} className="space-y-2 text-sm">
          <p className="text-xs text-muted-foreground mb-2">
            Deducts from sellable RTS for packaging use. Logged under Where used = Packaging.
          </p>
          {row.category === "honey" ? HONEY_SIZES.map(s => (
            <input
              key={s}
              type="number"
              step="0.01"
              min="0"
              placeholder={s}
              onChange={e => setSizes({ ...sizes, [s]: e.target.value })}
              className="w-full border rounded-lg px-3 py-2"
            />
          )) : (
            <input
              type="number"
              step="0.01"
              min="0"
              value={qtyOther}
              onChange={e => setQtyOther(e.target.value)}
              className="w-full border rounded-lg px-3 py-2"
              required
            />
          )}
          <Button type="submit" disabled={busy} className="w-full mt-2 font-bold text-white" style={{ background: "#C8900A" }}>
            {busy ? "Saving…" : "Confirm Use RTS"}
          </Button>
        </form>
      </AppModalBody>
    </AppModal>
  );
}

function EditHoneyModal({
  row,
  existingVariants,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow | null;
  existingVariants: string[];
  onClose: () => void;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const isAdd = !row;
  const availableVariants = isAdd
    ? HONEY_VARIANTS.filter(v => !existingVariants.includes(v))
    : [...HONEY_VARIANTS];
  const [variant, setVariant] = useState(
    () => row?.variant ?? availableVariants[0] ?? "light",
  );
  const [sizes, setSizes] = useState<Record<string, string>>(() =>
    row
      ? {
          liter: String(row.current_liter ?? "0"),
          "250ml": String(row.current_250ml ?? "0"),
          "350ml": String(row.current_350ml ?? "0"),
          gallon: String(row.current_gallon ?? "0"),
        }
      : { liter: "0", "250ml": "0", "350ml": "0", gallon: "0" },
  );
  const [busy, setBusy] = useState(false);

  const save = async () => {
    const v = (isAdd ? variant : row?.variant ?? variant).toLowerCase();
    if (!v) {
      onError("Pick a honey variant.");
      return;
    }
    setBusy(true);
    try {
      const size_qtys: Record<string, number> = {};
      for (const s of HONEY_SIZES) size_qtys[s] = Number(sizes[s] ?? 0);
      await apiPost("rts.php", "inventory_set_honey", {
        variant: v,
        pool: "sellable",
        size_qtys,
      });
      onDone(isAdd ? "Honey RTS added." : "Honey RTS updated.");
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save honey RTS.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={busy ? () => undefined : onClose} panelClassName="max-w-md w-full" labelledBy="rts-edit-honey-title">
      <AppModalHeader
        title={isAdd ? "Add honey RTS" : `Edit — ${variantLabel(row?.variant ?? "")}`}
        titleId="rts-edit-honey-title"
        onClose={busy ? () => undefined : onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {isAdd && (
          <label className="block text-xs space-y-1">
            <span className="uppercase text-muted-foreground font-semibold">Variant</span>
            {availableVariants.length === 0 ? (
              <p className="text-amber-800 text-sm">All variants already exist — use Edit on a row.</p>
            ) : (
              <select
                value={variant}
                onChange={e => setVariant(e.target.value)}
                className="w-full border rounded-lg px-3 py-2 bg-card"
              >
                {availableVariants.map(v => (
                  <option key={v} value={v}>{variantLabel(v)}</option>
                ))}
              </select>
            )}
          </label>
        )}
        <div className="grid grid-cols-2 gap-2">
          {HONEY_SIZES.map(s => (
            <label key={s} className="text-xs space-y-1">
              <span className="uppercase text-muted-foreground font-semibold">{s}</span>
              <input
                type="number"
                min="0"
                step="0.01"
                value={sizes[s] ?? "0"}
                onChange={e => setSizes(p => ({ ...p, [s]: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2"
              />
            </label>
          ))}
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
        <Button
          type="button"
          disabled={busy || (isAdd && availableVariants.length === 0)}
          onClick={() => void save()}
          style={{ background: "#C8900A" }}
          className="text-white"
        >
          {busy ? "Saving…" : "Save"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}

function EditOtherModal({
  row,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow | null;
  onClose: () => void;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const isAdd = !row;
  const [label, setLabel] = useState(row?.product_label ?? OTHER_PRODUCT_PRESETS[0]);
  const [customLabel, setCustomLabel] = useState("");
  const [qty, setQty] = useState(String(row?.current_qty ?? "0"));
  const [busy, setBusy] = useState(false);

  const save = async () => {
    const productLabel = isAdd
      ? (label === "__custom__" ? customLabel.trim() : label.trim())
      : (row?.product_label ?? "").trim();
    if (!productLabel) {
      onError("Product name is required.");
      return;
    }
    setBusy(true);
    try {
      await apiPost("rts.php", "inventory_set_other", {
        product_label: productLabel,
        qty: Number(qty),
        pool: "sellable",
      });
      onDone(isAdd ? "Product added to RTS." : "RTS product updated.");
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save RTS product.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={busy ? () => undefined : onClose} panelClassName="max-w-sm w-full" labelledBy="rts-edit-other-title">
      <AppModalHeader
        title={isAdd ? "Add other product" : `Edit — ${row?.product_label ?? ""}`}
        titleId="rts-edit-other-title"
        onClose={busy ? () => undefined : onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {isAdd && (
          <>
            <label className="block text-xs space-y-1">
              <span className="uppercase text-muted-foreground font-semibold">Product</span>
              <select
                value={label}
                onChange={e => setLabel(e.target.value)}
                className="w-full border rounded-lg px-3 py-2 bg-card"
              >
                {OTHER_PRODUCT_PRESETS.map(p => (
                  <option key={p} value={p}>{p}</option>
                ))}
                <option value="__custom__">Custom…</option>
              </select>
            </label>
            {label === "__custom__" && (
              <input
                value={customLabel}
                onChange={e => setCustomLabel(e.target.value)}
                placeholder="Product name"
                className="w-full border rounded-lg px-3 py-2"
              />
            )}
          </>
        )}
        <label className="block text-xs space-y-1">
          <span className="uppercase text-muted-foreground font-semibold">Quantity</span>
          <input
            type="number"
            min="0"
            step="0.01"
            value={qty}
            onChange={e => setQty(e.target.value)}
            className="w-full border rounded-lg px-3 py-2"
          />
        </label>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
        <Button
          type="button"
          disabled={busy}
          onClick={() => void save()}
          style={{ background: "#C8900A" }}
          className="text-white"
        >
          {busy ? "Saving…" : "Save"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
