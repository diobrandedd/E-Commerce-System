import { useEffect, useState } from "react";
import { TrendingUp, TrendingDown, FileEdit, RefreshCcw, AlertCircle, Package, Users, Layers, ClipboardEdit } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { DashboardInventorySection } from "@/app/components/dashboard/DashboardInventorySection";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, ApiError } from "@/app/lib/api";

interface EditRequestRow {
  id: number;
  table_name: string;
  record_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  reason: string;
  created_at: string;
  status?: "pending" | "approved" | "rejected";
  rejection_note?: string | null;
  reviewed_at?: string | null;
  requested_by_name?: string;
  dept_name?: string | null;
}

interface StockChangeRow {
  id: number;
  stock_name: string;
  dept_name: string | null;
  old_price: string;
  new_price: string;
  changed_by_name: string | null;
  changed_at: string;
}

interface CostChangeRow {
  id: number;
  product_name: string;
  dept_name: string | null;
  old_cost: string;
  new_cost: string;
  recalculated_at: string;
}

interface DashboardData {
  role: string;
  name: string;
  dept_id: number | null;
  stats: {
    stocks: number;
    products: number;
    active_users: number;
    stock_entries_30d: number;
  };
  pending_edit_requests: EditRequestRow[];
  pending_edit_requests_count: number;
  recent_stock_changes: StockChangeRow[];
  recent_cost_changes: CostChangeRow[];
}

const peso = (v: string | number) => {
  const n = typeof v === "string" ? parseFloat(v) : v;
  if (!Number.isFinite(n)) return "—";
  return `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const fmtDate = (s: string) => {
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

function StatCard({ icon: Icon, label, value, hint }: { icon: React.ElementType; label: string; value: string; hint?: string }) {
  return (
    <div className="bg-card rounded-xl border border-border p-5" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
      <div className="flex items-start justify-between mb-3">
        <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide">{label}</p>
        <Icon size={16} className="text-muted-foreground" />
      </div>
      <p className="text-2xl font-bold text-foreground">{value}</p>
      {hint && <p className="text-xs text-muted-foreground mt-1">{hint}</p>}
    </div>
  );
}

export function DashboardPage() {
  const { user } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<DashboardData>("dashboard.php", "index");
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load dashboard.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    refresh();
  }, []);

  const isExec = user?.role === "executive";
  const showPackagingInventory =
    user?.role === "prod_manager" || user?.role === "executive" || user?.role === "hr";

  return (
    <PageLayout
      title="Dashboard"
      subtitle={`Welcome back, ${user?.name ?? ""}`}
      notificationCount={data?.pending_edit_requests_count ?? 0}
      actions={
        <button
          onClick={refresh}
          className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border"
        >
          <RefreshCcw size={13} /> Refresh
        </button>
      }
    >
      <div className="space-y-6">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />
            <span>{error}</span>
          </div>
        )}

        {showPackagingInventory && (
          <div className="bg-card rounded-xl border border-border p-5 sm:p-6" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <DashboardInventorySection />
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <StatCard
            icon={Package}
            label="Stocks"
            value={loading ? "…" : String(data?.stats.stocks ?? 0)}
            hint={isExec ? "Across all departments" : "In your department"}
          />
          <StatCard
            icon={Layers}
            label="Products"
            value={loading ? "…" : String(data?.stats.products ?? 0)}
            hint={isExec ? "Across all departments" : "In your department"}
          />
          <StatCard
            icon={Users}
            label="Active staff"
            value={loading ? "…" : String(data?.stats.active_users ?? 0)}
            hint="Login enabled"
          />
          <StatCard
            icon={ClipboardEdit}
            label="Entries (30d)"
            value={loading ? "…" : String(data?.stats.stock_entries_30d ?? 0)}
            hint="Stock entries in last 30 days"
          />
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
          {/* Recent stock price changes */}
          <div className="xl:col-span-2 bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <div className="flex items-center justify-between px-6 py-4 border-b border-border">
              <h3 className="text-sm font-semibold text-foreground">Recent stock price changes</h3>
              <span className="text-xs text-muted-foreground">Latest 6</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-secondary/40">
                    {["Stock", "Department", "Old", "New", "By", "When"].map(h => (
                      <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {(data?.recent_stock_changes ?? []).map(r => {
                    const old = parseFloat(r.old_price);
                    const next = parseFloat(r.new_price);
                    const up = Number.isFinite(old) && Number.isFinite(next) && next > old;
                    return (
                      <tr key={r.id} className="border-b border-border last:border-0 hover:bg-secondary/30 transition-colors">
                        <td className="px-6 py-3 font-medium text-foreground">{r.stock_name}</td>
                        <td className="px-4 py-3 text-muted-foreground">{r.dept_name ?? "—"}</td>
                        <td className="px-4 py-3 text-muted-foreground line-through">{peso(r.old_price)}</td>
                        <td className="px-4 py-3 font-semibold text-foreground flex items-center gap-1">
                          {up ? <TrendingUp size={13} className="text-red-500" /> : <TrendingDown size={13} className="text-green-600" />}
                          {peso(r.new_price)}
                        </td>
                        <td className="px-4 py-3 text-muted-foreground">{r.changed_by_name ?? "—"}</td>
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.changed_at)}</td>
                      </tr>
                    );
                  })}
                  {!loading && (data?.recent_stock_changes?.length ?? 0) === 0 && (
                    <tr><td colSpan={6} className="px-6 py-6 text-center text-sm text-muted-foreground">No stock price changes yet.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Edit requests */}
          <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
              <div className="flex items-center gap-2">
                <h3 className="text-sm font-semibold text-foreground">{isExec ? "Pending edit requests" : "Your edit requests"}</h3>
                {(data?.pending_edit_requests_count ?? 0) > 0 && (
                  <span className="w-5 h-5 rounded-full bg-[#C8900A] text-white text-[10px] font-bold flex items-center justify-center">{data?.pending_edit_requests_count}</span>
                )}
              </div>
              <FileEdit size={15} className="text-muted-foreground" />
            </div>
            <div className="divide-y divide-border">
              {(data?.pending_edit_requests ?? []).map(r => (
                <div key={r.id} className="px-5 py-4 hover:bg-secondary/30 transition-colors">
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <p className="text-sm font-medium text-foreground">{isExec ? (r.requested_by_name ?? "—") : `#${r.id}`}</p>
                    <span className="text-[10px] text-muted-foreground whitespace-nowrap">{fmtDate(r.created_at)}</span>
                  </div>
                  <p className="text-xs text-muted-foreground mb-2">
                    {(r.dept_name ?? r.table_name)} · {r.field_name}: <span className="line-through">{r.old_value ?? "—"}</span> → <span className="text-[#C8900A] font-semibold">{r.new_value ?? "—"}</span>
                  </p>
                  <p className="text-xs text-muted-foreground italic">“{r.reason}”</p>
                  {!isExec && r.status === "rejected" && r.rejection_note && (
                    <p className="text-xs text-red-600 mt-2"><strong>Rejected:</strong> {r.rejection_note}</p>
                  )}
                </div>
              ))}
              {!loading && (data?.pending_edit_requests?.length ?? 0) === 0 && (
                <p className="px-5 py-6 text-center text-sm text-muted-foreground">No edit requests yet.</p>
              )}
            </div>
          </div>

          {/* Recent cost recalculations */}
          <div className="xl:col-span-3 bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
            <div className="flex items-center justify-between px-6 py-4 border-b border-border">
              <h3 className="text-sm font-semibold text-foreground">Recent product cost recalculations</h3>
              <span className="text-xs text-muted-foreground">Latest 6</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-border bg-secondary/40">
                    {["Product", "Department", "Old cost", "New cost", "When"].map(h => (
                      <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {(data?.recent_cost_changes ?? []).map(r => {
                    const old = parseFloat(r.old_cost);
                    const next = parseFloat(r.new_cost);
                    const up = Number.isFinite(old) && Number.isFinite(next) && next > old;
                    return (
                      <tr key={r.id} className="border-b border-border last:border-0 hover:bg-secondary/30 transition-colors">
                        <td className="px-6 py-3 font-medium text-foreground">{r.product_name}</td>
                        <td className="px-4 py-3 text-muted-foreground">{r.dept_name ?? "—"}</td>
                        <td className="px-4 py-3 text-muted-foreground line-through">{peso(r.old_cost)}</td>
                        <td className="px-4 py-3 font-semibold text-foreground flex items-center gap-1">
                          {up ? <TrendingUp size={13} className="text-red-500" /> : <TrendingDown size={13} className="text-green-600" />}
                          {peso(r.new_cost)}
                        </td>
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.recalculated_at)}</td>
                      </tr>
                    );
                  })}
                  {!loading && (data?.recent_cost_changes?.length ?? 0) === 0 && (
                    <tr><td colSpan={5} className="px-6 py-6 text-center text-sm text-muted-foreground">No product cost changes yet.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
