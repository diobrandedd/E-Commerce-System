import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalFooter, AppModalHeader } from "@/app/components/AppModal";
import type { Role } from "@/app/context/AuthContext";
import {
  PT_TEA_BAGS_PER_POUCH,
  deptStockInYieldHint,
  ptTeaBagsPerBag,
} from "@/app/lib/deptProducts";

const ROLE_LABELS: Partial<Record<Role, string>> = {
  executive: "Executive",
  hr: "HR",
  prod_manager: "Production Manager",
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
  onOpenExactTeaBagCount?: () => void;
}

export function ParagisProductionGuideModal({
  open,
  onClose,
  role,
  canMutate = false,
  onOpenExactTeaBagCount,
}: ParagisProductionGuideModalProps) {
  const roleLabel = role ? ROLE_LABELS[role] : undefined;
  const yieldHint = deptStockInYieldHint("pt");
  const teaPerBag = ptTeaBagsPerBag();

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
        <GuideSection title="Three stages">
          <p>
            <strong className="text-foreground">Raw bags</strong> — sealed 1 kg supplier leaf in the warehouse.
            {" "}
            <strong className="text-foreground">Tea bags (WIP)</strong> — loose filled tea bags waiting to be pouched.
            {" "}
            <strong className="text-foreground">Finished pouches</strong> — retail packs ready to sell ({PT_TEA_BAGS_PER_POUCH} tea bags each).
          </p>
        </GuideSection>

        <GuideSection title="Production workflow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Stock in</strong> — receive sealed 1 kg supplier bags from a supplier. This only increases <strong className="text-foreground">Raw bags</strong>.
            </li>
            <li>
              <strong className="text-foreground">Use raw bags</strong> — enter how many 1 kg bags you opened for production. Raw leaf deducts; <strong className="text-foreground">Tea bags (WIP)</strong> goes up by ~{teaPerBag.toLocaleString()} per bag (2 g each). You do <strong className="text-foreground">not</strong> need to count individual tea bags.
            </li>
            <li>
              <strong className="text-foreground">Pouch</strong> — enter how many retail pouches you packed. WIP tea bags and pouch packaging deduct; <strong className="text-foreground">Finished pouches</strong> goes up.
            </li>
          </ol>
          <p>
            Materials come from Product Costing formula lines (stock-linked only). After stock-in, use <strong className="text-foreground">Use raw bags</strong> — not Stock in again.
          </p>
        </GuideSection>

        <GuideSection title="Production yield">
          <p className="rounded-lg border border-[#C8900A]/25 bg-[#C8900A]/5 px-3 py-2 text-[#8a6506]">{yieldHint}</p>
        </GuideSection>

        <GuideSection title="Waste & corrections">
          <ul className="list-disc pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">WIP waste</strong> — loose tea bags lost or spoiled before pouching (reduces WIP only).
            </li>
            <li>
              <strong className="text-foreground">Bag waste</strong> — sealed raw bags discarded before opening (reduces Raw bags).
            </li>
          </ul>
        </GuideSection>

        {canMutate && (
          <GuideSection title="Advanced">
            <p>
              If you have an exact count of individual tea bags (small batch only), use <strong className="text-foreground">exact tea bag count</strong> instead of Use raw bags.
            </p>
          </GuideSection>
        )}
      </AppModalBody>
      {canMutate && onOpenExactTeaBagCount && (
        <AppModalFooter className="justify-between gap-2 flex-wrap">
          <button
            type="button"
            onClick={() => {
              onClose();
              onOpenExactTeaBagCount();
            }}
            className="text-xs font-medium text-[#8a6506] hover:underline"
          >
            Open exact tea bag count…
          </button>
          <button type="button" onClick={onClose} className="px-4 py-2 text-sm rounded-lg border border-border">
            Close
          </button>
        </AppModalFooter>
      )}
    </AppModal>
  );
}
