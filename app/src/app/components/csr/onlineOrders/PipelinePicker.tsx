import type { ElementType } from "react";
import { FileSpreadsheet, Package, Truck } from "lucide-react";
import type { OnlineOrdersPipeline } from "@/app/lib/onlineOrdersTypes";
import { cn } from "@/app/components/ui/utils";

const PIPELINES: {
  id: OnlineOrdersPipeline;
  title: string;
  description: string;
  icon: ElementType;
}[] = [
  {
    id: "partner",
    title: "Partner sellers",
    description: "Worker PDF + iPYX Excel → stamp waybills",
    icon: Package,
  },
  {
    id: "vip",
    title: "VIP orders",
    description: "Courier Excel → resolve remarks → stamp waybills",
    icon: FileSpreadsheet,
  },
  {
    id: "shopee_spx",
    title: "Shopee SPX (ours)",
    description: "PDF only → set items → stamp waybills",
    icon: Truck,
  },
];

export function PipelinePicker({
  lineCount,
  onSelect,
}: {
  lineCount: number;
  onSelect: (pipeline: OnlineOrdersPipeline) => void;
}) {
  return (
    <div className="space-y-4 max-w-4xl">
      <p className="text-sm text-muted-foreground">
        {lineCount > 0
          ? `${lineCount} parcel line(s) saved in this batch. Pick another pipeline or finish the batch.`
          : "Choose which type of orders to process next."}
      </p>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {PIPELINES.map(p => {
          const Icon = p.icon;
          return (
            <button
              key={p.id}
              type="button"
              onClick={() => onSelect(p.id)}
              className={cn(
                "text-left bg-card rounded-xl border border-border p-5 hover:border-primary hover:shadow-md transition-all",
              )}
            >
              <Icon size={28} className="text-primary mb-3" />
              <p className="font-semibold text-foreground">{p.title}</p>
              <p className="text-xs text-muted-foreground mt-2">{p.description}</p>
            </button>
          );
        })}
      </div>
    </div>
  );
}
