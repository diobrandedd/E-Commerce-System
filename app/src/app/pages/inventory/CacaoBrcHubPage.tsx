import { Link } from "react-router";
import { PageLayout } from "@/app/components/PageLayout";

export function CacaoBrcHubPage() {
  return (
    <PageLayout title="Cacao / BRC" subtitle="Choose a finished product inventory">
      <div className="grid sm:grid-cols-2 gap-4 max-w-2xl">
        <Link
          to="/inventory/products/black-rice-coffee"
          className="block rounded-xl border border-border bg-card p-5 hover:border-[#C8900A]/40"
        >
          <h3 className="text-sm font-semibold">Black Rice Coffee</h3>
          <p className="mt-2 text-xs text-muted-foreground">Finished pouches</p>
        </Link>
        <Link
          to="/inventory/products/cacao-tablets"
          className="block rounded-xl border border-border bg-card p-5 hover:border-[#C8900A]/40"
        >
          <h3 className="text-sm font-semibold">Cacao Tablets</h3>
          <p className="mt-2 text-xs text-muted-foreground">Finished jars</p>
        </Link>
      </div>
    </PageLayout>
  );
}
