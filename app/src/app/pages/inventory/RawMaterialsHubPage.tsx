import { useCallback, useEffect, useMemo, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { fetchRawSnapshot, type RawSnapshot } from "@/app/lib/materials";
import type { PermissionKey } from "@/app/lib/permissions";
import { cn } from "@/app/components/ui/utils";

const CARDS = [
  { slug: "honey", label: "Raw Honey", path: "/inventory/raw-materials/honey/warehouse", key: "honey" as const, perm: "honey.view" as PermissionKey },
  { slug: "paragis", label: "Dried Paragis", path: "/inventory/raw-materials/paragis", key: "paragis" as const, perm: "dept.paragis.view" as PermissionKey },
  { slug: "black-rice", label: "Black Rice", path: "/inventory/raw-materials/black-rice", key: "black_rice" as const, perm: "dept.brc.view" as PermissionKey },
  { slug: "cacao", label: "Cacao", path: "/inventory/raw-materials/cacao", key: "cacao" as const, perm: "dept.cacao.view" as PermissionKey },
  { slug: "honey-comb", label: "Honey Comb (raw)", path: "/inventory/raw-materials/honey-comb", key: "honey_comb" as const, perm: "dept.comb.view" as PermissionKey },
];

function summaryFor(card: (typeof CARDS)[number], snap: RawSnapshot | null): string {
  if (!snap) return "—";
  if (card.key === "honey") {
    return `${snap.honey.total_gallons.toLocaleString()} gal in warehouse`;
  }
  const raw = snap.raw_stocks[card.key];
  if (!raw) return "—";
  const qty = raw.entries_sum ?? raw.on_hand ?? 0;
  return `${qty} ${raw.unit ?? "bag"} logged`;
}

export function RawMaterialsHubPage() {
  const { can } = useAuth();
  const [snap, setSnap] = useState<RawSnapshot | null>(null);
  const [loading, setLoading] = useState(true);
  const visible = useMemo(() => CARDS.filter(card => can(card.perm)), [can]);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      setSnap(await fetchRawSnapshot());
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);

  return (
    <PageLayout title="Raw Materials" subtitle="Supplier inputs before production">
      {loading ? (
        <div className="py-12 text-center text-muted-foreground">
          <Loader2 className="inline animate-spin" size={18} />
        </div>
      ) : visible.length === 0 ? (
        <p className="text-sm text-muted-foreground">No raw material lines are enabled for this account.</p>
      ) : (
        <div className="grid sm:grid-cols-2 gap-4">
          {visible.map(card => (
            <Link
              key={card.slug}
              to={card.path}
              className={cn("block rounded-xl border border-border bg-card p-5", appHoverCard)}
              style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
            >
              <h3 className="text-sm font-semibold text-foreground">{card.label}</h3>
              <p className="mt-2 text-xs text-muted-foreground">{summaryFor(card, snap)}</p>
              <p className="mt-3 text-xs font-semibold text-primary">View details →</p>
            </Link>
          ))}
        </div>
      )}
    </PageLayout>
  );
}
