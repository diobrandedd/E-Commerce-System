import { useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { FulfillmentErrorBanner } from "@/app/components/csr/onlineFulfillmentMotion";
import { ApiError } from "@/app/lib/api";
import { rtsDashboard, type RtsDashboardData } from "@/app/lib/rtsApi";

export function RTSDashboardPage() {
  const [data, setData] = useState<RtsDashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void rtsDashboard()
      .then(setData)
      .catch(e => setError(e instanceof ApiError ? e.message : "Could not load dashboard."))
      .finally(() => setLoading(false));
  }, []);

  return (
    <PageLayout
      title="RTS dashboard"
      subtitle="Operational overview"
      actions={
        <div className="flex gap-2 text-xs">
          <Link to="/rts/records" className="text-[#C8900A] underline self-center">All records</Link>
          <Link to="/rts/record" className="font-semibold text-white px-3 py-1.5 rounded-lg" style={{ background: "#C8900A" }}>Record RTS</Link>
        </div>
      }
    >
      <FulfillmentErrorBanner message={error} />

      {loading || !data ? (
        <div className="py-12 text-center text-muted-foreground"><Loader2 className="inline animate-spin" /></div>
      ) : (
        <div className="space-y-6">
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total cases" value={data.total_cases} />
            <StatCard label="Last 30 days" value={data.cases_last_30} />
            <StatCard label="This month" value={data.cases_this_month} />
            <StatCard label="VIP share" value={`${data.vip_pct}%`} sub={`${data.vip_count} VIP`} />
          </div>

          <div className="grid gap-4 lg:grid-cols-2">
            <BreakdownCard title="By shop" items={data.by_shop} />
            <BreakdownCard title="By courier" items={data.by_courier} />
          </div>

          <section>
            <div className="flex justify-between items-center mb-2">
              <h3 className="text-sm font-semibold">Recent cases</h3>
              <Link to="/rts/records?damage=all_damaged" className="text-xs text-[#C8900A] underline">View damaged →</Link>
            </div>
            <div className="bg-card rounded-xl border overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
                    <th className="px-3 py-2 text-left">Tracking</th>
                    <th className="px-3 py-2 text-left">Shop</th>
                    <th className="px-3 py-2 text-left">Courier</th>
                    <th className="px-3 py-2 text-left">Opened</th>
                  </tr>
                </thead>
                <tbody>
                  {data.recent.map(r => (
                    <tr key={r.id} className="border-b">
                      <td className="px-3 py-2">
                        <Link to={`/rts/records?q=${encodeURIComponent(r.tracking_number)}`} className="font-mono text-xs text-[#C8900A] underline">
                          {r.tracking_number}
                        </Link>
                      </td>
                      <td className="px-3 py-2">{r.shop}</td>
                      <td className="px-3 py-2">{r.courier_final}</td>
                      <td className="px-3 py-2">{r.date_opened}{r.voided_at ? " (voided)" : ""}</td>
                    </tr>
                  ))}
                  {!data.recent.length && (
                    <tr><td colSpan={4} className="px-3 py-8 text-center text-muted-foreground">Nothing recorded yet. <Link to="/rts/record" className="text-[#C8900A] underline">Record RTS</Link>.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>

          {data.timeline.length > 0 && (
            <section>
              <h3 className="text-sm font-semibold mb-2">Cases opened (14 days)</h3>
              <div className="flex items-end gap-1 h-24">
                {data.timeline.map(t => {
                  const max = Math.max(...data.timeline.map(x => x.cnt), 1);
                  const h = Math.round((t.cnt / max) * 100);
                  return (
                    <div key={t.d} className="flex-1 flex flex-col items-center gap-1" title={`${t.d}: ${t.cnt}`}>
                      <div className="w-full rounded-t bg-[#C8900A]/70" style={{ height: `${h}%`, minHeight: t.cnt ? 4 : 0 }} />
                      <span className="text-[9px] text-muted-foreground rotate-[-45deg] origin-top-left">{t.d.slice(5)}</span>
                    </div>
                  );
                })}
              </div>
            </section>
          )}
        </div>
      )}
    </PageLayout>
  );
}

function StatCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
  return (
    <div className="rounded-xl border border-border bg-card p-4">
      <p className="text-xs text-muted-foreground">{label}</p>
      <p className="text-2xl font-bold mt-1">{value}</p>
      {sub && <p className="text-xs text-muted-foreground mt-0.5">{sub}</p>}
    </div>
  );
}

function BreakdownCard({ title, items }: { title: string; items: Record<string, number> }) {
  const entries = Object.entries(items);
  const total = entries.reduce((s, [, n]) => s + n, 0) || 1;
  return (
    <div className="rounded-xl border border-border bg-card p-4">
      <h3 className="text-sm font-semibold mb-3">{title}</h3>
      <ul className="space-y-2 text-sm">
        {entries.map(([k, n]) => (
          <li key={k} className="flex justify-between gap-2">
            <span>{k}</span>
            <span className="text-muted-foreground">{n} ({Math.round(100 * n / total)}%)</span>
          </li>
        ))}
        {!entries.length && <li className="text-muted-foreground">No data</li>}
      </ul>
    </div>
  );
}
