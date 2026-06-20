import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { createMaterial, listMaterialDepartments, type Department, type MaterialCategory } from "@/app/lib/materials";
import { ApiError } from "@/app/lib/api";

interface MaterialCatalogModalProps {
  category: MaterialCategory;
  onClose: () => void;
  onDone: () => void;
}

export function MaterialCatalogModal({ category, onClose, onDone }: MaterialCatalogModalProps) {
  const [name, setName] = useState("");
  const [unit, setUnit] = useState("pcs");
  const [deptId, setDeptId] = useState<number>(0);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void listMaterialDepartments().then(d => {
      setDepartments(d.departments);
      const honey = d.departments.find(x => x.name === "honey" || x.name === "Honey");
      if (honey) setDeptId(honey.id);
      else if (d.departments[0]) setDeptId(d.departments[0].id);
    });
  }, []);

  const isSupply = category === "supply";

  const submit = async () => {
    if (!name.trim()) {
      setError("Name is required.");
      return;
    }
    if (!isSupply && deptId < 1) {
      setError("Name and department are required.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      await createMaterial({
        name: name.trim(),
        dept_id: isSupply ? 0 : deptId,
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
      <AppModalHeader title="Add inventory item" subtitle={category === "supply" ? "Supplies" : "Packaging materials"} onClose={onClose} />
      <AppModalBody className="space-y-4">
        {error && <p className="text-sm text-red-600">{error}</p>}
        <div>
          <label className="text-xs font-medium text-muted-foreground">Name</label>
          <input
            value={name}
            onChange={e => setName(e.target.value)}
            className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
            placeholder={category === "supply" ? "Gloves" : "Honey Bottles"}
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
        {!isSupply && (
          <div>
            <label className="text-xs font-medium text-muted-foreground">Department</label>
            <select
              value={deptId}
              onChange={e => setDeptId(Number(e.target.value))}
              className="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm bg-background"
            >
              {departments.map(d => (
                <option key={d.id} value={d.id}>{d.name}</option>
              ))}
            </select>
          </div>
        )}
      </AppModalBody>
      <AppModalFooter>
        <button type="button" onClick={onClose} className="text-sm px-4 py-2 rounded-lg border border-border">Cancel</button>
        <button
          type="button"
          disabled={busy}
          onClick={() => void submit()}
          className="text-sm font-semibold px-4 py-2 rounded-lg text-white disabled:opacity-60"
          style={{ background: "#C8900A" }}
        >
          {busy ? <Loader2 className="inline animate-spin" size={14} /> : "Create"}
        </button>
      </AppModalFooter>
    </AppModal>
  );
}
