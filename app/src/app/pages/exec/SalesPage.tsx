import { Fragment, useCallback, useEffect, useMemo, useState } from "react";
import { AlertCircle, ChevronDown, ChevronRight, Loader2, Package, Search, X } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyVariantPieGrid, type VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, ApiError } from "@/app/lib/api";
import { peso, variantLabel } from "@/app/lib/posHoney";

type PeriodFilter = "all" | "day" | "week" | "month" | "year" | "custom";
type SaleTypeFilter = "all" | "regular" | "discount" | "ar";
type ChannelFilter = "all" | "walk_in" | "online";

interface CheckoutItem {
  variant: string;
  size: string;
  size_label: string;
  qty: number;
  list_unit_price?: number;
  unit_price: number;
  line_subtotal?: number;
  line_total: number;
  name?: string;
}

interface CheckoutRow {
  sale_id: number;
  receipt_no: string;
  sold_at: string;
  subtotal_amount?: number;
  discount_percent?: number | null;
  discount_amount?: number;
  total_amount: number;
  buyer_name: string;
  buyer_credentials?: string | null;
  is_ar?: boolean;
  item_count: number;
  items: CheckoutItem[];
}

interface CheckoutsPage {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
  rows: CheckoutRow[];
}

interface SalesSnapshot {
  filter: { period: string; from: string | null; to: string | null; label: string; receipt?: string | null; sale_type?: SaleTypeFilter; channel?: ChannelFilter };
  total_bottles_sold: number;
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
    revenue_regular: number;
    revenue_discounted: number;
    discounts_given: number;
    revenue_ar?: number;
  };
  variants_all: VariantChart[];
  variants_regular: VariantChart[];
  variants_discount_10: VariantChart[];
  variants_discount_20: VariantChart[];
  checkouts: CheckoutsPage;
}

const PERIOD_OPTIONS: { value: PeriodFilter; label: string }[] = [
  { value: "day", label: "Today" },
  { value: "week", label: "This week" },
  { value: "month", label: "This month" },
  { value: "year", label: "This year" },
  { value: "all", label: "All time" },
  { value: "custom", label: "Custom range" },
];

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function itemSummary(items: CheckoutItem[]): string {
  if (items.length === 0) return "—";
  if (items.length === 1) {
    const i = items[0];
    return `${variantLabel(i.variant)} ${i.size_label} ×${fmtQty(i.qty)}`;
  }
  return `${items.length} line items`;
}

export function SalesPage() {
  const [period, setPeriod] = useState<PeriodFilter>("day");
  const [saleType, setSaleType] = useState<SaleTypeFilter>("all");
  const [channel, setChannel] = useState<ChannelFilter>("all");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);
  const [receipt, setReceipt] = useState("");
  const [appliedReceipt, setAppliedReceipt] = useState("");
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [data, setData] = useState<SalesSnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (opts?: { receipt?: string; page?: number; saleType?: SaleTypeFilter }) => {
      const receiptQ = opts?.receipt !== undefined ? opts.receipt : appliedReceipt;
      const pageNum = opts?.page ?? page;
      const saleTypeQ = opts?.saleType ?? saleType;

      setLoading(true);
      setError(null);
      try {
        const params: Record<string, string> = { period, page: String(pageNum), sale_type: saleTypeQ, channel };
        if (period === "custom") {
          if (!from || !to) {
            setError("Choose both start and end dates for a custom range.");
            setLoading(false);
            return;
          }
          params.from = from;
          params.to = to;
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
    [period, from, to, page, appliedReceipt, saleType, channel],
  );

  useEffect(() => {
    if (period === "custom" && (!from || !to)) return;
    void load();
  }, [load, period, from, to, page, appliedReceipt]);

  useEffect(() => {
    setPage(1);
  }, [period, from, to, appliedReceipt, saleType, channel]);

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

  useLiveRefresh(load);

  const checkouts = data?.checkouts;
  const canPrev = (checkouts?.page ?? 1) > 1;
  const canNext = checkouts ? checkouts.page < checkouts.total_pages : false;

  const toggleExpand = (saleId: number) => {
    setExpandedId(prev => (prev === saleId ? null : saleId));
  };

  const summary = useMemo(() => data?.summary ?? {
    total_bottles_regular: 0,
    total_bottles_discounted: 0,
    revenue_regular: 0,
    revenue_discounted: 0,
    discounts_given: 0,
    revenue_ar: 0,
  }, [data]);

  return (
    <PageLayout title="Sales" subtitle="Honey sold from POS scans and pack scans">
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
          <div>
            <p className="text-sm text-muted-foreground">
              Bottles sold at the register
              {data?.filter.label ? ` · ${data.filter.label}` : ""}
            </p>
            <p className="text-2xl font-bold text-foreground mt-1">
              {loading ? "…" : fmtQty(data?.total_bottles_sold ?? 0)} total bottles
            </p>
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
              ["online", "Online"],
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
              Walk-in {fmtQty(data.channel_share.walk_in)} ({data.channel_share.walk_in_pct}%)
              {" · "}
              Online {fmtQty(data.channel_share.online)} ({data.channel_share.online_pct}%)
            </p>
          )}
        </div>

        {channel !== "online" && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Regular sales</p>
            <p className="text-xl font-bold mt-1">{fmtQty(summary.total_bottles_regular)} bottles</p>
            <p className="text-sm text-muted-foreground mt-1">{peso(summary.revenue_regular)}</p>
          </div>
          <div className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Discounted sales</p>
            <p className="text-xl font-bold mt-1">{fmtQty(summary.total_bottles_discounted)} bottles</p>
            <p className="text-sm text-muted-foreground mt-1">{peso(summary.revenue_discounted)}</p>
          </div>
          <div className="rounded-xl border border-red-200 bg-red-50/40 p-4">
            <p className="text-xs uppercase tracking-wide text-red-700">AR sales</p>
            <p className="text-xl font-bold mt-1 text-red-700">{peso(summary.revenue_ar ?? 0)}</p>
          </div>
          <div className="rounded-xl border border-border bg-card p-4">
            <p className="text-xs uppercase tracking-wide text-muted-foreground">Discount given</p>
            <p className="text-xl font-bold mt-1">{peso(summary.discounts_given)}</p>
          </div>
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

        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />
            <span>{error}</span>
          </div>
        )}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-4 py-3 border-b border-border flex items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <Package size={16} className="text-[#C8900A]" />
              <h2 className="text-sm font-semibold">Recent walk-in sales</h2>
            </div>
            {checkouts && checkouts.total > 0 && (
              <span className="text-xs text-muted-foreground">
                {checkouts.total} checkout{checkouts.total === 1 ? "" : "s"}
              </span>
            )}
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
                      <th className="px-3 py-2 font-semibold">Items</th>
                      <th className="px-3 py-2 font-semibold">Type</th>
                      <th className="px-3 py-2 font-semibold text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {checkouts!.rows.map(row => {
                      const expanded = expandedId === row.sale_id;
                      return (
                        <Fragment key={row.sale_id}>
                          <tr
                            className={`border-b border-border/60 cursor-pointer transition-colors hover:bg-secondary/40 ${expanded ? "bg-secondary/30" : ""}`}
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
                            <td className="px-3 py-2.5 text-right font-semibold" style={{ color: "#C8900A" }}>
                              {peso(row.total_amount)}
                            </td>
                          </tr>
                          {expanded && (
                            <tr key={`${row.sale_id}-detail`} className="border-b border-border/60 bg-secondary/10">
                              <td colSpan={6} className="px-4 py-3">
                                <p className="text-xs text-muted-foreground mb-2">
                                  Buyer: <span className="text-foreground font-medium">{row.buyer_name}</span>
                                  {row.is_ar && row.buyer_credentials ? (
                                    <span className="ml-2">
                                      · Mobile: <span className="text-foreground font-medium">{row.buyer_credentials}</span>
                                    </span>
                                  ) : null}
                                </p>
                                {row.discount_percent ? (
                                  <p className="text-xs text-muted-foreground mb-2">
                                    Subtotal {peso(row.subtotal_amount ?? row.total_amount)} · Discount {row.discount_percent}% (
                                    -{peso(row.discount_amount ?? 0)})
                                  </p>
                                ) : null}
                                <ul className="space-y-1.5">
                                  {row.items.map((item, idx) => (
                                    <li
                                      key={idx}
                                      className="flex justify-between gap-3 text-sm py-1.5 px-3 rounded-lg bg-card border border-border/60"
                                    >
                                      <span>
                                        <span className="capitalize font-medium">{variantLabel(item.variant)}</span>
                                        <span className="text-muted-foreground"> · {item.size_label}</span>
                                        <span className="text-muted-foreground"> × {fmtQty(item.qty)}</span>
                                      </span>
                                      <span className="font-semibold whitespace-nowrap">
                                        {item.line_subtotal != null && item.line_subtotal > item.line_total ? (
                                          <>
                                            <span className="line-through text-muted-foreground mr-1">{peso(item.line_subtotal)}</span>
                                            {peso(item.line_total)}
                                          </>
                                        ) : (
                                          peso(item.line_total)
                                        )}
                                      </span>
                                    </li>
                                  ))}
                                </ul>
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
          <h2 className="text-sm font-semibold mb-3">Sold by variant and size</h2>
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
