import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { setPackagingOnHand, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface PackagingOnHandSetModalProps {
  stock: MaterialStockRow;
  onClose: () => void;
  onDone: () => void;
}

export function PackagingOnHandSetModal({ stock, onClose, onDone }: PackagingOnHandSetModalProps) {
  const [onHand, setOnHand] = useState(String(stock.on_hand ?? "0"));
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    const qty = parseFloat(onHand);
    if (!Number.isFinite(qty) || qty < 0) {
      setError("Enter a valid on-hand count (0 or greater).");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await setPackagingOnHand({
        stock_id: stock.id,
        on_hand: qty,
        note: note.trim() || undefined,
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not update on-hand.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title="Set on-hand count"
        subtitle={`${stock.name} · executive count correction`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <p className="text-xs text-muted-foreground">
          Set the exact pieces on hand after a physical count. Unit cost from purchase lots is unchanged.
        </p>
        <div>
          <label className="text-xs font-medium text-muted-foreground">On hand ({stock.unit})</label>
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
            placeholder="e.g. Monthly cycle count"
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
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Save count"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
