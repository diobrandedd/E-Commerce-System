import { useCallback, useEffect, useMemo, useState } from "react";
import { BookOpen, ChevronRight, Loader2, Package, Plus, Search, ListChecks } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { PackagingInventoryGuideModal } from "@/app/components/inventory/PackagingInventoryGuideModal";
import { runPackagingInventoryTour } from "@/app/components/tour/guides/packagingInventoryTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { PackagingMaterialDetailModal } from "@/app/components/inventory/PackagingMaterialDetailModal";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { listMaterials, type MaterialStockRow } from "@/app/lib/materials";
import { cn } from "@/app/components/ui/utils";

type StockFilter = "all" | "per_unit" | "pool" | "out_of_stock" | "fulfillment";

const FILTER_CHIPS: { id: StockFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "fulfillment", label: "For packing" },
  { id: "per_unit", label: "Per-unit" },
  { id: "pool", label: "Pool" },
  { id: "out_of_stock", label: "Out of stock" },
];

function isFulfillmentPackagingRow(name: string): boolean {
  const n = name.toLowerCase();
  return n.startsWith("bubble wrap roll")
    || n.startsWith("cushion piece")
    || n.startsWith("cushion roll")
    || n.startsWith("courier pouch")
    || n.startsWith("insulation foam roll")
    || n.startsWith("cardboard box");
}

export function PackagingMaterialsPage() {
  const { user, can } = useAuth();
  const isExec = can("packaging.catalog");
  const isHr = can("packaging.request") && !can("packaging.catalog");
  const isPm = can("packaging.deduct") && !can("packaging.catalog");
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [detailRow, setDetailRow] = useState<MaterialStockRow | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);
  const [actionsOpen, setActionsOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<StockFilter>("all");

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
    const outOfStock = items.length - inStock;
    const perUnit = items.filter(i => !i.is_pool_packaging).length;
    const pool = items.filter(i => i.is_pool_packaging).length;
    const perUnitStocked = items.filter(i => !i.is_pool_packaging && Number(i.on_hand) > 0).length;
    const poolStocked = items.filter(i => i.is_pool_packaging && Number(i.on_hand) > 0).length;
    return { count: items.length, inStock, outOfStock, perUnit, pool, perUnitStocked, poolStocked };
  }, [items]);

  const filteredItems = useMemo(() => {
    const q = search.trim().toLowerCase();
    return items.filter(row => {
      if (/^bubble wrap roll - \d+ cm$/i.test(row.name.trim())) return false;
      if (/^insulation foam roll - /i.test(row.name.trim())) return false;
      if (filter === "fulfillment" && !isFulfillmentPackagingRow(row.name)) return false;
      if (filter === "per_unit" && row.is_pool_packaging) return false;
      if (filter === "pool" && !row.is_pool_packaging) return false;
      if (filter === "out_of_stock" && Number(row.on_hand ?? 0) > 0) return false;
      if (q && !row.name.toLowerCase().includes(q) && !row.unit.toLowerCase().includes(q)) return false;
      return true;
    });
  }, [items, search, filter]);

  return (
    <PageLayout
      title="Packaging Materials"
      subtitle="HR stock-in · production deducts for use · executive full control — click a row for details"
      actions={
        <div className="flex items-center gap-2">
          <div className="sm:hidden flex items-center gap-2">
            <button
              type="button"
              onClick={() => setGuideOpen(true)}
              className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-2.5 py-1.5 rounded-lg border border-border"
            >
              <BookOpen size={12} /> Guide
            </button>
            <button
              type="button"
              onClick={() => setActionsOpen(true)}
              className="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-[#C8900A] text-[#8a6506]"
            >
              <ListChecks size={12} /> Actions
            </button>
          </div>
          <div className="hidden sm:flex items-center gap-2">
          <button
            type="button"
            onClick={() => setGuideOpen(true)}
            className="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
          >
            <BookOpen size={13} /> Guide
          </button>
          {isExec && (
            <button
              type="button"
              onClick={() => setShowCreate(true)}
              className="inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg"
              style={{ background: "#C8900A" }}
            >
              <Plus size={14} /> Add item
            </button>
          )}
          </div>
        </div>
      }
    >
      <div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0 gap-4">
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0" data-tour="packaging-stats">
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Catalog items</p>
            <p className="text-xl font-semibold mt-1">{stats.count}</p>
            <p className="text-[10px] text-muted-foreground mt-0.5">
              {stats.perUnit} per-unit · {stats.pool} pool
            </p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">With stock</p>
            <p className="text-xl font-semibold mt-1 text-emerald-700 dark:text-emerald-400">{stats.inStock}</p>
            <p className="text-[10px] text-muted-foreground mt-0.5">
              {stats.perUnitStocked} per-unit · {stats.poolStocked} pool
            </p>
          </div>
          <div className="rounded-xl border bg-card px-4 py-3">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Out of stock</p>
            <p className={cn("text-xl font-semibold mt-1", stats.outOfStock > 0 && "text-amber-700 dark:text-amber-400")}>
              {stats.outOfStock}
            </p>
            <p className="text-[10px] text-muted-foreground mt-0.5">SKUs at zero on-hand</p>
          </div>
          <div className="rounded-xl border border-dashed bg-secondary/20 px-4 py-3 col-span-2 lg:col-span-1">
            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Production impact</p>
            <p className="text-sm font-medium mt-1 leading-snug">
              Per-unit items block production when empty
            </p>
            <p className="text-[10px] text-muted-foreground mt-0.5">Pool rolls are cost-only</p>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center gap-2 shrink-0" data-tour="packaging-filters">
          <div className="relative flex-1 min-w-0">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
              type="search"
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="Search packaging…"
              className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background"
            />
          </div>
          <div className="flex flex-wrap gap-1.5">
            {FILTER_CHIPS.map(chip => (
              <button
                key={chip.id}
                type="button"
                onClick={() => setFilter(chip.id)}
                className={cn(
                  "text-[11px] font-medium px-2.5 py-1.5 rounded-full border transition-colors",
                  filter === chip.id
                    ? "border-[#C8900A]/50 bg-[#C8900A]/10 text-[#8a6507]"
                    : "border-border text-muted-foreground hover:bg-secondary/50",
                )}
              >
                {chip.label}
              </button>
            ))}
          </div>
        </div>

        <div className="bg-card rounded-xl border overflow-hidden flex flex-col min-h-0 flex-1" data-tour="packaging-catalog">
          <div className="hidden sm:grid sm:grid-cols-[1fr_130px_150px_24px] gap-2 px-4 py-2.5 border-b bg-secondary/40 text-[10px] uppercase tracking-wide text-muted-foreground font-medium shrink-0">
            <span>Item</span>
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

            {!loading && items.length > 0 && filteredItems.length === 0 && (
              <div className="py-12 text-center text-muted-foreground text-sm">
                No items match your search or filter.
              </div>
            )}

            {!loading && (
              <div className="sm:hidden divide-y divide-border">
                {filteredItems.map(row => {
                  const onHand = Number(row.on_hand ?? 0);
                  const hasStock = onHand > 0;
                  const isPool = Boolean(row.is_pool_packaging);
                  return (
                    <button
                      key={row.id}
                      type="button"
                      onClick={() => setDetailRow(row)}
                      className={cn(
                        "w-full text-left px-4 py-3 space-y-2 hover:bg-secondary/30 transition-colors",
                        !hasStock && "bg-amber-50/50 dark:bg-amber-950/10",
                      )}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <p className="font-medium text-sm text-foreground leading-snug break-words">{row.name}</p>
                          <p className="text-xs text-muted-foreground mt-0.5">{row.unit}</p>
                        </div>
                        <ChevronRight size={14} className="text-muted-foreground" />
                      </div>
                      <div className="flex flex-wrap gap-1.5">
                        <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground">
                          {isPool ? "Pool" : "Per-unit"}
                        </span>
                        {!hasStock && (
                          <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                            Empty
                          </span>
                        )}
                      </div>
                      <div className="rounded-md bg-secondary/30 px-2 py-1.5 text-xs">
                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground">On hand</p>
                        <p className={cn("mt-0.5 tabular-nums", !hasStock && "text-amber-700 dark:text-amber-400 font-medium")}>
                          {onHand.toLocaleString()} {row.unit}
                        </p>
                      </div>
                    </button>
                  );
                })}
              </div>
            )}

            {!loading && (
              <div className="hidden sm:block">
                {filteredItems.map(row => {
              const onHand = Number(row.on_hand ?? 0);
              const hasStock = onHand > 0;
              const isPool = Boolean(row.is_pool_packaging);
              return (
                <button
                  key={row.id}
                  type="button"
                  onClick={() => setDetailRow(row)}
                  className={cn(
                    "w-full text-left border-b last:border-b-0 px-4 py-2.5 hover:bg-secondary/40 transition-colors group",
                    !hasStock && "bg-amber-50/50 dark:bg-amber-950/10",
                  )}
                >
                  <div className="flex items-center gap-3 sm:grid sm:grid-cols-[1fr_130px_150px_24px] sm:gap-2 sm:items-center">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 min-w-0">
                        <p className="font-medium text-sm truncate">{row.name}</p>
                        {isPool ? (
                          <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground">
                            Pool
                          </span>
                        ) : (
                          <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-[#C8900A]/10 text-[#8a6507]">
                            Per-unit
                          </span>
                        )}
                        {!hasStock && (
                          <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                            Empty
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-muted-foreground mt-0.5">
                        {row.unit}
                        {isPool && row.pool_yield_qty != null && row.pool_yield_unit && (
                          <span className="text-muted-foreground/80">
                            {" "}· ~{Number(row.pool_yield_qty).toLocaleString()} {row.pool_yield_unit.replace(/_/g, " ")}s/{row.unit}
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="hidden sm:block tabular-nums">
                      <span
                        className={cn(
                          "text-sm font-medium",
                          hasStock ? "text-foreground" : "text-amber-700 dark:text-amber-400",
                        )}
                      >
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
                  </div>
                </button>
              );
            })}
              </div>
            )}
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
      {actionsOpen && (
        <PackagingMobileActionsModal
          canCreate={isExec}
          onClose={() => setActionsOpen(false)}
          onAddItem={() => {
            setActionsOpen(false);
            setShowCreate(true);
          }}
        />
      )}
      {detailRow && (
        <PackagingMaterialDetailModal
          stock={detailRow}
          isExec={isExec}
          isHr={isHr}
          isPm={isPm}
          onClose={() => setDetailRow(null)}
          onChanged={() => void refresh()}
        />
      )}
      <PackagingInventoryGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        role={user?.role}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runPackagingInventoryTour(user?.role));
        }}
      />
    </PageLayout>
  );
}

function PackagingMobileActionsModal({
  canCreate,
  onClose,
  onAddItem,
}: {
  canCreate: boolean;
  onClose: () => void;
  onAddItem: () => void;
}) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="packaging-actions-title">
      <AppModalHeader title="Packaging actions" titleId="packaging-actions-title" onClose={onClose} />
      <AppModalBody className="space-y-2">
        {canCreate && (
          <button
            type="button"
            onClick={onAddItem}
            className="w-full text-left px-3 py-2.5 rounded-lg border border-border text-sm font-medium hover:bg-secondary"
          >
            Add catalog item
          </button>
        )}
      </AppModalBody>
      <AppModalFooter>
        <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">
          Close
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
