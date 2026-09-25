import { useCallback, useEffect, useMemo, useState } from "react";
import { Link } from "react-router";
import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Loader2 } from "lucide-react";
import { AppErrorBanner, AppSectionEnter } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { apiGet, ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

export type StockInsFamily = "all" | "honey" | "pt" | "brc" | "jar_cacao" | "honey_comb";
export type StockInsDays = 7 | 30 | 90;

interface FilterOption {
  key: string;
  label: string;
}

interface SeriesDef {
  key: string;
  label: string;
  color: string;
}

interface StockInsSeries {
  family: string;
  variant: string;
  size: string;
  filter: { from: string; to: string; days: number; label: string };
  chart_mode: "single" | "multi";
  group_by: "family" | "variant" | "size" | null;
  series: SeriesDef[];
  points: Record<string, string | number>[];
  total: number;
  families: FilterOption[];
  variants: FilterOption[];
  sizes: FilterOption[];
}

export interface StockInsTrendChartProps {
  /** quick = compact dashboard strip; full = inventory page */
  mode?: "quick" | "full";
  /** Lock family (e.g. honey page → "honey", paragis → "pt") */
  lockedFamily?: StockInsFamily;
  defaultFamily?: StockInsFamily;
  defaultDays?: StockInsDays;
  className?: string;
  /** Deep link under chart (dashboard only) */
  inventoryHref?: string;
}

function shortDate(iso: string): string {
  const d = new Date(String(iso) + "T12:00:00");
  if (Number.isNaN(d.getTime())) return String(iso);
  return d.toLocaleDateString(undefined, { month: "short", day: "numeric" });
}

function ChipRow({
  options,
  value,
  onChange,
  ariaLabel,
}: {
  options: FilterOption[];
  value: string;
  onChange: (key: string) => void;
  ariaLabel: string;
}) {
  return (
    <div className="flex flex-wrap gap-1.5" role="group" aria-label={ariaLabel}>
      {options.map(opt => {
        const active = opt.key === value;
        return (
          <Button
            key={opt.key || "all"}
            type="button"
            size="sm"
            variant={active ? "default" : "outline"}
            onClick={() => onChange(opt.key)}
            className={cn(
              "min-w-0 h-8 px-2.5 text-xs",
              active && "bg-[#C8900A] hover:bg-[#C8900A]/90 text-white",
            )}
          >
            {opt.label}
          </Button>
        );
      })}
    </div>
  );
}

export function StockInsTrendChart({
  mode = "full",
  lockedFamily,
  defaultFamily = "all",
  defaultDays = 30,
  className,
  inventoryHref,
}: StockInsTrendChartProps) {
  const isQuick = mode === "quick";
  const [family, setFamily] = useState<StockInsFamily>(lockedFamily ?? defaultFamily);
  const [variant, setVariant] = useState("");
  const [size, setSize] = useState("");
  const [days, setDays] = useState<StockInsDays>(isQuick ? 7 : defaultDays);
  const [data, setData] = useState<StockInsSeries | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const trendsHref = inventoryHref ?? stockInsInventoryHref(family);

  useEffect(() => {
    if (lockedFamily) setFamily(lockedFamily);
  }, [lockedFamily]);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<StockInsSeries>("dashboard.php", "stock_ins_series", {
        family,
        variant: family === "honey" ? variant : "",
        size: family === "honey" ? size : "",
        days: String(days),
      });
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load stock-in trend.");
      setData(null);
    } finally {
      setLoading(false);
    }
  }, [family, variant, size, days]);

  useEffect(() => {
    void load();
  }, [load]);

  const chartRows = useMemo(
    () =>
      (data?.points ?? []).map(p => ({
        ...p,
        label: shortDate(String(p.date)),
      })),
    [data?.points],
  );

  const seriesList = data?.series ?? [{ key: "qty", label: "Stocked in", color: "#C8900A" }];

  const subtitleHint = useMemo(() => {
    if (!data) return null;
    if (data.group_by === "family") return "Lines = products";
    if (data.group_by === "variant") return "Lines = variants";
    if (data.group_by === "size") return "Lines = sizes";
    return null;
  }, [data]);

  const dayOptions: FilterOption[] = [
    { key: "7", label: "7d" },
    { key: "30", label: "30d" },
    ...(isQuick ? [] : [{ key: "90", label: "90d" }]),
  ];

  const families = data?.families ?? [];
  const variants = data?.variants ?? [];
  const sizes = data?.sizes ?? [];
  const showHoneyFilters = family === "honey";

  return (
    <AppSectionEnter index={0} className={className}>
      <div className="space-y-3">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0">
            <p className="text-sm font-semibold text-foreground">Daily stock-ins</p>
            <p className="text-xs text-muted-foreground mt-0.5">
              {data?.filter.label ?? "Finished units scanned in"}
              {data != null && (
                <>
                  {" · "}
                  <span className="tabular-nums font-medium text-foreground">
                    {data.total.toLocaleString()}
                  </span>{" "}
                  total
                  {subtitleHint ? ` · ${subtitleHint}` : ""}
                </>
              )}
            </p>
          </div>
          <div className="flex flex-col items-stretch sm:items-end gap-2">
            <ChipRow
              ariaLabel="Range"
              options={dayOptions}
              value={String(days)}
              onChange={k => setDays(Number(k) as StockInsDays)}
            />
            {isQuick && (
              <Link
                to={trendsHref}
                className="text-xs font-medium text-[#C8900A] hover:underline self-end"
              >
                Open full trends →
              </Link>
            )}
          </div>
        </div>

        {!lockedFamily && (
          <ChipRow
            ariaLabel="Product family"
            options={
              isQuick
                ? families.filter(f => ["all", "honey", "pt", "brc", "jar_cacao"].includes(f.key))
                : families
            }
            value={family}
            onChange={k => {
              setFamily(k as StockInsFamily);
              setVariant("");
              setSize("");
            }}
          />
        )}

        {showHoneyFilters && (
          <div className="space-y-2">
            <ChipRow
              ariaLabel="Honey variant"
              options={variants}
              value={variant}
              onChange={k => {
                setVariant(k);
                // Size-only multi-line is variant-scoped; clearing variant keeps size as optional filter
              }}
            />
            <ChipRow ariaLabel="Honey size" options={sizes} value={size} onChange={setSize} />
          </div>
        )}

        <AppErrorBanner message={error} />

        {loading && !data ? (
          <div className="flex justify-center py-10 text-muted-foreground">
            <Loader2 size={22} className="animate-spin" />
          </div>
        ) : (
          <div className={cn("w-full", isQuick ? "h-44" : "h-60 sm:h-72")}>
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chartRows} margin={{ top: 8, right: 12, left: 0, bottom: 4 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-border" />
                <XAxis
                  dataKey="label"
                  tick={{ fontSize: 10 }}
                  interval={isQuick ? "preserveStartEnd" : days === 7 ? 0 : "equidistantPreserveStart"}
                  minTickGap={isQuick ? 28 : 12}
                />
                <YAxis
                  allowDecimals={false}
                  width={36}
                  tick={{ fontSize: 10 }}
                  className="tabular-nums"
                />
                <Tooltip
                  formatter={(value: number | string, name: string) => {
                    const n = typeof value === "number" ? value : Number(value);
                    const label =
                      seriesList.find(s => s.key === name || s.label === name)?.label ?? name;
                    return [Number.isFinite(n) ? n.toLocaleString() : value, label];
                  }}
                  labelFormatter={(_, payload) => {
                    const row = payload?.[0]?.payload as { date?: string } | undefined;
                    return row?.date ? shortDate(row.date) : "";
                  }}
                  contentStyle={{
                    borderRadius: 8,
                    border: "1px solid hsl(var(--border))",
                    fontSize: 12,
                  }}
                />
                {seriesList.length > 1 && (
                  <Legend
                    verticalAlign="bottom"
                    height={28}
                    iconType="line"
                    wrapperStyle={{ fontSize: 11 }}
                  />
                )}
                {seriesList.map(s => (
                  <Line
                    key={s.key}
                    type="monotone"
                    dataKey={s.key}
                    name={s.label}
                    stroke={s.color}
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4 }}
                    isAnimationActive={!isQuick}
                  />
                ))}
              </LineChart>
            </ResponsiveContainer>
          </div>
        )}

        {!loading && data && data.total === 0 && (
          <p className="text-xs text-muted-foreground text-center -mt-2">
            No stock-ins in this range for the selected filters.
          </p>
        )}
      </div>
    </AppSectionEnter>
  );
}

/** Map family chip to the best inventory product route for “full trends”. */
export function stockInsInventoryHref(family: StockInsFamily | string): string {
  switch (family) {
    case "pt":
      return "/inventory/products/paragis-tea";
    case "brc":
      return "/inventory/products/black-rice-coffee";
    case "jar_cacao":
      return "/inventory/products/cacao-tablets";
    case "honey_comb":
      return "/inventory/products/honey-comb";
    case "honey":
    case "all":
    default:
      return "/inventory/products/honey";
  }
}
