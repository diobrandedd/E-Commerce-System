import { useCallback, useEffect, useState } from "react";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { PackagingSetValuesModal } from "@/app/components/inventory/PackagingSetValuesModal";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { listMaterials, type MaterialStockRow } from "@/app/lib/materials";

const peso = (v: string | number | undefined) => {
  const n = typeof v === "string" ? parseFloat(v) : Number(v ?? 0);
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

export function PackagingMaterialsPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const canSet = isExec || user?.role === "hr";
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [editStock, setEditStock] = useState<MaterialStockRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listMaterials("packaging");
      setItems(data.items);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const colSpan = canSet ? 5 : 4;

  return (
    <PageLayout
      title="Packaging Materials"
      subtitle="Unit price and on-hand — used in product costing formula lines"
    >
      {isExec && (
        <div className="mb-4 flex justify-end">
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg"
            style={{ background: "#C8900A" }}
          >
            <Plus size={14} /> Add item
          </button>
        </div>
      )}
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40">
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Item</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Unit price</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">On hand</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground hidden sm:table-cell">Last change</th>
              {canSet && <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground" />}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={colSpan} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {!loading && items.length === 0 && (
              <tr>
                <td colSpan={colSpan} className="py-8 text-center text-muted-foreground text-sm">
                  No packaging items yet.{isExec ? " Add your first catalog entry above." : ""}
                </td>
              </tr>
            )}
            {!loading && items.map(row => (
              <tr key={row.id} className="border-b">
                <td className="px-4 py-3 font-medium">
                  {row.name}
                  <span className="ml-2 text-xs text-muted-foreground">({row.unit})</span>
                </td>
                <td className="px-4 py-3">{peso(row.current_price)}</td>
                <td className="px-4 py-3">
                  {row.on_hand} {row.unit}
                </td>
                <td className="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell">
                  {row.last_entry_at
                    ? new Date(row.last_entry_at.replace(" ", "T")).toLocaleString()
                    : "—"}
                </td>
                {canSet && (
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      onClick={() => setEditStock(row)}
                      className="text-xs font-semibold text-[#C8900A] hover:underline"
                    >
                      Adjust
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {showCreate && (
        <MaterialCatalogModal
          category="packaging"
          onClose={() => setShowCreate(false)}
          onDone={() => { setShowCreate(false); void refresh(); }}
        />
      )}
      {editStock && (
        <PackagingSetValuesModal
          stock={editStock}
          onClose={() => setEditStock(null)}
          onDone={() => { setEditStock(null); void refresh(); }}
        />
      )}
    </PageLayout>
  );
}
