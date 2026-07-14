import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";
import type { Role } from "@/app/context/AuthContext";
import {
  BRC_GRAMS_PER_POUCH,
  JAR_CACAO_GRAMS_PER_JAR,
  JAR_CACAO_TABLETS_PER_JAR,
  HONEY_COMB_GRAMS_PER_JAR,
  brcPouchesPerBag,
  deptStockInYieldHint,
  honeyCombBagsPerJar,
  honeyCombJarsPerBag,
  jarCacaoJarsPerBag,
  type DeptProductKey,
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

const GUIDE_META: Record<
  "brc" | "jar_cacao" | "honey_comb",
  { title: string; labelledBy: string; rawLabel: string; finishedLabel: string; produceVerb: string; sellUnit: string }
> = {
  brc: {
    title: "Black Rice Coffee production guide",
    labelledBy: "brc-production-guide-title",
    rawLabel: "Black Rice — raw",
    finishedLabel: "finished pouches",
    produceVerb: "Produce",
    sellUnit: "pouch",
  },
  jar_cacao: {
    title: "Jar Cacao production guide",
    labelledBy: "jar-cacao-production-guide-title",
    rawLabel: "Jar Cacao — raw",
    finishedLabel: "finished jars",
    produceVerb: "Produce",
    sellUnit: "jar",
  },
  honey_comb: {
    title: "Honey Comb production guide",
    labelledBy: "honey-comb-production-guide-title",
    rawLabel: "Honey comb — raw",
    finishedLabel: "finished jars",
    produceVerb: "Produce",
    sellUnit: "jar",
  },
};

interface DeptRawProduceGuideModalProps {
  productKey: Extract<DeptProductKey, "brc" | "jar_cacao" | "honey_comb">;
  open: boolean;
  onClose: () => void;
  role: Role | undefined;
  onStartTour: () => void;
}

export function DeptRawProduceGuideModal({
  productKey,
  open,
  onClose,
  role,
  onStartTour,
}: DeptRawProduceGuideModalProps) {
  const meta = GUIDE_META[productKey];
  const roleLabel = role ? ROLE_LABELS[role] : undefined;
  const yieldHint = deptStockInYieldHint(productKey);

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy={meta.labelledBy}
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            {meta.title}
            {roleLabel && (
              <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
                {roleLabel}
              </span>
            )}
          </span>
        }
        titleId={meta.labelledBy}
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="Two stages">
          {productKey === "brc" ? (
            <p>
              <strong className="text-foreground">Raw bags</strong> — sealed 1 kg supplier bags of black rice coffee.
              {" "}
              <strong className="text-foreground">Finished pouches</strong> — retail packs ready to sell ({BRC_GRAMS_PER_POUCH} g each).
              There is no WIP step — production goes straight from raw to finished.
            </p>
          ) : productKey === "jar_cacao" ? (
            <p>
              <strong className="text-foreground">Raw bags</strong> — sealed 1 kg supplier bags of cacao.
              {" "}
              <strong className="text-foreground">Finished jars</strong> — retail jars ready to sell ({JAR_CACAO_GRAMS_PER_JAR} g cacao, {JAR_CACAO_TABLETS_PER_JAR} tablets inside each).
              There is no WIP step — production goes straight from raw to finished.
            </p>
          ) : (
            <p>
              <strong className="text-foreground">Raw bags</strong> — sealed 1 kg supplier bags of honey comb.
              {" "}
              <strong className="text-foreground">Finished jars</strong> — retail jars with cap + sticker ({HONEY_COMB_GRAMS_PER_JAR} g comb per jar).
              Production deducts jar packaging and sticker from Product Costing formula lines.
            </p>
          )}
        </GuideSection>

        <GuideSection title="Production workflow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Stock in</strong> — receive sealed 1 kg supplier bags. This only increases <strong className="text-foreground">Raw bags</strong>.
            </li>
            <li>
              <strong className="text-foreground">{meta.produceVerb}</strong> — enter how many {meta.sellUnit}s you made. Raw {meta.rawLabel.toLowerCase()} and packaging from the Product Costing formula deduct; <strong className="text-foreground">Finished {meta.sellUnit}s</strong> goes up.
            </li>
          </ol>
          <p>
            Materials come from Product Costing formula lines (stock-linked only). After stock-in, use <strong className="text-foreground">{meta.produceVerb}</strong> — not Stock in again.
          </p>
        </GuideSection>

        <GuideSection title="Production yield">
          <p className="rounded-lg border border-[#C8900A]/25 bg-[#C8900A]/5 px-3 py-2 text-[#8a6506]">{yieldHint}</p>
          {productKey === "brc" && (
            <p>
              Example: opening <strong className="text-foreground">1 bag</strong> worth of production uses{" "}
              <strong className="text-foreground">0.1 bag</strong> of raw per pouch — about{" "}
              <strong className="text-foreground">{brcPouchesPerBag().toLocaleString()} pouches</strong> per full bag at yield.
            </p>
          )}
          {productKey === "jar_cacao" && (
            <p>
              Example: opening <strong className="text-foreground">1 bag</strong> worth of production uses{" "}
              <strong className="text-foreground">0.22 bag</strong> of raw per jar — about{" "}
              <strong className="text-foreground">{jarCacaoJarsPerBag().toFixed(2)} jars</strong> per full bag at yield.
            </p>
          )}
          {productKey === "honey_comb" && (
            <p>
              Example: opening <strong className="text-foreground">1 bag</strong> worth of production uses{" "}
              <strong className="text-foreground">{honeyCombBagsPerJar().toFixed(2)} bag</strong> of raw per jar — about{" "}
              <strong className="text-foreground">{honeyCombJarsPerBag().toFixed(2)} jars</strong> per full bag at yield.
            </p>
          )}
        </GuideSection>

        <GuideSection title="Waste & corrections">
          <ul className="list-disc pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Bag waste</strong> — sealed raw bags discarded before opening (reduces Raw bags).
            </li>
          </ul>
          <p>
            The <strong className="text-foreground">Produce</strong> modal shows a preview of raw and packaging lines that will deduct before you confirm.
          </p>
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
