import type { ElementType } from "react";
import { FileSpreadsheet, Package, Stamp, Truck } from "lucide-react";
import { FulfillmentPickerCard, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import type { OnlineOrdersPrepTool } from "@/app/lib/onlineOrdersTypes";

const TOOLS: {
  id: OnlineOrdersPrepTool;
  title: string;
  description: string;
  icon: ElementType;
}[] = [
  {
    id: "vip",
    title: "VIP Excel arranger",
    description: "Courier Excel → resolve remarks → download arranged iPYX sheet",
    icon: FileSpreadsheet,
  },
  {
    id: "spx",
    title: "SPX name merge",
    description: "Packing list or PDF extract → review → download iPYX sheet",
    icon: Truck,
  },
  {
    id: "excel",
    title: "Validate existing Excel",
    description: "Check a partner iPYX workbook you already have",
    icon: Package,
  },
  {
    id: "stamper",
    title: "PDF + Excel stamper",
    description: "Prepared Excel + waybill PDFs → stamp labels into this batch",
    icon: Stamp,
  },
];

export function ToolsPicker({
  lineCount,
  onSelect,
}: {
  lineCount: number;
  onSelect: (tool: OnlineOrdersPrepTool) => void;
}) {
  return (
    <FulfillmentSectionEnter className="space-y-4 max-w-4xl">
      <p className="text-sm text-muted-foreground">
        {lineCount > 0
          ? `${lineCount} parcel line(s) in this batch. Prepare more Excel sheets, stamp waybills, or finish the batch.`
          : "Pick a prep tool to build Excel, or use the stamper when your sheet is ready."}
      </p>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {TOOLS.map((t, i) => {
          const Icon = t.icon;
          return (
            <FulfillmentPickerCard key={t.id} index={i} onClick={() => onSelect(t.id)}>
              <Icon size={28} className="text-primary mb-3" />
              <p className="font-semibold text-foreground">{t.title}</p>
              <p className="text-xs text-muted-foreground mt-2">{t.description}</p>
            </FulfillmentPickerCard>
          );
        })}
      </div>
    </FulfillmentSectionEnter>
  );
}
