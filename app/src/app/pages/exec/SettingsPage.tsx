import { useState } from "react";
import { PageLayout } from "@/app/components/PageLayout";

export function SettingsPage() {
  const [systemName, setSystemName] = useState("Pyx Tracker Management System");
  const [timezone, setTimezone] = useState("Asia/Manila");
  const [exportFormat, setExportFormat] = useState("xlsx");

  return (
    <PageLayout title="System Settings" subtitle="Configure system-wide preferences">
      <div className="max-w-2xl space-y-6">
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="px-6 py-4 border-b border-border">
            <h3 className="text-sm font-semibold text-foreground">General</h3>
          </div>
          <div className="px-6 py-5 space-y-5">
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">System Name</label>
              <input value={systemName} onChange={e => setSystemName(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] transition-colors" />
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Timezone</label>
              <select value={timezone} onChange={e => setTimezone(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] transition-colors">
                <option value="Asia/Manila">Asia/Manila (GMT+8)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>
            <div>
              <label className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1.5 block">Default Export Format</label>
              <select value={exportFormat} onChange={e => setExportFormat(e.target.value)} className="w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm outline-none focus:border-[#C8900A] transition-colors">
                <option value="xlsx">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv)</option>
              </select>
            </div>
          </div>
        </div>

        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
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

        <button className="px-6 py-3 rounded-lg text-sm font-semibold text-white" style={{ background: "#C8900A" }}>
          Save Settings
        </button>
      </div>
    </PageLayout>
  );
}
