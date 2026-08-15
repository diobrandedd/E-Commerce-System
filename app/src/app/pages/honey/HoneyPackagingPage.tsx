import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { BatchTraceabilityDrawer } from "@/app/components/honey/BatchTraceabilityDrawer";
import { PackagingScanLogModal } from "@/app/components/honey/PackagingScanLogModal";
import { appHoverRow } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet } from "@/app/lib/api";
import { HONEY_SIZES } from "@/app/lib/honey";
import { canExecutiveDirectEdit, canViewSupplierProfile } from "@/app/lib/honeyPermissions";

interface PkRow {
  id: number;
  supplier_code: string;
  batch_no: string;
  bottling_date: string;
  variant: string;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
}

export function HoneyPackagingPage() {
  const { user } = useAuth();
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const showSupplierProfile = canViewSupplierProfile(user?.role);
  const [rows, setRows] = useState<PkRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [traceBatch, setTraceBatch] = useState<string | null>(null);
  const [scanLogRow, setScanLogRow] = useState<PkRow | null>(null);
  const [editRow, setEditRow] = useState<PkRow | null>(null);
  const [traceQ, setTraceQ] = useState("");

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: PkRow[] }>("honey.php", "packaging_list");
      setRows(data.rows);
      setPage(1);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);
  const colCount = canExecEdit ? 9 : 8;

  return (
    <PageLayout
      title="Raw Honey"
      subtitle="Packaging — batch rows and scan-in history"
      actions={
        <input
          value={traceQ}
          onChange={e => setTraceQ(e.target.value)}
          placeholder="Trace batch / supplier…"
          className="text-xs border rounded-lg px-2 py-1.5 w-full max-w-[11rem] sm:max-w-none"
        />
      }
    >
      <div className="space-y-4 mb-6">
        <HoneyStageTabs />
      </div>
      {traceQ && (
        <button
          type="button"
          onClick={() => setTraceBatch(traceQ)}
          className="text-xs text-blue-600 underline mb-3"
        >
          Search traceability
        </button>
      )}

      <h2 className="text-sm font-semibold mb-1">Batch rows</h2>
      <p className="text-xs text-muted-foreground mb-3">
        Tap a row to see who scanned stickers into inventory.
      </p>
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm min-w-[36rem]">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Supplier</th>
              <th className="px-3 py-2 text-left">Batch</th>
              <th className="px-3 py-2 text-left">Date</th>
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => (
                <th key={s} className="px-3 py-2 text-left whitespace-nowrap">
                  {s}
                </th>
              ))}
              {canExecEdit && <th className="px-3 py-2">Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={colCount} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {pagedRows.map(r => (
              <tr
                key={r.id}
                className={`border-b cursor-pointer ${appHoverRow}`}
                onClick={() => setScanLogRow(r)}
              >
                <td className="px-3 py-2" onClick={e => e.stopPropagation()}>
                  {showSupplierProfile ? (
                    <SupplierCodeButton code={r.supplier_code} className="text-xs" />
                  ) : (
                    <span className="font-mono text-xs">{r.supplier_code}</span>
                  )}
                </td>
                <td className="px-3 py-2 font-medium whitespace-nowrap">{r.batch_no}</td>
                <td className="px-3 py-2 whitespace-nowrap">{r.bottling_date}</td>
                <td className="px-3 py-2 capitalize">{r.variant}</td>
                <td className="px-3 py-2">
                  <QtyCounter current={r.current_liter} initial={r.initial_liter} />
                </td>
                <td className="px-3 py-2">
                  <QtyCounter current={r.current_250ml} initial={r.initial_250ml} />
                </td>
                <td className="px-3 py-2">
                  <QtyCounter current={r.current_350ml} initial={r.initial_350ml} />
                </td>
                <td className="px-3 py-2">
                  <QtyCounter current={r.current_gallon} initial={r.initial_gallon} />
                </td>
                {canExecEdit && (
                  <td className="px-3 py-2 whitespace-nowrap" onClick={e => e.stopPropagation()}>
                    <button
                      type="button"
                      onClick={() => setEditRow(r)}
                      className="text-xs font-semibold text-muted-foreground hover:text-foreground"
                    >
                      Edit
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {!loading && totalRows > 0 && (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 px-1 mt-3">
          <p className="text-xs text-muted-foreground">
            Showing {start + 1}-{Math.min(start + perPage, totalRows)} of {totalRows} rows
          </p>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={safePage <= 1}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <span className="text-xs text-muted-foreground">
              Page {safePage} of {totalPages}
            </span>
            <button
              type="button"
              onClick={() => setPage(p => Math.min(totalPages, p + 1))}
              disabled={safePage >= totalPages}
              className="text-xs font-medium border rounded-lg px-3 py-1.5 hover:bg-secondary disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      )}
      {scanLogRow && (
        <PackagingScanLogModal
          packagingRowId={scanLogRow.id}
          batchNo={scanLogRow.batch_no}
          variant={scanLogRow.variant}
          onClose={() => setScanLogRow(null)}
        />
      )}
      {traceBatch && (
        <BatchTraceabilityDrawer query={traceBatch} onClose={() => setTraceBatch(null)} />
      )}
      {editRow && (
        <ExecutiveRowEditModal
          stage="packaging"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => {
            setEditRow(null);
            void refresh();
          }}
        />
      )}
    </PageLayout>
  );
}
