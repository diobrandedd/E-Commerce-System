import { Link } from "react-router";
import { PageLayout } from "@/app/components/PageLayout";
import { appHoverCard } from "@/app/components/layout/appFeedbackMotion";
import { useAuth } from "@/app/context/AuthContext";
import { cn } from "@/app/components/ui/utils";

export function CacaoBrcHubPage() {
  const { can } = useAuth();
  const showBrc = can("dept.brc.view");
  const showCacao = can("dept.cacao.view");

  return (
    <PageLayout title="Cacao / BRC" subtitle="Choose a finished product inventory">
      <div className="grid sm:grid-cols-2 gap-4 max-w-2xl">
        {showBrc && (
          <Link
            to="/inventory/products/black-rice-coffee"
            className={cn("block rounded-xl border border-border bg-card p-5", appHoverCard)}
          >
            <h3 className="text-sm font-semibold">Black Rice Coffee</h3>
            <p className="text-sm text-muted-foreground mt-2">Finished pouches</p>
          </Link>
        )}
        {showCacao && (
          <Link
            to="/inventory/products/cacao-tablets"
            className={cn("block rounded-xl border border-border bg-card p-5", appHoverCard)}
          >
            <h3 className="text-sm font-semibold">Cacao Tablets</h3>
            <p className="text-sm text-muted-foreground mt-2">Finished jars</p>
          </Link>
        )}
        {!showBrc && !showCacao && (
          <p className="text-sm text-muted-foreground">No BRC or cacao inventory is enabled for this account.</p>
        )}
      </div>
    </PageLayout>
  );
}
