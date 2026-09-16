import { useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { ApiError } from "@/app/lib/api";
import { deptRawExecutiveSetGrams, type DeptBagPool } from "@/app/lib/deptRawInventory";

type Props = {
  productKey: "pt" | "brc" | "jar_cacao";
  title: string;
  pool: DeptBagPool;
  onClose: () => void;
  onSaved: () => void;
};

function formatGrams(n: number): string {
  return n.toLocaleString(undefined, { maximumFractionDigits: 1 });
}

export function ExecutiveRawPoolEditModal({ productKey, title, pool, onClose, onSaved }: Props) {
  const fieldId = useId();
  const [grams, setGrams] = useState(() => {
    const g = Number(pool.grams_remaining);
    if (!Number.isFinite(g) || g < 0) return "0";
    return String(Math.round(g * 10) / 10);
  });
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const current = Number(pool.grams_remaining);
  const overdrawn = pool.overdrawn || (Number.isFinite(current) && current < -0.0001);
  const kgHint =
    pool.grams_per_bag > 0
      ? `${formatGrams(Number(grams) || 0)} g ≈ ${((Number(grams) || 0) / pool.grams_per_bag).toLocaleString(undefined, {
          maximumFractionDigits: 4,
        })} kg`
      : null;

  const submit = async () => {
    setBusy(true);
    setErr(null);
    const value = parseFloat(grams);
    if (!Number.isFinite(value) || value < 0) {
      setErr("Enter a non-negative gram amount.");
      setBusy(false);
      return;
    }
    try {
      await deptRawExecutiveSetGrams({
        product_key: productKey,
        grams: value,
        note: "Executive pool correction",
      });
      onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Save failed");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="exec-raw-pool-edit-title">
      <AppModalHeader
        title={`Edit leaf pool — ${title}`}
        titleId="exec-raw-pool-edit-title"
        subtitle="Sets the remaining leaf pool in grams. Stored as kg (grams ÷ Settings g/kg). Does not change finished pouches/jars."
        onClose={onClose}
      />
      <AppModalBody className="space-y-3 text-sm">
        {err && <AppErrorBanner message={err} />}
        <p className="text-xs text-muted-foreground">
          On hand now:{" "}
          <span className="font-semibold text-foreground">
            {overdrawn
              ? `−${formatGrams(Math.abs(current))} g overdrawn`
              : `${formatGrams(current)} g`}
          </span>
          <span className="ml-1">
            · {pool.bags_on_hand.toLocaleString(undefined, { maximumFractionDigits: 4 })} kg
          </span>
        </p>
        <div>
          <label htmlFor={fieldId} className="text-xs text-muted-foreground block mb-1">
            Grams remaining (exact)
          </label>
          <input
            id={fieldId}
            type="number"
            step="0.1"
            min="0"
            value={grams}
            onChange={e => setGrams(e.target.value)}
            className="w-full border rounded-lg px-3 py-2"
            autoFocus
          />
          {kgHint ? <p className="mt-1 text-[11px] text-muted-foreground font-mono">{kgHint}</p> : null}
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose} className="min-h-11 flex-1">
          Cancel
        </Button>
        <Button type="button" disabled={busy} onClick={() => void submit()} className="min-h-11 flex-1">
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Save changes"}
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
