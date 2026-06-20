import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { listMaterials, submitSupplyIssueRequest, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface SupplyIssueRequestModalProps {
  stock?: MaterialStockRow | null;
  onClose: () => void;
  onDone: () => void;
}

export function SupplyIssueRequestModal({ stock: initialStock, onClose, onDone }: SupplyIssueRequestModalProps) {
  const [items, setItems] = useState<MaterialStockRow[]>([]);
  const [stockId, setStockId] = useState(initialStock ? String(initialStock.id) : "");
  const [requesterName, setRequesterName] = useState("");
  const [qty, setQty] = useState("");
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
    }
  }, [initialStock]);

  const submit = async () => {
    const id = parseInt(stockId, 10);
    const amount = parseFloat(qty);
    if (!Number.isFinite(id) || id < 1) {
      setError("Select a supply item.");
      return;
    }
    if (!requesterName.trim()) {
      setError("Requester name is required.");
      return;
    }
    if (!Number.isFinite(amount) || amount <= 0) {
      setError("Enter a valid quantity to issue.");
      return;
    }
    if (!reason.trim()) {
      setError("Reason is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await submitSupplyIssueRequest({
        stock_id: id,
        requester_name: requesterName.trim(),
        amount,
        reason: reason.trim(),
      });
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
        title="Supply issue request"
        subtitle="For staff who asked offline — executive approval required"
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        {!initialStock && (
          <div>
            <label className="text-xs font-medium text-muted-foreground">Item</label>
            <select
              value={stockId}
              onChange={e => setStockId(e.target.value)}
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            >
              <option value="">— Select —</option>
              {items.map(row => (
                <option key={row.id} value={row.id}>
                  {row.name} ({row.on_hand} {row.unit} on hand)
                </option>
              ))}
            </select>
          </div>
        )}
        {selected && (
          <p className="text-xs text-muted-foreground">
            On hand: <strong>{selected.on_hand} {selected.unit}</strong>
          </p>
        )}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Requester name</label>
          <input
            value={requesterName}
            onChange={e => setRequesterName(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="e.g. Maria Santos"
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Quantity ({selected?.unit ?? "pcs"})</label>
          <input
            type="number"
            min="0.0001"
            step="any"
            value={qty}
            onChange={e => setQty(e.target.value)}
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
            placeholder="e.g. Production floor — gloves"
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
