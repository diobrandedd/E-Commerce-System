import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { FulfillmentErrorBanner, fulfillmentHoverRow } from "@/app/components/csr/onlineFulfillmentMotion";
import { cn } from "@/app/components/ui/utils";
import { ApiError } from "@/app/lib/api";
import { rtsGetCatalog, rtsListSwapped, type RtsCaseRow, type RtsProductCatalog } from "@/app/lib/rtsApi";

const PER_PAGE = 15;

function RegistryTable({
  rows,
  catalog,
  loading,
}: {
  rows: RtsCaseRow[];
  catalog: RtsProductCatalog | null;
  loading: boolean;
}) {
  const docBase = catalog?.doc_url_base ?? "";

  if (loading) {
    return <div className="py-12 text-center text-muted-foreground"><Loader2 className="inline animate-spin" /></div>;
  }
  if (!rows.length) {
    return <p className="text-sm text-muted-foreground py-8 text-center">No swapped parcels recorded yet.</p>;
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-border">
      <table className="w-full text-sm">
        <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
          <tr>
            <th className="text-left px-4 py-2">Tracking</th>
            <th className="text-left px-4 py-2">Shop</th>
            <th className="text-left px-4 py-2">Courier</th>
            <th className="text-left px-4 py-2">Opened</th>
            <th className="text-left px-4 py-2">Before shipment</th>
            <th className="text-left px-4 py-2">RTS media</th>
          </tr>
        </thead>
        <tbody>
          {rows.map(row => (
            <tr key={row.id} className={cn("border-t border-border", fulfillmentHoverRow)}>
              <td className="px-4 py-2 font-mono text-xs">
                <Link to={`/rts/records?q=${encodeURIComponent(row.tracking_number)}`} className="text-[#C8900A] underline">
                  {row.tracking_number}
                </Link>
              </td>
              <td className="px-4 py-2">{row.shop}</td>
              <td className="px-4 py-2">{row.courier_final}</td>
              <td className="px-4 py-2">{row.date_opened}</td>
              <td className="px-4 py-2">
                {row.before_shipment?.available && row.before_shipment.download_url ? (
                  <a href={row.before_shipment.download_url} target="_blank" rel="noreferrer" className="text-[#C8900A] underline text-xs">Download</a>
                ) : (
                  <span className="text-xs text-muted-foreground">N/A</span>
                )}
              </td>
              <td className="px-4 py-2 text-xs">
                {(row.documentation_attachments ?? []).map((a, i) => (
                  <a key={i} href={`${docBase}${a.file}`} target="_blank" rel="noreferrer" className="text-[#C8900A] underline mr-2">
                    {a.kind}
                  </a>
                ))}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export function RTSSwappedPage() {
  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [rows, setRows] = useState<RtsCaseRow[]>([]);
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [cat, data] = await Promise.all([
        catalog ? Promise.resolve(catalog) : rtsGetCatalog(),
        rtsListSwapped({ q, page, per_page: PER_PAGE }),
      ]);
      if (!catalog) setCatalog(cat);
      setRows(data.cases);
      setTotal(data.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load swapped parcels.");
    } finally {
      setLoading(false);
    }
  }, [catalog, page, q]);

  useEffect(() => {
    void load();
  }, [load]);

  return (
    <PageLayout
      title="Swapped parcels"
      subtitle="Wrong or replaced contents — media registry only (not sellable RTS stock)"
      actions={<Link to="/rts/record" className="text-xs text-[#C8900A] underline">Record RTS</Link>}
    >
      <FulfillmentErrorBanner message={error} />
      <div className="flex gap-2 mb-4 max-w-md">
        <input
          value={q}
          onChange={e => { setQ(e.target.value); setPage(1); }}
          placeholder="Search tracking…"
          className="flex-1 border rounded-lg px-3 py-2 text-sm"
        />
        <Button type="button" variant="outline" onClick={() => void load()}>Search</Button>
      </div>
      <RegistryTable rows={rows} catalog={catalog} loading={loading} />
      {total > PER_PAGE && (
        <div className="flex justify-center gap-2 mt-4">
          <Button type="button" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Previous</Button>
          <span className="text-xs self-center text-muted-foreground">Page {page}</span>
          <Button type="button" variant="outline" disabled={page * PER_PAGE >= total} onClick={() => setPage(p => p + 1)}>Next</Button>
        </div>
      )}
    </PageLayout>
  );
}
