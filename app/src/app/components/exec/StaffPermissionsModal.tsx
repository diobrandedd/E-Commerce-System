import { useEffect, useMemo, useState } from "react";
import { ChevronDown, Search, Shield } from "lucide-react";
import { motion, AnimatePresence, useReducedMotion } from "motion/react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import { appFeedbackEase } from "@/app/components/layout/appFeedbackMotion";
import { AppErrorBanner } from "@/app/components/layout/appFeedbackMotion";
import { Button } from "@/app/components/ui/button";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

interface PermissionItem {
  key: string;
  label: string;
  description: string;
}

interface PermissionGroup {
  group: string;
  permissions: PermissionItem[];
}

interface PermissionsPayload {
  user_id: number;
  role: string;
  dept_name: string | null;
  permissions: string[];
  preset: string[];
  read_only: boolean;
}

const ROLE_LABELS: Record<string, string> = {
  executive: "Executive",
  prod_manager: "Production Manager",
  hr: "HR",
  tl: "Team Leader",
  staff: "Staff",
};

function groupSelectedCount(items: PermissionItem[], selected: Set<string>): number {
  return items.filter(p => selected.has(p.key)).length;
}

export function StaffPermissionsModal({
  userId,
  userName,
  userRole,
  onClose,
  onSaved,
}: {
  userId: number;
  userName: string;
  userRole: string;
  onClose: () => void;
  onSaved: (msg: string) => void;
}) {
  const reduce = useReducedMotion();
  const [groups, setGroups] = useState<PermissionGroup[]>([]);
  const [selected, setSelected] = useState<Set<string>>(new Set());
  const [preset, setPreset] = useState<string[]>([]);
  const [readOnly, setReadOnly] = useState(false);
  const [search, setSearch] = useState("");
  const [expanded, setExpanded] = useState<Set<string>>(new Set());
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const [catalog, perms] = await Promise.all([
          apiGet<{ groups: PermissionGroup[] }>("staff.php", "permission_catalog"),
          apiGet<PermissionsPayload>("staff.php", "permissions_get", { user_id: userId }),
        ]);
        if (cancelled) return;
        setGroups(catalog.groups ?? []);
        setSelected(new Set(perms.permissions ?? []));
        setPreset(perms.preset ?? []);
        setReadOnly(!!perms.read_only);
        setExpanded(new Set());
      } catch (e) {
        if (!cancelled) {
          setError(e instanceof ApiError ? e.message : "Could not load permissions.");
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [userId]);

  const filteredGroups = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return groups;
    return groups
      .map(g => ({
        ...g,
        permissions: g.permissions.filter(
          p =>
            p.label.toLowerCase().includes(q) ||
            p.key.toLowerCase().includes(q) ||
            p.description.toLowerCase().includes(q),
        ),
      }))
      .filter(g => g.permissions.length > 0);
  }, [groups, search]);

  const searching = search.trim().length > 0;

  const isExpanded = (groupName: string) => searching || expanded.has(groupName);

  const toggleExpanded = (groupName: string) => {
    if (searching) return;
    setExpanded(prev => {
      const next = new Set(prev);
      if (next.has(groupName)) next.delete(groupName);
      else next.add(groupName);
      return next;
    });
  };

  const expandAll = () => setExpanded(new Set(filteredGroups.map(g => g.group)));
  const collapseAll = () => setExpanded(new Set());

  const totalKeys = useMemo(
    () => groups.reduce((n, g) => n + g.permissions.length, 0),
    [groups],
  );

  const toggle = (key: string) => {
    setSelected(prev => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  const toggleGroup = (items: PermissionItem[], on: boolean) => {
    setSelected(prev => {
      const next = new Set(prev);
      for (const p of items) {
        if (on) next.add(p.key);
        else next.delete(p.key);
      }
      return next;
    });
  };

  const applyPreset = () => {
    setSelected(new Set(preset));
  };

  const clearAll = () => setSelected(new Set());

  const save = async () => {
    setSaving(true);
    setError(null);
    try {
      await apiPost("staff.php", "permissions_set", {
        user_id: userId,
        keys: Array.from(selected),
      });
      onSaved(`Permissions updated for ${userName}.`);
      onClose();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save permissions.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-2xl w-full max-h-[90dvh]" labelledBy="staff-perms-title">
      <AppModalHeader
        title={`Permissions — ${userName}`}
        titleId="staff-perms-title"
        onClose={onClose}
      />
      <AppModalBody className="py-4 space-y-4 overflow-y-auto">
        {error && <AppErrorBanner message={error} />}

        <p className="text-sm text-muted-foreground">
          Role template: <span className="font-medium text-foreground">{ROLE_LABELS[userRole] ?? userRole}</span>
          {" · "}
          {selected.size} of {totalKeys} permissions selected
        </p>

        {readOnly ? (
          <p className="text-sm rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800">
            Executives have full access automatically. Permission checkboxes are not editable for this account.
          </p>
        ) : (
          <>
            <div className="flex flex-wrap gap-2">
              <div className="relative flex-1 min-w-[200px]">
                <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  value={search}
                  onChange={e => setSearch(e.target.value)}
                  placeholder="Search permissions…"
                  className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background outline-none focus:border-[#C8900A]"
                />
              </div>
              <Button type="button" variant="outline" size="sm" onClick={applyPreset}>
                Apply role preset
              </Button>
              <Button type="button" variant="outline" size="sm" onClick={clearAll}>
                Clear all
              </Button>
            </div>

            {!searching && !loading && filteredGroups.length > 0 && (
              <div className="flex gap-2 text-xs">
                <button type="button" onClick={expandAll} className="text-[#C8900A] hover:underline mr-3">
                  Expand all
                </button>
                <button type="button" onClick={collapseAll} className="text-muted-foreground hover:underline">
                  Collapse all
                </button>
              </div>
            )}

            {loading && <p className="text-sm text-muted-foreground py-6 text-center">Loading permissions…</p>}

            {!loading && filteredGroups.map(g => {
              const allOn = g.permissions.every(p => selected.has(p.key));
              const someOn = g.permissions.some(p => selected.has(p.key));
              const open = isExpanded(g.group);
              const picked = groupSelectedCount(g.permissions, selected);

              return (
                <div key={g.group} className="rounded-xl border border-border overflow-hidden">
                  <div className="flex items-center gap-2 px-3 py-2.5 bg-secondary/40 border-b border-border">
                    <button
                      type="button"
                      onClick={() => toggleExpanded(g.group)}
                      className="flex flex-1 items-center gap-2 min-w-0 text-left rounded-md py-0.5 hover:bg-secondary/60 transition-colors"
                      aria-expanded={open}
                    >
                      <motion.span
                        animate={{ rotate: open ? 180 : 0 }}
                        transition={reduce ? { duration: 0 } : appFeedbackEase}
                        className="flex-shrink-0 text-muted-foreground"
                      >
                        <ChevronDown size={16} aria-hidden />
                      </motion.span>
                      <span className="text-xs font-semibold uppercase tracking-wide text-foreground truncate">
                        {g.group}
                      </span>
                      <span className="text-[11px] text-muted-foreground flex-shrink-0">
                        {picked}/{g.permissions.length}
                      </span>
                    </button>
                    <button
                      type="button"
                      onClick={e => {
                        e.stopPropagation();
                        toggleGroup(g.permissions, !allOn);
                      }}
                      className="text-xs text-[#C8900A] hover:underline flex-shrink-0 px-1"
                    >
                      {allOn ? "Clear" : someOn ? "Select all" : "Select all"}
                    </button>
                  </div>
                  <AnimatePresence initial={false}>
                    {open && (
                      <motion.ul
                        initial={reduce ? false : { height: 0, opacity: 0 }}
                        animate={{ height: "auto", opacity: 1 }}
                        exit={reduce ? undefined : { height: 0, opacity: 0 }}
                        transition={appFeedbackEase}
                        className="divide-y divide-border overflow-hidden"
                      >
                        {g.permissions.map(p => (
                          <li key={p.key}>
                            <label
                              className={cn(
                                "flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-secondary/30 transition-colors",
                              )}
                            >
                              <input
                                type="checkbox"
                                checked={selected.has(p.key)}
                                onChange={() => toggle(p.key)}
                                className="mt-1 rounded border-border"
                              />
                              <span>
                                <span className="text-sm font-medium text-foreground block">{p.label}</span>
                                <span className="text-xs text-muted-foreground">{p.description}</span>
                              </span>
                            </label>
                          </li>
                        ))}
                      </motion.ul>
                    )}
                  </AnimatePresence>
                </div>
              );
            })}
          </>
        )}
      </AppModalBody>
      {!readOnly && (
        <AppModalFooter>
          <Button type="button" variant="outline" onClick={onClose}>Cancel</Button>
          <Button type="button" disabled={saving || loading} onClick={save}>
            {saving ? "Saving…" : "Save permissions"}
          </Button>
        </AppModalFooter>
      )}
    </AppModal>
  );
}
