import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { adjustMaterial, submitMaterialRequest, type MaterialStockRow } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface MaterialAdjustModalProps {
  stock: MaterialStockRow;
  mode: "executive" | "request";
  onClose: () => void;
  onDone: () => void;
}

export function MaterialAdjustModal({ stock, mode, onClose, onDone }: MaterialAdjustModalProps) {
  const [action, setAction] = useState<"add" | "deduct">("add");
  const [amount, setAmount] = useState("");
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    const qty = parseFloat(amount);
    if (!Number.isFinite(qty) || qty <= 0) {
      setError("Enter a positive quantity.");
      return;
    }
    if (!reason.trim()) {
      setError("Reason is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      if (mode === "executive") {
        await adjustMaterial({ stock_id: stock.id, action, amount: qty, reason: reason.trim() });
      } else {
        await submitMaterialRequest({ stock_id: stock.id, action, amount: qty, reason: reason.trim() });
      }
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Request failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader
        title={mode === "executive" ? "Adjust on-hand" : "Request adjustment"}
        subtitle={`${stock.name} · current ${stock.on_hand} ${stock.unit}`}
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex gap-2">
          {(["add", "deduct"] as const).map(a => (
            <button
              key={a}
              type="button"
              onClick={() => setAction(a)}
              className={`flex-1 text-sm font-semibold py-2 rounded-lg border ${
                action === a ? "border-[#C8900A] bg-[#C8900A]/10 text-[#C8900A]" : "border-border"
              }`}
            >
              {a === "add" ? "Add" : "Deduct"}
            </button>
          ))}
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Quantity ({stock.unit})</label>
          <input
            type="number"
            min="0"
            step="any"
            value={amount}
            onChange={e => setAmount(e.target.value)}
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
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : mode === "executive" ? "Apply" : "Submit request"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
