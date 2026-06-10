import { useCallback, useEffect, useState } from "react";
import { useParams } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { fetchRawDetail } from "@/app/lib/materials";

const LABELS: Record<string, string> = {
  paragis: "Dried Paragis",
  "black-rice": "Black Rice",
  cacao: "Cacao Beans",
};

export function RawMaterialDetailPage() {
  const { slug } = useParams();
  const key = slug ?? "";
  const [stock, setStock] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    if (!key) return;
    setLoading(true);
    try {
      const data = await fetchRawDetail(key);
      setStock(data.stock);
    } finally {
      setLoading(false);
    }
  }, [key]);

  useEffect(() => { void refresh(); }, [refresh]);

  const title = LABELS[key] ?? "Raw material";

  return (
    <PageLayout title={title} subtitle="Costing / stock-in log for supplier raw bags">
      {loading ? (
        <div className="py-12 text-center">
          <Loader2 className="inline animate-spin" size={18} />
        </div>
      ) : !stock ? (
        <p className="text-sm text-muted-foreground">No stock row found.</p>
      ) : (
        <div className="rounded-xl border bg-card p-6 space-y-3 max-w-lg">
          <div>
            <p className="text-xs uppercase text-muted-foreground">Stock name</p>
            <p className="font-semibold">{String(stock.name)}</p>
          </div>
          <div>
            <p className="text-xs uppercase text-muted-foreground">Department</p>
            <p>{String(stock.dept_name ?? "—")}</p>
          </div>
          <div>
            <p className="text-xs uppercase text-muted-foreground">Entries sum</p>
            <p className="text-lg font-semibold">
              {String(stock.entries_sum ?? stock.on_hand ?? 0)} {String(stock.unit ?? "bag")}
            </p>
          </div>
          <div>
            <p className="text-xs uppercase text-muted-foreground">Last stock entry</p>
            <p className="text-sm text-muted-foreground">
              {stock.last_entry_at
                ? new Date(String(stock.last_entry_at).replace(" ", "T")).toLocaleString()
                : "—"}
            </p>
          </div>
          <p className="text-xs text-muted-foreground pt-2">
            Finished sellable units are tracked under Products. Stock-in bags via the product inventory page credits pouches/jars on hand.
          </p>
        </div>
      )}
    </PageLayout>
  );
}
