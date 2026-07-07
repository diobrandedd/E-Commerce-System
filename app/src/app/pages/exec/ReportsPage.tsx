import { useCallback, useEffect, useMemo, useState } from "react";
import { PieChart as PieChartIcon, AlertCircle, Loader2, RefreshCcw } from "lucide-react";
import { PieChart, Pie, Tooltip, ResponsiveContainer, Legend, Cell } from "recharts";
import { PageLayout } from "@/app/components/PageLayout";
import { apiGet, ApiError } from "@/app/lib/api";

type CourierFilterMode = "date_range" | "month" | "year";
type ParcelStatus = "packing" | "shipped" | "on_hold" | "cancelled" | "returned" | "rts";

interface CourierChartRow {
  key: "jnt" | "spx" | "flash";
  name: string;
  count: number;
  percent: number;
}

interface CourierSummaryPayload {
  filter_mode: CourierFilterMode;
  status: string;
  total_shipments: number;
  counts: {
    jnt: number;
    spx: number;
    flash: number;
  };
  chart: CourierChartRow[];
}

interface DeptSummaryRow {
  dept_id: number;
  dept_name: string;
  stocks: number;
  products: number;
  active_users: number;
  total_inventory_kg: number;
}

const COURIER_COLORS: Record<"jnt" | "spx" | "flash", string> = {
  jnt: "#C8900A",
  spx: "#3366CC",
  flash: "#F97316",
};

const STATUS_OPTIONS: Array<{ value: "" | ParcelStatus; label: string }> = [
  { value: "", label: "All statuses" },
  { value: "packing", label: "packing" },
  { value: "shipped", label: "shipped" },
  { value: "on_hold", label: "on_hold" },
  { value: "cancelled", label: "cancelled" },
  { value: "returned", label: "returned" },
  { value: "rts", label: "rts" },
];

const now = new Date();
const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
const defaultYear = String(now.getFullYear());
const defaultDateTo = now.toISOString().slice(0, 10);
const sevenDaysAgo = new Date(now);
sevenDaysAgo.setDate(now.getDate() - 6);
const defaultDateFrom = sevenDaysAgo.toISOString().slice(0, 10);

const pctLabel = (n: number) => `${n.toFixed(1)}%`;

const CourierTooltip = ({ active, payload }: { active?: boolean; payload?: Array<{ payload: CourierChartRow }> }) => {
  if (!active || !payload || payload.length === 0) return null;
  const row = payload[0].payload;
  return (
    <div className="rounded-lg border border-border bg-card px-3 py-2 text-xs shadow-sm">
      <p className="font-semibold text-foreground">{row.name}</p>
      <p className="text-muted-foreground">Shipments: {row.count.toLocaleString()}</p>
      <p className="text-muted-foreground">Percentage: {pctLabel(row.percent)}</p>
    </div>
  );
};

export function ReportsPage() {
  const [filterMode, setFilterMode] = useState<CourierFilterMode>("month");
  const [dateFrom, setDateFrom] = useState(defaultDateFrom);
  const [dateTo, setDateTo] = useState(defaultDateTo);
  const [month, setMonth] = useState(defaultMonth);
  const [year, setYear] = useState(defaultYear);
  const [status, setStatus] = useState<"" | ParcelStatus>("");
  const [courierSummary, setCourierSummary] = useState<CourierSummaryPayload | null>(null);
  const [deptSummary, setDeptSummary] = useState<DeptSummaryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const query = useMemo(() => {
    const params: Record<string, string> = { filter_mode: filterMode };
    if (status) params.status = status;
    if (filterMode === "date_range") {
      if (dateFrom) params.date_from = dateFrom;
      if (dateTo) params.date_to = dateTo;
    } else if (filterMode === "month") {
      params.month = month;
    } else {
      params.year = year;
    }
    return params;
  }, [dateFrom, dateTo, filterMode, month, status, year]);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [c, d] = await Promise.all([
        apiGet<CourierSummaryPayload>("reports.php", "courier_summary", query),
        apiGet<{ rows: DeptSummaryRow[] }>("reports.php", "dept_summary"),
      ]);
      setCourierSummary(c);
      setDeptSummary(d.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load reports.");
    } finally {
      setLoading(false);
    }
  }, [query]);

  useEffect(() => { load(); }, [load]);

  return (
    <PageLayout
      title="Inventory Reports"
      subtitle="Courier shipment analytics and department snapshot"
      actions={
        <button onClick={() => load()} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
          <RefreshCcw size={13} /> Refresh
        </button>
      }
    >
      <div className="space-y-6">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}

        <div className="flex flex-wrap items-center gap-2">
          {([
            { key: "date_range", label: "Date range" },
            { key: "month", label: "Month" },
            { key: "year", label: "Year" },
          ] as const).map(mode => (
            <button
              key={mode.key}
              onClick={() => setFilterMode(mode.key)}
              className={`text-sm font-medium px-4 py-2 rounded-lg transition-colors ${filterMode === mode.key ? "text-white" : "text-muted-foreground hover:bg-secondary"}`}
              style={filterMode === mode.key ? { background: "#C8900A" } : {}}
            >
              {mode.label}
            </button>
          ))}
        </div>

        <div className="bg-card rounded-xl border border-border p-6" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center gap-2 mb-5">
            <PieChartIcon size={16} style={{ color: "#C8900A" }} />
            <h3 className="text-sm font-semibold text-foreground">Courier Statistics</h3>
          </div>
          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4 mb-5">
            {filterMode === "date_range" && (
              <>
                <label className="text-xs text-muted-foreground">
                  Date from
                  <input
                    type="date"
                    value={dateFrom}
                    onChange={e => setDateFrom(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                  />
                </label>
                <label className="text-xs text-muted-foreground">
                  Date to
                  <input
                    type="date"
                    value={dateTo}
                    onChange={e => setDateTo(e.target.value)}
                    className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                  />
                </label>
              </>
            )}
            {filterMode === "month" && (
              <label className="text-xs text-muted-foreground">
                Month
                <input
                  type="month"
                  value={month}
                  onChange={e => setMonth(e.target.value)}
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                />
              </label>
            )}
            {filterMode === "year" && (
              <label className="text-xs text-muted-foreground">
                Year
                <input
                  type="number"
                  min="2000"
                  max="2099"
                  value={year}
                  onChange={e => setYear(e.target.value)}
                  className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                />
              </label>
            )}
            <label className="text-xs text-muted-foreground">
              Status (optional)
              <select
                value={status}
                onChange={e => setStatus(e.target.value as "" | ParcelStatus)}
                className="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
              >
                {STATUS_OPTIONS.map(opt => (
                  <option key={opt.label} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </label>
          </div>

          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4 mb-5">
            <SummaryCard label="Total shipments" value={courierSummary?.total_shipments ?? 0} />
            <SummaryCard label="J&T Express" value={courierSummary?.counts.jnt ?? 0} />
            <SummaryCard label="SPX Express" value={courierSummary?.counts.spx ?? 0} />
            <SummaryCard label="Flash Express" value={courierSummary?.counts.flash ?? 0} />
          </div>

          {loading ? (
            <div className="h-[320px] flex items-center justify-center text-sm text-muted-foreground">
              <Loader2 className="animate-spin mr-2" size={14} /> Loading…
            </div>
          ) : !courierSummary || courierSummary.total_shipments <= 0 ? (
            <div className="h-[320px] flex items-center justify-center text-sm text-muted-foreground">
              No courier shipments for the selected filters.
            </div>
          ) : (
            <ResponsiveContainer width="100%" height={320}>
              <PieChart>
                <Pie
                  data={courierSummary.chart}
                  dataKey="count"
                  nameKey="name"
                  cx="50%"
                  cy="50%"
                  outerRadius={110}
                  label={entry => `${entry.name}: ${pctLabel(Number(entry.percent ?? 0))}`}
                >
                  {courierSummary.chart.map(entry => (
                    <Cell key={entry.key} fill={COURIER_COLORS[entry.key]} />
                  ))}
                </Pie>
                <Tooltip content={<CourierTooltip />} />
                <Legend wrapperStyle={{ fontSize: 12 }} />
              </PieChart>
            </ResponsiveContainer>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Department snapshot</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Department", "Stocks", "Products", "Active users", "Inventory on hand"].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={5} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && deptSummary.length === 0 && (
                  <tr><td colSpan={5} className="px-6 py-8 text-center text-sm text-muted-foreground">No departments to show.</td></tr>
                )}
                {!loading && deptSummary.map((r, i) => (
                  <tr key={r.dept_id} className={`border-b border-border last:border-0 hover:bg-secondary/30 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3.5 font-semibold text-foreground">{r.dept_name}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">{Number(r.stocks).toLocaleString()}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">{Number(r.products).toLocaleString()}</td>
                    <td className="px-5 py-3.5 text-muted-foreground">{Number(r.active_users).toLocaleString()}</td>
                    <td className="px-5 py-3.5 font-bold text-foreground">{Number(r.total_inventory_kg).toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}

function SummaryCard({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-lg border border-border bg-background px-4 py-3">
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="text-xl font-semibold text-foreground mt-1">{value.toLocaleString()}</p>
    </div>
  );
}
