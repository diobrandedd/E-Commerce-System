import { useMemo, useState } from "react";
import { AlertCircle } from "lucide-react";
import { ActionFeedbackButton } from "@/app/components/pos/posMotion";
import { StaffDebtorCombobox, type StaffDebtorValue } from "@/app/components/pos/StaffDebtorCombobox";
import type { PosStockSource } from "@/app/components/pos/POSStockSourceModal";
import { apiPost, ApiError } from "@/app/lib/api";
import { peso, variantLabel } from "@/app/lib/posHoney";

export interface POSARLineItem {
  key: string;
  label: string;
  qty: number;
  unitPrice: number;
  kind?: "honey" | "dept";
  productId?: number;
  variant?: string;
  size?: string;
  productKey?: string;
  lookupKind?: "sku" | "unique_honey" | "unique_dept";
  skuId?: number;
  bottleUnitIds?: number[];
  pouchUnitIds?: number[];
}

export interface POSARCheckoutResult {
  sale_id: number;
  receipt_no: string;
  total: number;
  debtor_name: string;
  debtor_mobile?: string | null;
  ar_credit_ids: number[];
}

interface POSARCheckoutPanelProps {
  mode: "honey" | "dept" | "unified";
  lines: POSARLineItem[];
  onCancel: () => void;
  onSuccess: (result: POSARCheckoutResult) => void;
}

export function POSARCheckoutPanel({ mode, lines, onCancel, onSuccess }: POSARCheckoutPanelProps) {
  const [debtor, setDebtor] = useState<StaffDebtorValue>({ debtor_user_id: null, debtor_name: "" });
  const [mobile, setMobile] = useState("");
  const [stockSource, setStockSource] = useState<PosStockSource>("packaging");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const total = useMemo(() => lines.reduce((s, l) => s + l.unitPrice * l.qty, 0), [lines]);
  const todayLabel = useMemo(
    () =>
      new Date().toLocaleDateString("en-PH", {
        weekday: "short",
        year: "numeric",
        month: "short",
        day: "numeric",
      }),
    [],
  );

  const submit = async () => {
    const debtorName = debtor.debtor_name.trim();
    if (!debtorName) {
      setError("Debtor name is required.");
      return;
    }
    if (lines.length === 0) {
      setError("Cart is empty.");
      return;
    }

    setSubmitting(true);
    setError(null);
    try {
      const hasUnitBarcodes = lines.some(
        l =>
          l.lookupKind === "sku" ||
          (l.bottleUnitIds?.length ?? 0) > 0 ||
          (l.pouchUnitIds?.length ?? 0) > 0,
      );
      const resolvedSource =
        mode === "unified" || (mode === "dept" && lines.some(l => (l.pouchUnitIds?.length ?? 0) > 0 || l.lookupKind === "sku"))
          ? "packaging"
          : stockSource;
      if (resolvedSource === "rts" && !hasUnitBarcodes) {
        setError(
          "RTS AR requires scanned unit barcodes in the cart. Qty-only RTS is disabled after barcode cutover.",
        );
        setSubmitting(false);
        return;
      }
      const action =
        mode === "honey" ? "honey_ar_checkout" : mode === "dept" ? "dept_ar_checkout" : "unified_ar_checkout";
      const items =
        mode === "unified"
          ? lines.map(l => {
              const lookupKind =
                l.lookupKind ??
                (l.skuId
                  ? "sku"
                  : l.bottleUnitIds?.length
                    ? "unique_honey"
                    : "unique_dept");
              if (lookupKind === "sku") {
                return {
                  kind: l.kind ?? "honey",
                  product_id: l.productId,
                  list_unit_price: l.unitPrice,
                  qty: l.qty,
                  lookup_kind: "sku",
                  sku_id: l.skuId,
                  variant: l.variant,
                  size: l.size,
                  product_key: l.productKey,
                };
              }
              return {
                kind: l.kind ?? (l.bottleUnitIds?.length ? "honey" : "dept"),
                product_id: l.productId,
                list_unit_price: l.unitPrice,
                qty: l.qty,
                lookup_kind: lookupKind,
                variant: l.variant,
                size: l.size,
                product_key: l.productKey,
                bottle_unit_ids: l.bottleUnitIds ?? [],
                pouch_unit_ids: l.pouchUnitIds ?? [],
              };
            })
          : mode === "honey"
          ? lines.map(l => ({
              product_id: l.productId,
              qty: l.qty,
              variant: l.variant,
              size: l.size,
              bottle_unit_ids: l.bottleUnitIds ?? [],
            }))
          : lines.map(l => ({
              product_key: l.productKey,
              product_id: l.productId,
              qty: l.qty,
              pouch_unit_ids: l.pouchUnitIds ?? [],
            }));

      const result = await apiPost<POSARCheckoutResult>("pos.php", action, {
        items,
        debtor_user_id: debtor.debtor_user_id,
        debtor_name: debtorName,
        debtor_mobile: mobile.trim(),
        stock_source: resolvedSource,
      });
      onSuccess(result);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not record AR checkout.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="bg-card rounded-2xl border w-full p-5 shadow-lg">
      <h3 className="font-bold text-lg mb-1 text-red-700">Accounts Receivable</h3>
      <p className="text-xs text-muted-foreground mb-4">Inventory will be deducted. Payment recorded as outstanding credit.</p>

      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-start gap-2 mb-3">
          <AlertCircle size={14} className="mt-0.5 flex-shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <div className="mb-4 space-y-2">
        <p className="text-xs uppercase tracking-wide text-muted-foreground font-semibold">Stock source</p>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => setStockSource("rts")}
            className={`flex-1 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors ${
              stockSource === "rts"
                ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a5c00]"
                : "border-border text-muted-foreground"
            }`}
          >
            RTS products
          </button>
          <button
            type="button"
            onClick={() => setStockSource("packaging")}
            className={`flex-1 rounded-lg border px-3 py-2 text-xs font-semibold transition-colors ${
              stockSource === "packaging"
                ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a5c00]"
                : "border-border text-muted-foreground"
            }`}
          >
            Non-RTS
          </button>
        </div>
        {stockSource === "rts" && !lines.some(l => (l.bottleUnitIds?.length ?? 0) > 0 || (l.pouchUnitIds?.length ?? 0) > 0) ? (
          <p className="text-xs text-amber-800 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
            RTS AR requires scanned unit barcodes in the cart. Qty-only RTS is not available after barcode cutover.
          </p>
        ) : null}
      </div>

      <div className="space-y-3 mb-4">
        <label className="block text-xs uppercase tracking-wide text-muted-foreground font-semibold">
          Debtor name
          <div className="mt-1">
            <StaffDebtorCombobox value={debtor} onChange={setDebtor} disabled={submitting} />
          </div>
        </label>
        <label className="block text-xs uppercase tracking-wide text-muted-foreground font-semibold">
          Mobile (optional)
          <input
            value={mobile}
            onChange={e => setMobile(e.target.value)}
            placeholder="09xx xxx xxxx"
            className="mt-1 w-full rounded-lg border border-border bg-card px-3 py-2 text-sm"
            disabled={submitting}
          />
        </label>
        <label className="block text-xs uppercase tracking-wide text-muted-foreground font-semibold">
          Date
          <input
            readOnly
            value={todayLabel}
            className="mt-1 w-full rounded-lg border border-border bg-secondary/30 px-3 py-2 text-sm text-muted-foreground"
          />
        </label>
      </div>

      <div className="space-y-2 text-sm mb-4 max-h-40 overflow-y-auto">
        {lines.map(line => (
          <div key={line.key} className="flex justify-between gap-2">
            <span>
              {mode === "honey" && line.variant ? `${variantLabel(line.variant)} · ` : ""}
              {line.label} ×{line.qty}
            </span>
            <span className="font-semibold">{peso(line.unitPrice * line.qty)}</span>
          </div>
        ))}
      </div>

      <div className="flex justify-between font-bold text-base border-t pt-3 mb-4">
        <span>TOTAL AR</span>
        <span className="text-red-700">{peso(total)}</span>
      </div>

      <div className="flex gap-2">
        <ActionFeedbackButton variant="cancel" onClick={onCancel} disabled={submitting}>
          Cancel
        </ActionFeedbackButton>
        <ActionFeedbackButton
          variant="confirm"
          disabled={submitting || !debtor.debtor_name.trim()}
          flashOnClick={false}
          onClick={() => void submit()}
          className="font-bold bg-red-600 hover:bg-red-700 border-red-600"
        >
          {submitting ? "Recording…" : "Record AR"}
        </ActionFeedbackButton>
      </div>
    </div>
  );
}
