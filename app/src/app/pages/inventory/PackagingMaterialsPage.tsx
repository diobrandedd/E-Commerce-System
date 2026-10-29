import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  AlertTriangle,
  ArrowDown,
  ArrowUp,
  BookOpen,
  ChevronDown,
  ChevronRight,
  History,
  Loader2,
  Package,
  Plus,
  Printer,
  ScanLine,
  Search,
  ListChecks,
  Tags,
  Users,
} from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { MaterialCatalogModal } from "@/app/components/inventory/MaterialCatalogModal";
import { SupplyCategoriesModal } from "@/app/components/inventory/SupplyCategoriesModal";
import { PackagingDamageConfirmModal } from "@/app/components/inventory/PackagingDamageConfirmModal";
import { PackagingInventoryGuideModal } from "@/app/components/inventory/PackagingInventoryGuideModal";
import { PackagingReportDamageModal } from "@/app/components/inventory/PackagingReportDamageModal";
import { PackagingScanStockOutModal } from "@/app/components/inventory/PackagingScanStockOutModal";
import { PackagingStockOutHistoryModal } from "@/app/components/inventory/PackagingStockOutHistoryModal";
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
  fetchPackagingDamageTotals,
  listMaterials,
  listSupplyCategories,
  packagingPrintSheetUrl,
  type MaterialStockRow,
  type PackagingAccessPerson,
  type SupplyCategory,
} from "@/app/lib/materials";
import { cn } from "@/app/components/ui/utils";

type StockFilter = "all" | "per_unit" | "pool" | "out_of_stock" | "fulfillment";
type SortKey = "name" | "on_hand" | "level" | "last_stock_in";
type SortDir = "asc" | "desc";
type DamagePeriod = "today" | "week" | "custom";

function ymdLocal(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function damagePeriodRange(
  period: DamagePeriod,
  customFrom: string,
  customTo: string,
): { from: string; to: string } {
  const now = new Date();
  const to = ymdLocal(now);
  if (period === "today") {
    return { from: to, to };
  }
  if (period === "week") {
    const start = new Date(now);
    const day = start.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    start.setDate(start.getDate() + mondayOffset);
    return { from: ymdLocal(start), to };
  }
  const from = customFrom || to;
  const end = customTo || to;
  return from <= end ? { from, to: end } : { from: end, to: from };
}

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

const CATALOG_GRID =
  "sm:grid sm:grid-cols-[2rem_minmax(0,1fr)_7rem_5rem_7.5rem_8.5rem_1.5rem] gap-2";

function SelectBox({
  checked,
  indeterminate = false,
  label,
  onChange,
}: {
  checked: boolean;
  indeterminate?: boolean;
  label: string;
  onChange: () => void;
}) {
  const ref = useRef<HTMLInputElement>(null);
  useEffect(() => {
    if (ref.current) ref.current.indeterminate = indeterminate && !checked;
  }, [indeterminate, checked]);
  return (
    <input
      ref={ref}
      type="checkbox"
      checked={checked}
      onChange={onChange}
      onClick={e => e.stopPropagation()}
      onKeyDown={e => e.stopPropagation()}
      aria-label={label}
      className="size-4 rounded border-border accent-primary cursor-pointer"
    />
  );
}

const SEARCH_INPUT_CLASS =
  "w-full min-h-10 sm:min-h-11 pl-9 pr-3 py-2 text-base sm:text-sm rounded-lg border-2 border-border bg-card " +
  "focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-shadow";

function PackagingMobileCatalogRow({
  row,
  damagedQty,
  canDeduct,
  selected,
  onToggleSelect,
  onOpen,
  onReportDamage,
}: {
  row: MaterialStockRow;
  damagedQty: number;
  canDeduct: boolean;
  selected: boolean;
  onToggleSelect: () => void;
  onOpen: () => void;
  onReportDamage: () => void;
}) {
  const onHand = Number(row.on_hand ?? 0);
  const hasStock = onHand > 0;
  const isPool = Boolean(row.is_pool_packaging);
  const pct = row.level_pct;
  const levelLabel = pct != null && Number.isFinite(pct) ? `${Math.round(pct)}%` : "—";

  return (
    <div
      className={cn(
        "flex items-stretch border-b border-border last:border-b-0",
        !hasStock && "bg-amber-50/70 dark:bg-amber-950/20",
        selected && "bg-primary/5",
      )}
    >
      <div className="flex items-center pl-3">
        <SelectBox
          checked={selected}
          label={`Select ${row.name}`}
          onChange={onToggleSelect}
        />
      </div>
      <button
        type="button"
        onClick={onOpen}
        className={cn(
          "min-w-0 flex-1 text-left px-3 py-2.5 space-y-1 transition-colors",
          "active:bg-secondary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/40",
          appHoverRow,
        )}
      >
        <div className="flex items-center gap-2 min-w-0">
          <p className="font-medium text-sm text-foreground truncate leading-snug">{row.name}</p>
          <TypeBadge isPool={isPool} />
          {row.supply_category_name ? (
            <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-foreground truncate max-w-[7rem]">
              {row.supply_category_name}
            </span>
          ) : null}
          {!hasStock && (
            <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">
              Empty
            </span>
          )}
          <ChevronRight size={14} className="text-muted-foreground shrink-0 ml-auto" aria-hidden />
        </div>
        {row.supply_note ? (
          <p className="text-xs text-muted-foreground line-clamp-2">{row.supply_note}</p>
        ) : null}
        <div className="flex items-center gap-2.5 text-xs tabular-nums text-muted-foreground">
          <span className={cn("font-semibold text-foreground", !hasStock && "text-amber-800 dark:text-amber-300")}>
            {onHand.toLocaleString()} {row.unit}
          </span>
          <span aria-hidden>·</span>
          <span>{levelLabel}</span>
          <span aria-hidden>·</span>
          <span className={cn(damagedQty > 0 ? "text-destructive font-medium" : undefined)}>
            {damagedQty.toLocaleString()} dmg
          </span>
        </div>
      </button>
      {canDeduct && (
        <button
          type="button"
          onClick={onReportDamage}
          className={cn(
            "shrink-0 w-11 flex flex-col items-center justify-center gap-0.5 border-l border-border",
            "text-destructive active:bg-destructive/10",
            "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-destructive/40",
          )}
          aria-label={`Report damage for ${row.name}`}
        >
          <AlertTriangle size={15} aria-hidden />
          <span className="text-[9px] font-semibold leading-none">Dmg</span>
        </button>
      )}
    </div>
  );
}

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
  // Assigned stock-out staff (including production managers) scan only.
  // Executives keep the catalog, history, and report-damage actions.
  const scanOnly = canDeduct && !isExec;
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [roster, setRoster] = useState<PackagingAccessPerson[]>([]);
  const [stockInNote, setStockInNote] = useState("Only executives can stock in packaging lots.");
  const [loading, setLoading] = useState(true);
  const [showCreate, setShowCreate] = useState(false);
  const [showCategories, setShowCategories] = useState(false);
  const [scanOpen, setScanOpen] = useState(false);
  const [reportDamageOpen, setReportDamageOpen] = useState(false);
  const [damageRow, setDamageRow] = useState<MaterialStockRow | null>(null);
  const [detailRow, setDetailRow] = useState<MaterialStockRow | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);
  const [actionsOpen, setActionsOpen] = useState(false);
  const [historyOpen, setHistoryOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<StockFilter>("all");
  const [categoryFilter, setCategoryFilter] = useState("");
  const [supplyCategories, setSupplyCategories] = useState<SupplyCategory[]>([]);
  const [sortKey, setSortKey] = useState<SortKey>("name");
  const [sortDir, setSortDir] = useState<SortDir>("asc");
  const [damagePeriod, setDamagePeriod] = useState<DamagePeriod>("today");
  const [damageCustomFrom, setDamageCustomFrom] = useState(() => ymdLocal(new Date()));
  const [damageCustomTo, setDamageCustomTo] = useState(() => ymdLocal(new Date()));
  const [damageTotals, setDamageTotals] = useState<Record<number, number>>({});
  const [rosterExpanded, setRosterExpanded] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<number>>(() => new Set());

  const toggleSort = (key: SortKey) => {
    if (sortKey === key) {
      setSortDir(d => (d === "asc" ? "desc" : "asc"));
      return;
    }
    setSortKey(key);
    setSortDir(key === "name" ? "asc" : "desc");
  };

  const damageRange = useMemo(
    () => damagePeriodRange(damagePeriod, damageCustomFrom, damageCustomTo),
    [damagePeriod, damageCustomFrom, damageCustomTo],
  );

  const refreshDamageTotals = useCallback(async () => {
    if (scanOnly || !canView) {
      setDamageTotals({});
      return;
    }
    try {
      const data = await fetchPackagingDamageTotals(damageRange);
      const map: Record<number, number> = {};
      for (const row of data.totals) {
        map[row.stock_id] = Number(row.qty) || 0;
      }
      setDamageTotals(map);
    } catch {
      setDamageTotals({});
    }
  }, [scanOnly, canView, damageRange]);

  const refresh = useCallback(async () => {
    if (scanOnly) {
      setLoading(false);
      return;
    }
    setLoading(true);
    try {
      const [data, access, categories] = await Promise.all([
        listMaterials("packaging"),
        canView ? fetchPackagingAccessRoster().catch(() => null) : Promise.resolve(null),
        canView ? listSupplyCategories().catch(() => ({ categories: [] as SupplyCategory[] })) : Promise.resolve({ categories: [] as SupplyCategory[] }),
      ]);
      setItems(data.items);
      setSupplyCategories(categories.categories);
      if (access) {
        setRoster(access.stock_out);
        setStockInNote(access.stock_in_note);
      }
      await refreshDamageTotals();
    } finally {
      setLoading(false);
    }
  }, [scanOnly, canView, refreshDamageTotals]);

  useEffect(() => { void refresh(); }, [refresh]);
  useEffect(() => { void refreshDamageTotals(); }, [refreshDamageTotals]);
  useLiveRefresh(refresh);

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
      if (categoryFilter === "none" && row.supply_category_id) return false;
      if (categoryFilter && categoryFilter !== "none" && row.supply_category_id !== Number(categoryFilter)) return false;
      if (
        q
        && !row.name.toLowerCase().includes(q)
        && !row.unit.toLowerCase().includes(q)
        && !(row.barcode ?? "").toLowerCase().includes(q)
        && !(row.supply_note ?? "").toLowerCase().includes(q)
        && !(row.supply_category_name ?? "").toLowerCase().includes(q)
      ) {
        return false;
      }
      return true;
    });
    rows.sort((a, b) => comparePackagingRows(a, b, sortKey, sortDir));
    return rows;
  }, [items, search, filter, categoryFilter, sortKey, sortDir]);

  const openPrintSheet = (ids?: number[]) => {
    window.open(packagingPrintSheetUrl(ids), "_blank", "noopener,noreferrer");
  };

  const toggleSelected = (id: number) => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const visibleIds = useMemo(() => filteredItems.map(row => row.id), [filteredItems]);
  const allVisibleSelected = visibleIds.length > 0 && visibleIds.every(id => selectedIds.has(id));
  const someVisibleSelected = visibleIds.some(id => selectedIds.has(id));

  const toggleVisibleSelection = () => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      if (allVisibleSelected) {
        for (const id of visibleIds) next.delete(id);
      } else {
        for (const id of visibleIds) next.add(id);
      }
      return next;
    });
  };

  if (scanOnly) {
    return (
      <PageLayout
        title="Supplies"
        subtitle="Scan a supply barcode, then enter quantity"
        actions={
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="border-destructive/40 text-destructive hover:bg-destructive/5"
            onClick={() => setReportDamageOpen(true)}
          >
            <AlertTriangle className="size-3.5" /> Report damage
          </Button>
        }
      >
        <AppSectionEnter className="max-w-md mx-auto">
          <div className="rounded-2xl border border-border bg-card p-6 sm:p-8 shadow-sm space-y-5 text-center">
            <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
              <ScanLine size={28} strokeWidth={1.75} aria-hidden />
            </div>
            <div className="space-y-2">
              <h2 className="text-lg font-semibold text-foreground tracking-tight">Scan stock out</h2>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Scan the supply barcode and enter how many you are taking for floor use.
              </p>
            </div>
            <Button type="button" className="w-full min-h-12 text-base" onClick={() => setScanOpen(true)}>
              <ScanLine className="size-4" /> Scan stock out
            </Button>
          </div>
        </AppSectionEnter>
        <PackagingScanStockOutModal
          open={scanOpen}
          onClose={() => setScanOpen(false)}
          onDone={() => undefined}
          persistOpen
        />
        <PackagingReportDamageModal
          open={reportDamageOpen}
          onClose={() => setReportDamageOpen(false)}
          onDone={() => undefined}
          persistOpen
        />
      </PageLayout>
    );
  }

  return (
    <PageLayout
      title="Supplies"
      subtitle="Warehouse supplies · scan stock-out · report damage · level vs last lot"
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
            {canView && (
              <AppPressable
                type="button"
                onClick={() => setHistoryOpen(true)}
                className={appHeaderActionClass}
              >
                <History size={13} /> History
              </AppPressable>
            )}
            <Button type="button" variant="outline" size="sm" onClick={() => openPrintSheet()}>
              <Printer className="size-3.5" /> Generate all barcodes
            </Button>
            {selectedIds.size > 0 && (
              <Button type="button" size="sm" onClick={() => openPrintSheet([...selectedIds])}>
                <Printer className="size-3.5" /> Print selected ({selectedIds.size})
              </Button>
            )}
            {canDeduct && (
              <>
                <Button type="button" size="sm" onClick={() => setScanOpen(true)}>
                  <ScanLine className="size-3.5" /> Scan stock out
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  className="border-destructive/40 text-destructive hover:bg-destructive/5"
                  onClick={() => setReportDamageOpen(true)}
                >
                  <AlertTriangle className="size-3.5" /> Report damage
                </Button>
              </>
            )}
            {isExec && (
              <>
                <Button type="button" size="sm" variant="outline" onClick={() => setShowCategories(true)}>
                  <Tags className="size-3.5" /> Categories
                </Button>
                <Button type="button" size="sm" onClick={() => setShowCreate(true)}>
                  <Plus className="size-3.5" /> Add item
                </Button>
              </>
            )}
          </div>
        </div>
      }
    >
      <div className="flex flex-col h-[calc(100dvh-8rem)] min-h-[18rem] min-w-0 gap-2 sm:gap-4">
        <AppSectionEnter index={0} className="shrink-0 hidden sm:block">
          <div
            className="rounded-xl border border-border bg-card overflow-hidden"
            data-tour="packaging-access"
          >
            <button
              type="button"
              className={cn(
                "w-full flex items-center gap-2.5 px-3.5 py-3 sm:px-4 text-left sm:cursor-default",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/40",
                "sm:pointer-events-none",
              )}
              onClick={() => setRosterExpanded(v => !v)}
              aria-expanded={rosterExpanded}
              aria-controls="packaging-roster-body"
            >
              <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-secondary text-muted-foreground">
                <Users size={15} aria-hidden />
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-foreground">Assigned stock-out staff</p>
                <p className="text-xs text-muted-foreground mt-0.5 truncate sm:whitespace-normal sm:leading-relaxed">
                  {roster.length === 0
                    ? "Nobody assigned yet"
                    : `${roster.length} can scan stock out or report damage`}
                  <span className="hidden sm:inline"> · {stockInNote}</span>
                </p>
              </div>
              <ChevronDown
                size={16}
                className={cn(
                  "text-muted-foreground shrink-0 transition-transform sm:hidden",
                  rosterExpanded && "rotate-180",
                )}
                aria-hidden
              />
            </button>
            <div
              id="packaging-roster-body"
              className={cn(
                "px-3.5 pb-3 sm:px-4 sm:pb-3.5 space-y-2",
                !rosterExpanded && "hidden sm:block",
              )}
            >
              <p className="text-xs text-muted-foreground leading-relaxed sm:hidden pl-[2.625rem]">
                {stockInNote}
              </p>
              {roster.length === 0 ? (
                <p className="text-xs text-muted-foreground pl-[2.625rem] leading-relaxed">
                  In Staff Management, enable{" "}
                  <strong className="text-foreground font-medium">Stock out packaging (scan)</strong>.
                </p>
              ) : (
                <div className="flex flex-wrap gap-1.5 pl-[2.625rem]">
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
          </div>
        </AppSectionEnter>

        <AppSectionEnter index={1} className="shrink-0 hidden sm:block">
          <div data-tour="packaging-stats" className="grid grid-cols-4 gap-3">
            <div className="rounded-xl border border-border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Catalog</p>
              <p className="text-xl font-semibold mt-0.5 tabular-nums text-foreground">{stats.count}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5 leading-snug">
                {stats.perUnit} per-unit · {stats.pool} pool
              </p>
            </div>
            <div className="rounded-xl border border-border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">In stock</p>
              <p className="text-xl font-semibold mt-0.5 tabular-nums text-emerald-800 dark:text-emerald-300">{stats.inStock}</p>
              <p className="text-[10px] text-muted-foreground mt-0.5 leading-snug">
                {stats.perUnitStocked} per-unit · {stats.poolStocked} pool
              </p>
            </div>
            <div className="rounded-xl border border-border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Empty</p>
              <p className={cn("text-xl font-semibold mt-0.5 tabular-nums", stats.outOfStock > 0 ? "text-amber-800 dark:text-amber-300" : "text-foreground")}>
                {stats.outOfStock}
              </p>
              <p className="text-[10px] text-muted-foreground mt-0.5">Zero on-hand</p>
            </div>
            <div className="rounded-xl border border-border bg-card px-4 py-3">
              <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Low level</p>
              <p className={cn("text-xl font-semibold mt-0.5 tabular-nums", stats.lowLevel > 0 ? "text-amber-800 dark:text-amber-300" : "text-foreground")}>
                {stats.lowLevel}
              </p>
              <p className="text-[10px] text-muted-foreground mt-0.5">≤25% of last lot</p>
            </div>
          </div>
        </AppSectionEnter>

        <AppSectionEnter index={2} className="shrink-0">
          <div className="flex flex-col gap-1.5 sm:gap-2.5" data-tour="packaging-filters">
            <div className="relative min-w-0">
              <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" aria-hidden />
              <input
                type="search"
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search name, barcode, or note…"
                className={SEARCH_INPUT_CLASS}
                aria-label="Search packaging"
              />
            </div>

            <div className="flex items-center gap-2">
            <div className="flex gap-1.5 overflow-x-auto no-pyx-scroll pb-0.5 -mx-0.5 px-0.5 snap-x min-w-0 flex-1">
              {FILTER_CHIPS.map(chip => (
                <button
                  key={chip.id}
                  type="button"
                  onClick={() => setFilter(chip.id)}
                  className={cn(
                    "snap-start shrink-0 text-[11px] sm:text-xs font-medium min-h-8 sm:min-h-9 px-2.5 sm:px-3.5 rounded-full border transition-colors",
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
            <select
              value={categoryFilter}
              onChange={e => setCategoryFilter(e.target.value)}
              aria-label="Filter by category"
              className="shrink-0 min-h-8 sm:min-h-9 max-w-[11rem] rounded-lg border border-border bg-card px-2.5 text-[11px] sm:text-xs outline-none focus:border-primary focus:ring-4 focus:ring-primary/10"
            >
              <option value="">All categories</option>
              <option value="none">Uncategorized</option>
              {supplyCategories.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            </div>

            <div
              className="flex flex-row items-center gap-1.5 flex-wrap"
              data-tour="packaging-damage-period"
            >
              <p className="hidden sm:inline text-[11px] text-muted-foreground shrink-0">
                Damaged column
              </p>
              <div className="flex gap-1 flex-1 min-w-0 overflow-x-auto no-pyx-scroll" role="group" aria-label="Damaged column period">
                {(
                  [
                    { id: "today" as const, label: "Dmg today" },
                    { id: "week" as const, label: "Dmg week" },
                    { id: "custom" as const, label: "Dmg custom" },
                  ] as const
                ).map(chip => (
                  <button
                    key={chip.id}
                    type="button"
                    onClick={() => setDamagePeriod(chip.id)}
                    className={cn(
                      "shrink-0 text-[11px] font-medium min-h-7 sm:min-h-8 px-2 rounded-full border transition-colors",
                      "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40",
                      damagePeriod === chip.id
                        ? "border-destructive/35 bg-destructive/10 text-destructive"
                        : "border-border text-muted-foreground hover:bg-secondary/60 hover:text-foreground",
                    )}
                  >
                    <span className="sm:hidden">{chip.label}</span>
                    <span className="hidden sm:inline">
                      {chip.id === "today" ? "Today" : chip.id === "week" ? "This week" : "Custom"}
                    </span>
                  </button>
                ))}
              </div>
              {damagePeriod === "custom" && (
                <div className="flex items-center gap-1.5 w-full sm:w-auto">
                  <input
                    type="date"
                    value={damageCustomFrom}
                    onChange={e => setDamageCustomFrom(e.target.value)}
                    className="min-h-8 flex-1 sm:flex-none rounded-lg border border-border bg-card px-2 text-xs outline-none focus:border-primary"
                    aria-label="Damage from date"
                  />
                  <span className="text-xs text-muted-foreground shrink-0">–</span>
                  <input
                    type="date"
                    value={damageCustomTo}
                    onChange={e => setDamageCustomTo(e.target.value)}
                    className="min-h-8 flex-1 sm:flex-none rounded-lg border border-border bg-card px-2 text-xs outline-none focus:border-primary"
                    aria-label="Damage to date"
                  />
                </div>
              )}
            </div>

            <p className="sm:hidden text-[11px] text-muted-foreground tabular-nums px-0.5">
              {stats.inStock} in stock · {stats.outOfStock} empty
              {stats.lowLevel > 0 ? ` · ${stats.lowLevel} low` : ""}
              <span className="text-muted-foreground/80"> · sort in Actions</span>
            </p>
          </div>
        </AppSectionEnter>

        <div className="bg-card rounded-xl border border-border overflow-hidden flex flex-col min-h-0 flex-1 shadow-sm" data-tour="packaging-catalog">
          {selectedIds.size > 0 && (
            <div className="shrink-0 flex flex-wrap items-center gap-2 px-3 py-2 border-b border-border bg-primary/5">
              <p className="text-sm font-medium text-foreground">{selectedIds.size} selected</p>
              <p className="text-xs text-muted-foreground">Selection stays when you change category or search.</p>
              <Button type="button" size="sm" className="sm:ml-auto" onClick={() => openPrintSheet([...selectedIds])}>
                <Printer className="size-3.5" /> Print barcodes
              </Button>
              <Button type="button" size="sm" variant="ghost" onClick={() => setSelectedIds(new Set())}>
                Clear
              </Button>
            </div>
          )}
          <div className={cn("hidden px-4 py-2.5 border-b border-border bg-secondary/35 text-[10px] shrink-0 items-center", CATALOG_GRID)}>
            <SelectBox
              checked={allVisibleSelected}
              indeterminate={someVisibleSelected}
              label={allVisibleSelected ? "Clear visible supplies" : "Select visible supplies"}
              onChange={toggleVisibleSelection}
            />
            <SortHeader label="Item" column="name" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <SortHeader label="On hand" column="on_hand" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <SortHeader label="Level" column="level" sortKey={sortKey} sortDir={sortDir} onSort={toggleSort} />
            <span className="uppercase tracking-wide font-medium text-muted-foreground self-center">Damaged</span>
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
              <div className="sm:hidden">
                {filteredItems.map(row => (
                  <PackagingMobileCatalogRow
                    key={row.id}
                    row={row}
                    damagedQty={damageTotals[row.id] ?? 0}
                    canDeduct={canDeduct}
                    selected={selectedIds.has(row.id)}
                    onToggleSelect={() => toggleSelected(row.id)}
                    onOpen={() => setDetailRow(row)}
                    onReportDamage={() => setDamageRow(row)}
                  />
                ))}
              </div>
            )}

            {!loading && (
              <div className="hidden sm:block">
                {filteredItems.map(row => {
                  const onHand = Number(row.on_hand ?? 0);
                  const hasStock = onHand > 0;
                  const isPool = Boolean(row.is_pool_packaging);
                  const damagedQty = damageTotals[row.id] ?? 0;
                  return (
                    <div
                      key={row.id}
                      role="button"
                      tabIndex={0}
                      aria-label={`Open ${row.name}`}
                      onClick={() => setDetailRow(row)}
                      onKeyDown={e => {
                        if (e.key === "Enter" || e.key === " ") {
                          e.preventDefault();
                          setDetailRow(row);
                        }
                      }}
                      className={cn(
                        "w-full text-left border-b border-border last:border-b-0 px-4 py-2.5 group cursor-pointer",
                        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/40",
                        appHoverRow,
                        !hasStock && "bg-amber-50/50 dark:bg-amber-950/10",
                        selectedIds.has(row.id) && "bg-primary/5",
                      )}
                    >
                      <div className={cn(CATALOG_GRID, "items-center")}>
                        <SelectBox
                          checked={selectedIds.has(row.id)}
                          label={`Select ${row.name}`}
                          onChange={() => toggleSelected(row.id)}
                        />
                        <div className="min-w-0">
                          <div className="flex items-center gap-2 min-w-0">
                            <p className="font-medium text-sm truncate text-foreground">{row.name}</p>
                            <TypeBadge isPool={isPool} />
                            {row.supply_category_name ? (
                              <span className="shrink-0 text-[10px] px-1.5 py-0.5 rounded-full bg-secondary text-foreground">
                                {row.supply_category_name}
                              </span>
                            ) : null}
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
                          {row.supply_note ? (
                            <p className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{row.supply_note}</p>
                          ) : null}
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
                        <div
                          className="min-w-0 space-y-1"
                          onClick={e => e.stopPropagation()}
                          onKeyDown={e => e.stopPropagation()}
                        >
                          <p className={cn(
                            "text-xs font-medium tabular-nums",
                            damagedQty > 0 ? "text-destructive" : "text-muted-foreground",
                          )}>
                            {damagedQty.toLocaleString()} {row.unit}
                          </p>
                          {canDeduct && (
                            <Button
                              type="button"
                              size="sm"
                              variant="outline"
                              className="h-7 px-2 text-[11px] border-destructive/35 text-destructive"
                              onClick={e => {
                                e.stopPropagation();
                                setDamageRow(row);
                              }}
                            >
                              <AlertTriangle className="size-3" /> Damaged
                            </Button>
                          )}
                        </div>
                        <div className="text-xs text-muted-foreground leading-snug">
                          {formatLastStockIn(row.last_entry_at)}
                        </div>
                        <div className="flex justify-end text-muted-foreground group-hover:text-primary transition-colors">
                          <ChevronRight size={16} aria-hidden />
                        </div>
                      </div>
                    </div>
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
      {showCategories && (
        <SupplyCategoriesModal
          items={items}
          onClose={() => setShowCategories(false)}
          onDone={() => void refresh()}
        />
      )}
      {scanOpen && (
        <PackagingScanStockOutModal
          open={scanOpen}
          onClose={() => setScanOpen(false)}
          onDone={() => void refresh()}
        />
      )}
      {reportDamageOpen && (
        <PackagingReportDamageModal
          open={reportDamageOpen}
          onClose={() => setReportDamageOpen(false)}
          onDone={() => void refresh()}
        />
      )}
      {damageRow && (
        <PackagingDamageConfirmModal
          open
          stock={damageRow}
          onClose={() => setDamageRow(null)}
          onDone={() => {
            setDamageRow(null);
            void refresh();
          }}
        />
      )}
      {actionsOpen && (
        <PackagingMobileActionsModal
          canCreate={isExec}
          canDeduct={canDeduct}
          canViewHistory={canView}
          roster={roster}
          stockInNote={stockInNote}
          sortKey={sortKey}
          sortDir={sortDir}
          onClose={() => setActionsOpen(false)}
          onAddItem={() => {
            setActionsOpen(false);
            setShowCreate(true);
          }}
          onCategories={() => {
            setActionsOpen(false);
            setShowCategories(true);
          }}
          onScan={() => {
            setActionsOpen(false);
            setScanOpen(true);
          }}
          onReportDamage={() => {
            setActionsOpen(false);
            setReportDamageOpen(true);
          }}
          onPrint={() => {
            setActionsOpen(false);
            openPrintSheet();
          }}
          selectedCount={selectedIds.size}
          onPrintSelected={() => {
            setActionsOpen(false);
            openPrintSheet([...selectedIds]);
          }}
          onHistory={() => {
            setActionsOpen(false);
            setHistoryOpen(true);
          }}
          onSortKey={key => {
            setSortKey(key);
            setSortDir(key === "name" ? "asc" : "desc");
          }}
          onToggleSortDir={() => setSortDir(d => (d === "asc" ? "desc" : "asc"))}
        />
      )}
      {historyOpen && canView && (
        <PackagingStockOutHistoryModal
          open={historyOpen}
          onClose={() => setHistoryOpen(false)}
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
  canViewHistory,
  roster,
  stockInNote,
  sortKey,
  sortDir,
  onClose,
  onAddItem,
  onCategories,
  onScan,
  onReportDamage,
  onPrint,
  selectedCount,
  onPrintSelected,
  onHistory,
  onSortKey,
  onToggleSortDir,
}: {
  canCreate: boolean;
  canDeduct: boolean;
  canViewHistory: boolean;
  roster: PackagingAccessPerson[];
  stockInNote: string;
  sortKey: SortKey;
  sortDir: SortDir;
  onClose: () => void;
  onAddItem: () => void;
  onCategories: () => void;
  onScan: () => void;
  onReportDamage: () => void;
  onPrint: () => void;
  selectedCount: number;
  onPrintSelected: () => void;
  onHistory: () => void;
  onSortKey: (key: SortKey) => void;
  onToggleSortDir: () => void;
}) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-sm w-full" labelledBy="packaging-actions-title">
      <AppModalHeader title="Supplies actions" titleId="packaging-actions-title" onClose={onClose} />
      <AppModalBody className="space-y-3">
        <div className="rounded-xl border border-border bg-secondary/20 px-3 py-2.5 space-y-1.5">
          <div className="flex items-center gap-2 text-xs font-semibold text-foreground">
            <Users size={14} className="text-muted-foreground" aria-hidden />
            Stock-out staff ({roster.length})
          </div>
          <p className="text-[11px] text-muted-foreground leading-relaxed">{stockInNote}</p>
          {roster.length > 0 && (
            <p className="text-[11px] text-foreground leading-snug">
              {roster.map(p => p.name).join(" · ")}
            </p>
          )}
        </div>

        <div className="flex items-center gap-2">
          <label className="sr-only" htmlFor="packaging-actions-sort">Sort by</label>
          <select
            id="packaging-actions-sort"
            value={sortKey}
            onChange={e => onSortKey(e.target.value as SortKey)}
            className="flex-1 min-h-10 rounded-lg border border-border bg-card px-3 text-sm outline-none focus:border-primary"
          >
            {SORT_OPTIONS.map(opt => (
              <option key={opt.id} value={opt.id}>Sort: {opt.label}</option>
            ))}
          </select>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="min-h-10 min-w-10 px-3"
            onClick={onToggleSortDir}
            aria-label={sortDir === "asc" ? "Sort descending" : "Sort ascending"}
          >
            {sortDir === "asc" ? <ArrowUp className="size-4" /> : <ArrowDown className="size-4" />}
          </Button>
        </div>

        <div className="space-y-2">
          {canViewHistory && (
            <button
              type="button"
              onClick={onHistory}
              className={cn(
                "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-border text-sm font-medium",
                appHoverRow,
              )}
            >
              <History size={16} className="text-muted-foreground shrink-0" aria-hidden />
              Stock-out history
            </button>
          )}
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
          {selectedCount > 0 && (
            <button
              type="button"
              onClick={onPrintSelected}
              className={cn(
                "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-primary/30 bg-primary/5 text-sm font-medium text-primary",
                appHoverRow,
              )}
            >
              <Printer size={16} className="shrink-0" aria-hidden />
              Print selected ({selectedCount})
            </button>
          )}
          {canDeduct && (
            <>
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
              <button
                type="button"
                onClick={onReportDamage}
                className={cn(
                  "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-destructive/30 bg-destructive/5 text-sm font-medium text-destructive",
                  appHoverRow,
                )}
              >
                <AlertTriangle size={16} className="shrink-0" aria-hidden />
                Report damage
              </button>
            </>
          )}
          {canCreate && (
            <>
              <button
                type="button"
                onClick={onCategories}
                className={cn(
                  "w-full flex items-center gap-3 text-left px-3.5 py-3 rounded-xl border border-border text-sm font-medium",
                  appHoverRow,
                )}
              >
                <Tags size={16} className="text-muted-foreground shrink-0" aria-hidden />
                Categories
              </button>
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
            </>
          )}
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose}>
          Close
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
