import { useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { recordPackagingInvoicePrice, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingPriceLotModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

const peso = (n: number) =>
  `₱${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function PackagingPriceLotModal({ stock, onClose, onDone }: PackagingPriceLotModalProps) {
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
      setError("Enter a valid invoice total (0 or greater).");
      return;
    }
    if (!Number.isFinite(qtyNum) || qtyNum <= 0) {
      setError("Enter invoice quantity (greater than 0).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await recordPackagingInvoicePrice({
        stock_id: stock.id,
        lot_total_price: totalNum,
        lot_qty: qtyNum,
        note: note.trim() || undefined,
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not record invoice price.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Update price from invoice"
        subtitle={`${stock.name} · price only — on-hand unchanged`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <p className="text-xs text-muted-foreground">
          HR records what was paid on the supplier invoice. Production manager stocks in physical quantity separately.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Invoice total (₱)</label>
          <input
            type="number"
            min={0}
            step="0.01"
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card"
            value={totalPrice}
            onChange={(e) => setTotalPrice(e.target.value)}
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Invoice quantity ({stock.unit})</label>
          <input
            type="number"
            min={0}
            step="any"
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card"
            value={qty}
            onChange={(e) => setQty(e.target.value)}
          />
        </div>
        {unitPreview != null && (
          <p className="text-sm text-muted-foreground">Unit cost preview: {peso(unitPreview)}/{stock.unit}</p>
        )}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Note (optional)</label>
          <input
            type="text"
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 bg-card"
            value={note}
            onChange={(e) => setNote(e.target.value)}
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <button type="button" className="text-sm px-4 py-2 rounded-lg border" onClick={onClose} disabled={busy}>
          Cancel
        </button>
        <button
          type="button"
          className="text-sm px-4 py-2 rounded-lg bg-primary text-primary-foreground font-semibold disabled:opacity-50"
          onClick={() => void submit()}
          disabled={busy}
        >
          {busy ? <Loader2 className="h-4 w-4 animate-spin inline" /> : "Save price"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
