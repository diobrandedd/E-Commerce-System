import { useCallback, useEffect, useMemo, useState } from "react";
import { useParams } from "react-router";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { useAuth } from "@/app/context/AuthContext";
import { canExecutiveDirectEdit, canMutateHoneyInventory } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import {
  deptProductUsesSupplierPricing,
  deptSellUnitsPerBag,
  deptStockInYieldHint,
  deptSupplierCategoryForProduct,
  type DeptSupplierProductKey,
} from "@/app/lib/deptProducts";
import { resolveProductInventoryRoute } from "@/app/lib/inventoryRoutes";
import { listSuppliers, type Supplier, type SupplierCategory } from "@/app/lib/suppliers";

interface DeptRow {
  id: number | null;
  product_key: string;
  product_label: string;
  unit?: string;
  stock_in_unit?: string;
  inventory_unit?: string;
  uses_supplier_pricing?: boolean;
  pouches_per_bag?: number | null;
  current_qty: string | number;
  initial_qty: string | number;
  dept_name: string;
  last_stock_in_at?: string | null;
  selling_price?: string | number | null;
}

function inventorySubtitle(deptKey: string): string {
  if (deptKey === "paragis") {
    return "Stock in raw leaf bags (supplier price) — on-hand is sellable pouches";
  }
  if (deptKey === "cacao_brc") {
    return "Stock in raw bags (supplier price) for PT/BRC — on-hand is pouches; Jar Cacao uses manual cost";
  }
  return "Stock in finished units";
}

export function DeptInventoryPage() {
  const { dept, productSlug } = useParams();
  const routeSlug = productSlug ?? dept ?? "";
  const routeConfig = resolveProductInventoryRoute(routeSlug);
  const deptKey = routeConfig?.deptKey ?? routeSlug.replace("-", "_");
  const productKeyFilter = routeConfig?.productKey;
  const title = routeConfig?.title ?? deptKey.replace("_", " / ");
  const { user } = useAuth();
  const canMutate = canMutateHoneyInventory(user?.role);
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const [rows, setRows] = useState<DeptRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [editRow, setEditRow] = useState<DeptRow | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const params: Record<string, string> = { dept: deptKey };
      if (productKeyFilter) params.product_key = productKeyFilter;
      const data = await apiGet<{ rows: DeptRow[] }>("honey.php", "dept_list", params);
      setRows(data.rows);
    } finally {
      setLoading(false);
    }
  }, [deptKey, productKeyFilter]);

  useEffect(() => { refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  return (
    <PageLayout
      title={`${title} inventory`}
      subtitle={routeConfig?.subtitle ?? inventorySubtitle(deptKey)}
    >
      {canMutate && (
        <button
          type="button"
          onClick={() => setShowStockIn(true)}
          className="mb-4 inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg"
          style={{ background: "#C8900A" }}
        >
          <Plus size={14} /> Stock in
        </button>
      )}
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40">
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Product</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">On hand</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground hidden sm:table-cell">Last stock-in</th>
              {canExecEdit && <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground" />}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={canExecEdit ? 4 : 3} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {!loading && rows.map(r => (
              <tr key={r.product_key} className="border-b">
                <td className="px-4 py-3 font-medium">
                  {r.product_label}
                  {r.inventory_unit && (
                    <span className="ml-2 text-xs text-muted-foreground">({r.inventory_unit})</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  <QtyCounter current={String(r.current_qty)} initial={String(r.initial_qty)} />
                </td>
                <td className="px-4 py-3 text-muted-foreground text-xs hidden sm:table-cell">
                  {r.last_stock_in_at ? new Date(r.last_stock_in_at.replace(" ", "T")).toLocaleString() : "—"}
                </td>
                {canExecEdit && (
                  <td className="px-4 py-3">
                    {r.id != null ? (
                      <button
                        type="button"
                        onClick={() => setEditRow(r)}
                        className="text-xs font-semibold text-muted-foreground hover:text-foreground"
                      >
                        Edit
                      </button>
                    ) : (
                      <span className="text-xs text-muted-foreground">—</span>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {showStockIn && (
        <DeptStockInModal
          deptKey={deptKey}
          products={rows}
          onClose={() => setShowStockIn(false)}
          onDone={() => { setShowStockIn(false); refresh(); }}
        />
      )}
      {editRow && editRow.id != null && (
        <ExecutiveRowEditModal
          stage="dept"
          row={{ ...editRow, id: editRow.id }}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}

function supplierCategoryForProductKey(productKey: string): SupplierCategory | null {
  if (productKey === "pt" || productKey === "brc" || productKey === "jar_cacao") {
    return deptSupplierCategoryForProduct(productKey as DeptSupplierProductKey);
  }
  return null;
}

function DeptStockInModal({
  deptKey,
  products,
  onClose,
  onDone,
}: {
  deptKey: string;
  products: DeptRow[];
  onClose: () => void;
  onDone: () => void;
}) {
  const catalog = products.filter(p => p.dept_name === deptKey || deptKey === "");
  const [productKey, setProductKey] = useState(catalog[0]?.product_key ?? "");
  const [qty, setQty] = useState("");
  const [unitCost, setUnitCost] = useState("");
  const [supplierId, setSupplierId] = useState("");
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [suppliersLoading, setSuppliersLoading] = useState(false);
  const [arrival, setArrival] = useState(new Date().toISOString().slice(0, 10));
  const [note, setNote] = useState("");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selected = catalog.find(p => p.product_key === productKey);
  const usesSupplier = deptProductUsesSupplierPricing(productKey) || selected?.uses_supplier_pricing;
  const sellUnitsPerBag =
    selected?.sell_units_per_bag ?? selected?.pouches_per_bag ??
    (productKey === "pt" || productKey === "brc" || productKey === "jar_cacao"
      ? deptSellUnitsPerBag(productKey)
      : 1);
  const inventoryUnitLabel = selected?.inventory_unit ?? (usesSupplier ? "unit" : "pcs");
  const supplierCategory = supplierCategoryForProductKey(productKey);
  const bags = parseFloat(qty);
  const sellUnitPreview =
    usesSupplier && Number.isFinite(bags) && bags > 0
      ? (bags * sellUnitsPerBag).toFixed(2)
      : null;

  useEffect(() => {
    if (!usesSupplier || !supplierCategory) {
      setSuppliers([]);
      setSupplierId("");
      return;
    }
    setSuppliersLoading(true);
    setSupplierId("");
    void listSuppliers({ category: supplierCategory, active: true })
      .then(data => {
        setSuppliers(data.suppliers);
        if (data.suppliers.length === 1) {
          setSupplierId(String(data.suppliers[0].id));
        }
      })
      .catch(() => setSuppliers([]))
      .finally(() => setSuppliersLoading(false));
  }, [usesSupplier, supplierCategory, productKey]);

  const stockInUnit = useMemo(() => {
    if (selected?.stock_in_unit) return selected.stock_in_unit;
    return usesSupplier ? "bag" : "pcs";
  }, [selected, usesSupplier]);

  const yieldHint =
    productKey === "pt" || productKey === "brc" || productKey === "jar_cacao"
      ? deptStockInYieldHint(productKey)
      : null;

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const payload: Record<string, unknown> = {
        product_key: productKey,
        qty: parseFloat(qty),
        arrival_date: arrival,
        note: note.trim() || undefined,
      };
      if (usesSupplier) {
        payload.supplier_id = parseInt(supplierId, 10);
      } else {
        payload.unit_cost = parseFloat(unitCost);
      }
      await apiPost("honey.php", "dept_stock_in", payload);
      onDone();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not save stock-in.");
    } finally {
      setSaving(false);
    }
  };

  const inputCls = "w-full border border-border rounded-lg px-3 py-2 text-sm bg-background";
  const supplierPlaceholder =
    supplierCategory === "black_rice"
      ? "Select black rice supplier…"
      : supplierCategory === "cacao"
        ? "Select cacao supplier…"
        : "Select paragis supplier…";

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="dept-stock-in-title">
      <AppModalHeader title="Stock in" titleId="dept-stock-in-title" onClose={onClose} />
      <form onSubmit={submit}>
        <AppModalBody className="space-y-3">
          {error && <p className="text-xs text-red-600">{error}</p>}
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Product</label>
            <select value={productKey} onChange={e => setProductKey(e.target.value)} required className={inputCls}>
              {catalog.map(p => (
                <option key={p.product_key} value={p.product_key}>{p.product_label}</option>
              ))}
            </select>
          </div>
          {usesSupplier ? (
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Supplier</label>
              {suppliersLoading ? (
                <p className="text-xs text-muted-foreground flex items-center gap-1">
                  <Loader2 size={12} className="animate-spin" /> Loading suppliers…
                </p>
              ) : (
                <select
                  value={supplierId}
                  onChange={e => setSupplierId(e.target.value)}
                  required
                  className={inputCls}
                >
                  <option value="">{supplierPlaceholder}</option>
                  {suppliers.map(s => (
                    <option key={s.id} value={s.id}>{s.display_name}</option>
                  ))}
                </select>
              )}
              {yieldHint && (
                <p className="text-xs text-muted-foreground mt-1">
                  Price from Supplier Master (₱/bag). {yieldHint}
                </p>
              )}
            </div>
          ) : null}
          <div className={usesSupplier ? "" : "grid grid-cols-2 gap-2"}>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">
                Quantity ({stockInUnit}{usesSupplier ? "s" : ""})
              </label>
              <input type="number" step={usesSupplier ? "0.01" : "1"} min="0.01" value={qty} onChange={e => setQty(e.target.value)} required className={inputCls} />
              {sellUnitPreview && (
                <p className="text-xs text-muted-foreground mt-1">
                  ≈ {sellUnitPreview} {inventoryUnitLabel}(s) to inventory
                </p>
              )}
            </div>
            {!usesSupplier && (
              <div>
                <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Unit cost (₱)</label>
                <input type="number" step="0.01" min="0" value={unitCost} onChange={e => setUnitCost(e.target.value)} required className={inputCls} />
              </div>
            )}
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Arrival date</label>
            <input type="date" value={arrival} onChange={e => setArrival(e.target.value)} required className={inputCls} />
          </div>
          <div>
            <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">Note (optional)</label>
            <input type="text" value={note} onChange={e => setNote(e.target.value)} maxLength={255} className={inputCls} />
          </div>
        </AppModalBody>
        <AppModalFooter>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">Cancel</button>
          <button
            type="submit"
            disabled={saving || (usesSupplier && !supplierId)}
            className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-50"
            style={{ background: "#C8900A" }}
          >
            {saving ? "Saving…" : "Stock in"}
          </button>
        </AppModalFooter>
      </form>
    </AppModal>
  );
}
