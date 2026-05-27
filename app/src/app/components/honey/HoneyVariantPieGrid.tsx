import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";
import { useMemo, useState } from "react";

export interface SizeSlice {
  size: string;
  label: string;
  qty: number;
  pct: number;
}

export interface VariantChart {
  variant: string;
  total_bottles: number;
  sizes: SizeSlice[];
}

type PieMode = "all" | "regular" | "discount10" | "discount20";

const SIZE_COLORS: Record<string, string> = {
  "250ml": "#C8900A",
  "350ml": "#2a8a4a",
  liter: "#4a7fc8",
  gallon: "#6b4c2a",
};

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function variantLabel(v: string): string {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

export function HoneyVariantPieGrid({
  variantsAll,
  variantsRegular,
  variantsDiscount10,
  variantsDiscount20,
  emptyMessage = "No data for this period.",
}: {
  variantsAll: VariantChart[];
  variantsRegular: VariantChart[];
  variantsDiscount10: VariantChart[];
  variantsDiscount20: VariantChart[];
  emptyMessage?: string;
}) {
  const merged = useMemo(() => {
    const order = new Map<string, number>();
    variantsAll.forEach((v, idx) => order.set(v.variant, idx));
    const byVariant = new Map<string, {
      variant: string;
      all?: VariantChart;
      regular?: VariantChart;
      discount10?: VariantChart;
      discount20?: VariantChart;
    }>();

    const put = (bucket: PieMode, rows: VariantChart[]) => {
      rows.forEach((row, idx) => {
        if (!order.has(row.variant)) order.set(row.variant, variantsAll.length + idx);
        if (!byVariant.has(row.variant)) {
          byVariant.set(row.variant, { variant: row.variant });
        }
        byVariant.get(row.variant)![bucket] = row;
      });
    };

    put("all", variantsAll);
    put("regular", variantsRegular);
    put("discount10", variantsDiscount10);
    put("discount20", variantsDiscount20);

    return Array.from(byVariant.values()).sort((a, b) => (order.get(a.variant) ?? 0) - (order.get(b.variant) ?? 0));
  }, [variantsAll, variantsRegular, variantsDiscount10, variantsDiscount20]);

  const [modeByVariant, setModeByVariant] = useState<Record<string, PieMode>>({});

  if (merged.length === 0) {
    return <p className="text-sm text-muted-foreground text-center py-6">{emptyMessage}</p>;
  }

  const modeLabel: Record<PieMode, string> = {
    all: "All",
    regular: "Regular",
    discount10: "10%",
    discount20: "20%",
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      {merged.map(v => {
        const mode = modeByVariant[v.variant] ?? "all";
        const source = mode === "all" ? v.all : mode === "regular" ? v.regular : mode === "discount10" ? v.discount10 : v.discount20;
        const effective = source ?? { variant: v.variant, total_bottles: 0, sizes: [] as SizeSlice[] };
        const chartData = effective.sizes.filter(s => s.qty > 0);
        const hasData = chartData.length > 0;

        return (
          <div
            key={v.variant}
            className="bg-card rounded-xl border border-border p-4"
            style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
          >
            <div className="flex items-baseline justify-between mb-2">
              <h3 className="text-sm font-semibold capitalize">{variantLabel(v.variant)}</h3>
              <span className="text-xs text-muted-foreground">{fmtQty(effective.total_bottles)} sold</span>
            </div>

            <div className="mb-3 grid grid-cols-4 gap-1">
              {(["all", "regular", "discount10", "discount20"] as PieMode[]).map(m => (
                <button
                  key={m}
                  type="button"
                  onClick={() => setModeByVariant(prev => ({ ...prev, [v.variant]: m }))}
                  className={`rounded-md border px-2 py-1 text-[11px] font-medium transition-colors ${
                    mode === m
                      ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a6506]"
                      : "border-border text-muted-foreground hover:bg-secondary/50"
                  }`}
                >
                  {modeLabel[m]}
                </button>
              ))}
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
              <p className="text-xs text-muted-foreground text-center py-10">No sales</p>
            )}

            <div className="mt-3 grid grid-cols-2 gap-2">
              {effective.sizes.map(s => (
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
  );
}
