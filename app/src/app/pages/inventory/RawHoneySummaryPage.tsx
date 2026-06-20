import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { fetchRawSnapshot } from "@/app/lib/materials";

export function RawHoneySummaryPage() {
  const [rows, setRows] = useState<Array<Record<string, unknown>>>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const snap = await fetchRawSnapshot();
      setRows(snap.honey.rows);
      setTotal(snap.honey.total_gallons);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);

  return (
    <PageLayout
      title="Raw Honey"
      subtitle={`Warehouse gallons — ${total.toLocaleString()} gal total on hand`}
      actions={
        <Link
          to="/inventory/products/honey/warehouse"
          className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
        >
          Stock in (Products → Warehouse)
        </Link>
      }
    >
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40">
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Supplier</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Variant</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">On hand</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground hidden sm:table-cell">Arrival</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={4} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {!loading && rows.length === 0 && (
              <tr>
                <td colSpan={4} className="py-8 text-center text-muted-foreground text-sm">No warehouse rows.</td>
              </tr>
            )}
            {!loading && rows.map(r => (
              <tr key={String(r.id)} className="border-b">
                <td className="px-4 py-3 font-medium">{String(r.supplier_code ?? "—")}</td>
                <td className="px-4 py-3 capitalize">{String(r.variant ?? "—")}</td>
                <td className="px-4 py-3">
                  <QtyCounter current={String(r.current_qty)} initial={String(r.initial_qty)} />
                </td>
                <td className="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell">
                  {r.arrival_date ? new Date(String(r.arrival_date).replace(" ", "T")).toLocaleDateString() : "—"}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </PageLayout>
  );
}
