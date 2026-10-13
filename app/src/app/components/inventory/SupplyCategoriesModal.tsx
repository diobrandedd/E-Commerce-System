import { useEffect, useMemo, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { AppErrorBanner, AppSuccessNotice } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import {
  assignSuppliesToCategory,
  createSupplyCategory,
  listSupplyCategories,
  type MaterialStockRow,
  type SupplyCategory,
} from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface SupplyCategoriesModalProps {
  items: MaterialStockRow[];
  onClose: () => void;
  onDone: () => void;
}

export function SupplyCategoriesModal({ items, onClose, onDone }: SupplyCategoriesModalProps) {
  const [categories, setCategories] = useState<SupplyCategory[]>([]);
  const [categoryId, setCategoryId] = useState("");
  const [newName, setNewName] = useState("");
  const [selected, setSelected] = useState<number[]>([]);
  const [query, setQuery] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [ok, setOk] = useState<string | null>(null);

  useEffect(() => {
    void listSupplyCategories()
      .then(res => setCategories(res.categories))
      .catch(e => setError(e instanceof ApiError ? e.message : "Could not load categories."));
  }, []);

  useEffect(() => {
    if (!categoryId) {
      setSelected([]);
      return;
    }
    const id = Number(categoryId);
    setSelected(items.filter(item => item.supply_category_id === id).map(item => item.id));
  }, [categoryId, items]);

  const visible = useMemo(() => {
    const q = query.trim().toLowerCase();
    const rows = [...items].sort((a, b) => a.name.localeCompare(b.name));
    if (!q) return rows;
    return rows.filter(item =>
      item.name.toLowerCase().includes(q)
      || (item.barcode ?? "").toLowerCase().includes(q)
      || (item.supply_note ?? "").toLowerCase().includes(q),
    );
  }, [items, query]);

  const toggle = (id: number) => {
    setSelected(prev => (prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]));
  };

  const addCategory = async () => {
    const label = newName.trim();
    if (!label) {
      setError("Category name is required.");
      return;
    }
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      const res = await createSupplyCategory(label);
      setCategories(prev => {
        const next = prev.some(c => c.id === res.category.id) ? prev : [...prev, res.category];
        return next.sort((a, b) => a.name.localeCompare(b.name));
      });
      setCategoryId(String(res.category.id));
      setNewName("");
      setOk(`Category “${res.category.name}” is ready. Select supplies and assign them.`);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not add category.");
    } finally {
      setBusy(false);
    }
  };

  const assign = async () => {
    if (!categoryId) {
      setError("Choose a category first.");
      return;
    }
    if (selected.length === 0) {
      setError("Select at least one supply.");
      return;
    }
    setBusy(true);
    setError(null);
    setOk(null);
    try {
      const res = await assignSuppliesToCategory(Number(categoryId), selected);
      const name = categories.find(c => c.id === Number(categoryId))?.name ?? "that category";
      setOk(`${res.updated} suppl${res.updated === 1 ? "y" : "ies"} assigned to ${name}.`);
      onDone();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not assign supplies.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <AppModal open onClose={onClose} labelledBy="supply-categories-title" panelClassName="max-w-lg w-full">
      <AppModalHeader
        title="Categories"
        titleId="supply-categories-title"
        subtitle="Add a category, then assign several supplies to it."
        onClose={onClose}
      />
      <AppModalBody className="space-y-4">
        <AppErrorBanner message={error} />
        <AppSuccessNotice message={ok} />
        <div className="flex gap-2">
          <input
            value={newName}
            onChange={e => setNewName(e.target.value)}
            className="flex-1 rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="New category name"
            aria-label="New category name"
            onKeyDown={e => {
              if (e.key === "Enter") {
                e.preventDefault();
                void addCategory();
              }
            }}
          />
          <Button type="button" variant="outline" disabled={busy} onClick={() => void addCategory()}>
            Add category
          </Button>
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-assign-category">
            Assign to
          </label>
          <select
            id="supply-assign-category"
            value={categoryId}
            onChange={e => setCategoryId(e.target.value)}
            className="mt-1 w-full min-h-10 rounded-lg border border-border bg-card px-3 py-2 text-sm outline-none focus:border-primary"
          >
            <option value="">Choose a category</option>
            {categories.map(c => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="text-xs font-medium text-muted-foreground" htmlFor="supply-assign-search">
            Supplies
          </label>
          <input
            id="supply-assign-search"
            value={query}
            onChange={e => setQuery(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder="Search supplies…"
          />
          <div className="mt-2 max-h-64 overflow-y-auto rounded-lg border border-border divide-y divide-border">
            {visible.length === 0 ? (
              <p className="px-3 py-4 text-sm text-muted-foreground">No supplies match.</p>
            ) : visible.map(item => (
              <label key={item.id} className="flex items-start gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-secondary/40">
                <input
                  type="checkbox"
                  className="mt-1 h-4 w-4 accent-[#C8900A]"
                  checked={selected.includes(item.id)}
                  onChange={() => toggle(item.id)}
                />
                <span className="min-w-0">
                  <span className="block font-medium text-foreground truncate">{item.name}</span>
                  <span className="block text-xs text-muted-foreground truncate">
                    {item.supply_category_name ? item.supply_category_name : "Uncategorized"}
                    {item.supply_note ? ` · ${item.supply_note}` : ""}
                  </span>
                </span>
              </label>
            ))}
          </div>
          <p className="text-[11px] text-muted-foreground mt-1.5">
            {selected.length} selected. Supplies already in this category start checked.
          </p>
        </div>
      </AppModalBody>
      <AppModalFooter>
        <Button type="button" variant="outline" onClick={onClose}>Close</Button>
        <Button type="button" disabled={busy || !categoryId || selected.length === 0} onClick={() => void assign()}>
          {busy ? <Loader2 className="animate-spin" size={14} /> : null}
          Assign selected
        </Button>
      </AppModalFooter>
    </AppModal>
  );
}
