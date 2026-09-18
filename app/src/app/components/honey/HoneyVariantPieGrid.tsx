import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from "recharts";
import { useMemo, useState, type ReactNode } from "react";

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
export type InventorySizeFilter = "all" | "250ml" | "350ml" | "liter" | "gallon";

const SIZE_COLORS: Record<string, string> = {
  "250ml": "#C8900A",
  "350ml": "#2a8a4a",
  liter: "#4a7fc8",
  gallon: "#6b4c2a",
};

const INVENTORY_SIZE_FILTERS: { id: InventorySizeFilter; label: string }[] = [
  { id: "all", label: "All" },
  { id: "250ml", label: "250ML" },
  { id: "350ml", label: "350ML" },
  { id: "liter", label: "1L" },
  { id: "gallon", label: "Gallon" },
];

function fmtQty(n: number): string {
  if (!Number.isFinite(n)) return "0";
  if (Math.abs(n - Math.round(n)) < 0.001) return Math.round(n).toLocaleString();
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

function variantLabel(v: string): string {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

function applyInventorySizeFilter(chart: VariantChart, filter: InventorySizeFilter): VariantChart {
  if (filter === "all") {
    return chart;
  }
  const slice = chart.sizes.find(s => s.size === filter);
  const qty = slice?.qty ?? 0;
  return {
    variant: chart.variant,
    total_bottles: qty,
    sizes: chart.sizes.map(s => ({
      ...s,
      qty: s.size === filter ? s.qty : 0,
      pct: s.size === filter && qty > 0 ? 100 : 0,
    })),
  };
}

type SalesGridProps = {
  mode?: "sales";
  variantsAll: VariantChart[];
  variantsRegular: VariantChart[];
  variantsDiscount10: VariantChart[];
  variantsDiscount20: VariantChart[];
  emptyMessage?: string;
};

type InventoryGridProps = {
  mode: "inventory";
  variants: VariantChart[];
  emptyMessage?: string;
  headerNote?: string;
  variantActions?: { variant: string; node: ReactNode }[];
};

export function HoneyVariantPieGrid(props: SalesGridProps | InventoryGridProps) {
  if (props.mode === "inventory") {
    return (
      <HoneyInventoryGrid
        variants={props.variants}
        emptyMessage={props.emptyMessage}
        headerNote={props.headerNote}
        variantActions={props.variantActions}
      />
    );
  }

  return <HoneySalesGrid {...props} />;
}

function HoneyInventoryGrid({
  variants,
  emptyMessage = "No bottled batch inventory.",
  headerNote = "Bottled stock with batch numbers only (warehouse & production gallons excluded)",
  variantActions,
}: {
  variants: VariantChart[];
  emptyMessage?: string;
  headerNote?: string;
  variantActions?: { variant: string; node: ReactNode }[];
}) {
  const [sizeFilter, setSizeFilter] = useState<InventorySizeFilter>("all");
  const actionByVariant = useMemo(() => {
    const map = new Map<string, ReactNode>();
    for (const a of variantActions ?? []) {
      map.set(a.variant, a.node);
    }
    return map;
  }, [variantActions]);

  const display = useMemo(
    () => variants.map(v => applyInventorySizeFilter(v, sizeFilter)),
    [variants, sizeFilter],
  );

  const total = useMemo(() => display.reduce((sum, v) => sum + v.total_bottles, 0), [display]);

  if (variants.length === 0) {
    return <p className="text-sm text-muted-foreground text-center py-6">{emptyMessage}</p>;
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p className="text-sm text-muted-foreground">{headerNote}</p>
        <p className="text-xl font-bold text-foreground">{fmtQty(total)} total bottles</p>
      </div>

      <div className="flex flex-wrap gap-2">
        <span className="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold self-center mr-1">
          Size
        </span>
        {INVENTORY_SIZE_FILTERS.map(f => (
          <button
            key={f.id}
            type="button"
            onClick={() => setSizeFilter(f.id)}
            className={`rounded-md border px-3 py-1.5 text-[11px] font-medium transition-colors ${
              sizeFilter === f.id
                ? "border-[#C8900A] bg-[#C8900A]/10 text-[#8a6506]"
                : "border-border text-muted-foreground hover:bg-secondary/50"
            }`}
          >
            {f.label}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        {display.map(effective => {
          const chartData = effective.sizes.filter(s => s.qty > 0);
          const hasData = chartData.length > 0;
          const editAction = actionByVariant.get(effective.variant);

          return (
            <div
              key={effective.variant}
              className="bg-card rounded-xl border border-border p-4"
              style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
            >
              <div className="flex items-start justify-between gap-2 mb-3">
                <div className="flex items-baseline justify-between gap-2 flex-1 min-w-0">
                  <h3 className="text-sm font-semibold capitalize">{variantLabel(effective.variant)}</h3>
                  <span className="text-xs text-muted-foreground shrink-0">
                    {fmtQty(effective.total_bottles)} in stock
                  </span>
                </div>
                {editAction ? <div className="shrink-0">{editAction}</div> : null}
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
                <p className="text-xs text-muted-foreground text-center py-10">No stock for this filter</p>
              )}

              <div className="mt-3 grid grid-cols-2 gap-2">
                {effective.sizes.map(s => (
                  <div
                    key={s.size}
                    className={`text-center rounded-lg px-2 py-2 ${
                      sizeFilter !== "all" && s.size !== sizeFilter ? "opacity-40" : "bg-secondary/40"
                    }`}
                  >
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
    </div>
  );
}

function HoneySalesGrid({
  variantsAll,
  variantsRegular,
  variantsDiscount10,
  variantsDiscount20,
  emptyMessage = "No data for this period.",
}: SalesGridProps) {
  const merged = useMemo(() => {
    const order = new Map<string, number>();
    variantsAll.forEach((v, idx) => order.set(v.variant, idx));
    const byVariant = new Map<
      string,
      {
        variant: string;
        all?: VariantChart;
        regular?: VariantChart;
        discount10?: VariantChart;
        discount20?: VariantChart;
      }
    >();

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
        const source =
          mode === "all" ? v.all : mode === "regular" ? v.regular : mode === "discount10" ? v.discount10 : v.discount20;
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
