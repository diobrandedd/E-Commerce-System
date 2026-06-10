import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import { Loader2 } from "lucide-react";
import { PageLayout } from "@/app/components/PageLayout";
import { fetchRawSnapshot, type RawSnapshot } from "@/app/lib/materials";

const CARDS = [
  { slug: "honey", label: "Raw Honey", path: "/inventory/raw-materials/honey", key: "honey" as const },
  { slug: "paragis", label: "Dried Paragis", path: "/inventory/raw-materials/paragis", key: "paragis" as const },
  { slug: "black-rice", label: "Black Rice", path: "/inventory/raw-materials/black-rice", key: "black_rice" as const },
  { slug: "cacao", label: "Cacao Beans", path: "/inventory/raw-materials/cacao", key: "cacao" as const },
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
  const [snap, setSnap] = useState<RawSnapshot | null>(null);
  const [loading, setLoading] = useState(true);

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
      ) : (
        <div className="grid sm:grid-cols-2 gap-4">
          {CARDS.map(card => (
            <Link
              key={card.slug}
              to={card.path}
              className="block rounded-xl border border-border bg-card p-5 hover:border-[#C8900A]/40 transition-colors"
              style={{ boxShadow: "0 1px 4px rgba(0,0,0,0.05)" }}
            >
              <h3 className="text-sm font-semibold text-foreground">{card.label}</h3>
              <p className="mt-2 text-xs text-muted-foreground">{summaryFor(card, snap)}</p>
              <p className="mt-3 text-xs font-semibold text-[#C8900A]">View details →</p>
            </Link>
          ))}
        </div>
      )}
    </PageLayout>
  );
}
