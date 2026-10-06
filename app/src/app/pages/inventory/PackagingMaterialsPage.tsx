import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ArrowDown,
  ArrowUp,
  BookOpen,
  ChevronRight,
  Loader2,
  Package,
  Plus,
  Printer,
  ScanLine,
  Search,
  ListChecks,
  Users,
} from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { PackagingInventoryGuideModal } from "@/app/components/inventory/PackagingInventoryGuideModal";
import { PackagingScanStockOutModal } from "@/app/components/inventory/PackagingScanStockOutModal";
import { runPackagingInventoryTour } from "@/app/components/tour/guides/packagingInventoryTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { PackagingMaterialDetailModal } from "@/app/components/inventory/PackagingMaterialDetailModal";
import {
  AppPressable,
  AppSectionEnter,
  appHeaderActionClass,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import {
  fetchPackagingAccessRoster,
  listMaterials,
  packagingPrintSheetUrl,
  type MaterialStockRow,
  type PackagingAccessPerson,
} from "@/app/lib/materials";
import { cn } from "@/app/components/ui/utils";

type StockFilter = "all" | "per_unit" | "pool" | "out_of_stock" | "fulfillment";
type SortKey = "name" | "on_hand" | "level" | "last_stock_in";
type SortDir = "asc" | "desc";

const FILTER_CHIPS: { id: StockFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "fulfillment", label: "For packing" },
  { id: "per_unit", label: "Per-unit" },
  { id: "pool", label: "Pool" },
  { id: "out_of_stock", label: "Out of stock" },
];

const SORT_OPTIONS: { id: SortKey; label: string }[] = [
  { id: "name", label: "Name" },
  { id: "on_hand", label: "On hand" },
  { id: "level", label: "Level" },
  { id: "last_stock_in", label: "Last stock-in" },
];

const SEARCH_INPUT_CLASS =
  "w-full pl-9 pr-3 py-2.5 text-sm rounded-lg border-2 border-border bg-card " +
  "focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-shadow";

function comparePackagingRows(a: MaterialStockRow, b: MaterialStockRow, key: SortKey, dir: SortDir): number {
  const mul = dir === "asc" ? 1 : -1;
  if (key === "name") {
    return mul * a.name.localeCompare(b.name, undefined, { sensitivity: "base" });
  }
  if (key === "on_hand") {
    return mul * (Number(a.on_hand ?? 0) - Number(b.on_hand ?? 0));
  }
  if (key === "level") {
    const av = a.level_pct == null || !Number.isFinite(a.level_pct) ? -1 : Number(a.level_pct);
    const bv = b.level_pct == null || !Number.isFinite(b.level_pct) ? -1 : Number(b.level_pct);
    return mul * (av - bv);
  }
  const at = a.last_entry_at ? Date.parse(a.last_entry_at.replace(" ", "T")) : 0;
  const bt = b.last_entry_at ? Date.parse(b.last_entry_at.replace(" ", "T")) : 0;
  const aOk = Number.isFinite(at) ? at : 0;
  const bOk = Number.isFinite(bt) ? bt : 0;
  return mul * (aOk - bOk);
}

function formatLastStockIn(raw: string | null | undefined): string {
  if (!raw) return "—";
  const d = new Date(raw.replace(" ", "T"));
  if (!Number.isFinite(d.getTime())) return "—";
  return d.toLocaleString(undefined, {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function SortHeader({
  label,
  column,
  sortKey,
  sortDir,
  onSort,
}: {
  label: string;
  column: SortKey;
  sortKey: SortKey;
  sortDir: SortDir;
  onSort: (key: SortKey) => void;
}) {
  const active = sortKey === column;
  return (
    <button
      type="button"
      onClick={() => onSort(column)}
      className={cn(
        "inline-flex items-center gap-1 text-left uppercase tracking-wide font-medium transition-colors min-h-8",
        "hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 rounded-sm",
        active ? "text-foreground" : "text-muted-foreground",
      )}
      aria-sort={active ? (sortDir === "asc" ? "ascending" : "descending") : "none"}
    >
      <span>{label}</span>
      {active ? (
        sortDir === "asc"
          ? <ArrowUp size={11} className="shrink-0 opacity-80" aria-hidden />
          : <ArrowDown size={11} className="shrink-0 opacity-80" aria-hidden />
      ) : (
        <span className="w-[11px] shrink-0" aria-hidden />
      )}
    </button>
  );
}

function isFulfillmentPackagingRow(name: string): boolean {
  const n = name.toLowerCase();
  return n.startsWith("bubble wrap roll")
    || n.startsWith("cushion piece")
    || n.startsWith("cushion roll")
    || n.startsWith("courier pouch")
    || n.startsWith("insulation foam roll")
    || n.startsWith("cardboard box");
}

function LevelCell({ pct, compact = false }: { pct: number | null | undefined; compact?: boolean }) {
  if (pct == null || !Number.isFinite(pct)) {
    return <span className="text-xs text-muted-foreground">—</span>;
  }
  const low = pct <= 25;
  const mid = pct <= 50;
  return (
    <div className={cn("min-w-0", compact ? "space-y-0.5" : "space-y-1")}>
      <p
        className={cn(
          "font-medium tabular-nums",
          compact ? "text-xs" : "text-sm",
          low ? "text-amber-800 dark:text-amber-300" : mid ? "text-primary" : "text-emerald-800 dark:text-emerald-300",
        )}
      >
        {Math.round(pct)}%
      </p>
      <div className={cn("rounded-full bg-secondary overflow-hidden", compact ? "h-1" : "h-1.5")}>
        <div
          className={cn(
            "h-full rounded-full transition-[width] duration-300 ease-out",
            low ? "bg-amber-500" : mid ? "bg-primary" : "bg-emerald-500",
          )}
          style={{ width: `${Math.max(0, Math.min(100, pct))}%` }}
        />
      </div>
    </div>
  );
}

function TypeBadge({ isPool }: { isPool: boolean }) {
  return isPool ? (
    <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground">
      Pool
    </span>
  ) : (
    <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-primary/10 text-primary">
      Per-unit
    </span>
  );
}

export function PackagingMaterialsPage() {
  const { user, can } = useAuth();
  const isExec = can("packaging.catalog");
  const isHr = can("packaging.request") && !can("packaging.catalog");
  const isPm = can("packaging.deduct") && !can("packaging.catalog");
  const canView = can("packaging.view");
  const canDeduct = can("packaging.deduct");
  const scanOnly = canDeduct && !canView;
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [roster, setRoster] = useState<PackagingAccessPerson[]>([]);
  const [stockInNote, setStockInNote] = useState("Only executives can stock in packaging lots.");
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [scanOpen, setScanOpen] = useState(false);
  const [detailRow, setDetailRow] = useState<MaterialStockRow | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);
  const [actionsOpen, setActionsOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<StockFilter>("all");
  const [sortKey, setSortKey] = useState<SortKey>("name");
  const [sortDir, setSortDir] = useState<SortDir>("asc");

  const toggleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir(d => (d === "asc" ? "desc" : "asc"));
      return;
    }
    setSortKey(key);
    setSortDir(key === "name" ? "asc" : "desc");
  };

  const refresh = useCallback(async () => {
    if (scanOnly) {
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const [data, access] = await Promise.all([
        listMaterials("packaging"),
        canView ? fetchPackagingAccessRoster().catch(() => null) : Promise.resolve(null),
      ]);
      setItems(data.items);
      if (access) {
        setRoster(access.stock_out);
        setStockInNote(access.stock_in_note);
      }
    } finally {
      setLoading(false);
    }
  }, [scanOnly, canView]);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  useEffect(() => {
    if (scanOnly) setScanOpen(true);
  }, [scanOnly]);

  const stats = useMemo(() => {
    const inStock = items.filter(i => Number(i.on_hand) > 0).length;
    const outOfStock = items.length - inStock;
    const perUnit = items.filter(i => !i.is_pool_packaging).length;
    const pool = items.filter(i => i.is_pool_packaging).length;
    const perUnitStocked = items.filter(i => !i.is_pool_packaging && Number(i.on_hand) > 0).length;
    const poolStocked = items.filter(i => i.is_pool_packaging && Number(i.on_hand) > 0).length;
    const lowLevel = items.filter(i => {
      const pct = i.level_pct;
      return pct != null && Number.isFinite(pct) && pct > 0 && pct <= 25;
    }).length;
    return { count: items.length, inStock, outOfStock, perUnit, pool, perUnitStocked, poolStocked, lowLevel };
  }, [items]);

  const filteredItems = useMemo(() => {
    const q = search.trim().toLowerCase();
    const rows = items.filter(row => {
      if (/^bubble wrap roll - \d+ cm$/i.test(row.name.trim())) return false;
      if (/^insulation foam roll - /i.test(row.name.trim())) return false;
      if (filter === "fulfillment" && !isFulfillmentPackagingRow(row.name)) return false;
      if (filter === "per_unit" && row.is_pool_packaging) return false;
      if (filter === "pool" && !row.is_pool_packaging) return false;
      if (filter === "out_of_stock" && Number(row.on_hand ?? 0) > 0) return false;
      if (
        q
        && !row.name.toLowerCase().includes(q)
        && !row.unit.toLowerCase().includes(q)
        && !(row.barcode ?? "").toLowerCase().includes(q)
      ) {
        return false;
      }
      return true;
    });
    rows.sort((a, b) => comparePackagingRows(a, b, sortKey, sortDir));
    return rows;
  }, [items, search, filter, sortKey, sortDir]);

  const openPrintSheet = () => {
    window.open(packagingPrintSheetUrl(), "_blank", "noopener,noreferrer");
  };

  if (scanOnly) {
    return (
      <PageLayout
        title="Packaging stock out"
        subtitle="Scan a barcode to deduct packaging from on-hand"
      >
        <AppSectionEnter className="max-w-md mx-auto">
          <div className="rounded-2xl border border-border bg-card p-6 sm:p-8 shadow-sm space-y-5 text-center">
            <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
              <ScanLine size={28} strokeWidth={1.75} aria-hidden />
            </div>
            <div className="space-y-2">
              <h2 className="text-lg font-semibold text-foreground tracking-tight">Ready to stock out</h2>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Point the camera at a packaging label or type the code. Confirm quantity and reason before deducting.
              </p>
            </div>
            <Button type="button" className="w-full min-h-11" onClick={() => setScanOpen(true)}>
              <ScanLine className="size-4" /> Scan packaging barcode
            </Button>
          </div>
        </AppSectionEnter>
        <PackagingScanStockOutModal
          open={scanOpen}
          onClose={() => setScanOpen(false)}
          onDone={() => undefined}
        />
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Packaging Materials"
      subtitle="Warehouse packaging · scan stock-out · level vs last lot"
      actions={
        <div className="flex items-center gap-2">
          <div className="sm:hidden flex items-center gap-2">
            <AppPressable
              type="button"
              onClick={() => setGuideOpen(true)}
              className={appHeaderActionClass}
            >
              <BookOpen size={12} /> Guide
            </AppPressable>
            <AppPressable
              type="button"
              onClick={() => setActionsOpen(true)}
              className="inline-flex items-center gap-1.5 text-xs font-semibold min-h-9 px-3 rounded-lg border border-primary/40 bg-primary/5 text-primary"
            >
              <ListChecks size={13} /> Actions
            </AppPressable>
          </div>
          <div className="hidden sm:flex items-center gap-2 flex-wrap justify-end">
            <AppPressable type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
              <BookOpen size={13} /> Guide
            </AppPressable>
            <Button type="button" variant="outline" size="sm" onClick={openPrintSheet}>
              <Printer className="size-3.5" /> Generate all barcodes
            </Button>
            {canDeduct && (
              <Button type="button" size="sm" onClick={() => setScanOpen(true)}>
                <ScanLine className="size-3.5" /> Scan stock out
              </Button>
            )}
            {isExec && (
              <Button type="button" size="sm" onClick={() => setShowCreate(true)}>
                <Plus className="size-3.5" /> Add item
              </Button>
            )}
          </div>
        </div>
      }
    >
      <div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0 gap-3 sm:gap-4">
        <AppSectionEnter index={0} className="shrink-0">
          <div
            className="rounded-xl border border-border bg-card px-3.5 py-3 sm:px-4 space-y-2.5"
            data-tour="packaging-access"
          >
            <div className="flex items-start gap-2.5">
              <div className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-secondary text-muted-foreground">
                <Users size={15} aria-hidden />
              </div>
              <div className="min-w-0 flex-1 space-y-1">
                <p className="text-sm font-semibold text-foreground">Assigned stock-out staff</p>
                <p className="text-xs text-muted-foreground leading-relaxed">{stockInNote}</p>
              </div>
            </div>
            {roster.length === 0 ? (
              <p className="text-xs text-muted-foreground pl-0 sm:pl-[2.625rem] leading-relaxed">
                Nobody assigned yet. In Staff Management, enable{" "}
                <strong className="text-foreground font-medium">Stock out packaging (scan)</strong>.
              </p>
            ) : (
              <div className="flex flex-wrap gap-1.5 pl-0 sm:pl-[2.625rem]">
                {roster.map(p => (
                  <span
                    key={p.id}
                    className="inline-flex items-center gap-1 rounded-full border border-border bg-secondary/40 px-2.5 py-1 text-xs text-foreground"
                  >
                    <span className="font-medium">{p.name}</span>
                    <span className="text-muted-foreground">· {p.role.replace(/_/g, " ")}</span>
                  </span>
                ))}
              </div>
            )}
          </div>
        </AppSectionEnter>

        <AppSectionEnter index={1} className="shrink-0">
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3" data-tour="packaging-stats">
            <div className="rounded-xl border border-border bg-card px-3 py-2.5 sm:px-4 sm:py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Catalog</p>
              <p className="text-xl font-semibold mt-0.5 tabular-nums text-foreground">{stats.count}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5 leading-snug">
                {stats.perUnit} per-unit · {stats.pool} pool
              </p>
            </div>
            <div className="rounded-xl border border-border bg-card px-3 py-2.5 sm:px-4 sm:py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">In stock</p>
              <p className="text-xl font-semibold mt-0.5 tabular-nums text-emerald-800 dark:text-emerald-300">{stats.inStock}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5 leading-snug">
                {stats.perUnitStocked} per-unit · {stats.poolStocked} pool
              </p>
            </div>
            <div className="rounded-xl border border-border bg-card px-3 py-2.5 sm:px-4 sm:py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Empty</p>
              <p className={cn("text-xl font-semibold mt-0.5 tabular-nums", stats.outOfStock > 0 ? "text-amber-800 dark:text-amber-300" : "text-foreground")}>
                {stats.outOfStock}
              </p>
              <p className="text-[10px] text-muted-foreground mt-0.5">Zero on-hand</p>
            </div>
            <div className="rounded-xl border border-border bg-card px-3 py-2.5 sm:px-4 sm:py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Low level</p>
              <p className={cn("text-xl font-semibold mt-0.5 tabular-nums", stats.lowLevel > 0 ? "text-amber-800 dark:text-amber-300" : "text-foreground")}>
                {stats.lowLevel}
              </p>
              <p className="text-[10px] text-muted-foreground mt-0.5">≤25% of last lot</p>
            </div>
          </div>
        </AppSectionEnter>

        <AppSectionEnter index={2} className="shrink-0">
          <div className="flex flex-col gap-2.5" data-tour="packaging-filters">
            <div className="relative min-w-0">
              <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" aria-hidden />
              <input
                type="search"
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search name or barcode…"
                className={SEARCH_INPUT_CLASS}
                aria-label="Search packaging"
              />
            </div>

            <div className="flex gap-1.5 overflow-x-auto no-pyx-scroll pb-0.5 -mx-0.5 px-0.5 snap-x">
              {FILTER_CHIPS.map(chip => (
                <button
                  key={chip.id}
                  type="button"
                  onClick={() => setFilter(chip.id)}
                  className={cn(
                    "snap-start shrink-0 text-[11px] font-medium min-h-8 px-3 rounded-full border transition-colors",
                    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40",
                    filter === chip.id
                      ? "border-primary/40 bg-primary/10 text-primary"
                      : "border-border text-muted-foreground hover:bg-secondary/60 hover:text-foreground",
                  )}
                >
                  {chip.label}
                </button>
              ))}
            </div>

            <div className="sm:hidden flex items-center gap-2">
              <label className="sr-only" htmlFor="packaging-sort">Sort by</label>
              <select
                id="packaging-sort"
                value={sortKey}
                onChange={e => {
                  const next = e.target.value as SortKey;
                  setSortKey(next);
                  setSortDir(next === "name" ? "asc" : "desc");
                }}
                className="flex-1 min-h-10 rounded-lg border-2 border-border bg-card px-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
              >
                {SORT_OPTIONS.map(opt => (
                  <option key={opt.id} value={opt.id}>Sort: {opt.label}</option>
                ))}
              </select>
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="min-h-10 px-3"
                onClick={() => setSortDir(d => (d === "asc" ? "desc" : "asc"))}
                aria-label={sortDir === "asc" ? "Sort descending" : "Sort ascending"}
              >
                {sortDir === "asc" ? <ArrowUp className="size-4" /> : <ArrowDown className="size-4" />}
              </Button>
            </div>
          </div>
        </AppSectionEnter>

        <div className="bg-card rounded-xl border border-border overflow-hidden flex flex-col min-h-0 flex-1 shadow-sm" data-tour="packaging-catalog">
          <div className="hidden sm:grid sm:grid-cols-[minmax(0,1fr)_7.5rem_5.5rem_9rem_1.5rem] gap-2 px-4 py-2.5 border-b border-border bg-secondary/35 text-[10px] shrink-0">
            <SortHeader label="Item" column="name" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <SortHeader label="On hand" column="on_hand" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <SortHeader label="Level" column="level" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <SortHeader label="Last stock-in" column="last_stock_in" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <span />
          </div>

          <div className="flex-1 min-h-0 overflow-y-auto overscroll-contain">
            {loading && (
              <div className="py-16 text-center space-y-2">
                <Loader2 className="inline animate-spin text-muted-foreground" size={20} />
                <p className="text-xs text-muted-foreground">Loading packaging…</p>
              </div>
            )}

            {!loading && items.length === 0 && (
              <div className="py-16 px-6 text-center space-y-2">
                <Package className="mx-auto text-muted-foreground/50" size={32} strokeWidth={1.5} />
                <p className="text-sm font-medium text-foreground">No packaging items yet</p>
                <p className="text-xs text-muted-foreground">
                  {isExec ? "Use Add item to create the first catalog entry." : "Ask an executive to add catalog items."}
                </p>
              </div>
            )}

            {!loading && items.length > 0 && filteredItems.length === 0 && (
              <div className="py-14 px-6 text-center space-y-1">
                <p className="text-sm font-medium text-foreground">No matches</p>
                <p className="text-xs text-muted-foreground">Try another search or clear the filter.</p>
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
                        "w-full text-left px-4 py-3.5 space-y-2.5 active:bg-secondary/50 transition-colors",
                        appHoverRow,
                        !hasStock && "bg-amber-50/60 dark:bg-amber-950/15",
                      )}
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 space-y-1">
                          <p className="font-medium text-sm text-foreground leading-snug break-words">{row.name}</p>
                          <p className="text-xs text-muted-foreground tabular-nums">
                            {row.unit}
                            {row.barcode ? ` · ${row.barcode}` : ""}
                          </p>
                        </div>
                        <ChevronRight size={16} className="text-muted-foreground mt-0.5 shrink-0" aria-hidden />
                      </div>
                      <div className="flex flex-wrap gap-1.5">
                        <TypeBadge isPool={isPool} />
                        {!hasStock && (
                          <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
                            Empty
                          </span>
                        )}
                      </div>
                      <div className="grid grid-cols-3 gap-2">
                        <div className="rounded-lg bg-secondary/40 px-2.5 py-2">
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground">On hand</p>
                          <p className={cn("mt-0.5 text-sm font-medium tabular-nums", !hasStock && "text-amber-800 dark:text-amber-300")}>
                            {onHand.toLocaleString()}
                            <span className="text-muted-foreground font-normal"> {row.unit}</span>
                          </p>
                        </div>
                        <div className="rounded-lg bg-secondary/40 px-2.5 py-2">
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Level</p>
                          <div className="mt-1">
                            <LevelCell pct={row.level_pct} compact />
                          </div>
                        </div>
                        <div className="rounded-lg bg-secondary/40 px-2.5 py-2">
                          <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Last in</p>
                          <p className="mt-0.5 text-[11px] text-foreground leading-snug">
                            {formatLastStockIn(row.last_entry_at)}
                          </p>
                        </div>
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
                        "w-full text-left border-b border-border last:border-b-0 px-4 py-2.5 group",
                        appHoverRow,
                        !hasStock && "bg-amber-50/50 dark:bg-amber-950/10",
                      )}
                    >
                      <div className="grid grid-cols-[minmax(0,1fr)_7.5rem_5.5rem_9rem_1.5rem] gap-2 items-center">
                        <div className="min-w-0">
                          <div className="flex items-center gap-2 min-w-0">
                            <p className="font-medium text-sm truncate text-foreground">{row.name}</p>
                            <TypeBadge isPool={isPool} />
                            {!hasStock && (
                              <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
                                Empty
                              </span>
                            )}
                          </div>
                          <p className="text-xs text-muted-foreground mt-0.5 tabular-nums">
                            {row.unit}
                            {row.barcode ? ` · ${row.barcode}` : ""}
                            {isPool && row.pool_yield_qty != null && row.pool_yield_unit && (
                              <span>
                                {" "}· ~{Number(row.pool_yield_qty).toLocaleString()} {row.pool_yield_unit.replace(/_/g, " ")}s/{row.unit}
                              </span>
                            )}
                          </p>
                        </div>
                        <div className="tabular-nums">
                          <span
                            className={cn(
                              "text-sm font-medium",
                              hasStock ? "text-foreground" : "text-amber-800 dark:text-amber-300",
                            )}
                          >
                            {onHand.toLocaleString()}
                            <span className="text-muted-foreground font-normal"> {row.unit}</span>
                          </span>
                        </div>
                        <LevelCell pct={row.level_pct} />
                        <div className="text-xs text-muted-foreground leading-snug">
                          {formatLastStockIn(row.last_entry_at)}
                        </div>
                        <div className="flex justify-end text-muted-foreground group-hover:text-primary transition-colors">
                          <ChevronRight size={16} aria-hidden />
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
      {scanOpen && (
        <PackagingScanStockOutModal
          open={scanOpen}
          onClose={() => setScanOpen(false)}
          onDone={() => void refresh()}
        />
      )}
      {actionsOpen && (
        <PackagingMobileActionsModal
          canCreate={isExec}
          canDeduct={canDeduct}
          onClose={() => setActionsOpen(false)}
          onAddItem={() => {
            setActionsOpen(false);
            setShowCreate(true);
          }}
          onScan={() => {
            setActionsOpen(false);
            setScanOpen(true);
          }}
          onPrint={() => {
            setActionsOpen(false);
            openPrintSheet();
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
  canDeduct,
  onClose,
  onAddItem,
  onScan,
  onPrint,
}: {
  canCreate: boolean;
  canDeduct: boolean;
  onClose: () => void;
  onAddItem: () => void;
  onScan: () => void;
  onPrint: () => void;
}) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="packaging-actions-title">
      <AppModalHeader title="Packaging actions" titleId="packaging-actions-title" onClose={onClose} />
      <AppModalBody className="space-y-2">
        <button
          type="button"
          onClick={onPrint}
          className={cn(
            "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-border text-sm font-medium",
            appHoverRow,
          )}
        >
          <Printer size={16} className="text-muted-foreground shrink-0" aria-hidden />
          Generate all barcodes
        </button>
        {canDeduct && (
          <button
            type="button"
            onClick={onScan}
            className={cn(
              "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-primary/30 bg-primary/5 text-sm font-medium text-primary",
              appHoverRow,
            )}
          >
            <ScanLine size={16} className="shrink-0" aria-hidden />
            Scan stock out
          </button>
        )}
        {canCreate && (
          <button
            type="button"
            onClick={onAddItem}
            className={cn(
              "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-border text-sm font-medium",
              appHoverRow,
            )}
          >
            <Plus size={16} className="text-muted-foreground shrink-0" aria-hidden />
            Add catalog item
          </button>
        )}
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose}>
          Close
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
