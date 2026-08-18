import { useEffect, useMemo, useState } from "react";
import { ArrowDown, ArrowUp, ArrowUpDown, BookOpen, Plus, Search, Edit2, ToggleLeft, ToggleRight, KeyRound, AlertCircle, Loader2, Shield } from "lucide-react";
import { StaffPermissionsModal } from "@/app/components/exec/StaffPermissionsModal";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { PageLayout } from "@/app/components/PageLayout";
import { StaffManagementGuideModal } from "@/app/components/exec/StaffManagementGuideModal";
import { appHeaderActionClass } from "@/app/components/layout/appFeedbackMotion";
import { runStaffManagementTour } from "@/app/components/tour/guides/staffManagementTour";
import { startPageTourAfterModalClose } from "@/app/components/tour/runPageTour";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";
import { cn } from "@/app/components/ui/utils";

type StaffSortKey = "name" | "role" | "department" | "employee_id" | "status";
type SortDir = "asc" | "desc";

const SORT_COLUMNS: { key: StaffSortKey; label: string }[] = [
  { key: "name", label: "Name / username" },
  { key: "role", label: "Role" },
  { key: "department", label: "Department" },
  { key: "employee_id", label: "Employee ID" },
  { key: "status", label: "Status" },
];

interface StaffUser {
  id: number;
  username: string;
  name: string;
  email: string;
  employee_id: string;
  role: string;
  role_id: number;
  dept_id: number | null;
  dept_name: string | null;
  is_active: boolean;
  created_at: string;
  permission_count?: number;
}

interface StaffOption { id: number; name: string }

interface StaffListPayload {
  users: StaffUser[];
  roles: StaffOption[];
  departments: StaffOption[];
}

const ROLE_LABELS: Record<string, string> = {
  executive: "Executive",
  prod_manager: "Production Manager",
  hr: "HR",
  tl: "Team Leader",
  staff: "Staff",
};

const ROLE_COLORS: Record<string, string> = {
  executive: "bg-[#C8900A]/20 text-[#C8900A]",
  prod_manager: "bg-blue-100 text-blue-700",
  hr: "bg-teal-100 text-teal-700",
  tl: "bg-green-100 text-green-700",
  staff: "bg-purple-100 text-purple-700",
};

const DEPT_LABELS: Record<string, string> = {
  honey: "Honey",
  paragis: "Paragis",
  cacao_brc: "Cacao / BRC",
  csr: "CSR",
};

function staffSortValue(u: StaffUser, key: StaffSortKey): string {
  switch (key) {
    case "name":
      return `${u.name} ${u.username}`;
    case "role":
      return ROLE_LABELS[u.role] ?? u.role;
    case "department":
      return u.dept_name ? (DEPT_LABELS[u.dept_name] ?? u.dept_name) : "";
    case "employee_id":
      return u.employee_id;
    case "status":
      return u.is_active ? "active" : "inactive";
  }
}

interface FormState {
  name: string;
  username: string;
  employee_id: string;
  role: string;
  dept_id: string;
  is_active: string;
}

const EMPTY_FORM: FormState = {
  name: "",
  username: "",
  employee_id: "",
  role: "staff",
  dept_id: "",
  is_active: "1",
};

const needsDept = (role: string) => role === "tl" || role === "staff";

export function StaffManagementPage() {
  const [data, setData] = useState<StaffListPayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [editing, setEditing] = useState<StaffUser | null>(null);
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [guideOpen, setGuideOpen] = useState(false);
  const [sortKey, setSortKey] = useState<StaffSortKey>("name");
  const [sortDir, setSortDir] = useState<SortDir>("asc");
  const [permUser, setPermUser] = useState<StaffUser | null>(null);
  const [pendingPermUserId, setPendingPermUserId] = useState<number | null>(null);

  const refresh = async () => {
    setLoading(true);
    setError(null);
    try {
      const payload = await apiGet<StaffListPayload>("staff.php", "list");
      setData(payload);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load users.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { refresh(); }, []);

  const users = data?.users ?? [];
  const roles = data?.roles ?? [];
  const departments = data?.departments ?? [];

  const filtered = useMemo(() => {
    const q = search.toLowerCase().trim();
    const list = q
      ? users.filter(s =>
          s.name.toLowerCase().includes(q) ||
          s.username.toLowerCase().includes(q) ||
          (s.dept_name ?? "").toLowerCase().includes(q) ||
          s.employee_id.toLowerCase().includes(q),
        )
      : [...users];

    const dir = sortDir === "asc" ? 1 : -1;
    list.sort((a, b) => {
      if (a.is_active !== b.is_active) {
        return a.is_active ? -1 : 1;
      }
      const av = staffSortValue(a, sortKey).toLocaleLowerCase();
      const bv = staffSortValue(b, sortKey).toLocaleLowerCase();
      const cmp = av.localeCompare(bv, undefined, { numeric: true, sensitivity: "base" });
      if (cmp !== 0) {
        return cmp * dir;
      }
      return a.name.localeCompare(b.name, undefined, { sensitivity: "base" });
    });
    return list;
  }, [users, search, sortKey, sortDir]);

  const toggleSort = (key: StaffSortKey) => {
    if (sortKey === key) {
      setSortDir(prev => (prev === "asc" ? "desc" : "asc"));
      return;
    }
    setSortKey(key);
    setSortDir("asc");
  };

  const totals = useMemo(() => ({
    total: users.length,
    active: users.filter(u => u.is_active).length,
    tls: users.filter(u => u.role === "tl").length,
    departments: new Set(users.map(u => u.dept_id).filter(Boolean)).size,
  }), [users]);

  const toggleActive = async (u: StaffUser) => {
    setBusyId(u.id);
    setError(null);
    try {
      await apiPost("staff.php", "update", {
        user_id: u.id,
        name: u.name,
        username: u.username,
        employee_id: u.employee_id,
        role: u.role,
        dept_id: u.dept_id ?? 0,
        is_active: u.is_active ? 0 : 1,
      });
      await refresh();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not update user.");
    } finally {
      setBusyId(null);
    }
  };

  const resetPassword = async (u: StaffUser) => {
    if (!confirm(`Reset ${u.name}'s password to their employee ID (${u.employee_id})?`)) return;
    setBusyId(u.id);
    setError(null);
    try {
      await apiPost("staff.php", "reset_password", { user_id: u.id });
      setNotice(`Password reset to default for ${u.name}.`);
      setTimeout(() => setNotice(null), 3500);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not reset password.");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <PageLayout
      title="Staff Management"
      subtitle="Manage system users and role assignments"
      actions={
        <button type="button" onClick={() => setGuideOpen(true)} className={appHeaderActionClass}>
          <BookOpen size={13} /> Guide
        </button>
      }
    >
      <div className="space-y-5">
        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex items-start gap-2">
            <AlertCircle size={16} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
          </div>
        )}
        {notice && (
          <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{notice}</div>
        )}

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4" data-tour="staff-stats">
          {[
            { label: "Total Staff", value: totals.total },
            { label: "Active", value: totals.active },
            { label: "Team Leaders", value: totals.tls },
            { label: "Departments", value: totals.departments },
          ].map(c => (
            <div key={c.label} className="bg-card rounded-xl border border-border p-4 text-center" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
              <p className="text-2xl font-bold text-foreground">{c.value}</p>
              <p className="text-xs text-muted-foreground mt-1">{c.label}</p>
            </div>
          ))}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden flex flex-col min-h-0 max-h-[calc(100dvh-15rem)]" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border gap-4 shrink-0" data-tour="staff-toolbar">
            <div className="relative flex-1 max-w-xs">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input
                value={search}
                onChange={e => setSearch(e.target.value)}
                placeholder="Search staff..."
                className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background outline-none focus:border-[#C8900A] transition-colors"
              />
            </div>
            <button
              type="button"
              onClick={() => setCreating(true)}
              className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white transition-all"
              style={{ background: "#C8900A" }}
            >
              <Plus size={15} /> Add Staff
            </button>
          </div>
          <div className="flex-1 min-h-0 overflow-auto overscroll-contain" data-tour="staff-table">
            <table className="w-full text-sm">
              <thead className="sticky top-0 z-10 bg-secondary/95 backdrop-blur-sm">
                <tr className="border-b border-border">
                  {SORT_COLUMNS.map(col => (
                    <th
                      key={col.key}
                      className={cn(
                        "text-left text-xs font-semibold uppercase tracking-wide px-5 py-3",
                        col.key === "name" && "pl-6",
                      )}
                    >
                      <button
                        type="button"
                        onClick={() => toggleSort(col.key)}
                        className="inline-flex items-center gap-1.5 text-muted-foreground hover:text-foreground transition-colors rounded-md px-1 -mx-1 py-0.5 hover:bg-secondary/60"
                      >
                        {col.label}
                        {sortKey === col.key ? (
                          sortDir === "asc" ? (
                            <ArrowUp size={13} className="text-[#C8900A]" aria-hidden />
                          ) : (
                            <ArrowDown size={13} className="text-[#C8900A]" aria-hidden />
                          )
                        ) : (
                          <ArrowUpDown size={13} className="opacity-40" aria-hidden />
                        )}
                      </button>
                    </th>
                  ))}
                  <th
                    className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3"
                    data-tour="staff-actions-col"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {loading && (
                  <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-muted-foreground"><Loader2 className="inline animate-spin mr-2" size={14} /> Loading staff…</td></tr>
                )}
                {!loading && filtered.length === 0 && (
                  <tr><td colSpan={6} className="px-6 py-8 text-center text-sm text-muted-foreground">No staff found.</td></tr>
                )}
                {filtered.map(s => (
                  <tr
                    key={s.id}
                    className={cn(
                      "border-b border-border last:border-0 transition-colors",
                      !s.is_active
                        ? "bg-red-50/80 hover:bg-red-100/70"
                        : "hover:bg-secondary/30",
                    )}
                  >
                    <td className="px-6 py-3.5">
                      <p className="font-medium text-foreground">{s.name}</p>
                      <p className="text-xs text-muted-foreground">{s.username}</p>
                      {s.role !== "executive" && (
                        <p className="text-[11px] text-muted-foreground mt-0.5">
                          {s.permission_count ?? 0} permission{(s.permission_count ?? 0) === 1 ? "" : "s"}
                        </p>
                      )}
                    </td>
                    <td className="px-5 py-3.5">
                      <span className={`inline-block text-xs font-semibold px-2.5 py-1 rounded-full ${ROLE_COLORS[s.role] ?? "bg-gray-100 text-gray-700"}`}>
                        {ROLE_LABELS[s.role] ?? s.role}
                      </span>
                    </td>
                    <td className="px-5 py-3.5 text-muted-foreground">{s.dept_name ? (DEPT_LABELS[s.dept_name] ?? s.dept_name) : "—"}</td>
                    <td className="px-5 py-3.5 text-muted-foreground font-mono">{s.employee_id}</td>
                    <td className="px-5 py-3.5">
                      <span className={`inline-block text-xs font-semibold px-2.5 py-1 rounded-full ${s.is_active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"}`}>
                        {s.is_active ? "Active" : "Inactive"}
                      </span>
                    </td>
                    <td className="px-5 py-3.5">
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => setPermUser(s)}
                          className="p-1.5 rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors"
                          title="Permissions"
                        >
                          <Shield size={14} />
                        </button>
                        <button
                          onClick={() => setEditing(s)}
                          className="p-1.5 rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors"
                          title="Edit"
                        >
                          <Edit2 size={14} />
                        </button>
                        <button
                          onClick={() => resetPassword(s)}
                          disabled={busyId === s.id}
                          className="p-1.5 rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors disabled:opacity-50"
                          title="Reset to default password (employee ID)"
                        >
                          <KeyRound size={14} />
                        </button>
                        <button
                          onClick={() => toggleActive(s)}
                          disabled={busyId === s.id}
                          className={`p-1.5 rounded-lg transition-colors disabled:opacity-50 ${s.is_active ? "text-green-600 hover:bg-green-50" : "text-gray-400 hover:bg-gray-50"}`}
                          title={s.is_active ? "Deactivate" : "Activate"}
                        >
                          {s.is_active ? <ToggleRight size={18} /> : <ToggleLeft size={18} />}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {(creating || editing) && (
        <StaffModal
          mode={editing ? "edit" : "create"}
          user={editing}
          roles={roles}
          departments={departments}
          onClose={() => { setCreating(false); setEditing(null); }}
          onSaved={async (msg) => {
            setNotice(msg);
            setCreating(false);
            setEditing(null);
            await refresh();
            setTimeout(() => setNotice(null), 3500);
          }}
          onCreated={(id) => setPendingPermUserId(id)}
        />
      )}

      {permUser && (
        <StaffPermissionsModal
          userId={permUser.id}
          userName={permUser.name}
          userRole={permUser.role}
          onClose={() => setPermUser(null)}
          onSaved={async (msg) => {
            setNotice(msg);
            setPermUser(null);
            await refresh();
            setTimeout(() => setNotice(null), 3500);
          }}
        />
      )}

      {pendingPermUserId !== null && !creating && !editing && (
        <StaffPermissionsModal
          userId={pendingPermUserId}
          userName={users.find(u => u.id === pendingPermUserId)?.name ?? "New user"}
          userRole={users.find(u => u.id === pendingPermUserId)?.role ?? "staff"}
          onClose={() => setPendingPermUserId(null)}
          onSaved={async (msg) => {
            setNotice(msg);
            setPendingPermUserId(null);
            await refresh();
            setTimeout(() => setNotice(null), 3500);
          }}
        />
      )}

      <StaffManagementGuideModal
        open={guideOpen}
        onClose={() => setGuideOpen(false)}
        onStartTour={() => {
          setGuideOpen(false);
          startPageTourAfterModalClose(() => runStaffManagementTour());
        }}
      />
    </PageLayout>
  );
}

function StaffModal({
  mode,
  user,
  roles,
  departments,
  onClose,
  onSaved,
  onCreated,
}: {
  mode: "create" | "edit";
  user: StaffUser | null;
  roles: StaffOption[];
  departments: StaffOption[];
  onClose: () => void;
  onSaved: (msg: string) => void;
  onCreated?: (userId: number) => void;
}) {
  const [form, setForm] = useState<FormState>(
    user
      ? {
          name: user.name,
          username: user.username,
          employee_id: user.employee_id,
          role: user.role,
          dept_id: user.dept_id ? String(user.dept_id) : "",
          is_active: user.is_active ? "1" : "0",
        }
      : EMPTY_FORM
  );
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const set = <K extends keyof FormState>(k: K, v: string) => setForm(prev => ({ ...prev, [k]: v }));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    try {
      const payload = {
        user_id: user?.id,
        name: form.name.trim(),
        username: form.username.trim(),
        employee_id: form.employee_id.trim(),
        role: form.role,
        dept_id: form.dept_id ? Number(form.dept_id) : 0,
        is_active: Number(form.is_active),
      };
      if (mode === "create") {
        const res = await apiPost<{ id: number }>("staff.php", "create", payload);
        onSaved("User created with no permissions. Grant access in Permissions.");
        if (res?.id) onCreated?.(res.id);
      } else {
        await apiPost("staff.php", "update", payload);
        onSaved("User updated.");
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not save user.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <AppModal open onClose={onClose} panelClassName="max-w-md w-full" labelledBy="staff-form-modal-title">
      <AppModalHeader
        title={mode === "create" ? "Add staff" : `Edit ${user?.name ?? ""}`}
        titleId="staff-form-modal-title"
        onClose={onClose}
      />
      <AppModalBody className="py-5">
        <form onSubmit={submit} className="space-y-4">
          {error && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 flex items-start gap-2">
              <AlertCircle size={13} className="mt-0.5 flex-shrink-0" /> <span>{error}</span>
            </div>
          )}
          <Field label="Full name">
            <input required maxLength={120} value={form.name} onChange={e => set("name", e.target.value)} className={inputCls} placeholder="Noli Fin" />
          </Field>
          <Field label="Username (login)">
            <input required maxLength={64} value={form.username} onChange={e => set("username", e.target.value)} className={inputCls} placeholder="Noli_F" />
          </Field>
          <Field label="Employee ID (default password)">
            <input required maxLength={12} inputMode="numeric" value={form.employee_id} onChange={e => set("employee_id", e.target.value.replace(/\D/g, ""))} className={inputCls} placeholder="025027" />
          </Field>
          <Field label="Role">
            <select value={form.role} onChange={e => set("role", e.target.value)} className={inputCls}>
              {roles.map(r => (
                <option key={r.id} value={r.name}>{ROLE_LABELS[r.name] ?? r.name}</option>
              ))}
            </select>
          </Field>
          <Field label="Department">
            <select value={form.dept_id} onChange={e => set("dept_id", e.target.value)} disabled={!needsDept(form.role)} className={inputCls}>
              <option value="">— None —</option>
              {departments.map(d => (
                <option key={d.id} value={d.id}>{DEPT_LABELS[d.name] ?? d.name}</option>
              ))}
            </select>
            {!needsDept(form.role) && <p className="text-[11px] text-muted-foreground mt-1">Only required for Team Leader / Staff.</p>}
            {mode === "create" && (
              <p className="text-[11px] text-amber-700 mt-2">
                New users start with zero permissions. After create, the permissions editor opens so you can grant access.
              </p>
            )}
          </Field>
          <Field label="Status">
            <select value={form.is_active} onChange={e => set("is_active", e.target.value)} className={inputCls}>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </Field>
          <div className="pt-2 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="px-3 py-2 text-sm rounded-lg border border-border text-muted-foreground hover:text-foreground">Cancel</button>
            <button disabled={saving} type="submit" className="px-4 py-2 text-sm font-semibold rounded-lg text-white disabled:opacity-70" style={{ background: "#C8900A" }}>
              {saving ? "Saving…" : mode === "create" ? "Create user" : "Save changes"}
            </button>
          </div>
        </form>
      </AppModalBody>
    </AppModal>
  );
}

const inputCls = "w-full rounded-lg border-2 border-border bg-card text-foreground text-sm px-3 py-2.5 outline-none focus:border-[#C8900A] focus:ring-4 focus:ring-[#C8900A]/10 transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed";

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1 block">{label}</label>
      {children}
    </div>
  );
}
