import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import {
  deliveriesResolveItem,
  deliveriesReviewList,
  deliveriesVoidItem,
  type DeliveryReviewRow,
} from "@/app/lib/deliveriesApi";
import { ApiError } from "@/app/lib/api";

export function DeliveriesReviewPage() {
  const [q, setQ] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [voidOnly, setVoidOnly] = useState(false);
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<DeliveryReviewRow[]>([]);
  const [total, setTotal] = useState(0);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const res = await deliveriesReviewList({ q, from, to, void_only: voidOnly, page, per_page: 10 });
      setRows(res.rows);
      setTotal(res.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setBusy(false);
    }
  }, [q, from, to, voidOnly, page]);

  useEffect(() => {
    void load();
  }, [load]);

  const resolveRow = async (row: DeliveryReviewRow) => {
    const tn = window.prompt("Tracking number for item #" + row.item_id);
    if (!tn?.trim()) return;
    try {
      await deliveriesResolveItem(row.item_id, tn.trim());
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Resolve failed.");
    }
  };

  return (
    <PageLayout
      title="Deliveries review"
      subtitle="Resolve missing tracking or void duplicates"
      actions={
        <Link to="/deliveries" className="text-sm font-semibold text-[#C8900A]">
          ← Add deliveries
        </Link>
      }
    >
      <div className="flex flex-wrap gap-2 mb-4">
        <input
          className="rounded-lg border border-border px-3 py-2 text-sm"
          placeholder="Search tracking…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={from} onChange={(e) => setFrom(e.target.value)} />
        <input type="date" className="rounded-lg border border-border px-2 py-2 text-sm" value={to} onChange={(e) => setTo(e.target.value)} />
        <label className="flex items-center gap-1 text-sm">
          <input type="checkbox" checked={voidOnly} onChange={(e) => setVoidOnly(e.target.checked)} />
          Voided only
        </label>
        <button type="button" className="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground" onClick={() => void load()}>
          Search
        </button>
      </div>

      {error && <p className="text-sm text-destructive mb-3">{error}</p>}
      {busy && <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />}

      <div className="overflow-x-auto rounded-xl border border-border">
        <table className="w-full text-sm">
          <thead className="bg-muted/40">
            <tr>
              <th className="px-3 py-2 text-left">ID</th>
              <th className="px-3 py-2 text-left">Date</th>
              <th className="px-3 py-2 text-left">Tracking</th>
              <th className="px-3 py-2 text-left">Status</th>
              <th className="px-3 py-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.item_id} className="border-t border-border">
                <td className="px-3 py-2">{r.item_id}</td>
                <td className="px-3 py-2">{r.delivery_date}</td>
                <td className="px-3 py-2 font-mono">{r.tracking_number || "—"}</td>
                <td className="px-3 py-2">{r.voided_at ? "voided" : r.item_status}</td>
                <td className="px-3 py-2 space-x-2">
                  {!r.voided_at && (
                    <>
                      <button type="button" className="text-[#C8900A] font-semibold" onClick={() => void resolveRow(r)}>
                        Resolve
                      </button>
                      <button
                        type="button"
                        className="text-destructive font-semibold"
                        onClick={() => void deliveriesVoidItem(r.item_id).then(load)}
                      >
                        Void
                      </button>
                    </>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <p className="text-xs text-muted-foreground mt-2">
        {total} row{total === 1 ? "" : "s"} · page {page}
      </p>
      <div className="flex gap-2 mt-2">
        <button type="button" disabled={page <= 1} className="text-sm" onClick={() => setPage((p) => Math.max(1, p - 1))}>
          Prev
        </button>
        <button type="button" disabled={rows.length < 10} className="text-sm" onClick={() => setPage((p) => p + 1)}>
          Next
        </button>
      </div>
    </PageLayout>
  );
}
