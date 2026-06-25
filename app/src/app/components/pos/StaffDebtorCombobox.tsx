import { useEffect, useMemo, useRef, useState } from "react";
import { Loader2 } from "lucide-react";
import { apiGet } from "@/app/lib/api";
import { loadPosCatalogCached, readPosCache } from "@/app/lib/posCache";

export interface StaffPickerRow {
  id: number;
  name: string;
  dept_name: string | null;
}

export interface StaffDebtorValue {
  debtor_user_id: number | null;
  debtor_name: string;
}

interface StaffDebtorComboboxProps {
  value: StaffDebtorValue;
  onChange: (value: StaffDebtorValue) => void;
  disabled?: boolean;
}

export function StaffDebtorCombobox({ value, onChange, disabled }: StaffDebtorComboboxProps) {
  const [staff, setStaff] = useState<StaffPickerRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState(value.debtor_name);
  const [open, setOpen] = useState(false);
  const wrapRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const cached = readPosCache<{ staff: StaffPickerRow[] }>("staff_picker");
    if (cached?.staff?.length) {
      setStaff(cached.staff);
      setLoading(false);
    } else {
      setLoading(true);
    }

    void loadPosCatalogCached(
      "staff_picker",
      () => apiGet<{ staff: StaffPickerRow[] }>("pos.php", "staff_picker"),
      data => setStaff(data.staff ?? []),
    )
      .catch(() => {
        if (!cached?.staff?.length) setStaff([]);
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    setQuery(value.debtor_name);
  }, [value.debtor_name]);

  useEffect(() => {
    const onDoc = (e: MouseEvent) => {
      if (wrapRef.current && !wrapRef.current.contains(e.target as Node)) {
        setOpen(false);
        setQuery(value.debtor_name);
      }
    };
    document.addEventListener("mousedown", onDoc);
    return () => document.removeEventListener("mousedown", onDoc);
  }, [value.debtor_name]);

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return staff;
    return staff.filter(s => {
      const hay = `${s.name} ${s.dept_name ?? ""}`.toLowerCase();
      return hay.includes(q);
    });
  }, [staff, query]);

  const pick = (row: StaffPickerRow) => {
    onChange({ debtor_user_id: row.id, debtor_name: row.name });
    setQuery(row.name);
    setOpen(false);
  };

  const useCustomName = () => {
    const name = query.trim();
    if (!name) return;
    onChange({ debtor_user_id: null, debtor_name: name });
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
          if (value.debtor_user_id !== null && e.target.value !== value.debtor_name) {
            onChange({ debtor_user_id: null, debtor_name: e.target.value });
          } else if (value.debtor_user_id === null) {
            onChange({ debtor_user_id: null, debtor_name: e.target.value });
          }
        }}
        onFocus={() => setOpen(true)}
        onKeyDown={e => {
          if (e.key === "Enter") {
            e.preventDefault();
            useCustomName();
          }
        }}
        placeholder={loading ? "Loading staff…" : "Search staff or type custom name"}
        className="w-full rounded-lg border border-border bg-card px-3 py-2 text-sm outline-none focus:border-red-400"
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
            <li className="px-3 py-2 text-xs text-muted-foreground">
              {query.trim() ? (
                <button
                  type="button"
                  className="w-full text-left text-sm text-red-700 font-medium hover:underline"
                  onMouseDown={e => e.preventDefault()}
                  onClick={useCustomName}
                >
                  Use custom name: {query.trim()}
                </button>
              ) : (
                "No matching staff."
              )}
            </li>
          ) : (
            filtered.map(s => (
              <li key={s.id} role="option">
                <button
                  type="button"
                  className={`w-full text-left px-3 py-2 text-sm hover:bg-secondary/80 transition-colors ${
                    value.debtor_user_id === s.id ? "bg-red-50 font-semibold text-red-700" : ""
                  }`}
                  onMouseDown={e => e.preventDefault()}
                  onClick={() => pick(s)}
                >
                  <span>{s.name}</span>
                  {s.dept_name && <span className="text-muted-foreground text-xs ml-1">· {s.dept_name}</span>}
                </button>
              </li>
            ))
          )}
        </ul>
      )}
    </div>
  );
}
