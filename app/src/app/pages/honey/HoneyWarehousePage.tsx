import { useCallback, useEffect, useState } from "react";
import { Camera, Plus, Loader2, ScanLine, Printer } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { HoneyStockStatusBadge } from "@/app/components/honey/HoneyStockStatusBadge";
import { BarcodeCameraScannerModal } from "@/app/components/scan/BarcodeCameraScannerModal";
import type { ScannerDecodeFeedback } from "@/app/components/scan/TrackingQrScannerModal";
import {
  AppErrorBanner,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { HONEY_VARIANTS } from "@/app/lib/honey";
import {
  canExecutiveDirectEdit,
  canGenerateHoneyWarehouseBarcodes,
  canMutateHoneyPipeline,
  canRequestHoneyWarehouseBarcodes,
  canViewSupplierProfile,
} from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { previewSupplierCode, type Supplier } from "@/app/lib/suppliers";
import type { WarehousePricingRow } from "@/app/lib/honeyPricing";
import {
  honeyWarehouseBarcodesApi,
  type HoneyWarehouseBarcodeCandidate,
  type HoneyWarehouseBarcodeRequest,
} from "@/app/lib/honeyWarehouseBarcodes";
import { playScanBeep } from "@/app/lib/scanBeep";
import { useScanWedgeField } from "@/app/lib/scanWedgeField";

type WhRow = WarehousePricingRow;

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function HoneyWarehousePage() {
  const { user } = useAuth();
  const canMutate = canMutateHoneyPipeline(user);
  const canExecEdit = canExecutiveDirectEdit(user);
  const canRequestBarcode = canRequestHoneyWarehouseBarcodes(user);
  const canGenerateBarcode = canGenerateHoneyWarehouseBarcodes(user);
  const showSupplierProfile = canViewSupplierProfile(user);
  const [rows, setRows] = useState<WhRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showIn, setShowIn] = useState(false);
  const [showOut, setShowOut] = useState<WhRow | null>(null);
  const [showBarcodeRequest, setShowBarcodeRequest] = useState(false);
  const [showBarcodeScan, setShowBarcodeScan] = useState(false);
  const [requestPreset, setRequestPreset] = useState<{ supplier: Supplier; arrivalDate: string } | undefined>();
  const [editRow, setEditRow] = useState<WhRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: WhRow[] }>("honey.php", "warehouse_list");
      setRows(data.rows);
      setPage(1);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);

  return (
    <PageLayout title="Raw Honey" subtitle="Warehouse — stock-in and stock-out to production">
      <div className="space-y-4">
        <HoneyStageTabs />
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex flex-wrap gap-2">
          {canMutate && (
            <Button type="button" onClick={() => setShowIn(true)}>
              <Plus size={14} /> Stock in
            </Button>
          )}
          {canRequestBarcode && (
            <Button type="button" variant="outline" onClick={() => setShowBarcodeRequest(true)}>
              <Printer size={14} /> Request supplier barcode
            </Button>
          )}
          {canMutate && (
            <Button type="button" variant="outline" onClick={() => setShowBarcodeScan(true)}>
              <ScanLine size={14} /> Scan stock out
            </Button>
          )}
        </div>
        <div className="bg-card rounded-xl border border-border overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-secondary/40">
                {[
                  "Supplier code",
                  "Arrival",
                  "Variant",
                  "Qty",
                  "Status",
                  canMutate || canExecEdit ? "Actions" : "",
                ]
                  .filter(Boolean)
                  .map(h => (
                    <th key={h} className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase">{h}</th>
                  ))}
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={canMutate || canExecEdit ? 6 : 5} className="px-4 py-8 text-center text-muted-foreground">
                    <Loader2 className="inline animate-spin" size={14} />
                  </td>
                </tr>
              )}
              {!loading && pagedRows.map(r => {
                return (
                  <tr key={r.id} className="border-b hover:bg-secondary/20">
                    <td className="px-4 py-3">
                      {showSupplierProfile ? (
                        <SupplierCodeButton code={r.supplier_code} />
                      ) : (
                        <span className="font-medium font-mono">{r.supplier_code}</span>
                      )}
                    </td>
                    <td className="px-4 py-3">{r.arrival_date}</td>
                    <td className="px-4 py-3 capitalize">{r.variant}</td>
                    <td className="px-4 py-3">
                      <QtyCounter current={r.current_qty} initial={r.initial_qty} />
                    </td>
                    <td className="px-4 py-3">
                      <HoneyStockStatusBadge current={r.current_qty} initial={r.initial_qty} />
                    </td>
                    {(canMutate || canExecEdit) && (
                      <td className="px-4 py-3 space-x-2">
                        {canExecEdit && (
                          <button type="button" onClick={() => setEditRow(r)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                            Edit
                          </button>
                        )}
                        {canMutate && (
                          <button disabled={Number(r.current_qty) <= 0} onClick={() => setShowOut(r)} className="text-xs font-semibold text-[#C8900A] disabled:opacity-40">
                            Stock out
                          </button>
                        )}
                      </td>
                    )}
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
        {!loading && totalRows > 0 && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1">
            <p className="text-xs text-muted-foreground">
              Showing {start + 1}-{Math.min(start + perPage, totalRows)} of {totalRows} rows
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={safePage <= 1}
                className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Previous
              </button>
              <span className="text-xs text-muted-foreground">
                Page {safePage} of {totalPages}
              </span>
              <button
                type="button"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
                className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
      {(canRequestBarcode || canGenerateBarcode) && (
        <WarehouseBarcodeRequestsPanel
          canRequest={canRequestBarcode}
          canGenerate={canGenerateBarcode}
          onRequest={() => setShowBarcodeRequest(true)}
        />
      )}
      {showIn && (
        <StockInModal
          onClose={() => setShowIn(false)}
          onDone={result => {
            setShowIn(false);
            void refresh();
            if (canRequestBarcode) {
              setRequestPreset({ supplier: result.supplier, arrivalDate: result.arrival_date });
              setShowBarcodeRequest(true);
            }
          }}
        />
      )}
      {showOut && <StockOutModal row={showOut} onClose={() => setShowOut(null)} onDone={() => { setShowOut(null); refresh(); }} />}
      {showBarcodeRequest && (
        <WarehouseBarcodeRequestModal
          preset={requestPreset}
          onClose={() => {
            setShowBarcodeRequest(false);
            setRequestPreset(undefined);
          }}
          onDone={() => {
            setShowBarcodeRequest(false);
            setRequestPreset(undefined);
          }}
        />
      )}
      {showBarcodeScan && (
        <WarehouseBarcodeStockOutModal
          onClose={() => setShowBarcodeScan(false)}
          onDone={() => void refresh()}
        />
      )}
      {editRow && (
        <ExecutiveRowEditModal
          stage="warehouse"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}

interface WarehouseStockInResult {
  supplier: Supplier;
  supplier_id: number;
  arrival_date: string;
  supplier_code: string;
}

function StockInModal({
  onClose,
  onDone,
}: {
  onClose: () => void;
  onDone: (result: WarehouseStockInResult) => void;
}) {
  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [variants, setVariants] = useState([{ variant: "light", qty: "" }]);
  const [showConfirm, setShowConfirm] = useState(false);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const codePreview = supplier ? previewSupplierCode(supplier.display_name, arrival) : "";

  const variantsValid = variants.every(v => parseFloat(v.qty) > 0);
  const canProceed = supplier !== null && variantsValid;

  const save = async () => {
    if (!supplier) return;
    setBusy(true);
    setErr(null);
    try {
      const result = await apiPost<Omit<WarehouseStockInResult, "supplier">>("honey.php", "warehouse_stock_in", {
        supplier_id: supplier.id,
        arrival_date: arrival,
        variants: variants.map(v => ({ variant: v.variant, qty: parseFloat(v.qty) })),
      });
      onDone({ ...result, supplier });
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Failed");
      setShowConfirm(false);
    } finally {
      setBusy(false);
    }
  };

  if (showConfirm && supplier) {
    return (
      <Modal title="Confirm stock-in" onClose={() => setShowConfirm(false)}>
        <div className="space-y-3 text-sm">
          <p><strong>Supplier:</strong> {supplier.display_name}</p>
          <p><strong>Arrival:</strong> {arrival}</p>
          <p><strong>Supplier code:</strong> <span className="font-mono">{codePreview}</span></p>
          <p className="text-xs text-muted-foreground">Same supplier + same arrival date always share this code. A later date gets a new code.</p>
          <table className="w-full text-xs border rounded-lg">
            <thead><tr className="bg-secondary/50"><th className="text-left px-2 py-1">Variant</th><th className="text-left px-2 py-1">Gallon/s</th></tr></thead>
            <tbody>
              {variants.map((v, i) => (
                <tr key={i} className="border-t"><td className="px-2 py-1 capitalize">{v.variant}</td><td className="px-2 py-1">{v.qty}</td></tr>
              ))}
            </tbody>
          </table>
          {err && <p className="text-xs text-red-600">{err}</p>}
          <div className="flex gap-2 pt-2">
            <button type="button" onClick={() => setShowConfirm(false)} className="flex-1 py-2 rounded-lg border text-sm">Back</button>
            <button type="button" disabled={busy} onClick={save} className="flex-1 py-2 rounded-lg text-sm font-bold text-white" style={{ background: "#C8900A" }}>
              {busy ? "Saving…" : "Confirm & save"}
            </button>
          </div>
        </div>
      </Modal>
    );
  }

  return (
    <Modal title="Warehouse stock-in" onClose={onClose}>
      <div className="space-y-3">
        {err && <p className="text-xs text-red-600">{err}</p>}
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Supplier</label>
          <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
        </div>
        {supplier && (
          <p className="text-xs text-muted-foreground">Supplier code preview: <strong className="font-mono">{codePreview}</strong></p>
        )}
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Arrival date</label>
          <input type="date" value={arrival} onChange={e => setArrival(e.target.value)} className="w-full border rounded-lg px-3 py-2 text-sm" />
        </div>
        <p className="text-xs font-semibold text-muted-foreground">Variants (one row each — qty is always in gallon/s)</p>
        {variants.map((v, i) => (
          <div key={i} className="flex gap-2 items-center">
            <select value={v.variant} onChange={e => { const n = [...variants]; n[i].variant = e.target.value; setVariants(n); }} className="border rounded-lg px-2 py-2 text-sm flex-1">
              {HONEY_VARIANTS.map(x => <option key={x} value={x}>{x}</option>)}
            </select>
            <span className="text-xs text-muted-foreground whitespace-nowrap shrink-0">Gallon/s</span>
            <input type="number" step="0.01" min="0" value={v.qty} onChange={e => { const n = [...variants]; n[i].qty = e.target.value; setVariants(n); }} placeholder="0" className="border rounded-lg px-2 py-2 text-sm w-20" required />
            {variants.length > 1 && (
              <button type="button" onClick={() => setVariants(variants.filter((_, j) => j !== i))} className="text-xs text-red-600">Remove</button>
            )}
          </div>
        ))}
        <button type="button" onClick={() => setVariants([...variants, { variant: "light", qty: "" }])} className="text-xs text-[#C8900A]">+ Add another variant</button>
        <button
          type="button"
          disabled={!canProceed}
          onClick={() => setShowConfirm(true)}
          className="w-full py-2.5 rounded-lg text-sm font-bold text-white disabled:opacity-50"
          style={{ background: "#C8900A" }}
        >
          Review & confirm
        </button>
      </div>
    </Modal>
  );
}

function StockOutModal({ row, onClose, onDone }: { row: WhRow; onClose: () => void; onDone: () => void }) {
  const [qty, setQty] = useState("");
  const [busy, setBusy] = useState(false);
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    try {
      await apiPost("honey.php", "warehouse_stock_out", { warehouse_row_id: row.id, qty: parseFloat(qty) });
      onDone();
    } finally {
      setBusy(false);
    }
  };
  return (
    <Modal title="Stock out → Production" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3 text-sm">
        <p>Code: <strong>{row.supplier_code}</strong> · {row.variant}</p>
        <p>Available: <QtyCounter current={row.current_qty} initial={row.initial_qty} /></p>
        <input type="number" step="0.01" value={qty} onChange={e => setQty(e.target.value)} placeholder="Quantity" className="w-full border rounded-lg px-3 py-2" required />
        <button type="submit" disabled={busy} className="w-full py-2.5 rounded-lg font-bold text-white" style={{ background: "#C8900A" }}>Confirm</button>
      </form>
    </Modal>
  );
}

function WarehouseBarcodeRequestsPanel({
  canRequest,
  canGenerate,
  onRequest,
}: {
  canRequest: boolean;
  canGenerate: boolean;
  onRequest: () => void;
}) {
  const [rows, setRows] = useState<HoneyWarehouseBarcodeRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const result = await honeyWarehouseBarcodesApi.requestList();
      setRows(result.rows);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load warehouse barcode requests.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const generate = async (requestId: number) => {
    setBusy(requestId);
    setError(null);
    try {
      const result = await honeyWarehouseBarcodesApi.generate(requestId);
      window.open(result.print_url, "_blank");
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not generate warehouse barcode labels.");
    } finally {
      setBusy(null);
    }
  };

  const cancel = async (requestId: number) => {
    setBusy(requestId);
    setError(null);
    try {
      await honeyWarehouseBarcodesApi.requestCancel(requestId);
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not cancel the request.");
    } finally {
      setBusy(null);
    }
  };

  return (
    <div className="bg-card rounded-xl border border-border overflow-hidden">
      <div className="px-4 py-3 border-b border-border flex flex-wrap items-center justify-between gap-2">
        <div>
          <h3 className="text-sm font-semibold">Supplier barcode requests</h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            One reusable barcode per supplier and arrival date. Print identical labels for warehouse containers.
          </p>
        </div>
        {canRequest && (
          <Button type="button" size="sm" variant="outline" onClick={onRequest}>
            <Printer size={14} /> Request labels
          </Button>
        )}
      </div>
      {error && <AppErrorBanner message={error} />}
      {loading ? (
        <div className="px-4 py-5 text-sm text-muted-foreground">Loading requests…</div>
      ) : rows.length === 0 ? (
        <div className="px-4 py-5 text-sm text-muted-foreground">No supplier barcode requests yet.</div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-secondary/40 text-[10px] uppercase tracking-wide text-muted-foreground">
              <tr>
                <th className="text-left px-3 py-2">When</th>
                <th className="text-left px-3 py-2">Supplier / arrival</th>
                <th className="text-left px-3 py-2">Code</th>
                <th className="text-left px-3 py-2">Labels</th>
                <th className="text-left px-3 py-2">Status</th>
                <th className="px-3 py-2" />
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {rows.map(row => (
                <tr key={row.id} className={appHoverRow}>
                  <td className="px-3 py-2 whitespace-nowrap">{row.requested_at.slice(0, 16)}</td>
                  <td className="px-3 py-2">
                    <span className="font-medium">{row.supplier_name}</span>
                    <span className="block text-xs text-muted-foreground">{row.arrival_date}</span>
                  </td>
                  <td className="px-3 py-2 font-mono">{row.supplier_code}</td>
                  <td className="px-3 py-2">{row.quantity}</td>
                  <td className="px-3 py-2 capitalize">{row.status}</td>
                  <td className="px-3 py-2 whitespace-nowrap">
                    {canGenerate && row.status !== "cancelled" && (
                      <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={busy === row.id}
                        onClick={() => void generate(row.id)}
                      >
                        {busy === row.id ? <Loader2 className="animate-spin" size={14} /> : <Printer size={14} />}
                        {row.status === "printed" ? "Reprint" : "Print"}
                      </Button>
                    )}
                    {canRequest && row.status === "pending" && (
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        disabled={busy === row.id}
                        onClick={() => void cancel(row.id)}
                      >
                        Cancel
                      </Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function WarehouseBarcodeRequestModal({
  preset,
  onClose,
  onDone,
}: {
  preset?: { supplier: Supplier; arrivalDate: string };
  onClose: () => void;
  onDone: () => void;
}) {
  const [supplier, setSupplier] = useState<Supplier | null>(preset?.supplier ?? null);
  const [arrival, setArrival] = useState(preset?.arrivalDate ?? new Date().toISOString().slice(0, 10));
  const [quantity, setQuantity] = useState("50");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const codePreview = supplier ? previewSupplierCode(supplier.display_name, arrival) : "";

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!supplier) return;
    setBusy(true);
    setError(null);
    try {
      await honeyWarehouseBarcodesApi.requestCreate({
        supplier_id: supplier.id,
        arrival_date: arrival,
        quantity: Number(quantity),
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not submit barcode request.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <Modal title="Request supplier barcode" onClose={onClose}>
      <form onSubmit={submit} className="space-y-3 text-sm">
        {error && <AppErrorBanner message={error} />}
        <p className="text-xs text-muted-foreground">
          This prints identical labels for the warehouse stock. The code is based on supplier initials plus arrival date.
        </p>
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Supplier</label>
          <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
        </div>
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Arrival date</label>
          <input
            type="date"
            value={arrival}
            onChange={e => setArrival(e.target.value)}
            className="w-full border rounded-lg px-3 py-2 text-sm"
            required
          />
        </div>
        {supplier && (
          <div className="rounded-lg bg-secondary/40 px-3 py-2">
            <p className="text-xs text-muted-foreground">Supplier/date barcode</p>
            <p className="font-mono font-bold text-base">{codePreview}</p>
          </div>
        )}
        <div>
          <label className="text-xs text-muted-foreground block mb-1">Number of identical labels</label>
          <input
            type="number"
            min={1}
            max={5000}
            value={quantity}
            onChange={e => setQuantity(e.target.value)}
            className="w-full border rounded-lg px-3 py-2 text-sm"
            required
          />
        </div>
        <div className="flex gap-2 pt-2">
          <Button type="button" variant="outline" className="flex-1" onClick={onClose}>Cancel</Button>
          <Button type="submit" className="flex-1" disabled={busy || supplier === null}>
            {busy ? <Loader2 className="animate-spin" size={15} /> : <Printer size={15} />}
            Submit request
          </Button>
        </div>
      </form>
    </Modal>
  );
}

function WarehouseBarcodeStockOutModal({
  onClose,
  onDone,
}: {
  onClose: () => void;
  onDone: () => void;
}) {
  const [code, setCode] = useState("");
  const [candidates, setCandidates] = useState<HoneyWarehouseBarcodeCandidate[]>([]);
  const [selectedKey, setSelectedKey] = useState("");
  const [quantity, setQuantity] = useState("1");
  const [cameraOpen, setCameraOpen] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const cameraTitleId = "honey-warehouse-barcode-camera";
  const { inputRef, refocus, inputProps } = useScanWedgeField(!cameraOpen);

  const candidateKey = (candidate: HoneyWarehouseBarcodeCandidate) =>
    `${candidate.supplier_id}|${candidate.arrival_date}|${candidate.variant}`;

  const lookup = async (raw: string): Promise<ScannerDecodeFeedback | void> => {
    const value = raw.trim();
    if (!value) return;
    setCode(value);
    setError(null);
    try {
      const result = await honeyWarehouseBarcodesApi.lookup(value);
      setCandidates(result.candidates);
      setSelectedKey(result.candidates.length === 1 ? candidateKey(result.candidates[0]) : "");
      setQuantity("1");
      setCameraOpen(false);
      playScanBeep("ok");
      refocus();
      return {
        kind: "success",
        message: result.candidates.length === 1
          ? "Supplier/date found — enter gallons."
          : "Multiple matches found — select the supplier and variant.",
      };
    } catch (e) {
      const reason = e instanceof ApiError ? e.message : "Barcode lookup failed.";
      setCandidates([]);
      setSelectedKey("");
      setError(reason);
      playScanBeep("bad");
      refocus();
      return { kind: "error", message: reason };
    }
  };

  const selected = candidates.find(candidate => candidateKey(candidate) === selectedKey) ?? null;

  const stockOut = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selected) {
      setError("Select the supplier and variant first.");
      return;
    }
    const qty = Number(quantity);
    if (!Number.isFinite(qty) || qty <= 0) {
      setError("Enter gallons greater than zero.");
      return;
    }
    setError(null);
    try {
      const result = await honeyWarehouseBarcodesApi.stockOut({
        code,
        supplier_id: selected.supplier_id,
        arrival_date: selected.arrival_date,
        variant: selected.variant,
        quantity_gallons: qty,
      });
      setMessage(
        `${result.stock_out.supplier_code} · ${result.stock_out.variant} · ${result.stock_out.quantity_gallons} gallon/s moved to Production.`,
      );
      setCandidates([]);
      setSelectedKey("");
      setCode("");
      setQuantity("1");
      onDone();
      refocus();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not stock out warehouse gallons.");
    }
  };

  return (
    <Modal title="Scan warehouse stock out" onClose={onClose}>
      <div className="space-y-3 text-sm">
        {message && <AppSuccessNotice message={message} />}
        {error && <AppErrorBanner message={error} />}
        <p className="text-xs text-muted-foreground">
          Scan the supplier/date label, choose the variant, then enter gallons moving to Production.
        </p>
        <div className="flex gap-2">
          <input
            ref={inputRef}
            {...inputProps}
            value={code}
            onChange={e => setCode(e.target.value)}
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void lookup(code);
              }
            }}
            placeholder="Scan supplier/date barcode"
            className="flex-1 border rounded-lg px-3 py-2.5 font-mono text-sm"
          />
          <Button type="button" variant="outline" onClick={() => setCameraOpen(true)}>
            <Camera size={16} /> Scan
          </Button>
        </div>
        {candidates.length > 0 && (
          <label className="block text-xs text-muted-foreground">
            Supplier / variant
            <select
              value={selectedKey}
              onChange={e => setSelectedKey(e.target.value)}
              className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
            >
              <option value="">Select a matching stock row…</option>
              {candidates.map(candidate => (
                <option key={candidateKey(candidate)} value={candidateKey(candidate)}>
                  {candidate.supplier_name} · {candidate.arrival_date} · {candidate.variant} · {candidate.current_qty} gal available
                </option>
              ))}
            </select>
          </label>
        )}
        {selected && (
          <form onSubmit={stockOut} className="rounded-lg border border-primary/30 bg-primary/5 p-3 space-y-2">
            <p className="font-medium">
              {selected.supplier_name} · {selected.variant} · {selected.arrival_date}
            </p>
            <p className="text-xs text-muted-foreground">{selected.current_qty} gallon/s available</p>
            <label className="block text-xs text-muted-foreground">
              Gallons to Production
              <input
                type="number"
                step="0.01"
                min="0.01"
                max={selected.current_qty}
                value={quantity}
                onChange={e => setQuantity(e.target.value)}
                className="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
                required
              />
            </label>
            <Button type="submit" className="w-full">
              <ScanLine size={15} /> Confirm stock out
            </Button>
          </form>
        )}
        {cameraOpen && (
          <BarcodeCameraScannerModal
            title="Scan supplier/date barcode"
            titleId={cameraTitleId}
            onClose={() => setCameraOpen(false)}
            onDecoded={decoded => lookup(decoded)}
          />
        )}
      </div>
    </Modal>
  );
}

function Modal({ title, children, onClose }: { title: string; children: React.ReactNode; onClose: () => void }) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="honey-warehouse-modal-title">
      <AppModalHeader title={title} titleId="honey-warehouse-modal-title" onClose={onClose} />
      <AppModalBody>{children}</AppModalBody>
    </AppModal>
  );
}
