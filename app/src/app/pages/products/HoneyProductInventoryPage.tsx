import { Link } from "react-router";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyBottledInventoryPanel } from "@/app/components/honey/HoneyBottledInventoryPanel";
import { StockInsTrendChart } from "@/app/components/dashboard/StockInsTrendChart";

export function HoneyProductInventoryPage() {
  return (
    <PageLayout
      title="Honey"
      subtitle="Finished bottled products on hand — by variant and size"
      actions={
        <div className="flex flex-wrap gap-2">
          <Link
            to="/inventory/products/honey-comb"
            className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
          >
            Honey Comb jars →
          </Link>
          <Link
            to="/inventory/raw-materials/honey/packaging"
            className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
          >
            Stock-in report →
          </Link>
        </div>
      }
    >
      <div className="space-y-6">
        <div className="rounded-xl border border-border bg-card p-4 sm:p-5">
          <StockInsTrendChart mode="full" lockedFamily="honey" defaultDays={30} />
        </div>
        <HoneyBottledInventoryPanel />
      </div>
    </PageLayout>
  );
}
