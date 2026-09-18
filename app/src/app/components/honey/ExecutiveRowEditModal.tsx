import { useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { SupplierCombobox } from "@/app/components/suppliers/SupplierCombobox";
import { apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import type { Supplier } from "@/app/lib/suppliers";

export type EditStage = "warehouse" | "production" | "packaging" | "dept";
export type QtyEditMode = "current_only" | "set_exact";

export interface WarehouseEditRow {
  id: number;
  supplier_id?: number;
  supplier_code: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
}

export interface ProductionEditRow {
  id: number;
  supplier_code: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
  is_active?: number | boolean;
}

export interface PackagingEditRow {
  id: number;
  supplier_code: string;
  batch_no: string;
  variant: string;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
}

export interface DeptEditRow {
  id: number;
  product_label: string;
  current_qty: string;
  initial_qty: string;
}

type Props =
  | { stage: "warehouse"; row: WarehouseEditRow; onClose: () => void; onSaved: () => void }
  | { stage: "production"; row: ProductionEditRow; onClose: () => void; onSaved: () => void }
  | { stage: "packaging"; row: PackagingEditRow; onClose: () => void; onSaved: () => void }
  | { stage: "dept"; row: DeptEditRow; onClose: () => void; onSaved: () => void };

function fmtPair(current: string, initial: string, unit?: string): string {
  const c = parseFloat(current);
  const i = parseFloat(initial);
  const cs = Number.isFinite(c) ? (Math.abs(c - Math.round(c)) < 0.001 ? String(Math.round(c)) : c.toFixed(1)) : current;
  const is = Number.isFinite(i) ? (Math.abs(i - Math.round(i)) < 0.001 ? String(Math.round(i)) : i.toFixed(1)) : initial;
  return unit ? `${cs} / ${is} ${unit}` : `${cs} / ${is}`;
}

function QtyModePicker({ mode, onChange }: { mode: QtyEditMode; onChange: (m: QtyEditMode) => void }) {
  return (
    <div className="rounded-lg border border-border bg-secondary/20 p-3 space-y-2">
      <p className="text-xs font-semibold text-muted-foreground">Quantity change type</p>
      <label className="flex items-start gap-2 text-xs cursor-pointer">
        <input
          type="radio"
          name="qty_mode"
          checked={mode === "current_only"}
          onChange={() => onChange("current_only")}
          className="mt-0.5"
        />
        <span>
          <span className="font-medium text-foreground">Adjust current on hand only</span>
          <span className="block text-muted-foreground">Recorded (initial) amount stays the same.</span>
        </span>
      </label>
      <label className="flex items-start gap-2 text-xs cursor-pointer">
        <input
          type="radio"
          name="qty_mode"
          checked={mode === "set_exact"}
          onChange={() => onChange("set_exact")}
          className="mt-0.5"
        />
        <span>
          <span className="font-medium text-foreground">Set to exact value</span>
          <span className="block text-muted-foreground">Resets both current and recorded (initial) to the value you enter.</span>
        </span>
      </label>
    </div>
  );
}

export function ExecutiveRowEditModal(props: Props) {
  const { onClose, onSaved, stage } = props;
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [qtyMode, setQtyMode] = useState<QtyEditMode>("current_only");
  const fieldId = useId();

  const [supplier, setSupplier] = useState<Supplier | null>(null);
  const [variant, setVariant] = useState(
    stage === "warehouse" || stage === "production" || stage === "packaging" ? props.row.variant : "light",
  );
  const [qty, setQty] = useState(
    stage === "warehouse" || stage === "production" || stage === "dept" ? props.row.current_qty : "",
  );
  const [isActive, setIsActive] = useState(
    stage === "production" ? props.row.is_active !== 0 && props.row.is_active !== false : true,
  );
  const [productLabel, setProductLabel] = useState(stage === "dept" ? props.row.product_label : "");
  const [sizeQtys, setSizeQtys] = useState<Record<string, string>>(
    stage === "packaging"
      ? {
          liter: props.row.current_liter,
          "250ml": props.row.current_250ml,
          "350ml": props.row.current_350ml,
          gallon: props.row.current_gallon,
        }
      : {},
  );
  const [reason, setReason] = useState("");

  const rowTable =
    stage === "warehouse"
      ? "honey_warehouse_rows"
      : stage === "production"
        ? "honey_production_rows"
        : stage === "packaging"
          ? "honey_packaging_rows"
          : "dept_inventory_rows";

  const submit = async () => {
    setBusy(true);
    setErr(null);
    if (stage === "packaging") {
      const trimmed = reason.trim();
      if (trimmed.length < 5) {
        setErr("Enter a reason for this correction (at least 5 characters).");
        setBusy(false);
        return;
      }
    }
    const fields: Record<string, unknown> = { qty_mode: qtyMode };
    if (stage === "warehouse") {
      if (supplier) fields.supplier_id = supplier.id;
      fields.variant = variant;
      fields.current_qty = parseFloat(qty);
    } else if (stage === "production") {
      fields.variant = variant;
      fields.current_qty = parseFloat(qty);
      fields.is_active = isActive ? 1 : 0;
    } else if (stage === "packaging") {
      fields.variant = variant;
      const sq: Record<string, number> = {};
      HONEY_SIZES.forEach(s => {
        const v = parseFloat(sizeQtys[s] ?? "0");
        if (Number.isFinite(v)) sq[s] = v;
      });
      fields.size_qtys = sq;
      fields.reason = reason.trim();
    } else {
      fields.product_label = productLabel;
      fields.current_qty = parseFloat(qty);
    }
    try {
      await apiPost("honey.php", "executive_edit", {
        stage,
        row_table: rowTable,
        row_id: props.row.id,
        fields,
      });
      onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Save failed");
    } finally {
      setBusy(false);
    }
  };

  const title =
    stage === "warehouse"
      ? `Edit warehouse — ${props.row.supplier_code}`
      : stage === "production"
        ? `Edit production — ${props.row.supplier_code}`
        : stage === "packaging"
          ? props.row.batch_no === "POOL"
            ? `Edit finished honey — ${props.row.variant}`
            : `Edit packaging — ${props.row.batch_no}`
          : "Edit dept row";

  const recordedQty =
    stage === "warehouse"
      ? fmtPair(props.row.current_qty, props.row.initial_qty, "gallon/s")
      : stage === "production"
        ? fmtPair(props.row.current_qty, props.row.initial_qty, "gallon/s")
        : stage === "dept"
          ? fmtPair(props.row.current_qty, props.row.initial_qty, "units")
          : null;

  const packagingInitial: Record<string, string> =
    stage === "packaging"
      ? {
          liter: props.row.initial_liter,
          "250ml": props.row.initial_250ml,
          "350ml": props.row.initial_350ml,
          gallon: props.row.initial_gallon,
        }
      : {};

  const packagingCurrent: Record<string, string> =
    stage === "packaging"
      ? {
          liter: props.row.current_liter,
          "250ml": props.row.current_250ml,
          "350ml": props.row.current_350ml,
          gallon: props.row.current_gallon,
        }
      : {};

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="exec-row-edit-title">
      <AppModalHeader
        title={title}
        titleId="exec-row-edit-title"
        subtitle="Fixes data entry errors only. Does not move stock between stages. For packaging, quantities are current on hand (packing/sales deduct from these). Warehouse supplier/variant changes cascade to linked production and packaging rows (batch numbers stay unchanged)."
        onClose={onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
          {err && <AppErrorBanner message={err} />}

          {stage === "warehouse" && (
            <>
              <div>
                <label className="text-xs text-muted-foreground block mb-1">Supplier</label>
                <SupplierCombobox value={supplier} onChange={setSupplier} category="honey" />
                <p className="text-[10px] text-muted-foreground mt-1 font-mono">Current code: {props.row.supplier_code}</p>
              </div>
              <div>
                <label htmlFor={`${fieldId}-variant`} className="text-xs text-muted-foreground block mb-1">Variant</label>
                <select id={`${fieldId}-variant`} value={variant} onChange={e => setVariant(e.target.value)} className="w-full border rounded-lg px-3 py-2">
                  {HONEY_VARIANTS.map(v => (
                    <option key={v} value={v}>
                      {v}
                    </option>
                  ))}
                </select>
              </div>
              <p className="text-xs text-muted-foreground">
                On hand now: <span className="font-semibold text-foreground">{recordedQty}</span>
              </p>
              <QtyModePicker mode={qtyMode} onChange={setQtyMode} />
              <div>
                <label htmlFor={`${fieldId}-qty`} className="text-xs text-muted-foreground block mb-1">
                  {qtyMode === "set_exact" ? "Gallon/s (exact — current & recorded)" : "Gallon/s (current on hand)"}
                </label>
                <input id={`${fieldId}-qty`} type="number" step="0.01" min="0" value={qty} onChange={e => setQty(e.target.value)} className="w-full border rounded-lg px-3 py-2" />
              </div>
            </>
          )}

          {stage === "production" && (
            <>
              <p className="text-xs font-mono text-muted-foreground">Supplier code: {props.row.supplier_code}</p>
              <div>
                <label htmlFor={`${fieldId}-variant`} className="text-xs text-muted-foreground block mb-1">Variant</label>
                <select id={`${fieldId}-variant`} value={variant} onChange={e => setVariant(e.target.value)} className="w-full border rounded-lg px-3 py-2">
                  {HONEY_VARIANTS.map(v => (
                    <option key={v} value={v}>
                      {v}
                    </option>
                  ))}
                </select>
              </div>
              <p className="text-xs text-muted-foreground">
                On hand now: <span className="font-semibold text-foreground">{recordedQty}</span>
              </p>
              <QtyModePicker mode={qtyMode} onChange={setQtyMode} />
              <div>
                <label htmlFor={`${fieldId}-qty`} className="text-xs text-muted-foreground block mb-1">
                  {qtyMode === "set_exact" ? "Gallon/s (exact — current & recorded)" : "Gallon/s (current on hand)"}
                </label>
                <input id={`${fieldId}-qty`} type="number" step="0.01" min="0" value={qty} onChange={e => setQty(e.target.value)} className="w-full border rounded-lg px-3 py-2" />
              </div>
              <label className="flex items-start gap-2 text-sm cursor-pointer rounded-lg border border-border bg-secondary/20 px-3 py-2.5">
                <input
                  type="checkbox"
                  checked={isActive}
                  onChange={e => setIsActive(e.target.checked)}
                  className="mt-0.5"
                />
                <span>
                  <span className="font-medium text-foreground">Active lot</span>
                  <span className="block text-xs text-muted-foreground">
                    Inactive lots stay in history but are hidden from the Production lot scanner dropdown.
                  </span>
                </span>
              </label>
            </>
          )}

          {stage === "packaging" && (
            <>
              <p className="text-xs font-mono text-muted-foreground">{props.row.supplier_code} · batch {props.row.batch_no}</p>
              <div>
                <label htmlFor={`${fieldId}-variant`} className="text-xs text-muted-foreground block mb-1">Variant</label>
                <select id={`${fieldId}-variant`} value={variant} onChange={e => setVariant(e.target.value)} className="w-full border rounded-lg px-3 py-2">
                  {HONEY_VARIANTS.map(v => (
                    <option key={v} value={v}>
                      {v}
                    </option>
                  ))}
                </select>
              </div>
              <QtyModePicker mode={qtyMode} onChange={setQtyMode} />
              {HONEY_SIZES.map(s => (
                <div key={s}>
                  <p className="text-[10px] text-muted-foreground mb-1">
                    {s} now: {fmtPair(packagingCurrent[s] ?? "0", packagingInitial[s] ?? "0", "bottles")}
                  </p>
                  <label htmlFor={`${fieldId}-${s}`} className="text-xs text-muted-foreground block mb-1">
                    {qtyMode === "set_exact" ? `${s} (exact — current & recorded)` : `${s} (current on hand)`}
                  </label>
                  <input
                    id={`${fieldId}-${s}`}
                    type="number"
                    step="0.01"
                    min="0"
                    value={sizeQtys[s] ?? ""}
                    onChange={e => setSizeQtys({ ...sizeQtys, [s]: e.target.value })}
                    className="w-full border rounded-lg px-3 py-2"
                  />
                </div>
              ))}
              <div>
                <label htmlFor={`${fieldId}-reason`} className="text-xs text-muted-foreground block mb-1">
                  Reason for correction <span className="text-destructive">*</span>
                </label>
                <textarea
                  id={`${fieldId}-reason`}
                  value={reason}
                  onChange={e => setReason(e.target.value)}
                  rows={3}
                  maxLength={500}
                  placeholder="e.g. Physical count short 12× 250ml Light — missed stock-in on Sep 17"
                  className="w-full border rounded-lg px-3 py-2 resize-y min-h-[4.5rem]"
                  required
                />
                <p className="text-[10px] text-muted-foreground mt-1">
                  Saved to Edit Logs for reporting. Does not rewrite stock-in scan history.
                </p>
              </div>
            </>
          )}

          {stage === "dept" && (
            <>
              <div>
                <label htmlFor={`${fieldId}-product`} className="text-xs text-muted-foreground block mb-1">Product label</label>
                <input id={`${fieldId}-product`} value={productLabel} onChange={e => setProductLabel(e.target.value)} className="w-full border rounded-lg px-3 py-2" />
              </div>
              <p className="text-xs text-muted-foreground">
                On hand now: <span className="font-semibold text-foreground">{recordedQty}</span>
              </p>
              <QtyModePicker mode={qtyMode} onChange={setQtyMode} />
              <div>
                <label htmlFor={`${fieldId}-qty`} className="text-xs text-muted-foreground block mb-1">
                  {qtyMode === "set_exact" ? "Qty (exact — current & recorded)" : "Qty (current on hand)"}
                </label>
                <input id={`${fieldId}-qty`} type="number" step="0.01" min="0" value={qty} onChange={e => setQty(e.target.value)} className="w-full border rounded-lg px-3 py-2" />
              </div>
            </>
          )}

      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose} className="min-h-11 flex-1">
          Cancel
        </Button>
        <Button
          type="button"
          disabled={busy}
          onClick={() => void submit()}
          className="min-h-11 flex-1"
        >
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Save changes"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
