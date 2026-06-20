import { TrendingDown, TrendingUp } from "lucide-react";
import { AppSectionEnter, appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { CostChangeRow, StockChangeRow, fmtDate, peso } from "@/app/components/dashboard/dashboardTypes";
import { cn } from "@/app/components/ui/utils";

interface DashboardStockPriceTableProps {
  stockChanges: StockChangeRow[];
  loading: boolean;
}

export function DashboardStockPriceTable({ stockChanges, loading }: DashboardStockPriceTableProps) {
  return (
    <AppSectionEnter index={4} className="xl:col-span-2">
      <div className="bg-card rounded-xl border border-border overflow-hidden h-full" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h3 className="text-sm font-semibold text-foreground">Recent stock price changes</h3>
          <span className="text-xs text-muted-foreground">Latest 6 · Executive only</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border bg-secondary/40">
                {["Stock", "Department", "Old", "New", "By", "When"].map(h => (
                  <th
                    key={h}
                    className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6"
                  >
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {stockChanges.map(r => {
                const old = parseFloat(r.old_price);
                const next = parseFloat(r.new_price);
                const up = Number.isFinite(old) && Number.isFinite(next) && next > old;
                return (
                  <tr key={r.id} className={cn("border-b border-border last:border-0", appHoverRow)}>
                    <td className="px-6 py-3 font-medium text-foreground">{r.stock_name}</td>
                    <td className="px-4 py-3 text-muted-foreground">{r.dept_name ?? "—"}</td>
                    <td className="px-4 py-3 text-muted-foreground line-through">{peso(r.old_price)}</td>
                    <td className="px-4 py-3 font-semibold text-foreground">
                      <span className="inline-flex items-center gap-1">
                        {up ? <TrendingUp size={13} className="text-red-500" /> : <TrendingDown size={13} className="text-green-600" />}
                        {peso(r.new_price)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-muted-foreground">{r.changed_by_name ?? "—"}</td>
                    <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.changed_at)}</td>
                  </tr>
                );
              })}
              {!loading && stockChanges.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-6 py-6 text-center text-sm text-muted-foreground">
                    No stock price changes yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppSectionEnter>
  );
}

interface DashboardCostTableProps {
  costChanges: CostChangeRow[];
  loading: boolean;
}

export function DashboardCostTable({ costChanges, loading }: DashboardCostTableProps) {
  return (
    <AppSectionEnter index={5} className="xl:col-span-3">
      <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h3 className="text-sm font-semibold text-foreground">Recent product cost recalculations</h3>
          <span className="text-xs text-muted-foreground">Latest 6 · Executive only</span>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-border bg-secondary/40">
                {["Product", "Department", "Old cost", "New cost", "When"].map(h => (
                  <th
                    key={h}
                    className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6"
                  >
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {costChanges.map(r => {
                const old = parseFloat(r.old_cost);
                const next = parseFloat(r.new_cost);
                const up = Number.isFinite(old) && Number.isFinite(next) && next > old;
                return (
                  <tr key={r.id} className={cn("border-b border-border last:border-0", appHoverRow)}>
                    <td className="px-6 py-3 font-medium text-foreground">{r.product_name}</td>
                    <td className="px-4 py-3 text-muted-foreground">{r.dept_name ?? "—"}</td>
                    <td className="px-4 py-3 text-muted-foreground line-through">{peso(r.old_cost)}</td>
                    <td className="px-4 py-3 font-semibold text-foreground">
                      <span className="inline-flex items-center gap-1">
                        {up ? <TrendingUp size={13} className="text-red-500" /> : <TrendingDown size={13} className="text-green-600" />}
                        {peso(r.new_cost)}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(r.recalculated_at)}</td>
                  </tr>
                );
              })}
              {!loading && costChanges.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-6 py-6 text-center text-sm text-muted-foreground">
                    No product cost changes yet.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppSectionEnter>
  );
}
