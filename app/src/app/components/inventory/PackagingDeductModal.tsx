import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { deductPackagingForUse, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingDeductModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

export function PackagingDeductModal({ stock, onClose, onDone }: PackagingDeductModalProps) {
  const [qty, setQty] = useState("1");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const onHand = Number(stock.on_hand ?? 0);

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
      setError("Reason is required (e.g. Paragis pouch run, honey bottling).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await deductPackagingForUse({
        stock_id: stock.id,
        qty: qtyNum,
        reason: reason.trim(),
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not record deduction.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Deduct for use"
        subtitle={`${stock.name} · ${onHand.toLocaleString()} ${stock.unit} on hand`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <p className="text-xs text-muted-foreground">
          Record packaging taken for production. On-hand goes down; unit cost from purchase lots is unchanged.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Quantity ({stock.unit})</label>
          <input
            type="number"
            min="0"
            step={stock.unit === "roll" ? "1" : "any"}
            value={qty}
            onChange={e => setQty(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Reason</label>
          <input
            value={reason}
            onChange={e => setReason(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. Packed 50 PT pouches"
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <button type="button" onClick={onClose} className="text-sm px-4 py-2 rounded-lg border border-border">
          Cancel
        </button>
        <button
          type="button"
          disabled={busy}
          onClick={() => void submit()}
          className="text-sm font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-60"
          style={{ background: "#C8900A" }}
        >
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Deduct"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
