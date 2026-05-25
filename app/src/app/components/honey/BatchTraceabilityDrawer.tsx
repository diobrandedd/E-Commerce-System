import { useEffect, useState } from "react";
import { X, Loader2 } from "lucide-react";
import { apiGet } from "@/app/lib/api";

interface TraceChain {
  batch_no: string;
  warehouse?: Record<string, unknown>;
  production?: Record<string, unknown>;
  packaging?: Record<string, unknown>;
  sales?: unknown[];
}

export function BatchTraceabilityDrawer({ query, onClose }: { query: string; onClose: () => void }) {
  const [data, setData] = useState<{ matches: TraceChain[] } | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const res = await apiGet<{ matches: TraceChain[] }>("honey.php", "traceability", { q: query });
        setData(res);
      } finally {
        setLoading(false);
      }
    })();
  }, [query]);

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <div className="absolute inset-0 bg-black/30" onClick={onClose} />
      <div className="relative w-full max-w-md bg-card border-l h-full overflow-y-auto p-6 shadow-xl">
        <div className="flex justify-between items-center mb-4">
          <h3 className="font-bold">Traceability</h3>
          <button onClick={onClose}><X size={18} /></button>
        </div>
        {loading && <Loader2 className="animate-spin" size={20} />}
        {!loading && data?.matches?.length === 0 && <p className="text-sm text-muted-foreground">No matches.</p>}
        {data?.matches?.map((m, i) => (
          <div key={i} className="mb-6 text-sm space-y-2 border-b pb-4">
            <p className="font-bold text-blue-600">{m.batch_no}</p>
            {m.warehouse && <pre className="text-xs bg-secondary/40 p-2 rounded overflow-auto">{JSON.stringify(m.warehouse, null, 2)}</pre>}
            {m.production && <pre className="text-xs bg-secondary/40 p-2 rounded overflow-auto">{JSON.stringify(m.production, null, 2)}</pre>}
            {m.packaging && <pre className="text-xs bg-secondary/40 p-2 rounded overflow-auto">{JSON.stringify(m.packaging, null, 2)}</pre>}
            {m.sales && m.sales.length > 0 && <p className="text-xs">{m.sales.length} sale(s) logged</p>}
          </div>
        ))}
      </div>
    </div>
  );
}
