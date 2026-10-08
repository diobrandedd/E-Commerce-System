import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { ApiError } from "@/app/lib/api";
import { reportPackagingDamage, type MaterialStockRow } from "@/app/lib/materials";

interface PackagingDamageConfirmModalProps {
  open: boolean;
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

const INPUT_CLASS =
  "mt-1 w-full rounded-lg border-2 border-border bg-card px-3 py-2.5 text-sm " +
  "outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-shadow";

export function PackagingDamageConfirmModal({
  open,
  stock,
  onClose,
  onDone,
}: PackagingDamageConfirmModalProps) {
  const [qty, setQty] = useState("1");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);
  const onHand = Number(stock.on_hand ?? 0);

  const handleClose = () => {
    setQty("1");
    setReason("");
    setBusy(false);
    setError(null);
    setOk(null);
    onClose();
  };

  const submit = async () => {
    const qtyNum = parseFloat(qty);
    if (!Number.isFinite(qtyNum) || qtyNum <= 0) {
      setError("Enter a valid quantity (greater than 0).");
      return;
    }
    if (stock.unit === "roll" && Math.abs(qtyNum - Math.round(qtyNum)) > 0.0001) {
      setError("Roll quantity must be a whole number (e.g. 1 roll).");
      return;
    }
    if (qtyNum > onHand) {
      setError(`Only ${onHand.toLocaleString()} ${stock.unit} on hand.`);
      return;
    }
    if (!reason.trim()) {
      setError("Reason is required (e.g. Torn pouch, wet roll).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await reportPackagingDamage({
        stock_id: stock.id,
        qty: qtyNum,
        reason: reason.trim(),
      });
      setOk(`Reported ${qtyNum} ${stock.unit} damaged.`);
      onDone();
      setTimeout(() => {
        handleClose();
      }, 700);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not report damage.");
    } finally {
      setBusy(false);
    }
  };

  if (!open) return null;

  return (
    <AppModal open onClose={handleClose} panelClassName="max-w-md w-full">
      <AppModalHeader
        title="Report damage"
        subtitle={`${stock.name} · ${onHand.toLocaleString()} ${stock.unit} on hand`}
        onClose={handleClose}
      />
      <AppModalBody className="space-y-4">
        {error && <AppErrorBanner message={error} />}
        {ok && <AppSuccessNotice message={ok} />}
        <p className="text-xs text-muted-foreground leading-relaxed">
          Deducts warehouse on-hand and records this qty in the damaged pool. Reason is required.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="packaging-dmg-qty">
            Quantity ({stock.unit})
          </label>
          <input
            id="packaging-dmg-qty"
            type="number"
            min="0"
            step={stock.unit === "roll" ? "1" : "any"}
            value={qty}
            onChange={e => setQty(e.target.value)}
            className={INPUT_CLASS}
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="packaging-dmg-reason">
            Reason
          </label>
          <input
            id="packaging-dmg-reason"
            value={reason}
            onChange={e => setReason(e.target.value)}
            placeholder="e.g. Torn during packing"
            className={INPUT_CLASS}
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={handleClose} disabled={busy}>
          Cancel
        </Button>
        <Button type="button" variant="destructive" onClick={() => void submit()} disabled={busy}>
          {busy ? <Loader2 className="size-4 animate-spin" /> : null}
          Report damage
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
