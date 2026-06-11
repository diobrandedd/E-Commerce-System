import { Link } from "react-router";
import { PageLayout } from "@/app/components/PageLayout";
import { HoneyBottledInventoryPanel } from "@/app/components/honey/HoneyBottledInventoryPanel";

export function HoneyProductInventoryPage() {
  return (
    <PageLayout
      title="Honey"
      subtitle="Finished bottled products — batch inventory by variant and size"
      actions={
        <Link
          to="/inventory/raw-materials/honey/packaging"
          className="text-xs font-semibold px-3 py-1.5 rounded-lg border border-border hover:bg-secondary"
        >
          Batch rows & sales →
        </Link>
      }
    >
      <HoneyBottledInventoryPanel />
    </PageLayout>
  );
}
