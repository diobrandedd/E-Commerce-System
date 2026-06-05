import { useEffect, useState } from "react";
import type { PackagingStockSnapshot } from "@/app/lib/honeyPackagingStock";
import { onlineOrdersPackagingStock } from "@/app/lib/onlineOrdersApi";

export function useHoneyPackagingStock() {
  const [snapshot, setSnapshot] = useState<PackagingStockSnapshot | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    void (async () => {
      try {
        const data = await onlineOrdersPackagingStock();
        if (!cancelled) setSnapshot(data);
      } catch {
        if (!cancelled) setSnapshot(null);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  return { snapshot, loading };
}
