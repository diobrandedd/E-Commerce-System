import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { ApiError } from "@/app/lib/api";
import { HONEY_SIZES } from "@/app/lib/honey";
import { rtsListDamagedInventory, rtsUseDamaged } from "@/app/lib/rtsApi";

interface RtsInvRow {
  id: number;
  category: string;
  product_label: string;
  variant: string | null;
  current_liter: string;
  initial_liter: string;
  current_250ml: string;
  initial_250ml: string;
  current_350ml: string;
  initial_350ml: string;
  current_gallon: string;
  initial_gallon: string;
  current_qty: string;
  initial_qty: string;
}

const HONEY_VARIANTS = ["light", "amber", "dark", "other"] as const;

export function RTSDamagedInventoryPage() {
  const [honey, setHoney] = useState<RtsInvRow[]>([]);
  const [other, setOther] = useState<RtsInvRow[]>([]);
  const [canUseDamaged, setCanUseDamaged] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [useRow, setUseRow] = useState<RtsInvRow | null>(null);

  const loadInventory = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const inv = await rtsListDamagedInventory();
      setHoney(inv.honey as unknown as RtsInvRow[]);
      setOther(inv.other as unknown as RtsInvRow[]);
      setCanUseDamaged(!!inv.can_use_damaged);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load damaged RTS stock.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadInventory();
  }, [loadInventory]);

  return (
    <PageLayout
      title="Damaged RTS stock"
      subtitle="Leaked or damaged returns — view-only for honey staff; TL / PM / Exec can use damaged stock"
      actions={
        <Link to="/rts" className="text-xs text-[#C8900A] underline">
          Sellable RTS inventory
        </Link>
      }
    >
      <AppSuccessNotice message={toast} />
      <AppErrorBanner message={error} />

      <p className="text-xs text-muted-foreground mb-4">
        Stock here is synced from leak cases (damaged lines). Use damaged to scrap or mix honey back into a production pool.
        {!canUseDamaged && " Your role can view this list only."}
      </p>

      {loading ? (
        <div className="text-sm text-muted-foreground py-8 text-center">
          <Loader2 className="inline animate-spin mr-2" size={14} /> Loading…
        </div>
      ) : (
        <>
          <section className="mb-6">
            <h3 className="text-sm font-semibold mb-3">Honey (damaged)</h3>
            <DamagedTable rows={honey} honey canUse={canUseDamaged} onUse={setUseRow} />
          </section>
          <section>
            <h3 className="text-sm font-semibold mb-3">Other products (damaged)</h3>
            <DamagedTable rows={other} honey={false} canUse={canUseDamaged} onUse={setUseRow} />
          </section>
        </>
      )}

      {useRow && (
        <UseDamagedModal
          row={useRow}
          onClose={() => setUseRow(null)}
          onDone={msg => {
            setUseRow(null);
            setToast(msg);
            window.setTimeout(() => setToast(null), 3500);
            void loadInventory();
          }}
          onError={msg => setError(msg)}
        />
      )}
    </PageLayout>
  );
}

function DamagedTable({
  rows,
  honey,
  canUse,
  onUse,
}: {
  rows: RtsInvRow[];
  honey: boolean;
  canUse: boolean;
  onUse: (r: RtsInvRow) => void;
}) {
  if (!rows.length) {
    return <p className="text-sm text-muted-foreground">No damaged rows yet. Record a leak case with damaged lines, then run RTS sync.</p>;
  }
  if (honey) {
    return (
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
              <th className="px-3 py-2 text-left">Variant</th>
              {HONEY_SIZES.map(s => (
                <th key={s} className="px-3 py-2 text-left">
                  {s}
                </th>
              ))}
              {canUse && <th className="px-3 py-2" />}
            </tr>
          </thead>
          <tbody>
            {rows.map(r => (
              <tr key={r.id} className="border-b">
                <td className="px-3 py-2 capitalize">{r.variant ?? r.product_label}</td>
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
                {canUse && (
                  <td className="px-3 py-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => onUse(r)}>
                      Use damaged
                    </Button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  }
  return (
    <div className="bg-card rounded-xl border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
            <th className="px-3 py-2 text-left">Product</th>
            <th className="px-3 py-2 text-left">Qty</th>
            {canUse && <th className="px-3 py-2" />}
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.id} className="border-b">
              <td className="px-3 py-2">{r.product_label}</td>
              <td className="px-3 py-2">
                <QtyCounter current={r.current_qty} initial={r.initial_qty} />
              </td>
              {canUse && (
                <td className="px-3 py-2">
                  <Button type="button" size="sm" variant="outline" onClick={() => onUse(r)}>
                    Use damaged
                  </Button>
                </td>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function UseDamagedModal({
  row,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow;
  onClose: () => void;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const isHoney = row.category === "honey";
  const [outcome, setOutcome] = useState<"mix_production" | "scrap" | "cap_reseal">(
    isHoney ? "cap_reseal" : "scrap",
  );
  const [variant, setVariant] = useState(row.variant ?? "light");
  const [sizeQtys, setSizeQtys] = useState<Record<string, string>>({});
  const [qtyOther, setQtyOther] = useState("1");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    setBusy(true);
    onError("");
    try {
      const body: Parameters<typeof rtsUseDamaged>[0] = {
        row_id: row.id,
        outcome,
        notes: notes.trim() || undefined,
      };
      if (isHoney) {
        const sq: Record<string, number> = {};
        for (const s of HONEY_SIZES) {
          const q = parseFloat(sizeQtys[s] ?? "0");
          if (q > 0) sq[s] = q;
        }
        body.size_qtys = sq;
        if (outcome === "mix_production") body.variant = variant;
      } else {
        body.qty_other = parseFloat(qtyOther) || 0;
      }
      const result = await rtsUseDamaged(body);
      if (result.outcome === "mix_production" && result.volume_gallons) {
        onDone(`Mixed ${result.volume_gallons.toFixed(2)} gal into ${variant} production pool`);
      } else if (result.outcome === "cap_reseal" && result.bottles_resealed) {
        onDone(
          `Re-sealed ${result.bottles_resealed} bottle(s) — moved to sellable RTS inventory (${result.bottles_resealed} cap seal${result.bottles_resealed === 1 ? "" : "s"} used)`,
        );
      } else {
        onDone("Damaged stock updated (scrap)");
      }
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Use damaged failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="use-damaged-title">
      <AppModalHeader
        title="Use damaged stock"
        titleId="use-damaged-title"
        subtitle={row.product_label}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        <label className="text-xs block">
          Outcome
          <select
            value={outcome}
            onChange={e => setOutcome(e.target.value as "mix_production" | "scrap" | "cap_reseal")}
            className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm"
          >
            {isHoney && <option value="cap_reseal">Re-seal cap → sellable RTS</option>}
            {isHoney && <option value="mix_production">Mix into honey production pool</option>}
            <option value="scrap">Scrap / dispose</option>
          </select>
        </label>

        {isHoney && outcome === "cap_reseal" && (
          <p className="text-xs text-muted-foreground rounded-lg border border-border bg-secondary/30 px-3 py-2">
            Deducts <strong>Honey cap seal</strong> from packaging materials (1 per bottle) and moves the resealed qty to{" "}
            <strong>RTS inventory</strong> (sellable).
          </p>
        )}

        {isHoney ? (
          <div className="grid grid-cols-2 gap-2">
            {HONEY_SIZES.map(s => (
              <label key={s} className="text-xs">
                {s}
                <input
                  type="number"
                  min={0}
                  step={0.01}
                  value={sizeQtys[s] ?? ""}
                  onChange={e => setSizeQtys(prev => ({ ...prev, [s]: e.target.value }))}
                  className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm"
                  placeholder="0"
                />
              </label>
            ))}
          </div>
        ) : (
          <label className="text-xs block">
            Quantity to scrap
            <input
              type="number"
              min={0.01}
              step={0.01}
              value={qtyOther}
              onChange={e => setQtyOther(e.target.value)}
              className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm"
            />
          </label>
        )}

        {isHoney && outcome === "mix_production" && (
          <label className="text-xs block">
            Production pool variant
            <select
              value={variant}
              onChange={e => setVariant(e.target.value)}
              className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm capitalize"
            >
              {HONEY_VARIANTS.map(v => (
                <option key={v} value={v}>
                  {v}
                </option>
              ))}
            </select>
          </label>
        )}

        <label className="text-xs block">
          Notes (optional)
          <input
            type="text"
            value={notes}
            onChange={e => setNotes(e.target.value)}
            className="w-full mt-1 border rounded-lg px-2 py-1.5 text-sm"
          />
        </label>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>
          Cancel
        </Button>
        <Button type="button" disabled={busy} onClick={() => void submit()} className="bg-[#C8900A] hover:bg-[#C8900A]/90">
          {busy ? <Loader2 size={14} className="animate-spin" /> : null} Confirm
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
