import { useEffect, useMemo, useRef, useState } from "react";
import { Loader2 } from "lucide-react";
import { listSuppliers, type Supplier } from "@/app/lib/suppliers";

interface SupplierComboboxProps {
  value: Supplier | null;
  onChange: (supplier: Supplier | null) => void;
  category?: "honey";
  disabled?: boolean;
}

export function SupplierCombobox({ value, onChange, category = "honey", disabled }: SupplierComboboxProps) {
  const [suppliers, setSuppliers] = useState<Supplier[]>([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState("");
  const [open, setOpen] = useState(false);
  const wrapRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setLoading(true);
    listSuppliers({ category, active: true })
      .then(d => setSuppliers(d.suppliers))
      .catch(() => setSuppliers([]))
      .finally(() => setLoading(false));
  }, [category]);

  useEffect(() => {
    if (value) setQuery(value.display_name);
  }, [value]);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (wrapRef.current && !wrapRef.current.contains(e.target as Node)) {
        setOpen(false);
        if (value) setQuery(value.display_name);
        else setQuery("");
      }
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, [value]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return suppliers;
    return suppliers.filter(s => {
      const hay = `${s.first_name} ${s.last_name} ${s.display_name}`.toLowerCase();
      return hay.includes(q);
    });
  }, [suppliers, query]);

  if (!loading && suppliers.length === 0) {
    return (
      <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        No active Honey suppliers. Ask an executive to add suppliers in Supplier Master.
      </p>
    );
  }

  const pick = (s: Supplier) => {
    onChange(s);
    setQuery(s.display_name);
    setOpen(false);
  };

  return (
    <div ref={wrapRef} className="relative">
      <input
        type="text"
        disabled={disabled || loading}
        value={query}
        onChange={e => {
          setQuery(e.target.value);
          setOpen(true);
          if (value && e.target.value !== value.display_name) {
            onChange(null);
          }
        }}
        onFocus={() => setOpen(true)}
        placeholder={loading ? "Loading suppliers…" : "Type to search supplier…"}
        className="w-full border border-border rounded-lg px-3 py-2 text-sm bg-background outline-none focus:border-[#C8900A]"
        autoComplete="off"
      />
      {loading && (
        <Loader2 className="absolute right-3 top-2.5 h-4 w-4 animate-spin text-muted-foreground" />
      )}
      {open && !loading && (
        <ul
          className="absolute z-[200] mt-1 w-full max-h-48 overflow-y-auto rounded-lg border border-border bg-card shadow-lg py-1"
          role="listbox"
        >
          {filtered.length === 0 ? (
            <li className="px-3 py-2 text-xs text-muted-foreground">No matching supplier.</li>
          ) : (
            filtered.map(s => (
              <li key={s.id} role="option">
                <button
                  type="button"
                  className={`w-full text-left px-3 py-2 text-sm hover:bg-secondary/80 ${
                    value?.id === s.id ? "bg-[#C8900A]/15 font-semibold text-[#8a5c00]" : ""
                  }`}
                  onMouseDown={e => e.preventDefault()}
                  onClick={() => pick(s)}
                >
                  {s.display_name}
                </button>
              </li>
            ))
          )}
        </ul>
      )}
      {value && !open && (
        <p className="text-[10px] text-muted-foreground mt-1">Selected: {value.display_name}</p>
      )}
    </div>
  );
}
