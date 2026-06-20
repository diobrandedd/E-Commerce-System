import { useCallback, useEffect, useMemo, useState } from "react";
import { BarChart2, AlertCircle, Loader2, RefreshCcw } from "lucide-react";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from "recharts";
import { PageLayout } from "@/app/components/PageLayout";
import { apiGet, ApiError } from "@/app/lib/api";

type Period = "daily" | "weekly" | "monthly";

interface StockMovementRow {
  dept_id: number;
  dept_name: string;
  period: Period;
  stock_in: number;
  stock_out: number;
  deductions: number;
  balance: number;
}

interface ChartRow {
  bucket: string;
  [series: string]: number | string;
}

interface MovementPayload {
  rows: StockMovementRow[];
  chart: ChartRow[];
}

interface DeptSummaryRow {
  dept_id: number;
  dept_name: string;
  stocks: number;
  products: number;
  active_users: number;
  total_inventory_kg: number;
}

const DEPT_COLORS: Record<string, string> = {
  Honey: "#C8900A",
  Paragis: "#2a8a4a",
  "Cacao/BRC": "#6b4c2a",
  CSR: "#7280a3",
};

const colorFor = (name: string, idx: number) => {
  const palette = ["#C8900A", "#2a8a4a", "#6b4c2a", "#7280a3", "#9c3d6a"];
  return DEPT_COLORS[name] ?? palette[idx % palette.length];
};

export function ReportsPage() {
  const [period, setPeriod] = useState<Period>("monthly");
  const [movement, setMovement] = useState<MovementPayload | null>(null);
  const [deptSummary, setDeptSummary] = useState<DeptSummaryRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (p: Period) => {
    setLoading(true);
    setError(null);
    try {
      const [m, d] = await Promise.all([
        apiGet<MovementPayload>("reports.php", "stock_movement", { period: p }),
        apiGet<{ rows: DeptSummaryRow[] }>("reports.php", "dept_summary"),
      ]);
      setMovement(m);
      setDeptSummary(d.rows);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load reports.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(period); }, [load, period]);

  const seriesNames = useMemo(() => {
    if (!movement) return [] as string[];
    const set = new Set<string>();
    movement.chart.forEach(row => Object.keys(row).forEach(k => k !== "bucket" && set.add(k)));
    return Array.from(set);
  }, [movement]);

  return (
    <PageLayout
      title="Inventory Reports"
      subtitle="Cross-department stock movement overview"
      actions={
        <button onClick={() => load(period)} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
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

        <div className="flex items-center gap-2">
          {(["daily", "weekly", "monthly"] as const).map(p => (
            <button
              key={p}
              onClick={() => setPeriod(p)}
              className={`text-sm font-medium px-4 py-2 rounded-lg capitalize transition-colors ${period === p ? "text-white" : "text-muted-foreground hover:bg-secondary"}`}
              style={period === p ? { background: "#C8900A" } : {}}
            >
              {p}
            </button>
          ))}
        </div>

        <div className="bg-card rounded-xl border border-border p-6" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center gap-2 mb-5">
            <BarChart2 size={16} style={{ color: "#C8900A" }} />
            <h3 className="text-sm font-semibold text-foreground">Stock Movement by Department</h3>
          </div>
          {loading ? (
            <div className="h-[260px] flex items-center justify-center text-sm text-muted-foreground">
              <Loader2 className="animate-spin mr-2" size={14} /> Loading…
            </div>
          ) : !movement || movement.chart.length === 0 ? (
            <div className="h-[260px] flex items-center justify-center text-sm text-muted-foreground">No stock movement in this period.</div>
          ) : (
            <ResponsiveContainer width="100%" height={260}>
              <BarChart data={movement.chart} barGap={4}>
                <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                <XAxis dataKey="bucket" tick={{ fontSize: 12 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 12 }} axisLine={false} tickLine={false} />
                <Tooltip contentStyle={{ borderRadius: 8, border: "1px solid rgba(0,0,0,0.1)", fontSize: 12 }} />
                <Legend wrapperStyle={{ fontSize: 12 }} />
                {seriesNames.map((name, idx) => (
                  <Bar key={name} dataKey={name} fill={colorFor(name, idx)} radius={[4, 4, 0, 0]} />
                ))}
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Summary Table — {period.charAt(0).toUpperCase() + period.slice(1)}</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Department", "Period", "Stock In", "Stock Out", "Deductions", "Net balance"].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && movement && movement.rows.length === 0 && (
                  <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-muted-foreground">No movement recorded.</td></tr>
                )}
                {!loading && movement && movement.rows.map((r, i) => (
                  <tr key={r.dept_id} className={`border-b border-border last:border-0 hover:bg-secondary/30 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3.5 font-semibold text-foreground">{r.dept_name}</td>
                    <td className="px-5 py-3.5 text-muted-foreground capitalize">{r.period}</td>
                    <td className="px-5 py-3.5 text-green-600 font-semibold">+{Number(r.stock_in).toLocaleString()}</td>
                    <td className="px-5 py-3.5 text-red-500 font-semibold">-{Number(r.stock_out).toLocaleString()}</td>
                    <td className="px-5 py-3.5 text-amber-600 font-semibold">-{Number(r.deductions).toLocaleString()}</td>
                    <td className="px-5 py-3.5 font-bold text-foreground">{Number(r.balance).toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
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
