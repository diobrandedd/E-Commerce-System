import { useCallback, useEffect, useId, useState } from "react";
import { Link } from "react-router";
import { Camera, Loader2, Plus, RefreshCcw, ScanLine } from "lucide-react";
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
import { rtsStockInScan } from "@/app/lib/rtsApi";
import { skuBarcodesApi } from "@/app/lib/skuBarcodes";
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
  const [skuQty, setSkuQty] = useState("1");
  const [lastSku, setLastSku] = useState<{ code: string; label: string } | null>(null);
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

  const finishStockIn = useCallback(
    async (code: string, qty?: number): Promise<ScannerDecodeFeedback | void> => {
      setStockInBusy(true);
      setError(null);
      try {
        const res = await rtsStockInScan(code, qty);
        const unit = res.unit;
        const label =
          res.kind === "honey"
            ? `${variantLabel(String(unit.variant ?? ""))} ${String(unit.size_label ?? unit.size ?? "")}`.trim()
            : String(unit.product_label ?? unit.product_key ?? "Dept unit");
        const qtyNote = qty != null && qty !== 1 ? ` ×${qty}` : "";
        playScanBeep("ok");
        setToast(`Stocked into RTS: ${unit.code ?? code}${qtyNote} · ${label}`);
        window.setTimeout(() => setToast(null), 4000);
        await loadInventory(false);
        stockInRefocus();
        return { kind: "success", message: `Stocked into RTS: ${label}${qtyNote}` };
      } catch (e) {
        playScanBeep("bad");
        const msg = e instanceof ApiError ? e.message : "RTS stock-in scan failed.";
        setError(msg);
        stockInRefocus();
        return { kind: "error", message: msg };
      } finally {
        setStockInBusy(false);
      }
    },
    [loadInventory, stockInRefocus],
  );

  const runStockInScan = useCallback(
    async (raw?: string): Promise<ScannerDecodeFeedback | void> => {
      const code = (raw ?? stockInCode).trim();
      if (!code || !canMutate) {
        return { kind: "error", message: "Scan a barcode." };
      }
      setStockInCode("");
      setError(null);
      try {
        const looked = await skuBarcodesApi.lookup(code);
        const lookup = looked.lookup;
        if (lookup.lookup_kind === "sku" || lookup.requires_qty) {
          const label = lookup.label ?? lookup.product_label ?? code;
          setLastSku({ code, label });
          return await finishStockIn(code, 1);
        }
        return await finishStockIn(code);
      } catch {
        return await finishStockIn(code);
      }
    },
    [stockInCode, canMutate, finishStockIn],
  );

  const addSkuBulk = async () => {
    if (!lastSku) {
      setError("Scan a SKU barcode first, then use Quantity + Add for bulk.");
      stockInRefocus();
      return;
    }
    const qty = Math.max(1, parseInt(skuQty, 10) || 1);
    setError(null);
    await finishStockIn(lastSku.code, qty);
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
              <div className="flex items-center gap-2">
                <ScanLine size={16} className="text-[#C8900A]" />
                <h3 className="text-sm font-semibold">Stock in to RTS</h3>
              </div>
              <p className="text-xs text-muted-foreground">
                Scan honey or dept barcodes into sellable RTS. SKU stickers: each scan adds +1, or type a
                quantity and Add. Unique leftovers stock in one unit. Use Camera on phones/tablets.
              </p>
              <div className="flex flex-wrap gap-2 max-w-xl">
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
                  style={{ background: "#C8900A" }}
                  className="text-white"
                >
                  {stockInBusy ? <Loader2 size={14} className="animate-spin mr-1" /> : <ScanLine size={14} className="mr-1" />}
                  Scan
                </Button>
              </div>
              <div className="rounded-lg border border-border bg-secondary/20 p-3 space-y-2 max-w-xl">
                <p className="text-xs text-muted-foreground">
                  Bulk SKU add{lastSku ? ` — last: ${lastSku.label}` : " — scan a SKU first"}
                </p>
                <div className="flex flex-wrap gap-2 items-end">
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
                  <Button type="button" variant="outline" onClick={() => void addSkuBulk()} disabled={!lastSku || stockInBusy}>
                    Add
                  </Button>
                </div>
              </div>
            </section>
          </AppSectionEnter>
        )}
        {cameraOpen ? (
          <BarcodeCameraScannerModal
            titleId={cameraTitleId}
            title="Stock in to RTS"
            continuous
            scanPrompt="Point at a honey or dept barcode for RTS sellable stock."
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
