import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2, Plus } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import {
  AppErrorBanner,
  AppPressable,
  AppSuccessNotice,
  appHoverRow,
} from "@/app/components/layout/appFeedbackMotion";
import { PageLayout } from "@/app/components/PageLayout";
import { Button } from "@/app/components/ui/button";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { apiPost, ApiError } from "@/app/lib/api";
import { HONEY_SIZES, HONEY_VARIANTS } from "@/app/lib/honey";
import { variantLabel } from "@/app/lib/posHoney";
import {
  rtsListDamagedInventory,
  rtsListDamagedUseLog,
  rtsUseDamaged,
  type RtsDamagedUseLogRow,
} from "@/app/lib/rtsApi";
import { cn } from "@/app/components/ui/utils";

interface RtsInvRow {
  id?: number;
  first_row_id?: number;
  line_count?: number;
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

const OTHER_PRODUCT_PRESETS = [
  "Paragis Tea",
  "Black Rice Coffee",
  "Jar Cacao",
  "Honey Comb",
] as const;

export function RTSDamagedInventoryPage() {
  const [honey, setHoney] = useState<RtsInvRow[]>([]);
  const [other, setOther] = useState<RtsInvRow[]>([]);
  const [useLog, setUseLog] = useState<RtsDamagedUseLogRow[]>([]);
  const [canUseDamaged, setCanUseDamaged] = useState(false);
  const [canExecutiveEdit, setCanExecutiveEdit] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [useRow, setUseRow] = useState<RtsInvRow | null>(null);
  const [editHoney, setEditHoney] = useState<RtsInvRow | null>(null);
  const [editOther, setEditOther] = useState<RtsInvRow | null>(null);
  const [addHoneyOpen, setAddHoneyOpen] = useState(false);
  const [addOtherOpen, setAddOtherOpen] = useState(false);

  const loadUseLog = useCallback(async () => {
    try {
      const res = await rtsListDamagedUseLog(100);
      setUseLog(res.rows ?? []);
    } catch {
      setUseLog([]);
    }
  }, []);

  const loadInventory = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const inv = await rtsListDamagedInventory();
      setHoney(inv.honey as unknown as RtsInvRow[]);
      setOther(inv.other as unknown as RtsInvRow[]);
      setCanUseDamaged(!!inv.can_use_damaged);
      setCanExecutiveEdit(!!inv.can_executive_edit);
      await loadUseLog();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load damaged RTS stock.");
    } finally {
      setLoading(false);
    }
  }, [loadUseLog]);

  useEffect(() => {
    void loadInventory();
  }, [loadInventory]);

  const flash = (msg: string) => {
    setToast(msg);
    window.setTimeout(() => setToast(null), 3500);
  };

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
        {canExecutiveEdit && " Executives can edit quantities or add rows."}
        {!canUseDamaged && !canExecutiveEdit && " Your role can view this list only."}
      </p>

      {loading ? (
        <div className="text-sm text-muted-foreground py-8 text-center">
          <Loader2 className="inline animate-spin mr-2" size={14} /> Loading…
        </div>
      ) : (
        <>
          <section className="mb-6">
            <div className="flex items-center justify-between gap-2 mb-3">
              <h3 className="text-sm font-semibold">Honey (damaged)</h3>
              {canExecutiveEdit && (
                <AppPressable
                  type="button"
                  onClick={() => setAddHoneyOpen(true)}
                  className="text-xs font-semibold text-[#C8900A] inline-flex items-center"
                >
                  <Plus size={14} className="mr-1" /> Add honey
                </AppPressable>
              )}
            </div>
            <DamagedTable
              rows={honey}
              honey
              canUse={canUseDamaged}
              onUse={setUseRow}
              onEdit={canExecutiveEdit ? setEditHoney : undefined}
            />
          </section>
          <section className="mb-8">
            <div className="flex items-center justify-between gap-2 mb-3">
              <h3 className="text-sm font-semibold">Other products (damaged)</h3>
              {canExecutiveEdit && (
                <AppPressable
                  type="button"
                  onClick={() => setAddOtherOpen(true)}
                  className="text-xs font-semibold text-[#C8900A] inline-flex items-center"
                >
                  <Plus size={14} className="mr-1" /> Add product
                </AppPressable>
              )}
            </div>
            <DamagedTable
              rows={other}
              honey={false}
              canUse={canUseDamaged}
              onUse={setUseRow}
              onEdit={canExecutiveEdit ? setEditOther : undefined}
            />
          </section>
          <section>
            <h3 className="text-sm font-semibold mb-3">Used damaged history</h3>
            <DamagedUseLogTable rows={useLog} />
          </section>
        </>
      )}

      {useRow && (
        <UseDamagedModal
          row={useRow}
          onClose={() => setUseRow(null)}
          onDone={msg => {
            setUseRow(null);
            flash(msg);
            void loadInventory();
          }}
          onError={msg => setError(msg)}
        />
      )}

      {(addHoneyOpen || editHoney) && (
        <EditHoneyModal
          row={addHoneyOpen ? null : editHoney}
          existingVariants={honey.map(r => (r.variant ?? "").toLowerCase()).filter(Boolean)}
          onClose={() => {
            setAddHoneyOpen(false);
            setEditHoney(null);
          }}
          onDone={msg => {
            setAddHoneyOpen(false);
            setEditHoney(null);
            flash(msg);
            void loadInventory();
          }}
          onError={msg => setError(msg)}
        />
      )}

      {(addOtherOpen || editOther) && (
        <EditOtherModal
          row={addOtherOpen ? null : editOther}
          onClose={() => {
            setAddOtherOpen(false);
            setEditOther(null);
          }}
          onDone={msg => {
            setAddOtherOpen(false);
            setEditOther(null);
            flash(msg);
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
  onEdit,
}: {
  rows: RtsInvRow[];
  honey: boolean;
  canUse: boolean;
  onUse: (r: RtsInvRow) => void;
  onEdit?: (r: RtsInvRow) => void;
}) {
  const showActions = !!(canUse || onEdit);
  if (!rows.length) {
    return (
      <p className="text-sm text-muted-foreground">
        No damaged rows yet.
        {onEdit
          ? " Use Add above to create inventory, or sync from leak cases."
          : " Record a leak case with damaged lines, then run RTS sync."}
      </p>
    );
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
              {showActions && <th className="px-3 py-2" />}
            </tr>
          </thead>
          <tbody>
            {rows.map(r => (
              <tr key={r.variant ?? r.product_label} className={cn("border-b", appHoverRow)}>
                <td className="px-3 py-2 capitalize">
                  {r.variant ?? r.product_label}
                  {(r.line_count ?? 0) > 1 && (
                    <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                  )}
                </td>
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
                {showActions && (
                  <td className="px-3 py-2 whitespace-nowrap space-x-2">
                    {onEdit && (
                      <AppPressable type="button" onClick={() => onEdit(r)} className="text-xs font-semibold text-foreground">
                        Edit
                      </AppPressable>
                    )}
                    {canUse && (
                      <Button type="button" size="sm" variant="outline" onClick={() => onUse(r)}>
                        Use damaged
                      </Button>
                    )}
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
            {showActions && <th className="px-3 py-2" />}
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.product_label} className={cn("border-b", appHoverRow)}>
              <td className="px-3 py-2">
                {r.product_label}
                {(r.line_count ?? 0) > 1 && (
                  <span className="ml-1 text-[10px] text-muted-foreground">({r.line_count} lots)</span>
                )}
              </td>
              <td className="px-3 py-2">
                <QtyCounter current={r.current_qty} initial={r.initial_qty} />
              </td>
              {showActions && (
                <td className="px-3 py-2 whitespace-nowrap space-x-2">
                  {onEdit && (
                    <AppPressable type="button" onClick={() => onEdit(r)} className="text-xs font-semibold text-foreground">
                      Edit
                    </AppPressable>
                  )}
                  {canUse && (
                    <Button type="button" size="sm" variant="outline" onClick={() => onUse(r)}>
                      Use damaged
                    </Button>
                  )}
                </td>
              )}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function DamagedUseLogTable({ rows }: { rows: RtsDamagedUseLogRow[] }) {
  if (!rows.length) {
    return <p className="text-sm text-muted-foreground">No use-damaged entries yet.</p>;
  }
  return (
    <div className="bg-card rounded-xl border overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b bg-secondary/40 text-xs uppercase text-muted-foreground">
            <th className="px-3 py-2 text-left">Date</th>
            <th className="px-3 py-2 text-left">What was used</th>
            <th className="px-3 py-2 text-left">Account</th>
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.id} className={cn("border-b", appHoverRow)}>
              <td className="px-3 py-2 whitespace-nowrap text-xs">
                {r.created_at ? new Date(r.created_at.replace(" ", "T")).toLocaleString() : "—"}
              </td>
              <td className="px-3 py-2">
                {r.summary}
                {r.notes ? <span className="block text-[11px] text-muted-foreground mt-0.5">{r.notes}</span> : null}
              </td>
              <td className="px-3 py-2 font-medium">{r.account || "—"}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function EditHoneyModal({
  row,
  existingVariants,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow | null;
  existingVariants: string[];
  onClose: () => void;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const isAdd = !row;
  const availableVariants = isAdd
    ? HONEY_VARIANTS.filter(v => !existingVariants.includes(v))
    : [...HONEY_VARIANTS];
  const [variant, setVariant] = useState(
    () => row?.variant ?? availableVariants[0] ?? "light",
  );
  const [sizes, setSizes] = useState<Record<string, string>>(() =>
    row
      ? {
          liter: String(row.current_liter ?? "0"),
          "250ml": String(row.current_250ml ?? "0"),
          "350ml": String(row.current_350ml ?? "0"),
          gallon: String(row.current_gallon ?? "0"),
        }
      : { liter: "0", "250ml": "0", "350ml": "0", gallon: "0" },
  );
  const [busy, setBusy] = useState(false);

  const save = async () => {
    const v = (isAdd ? variant : row?.variant ?? variant).toLowerCase();
    if (!v) {
      onError("Pick a honey variant.");
      return;
    }
    setBusy(true);
    try {
      const size_qtys: Record<string, number> = {};
      for (const s of HONEY_SIZES) size_qtys[s] = Number(sizes[s] ?? 0);
      await apiPost("rts.php", "inventory_set_honey", {
        variant: v,
        pool: "damaged",
        size_qtys,
      });
      onDone(isAdd ? "Damaged honey added." : "Damaged honey updated.");
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save damaged honey.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={busy ? () => undefined : onClose} panelClassName="max-w-md w-full" labelledBy="dmg-edit-honey-title">
      <AppModalHeader
        title={isAdd ? "Add damaged honey" : `Edit — ${variantLabel(row?.variant ?? "")}`}
        titleId="dmg-edit-honey-title"
        onClose={busy ? () => undefined : onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {isAdd && (
          <label className="block text-xs space-y-1">
            <span className="uppercase text-muted-foreground font-semibold">Variant</span>
            {availableVariants.length === 0 ? (
              <p className="text-amber-800 text-sm">All variants already exist — use Edit on a row.</p>
            ) : (
              <select
                value={variant}
                onChange={e => setVariant(e.target.value)}
                className="w-full border rounded-lg px-3 py-2 bg-card"
              >
                {availableVariants.map(v => (
                  <option key={v} value={v}>{variantLabel(v)}</option>
                ))}
              </select>
            )}
          </label>
        )}
        <div className="grid grid-cols-2 gap-2">
          {HONEY_SIZES.map(s => (
            <label key={s} className="text-xs space-y-1">
              <span className="uppercase text-muted-foreground font-semibold">{s}</span>
              <input
                type="number"
                min="0"
                step="0.01"
                value={sizes[s] ?? "0"}
                onChange={e => setSizes(p => ({ ...p, [s]: e.target.value }))}
                className="w-full border rounded-lg px-3 py-2"
              />
            </label>
          ))}
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
        <Button
          type="button"
          disabled={busy || (isAdd && availableVariants.length === 0)}
          onClick={() => void save()}
          style={{ background: "#C8900A" }}
          className="text-white"
        >
          {busy ? "Saving…" : "Save"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}

function EditOtherModal({
  row,
  onClose,
  onDone,
  onError,
}: {
  row: RtsInvRow | null;
  onClose: () => void;
  onDone: (msg: string) => void;
  onError: (msg: string) => void;
}) {
  const isAdd = !row;
  const [label, setLabel] = useState(row?.product_label ?? OTHER_PRODUCT_PRESETS[0]);
  const [customLabel, setCustomLabel] = useState("");
  const [qty, setQty] = useState(String(row?.current_qty ?? "0"));
  const [busy, setBusy] = useState(false);

  const save = async () => {
    const productLabel = isAdd
      ? (label === "__custom__" ? customLabel.trim() : label.trim())
      : (row?.product_label ?? "").trim();
    if (!productLabel) {
      onError("Product name is required.");
      return;
    }
    setBusy(true);
    try {
      await apiPost("rts.php", "inventory_set_other", {
        product_label: productLabel,
        qty: Number(qty),
        pool: "damaged",
      });
      onDone(isAdd ? "Damaged product added." : "Damaged product updated.");
    } catch (e) {
      onError(e instanceof ApiError ? e.message : "Could not save damaged product.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={busy ? () => undefined : onClose} panelClassName="max-w-sm w-full" labelledBy="dmg-edit-other-title">
      <AppModalHeader
        title={isAdd ? "Add damaged product" : `Edit — ${row?.product_label ?? ""}`}
        titleId="dmg-edit-other-title"
        onClose={busy ? () => undefined : onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {isAdd && (
          <>
            <label className="block text-xs space-y-1">
              <span className="uppercase text-muted-foreground font-semibold">Product</span>
              <select
                value={label}
                onChange={e => setLabel(e.target.value)}
                className="w-full border rounded-lg px-3 py-2 bg-card"
              >
                {OTHER_PRODUCT_PRESETS.map(p => (
                  <option key={p} value={p}>{p}</option>
                ))}
                <option value="__custom__">Custom…</option>
              </select>
            </label>
            {label === "__custom__" && (
              <input
                value={customLabel}
                onChange={e => setCustomLabel(e.target.value)}
                placeholder="Product name"
                className="w-full border rounded-lg px-3 py-2"
              />
            )}
          </>
        )}
        <label className="block text-xs space-y-1">
          <span className="uppercase text-muted-foreground font-semibold">Quantity</span>
          <input
            type="number"
            min="0"
            step="0.01"
            value={qty}
            onChange={e => setQty(e.target.value)}
            className="w-full border rounded-lg px-3 py-2"
          />
        </label>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" disabled={busy} onClick={onClose}>Cancel</Button>
        <Button
          type="button"
          disabled={busy}
          onClick={() => void save()}
          style={{ background: "#C8900A" }}
          className="text-white"
        >
          {busy ? "Saving…" : "Save"}
        </Button>
      </AppModalFooter>
    </AppModal>
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
        row_id: row.first_row_id ?? row.id ?? 0,
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
