import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { listMaterials, submitSupplyOnHandRequest, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface SupplyOnHandRequestModalProps {
  stock?: MaterialStockRow | null;
  onClose: () => void;
  onDone: () => void;
}

export function SupplyOnHandRequestModal({ stock: initialStock, onClose, onDone }: SupplyOnHandRequestModalProps) {
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [stockId, setStockId] = useState(initialStock ? String(initialStock.id) : "");
  const [onHand, setOnHand] = useState(initialStock ? String(initialStock.on_hand ?? "") : "");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const selected = items.find(s => String(s.id) === stockId) ?? initialStock ?? null;

  useEffect(() => {
    void listMaterials("supply").then(d => setItems(d.items));
  }, []);

  useEffect(() => {
    if (initialStock) {
      setStockId(String(initialStock.id));
      setOnHand(String(initialStock.on_hand ?? ""));
    }
  }, [initialStock]);

  const submit = async () => {
    const id = parseInt(stockId, 10);
    const qty = parseFloat(onHand);
    if (!Number.isFinite(id) || id < 1) {
      setError("Select a supply item.");
      return;
    }
    if (!Number.isFinite(qty) || qty < 0) {
      setError("Enter the target on-hand count.");
      return;
    }
    if (!reason.trim()) {
      setError("Reason is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await submitSupplyOnHandRequest({ stock_id: id, on_hand: qty, reason: reason.trim() });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not submit request.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full">
      <AppModalHeader
        title="Request count correction"
        subtitle="Supply on-hand — executive approval required"
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        {!initialStock && (
          <div>
            <label className="text-xs font-medium text-muted-foreground">Item</label>
            <select
              value={stockId}
              onChange={e => {
                setStockId(e.target.value);
                const row = items.find(s => String(s.id) === e.target.value);
                if (row) setOnHand(String(row.on_hand ?? ""));
              }}
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            >
              <option value="">— Select —</option>
              {items.map(row => (
                <option key={row.id} value={row.id}>
                  {row.name} ({row.on_hand} {row.unit})
                </option>
              ))}
            </select>
          </div>
        )}
        {selected && (
          <p className="text-xs text-muted-foreground">
            Current on hand: <strong>{selected.on_hand} {selected.unit}</strong>
          </p>
        )}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Target on hand ({selected?.unit ?? "pcs"})</label>
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
          <label className="text-xs font-medium text-muted-foreground">Reason</label>
          <textarea
            value={reason}
            onChange={e => setReason(e.target.value)}
            rows={3}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm resize-none"
            placeholder="e.g. Cycle count — physical count differs from system"
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
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Submit request"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
