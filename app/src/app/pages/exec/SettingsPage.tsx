import { useCallback, useEffect, useState } from "react";
import { AlertCircle, CheckCircle2, Loader2, ShoppingCart } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { apiGet, apiPost, ApiError } from "@/app/lib/api";

interface PosPinRow {
  id: number;
  label: string;
  is_active: boolean;
  created_at: string;
}

export function SettingsPage() {
  const [systemName, setSystemName] = useState("Pyx Tracker Management System");
  const [timezone, setTimezone] = useState("Asia/Manila");
  const [exportFormat, setExportFormat] = useState("xlsx");

  const [pins, setPins] = useState<PosPinRow[]>([]);
  const [pinsLoading, setPinsLoading] = useState(true);
  const [pinForms, setPinForms] = useState<Record<number, { label: string; pin: string; pinConfirm: string; isActive: boolean }>>({});
  const [createForm, setCreateForm] = useState({ label: "POS Terminal", pin: "", pinConfirm: "" });
  const [showCreate, setShowCreate] = useState(false);
  const [posBusy, setPosBusy] = useState<number | "create" | null>(null);
  const [posMsg, setPosMsg] = useState<{ type: "ok" | "err"; text: string } | null>(null);

  const loadPins = useCallback(async () => {
    setPinsLoading(true);
    try {
      const data = await apiGet<{ pins: PosPinRow[] }>("settings.php", "pos_pins_list");
      setPins(data.pins);
      const forms: Record<number, { label: string; pin: string; pinConfirm: string; isActive: boolean }> = {};
      data.pins.forEach(p => {
        forms[p.id] = { label: p.label, pin: "", pinConfirm: "", isActive: p.is_active };
      });
      setPinForms(forms);
      setShowCreate(data.pins.length === 0);
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Could not load POS terminals." });
    } finally {
      setPinsLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadPins();
  }, [loadPins]);

  const savePin = async (id: number) => {
    const form = pinForms[id];
    if (!form) return;
    setPosBusy(id);
    setPosMsg(null);
    try {
      const res = await apiPost<{ pin_changed?: boolean }>("settings.php", "pos_pin_update", {
        id,
        label: form.label,
        is_active: form.isActive ? 1 : 0,
        pin: form.pin,
        pin_confirm: form.pinConfirm,
      });
      setPosMsg({
        type: "ok",
        text: res.pin_changed ? "POS PIN updated." : "Terminal settings saved.",
      });
      setPinForms(prev => ({
        ...prev,
        [id]: { ...prev[id], pin: "", pinConfirm: "" },
      }));
      await loadPins();
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Save failed." });
    } finally {
      setPosBusy(null);
    }
  };

  const createPin = async () => {
    setPosBusy("create");
    setPosMsg(null);
    try {
      await apiPost("settings.php", "pos_pin_create", {
        label: createForm.label,
        pin: createForm.pin,
        pin_confirm: createForm.pinConfirm,
      });
      setPosMsg({ type: "ok", text: "POS terminal created." });
      setCreateForm({ label: "POS Terminal", pin: "", pinConfirm: "" });
      setShowCreate(false);
      await loadPins();
    } catch (e) {
      setPosMsg({ type: "err", text: e instanceof ApiError ? e.message : "Could not create terminal." });
    } finally {
      setPosBusy(null);
    }
  };

  return (
    <PageLayout title="System Settings" subtitle="Configure system-wide preferences">
      <div className="max-w-2xl space-y-6">
        {posMsg && (
          <div
            className={`rounded-lg border px-4 py-3 text-sm flex items-start gap-2 ${
              posMsg.type === "ok"
                ? "border-green-200 bg-green-50 text-green-800"
                : "border-red-200 bg-red-50 text-red-700"
            }`}
          >
            {posMsg.type === "ok" ? <CheckCircle2 size={16} className="mt-0.5" /> : <AlertCircle size={16} className="mt-0.5" />}
            <span>{posMsg.text}</span>
          </div>
        )}

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border flex items-center gap-2">
            <ShoppingCart size={18} className="text-[#C8900A]" />
            <div>
              <h3 className="text-sm font-semibold text-foreground">POS terminal</h3>
              <p className="text-xs text-muted-foreground mt-0.5">
                Cashiers sign in at the login screen with this PIN (4-12 digits). Leave PIN blank to only update label or active status.
              </p>
            </div>
          </div>

          {pinsLoading ? (
            <div className="px-6 py-8 flex justify-center text-muted-foreground">
              <Loader2 className="animate-spin" size={20} />
            </div>
          ) : (
            <div className="px-6 py-5 space-y-6">
              {pins.map(p => {
                const form = pinForms[p.id];
                if (!form) return null;
                return (
                  <div key={p.id} className="rounded-lg border border-border p-4 space-y-3">
                    <div className="flex items-center justify-between gap-2">
                      <span className="text-xs text-muted-foreground font-mono">Terminal #{p.id}</span>
                      <label className="flex items-center gap-2 text-xs">
                        <input
                          type="checkbox"
                          checked={form.isActive}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], isActive: e.target.checked },
                            }))
                          }
                        />
                        Active
                      </label>
                    </div>
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                        Label
                      </label>
                      <input
                        value={form.label}
                        onChange={e =>
                          setPinForms(prev => ({
                            ...prev,
                            [p.id]: { ...prev[p.id], label: e.target.value },
                          }))
                        }
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A]"
                        placeholder="e.g. Main POS"
                      />
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                          New PIN
                        </label>
                        <input
                          type="password"
                          inputMode="numeric"
                          autoComplete="new-password"
                          value={form.pin}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], pin: e.target.value.replace(/\D/g, "") },
                            }))
                          }
                          className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] font-mono"
                          placeholder="••••"
                          maxLength={12}
                        />
                      </div>
                      <div>
                        <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">
                          Confirm PIN
                        </label>
                        <input
                          type="password"
                          inputMode="numeric"
                          autoComplete="new-password"
                          value={form.pinConfirm}
                          onChange={e =>
                            setPinForms(prev => ({
                              ...prev,
                              [p.id]: { ...prev[p.id], pinConfirm: e.target.value.replace(/\D/g, "") },
                            }))
                          }
                          className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] font-mono"
                          placeholder="••••"
                          maxLength={12}
                        />
                      </div>
                    </div>
                    <button
                      type="button"
                      disabled={posBusy === p.id}
                      onClick={() => void savePin(p.id)}
                      className="w-full sm:w-auto px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
                      style={{ background: "#C8900A" }}
                    >
                      {posBusy === p.id ? <Loader2 className="inline animate-spin" size={14} /> : "Save POS settings"}
                    </button>
                  </div>
                );
              })}

              {pins.length > 0 && !showCreate && (
                <button
                  type="button"
                  onClick={() => setShowCreate(true)}
                  className="text-sm font-medium text-[#C8900A] hover:underline"
                >
                  + Add another terminal
                </button>
              )}

              {showCreate && (
                <div className="rounded-lg border border-dashed border-border p-4 space-y-3">
                  <p className="text-sm font-semibold">{pins.length === 0 ? "Create POS terminal" : "New terminal"}</p>
                  <div>
                    <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Label</label>
                    <input
                      value={createForm.label}
                      onChange={e => setCreateForm(f => ({ ...f, label: e.target.value }))}
                      className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A]"
                    />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">PIN</label>
                      <input
                        type="password"
                        inputMode="numeric"
                        value={createForm.pin}
                        onChange={e => setCreateForm(f => ({ ...f, pin: e.target.value.replace(/\D/g, "") }))}
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm font-mono"
                        maxLength={12}
                      />
                    </div>
                    <div>
                      <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Confirm PIN</label>
                      <input
                        type="password"
                        inputMode="numeric"
                        value={createForm.pinConfirm}
                        onChange={e => setCreateForm(f => ({ ...f, pinConfirm: e.target.value.replace(/\D/g, "") }))}
                        className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm font-mono"
                        maxLength={12}
                      />
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      disabled={posBusy === "create"}
                      onClick={() => void createPin()}
                      className="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-50"
                      style={{ background: "#C8900A" }}
                    >
                      {posBusy === "create" ? <Loader2 className="inline animate-spin" size={14} /> : "Create terminal"}
                    </button>
                    {pins.length > 0 && (
                      <button type="button" onClick={() => setShowCreate(false)} className="px-4 py-2.5 rounded-lg border text-sm">
                        Cancel
                      </button>
                    )}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">General</h3>
            <p className="text-xs text-muted-foreground mt-0.5">Coming soon — not saved yet.</p>
          </div>
          <div className="px-6 py-5 space-y-5 opacity-60 pointer-events-none">
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">System Name</label>
              <input value={systemName} onChange={e => setSystemName(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm" />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Timezone</label>
              <select value={timezone} onChange={e => setTimezone(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm">
                <option value="Asia/Manila">Asia/Manila (GMT+8)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Default Export Format</label>
              <select value={exportFormat} onChange={e => setExportFormat(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm">
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv)</option>
              </select>
            </div>
          </div>
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden opacity-60" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">Departments</h3>
          </div>
          <div className="divide-y divide-border">
            {["Honey", "Paragis", "Cacao / BRC", "CSR"].map(dept => (
              <div key={dept} className="flex items-center justify-between px-6 py-4">
                <span className="text-sm text-foreground font-medium">{dept}</span>
                <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-green-100 text-green-700">Active</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
