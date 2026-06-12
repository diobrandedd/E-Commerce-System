import { useCallback, useEffect, useMemo, useState } from "react";
import { BookOpen, ChevronRight, Loader2, Package, Plus, Search } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { SuppliesInventoryGuideModal } from "@/app/components/inventory/SuppliesInventoryGuideModal";
import { SupplyIssueRequestModal } from "@/app/components/inventory/SupplyIssueRequestModal";
import { SupplyMaterialDetailModal } from "@/app/components/inventory/SupplyMaterialDetailModal";
import { useCorrectionNotifications } from "@/app/components/notifications/CorrectionNotificationsContext";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { listMaterials, type MaterialStockRow } from "@/app/lib/materials";
import { cn } from "@/app/components/ui/utils";

const peso = (v: string | number | undefined) => {
  const n = typeof v === "string" ? parseFloat(v) : Number(v ?? 0);
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

type StockFilter = "all" | "in_stock" | "out_of_stock";

const FILTER_CHIPS: { id: StockFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "in_stock", label: "In stock" },
  { id: "out_of_stock", label: "Out of stock" },
];

export function SuppliesMaterialsPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const isHr = user?.role === "hr";
  const canCreateCatalog = isExec || isHr;
  const canIssueRequest = isExec || isHr;
  const { refresh: refreshNotifications } = useCorrectionNotifications();
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [showIssueRequest, setShowIssueRequest] = useState(false);
  const [guideOpen, setGuideOpen] = useState(false);
  const [detailRow, setDetailRow] = useState<MaterialStockRow | null>(null);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<StockFilter>("all");

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await listMaterials("supply");
      setItems(data.items);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const stats = useMemo(() => {
    const inStock = items.filter(i => Number(i.on_hand) > 0).length;
    const outOfStock = items.length - inStock;
    const totalOnHand = items.reduce((sum, i) => sum + Number(i.on_hand ?? 0), 0);
    return { count: items.length, inStock, outOfStock, totalOnHand };
  }, [items]);

  const filteredItems = useMemo(() => {
    const q = search.trim().toLowerCase();
    return items.filter(row => {
      if (filter === "in_stock" && Number(row.on_hand ?? 0) <= 0) return false;
      if (filter === "out_of_stock" && Number(row.on_hand ?? 0) > 0) return false;
      if (q && !row.name.toLowerCase().includes(q) && !row.unit.toLowerCase().includes(q)) return false;
      return true;
    });
  }, [items, search, filter]);

  const handleIssueDone = () => {
    setShowIssueRequest(false);
    void refresh();
    void refreshNotifications();
  };

  return (
    <PageLayout
      title="Supplies"
      subtitle="Org-wide pool — lot stock-in, unit cost, and HR-submitted issues — click a row for details"
      actions={
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setGuideOpen(true)}
            className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
          >
            <BookOpen size={13} /> Guide
          </button>
          {canIssueRequest && (
            <button
              type="button"
              onClick={() => setShowIssueRequest(true)}
              className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg"
              style={{ background: "#C8900A" }}
            >
              <Plus size={14} /> Issue request
            </button>
          )}
          {canCreateCatalog && (
            <button
              type="button"
              onClick={() => setShowCreate(true)}
              className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-2 rounded-lg border border-border hover:bg-secondary/50"
            >
              <Plus size={14} /> Add item
            </button>
          )}
        </div>
      }
    >
      <div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0 gap-4">
        <div className="grid grid-cols-3 gap-3 shrink-0">
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Catalog items</p>
            <p className="text-xl font-semibold mt-1">{stats.count}</p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">With stock</p>
            <p className="text-xl font-semibold mt-1 text-emerald-700 dark:text-emerald-400">{stats.inStock}</p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Total pieces</p>
            <p className="text-xl font-semibold mt-1">{stats.totalOnHand.toLocaleString()}</p>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row gap-2 shrink-0">
          <div className="relative flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="search"
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="Search supplies…"
              className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background"
            />
          </div>
          <div className="flex gap-1.5 flex-wrap">
            {FILTER_CHIPS.map(chip => (
              <button
                key={chip.id}
                type="button"
                onClick={() => setFilter(chip.id)}
                className={cn(
                  "text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors",
                  filter === chip.id
                    ? "bg-[#C8900A]/10 border-[#C8900A]/40 text-[#C8900A]"
                    : "border-border text-muted-foreground hover:bg-secondary/50",
                )}
              >
                {chip.label}
              </button>
            ))}
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

            {!loading && filteredItems.length === 0 && (
              <div className="py-12 text-center text-muted-foreground text-sm">
                <Package className="mx-auto mb-2 opacity-40" size={28} />
                {items.length === 0
                  ? `No supply items yet.${canCreateCatalog ? " Add your first catalog entry above." : ""}`
                  : "No items match your search or filter."}
              </div>
            )}

            {!loading && filteredItems.map(row => {
              const onHand = Number(row.on_hand ?? 0);
              const hasStock = onHand > 0;
              return (
                <button
                  key={row.id}
                  type="button"
                  onClick={() => setDetailRow(row)}
                  className={cn(
                    "w-full text-left border-b last:border-b-0 px-4 py-3.5 hover:bg-secondary/40 transition-colors group",
                    !hasStock && "bg-red-50/30 dark:bg-red-950/10",
                  )}
                >
                  <div className="flex items-center gap-3 sm:grid sm:grid-cols-[1fr_120px_120px_160px_28px] sm:gap-2 sm:items-center">
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-sm truncate">{row.name}</p>
                      <p className="text-xs text-muted-foreground mt-0.5">{row.unit}</p>
                    </div>
                    <div className="hidden sm:block text-sm">{peso(row.current_price)}</div>
                    <div className="hidden sm:block">
                      <span className={cn("text-sm font-medium", hasStock ? "text-foreground" : "text-red-600 dark:text-red-400")}>
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
                      <span className={!hasStock ? "text-red-600" : ""}>{onHand} {row.unit}</span>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      </div>

      <SuppliesInventoryGuideModal open={guideOpen} onClose={() => setGuideOpen(false)} role={user?.role} />

      {showCreate && (
        <MaterialCatalogModal
          category="supply"
          onClose={() => setShowCreate(false)}
          onDone={() => { setShowCreate(false); void refresh(); }}
        />
      )}
      {showIssueRequest && (
        <SupplyIssueRequestModal
          onClose={() => setShowIssueRequest(false)}
          onDone={handleIssueDone}
        />
      )}
      {detailRow && (
        <SupplyMaterialDetailModal
          stock={detailRow}
          onClose={() => setDetailRow(null)}
          onChanged={() => void refresh()}
        />
      )}
    </PageLayout>
  );
}
