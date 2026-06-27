import type { ElementType } from "react";
import { ChevronRight, FileSpreadsheet, GitMerge, Package, Stamp, Truck } from "lucide-react";
import { FulfillmentPickerCard, FulfillmentSectionEnter } from "@/app/components/csr/onlineFulfillmentMotion";
import type { OnlineOrdersPrepTool } from "@/app/lib/onlineOrdersTypes";
import { cn } from "@/app/components/ui/utils";

const PREP_TOOLS: {
  id: OnlineOrdersPrepTool;
  title: string;
  description: string;
  icon: ElementType;
}[] = [
  {
    id: "vip",
    title: "VIP Excel arranger",
    description: "VIP order list → resolve remarks → save arranged rows",
    icon: FileSpreadsheet,
  },
  {
    id: "vip_merge",
    title: "VIP Excel merger",
    description: "Courier tracking export → match Parcel Name → download iPYX",
    icon: GitMerge,
  },
  {
    id: "spx",
    title: "SPX name merge",
    description: "Shopee PDFs or packing list → review lines → download iPYX",
    icon: Truck,
  },
  {
    id: "excel",
    title: "Validate Excel",
    description: "Partner iPYX workbook already built — check columns & rows",
    icon: Package,
  },
];

const STAMP_TOOL = {
  id: "stamper" as const,
  title: "PDF + Excel stamper",
  description: "Prepared iPYX + waybill PDFs → stamp labels into this batch",
  icon: Stamp,
};

function ToolIcon({ icon: Icon, stamp }: { icon: ElementType; stamp?: boolean }) {
  return (
    <span
      className={cn(
        "inline-flex h-11 w-11 items-center justify-center rounded-xl mb-3",
        stamp ? "bg-primary/15 text-primary" : "bg-[#C8900A]/12 text-[#8a6507]",
      )}
    >
      <Icon size={22} strokeWidth={2} />
    </span>
  );
}

export function ToolsPicker({
  lineCount,
  onSelect,
}: {
  lineCount: number;
  onSelect: (tool: OnlineOrdersPrepTool) => void;
}) {
  const StampIcon = STAMP_TOOL.icon;

  return (
    <FulfillmentSectionEnter className="mx-auto w-full max-w-3xl space-y-8">
      <div className="rounded-xl border border-border/70 bg-secondary/25 px-4 py-3 text-center">
        <p className="text-sm text-muted-foreground leading-relaxed">
          {lineCount > 0 ? (
            <>
              <span className="font-semibold text-foreground">{lineCount} line{lineCount === 1 ? "" : "s"}</span> in this batch.
              Prep tools download Excel only — return here, then stamp.
            </>
          ) : (
            <>Prep tools build or validate Excel. When ready, open the stamper — it does not run automatically after prep.</>
          )}
        </p>
      </div>

      <div data-tour="online-orders-tools" className="space-y-8">
        <section className="space-y-3">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground text-center">
            Step 1 — Prep Excel (download only)
          </p>
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {PREP_TOOLS.map((t, i) => {
              const Icon = t.icon;
              return (
                <FulfillmentPickerCard
                  key={t.id}
                  index={i}
                  onClick={() => onSelect(t.id)}
                  data-tour={`online-orders-tool-${t.id}`}
                  className="flex h-full flex-col p-4 sm:p-5"
                >
                  <ToolIcon icon={Icon} />
                  <p className="font-semibold text-foreground text-sm leading-snug">{t.title}</p>
                  <p className="mt-2 flex-1 text-xs text-muted-foreground leading-relaxed">{t.description}</p>
                  <span className="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-[#C8900A]">
                    Open <ChevronRight size={14} />
                  </span>
                </FulfillmentPickerCard>
              );
            })}
          </div>
        </section>

        <section className="space-y-3">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground text-center">
            Step 2 — Stamp into batch
          </p>
          <FulfillmentPickerCard
            index={3}
            onClick={() => onSelect("stamper")}
            data-tour="online-orders-tool-stamper"
            className="flex flex-col p-5 sm:flex-row sm:items-center sm:gap-5 sm:p-6 border-[#C8900A]/30 bg-gradient-to-br from-card to-[#C8900A]/5"
          >
            <ToolIcon icon={StampIcon} stamp />
            <div className="flex-1 min-w-0 text-left">
              <p className="font-semibold text-foreground">{STAMP_TOOL.title}</p>
              <p className="mt-1.5 text-sm text-muted-foreground leading-relaxed">{STAMP_TOOL.description}</p>
            </div>
            <span className="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary sm:mt-0 shrink-0">
              Open stamper <ChevronRight size={16} />
            </span>
          </FulfillmentPickerCard>
        </section>
      </div>
    </FulfillmentSectionEnter>
  );
}
