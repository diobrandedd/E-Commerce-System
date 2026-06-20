import { useCallback, useEffect, useMemo, useState } from "react";
import { Copy, Loader2, Check } from "lucide-react";
import { AppModal, AppModalBody } from "@/app/components/AppModal";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { SupplierCodeButton } from "@/app/components/suppliers/SupplierCodeButton";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyStageTabs } from "@/app/components/honey/HoneyStageTabs";
import { useAuth } from "@/app/context/AuthContext";
import { canExecutiveDirectEdit, canMutateHoneyInventory, canViewSupplierProfile } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import {
  HONEY_BOTTLE_OPTIONS,
  type BottleLine,
  type HoneyBottleSize,
  formatGallons,
  formatMlFriendly,
  linesToGallons,
  linesTotalMl,
  previewBatchNo,
} from "@/app/lib/honey";

interface PrRow {
  id: number;
  supplier_code: string;
  arrival_date: string;
  variant: string;
  current_qty: string;
  initial_qty: string;
  packaging_batch_no?: string | null;
  packaging_bottling_date?: string | null;
}

type ModalStep = "enter" | "confirm" | "success";

export function HoneyProductionPage() {
  const { user } = useAuth();
  const canMutate = canMutateHoneyInventory(user?.role);
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const showSupplierProfile = canViewSupplierProfile(user?.role);
  const [rows, setRows] = useState<PrRow[]>([]);
  const [page, setPage] = useState(1);
  const perPage = 12;
  const [loading, setLoading] = useState(true);
  const [active, setActive] = useState<PrRow | null>(null);
  const [editRow, setEditRow] = useState<PrRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: PrRow[] }>("honey.php", "production_list");
      setRows(data.rows);
      setPage(1);
    } catch {
      /* ignore */
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const totalRows = rows.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
  const safePage = Math.min(page, totalPages);
  const start = (safePage - 1) * perPage;
  const pagedRows = rows.slice(start, start + perPage);

  return (
    <PageLayout title="Raw Honey" subtitle="Production — stock-out to packaging (batch numbers generated)">
      <div className="space-y-4">
        <HoneyStageTabs />
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40">
              {["Supplier code", "From WH", "Variant", "Gallon/s", canMutate || canExecEdit ? "" : null].filter(Boolean).map(h => (
                <th key={h!} className="text-left px-4 py-3 text-xs font-semibold text-muted-foreground uppercase">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={5} className="py-8 text-center">
                  <Loader2 className="inline animate-spin" size={14} />
                </td>
              </tr>
            )}
            {pagedRows.map(r => (
              <tr key={r.id} className="border-b">
                <td className="px-4 py-3">
                  {showSupplierProfile ? (
                    <SupplierCodeButton code={r.supplier_code} />
                  ) : (
                    <span className="font-mono">{r.supplier_code}</span>
                  )}
                </td>
                <td className="px-4 py-3">{r.arrival_date}</td>
                <td className="px-4 py-3 capitalize">{r.variant}</td>
                <td className="px-4 py-3">
                  <QtyCounter current={r.current_qty} initial={r.initial_qty} />
                </td>
                {(canMutate || canExecEdit) && (
                  <td className="px-4 py-3 space-x-2">
                    {canExecEdit && (
                      <button type="button" onClick={() => setEditRow(r)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                        Edit
                      </button>
                    )}
                    {canMutate && (
                      <button
                        disabled={Number(r.current_qty) <= 0}
                        onClick={() => setActive(r)}
                        className="text-xs font-semibold text-[#C8900A] disabled:opacity-40"
                      >
                        To packaging
                      </button>
                    )}
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
            <span className="text-xs text-muted-foreground">Page {safePage} of {totalPages}</span>
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
      {active && (
        <PackOutModal row={active} onClose={() => setActive(null)} onDone={() => { setActive(null); refresh(); }} />
      )}
      {editRow && (
        <ExecutiveRowEditModal
          stage="production"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
      </div>
    </PageLayout>
  );
}

function PackOutModal({ row, onClose, onDone }: { row: PrRow; onClose: () => void; onDone: () => void }) {
  const available = Number(row.current_qty);
  const [step, setStep] = useState<ModalStep>("enter");
  const [lines, setLines] = useState<BottleLine[]>([{ size: "250ml", qty: "" }]);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [saved, setSaved] = useState<{ batch_no: string; bottling_date: string } | null>(null);
  const [copied, setCopied] = useState(false);

  const usedGal = useMemo(() => linesToGallons(lines), [lines]);
  const usedMl = useMemo(() => linesTotalMl(lines), [lines]);
  const remainingAfter = Math.max(0, available - usedGal);
  const overLimit = usedGal > available + 1e-9;
  const hasValidLines = lines.some(l => parseFloat(l.qty) > 0);
  const batchPreview = row.packaging_batch_no || previewBatchNo(row.supplier_code);
  const batchIsExisting = Boolean(row.packaging_batch_no);

  const updateLine = (index: number, patch: Partial<BottleLine>) => {
    setLines(prev => prev.map((l, i) => (i === index ? { ...l, ...patch } : l)));
  };

  const submit = async () => {
    setBusy(true);
    setErr(null);
    try {
      const payload = {
        production_row_id: row.id,
        lines: lines
          .filter(l => parseFloat(l.qty) > 0)
          .map(l => ({ size: l.size, qty: parseFloat(l.qty) })),
      };
      const res = await apiPost<{
        batch_no: string;
        bottling_date: string;
        used_gallons: number;
        remaining_production_gallons: number;
      }>("honey.php", "production_stock_out", payload);
      setSaved({ batch_no: res.batch_no, bottling_date: res.bottling_date });
      setStep("success");
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Save failed");
      setStep("confirm");
    } finally {
      setBusy(false);
    }
  };

  const copyBatch = async () => {
    if (!saved?.batch_no) return;
    try {
      await navigator.clipboard.writeText(saved.batch_no);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      /* ignore */
    }
  };

  if (step === "success" && saved) {
    return (
      <ModalShell onClose={onDone}>
        <div className="text-center space-y-4 py-2">
          <p className="text-sm font-semibold text-green-700">Saved to packaging</p>
          <p className="text-xs text-muted-foreground">Print this batch number on packaging labels</p>
          <div
            className="rounded-xl border-2 border-[#C8900A] bg-[#C8900A]/10 px-4 py-6"
            style={{ boxShadow: "0 0 0 4px rgba(200,144,10,0.15)" }}
          >
            <p className="text-[10px] uppercase tracking-widest text-muted-foreground mb-2">Batch number</p>
            <p className="text-3xl font-bold font-mono text-[#8a5c00] break-all">{saved.batch_no}</p>
            <p className="text-xs text-muted-foreground mt-3">Bottling date: {saved.bottling_date}</p>
          </div>
          <button
            type="button"
            onClick={copyBatch}
            className="inline-flex items-center gap-2 text-sm font-semibold border rounded-lg px-4 py-2 hover:bg-secondary"
          >
            {copied ? <Check size={16} className="text-green-600" /> : <Copy size={16} />}
            {copied ? "Copied" : "Copy batch number"}
          </button>
          <button
            type="button"
            onClick={onDone}
            className="w-full py-2.5 rounded-lg text-sm font-bold text-white"
            style={{ background: "#C8900A" }}
          >
            Done
          </button>
        </div>
      </ModalShell>
    );
  }

  if (step === "confirm") {
    return (
      <ModalShell onClose={() => setStep("enter")}>
        <h3 className="font-bold mb-3">Confirm — move to packaging</h3>
        <div className="space-y-3 text-sm">
          <p>
            <strong>{row.supplier_code}</strong> · <span className="capitalize">{row.variant}</span>
          </p>
          <p>
            Production use: <strong>{formatGallons(usedGal)} gallon/s</strong> ({formatMlFriendly(usedMl)})
          </p>
          <p>
            After save: <strong>{formatGallons(remainingAfter)}</strong> / {formatGallons(available)} gallon/s left in
            production for <strong className="font-mono">{row.supplier_code}</strong>
          </p>
          <table className="w-full text-xs border rounded-lg">
            <thead>
              <tr className="bg-secondary/50 border-b">
                <th className="text-left px-2 py-1.5">Size</th>
                <th className="text-right px-2 py-1.5">Bottles</th>
              </tr>
            </thead>
            <tbody>
              {lines
                .filter(l => parseFloat(l.qty) > 0)
                .map((l, i) => (
                  <tr key={i} className="border-b last:border-0">
                    <td className="px-2 py-1.5">
                      {HONEY_BOTTLE_OPTIONS.find(o => o.value === l.size)?.label ?? l.size}
                    </td>
                    <td className="px-2 py-1.5 text-right">{l.qty}</td>
                  </tr>
                ))}
            </tbody>
          </table>
          <p className="text-xs text-muted-foreground">
            Batch {batchIsExisting ? "(existing, unchanged)" : "(new)"}:{" "}
            <strong className="font-mono text-foreground">{batchPreview}</strong>
          </p>
          {err && <p className="text-xs text-red-600">{err}</p>}
          <div className="flex gap-2 pt-1">
            <button type="button" onClick={() => setStep("enter")} className="flex-1 py-2 rounded-lg border text-sm">
              Back
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={submit}
              className="flex-1 py-2 rounded-lg text-sm font-bold text-white disabled:opacity-50"
              style={{ background: "#C8900A" }}
            >
              {busy ? "Saving…" : "Confirm & save"}
            </button>
          </div>
        </div>
      </ModalShell>
    );
  }

  return (
    <ModalShell onClose={onClose}>
      <h3 className="font-bold mb-1">Stock out → Packaging</h3>
      <p className="text-xs text-muted-foreground mb-4">
        {row.supplier_code} · <span className="capitalize">{row.variant}</span> · Available{" "}
        <strong>{formatGallons(available)} gallon/s</strong>
      </p>

      <div className="space-y-3 text-sm">
        <p className="text-xs font-semibold text-muted-foreground">Bottled products</p>
        {lines.map((line, i) => (
          <div key={i} className="flex gap-2 items-center">
            <select
              value={line.size}
              onChange={e => updateLine(i, { size: e.target.value as HoneyBottleSize })}
              className="border rounded-lg px-2 py-2 text-sm flex-1 min-w-0"
            >
              {HONEY_BOTTLE_OPTIONS.map(o => (
                <option key={o.value} value={o.value}>
                  {o.label}
                </option>
              ))}
            </select>
            <span className="text-xs text-muted-foreground whitespace-nowrap shrink-0">Qty</span>
            <input
              type="number"
              step="0.01"
              min="0"
              value={line.qty}
              onChange={e => updateLine(i, { qty: e.target.value })}
              className="border rounded-lg px-2 py-2 text-sm w-20"
            />
            {lines.length > 1 && (
              <button type="button" onClick={() => setLines(lines.filter((_, j) => j !== i))} className="text-xs text-red-600 shrink-0">
                Remove
              </button>
            )}
          </div>
        ))}
        <button
          type="button"
          onClick={() => setLines([...lines, { size: "250ml", qty: "" }])}
          className="text-xs font-semibold text-[#C8900A]"
        >
          + Add another product
        </button>

        <div
          className={`rounded-lg border px-3 py-2.5 text-xs ${overLimit ? "border-red-300 bg-red-50 text-red-800" : "border-border bg-secondary/30"}`}
        >
          <p>
            Using: <strong>{formatGallons(usedGal)} gallon/s</strong>
            {usedMl > 0 && <span className="text-muted-foreground"> ({formatMlFriendly(usedMl)})</span>}
          </p>
          <p className="mt-1">
            After save: <strong>{formatGallons(remainingAfter)}</strong> / {formatGallons(available)} gallon/s left in
            production for <strong className="font-mono">{row.supplier_code}</strong>
          </p>
          {overLimit && <p className="mt-1 font-semibold">Not enough production volume for these bottles.</p>}
        </div>
        <p className="text-[10px] text-muted-foreground">1 gallon ≈ 3,785 ml (used for calculation only)</p>

        {err && <p className="text-xs text-red-600">{err}</p>}

        <button
          type="button"
          disabled={!hasValidLines || overLimit}
          onClick={() => { setErr(null); setStep("confirm"); }}
          className="w-full py-2.5 rounded-lg text-sm font-bold text-white disabled:opacity-50"
          style={{ background: "#C8900A" }}
        >
          Review & confirm
        </button>
      </div>
    </ModalShell>
  );
}

function ModalShell({ children, onClose }: { children: React.ReactNode; onClose: () => void }) {
  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full">
      <AppModalBody className="py-6">{children}</AppModalBody>
    </AppModal>
  );
}
