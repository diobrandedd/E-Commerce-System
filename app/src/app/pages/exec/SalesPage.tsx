import { Fragment, useCallback, useEffect, useMemo, useState } from "react";
import { ChevronDown, ChevronRight, Download, Loader2, Package, Printer, Search, X } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyVariantPieGrid, type VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import { AppSectionEnter, AppErrorBanner, appHoverCard, appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiDownload, apiGet, ApiError } from "@/app/lib/api";
import { peso, variantLabel } from "@/app/lib/posHoney";
import { printPosThermalReceipt, type PosThermalReceipt } from "@/app/lib/posThermalPrint";
import pyxLogo from "@/imports/pyxfoodpr.png";

type PeriodFilter = "all" | "day" | "date" | "week" | "month" | "year" | "custom";
type SaleTypeFilter = "all" | "regular" | "discount" | "ar";
type ChannelFilter = "all" | "walk_in" | "online";
type ProductFilter = "all" | "honey" | "pt" | "brc" | "jar_cacao" | "honey_comb";

interface CheckoutBarcode {
  code: string;
  returned?: boolean;
}

interface CheckoutItem {
  variant: string;
  size: string;
  size_label: string;
  label?: string;
  qty: number;
  list_unit_price?: number;
  unit_price?: number;
  line_subtotal?: number;
  line_total?: number;
  name?: string;
  barcodes?: CheckoutBarcode[];
}

interface CheckoutRow {
  sale_id: number;
  receipt_no: string;
  sold_at: string;
  subtotal_amount?: number;
  discount_percent?: number | null;
  discount_amount?: number;
  total_amount?: number;
  buyer_name: string;
  buyer_credentials?: string | null;
  sold_by_name?: string | null;
  is_ar?: boolean;
  item_count: number;
  items: CheckoutItem[];
  returned_units?: { code: string; kind?: string; match_key?: string }[];
}

interface CheckoutsPage {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
  rows: CheckoutRow[];
}

interface DeptProductUnits {
  key: string;
  label: string;
  unit: string;
  walk_in: number;
  shipped: number;
  total: number;
}

interface SalesSnapshot {
  filter: { period: string; from: string | null; to: string | null; label: string; receipt?: string | null; sale_type?: SaleTypeFilter; channel?: ChannelFilter; product?: ProductFilter };
  total_bottles_sold: number;
  total_units_sold?: number;
  honey?: { label: string; unit: string; walk_in: number; shipped: number; total: number };
  dept?: {
    products: DeptProductUnits[];
    walk_in: number;
    shipped: number;
    total: number;
  };
  rts?: {
    walk_in: number;
    shipped: number;
    total: number;
  };
  channel_share?: {
    walk_in: number;
    online: number;
    total: number;
    walk_in_pct: number;
    online_pct: number;
  };
  summary?: {
    total_bottles_regular: number;
    total_bottles_discounted: number;
    revenue_regular?: number;
    revenue_discounted?: number;
    discounts_given?: number;
    revenue_ar?: number;
  };
  variants_all: VariantChart[];
  variants_regular: VariantChart[];
  variants_discount_10: VariantChart[];
  variants_discount_20: VariantChart[];
  checkouts: CheckoutsPage;
  can_view_amounts?: boolean;
}

const PERIOD_OPTIONS: { value: PeriodFilter; label: string }[] = [
  { value: "day", label: "Today" },
  { value: "date", label: "Pick date" },
  { value: "custom", label: "Custom range" },
  { value: "week", label: "This week" },
  { value: "month", label: "This month" },
  { value: "year", label: "This year" },
  { value: "all", label: "All time" },
];

function localToday(): string {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function itemLabel(item: CheckoutItem): string {
  if (item.variant) return `${variantLabel(item.variant)} ${item.size_label}`.trim();
  return item.label ?? item.name ?? "Item";
}

function itemSummary(items: CheckoutItem[]): string {
  if (items.length === 0) return "—";
  if (items.length === 1) {
    const i = items[0];
    return `${itemLabel(i)} ×${fmtQty(i.qty)}`;
  }
  return `${items.length} line items`;
}

/** Rebuild thermal receipt from stored sale lines (no PDF on disk). */
function checkoutToThermalReceipt(row: CheckoutRow): PosThermalReceipt {
  return {
    receipt_no: row.receipt_no,
    total: row.total_amount ?? 0,
    buyer_name: row.buyer_name?.trim() || "Walk-in",
    seller_name: row.sold_by_name?.trim() || "POS",
    subtotal: row.subtotal_amount,
    discount_percent: row.discount_percent ?? null,
    discount_amount: row.discount_amount,
    items: row.items.map(it => ({
      product_id: 0,
      name: it.name || it.label || "Item",
      variant: it.variant || undefined,
      size: it.size_label || it.size || undefined,
      qty: it.qty,
      list_unit_price: it.list_unit_price,
      unit_price: it.unit_price ?? 0,
      line_subtotal: it.line_subtotal,
      line_total: it.line_total ?? 0,
    })),
  };
}

export function SalesPage() {
  const [period, setPeriod] = useState<PeriodFilter>("day");
  const [saleType, setSaleType] = useState<SaleTypeFilter>("all");
  const [channel, setChannel] = useState<ChannelFilter>("all");
  const [productFilter, setProductFilter] = useState<ProductFilter>("all");
  const [from, setFrom] = useState(localToday());
  const [to, setTo] = useState(localToday());
  const [pickDate, setPickDate] = useState(localToday());
  const [page, setPage] = useState(1);
  const [receipt, setReceipt] = useState("");
  const [appliedReceipt, setAppliedReceipt] = useState("");
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [data, setData] = useState<SalesSnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (opts?: { receipt?: string; page?: number; saleType?: SaleTypeFilter; product?: ProductFilter }) => {
      const receiptQ = opts?.receipt !== undefined ? opts.receipt : appliedReceipt;
      const pageNum = opts?.page ?? page;
      const saleTypeQ = opts?.saleType ?? saleType;
      const productQ = opts?.product ?? productFilter;

      setLoading(true);
      setError(null);
      try {
        const params: Record<string, string> = { page: String(pageNum), sale_type: saleTypeQ, channel, product: productQ };
        if (period === "custom") {
          if (!from || !to) {
            setError("Choose both start and end dates for a custom range.");
            setLoading(false);
            return;
          }
          params.period = "custom";
          params.from = from;
          params.to = to;
        } else if (period === "date") {
          if (!pickDate) {
            setError("Choose a date.");
            setLoading(false);
            return;
          }
          params.period = "custom";
          params.from = pickDate;
          params.to = pickDate;
        } else {
          params.period = period;
        }
        if (receiptQ.trim()) {
          params.receipt = receiptQ.trim();
        }
        const payload = await apiGet<SalesSnapshot>("sales.php", "honey_sold", params);
        setData(payload);
        if (receiptQ.trim() && payload.checkouts.rows.length === 1) {
          setExpandedId(payload.checkouts.rows[0].sale_id);
        } else {
          setExpandedId(null);
        }
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load sales data.");
      } finally {
        setLoading(false);
      }
    },
    [period, from, to, pickDate, page, appliedReceipt, saleType, channel, productFilter],
  );

  useEffect(() => {
    if ((period === "custom" && (!from || !to)) || (period === "date" && !pickDate)) return;
    void load();
  }, [load, period, from, to, pickDate, page, appliedReceipt]);

  useEffect(() => {
    setPage(1);
  }, [period, from, to, pickDate, appliedReceipt, saleType, channel, productFilter]);

  const applyFilters = () => {
    const next = receipt.trim();
    setAppliedReceipt(next);
    setPage(1);
    void load({ receipt: next, page: 1 });
  };

  const clearReceiptSearch = () => {
    setReceipt("");
    setAppliedReceipt("");
    setPage(1);
    void load({ receipt: "", page: 1 });
  };

  const buildFilterParams = useCallback((): Record<string, string> => {
    const params: Record<string, string> = {
      sale_type: saleType,
      channel,
      product: productFilter,
    };
    if (period === "custom") {
      params.period = "custom";
      params.from = from;
      params.to = to;
    } else if (period === "date") {
      params.period = "custom";
      params.from = pickDate;
      params.to = pickDate;
    } else {
      params.period = period;
    }
    if (appliedReceipt.trim()) {
      params.receipt = appliedReceipt.trim();
    }
    return params;
  }, [period, from, to, pickDate, saleType, channel, productFilter, appliedReceipt]);

  const exportExcel = async () => {
    if (period === "custom" && (!from || !to)) {
      setError("Choose both start and end dates for a custom range.");
      return;
    }
    if (period === "date" && !pickDate) {
      setError("Choose a date.");
      return;
    }
    setExporting(true);
    setError(null);
    try {
      await apiDownload("sales.php", "honey_sold_export", buildFilterParams(), "sales-export.xlsx");
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not export sales.");
    } finally {
      setExporting(false);
    }
  };

  useLiveRefresh(load);

  const checkouts = data?.checkouts;
  const canPrev = (checkouts?.page ?? 1) > 1;
  const canNext = checkouts ? checkouts.page < checkouts.total_pages : false;

  const toggleExpand = (saleId: number) => {
    setExpandedId(prev => (prev === saleId ? null : saleId));
  };

  const productCards = useMemo<DeptProductUnits[]>(() => {
    const honey = data?.honey;
    const cards: DeptProductUnits[] = honey
      ? [{ key: "honey", label: honey.label, unit: honey.unit, walk_in: honey.walk_in, shipped: honey.shipped, total: honey.total }]
      : [];
    return cards.concat(data?.dept?.products ?? []);
  }, [data]);

  const summary = useMemo(() => data?.summary ?? {
    total_bottles_regular: 0,
    total_bottles_discounted: 0,
    revenue_regular: 0,
    revenue_discounted: 0,
    discounts_given: 0,
    revenue_ar: 0,
  }, [data]);

  const canViewAmounts = data?.can_view_amounts === true;

  const applyProductFilter = (next: ProductFilter) => {
    setProductFilter(next);
    setPage(1);
  };

  const productFilterLabel = (key: ProductFilter): string => {
    if (key === "all") return "All products";
    const match = productCards.find(p => p.key === key);
    return match?.label ?? key;
  };

  return (
    <PageLayout title="Sales" subtitle="All products — walk-in POS scans and pack scans for shipping">
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">
              Sold at the register + packed for shipping
              {data?.filter.label ? ` · ${data.filter.label}` : ""}
            </p>
            <p className="text-2xl font-bold text-foreground mt-1">
              {loading ? "…" : fmtQty(data?.total_units_sold ?? data?.total_bottles_sold ?? 0)} total units
            </p>
            {!loading && data?.rts ? (
              <p className="text-xs text-muted-foreground mt-1">
                RTS barcode: walk-in {fmtQty(data.rts.walk_in)} · shipped {fmtQty(data.rts.shipped)}
                {data.rts.total > 0 ? ` · ${fmtQty(data.rts.total)} total` : ""}
              </p>
            ) : null}
          </div>
          <div className="flex flex-wrap items-end gap-2">
            <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
              Period
              <select
                value={period}
                onChange={e => setPeriod(e.target.value as PeriodFilter)}
                className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card min-w-[8.5rem]"
              >
                {PERIOD_OPTIONS.map(o => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </label>
            {period === "date" && (
              <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                Date
                <input
                  type="date"
                  value={pickDate}
                  onChange={e => setPickDate(e.target.value)}
                  className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card"
                />
              </label>
            )}
            {period === "custom" && (
              <>
                <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                  From
                  <input
                    type="date"
                    value={from}
                    onChange={e => setFrom(e.target.value)}
                    className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card"
                  />
                </label>
                <label className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
                  To
                  <input
                    type="date"
                    value={to}
                    onChange={e => setTo(e.target.value)}
                    className="mt-1 block text-sm border rounded-lg px-2 py-1.5 bg-card"
                  />
                </label>
              </>
            )}
            <button
              type="button"
              onClick={applyFilters}
              className="text-xs font-medium border rounded-lg px-3 py-2 hover:bg-secondary"
            >
              Apply
            </button>
          </div>
          <div className="flex flex-wrap gap-2 mt-3">
            {([
              ["all", "All"],
              ["walk_in", "Walk-in"],
              ["online", "Shipped"],
            ] as const).map(([id, label]) => (
              <button
                key={id}
                type="button"
                onClick={() => setChannel(id)}
                className={`rounded-md border px-3 py-1.5 text-[11px] font-medium ${
                  channel === id
                    ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a6506]"
                    : "border-border text-muted-foreground hover:bg-secondary/50"
                }`}
              >
                {label}
              </button>
            ))}
          </div>
          {data?.channel_share && (
            <p className="text-sm text-muted-foreground mt-2">
              Walk-in sold {fmtQty(data.channel_share.walk_in)} ({data.channel_share.walk_in_pct}%)
              {" · "}
              Shipped {fmtQty(data.channel_share.online)} ({data.channel_share.online_pct}%)
            </p>
          )}
        </div>

        {productCards.length > 0 && (
          <AppSectionEnter>
            <h2 className="text-sm font-semibold mb-1">By product</h2>
            <p className="text-xs text-muted-foreground mb-3">
              Tap a product to filter recent walk-in sales, or All products to show every receipt.
            </p>
            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
              <button
                type="button"
                onClick={() => applyProductFilter("all")}
                className={`rounded-xl border bg-card p-4 text-left ${appHoverCard} ${
                  productFilter === "all" ? "border-[#C8900A] bg-[#C8900A]/10" : "border-border"
                }`}
              >
                <p className="text-xs uppercase tracking-wide text-muted-foreground truncate">All products</p>
                <p className="text-xl font-bold mt-1">
                  {loading ? "…" : fmtQty(data?.total_units_sold ?? data?.total_bottles_sold ?? 0)}
                </p>
                <p className="text-xs text-muted-foreground mt-1.5">Select all receipts</p>
              </button>
              {productCards.map(p => {
                const active = productFilter === p.key;
                return (
                <button
                  key={p.key}
                  type="button"
                  onClick={() => {
                    if (p.key === "honey" || p.key === "pt" || p.key === "brc" || p.key === "jar_cacao" || p.key === "honey_comb") {
                      applyProductFilter(p.key);
                    }
                  }}
                  className={`rounded-xl border bg-card p-4 text-left ${appHoverCard} ${
                    active ? "border-[#C8900A] bg-[#C8900A]/10" : "border-border"
                  }`}
                >
                  <p className="text-xs uppercase tracking-wide text-muted-foreground truncate">{p.label}</p>
                  <p className="text-xl font-bold mt-1">
                    {loading ? "…" : fmtQty(p.total)}{" "}
                    <span className="text-sm font-medium text-muted-foreground">
                      {p.unit}
                      {p.total === 1 ? "" : "s"}
                    </span>
                  </p>
                  <p className="text-xs text-muted-foreground mt-1.5">
                    Walk-in {fmtQty(p.walk_in)} · Shipped {fmtQty(p.shipped)}
                  </p>
                </button>
                );
              })}
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Shipped units are stickers scanned in pack scan — packed for a parcel, not yet a register sale.
            </p>
          </AppSectionEnter>
        )}

        {channel !== "online" && (
        <div className={`grid grid-cols-1 gap-3 ${canViewAmounts ? "md:grid-cols-4" : "md:grid-cols-2"}`}>
          <div className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Honey regular sales</p>
            <p className="text-xl font-bold mt-1">{fmtQty(summary.total_bottles_regular)} bottles</p>
            {canViewAmounts ? (
              <p className="text-sm text-muted-foreground mt-1">{peso(summary.revenue_regular ?? 0)}</p>
            ) : null}
          </div>
          <div className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Honey discounted sales</p>
            <p className="text-xl font-bold mt-1">{fmtQty(summary.total_bottles_discounted)} bottles</p>
            {canViewAmounts ? (
              <p className="text-sm text-muted-foreground mt-1">{peso(summary.revenue_discounted ?? 0)}</p>
            ) : null}
          </div>
          {canViewAmounts ? (
            <>
              <div className="rounded-xl border border-red-200 bg-red-50/40 p-4">
                <p className="text-xs uppercase tracking-wide text-red-700">AR sales</p>
                <p className="text-xl font-bold mt-1 text-red-700">{peso(summary.revenue_ar ?? 0)}</p>
              </div>
              <div className="rounded-xl border border-border bg-card p-4">
                <p className="text-xs uppercase tracking-wide text-muted-foreground">Discount given</p>
                <p className="text-xl font-bold mt-1">{peso(summary.discounts_given ?? 0)}</p>
              </div>
            </>
          ) : null}
        </div>
        )}

        <div className="flex flex-col sm:flex-row sm:items-end gap-2">
          <label className="flex-1 text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">
            Receipt search
            <div className="mt-1 flex gap-2">
              <div className="relative flex-1">
                <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                <input
                  type="search"
                  value={receipt}
                  onChange={e => setReceipt(e.target.value)}
                  onKeyDown={e => {
                    if (e.key === "Enter") applyFilters();
                  }}
                  placeholder="RCP-… or partial number"
                  className="w-full text-sm border rounded-lg pl-8 pr-8 py-1.5 bg-card font-mono"
                />
                {receipt && (
                  <button
                    type="button"
                    onClick={clearReceiptSearch}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-0.5"
                    aria-label="Clear receipt search"
                  >
                    <X size={14} />
                  </button>
                )}
              </div>
            </div>
          </label>
          {appliedReceipt && (
            <p className="text-xs text-muted-foreground sm:pb-2">
              Filtering receipts matching <span className="font-mono font-medium text-foreground">{appliedReceipt}</span>
              {" · "}
              <button type="button" onClick={clearReceiptSearch} className="text-[#C8900A] hover:underline">
                Clear
              </button>
            </p>
          )}
        </div>

        <div className="flex flex-wrap gap-2">
          {([
            { value: "all", label: "All checkouts" },
            { value: "regular", label: "Regular only" },
            { value: "discount", label: "Discount only" },
            { value: "ar", label: "AR only" },
          ] as Array<{ value: SaleTypeFilter; label: string }>).map(opt => {
            const active = saleType === opt.value;
            const isAr = opt.value === "ar";
            return (
            <button
              key={opt.value}
              type="button"
              onClick={() => {
                setSaleType(opt.value);
                setPage(1);
                void load({ page: 1, saleType: opt.value });
              }}
              className={`px-3 py-1.5 text-xs rounded-full border transition-colors ${
                active
                  ? isAr
                    ? "bg-red-50 border-red-400 text-red-700 font-semibold"
                    : "bg-[#C8900A]/10 border-[#C8900A] text-[#8a6506] font-semibold"
                  : "border-border text-muted-foreground hover:bg-secondary/50"
              }`}
            >
              {opt.label}
            </button>
            );
          })}
        </div>

        {error && <AppErrorBanner message={error} />}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-4 py-3 border-b border-border flex flex-wrap items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <Package size={16} className="text-[#C8900A]" />
              <h2 className="text-sm font-semibold">
                Recent walk-in sales · {productFilterLabel(productFilter)}
              </h2>
            </div>
            <div className="flex items-center gap-2">
              {checkouts && checkouts.total > 0 && (
                <span className="text-xs text-muted-foreground">
                  {checkouts.total} checkout{checkouts.total === 1 ? "" : "s"}
                </span>
              )}
              <Button
                type="button"
                size="sm"
                variant="outline"
                className="gap-1.5"
                disabled={exporting || loading}
                onClick={() => void exportExcel()}
              >
                {exporting ? <Loader2 size={14} className="animate-spin" /> : <Download size={14} />}
                Export Excel
              </Button>
            </div>
          </div>

          {loading ? (
            <div className="flex justify-center py-8 text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (checkouts?.rows.length ?? 0) === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-8">
              {appliedReceipt
                ? `No checkout found for receipt “${appliedReceipt}” in this period.`
                : "No POS sales recorded for this period."}
            </p>
          ) : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-xs text-muted-foreground border-b border-border bg-secondary/20">
                      <th className="w-8 px-2 py-2" aria-label="Expand" />
                      <th className="px-3 py-2 font-semibold">When sold</th>
                      <th className="px-3 py-2 font-semibold">Receipt</th>
                      <th className="px-3 py-2 font-semibold">Sold by</th>
                      <th className="px-3 py-2 font-semibold">Sold to</th>
                      <th className="px-3 py-2 font-semibold">Items</th>
                      <th className="px-3 py-2 font-semibold">Type</th>
                      {canViewAmounts ? (
                        <th className="px-3 py-2 font-semibold text-right">Total</th>
                      ) : null}
                    </tr>
                  </thead>
                  <tbody>
                    {checkouts!.rows.map(row => {
                      const expanded = expandedId === row.sale_id;
                      return (
                        <Fragment key={row.sale_id}>
                          <tr
                            className={`border-b border-border/60 cursor-pointer ${appHoverRow} ${expanded ? "bg-secondary/30" : ""}`}
                            onClick={() => toggleExpand(row.sale_id)}
                          >
                            <td className="px-2 py-2.5 text-muted-foreground">
                              {expanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                            </td>
                            <td className="px-3 py-2.5 text-muted-foreground whitespace-nowrap">
                              {new Date(row.sold_at).toLocaleString("en-PH", {
                                month: "short",
                                day: "numeric",
                                year: "numeric",
                                hour: "2-digit",
                                minute: "2-digit",
                              })}
                            </td>
                            <td className="px-3 py-2.5 font-mono text-xs">{row.receipt_no}</td>
                            <td className="px-3 py-2.5">{row.sold_by_name || "POS"}</td>
                            <td className="px-3 py-2.5">{row.buyer_name || "Walk-in"}</td>
                            <td className="px-3 py-2.5 text-muted-foreground">{itemSummary(row.items)}</td>
                            <td className="px-3 py-2.5">
                              {row.is_ar ? (
                                <span className="inline-flex rounded-full bg-red-100 text-red-700 text-[11px] px-2 py-0.5 font-semibold">
                                  AR
                                </span>
                              ) : row.discount_percent ? (
                                <span className="inline-flex rounded-full bg-red-100 text-red-700 text-[11px] px-2 py-0.5 font-semibold">
                                  {row.discount_percent}% off
                                </span>
                              ) : (
                                <span className="inline-flex rounded-full bg-slate-100 text-slate-600 text-[11px] px-2 py-0.5 font-medium">
                                  Regular
                                </span>
                              )}
                            </td>
                            {canViewAmounts ? (
                              <td className="px-3 py-2.5 text-right font-semibold" style={{ color: "#C8900A" }}>
                                {peso(row.total_amount ?? 0)}
                              </td>
                            ) : null}
                          </tr>
                          {expanded && (
                            <tr key={`${row.sale_id}-detail`} className="border-b border-border/60 bg-secondary/10">
                              <td colSpan={canViewAmounts ? 8 : 7} className="px-4 py-3">
                                <div className="flex flex-wrap items-start justify-between gap-2 mb-2">
                                  <p className="text-xs text-muted-foreground">
                                    Sold by: <span className="text-foreground font-medium">{row.sold_by_name || "POS"}</span>
                                    {" · "}
                                    Sold to: <span className="text-foreground font-medium">{row.buyer_name}</span>
                                    {row.is_ar && row.buyer_credentials ? (
                                      <span className="ml-2">
                                        · Mobile: <span className="text-foreground font-medium">{row.buyer_credentials}</span>
                                      </span>
                                    ) : null}
                                  </p>
                                  {canViewAmounts ? (
                                    <Button
                                      type="button"
                                      size="sm"
                                      variant="outline"
                                      className="gap-1.5 shrink-0"
                                      onClick={e => {
                                        e.stopPropagation();
                                        printPosThermalReceipt(checkoutToThermalReceipt(row), pyxLogo);
                                      }}
                                    >
                                      <Printer size={14} />
                                      Reprint
                                    </Button>
                                  ) : null}
                                </div>
                                {canViewAmounts && row.discount_percent ? (
                                  <p className="text-xs text-muted-foreground mb-2">
                                    Subtotal {peso(row.subtotal_amount ?? row.total_amount ?? 0)} · Discount {row.discount_percent}% (
                                    -{peso(row.discount_amount ?? 0)})
                                  </p>
                                ) : null}
                                <ul className="space-y-1.5">
                                  {row.items.map((item, idx) => (
                                    <li
                                      key={idx}
                                      className="flex justify-between gap-3 text-sm py-1.5 px-3 rounded-lg bg-card border border-border/60"
                                    >
                                      <span className="min-w-0">
                                        <span className="capitalize font-medium">
                                          {item.variant ? variantLabel(item.variant) : (item.label ?? item.name ?? "Item")}
                                        </span>
                                        {item.variant && item.size_label ? (
                                          <span className="text-muted-foreground"> · {item.size_label}</span>
                                        ) : null}
                                        <span className="text-muted-foreground"> × {fmtQty(item.qty)}</span>
                                        {(item.barcodes ?? []).length > 0 ? (
                                          <span className="mt-1 flex flex-col gap-0.5">
                                            {item.barcodes!.map(bc => (
                                              <span
                                                key={bc.code}
                                                className="font-mono text-[11px] text-muted-foreground break-all"
                                              >
                                                {bc.code}
                                              </span>
                                            ))}
                                          </span>
                                        ) : null}
                                      </span>
                                      {canViewAmounts ? (
                                        <span className="font-semibold whitespace-nowrap self-start">
                                          {item.line_subtotal != null && item.line_total != null && item.line_subtotal > item.line_total ? (
                                            <>
                                              <span className="line-through text-muted-foreground mr-1">{peso(item.line_subtotal)}</span>
                                              {peso(item.line_total)}
                                            </>
                                          ) : (
                                            peso(item.line_total ?? 0)
                                          )}
                                        </span>
                                      ) : null}
                                    </li>
                                  ))}
                                </ul>
                                {(row.returned_units ?? []).length > 0 ? (
                                  <div className="mt-3 pt-2 border-t border-border/60">
                                    <p className="text-xs font-semibold text-amber-800 mb-1.5">Returned (removed from total)</p>
                                    <ul className="space-y-1">
                                      {row.returned_units!.map(u => (
                                        <li
                                          key={u.code}
                                          className="font-mono text-[11px] text-amber-900/80 py-1 px-3 rounded-lg bg-amber-50 border border-amber-200/80"
                                        >
                                          {u.code}
                                        </li>
                                      ))}
                                    </ul>
                                  </div>
                                ) : null}
                              </td>
                            </tr>
                          )}
                        </Fragment>
                      );
                    })}
                  </tbody>
                </table>
              </div>

              <div className="px-4 py-3 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                <p className="text-xs text-muted-foreground">
                  Page {checkouts!.page} of {Math.max(1, checkouts!.total_pages)}
                  <span className="hidden sm:inline"> · {checkouts!.per_page} per page</span>
                </p>
                <div className="flex gap-2">
                  <button
                    type="button"
                    disabled={!canPrev || loading}
                    onClick={() => setPage(p => Math.max(1, p - 1))}
                    className="text-xs font-medium border rounded-lg px-3 py-2 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    Previous
                  </button>
                  <button
                    type="button"
                    disabled={!canNext || loading}
                    onClick={() => setPage(p => p + 1)}
                    className="text-xs font-medium border rounded-lg px-3 py-2 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    Next
                  </button>
                </div>
              </div>
            </>
          )}
        </div>

        <div>
          <h2 className="text-sm font-semibold mb-3">Honey by variant and size</h2>
          {loading ? (
            <div className="flex justify-center py-8 text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <HoneyVariantPieGrid
              variantsAll={data?.variants_all ?? []}
              variantsRegular={data?.variants_regular ?? []}
              variantsDiscount10={data?.variants_discount_10 ?? []}
              variantsDiscount20={data?.variants_discount_20 ?? []}
              emptyMessage="No honey sales for this period."
            />
          )}
        </div>
      </div>
    </PageLayout>
  );
}
