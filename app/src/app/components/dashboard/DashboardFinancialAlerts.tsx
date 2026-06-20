import { Link } from "react-router";
import { TrendingDown, TrendingUp } from "lucide-react";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { DashboardSectionShell } from "@/app/components/dashboard/DashboardSectionShell";
import { CostChangeRow, StockChangeRow, fmtDate, peso } from "@/app/components/dashboard/dashboardTypes";
import { cn } from "@/app/components/ui/utils";

const ALERT_LIMIT = 3;

interface DashboardFinancialAlertsProps {
  stockChanges: StockChangeRow[];
  costChanges: CostChangeRow[];
  loading: boolean;
}

function PriceDelta({ oldVal, newVal }: { oldVal: string; newVal: string }) {
  const old = parseFloat(oldVal);
  const next = parseFloat(newVal);
  const up = Number.isFinite(old) && Number.isFinite(next) && next > old;
  return (
    <span className="inline-flex items-center gap-1 text-sm font-semibold text-foreground">
      {up ? <TrendingUp size={12} className="text-red-500" /> : <TrendingDown size={12} className="text-green-600" />}
      <span className="text-muted-foreground line-through text-xs font-normal">{peso(oldVal)}</span>
      {peso(newVal)}
    </span>
  );
}

function AlertColumn({
  title,
  linkTo,
  linkLabel,
  children,
}: {
  title: string;
  linkTo: string;
  linkLabel: string;
  children: React.ReactNode;
}) {
  return (
    <div className="flex flex-col min-h-0">
      <div className="px-4 sm:px-5 py-3 border-b border-border bg-secondary/20 text-center sm:text-left">
        <h3 className="text-xs font-semibold text-foreground uppercase tracking-wide">{title}</h3>
      </div>
      <div className="divide-y divide-border flex-1">{children}</div>
      <div className="px-4 py-2.5 border-t border-border text-center sm:text-right">
        <Link to={linkTo} className="text-[11px] font-medium text-[#C8900A] hover:underline">
          {linkLabel}
        </Link>
      </div>
    </div>
  );
}

export function DashboardFinancialAlerts({ stockChanges, costChanges, loading }: DashboardFinancialAlertsProps) {
  const stocks = stockChanges.slice(0, ALERT_LIMIT);
  const costs = costChanges.slice(0, ALERT_LIMIT);
  const hasAny = stocks.length > 0 || costs.length > 0;

  if (!loading && !hasAny) return null;

  return (
    <DashboardSectionShell
      title="Financial alerts"
      description="Recent material price and product cost moves — executive only"
      index={3}
    >
      <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-border">
        <AlertColumn title="Material prices" linkTo="/suppliers" linkLabel="Supplier master →">
          {loading && (
            <p className="px-5 py-6 text-center text-sm text-muted-foreground">Loading…</p>
          )}
          {!loading && stocks.length === 0 && (
            <p className="px-5 py-6 text-center text-sm text-muted-foreground">No recent changes.</p>
          )}
          {stocks.map(r => (
            <div key={r.id} className={cn("px-4 sm:px-5 py-3.5 text-center sm:text-left", appHoverRow)}>
              <p className="text-sm font-medium text-foreground truncate">{r.stock_name}</p>
              <p className="text-[11px] text-muted-foreground mt-0.5">{r.dept_name ?? "—"} · {fmtDate(r.changed_at)}</p>
              <div className="mt-1.5 flex justify-center sm:justify-start">
                <PriceDelta oldVal={r.old_price} newVal={r.new_price} />
              </div>
            </div>
          ))}
        </AlertColumn>

        <AlertColumn title="Product costs" linkTo="/costing" linkLabel="Product costing →">
          {loading && (
            <p className="px-5 py-6 text-center text-sm text-muted-foreground">Loading…</p>
          )}
          {!loading && costs.length === 0 && (
            <p className="px-5 py-6 text-center text-sm text-muted-foreground">No recent changes.</p>
          )}
          {costs.map(r => (
            <div key={r.id} className={cn("px-4 sm:px-5 py-3.5 text-center sm:text-left", appHoverRow)}>
              <p className="text-sm font-medium text-foreground truncate">{r.product_name}</p>
              <p className="text-[11px] text-muted-foreground mt-0.5">{r.dept_name ?? "—"} · {fmtDate(r.recalculated_at)}</p>
              <div className="mt-1.5 flex justify-center sm:justify-start">
                <PriceDelta oldVal={r.old_cost} newVal={r.new_cost} />
              </div>
            </div>
          ))}
        </AlertColumn>
      </div>
    </DashboardSectionShell>
  );
}
