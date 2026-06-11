import { useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { stockInPackagingLot, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingStockInLotModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function PackagingStockInLotModal({ stock, onClose, onDone }: PackagingStockInLotModalProps) {
  const [totalPrice, setTotalPrice] = useState("");
  const [qty, setQty] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const unitPreview = useMemo(() => {
    const total = parseFloat(totalPrice);
    const q = parseFloat(qty);
    if (!Number.isFinite(total) || !Number.isFinite(q) || q <= 0) return null;
    return total / q;
  }, [totalPrice, qty]);

  const submit = async () => {
    const totalNum = parseFloat(totalPrice);
    const qtyNum = parseFloat(qty);
    if (!Number.isFinite(totalNum) || totalNum < 0) {
      setError("Enter a valid total price paid (0 or greater).");
      return;
    }
    if (!Number.isFinite(qtyNum) || qtyNum <= 0) {
      setError("Enter a valid quantity received (greater than 0).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await stockInPackagingLot({
        stock_id: stock.id,
        lot_total_price: totalNum,
        lot_qty: qtyNum,
        note: note.trim() || undefined,
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not stock in lot.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Stock in lot"
        subtitle={`${stock.name} · ${stock.unit}`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <p className="text-xs text-muted-foreground">
          Enter the total amount paid for this delivery and how many pieces were received. Quantity is added to the on-hand pool. Unit cost (₱/pc) is derived from purchase lots — the preview below is what will be saved for costing.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Total price paid (₱)</label>
          <input
            type="number"
            min="0"
            step="0.01"
            value={totalPrice}
            onChange={e => setTotalPrice(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. 500"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Quantity received ({stock.unit})</label>
          <input
            type="number"
            min="0.0001"
            step="any"
            value={qty}
            onChange={e => setQty(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. 500"
          />
        </div>
        {unitPreview !== null && (
          <p className="text-sm font-medium text-foreground">
            → {peso(unitPreview)} per {stock.unit}
          </p>
        )}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Note (optional)</label>
          <input
            value={note}
            onChange={e => setNote(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. Supplier invoice #123"
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
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Stock in"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
