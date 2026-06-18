import { useCallback, useEffect, useMemo, useState } from "react";
import { AlertTriangle, Loader2, Package, Plus } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import {
  AppErrorBanner,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { listMaterials, type MaterialStockRow } from "@/app/lib/materials";
import {
  BUBBLE_WIDTHS_CM,
  isBubbleGenericName,
  isBubbleSizedName,
  isFoamSizedName,
  packingBalancesList,
  packingDamageReport,
  packingIssueCreate,
  packingIssueEstimate,
  packingIssuesList,
  type PackingBalanceRow,
  type PackingIssueRow,
} from "@/app/lib/fulfillmentPackingApi";
import { ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

function isIssueCatalogRow(name: string): boolean {
  if (isBubbleSizedName(name)) return false;
  if (isFoamSizedName(name)) return false;
  const n = name.toLowerCase();
  return (
    n.startsWith("bubble wrap roll") ||
    n.startsWith("cushion piece") ||
    n.startsWith("cushion roll") ||
    n.startsWith("courier pouch") ||
    n.startsWith("insulation foam roll") ||
    n.startsWith("cardboard box")
  );
}

function ProgressBar({ consumed, issued, damaged }: { consumed: number; issued: number; damaged: number }) {
  const used = consumed + damaged;
  const pct = issued > 0 ? Math.min(100, Math.round((used / issued) * 100)) : 0;
  return (
    <div className="h-2 rounded-full bg-muted overflow-hidden">
      <div className="h-full bg-amber-500 transition-all" style={{ width: `${pct}%` }} />
    </div>
  );
}

interface IssueLineDraft {
  stock_id: number;
  qty: string;
  bubble_width_cm: string;
}

function PackingIssueModal({
  catalog,
  onClose,
  onDone,
}: {
  catalog: MaterialStockRow[];
  onClose: () => void;
  onDone: () => void;
}) {
  const [lines, setLines] = useState<IssueLineDraft[]>([
    { stock_id: catalog[0]?.id ?? 0, qty: "", bubble_width_cm: "17" },
  ]);
  const [note, setNote] = useState("");
  const [estimate, setEstimate] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const validLines = useMemo(() => {
    return lines
      .map((l) => {
        const row = catalog.find((c) => c.id === l.stock_id);
        const qty = parseFloat(l.qty);
        if (!row || !Number.isFinite(qty) || qty <= 0) return null;
        const out: { stock_id: number; qty: number; bubble_width_cm?: number } = {
          stock_id: l.stock_id,
          qty,
        };
        if (isBubbleGenericName(row.name)) {
          const width = parseInt(l.bubble_width_cm, 10);
          if (!BUBBLE_WIDTHS_CM.includes(width as (typeof BUBBLE_WIDTHS_CM)[number])) return null;
          out.bubble_width_cm = width;
        }
        return out;
      })
      .filter((l): l is NonNullable<typeof l> => l !== null);
  }, [lines, catalog]);

  useEffect(() => {
    if (validLines.length === 0) {
      setEstimate(null);
      return;
    }
    const t = window.setTimeout(() => {
      void packingIssueEstimate(validLines)
        .then((r) => {
          const e = r.estimate;
          if (e.est_parcels > 0) {
            setEstimate(`~${e.est_parcels} parcels (${e.est_label || "typical mix"})`);
          } else {
            setEstimate("Estimate unavailable for this mix.");
          }
        })
        .catch(() => setEstimate(null));
    }, 300);
    return () => window.clearTimeout(t);
  }, [validLines]);

  const submit = async () => {
    if (validLines.length === 0) {
      setError("Add at least one line with quantity. Bubble wrap needs a width (13 / 17 / 22 cm).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await packingIssueCreate({ lines: validLines, note: note.trim() || undefined });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Issue failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader title="Issue to packing" subtitle="Moves stock from warehouse to packing area" onClose={onClose} />
      <AppModalBody className="space-y-3 max-h-[70vh] overflow-y-auto">
        {error && <AppErrorBanner message={error} />}
        {estimate && <p className="text-sm font-medium text-amber-800 bg-amber-50 rounded-lg px-3 py-2">{estimate}</p>}
        {lines.map((line, idx) => {
          const row = catalog.find((c) => c.id === line.stock_id);
          const showWidth = row ? isBubbleGenericName(row.name) : false;
          return (
            <div key={idx} className="flex flex-wrap gap-2 items-end">
              <div className="flex-1 min-w-[12rem]">
                <label className="text-xs text-muted-foreground">SKU</label>
                <select
                  className="mt-1 w-full rounded-lg border border-border px-2 py-2 bg-card text-sm"
                  value={line.stock_id}
                  onChange={(e) => {
                    const next = [...lines];
                    next[idx] = { ...next[idx], stock_id: parseInt(e.target.value, 10) };
                    setLines(next);
                  }}
                >
                  {catalog.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name} (warehouse: {c.on_hand} {c.unit})
                    </option>
                  ))}
                </select>
              </div>
              {showWidth && (
                <div className="w-28">
                  <label className="text-xs text-muted-foreground">Width</label>
                  <select
                    className="mt-1 w-full rounded-lg border border-border px-2 py-2 bg-card text-sm"
                    value={line.bubble_width_cm}
                    onChange={(e) => {
                      const next = [...lines];
                      next[idx] = { ...next[idx], bubble_width_cm: e.target.value };
                      setLines(next);
                    }}
                  >
                    {BUBBLE_WIDTHS_CM.map((w) => (
                      <option key={w} value={w}>
                        {w} cm
                      </option>
                    ))}
                  </select>
                </div>
              )}
              <div className="w-28">
                <label className="text-xs text-muted-foreground">Qty</label>
                <input
                  type="number"
                  min={0}
                  step="any"
                  className="mt-1 w-full rounded-lg border border-border px-2 py-2 bg-card text-sm"
                  value={line.qty}
                  onChange={(e) => {
                    const next = [...lines];
                    next[idx] = { ...next[idx], qty: e.target.value };
                    setLines(next);
                  }}
                />
              </div>
            </div>
          );
        })}
        <button
          type="button"
          className="text-xs font-semibold text-primary flex items-center gap-1"
          onClick={() =>
            setLines((prev) => [
              ...prev,
              { stock_id: catalog[0]?.id ?? 0, qty: "", bubble_width_cm: "17" },
            ])
          }
        >
          <Plus className="h-3 w-3" /> Add line
        </button>
        <div>
          <label className="text-xs text-muted-foreground">Note (optional)</label>
          <input
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card text-sm"
            value={note}
            onChange={(e) => setNote(e.target.value)}
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose} disabled={busy}>
          Cancel
        </Button>
        <Button type="button" onClick={() => void submit()} disabled={busy}>
          {busy ? "Issuing…" : "Issue materials"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}

function DamageReportModal({
  row,
  onClose,
  onDone,
}: {
  row: PackingBalanceRow;
  onClose: () => void;
  onDone: (message: string) => void;
}) {
  const [qty, setQty] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    const parsed = parseFloat(qty);
    if (!Number.isFinite(parsed) || parsed <= 0) {
      setError("Enter a quantity greater than 0.");
      return;
    }
    if (!note.trim()) {
      setError("A note is required (e.g. torn roll, water damage).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      const result = await packingDamageReport({
        stock_id: row.stock_id,
        qty: parsed,
        note: note.trim(),
      });
      onDone(`Reported ${result.qty} ${row.unit} damaged — ${result.remaining_qty} remaining.`);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Damage report failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Report damage"
        subtitle={`${row.name} · ${row.remaining_qty} ${row.unit} remaining on staging`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-3">
        {error && <AppErrorBanner message={error} />}
        <p className="text-xs text-muted-foreground">
          Damaged qty stays off staging and is not returned to warehouse. Leftover usable stock carries over for tomorrow.
        </p>
        <div>
          <label className="text-xs text-muted-foreground">Damaged quantity</label>
          <input
            type="number"
            min={0}
            step="any"
            max={row.remaining_qty}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card text-sm"
            value={qty}
            onChange={(e) => setQty(e.target.value)}
          />
        </div>
        <div>
          <label className="text-xs text-muted-foreground">Note (required)</label>
          <input
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card text-sm"
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="What happened?"
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose} disabled={busy}>
          Cancel
        </Button>
        <Button type="button" variant="destructive" onClick={() => void submit()} disabled={busy}>
          {busy ? "Saving…" : "Report damage"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}

export function PackingMaterialsStagingPage() {
  const [rows, setRows] = useState<PackingBalanceRow[]>([]);
  const [issues, setIssues] = useState<PackingIssueRow[]>([]);
  const [catalog, setCatalog] = useState<MaterialStockRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [issueOpen, setIssueOpen] = useState(false);
  const [damageRow, setDamageRow] = useState<PackingBalanceRow | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [bal, iss, mats] = await Promise.all([
        packingBalancesList(),
        packingIssuesList({ page: 1, per_page: 8 }),
        listMaterials("packaging"),
      ]);
      setRows(bal.rows);
      setIssues(iss.rows);
      setCatalog(mats.filter((m) => isIssueCatalogRow(m.name)));
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const withStaging = rows.filter((r) => r.issued_qty > 0 || r.remaining_qty > 0);

  return (
    <PageLayout
      title="For packing"
      subtitle="Issue fulfillment materials to packing · ship scan consumes staging · report damage on unusable rolls"
    >
      {error && <AppErrorBanner message={error} className="mb-4" />}
      {success && <AppSuccessNotice message={success} className="mb-4" />}

      <div className="flex flex-wrap gap-3 mb-6">
        <Button type="button" onClick={() => setIssueOpen(true)} disabled={catalog.length === 0}>
          <Package className="h-4 w-4" />
          Issue materials
        </Button>
        <p className="text-sm text-muted-foreground self-center">
          {withStaging.length} SKU{withStaging.length === 1 ? "" : "s"} in staging
        </p>
      </div>

      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : (
        <>
          <div className="rounded-2xl border border-border overflow-hidden mb-8">
            <table className="w-full text-sm">
              <thead className="bg-muted/50 text-left text-xs text-muted-foreground">
                <tr>
                  <th className="px-4 py-3 font-semibold">SKU</th>
                  <th className="px-4 py-3 font-semibold">Issued</th>
                  <th className="px-4 py-3 font-semibold">Consumed</th>
                  <th className="px-4 py-3 font-semibold">Damaged</th>
                  <th className="px-4 py-3 font-semibold">Remaining</th>
                  <th className="px-4 py-3 font-semibold w-40">Progress</th>
                  <th className="px-4 py-3 font-semibold w-28" />
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.stock_id} className={cn("border-t border-border", appHoverRow)}>
                    <td className="px-4 py-3">
                      <div className="font-medium">{r.name}</div>
                      <div className="text-xs text-muted-foreground">
                        Warehouse: {r.warehouse_on_hand} {r.unit}
                      </div>
                    </td>
                    <td className="px-4 py-3">{r.issued_qty}</td>
                    <td className="px-4 py-3">{r.consumed_qty}</td>
                    <td className="px-4 py-3">{r.damaged_qty ?? 0}</td>
                    <td
                      className={cn(
                        "px-4 py-3 font-semibold",
                        r.remaining_qty <= 0 && r.issued_qty > 0 && "text-red-600",
                      )}
                    >
                      {r.remaining_qty}
                    </td>
                    <td className="px-4 py-3">
                      <div className="text-xs mb-1 text-muted-foreground">
                        {r.consumed_qty + (r.damaged_qty ?? 0)}/{r.issued_qty || "—"}
                      </div>
                      <ProgressBar
                        consumed={r.consumed_qty}
                        issued={r.issued_qty}
                        damaged={r.damaged_qty ?? 0}
                      />
                    </td>
                    <td className="px-4 py-3">
                      {r.remaining_qty > 0 && r.issued_qty > 0 && (
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          className="text-xs"
                          onClick={() => setDamageRow(r)}
                        >
                          <AlertTriangle className="h-3 w-3" />
                          Damage
                        </Button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <h2 className="text-sm font-bold mb-3">Recent take-outs</h2>
          {issues.length === 0 ? (
            <p className="text-sm text-muted-foreground">No issues yet.</p>
          ) : (
            <ul className="space-y-3">
              {issues.map((issue) => (
                <li key={issue.id} className="rounded-xl border border-border p-4 text-sm">
                  <div className="flex flex-wrap justify-between gap-2 mb-2">
                    <span className="font-semibold">#{issue.id}</span>
                    <span className="text-xs text-muted-foreground">
                      {issue.issued_by_name ?? "—"} · {new Date(issue.created_at).toLocaleString()}
                    </span>
                  </div>
                  {issue.est_parcels_typical != null && (
                    <p className="text-xs text-amber-800 mb-2">
                      ~{issue.est_parcels_typical} parcels
                      {issue.est_parcels_label ? ` (${issue.est_parcels_label})` : ""}
                    </p>
                  )}
                  <ul className="text-xs text-muted-foreground space-y-0.5">
                    {issue.lines.map((l, i) => (
                      <li key={i}>
                        {l.stock_name}: {l.qty} {l.unit}
                        {l.bubble_width_cm ? ` (${l.bubble_width_cm} cm)` : ""}
                      </li>
                    ))}
                  </ul>
                  {issue.note && <p className="text-xs mt-2 italic">{issue.note}</p>}
                </li>
              ))}
            </ul>
          )}
        </>
      )}

      {issueOpen && (
        <PackingIssueModal
          catalog={catalog}
          onClose={() => setIssueOpen(false)}
          onDone={() => {
            setIssueOpen(false);
            setSuccess("Materials issued to packing.");
            void load();
          }}
        />
      )}

      {damageRow && (
        <DamageReportModal
          row={damageRow}
          onClose={() => setDamageRow(null)}
          onDone={(message) => {
            setDamageRow(null);
            setSuccess(message);
            void load();
          }}
        />
      )}
    </PageLayout>
  );
}
