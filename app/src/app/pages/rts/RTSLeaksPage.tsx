import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { ChevronRight, Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import {
  RtsCaseMediaTableCells,
  RtsRegistryDetailModal,
  RTS_MEDIA_TABLE_HEADERS,
  rtsRegistryRowClassName,
} from "@/app/components/rts/RtsCaseMediaUi";
import { FulfillmentErrorBanner } from "@/app/components/csr/onlineFulfillmentMotion";
import { ApiError } from "@/app/lib/api";
import { rtsGetCatalog, rtsLineDisplay, rtsListLeaks, type RtsCaseRow, type RtsProductCatalog } from "@/app/lib/rtsApi";

const PER_PAGE = 15;

export function RTSLeaksPage() {
  const [catalog, setCatalog] = useState<RtsProductCatalog | null>(null);
  const [rows, setRows] = useState<RtsCaseRow[]>([]);
  const [q, setQ] = useState("");
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selected, setSelected] = useState<RtsCaseRow | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [cat, data] = await Promise.all([
        catalog ? Promise.resolve(catalog) : rtsGetCatalog(),
        rtsListLeaks({ q, page, per_page: PER_PAGE }),
      ]);
      if (!catalog) setCatalog(cat);
      setRows(data.cases);
      setTotal(data.total);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load leak cases.");
    } finally {
      setLoading(false);
    }
  }, [catalog, page, q]);

  useEffect(() => {
    void load();
  }, [load]);

  const labels = catalog?.product_labels ?? {};
  const docBase = catalog?.doc_url_base ?? "";

  return (
    <PageLayout
      title="Leaked / damaged"
      subtitle="Damaged returns with product lines — tap a row for details and media"
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

      {loading ? (
        <div className="py-12 text-center text-muted-foreground"><Loader2 className="inline animate-spin" /></div>
      ) : !rows.length ? (
        <p className="text-sm text-muted-foreground py-8 text-center">No leak cases recorded yet.</p>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-border">
          <table className="w-full text-sm">
            <thead className="bg-secondary/40 text-xs uppercase text-muted-foreground">
              <tr>
                <th className="text-left px-3 py-2">Tracking</th>
                <th className="text-left px-3 py-2">Lines</th>
                <th className="text-left px-3 py-2">Opened</th>
                {RTS_MEDIA_TABLE_HEADERS}
                <th className="w-8 px-2" aria-hidden />
              </tr>
            </thead>
            <tbody>
              {rows.map(row => (
                <tr
                  key={row.id}
                  className={rtsRegistryRowClassName()}
                  onClick={() => setSelected(row)}
                >
                  <td className="px-3 py-2 font-mono text-xs">{row.tracking_number}</td>
                  <td className="px-3 py-2 text-xs max-w-[10rem] truncate">
                    {(row.lines ?? []).map((l, i) => (
                      <div key={i} className="truncate">{rtsLineDisplay(l, labels)}</div>
                    ))}
                  </td>
                  <td className="px-3 py-2">{row.date_opened}</td>
                  <RtsCaseMediaTableCells row={row} />
                  <td className="px-2 py-2 text-muted-foreground">
                    <ChevronRight size={16} className="opacity-40" />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {total > PER_PAGE && (
        <div className="flex justify-center gap-2 mt-4">
          <Button type="button" variant="outline" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Previous</Button>
          <span className="text-xs self-center text-muted-foreground">Page {page}</span>
          <Button type="button" variant="outline" disabled={page * PER_PAGE >= total} onClick={() => setPage(p => p + 1)}>Next</Button>
        </div>
      )}

      <RtsRegistryDetailModal
        row={selected}
        catalog={catalog}
        variant="leak"
        onClose={() => setSelected(null)}
      />
    </PageLayout>
  );
}
