import { useCallback, useEffect, useState } from "react";
import { History, Loader2 } from "lucide-react";
import { ExecutiveRowEditModal, type PackagingEditRow } from "@/app/components/honey/ExecutiveRowEditModal";
import { HoneyFinishedEditLogsModal } from "@/app/components/honey/HoneyFinishedEditLogsModal";
import { HoneyVariantPieGrid, type VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import { Button } from "@/app/components/ui/button";
import { useAuth } from "@/app/context/AuthContext";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet } from "@/app/lib/api";
import { canExecutiveDirectEdit } from "@/app/lib/honeyPermissions";

interface PoolVariant {
  variant: string;
  label: string;
  packaging_row_id: number;
  total_bottles: number;
  by_size: Record<string, number>;
  current_250ml: number;
  current_350ml: number;
  current_liter: number;
  current_gallon: number;
  initial_250ml: number;
  initial_350ml: number;
  initial_liter: number;
  initial_gallon: number;
}

interface PackagingPoolsSnapshot {
  total_bottles: number;
  variants: PoolVariant[];
  note?: string;
}

const SIZE_LABELS: Record<string, string> = {
  "250ml": "250ML",
  "350ml": "350ML",
  liter: "1L",
  gallon: "Gallon",
};

function poolToChart(pool: PoolVariant): VariantChart {
  const total = pool.total_bottles || 0;
  const sizes = (["250ml", "350ml", "liter", "gallon"] as const).map(size => {
    const qty = Number(pool.by_size?.[size] ?? pool[`current_${size}` as keyof PoolVariant] ?? 0) || 0;
    return {
      size,
      label: SIZE_LABELS[size] ?? size,
      qty,
      pct: total > 0 ? Math.round((qty / total) * 1000) / 10 : 0,
    };
  });
  return { variant: pool.variant, total_bottles: total, sizes };
}

function poolToEditRow(pool: PoolVariant): PackagingEditRow {
  return {
    id: pool.packaging_row_id,
    supplier_code: "POOL",
    batch_no: "POOL",
    variant: pool.variant,
    current_liter: String(pool.current_liter),
    initial_liter: String(pool.initial_liter),
    current_250ml: String(pool.current_250ml),
    initial_250ml: String(pool.initial_250ml),
    current_350ml: String(pool.current_350ml),
    initial_350ml: String(pool.initial_350ml),
    current_gallon: String(pool.current_gallon),
    initial_gallon: String(pool.initial_gallon),
  };
}

interface HoneyBottledInventoryPanelProps {
  emptyMessage?: string;
}

export function HoneyBottledInventoryPanel({
  emptyMessage = "No finished honey on hand yet. Stock in bottles from Production first.",
}: HoneyBottledInventoryPanelProps) {
  const { user } = useAuth();
  const canExecEdit = canExecutiveDirectEdit(user);
  const [pools, setPools] = useState<PoolVariant[]>([]);
  const [note, setNote] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [editRow, setEditRow] = useState<PackagingEditRow | null>(null);
  const [logsOpen, setLogsOpen] = useState(false);
  const [logsRefreshKey, setLogsRefreshKey] = useState(0);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<PackagingPoolsSnapshot>("honey.php", "packaging_variant_pools");
      setPools(data.variants ?? []);
      setNote(data.note ?? null);
    } catch {
      setPools([]);
      setNote(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);
  useLiveRefresh(refresh);

  const charts = pools.map(poolToChart);

  return (
    <section>
      <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div className="min-w-0">
          <h2 className="text-sm font-semibold">Finished honey on hand</h2>
          {canExecEdit ? (
            <p className="text-xs text-muted-foreground mt-0.5 max-w-xl">
              Edit adjusts Light / Amber / Dark pool counts — the same finished stock Pack and POS use. A reason
              is required and kept in Edit Logs.
            </p>
          ) : null}
        </div>
        {canExecEdit ? (
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="min-h-11 shrink-0 self-start sm:self-auto"
            onClick={() => setLogsOpen(true)}
          >
            <History size={15} className="mr-1.5" aria-hidden />
            Edit logs
          </Button>
        ) : null}
      </div>
      {loading ? (
        <div className="flex justify-center py-8 text-muted-foreground">
          <Loader2 className="animate-spin" size={20} />
        </div>
      ) : (
        <HoneyVariantPieGrid
          mode="inventory"
          variants={charts}
          emptyMessage={emptyMessage}
          headerNote="Live finished bottles by variant pool (warehouse & production gallons excluded)"
          variantActions={
            canExecEdit
              ? pools.map(pool => ({
                  variant: pool.variant,
                  node: (
                    <Button
                      type="button"
                      size="sm"
                      variant="outline"
                      className="min-h-9"
                      onClick={() => setEditRow(poolToEditRow(pool))}
                    >
                      Edit
                    </Button>
                  ),
                }))
              : undefined
          }
        />
      )}
      {note && <p className="text-xs text-muted-foreground mt-2">{note}</p>}

      {editRow ? (
        <ExecutiveRowEditModal
          stage="packaging"
          row={editRow}
          onClose={() => setEditRow(null)}
          onSaved={() => {
            setEditRow(null);
            setLogsRefreshKey(k => k + 1);
            void refresh();
          }}
        />
      ) : null}

      <HoneyFinishedEditLogsModal
        open={logsOpen}
        onClose={() => setLogsOpen(false)}
        refreshKey={logsRefreshKey}
      />
    </section>
  );
}
