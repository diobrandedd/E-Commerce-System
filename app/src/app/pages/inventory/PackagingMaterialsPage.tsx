import { useCallback, useEffect, useMemo, useState } from "react";
import { ChevronRight, Loader2, Package, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { PackagingMaterialDetailModal } from "@/app/components/inventory/PackagingMaterialDetailModal";
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
  const isHr = user?.role === "hr";
  const canStockIn = isExec || isHr;
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [detailRow, setDetailRow] = useState<MaterialStockRow | null>(null);

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

  const stats = useMemo(() => {
    const inStock = items.filter(i => Number(i.on_hand) > 0).length;
    const totalOnHand = items.reduce((sum, i) => sum + Number(i.on_hand ?? 0), 0);
    return { count: items.length, inStock, totalOnHand };
  }, [items]);

  return (
    <PageLayout
      title="Packaging Materials"
      subtitle="Purchase lots, unit cost for costing, and on-hand pool — click a row for details"
      actions={
        isExec ? (
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg"
            style={{ background: "#C8900A" }}
          >
            <Plus size={14} /> Add item
          </button>
        ) : undefined
      }
    >
      <div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0">
        <div className="grid grid-cols-3 gap-3 mb-5 shrink-0">
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Catalog items</p>
            <p className="text-xl font-semibold mt-1">{stats.count}</p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">With stock</p>
            <p className="text-xl font-semibold mt-1">{stats.inStock}</p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Total pieces</p>
            <p className="text-xl font-semibold mt-1">{stats.totalOnHand.toLocaleString()}</p>
          </div>
        </div>

        <div className="bg-card rounded-xl border overflow-hidden flex flex-col min-h-0 flex-1">
          <div className="hidden sm:grid sm:grid-cols-[1fr_120px_120px_160px_28px] gap-2 px-4 py-3 border-b bg-secondary/40 text-[10px] uppercase tracking-wide text-muted-foreground font-medium shrink-0">
            <span>Item</span>
            <span>Unit cost</span>
            <span>On hand</span>
            <span>Last stock-in</span>
            <span />
          </div>

          <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain">
            {loading && (
              <div className="py-12 text-center">
                <Loader2 className="inline animate-spin text-muted-foreground" size={18} />
              </div>
            )}

            {!loading && items.length === 0 && (
              <div className="py-12 text-center text-muted-foreground text-sm">
                <Package className="mx-auto mb-2 opacity-40" size={28} />
                No packaging items yet.{isExec ? " Add your first catalog entry above." : ""}
              </div>
            )}

            {!loading && items.map(row => {
              const onHand = Number(row.on_hand ?? 0);
              const hasStock = onHand > 0;
              return (
                <button
                  key={row.id}
                  type="button"
                  onClick={() => setDetailRow(row)}
                  className="w-full text-left border-b last:border-b-0 px-4 py-3.5 hover:bg-secondary/40 transition-colors group"
                >
                  <div className="flex items-center gap-3 sm:grid sm:grid-cols-[1fr_120px_120px_160px_28px] sm:gap-2 sm:items-center">
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm truncate">{row.name}</p>
                      <p className="text-xs text-muted-foreground mt-0.5">{row.unit}</p>
                    </div>
                    <div className="hidden sm:block text-sm">{peso(row.current_price)}</div>
                    <div className="hidden sm:block">
                      <span className={`text-sm font-medium ${hasStock ? "text-foreground" : "text-muted-foreground"}`}>
                        {onHand.toLocaleString()} {row.unit}
                      </span>
                    </div>
                    <div className="hidden sm:block text-xs text-muted-foreground">
                      {row.last_entry_at
                        ? new Date(row.last_entry_at.replace(" ", "T")).toLocaleString()
                        : "—"}
                    </div>
                    <div className="sm:flex sm:justify-end text-muted-foreground group-hover:text-[#C8900A]">
                      <ChevronRight size={16} />
                    </div>
                    <div className="sm:hidden flex gap-3 text-xs text-muted-foreground mt-1 col-span-full">
                      <span>{peso(row.current_price)}/pc</span>
                      <span>·</span>
                      <span>{onHand} {row.unit}</span>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      </div>

      {showCreate && (
        <MaterialCatalogModal
          category="packaging"
          onClose={() => setShowCreate(false)}
          onDone={() => { setShowCreate(false); void refresh(); }}
        />
      )}
      {detailRow && (
        <PackagingMaterialDetailModal
          stock={detailRow}
          isExec={isExec}
          isHr={isHr}
          canStockIn={canStockIn}
          onClose={() => setDetailRow(null)}
          onChanged={() => void refresh()}
        />
      )}
    </PageLayout>
  );
}
