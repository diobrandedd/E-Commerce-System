import { useCallback, useEffect, useState } from "react";
import { useParams } from "react-router";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { DeptStockInModal } from "@/app/components/dept/DeptStockInModal";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { canExecutiveDirectEdit, canMutateHoneyInventory } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { fetchDeptInventory, type DeptInventoryRow } from "@/app/lib/deptInventory";
import { resolveProductInventoryRoute } from "@/app/lib/inventoryRoutes";

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
  const canMutate = canMutateHoneyInventory(user);
  const canExecEdit = canExecutiveDirectEdit(user);
  const [rows, setRows] = useState<DeptInventoryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [editRow, setEditRow] = useState<DeptInventoryRow | null>(null);
  const [showStockIn, setShowStockIn] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchDeptInventory(deptKey, productKeyFilter);
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
