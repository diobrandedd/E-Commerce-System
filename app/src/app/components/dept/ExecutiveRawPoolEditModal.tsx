import { useId, useState } from "react";
import { Loader2 } from "lucide-react";
import { OverrideConfirmModal } from "@/app/components/approvals/OverrideConfirmModal";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
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
  const [ok, setOk] = useState<string | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);

  const current = Number(pool.grams_remaining);
  const overdrawn = pool.overdrawn || (Number.isFinite(current) && current < -0.0001);
  const kgHint =
    pool.grams_per_bag > 0
      ? `${formatGrams(Number(grams) || 0)} g ≈ ${((Number(grams) || 0) / pool.grams_per_bag).toLocaleString(undefined, {
          maximumFractionDigits: 4,
        })} kg`
      : null;

  const openConfirm = () => {
    setErr(null);
    const value = parseFloat(grams);
    if (!Number.isFinite(value) || value < 0) {
      setErr("Enter a non-negative gram amount.");
      return;
    }
    setConfirmOpen(true);
  };

  const submit = async () => {
    setBusy(true);
    setErr(null);
    const value = parseFloat(grams);
    try {
      const res = await deptRawExecutiveSetGrams({
        product_key: productKey,
        grams: value,
        note: "Executive pool correction",
      });
      if (res.pending) {
        setOk(
          res.email_sent === false
            ? `Sent for HR approval (#${res.request_id}). Email could not be sent${res.email_error ? `: ${res.email_error}` : "."}`
            : `Sent to HR manager for approval (#${res.request_id}).`,
        );
        setConfirmOpen(false);
        return;
      }
      setConfirmOpen(false);
      onSaved();
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Save failed");
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="exec-raw-pool-edit-title">
        <AppModalHeader
          title={`Edit leaf pool — ${title}`}
          titleId="exec-raw-pool-edit-title"
          subtitle="Sets the remaining leaf pool in grams. Non–HR managers send this for HR approval. Does not change finished pouches or jars."
          onClose={onClose}
        />
        <AppModalBody className="space-y-3 text-sm">
          {err && <AppErrorBanner message={err} />}
          <AppSuccessNotice message={ok} />
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
          <Button type="button" disabled={busy || !!ok} onClick={openConfirm} className="min-h-11 flex-1">
            {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Continue"}
          </Button>
        </AppModalFooter>
      </AppModal>

      <OverrideConfirmModal
        open={confirmOpen}
        busy={busy}
        error={err}
        title="Send leaf pool edit for approval?"
        summary={
          <p>
            Set <span className="font-semibold">{title}</span> leaf pool to{" "}
            <span className="font-semibold tabular-nums">{formatGrams(Number(grams) || 0)} g</span>
            {kgHint ? <span className="text-muted-foreground"> ({kgHint})</span> : null}.
          </p>
        }
        onClose={() => {
          if (busy) return;
          setConfirmOpen(false);
        }}
        onConfirm={submit}
      />
    </>
  );
}
