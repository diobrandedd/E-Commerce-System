import { useCallback, useEffect, useState } from "react";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialAdjustModal } from "@/app/components/inventory/MaterialAdjustModal";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { listMaterials, type MaterialCategory, type MaterialStockRow } from "@/app/lib/materials";

interface MaterialsCategoryPageProps {
  category: MaterialCategory;
  title: string;
  subtitle: string;
}

export function MaterialsCategoryPage({ category, title, subtitle }: MaterialsCategoryPageProps) {
  const { can } = useAuth();
  const isExec = can("packaging.catalog");
  const isPm = can("packaging.deduct") && !can("packaging.catalog");
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [adjustStock, setAdjustStock] = useState<MaterialStockRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listMaterials(category);
      setItems(data.items);
    } finally {
      setLoading(false);
    }
  }, [category]);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  return (
    <PageLayout title={title} subtitle={subtitle}>
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
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">On hand</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground hidden sm:table-cell">Last change</th>
              {(isExec || (isPm && category !== "supply")) && (
                <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground" />
              )}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={4} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {!loading && items.length === 0 && (
              <tr>
                <td colSpan={4} className="py-8 text-center text-muted-foreground text-sm">
                  No items yet.{isExec ? " Add your first catalog entry above." : ""}
                </td>
              </tr>
            )}
            {!loading && items.map(row => (
              <tr key={row.id} className="border-b">
                <td className="px-4 py-3 font-medium">
                  {row.name}
                  <span className="ml-2 text-xs text-muted-foreground">({row.unit})</span>
                </td>
                <td className="px-4 py-3">{row.on_hand}</td>
                <td className="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell">
                  {row.last_entry_at
                    ? new Date(row.last_entry_at.replace(" ", "T")).toLocaleString()
                    : "—"}
                </td>
                {(isExec || (isPm && category !== "supply")) && (
                  <td className="px-4 py-3">
                    {category !== "supply" && (
                      <button
                        type="button"
                        onClick={() => setAdjustStock(row)}
                        className="text-xs font-semibold text-[#C8900A] hover:underline"
                      >
                        {isExec ? "Adjust" : "Request"}
                      </button>
                    )}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {showCreate && (
        <MaterialCatalogModal
          category={category}
          onClose={() => setShowCreate(false)}
          onDone={() => { setShowCreate(false); void refresh(); }}
        />
      )}
      {adjustStock && (
        <MaterialAdjustModal
          stock={adjustStock}
          mode={isExec ? "executive" : "request"}
          onClose={() => setAdjustStock(null)}
          onDone={() => { setAdjustStock(null); void refresh(); }}
        />
      )}
    </PageLayout>
  );
}

/** @deprecated Supplies catalog is PackagingMaterialsPage at /inventory/supplies */
export { PackagingMaterialsPage as SuppliesInventoryPage } from "./PackagingMaterialsPage";
