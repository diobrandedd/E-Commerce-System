import { useState } from "react";
import { Plus, Search, Edit2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";

const SUPPLIERS = [
  { id: 1, name: "BeeKo Farms", code: "BK-001", contact: "Ricardo Santos", phone: "+63 917 888 1234", address: "Batangas City", stocks: ["Raw Honey — Light", "Raw Honey — Amber", "Beeswax"], active: true },
  { id: 2, name: "Laguna Apiary", code: "LA-003", contact: "Elena Reyes", phone: "+63 918 222 5678", address: "Bay, Laguna", stocks: ["Raw Honey — Dark"], active: true },
  { id: 3, name: "Northern Farms Co.", code: "NF-007", contact: "Benito Cruz", phone: "+63 927 333 9012", address: "Tuguegarao City", stocks: ["Paragis Grass", "Paragis Powder"], active: true },
  { id: 4, name: "Davao Cacao Corp", code: "DC-011", contact: "Maricel Torres", phone: "+63 945 444 3456", address: "Davao City", stocks: ["Cacao Nibs", "Cacao Powder"], active: true },
  { id: 5, name: "SunGrain PH", code: "SG-005", contact: "Armando Lim", phone: "+63 932 555 7890", address: "Nueva Ecija", stocks: ["BRC Barley"], active: false },
];

export function SuppliersPage() {
  const [search, setSearch] = useState("");
  const filtered = SUPPLIERS.filter(s =>
    s.name.toLowerCase().includes(search.toLowerCase()) ||
    s.code.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <PageLayout title="Supplier Master" subtitle="Manage supplier records and linked stocks">
      <div className="space-y-5">
        <div className="bg-card rounded-xl border border-border overflow-hidden" style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}>
          <div className="flex items-center justify-between px-6 py-4 border-b border-border gap-4">
            <div className="relative flex-1 max-w-xs">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search suppliers..." className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-border bg-background outline-none focus:border-[#C8900A] transition-colors" />
            </div>
            <button className="flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg text-white" style={{ background: "#C8900A" }}>
              <Plus size={15} /> Add Supplier
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border bg-secondary/40">
                  {["Supplier", "Code", "Contact", "Address", "Linked Stocks", "Status", ""].map(h => (
                    <th key={h} className="text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide px-5 py-3 first:pl-6">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map((s, i) => (
                  <tr key={s.id} className={`border-b border-border last:border-0 hover:bg-secondary/30 transition-colors ${i % 2 ? "bg-secondary/10" : ""}`}>
                    <td className="px-6 py-3.5 font-semibold text-foreground">{s.name}</td>
                    <td className="px-5 py-3.5 font-mono text-xs text-muted-foreground">{s.code}</td>
                    <td className="px-5 py-3.5">
                      <p className="text-sm text-foreground">{s.contact}</p>
                      <p className="text-xs text-muted-foreground">{s.phone}</p>
                    </td>
                    <td className="px-5 py-3.5 text-muted-foreground">{s.address}</td>
                    <td className="px-5 py-3.5">
                      <div className="flex flex-wrap gap-1">
                        {s.stocks.map(st => (
                          <span key={st} className="text-[10px] px-1.5 py-0.5 rounded bg-secondary text-muted-foreground">{st}</span>
                        ))}
                      </div>
                    </td>
                    <td className="px-5 py-3.5">
                      <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${s.active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"}`}>
                        {s.active ? "Active" : "Inactive"}
                      </span>
                    </td>
                    <td className="px-5 py-3.5">
                      <button className="p-1.5 rounded-lg hover:bg-secondary text-muted-foreground hover:text-foreground transition-colors">
                        <Edit2 size={14} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
