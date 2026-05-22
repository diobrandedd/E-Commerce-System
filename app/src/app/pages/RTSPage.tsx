import { useEffect, useState } from "react";
import { ExternalLink, RefreshCcw, AlertCircle, Loader2, RotateCcw } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { useAuth } from "@/app/context/AuthContext";
import { apiGet, ApiError } from "@/app/lib/api";

interface PoolRow {
  product_type: string;
  honey_size: string | null;
  honey_variant: string | null;
  total_qty: string;
  line_count: string;
}

interface RecentItem {
  line_id: number;
  case_id: number;
  product_type: string;
  honey_size: string | null;
  honey_variant: string | null;
  combo_product: string | null;
  quantity: string;
  is_bonus: number;
  created_at: string;
  tracking_number: string;
  shop: string;
  courier_final: string;
  claim_status: string;
  date_opened: string;
  damaged_tag: string;
}

interface LegacyUrls { records: string; create: string; dashboard: string }

const fmtDate = (s: string) => {
  const d = new Date(s.replace(" ", "T"));
  if (Number.isNaN(d.getTime())) return s;
  return d.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
};

const productLabel = (row: { product_type: string; honey_size: string | null; honey_variant: string | null }) => {
  let label = row.product_type.replace(/_/g, " ");
  if (row.honey_size) label += ` · ${row.honey_size}`;
  if (row.honey_variant) label += ` · ${row.honey_variant}`;
  return label;
};

export function RTSPage() {
  const { user } = useAuth();
  const isExec = user?.role === "executive";
  const canRecord = user?.role === "csr_staff" || user?.role === "csr_tl";

  const [pool, setPool] = useState<PoolRow[]>([]);
  const [recent, setRecent] = useState<RecentItem[]>([]);
  const [legacy, setLegacy] = useState<LegacyUrls | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const [poolPayload, recentPayload, urls] = await Promise.all([
        apiGet<{ pool: PoolRow[] }>("rts.php", "pool"),
        apiGet<{ items: RecentItem[] }>("rts.php", "recent"),
        apiGet<LegacyUrls>("rts.php", "legacy_urls"),
      ]);
      setPool(poolPayload.pool);
      setRecent(recentPayload.items);
      setLegacy(urls);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load RTS data.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  return (
    <PageLayout
      title="RTS Overview"
      subtitle="Return-to-Seller pool, recent items, and legacy admin links"
      actions={
        <div className="flex items-center gap-2">
          <button onClick={refresh} className="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground px-3 py-1.5 rounded-lg border border-border">
            <RefreshCcw size={13} /> Refresh
          </button>
        </div>
      }
    >
      <div className="space-y-6">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}

        <div className="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-start gap-3">
          <RotateCcw size={18} className="text-amber-700 mt-0.5" />
          <div className="flex-1">
            <p className="text-sm font-semibold text-amber-900">Legacy RTS still active</p>
            <p className="text-xs text-amber-800 mt-1">
              Case creation, reuse logging, and claim status are still managed in the existing RTS pages.
              The SPA shows live pool balances pulled from <code>rts_line_items</code>.
            </p>
            <div className="flex flex-wrap gap-2 mt-3">
              {legacy?.records && (
                <a href={legacy.records} className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-amber-200 hover:border-amber-400 transition-colors">
                  <ExternalLink size={12} /> RTS Records
                </a>
              )}
              {canRecord && legacy?.create && (
                <a href={legacy.create} className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-700 text-white hover:bg-amber-800 transition-colors">
                  <ExternalLink size={12} /> Record RTS parcel
                </a>
              )}
              {legacy?.dashboard && isExec && (
                <a href={legacy.dashboard} className="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border border-amber-200 hover:border-amber-400 transition-colors">
                  <ExternalLink size={12} /> RTS Dashboard
                </a>
              )}
            </div>
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-foreground mb-3">Current RTS pool balance</h3>
          {loading ? (
            <div className="bg-card rounded-xl border border-border px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} />Loading…</div>
          ) : pool.length === 0 ? (
            <div className="bg-card rounded-xl border border-border px-6 py-8 text-center text-sm text-muted-foreground">No RTS items found.</div>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {pool.map((p, i) => {
                const total = parseFloat(p.total_qty);
                return (
                  <div key={i} className="bg-card rounded-xl border border-border p-4" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
                    <div className="flex items-center justify-between mb-2">
                      <p className="font-semibold text-foreground capitalize">{p.product_type.replace(/_/g, " ")}</p>
                      <span className="text-xl font-bold text-amber-600">{total.toLocaleString()}</span>
                    </div>
                    {(p.honey_size || p.honey_variant) && (
                      <p className="text-xs text-muted-foreground mb-2">{[p.honey_size, p.honey_variant].filter(Boolean).join(" · ")}</p>
                    )}
                    <p className="text-xs text-muted-foreground">{p.line_count} line{p.line_count === "1" ? "" : "s"}</p>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center justify-between">
            <h3 className="text-sm font-semibold text-foreground">Recent RTS items</h3>
            <span className="text-xs text-muted-foreground">Last 50</span>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Tracking", "Shop / courier", "Product", "Qty", "Claim status", "Damaged", "Recorded"].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-4 py-2.5 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={7} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading…</td></tr>
                )}
                {!loading && recent.length === 0 && (
                  <tr><td colSpan={7} className="px-6 py-8 text-center text-sm text-muted-foreground">No recent RTS line items.</td></tr>
                )}
                {recent.map(item => (
                  <tr key={item.line_id} className="border-b border-border last:border-0 hover:bg-secondary/30 transition-colors">
                    <td className="px-6 py-3 font-mono text-xs">{item.tracking_number}</td>
                    <td className="px-4 py-3 text-muted-foreground capitalize">{item.shop} · {item.courier_final}</td>
                    <td className="px-4 py-3 text-foreground">{productLabel(item)}</td>
                    <td className="px-4 py-3 font-semibold text-foreground">{parseFloat(item.quantity).toLocaleString()}</td>
                    <td className="px-4 py-3"><span className="inline-block text-xs px-2 py-0.5 rounded-full bg-secondary text-muted-foreground capitalize">{item.claim_status.replace(/_/g, " ")}</span></td>
                    <td className="px-4 py-3 text-muted-foreground capitalize">{item.damaged_tag === "no" ? "—" : item.damaged_tag}</td>
                    <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">{fmtDate(item.created_at)}</td>
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
