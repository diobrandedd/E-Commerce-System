import { useCallback, useEffect, useMemo, useState } from "react";
import { Link, useParams } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { canExecutiveDirectEdit } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { deptStockInYieldHint, type DeptSupplierProductKey } from "@/app/lib/deptProducts";
import { fetchDeptInventory, type DeptInventoryRow } from "@/app/lib/deptInventory";
import { PRODUCT_INVENTORY_ROUTES } from "@/app/lib/inventoryRoutes";

const peso = (v: string | number | null | undefined) => {
  if (v == null || v === "") return "—";
  const n = typeof v === "string" ? parseFloat(v) : Number(v);
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

function formatDate(raw: string | null | undefined) {
  if (!raw) return "—";
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function yieldDetail(row: DeptInventoryRow): string | null {
  const key = row.product_key;
  if (key === "pt" || key === "brc" || key === "jar_cacao") {
    return deptStockInYieldHint(key as DeptSupplierProductKey);
  }
  return null;
}

function finishedSummary(row: DeptInventoryRow): string {
  const sellUnit = row.inventory_unit ?? row.unit ?? "unit";
  return `Finished ${sellUnit} count — stock in and produce from Raw Materials`;
}

export function DeptProductFinishedPage() {
  const { productSlug = "" } = useParams();
  const config = PRODUCT_INVENTORY_ROUTES[productSlug];
  const { user } = useAuth();
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const [rows, setRows] = useState<DeptInventoryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [editRow, setEditRow] = useState<DeptInventoryRow | null>(null);

  const refresh = useCallback(async () => {
    if (!config?.productKey) return;
    setLoading(true);
    try {
      const data = await fetchDeptInventory(config.deptKey, config.productKey);
      setRows(data.rows);
    } finally {
      setLoading(false);
    }
  }, [config?.deptKey, config?.productKey]);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const row = rows[0];

  const onHand = Number(row?.current_qty ?? 0);
  const initial = Number(row?.initial_qty ?? 0);
  const unit = row?.inventory_unit ?? row?.unit ?? "units";
  const hint = row ? yieldDetail(row) : null;
  const rawPath = config?.rawMaterialsPath;

  const retailValue = useMemo(() => {
    const price = row?.selling_price;
    if (price == null || price === "") return null;
    const p = typeof price === "string" ? parseFloat(price) : Number(price);
    if (!Number.isFinite(p)) return null;
    return onHand * p;
  }, [row?.selling_price, onHand]);

  if (!config?.productKey) {
    return null;
  }

  return (
    <PageLayout
      title={config.title}
      subtitle={config.subtitle ?? "Finished product inventory"}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          {rawPath && (
            <Link
              to={rawPath}
              className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
            >
              Stock in & produce →
            </Link>
          )}
        </div>
      }
    >
      {loading ? (
        <div className="py-16 text-center text-muted-foreground">
          <Loader2 className="inline animate-spin" size={20} />
        </div>
      ) : (
        <div className="space-y-5">
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div className="rounded-xl border bg-card px-4 py-3 col-span-2 lg:col-span-1">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">On hand</p>
              <p className="text-2xl font-semibold mt-1">{onHand.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{unit}</p>
            </div>
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Baseline</p>
              <p className="text-xl font-semibold mt-1">{initial.toLocaleString()}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">{unit}</p>
            </div>
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Sell price</p>
              <p className="text-xl font-semibold mt-1">{peso(row?.selling_price)}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5">per {unit}</p>
            </div>
            <div className="rounded-xl border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Last stock-in</p>
              <p className="text-sm font-semibold mt-1 leading-snug">{formatDate(row?.last_stock_in_at)}</p>
            </div>
          </div>

          <div className="rounded-xl border bg-card overflow-hidden">
            <div className="px-5 py-4 border-b bg-secondary/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <p className="text-sm font-semibold">{row?.product_label ?? config.title}</p>
                <p className="text-xs text-muted-foreground mt-0.5">{row ? finishedSummary(row) : "—"}</p>
              </div>
              {canExecEdit && row?.id != null && (
                <button
                  type="button"
                  onClick={() => setEditRow(row)}
                  className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-background shrink-0"
                >
                  Edit count
                </button>
              )}
            </div>
            <div className="px-5 py-6">
              <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                  <p className="text-[10px] uppercase tracking-wide text-muted-foreground mb-2">Current vs baseline</p>
                  <QtyCounter
                    current={String(row?.current_qty ?? 0)}
                    initial={String(row?.initial_qty ?? 0)}
                  />
                </div>
                {retailValue != null && onHand > 0 && (
                  <div className="text-right">
                    <p className="text-[10px] uppercase tracking-wide text-muted-foreground">At sell price</p>
                    <p className="text-lg font-semibold mt-0.5">{peso(retailValue)}</p>
                  </div>
                )}
              </div>
            </div>
          </div>

          {hint && rawPath && (
            <div className="rounded-xl border border-[#C8900A]/25 bg-[#C8900A]/5 px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-[#8a6506] font-semibold mb-1">Production yield</p>
              <p className="text-sm text-foreground leading-relaxed">{hint}</p>
              <Link to={rawPath} className="inline-block mt-2 text-xs font-semibold text-[#8a6506] hover:underline">
                Stock in & produce in Raw Materials →
              </Link>
            </div>
          )}

          {onHand <= 0 && (
            <p className="text-sm text-muted-foreground text-center py-4">
              No finished stock on hand.{rawPath ? " Stock in supplier bags and produce under Raw Materials." : ""}
            </p>
          )}
        </div>
      )}

      {editRow?.id != null && (
        <ExecutiveRowEditModal
          stage="dept"
          row={{ ...editRow, id: editRow.id }}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); void refresh(); }}
        />
      )}
    </PageLayout>
  );
}
