import { useCallback, useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { HoneyVariantPieGrid, type VariantChart } from "@/app/components/honey/HoneyVariantPieGrid";
import { useLiveRefresh } from "@/app/hooks/useLiveRefresh";
import { apiGet } from "@/app/lib/api";

interface PackagingInventorySnapshot {
  total_bottles: number;
  batch_count: number;
  variants: VariantChart[];
  note?: string;
}

interface HoneyBottledInventoryPanelProps {
  emptyMessage?: string;
}

export function HoneyBottledInventoryPanel({
  emptyMessage = "No bottled batch stock yet. Finish packaging from production to create batch numbers.",
}: HoneyBottledInventoryPanelProps) {
  const [inventory, setInventory] = useState<PackagingInventorySnapshot | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const data = await apiGet<PackagingInventorySnapshot>("honey.php", "packaging_total_inventory");
      setInventory(data);
    } catch {
      setInventory(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);
  useLiveRefresh(refresh);

  return (
    <section>
      <h2 className="text-sm font-semibold mb-3">Total inventory (batch bottled)</h2>
      {loading ? (
        <div className="flex justify-center py-8 text-muted-foreground">
          <Loader2 className="animate-spin" size={20} />
        </div>
      ) : (
        <HoneyVariantPieGrid
          mode="inventory"
          variants={inventory?.variants ?? []}
          emptyMessage={emptyMessage}
        />
      )}
      {inventory?.note && (
        <p className="text-xs text-muted-foreground mt-2">{inventory.note}</p>
      )}
    </section>
  );
}
