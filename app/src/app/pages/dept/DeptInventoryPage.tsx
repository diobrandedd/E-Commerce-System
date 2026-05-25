import { useCallback, useEffect, useState } from "react";
import { useParams } from "react-router";
import { Loader2, Plus } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { ExecutiveRowEditModal } from "@/app/components/honey/ExecutiveRowEditModal";
import { QtyCounter } from "@/app/components/honey/QtyCounter";
import { useAuth } from "@/app/context/AuthContext";
import { canExecutiveDirectEdit, canMutateHoneyInventory } from "@/app/lib/honeyPermissions";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet, apiPost } from "@/app/lib/api";

const DEPT_MAP: Record<string, string> = {
  paragis: "paragis",
  "cacao-brc": "cacao_brc",
  cacao_brc: "cacao_brc",
};

interface DeptRow {
  id: number;
  product_label: string;
  current_qty: string;
  initial_qty: string;
  dept_name: string;
}

export function DeptInventoryPage() {
  const { dept } = useParams();
  const deptKey = DEPT_MAP[dept ?? ""] ?? dept ?? "";
  const title = deptKey.replace("_", " / ");
  const { user } = useAuth();
  const canMutate = canMutateHoneyInventory(user?.role);
  const canExecEdit = canExecutiveDirectEdit(user?.role);
  const [rows, setRows] = useState<DeptRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [editRow, setEditRow] = useState<DeptRow | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<{ rows: DeptRow[] }>("honey.php", "dept_list", { dept: deptKey });
      setRows(data.rows);
    } finally {
      setLoading(false);
    }
  }, [deptKey]);

  useEffect(() => { refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  const addRow = async () => {
    const label = prompt("Product name:");
    const qty = prompt("Quantity:");
    if (!label || !qty) return;
    await apiPost("honey.php", "dept_add_row", { dept: deptKey, product_label: label, qty: parseFloat(qty) });
    refresh();
  };

  return (
    <PageLayout title={`${title} inventory`} subtitle="Other RTS products (simplified)">
      {canMutate && (
        <button onClick={addRow} className="mb-4 inline-flex items-center gap-1 text-xs font-semibold text-white px-3 py-2 rounded-lg" style={{ background: "#C8900A" }}>
          <Plus size={14} /> Add row
        </button>
      )}
      <div className="bg-card rounded-xl border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-secondary/40">
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Product</th>
              <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground">Qty</th>
              {canExecEdit && <th className="text-left px-4 py-3 text-xs uppercase text-muted-foreground" />}
            </tr>
          </thead>
          <tbody>
            {loading && <tr><td colSpan={canExecEdit ? 3 : 2} className="py-8 text-center"><Loader2 className="inline animate-spin" size={14} /></td></tr>}
            {rows.map(r => (
              <tr key={r.id} className="border-b">
                <td className="px-4 py-3 font-medium">{r.product_label}</td>
                <td className="px-4 py-3">
                  <QtyCounter current={r.current_qty} initial={r.initial_qty} />
                </td>
                {canExecEdit && (
                  <td className="px-4 py-3">
                    <button type="button" onClick={() => setEditRow(r)} className="text-xs font-semibold text-muted-foreground hover:text-foreground">
                      Edit
                    </button>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {editRow && (
        <ExecutiveRowEditModal
          stage="dept"
          row={editRow}
          onClose={() => setEditRow(null)}
          onDone={() => { setEditRow(null); refresh(); }}
        />
      )}
    </PageLayout>
  );
}
