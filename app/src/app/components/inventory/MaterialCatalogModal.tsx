import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import {
  createMaterial,
  listSupplyCategories,
  type MaterialCategory,
  type SupplyCategory,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface MaterialCatalogModalProps {
  category: MaterialCategory;
  onClose: () => void;
  onDone: () => void;
}

export function MaterialCatalogModal({ category, onClose, onDone }: MaterialCatalogModalProps) {
  const [name, setName] = useState("");
  const [unit, setUnit] = useState("pcs");
  const [categories, setCategories] = useState<SupplyCategory[]>([]);
  const [categoryId, setCategoryId] = useState("");
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const isSupplies = category === "packaging";

  useEffect(() => {
    if (!isSupplies) return;
    void listSupplyCategories()
      .then(res => setCategories(res.categories))
      .catch(e => setError(e instanceof ApiError ? e.message : "Could not load categories."));
  }, [isSupplies]);

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
        ...(isSupplies && categoryId ? { supply_category_id: Number(categoryId) } : {}),
        ...(isSupplies ? { supply_note: note.trim() } : {}),
      });
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not create item.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose} labelledBy="add-supply-title">
      <AppModalHeader title="Add inventory item" titleId="add-supply-title" subtitle="Supplies" onClose={onClose} />
      <AppModalBody className="space-y-4">
        <AppErrorBanner message={error} />
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-item-name">Name</label>
          <input
            id="supply-item-name"
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
        {isSupplies && (
          <div>
            <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-item-category">
              Category
            </label>
            <select
              id="supply-item-category"
              value={categoryId}
              onChange={e => setCategoryId(e.target.value)}
              className="mt-1 w-full min-h-10 rounded-lg border border-border bg-card px-3 py-2 text-sm outline-none focus:border-primary"
            >
              <option value="">Uncategorized</option>
              {categories.map(c => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <p className="text-[11px] text-muted-foreground mt-1">
              New categories and bulk assignment are on Categories from the supplies page.
            </p>
          </div>
        )}
        {isSupplies && (
          <div>
            <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-item-note">
              Note
            </label>
            <textarea
              id="supply-item-note"
              value={note}
              onChange={e => setNote(e.target.value)}
              maxLength={500}
              rows={3}
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
              placeholder="Where it came from, or any other detail"
            />
          </div>
        )}
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-item-unit">Unit</label>
          <input
            id="supply-item-unit"
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
