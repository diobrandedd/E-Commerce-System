import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";
import type { Role } from "@/app/context/AuthContext";
import {
  PT_TEA_BAGS_PER_POUCH,
  deptStockInYieldHint,
} from "@/app/lib/deptProducts";

const ROLE_LABELS: Partial<Record<Role, string>> = {
  executive: "Executive",
  hr: "HR",
  prod_manager: "Production Manager",
  team_leader: "Team Leader",
};

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

interface ParagisProductionGuideModalProps {
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
  canMutate?: boolean;
  onStartTour: () => void;
}

export function ParagisProductionGuideModal({
  open,
  onClose,
  role,
  onStartTour,
}: ParagisProductionGuideModalProps) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;
  const yieldHint = deptStockInYieldHint("pt");

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="paragis-production-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Paragis production guide
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId="paragis-production-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="Two stages">
          <p>
            <strong className="text-foreground">Bag pool</strong> — leaf from stocked 1 kg supplier bags,
            tracked as remaining grams (progress bar on this page).
            {" "}
            <strong className="text-foreground">Finished pouches</strong> — retail packs ready to sell (
            {PT_TEA_BAGS_PER_POUCH} tea bags each). There is no tea-bag WIP step.
          </p>
        </GuideSection>

        <GuideSection title="Day-to-day flow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Stock in raw bags</strong> — receive sealed 1 kg bags.
              This fills the bag pool.
            </li>
            <li>
              <strong className="text-foreground">Request / print pouch barcodes</strong> — label finished
              pouches when ready.
            </li>
            <li>
              <strong className="text-foreground">Scan pouches to inventory</strong> — each scan adds
              finished stock and deducts leaf from the bag pool (default 48 g = 2 g × 24 tea bags).
            </li>
          </ol>
          <p className="rounded-lg border border-[#C8900A]/25 bg-[#C8900A]/5 px-3 py-2 text-[#8a6506]">
            Short pool still allows scanning — the bar shows overdrawn so you know to stock more bags.
            Packaging materials are not deducted.
          </p>
        </GuideSection>

        <GuideSection title="Settings">
          <p>
            Executives can edit <strong className="text-foreground">g per tea bag</strong> and{" "}
            <strong className="text-foreground">tea bags per pouch</strong> under Settings → Paragis pouch BOM.
            That changes how many grams each scan deducts.
          </p>
        </GuideSection>

        <GuideSection title="Production yield">
          <p className="rounded-lg border border-[#C8900A]/25 bg-[#C8900A]/5 px-3 py-2 text-[#8a6506]">{yieldHint}</p>
        </GuideSection>

        <GuideSection title="Waste">
          <ul className="list-disc pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Bag waste</strong> — sealed raw bags discarded before use
              (reduces the bag pool).
            </li>
          </ul>
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
