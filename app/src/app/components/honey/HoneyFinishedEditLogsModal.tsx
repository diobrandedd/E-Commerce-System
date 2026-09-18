import { useCallback, useEffect, useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppSectionEnter,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { ApiError, apiGet } from "@/app/lib/api";

export interface InventoryEditLogRow {
  id: number;
  row_table: string;
  row_id: number;
  field_name: string;
  old_value: string | null;
  new_value: string | null;
  edited_by_name: string;
  source: string | null;
  reason: string | null;
  created_at: string;
}

interface HoneyFinishedEditLogsModalProps {
  open: boolean;
  onClose: () => void;
  /** Bump after a successful Edit so the list refreshes while open. */
  refreshKey?: number;
}

const PAGE_SIZE = 40;

function formatWhen(raw: string) {
  return new Date(raw.replace(" ", "T")).toLocaleString();
}

function fieldLabel(field: string): string {
  const map: Record<string, string> = {
    current_250ml: "250ML",
    current_350ml: "350ML",
    current_liter: "1L",
    current_gallon: "Gallon",
    initial_250ml: "250ML (recorded)",
    initial_350ml: "350ML (recorded)",
    initial_liter: "1L (recorded)",
    initial_gallon: "Gallon (recorded)",
    variant: "Variant",
  };
  return map[field] ?? field;
}

export function HoneyFinishedEditLogsModal({
  open,
  onClose,
  refreshKey = 0,
}: HoneyFinishedEditLogsModalProps) {
  const titleId = useId();
  const [rows, setRows] = useState<InventoryEditLogRow[]>([]);
  const [total, setTotal] = useState(0);
  const [offset, setOffset] = useState(0);
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (nextOffset: number, search: string) => {
      setLoading(true);
      setError(null);
      try {
        const res = await apiGet<{ rows: InventoryEditLogRow[]; total: number }>(
          "honey.php",
          "edit_log_list",
          {
            limit: PAGE_SIZE,
            offset: nextOffset,
            row_table: "honey_packaging_rows",
            source: "executive_edit",
            ...(search.trim() ? { q: search.trim() } : {}),
          },
        );
        setRows(res.rows ?? []);
        setTotal(res.total ?? 0);
        setOffset(nextOffset);
      } catch (e) {
        setError(e instanceof ApiError ? e.message : "Could not load edit logs.");
        setRows([]);
        setTotal(0);
      } finally {
        setLoading(false);
      }
    },
    [],
  );

  useEffect(() => {
    if (!open) return;
    void load(0, qApplied);
  }, [open, refreshKey, qApplied, load]);

  const page = Math.floor(offset / PAGE_SIZE) + 1;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  return (
    <AppModal open={open} onClose={onClose} panelClassName="max-w-3xl w-full" labelledBy={titleId}>
      <AppModalHeader
        title="Finished honey edit logs"
        titleId={titleId}
        subtitle="Corrections to Light / Amber / Dark on-hand pools — who changed what, and why."
        onClose={onClose}
      />
      <AppModalBody className="space-y-3 max-h-[75vh] overflow-y-auto">
        <AppSectionEnter className="space-y-3">
          <form
            className="flex flex-col sm:flex-row gap-2"
            onSubmit={e => {
              e.preventDefault();
              setQApplied(q);
            }}
          >
            <input
              type="search"
              value={q}
              onChange={e => setQ(e.target.value)}
              placeholder="Search reason, field, user…"
              className="flex-1 min-h-11 rounded-lg border border-border bg-card px-3 text-sm"
              aria-label="Search edit logs"
            />
            <Button type="submit" variant="outline" className="min-h-11" disabled={loading}>
              Search
            </Button>
          </form>

          {error && <AppErrorBanner message={error} />}

          {loading ? (
            <div className="py-12 text-center text-muted-foreground">
              <Loader2 className="inline animate-spin" size={18} />
            </div>
          ) : total === 0 ? (
            <p className="text-sm text-muted-foreground py-10 text-center">
              No finished honey corrections logged yet.
            </p>
          ) : (
            <>
              <p className="text-xs text-muted-foreground">
                {total} log{total === 1 ? "" : "s"}
                {qApplied.trim() ? ` matching “${qApplied.trim()}”` : ""}
              </p>

              <ul className="sm:hidden space-y-3">
                {rows.map(r => (
                  <li key={r.id} className={`rounded-xl border border-border bg-card p-3 ${appHoverRow}`}>
                    <div className="flex items-start justify-between gap-2">
                      <p className="text-sm font-medium">{r.edited_by_name || "—"}</p>
                      <p className="text-[11px] text-muted-foreground whitespace-nowrap">
                        {formatWhen(r.created_at)}
                      </p>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">
                      {fieldLabel(r.field_name)} ·{" "}
                      <span className="tabular-nums">{r.old_value ?? "—"}</span>
                      {" → "}
                      <span className="font-semibold text-foreground tabular-nums">
                        {r.new_value ?? "—"}
                      </span>
                    </p>
                    {r.reason ? (
                      <p className="text-sm mt-2 leading-snug">{r.reason}</p>
                    ) : (
                      <p className="text-xs text-muted-foreground mt-2 italic">No reason recorded</p>
                    )}
                  </li>
                ))}
              </ul>

              <div className="hidden sm:block overflow-x-auto rounded-xl border border-border">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b bg-secondary/30 text-left text-xs uppercase text-muted-foreground">
                      <th className="px-3 py-2">When</th>
                      <th className="px-3 py-2">Who</th>
                      <th className="px-3 py-2">Field</th>
                      <th className="px-3 py-2">Change</th>
                      <th className="px-3 py-2">Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.map(r => (
                      <tr key={r.id} className={appHoverRow}>
                        <td className="px-3 py-2 text-xs whitespace-nowrap text-muted-foreground">
                          {formatWhen(r.created_at)}
                        </td>
                        <td className="px-3 py-2 whitespace-nowrap">{r.edited_by_name || "—"}</td>
                        <td className="px-3 py-2">{fieldLabel(r.field_name)}</td>
                        <td className="px-3 py-2 tabular-nums whitespace-nowrap">
                          <span className="text-muted-foreground">{r.old_value ?? "—"}</span>
                          {" → "}
                          <span className="font-semibold">{r.new_value ?? "—"}</span>
                        </td>
                        <td className="px-3 py-2 text-xs max-w-[16rem]">
                          {r.reason ? (
                            <span className="line-clamp-3 leading-snug">{r.reason}</span>
                          ) : (
                            <span className="text-muted-foreground">—</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {totalPages > 1 && (
                <div className="flex items-center justify-between gap-2 pt-1">
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="min-h-11"
                    disabled={page <= 1 || loading}
                    onClick={() => void load(Math.max(0, offset - PAGE_SIZE), qApplied)}
                  >
                    Previous
                  </Button>
                  <span className="text-xs text-muted-foreground">
                    Page {page} of {totalPages}
                  </span>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="min-h-11"
                    disabled={page >= totalPages || loading}
                    onClick={() => void load(offset + PAGE_SIZE, qApplied)}
                  >
                    Next
                  </Button>
                </div>
              )}
            </>
          )}
        </AppSectionEnter>
      </AppModalBody>
    </AppModal>
  );
}
