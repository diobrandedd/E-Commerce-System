import { useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { Button } from "@/app/components/ui/button";
import { createMaterial, type MaterialCategory } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface MaterialCatalogModalProps {
  category: MaterialCategory;
  onClose: () => void;
  onDone: () => void;
}

export function MaterialCatalogModal({ category, onClose, onDone }: MaterialCatalogModalProps) {
  const [name, setName] = useState("");
  const [unit, setUnit] = useState("pcs");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    if (!name.trim()) {
      setError("Name is required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await createMaterial({
        name: name.trim(),
        dept_id: 0,
        category,
        unit: unit.trim() || "pcs",
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not create item.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose}>
      <AppModalHeader title="Add inventory item" subtitle="Supplies" onClose={onClose} />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Name</label>
          <input
            value={name}
            onChange={e => setName(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="Honey Bottles"
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void submit();
              }
            }}
          />
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground">Unit</label>
          <input
            value={unit}
            onChange={e => setUnit(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
          />
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose}>
          Cancel
        </Button>
        <Button type="button" disabled={busy} onClick={() => void submit()}>
          {busy ? <Loader2 className="animate-spin" size={14} /> : null}
          Create
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
