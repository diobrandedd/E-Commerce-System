import { useCallback, useEffect, useState } from "react";
import { AlertCircle, Loader2, Package } from "lucide-react";
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, ApiError } from "@/app/lib/api";

type PeriodFilter = "all" | "day" | "week" | "month" | "year" | "custom";

interface SizeSlice {
  size: string;
  label: string;
  qty: number;
  pct: number;
}

interface VariantChart {
  variant: string;
  total_bottles: number;
  sizes: SizeSlice[];
}

interface DeptProduct {
  key: string;
  label: string;
  total_qty: number;
}

interface InventorySnapshot {
  filter: { period: string; from: string | null; to: string | null; label: string };
  honey_packaging: { total_bottles_on_hand: number; variants: VariantChart[] };
  dept_products: { products: DeptProduct[] };
}

const SIZE_COLORS: Record<string, string> = {
  "250ml": "#C8900A",
  "350ml": "#2a8a4a",
  liter: "#4a7fc8",
  gallon: "#6b4c2a",
};

const PERIOD_OPTIONS: { value: PeriodFilter; label: string }[] = [
  { value: "all", label: "All time" },
  { value: "day", label: "Today" },
  { value: "week", label: "This week" },
  { value: "month", label: "This month" },
  { value: "year", label: "This year" },
  { value: "custom", label: "Custom range" },
];

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function variantLabel(v: string): string {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

export function DashboardInventorySection() {
  const [period, setPeriod] = useState<PeriodFilter>("all");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [data, setData] = useState<InventorySnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, string> = { period };
      if (period === "custom") {
        if (!from || !to) {
          setError("Choose both start and end dates for a custom range.");
          setLoading(false);
          return;
        }
        params.from = from;
        params.to = to;
      }
      const payload = await apiGet<InventorySnapshot>("dashboard.php", "inventory_snapshot", params);
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load packaging inventory.");
    } finally {
      setLoading(false);
    }
  }, [period, from, to]);

  useEffect(() => {
    if (period === "custom" && (!from || !to)) return;
    load();
  }, [load, period, from, to]);
  useLiveRefresh(load);

  const honey = data?.honey_packaging;
  const deptProducts = data?.dept_products.products ?? [];

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
          <h2 className="text-sm font-semibold text-foreground">Packaging & display stock</h2>
          <p className="text-xs text-muted-foreground mt-0.5">
            Bottled honey on hand and other RTS products ready for display
            {data?.filter.label ? ` · ${data.filter.label}` : ""}
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
            onClick={load}
            className="text-xs font-medium border rounded-lg px-3 py-2 hover:bg-secondary"
          >
            Apply
          </button>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
          <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />
          <span>{error}</span>
        </div>
      )}

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div className="bg-card rounded-xl border border-border p-5 sm:col-span-2 xl:col-span-1" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-start justify-between mb-3">
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">Honey (packaging)</p>
            <Package size={16} className="text-[#C8900A]" />
          </div>
          <p className="text-2xl font-bold text-foreground">
            {loading ? "…" : fmtQty(honey?.total_bottles_on_hand ?? 0)}
          </p>
          <p className="text-xs text-muted-foreground mt-1">Total bottles on display (all sizes)</p>
        </div>

        {deptProducts.map(p => (
          <div
            key={p.key}
            className="bg-card rounded-xl border border-border p-5"
            style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
          >
            <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-3">{p.label}</p>
            <p className="text-2xl font-bold text-foreground">{loading ? "…" : fmtQty(p.total_qty)}</p>
            <p className="text-xs text-muted-foreground mt-1">Units on hand</p>
          </div>
        ))}
      </div>

      {loading && (
        <div className="flex justify-center py-8 text-muted-foreground">
          <Loader2 className="animate-spin" size={20} />
        </div>
      )}

      {!loading && (honey?.variants.length ?? 0) === 0 && (
        <p className="text-sm text-muted-foreground text-center py-6">No honey packaging stock for this filter.</p>
      )}

      {!loading && (honey?.variants.length ?? 0) > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          {honey!.variants.map(v => {
            const chartData = v.sizes.filter(s => s.qty > 0);
            const hasData = chartData.length > 0;

            return (
              <div
                key={v.variant}
                className="bg-card rounded-xl border border-border p-4"
                style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
              >
                <div className="flex items-baseline justify-between mb-2">
                  <h3 className="text-sm font-semibold capitalize">{variantLabel(v.variant)}</h3>
                  <span className="text-xs text-muted-foreground">{fmtQty(v.total_bottles)} total</span>
                </div>

                {hasData ? (
                  <div className="h-44">
                    <ResponsiveContainer width="100%" height="100%">
                      <PieChart>
                        <Pie
                          data={chartData}
                          dataKey="qty"
                          nameKey="label"
                          cx="50%"
                          cy="50%"
                          innerRadius={36}
                          outerRadius={58}
                          paddingAngle={2}
                        >
                          {chartData.map(entry => (
                            <Cell key={entry.size} fill={SIZE_COLORS[entry.size] ?? "#888"} />
                          ))}
                        </Pie>
                        <Tooltip
                          formatter={(value: number, _name, item) => {
                            const pct = (item?.payload as SizeSlice)?.pct ?? 0;
                            return [`${fmtQty(value)} (${pct}%)`, (item?.payload as SizeSlice)?.label ?? ""];
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground text-center py-10">No stock</p>
                )}

                <div className="mt-3 grid grid-cols-2 gap-2">
                  {v.sizes.map(s => (
                    <div key={s.size} className="text-center rounded-lg bg-secondary/40 px-2 py-2">
                      <p className="text-[10px] text-muted-foreground uppercase tracking-wide">{s.label}</p>
                      <p className="text-lg font-bold text-foreground">{fmtQty(s.qty)}</p>
                      <p className="text-[10px] text-muted-foreground">{s.pct}%</p>
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      )}

      <p className="text-[10px] text-muted-foreground">
        Honey: current bottle counts from packaging batches
        {data?.filter.period !== "all" ? " with bottling date in the selected period" : ""}.
        Other products: dept inventory rows in the same date window (by stock-in date).
      </p>
    </div>
  );
}
