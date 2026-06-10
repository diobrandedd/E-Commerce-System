import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { setPackagingValues, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingSetValuesModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

export function PackagingSetValuesModal({ stock, onClose, onDone }: PackagingSetValuesModalProps) {
  const [price, setPrice] = useState(String(stock.current_price ?? "0"));
  const [onHand, setOnHand] = useState(String(stock.on_hand ?? "0"));
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    const priceNum = parseFloat(price);
    const onHandNum = parseFloat(onHand);
    if (!Number.isFinite(priceNum) || priceNum < 0) {
      setError("Enter a valid unit price (0 or greater).");
      return;
    }
    if (!Number.isFinite(onHandNum) || onHandNum < 0) {
      setError("Enter a valid on-hand quantity (0 or greater).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await setPackagingValues({
        stock_id: stock.id,
        current_price: priceNum,
        on_hand: onHandNum,
        note: note.trim() || undefined,
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save values.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Set price & quantity"
        subtitle={`${stock.name} · ${stock.unit}`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <p className="text-xs text-muted-foreground">
          Enter the exact unit price and on-hand count. Price updates recalculate product costing for any formula lines using this stock.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Unit price (₱)</label>
          <input
            type="number"
            min="0"
            step="0.01"
            value={price}
            onChange={e => setPrice(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Quantity on hand ({stock.unit})</label>
          <input
            type="number"
            min="0"
            step="any"
            value={onHand}
            onChange={e => setOnHand(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Note (optional)</label>
          <input
            value={note}
            onChange={e => setNote(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. Monthly count"
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
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Save"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
