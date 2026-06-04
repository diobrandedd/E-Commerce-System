import { useCallback, useEffect, useState } from "react";
import { Loader2, ScanLine, Search } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { parcelsList, parcelsLookup, type OnlineParcel } from "@/app/lib/parcelsApi";
import { ApiError } from "@/app/lib/api";

export function OnlineParcelsPage() {
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [rows, setRows] = useState<OnlineParcel[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [detail, setDetail] = useState<OnlineParcel | null>(null);

  const load = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const res = await parcelsList({ q, status: status || undefined, page, per_page: 25 });
      setRows(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setBusy(false);
    }
  }, [q, status, page]);

  useEffect(() => {
    void load();
  }, [load]);

  const lookup = async () => {
    if (!q.trim()) return;
    setBusy(true);
    setError(null);
    try {
      const res = await parcelsLookup(q.trim());
      setDetail(res.parcel);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Not found.");
      setDetail(null);
    } finally {
      setBusy(false);
    }
  };

  return (
    <PageLayout title="Online parcels" subtitle="Search or scan tracking to view parcel contents">
      <div className="flex flex-wrap gap-2 mb-4 max-w-2xl">
        <div className="flex flex-1 min-w-[200px] gap-2">
          <input
            className="flex-1 rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="Tracking number…"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && void lookup()}
          />
          <button type="button" className="rounded-lg bg-primary px-3 py-2 text-primary-foreground" onClick={() => void lookup()} aria-label="Search">
            <Search className="h-4 w-4" />
          </button>
        </div>
        <select
          className="rounded-lg border border-border px-3 py-2 text-sm"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
        >
          <option value="">All statuses</option>
          <option value="packing">Packing</option>
          <option value="shipped">Shipped</option>
        </select>
        <button type="button" className="text-sm font-semibold text-[#C8900A]" onClick={() => void load()}>
          Refresh list
        </button>
      </div>

      {error && <p className="text-sm text-destructive mb-3">{error}</p>}
      {busy && <Loader2 className="h-5 w-5 animate-spin mb-3" />}

      {detail && (
        <div className="mb-6 max-w-xl rounded-2xl border border-border bg-card p-4">
          <button type="button" className="text-xs text-muted-foreground mb-2" onClick={() => setDetail(null)}>
            Close
          </button>
          <p className="font-mono font-bold">{detail.tracking_number}</p>
          <p className="text-sm text-muted-foreground mt-1">
            {detail.recipient_name} · {detail.courier} · {detail.status}
            {detail.ship_date ? ` · shipped ${detail.ship_date}` : ""}
          </p>
          <p className="mt-3 text-sm whitespace-pre-wrap">{detail.items_raw}</p>
          {detail.items_parsed?.deltas && (
            <ul className="mt-2 text-xs text-muted-foreground list-disc pl-4">
              {detail.items_parsed.deltas.map((d, i) => (
                <li key={i}>
                  {d.qty}× {d.variant} {d.size}
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      <div className="overflow-x-auto rounded-xl border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40">
            <tr>
              <th className="px-3 py-2 text-left">Tracking</th>
              <th className="px-3 py-2 text-left">Recipient</th>
              <th className="px-3 py-2 text-left">Items</th>
              <th className="px-3 py-2 text-left">Status</th>
              <th className="px-3 py-2 text-left">Ship</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr
                key={r.id}
                className="border-t border-border cursor-pointer hover:bg-muted/20"
                onClick={() => setDetail(r)}
              >
                <td className="px-3 py-2 font-mono text-xs">{r.tracking_number}</td>
                <td className="px-3 py-2">{r.recipient_name}</td>
                <td className="px-3 py-2 max-w-xs truncate">{r.items_raw}</td>
                <td className="px-3 py-2">{r.status}</td>
                <td className="px-3 py-2">{r.ship_date ?? "—"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <p className="text-xs text-muted-foreground mt-2">
        {total} parcel{total === 1 ? "" : "s"}
        <ScanLine className="inline h-3 w-3 ml-2" /> Use search with scanned value from label
      </p>
    </PageLayout>
  );
}
