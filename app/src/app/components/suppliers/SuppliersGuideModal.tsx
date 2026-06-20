import { BookOpen } from "lucide-react";
import { AppModal, AppModalBody, AppModalHeader } from "@/app/components/AppModal";
import { GuideTourFooter, GuideWalkthroughSection } from "@/app/components/tour/GuideTourFooter";

function GuideSection({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="space-y-2">
      <h3 className="text-sm font-semibold text-foreground border-b border-border pb-1">{title}</h3>
      <div className="text-xs text-muted-foreground space-y-2 leading-relaxed">{children}</div>
    </section>
  );
}

export function SuppliersGuideModal({
  open,
  onClose,
  onStartTour,
}: {
  open: boolean;
  onClose: () => void;
  onStartTour: () => void;
}) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="suppliers-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Supplier Master guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              Executive
            </span>
          </span>
        }
        titleId="suppliers-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Register suppliers and set prices that flow into costing and raw inventory. Production staff pick suppliers
            from this list when stocking in — they cannot create new suppliers here.
          </p>
        </GuideSection>
        <GuideSection title="Your workflow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              <strong className="text-foreground">Add Supplier</strong> — category (honey, paragis, black rice, cacao,
              packaging, etc.), contact, payment terms.
            </li>
            <li>
              <strong className="text-foreground">Honey / dept pricing rows</strong> — expand honey, paragis, black rice,
              or cacao suppliers to set ₱/gallon or ₱/bag. Saves sync to costing stock and raw yield hints.
            </li>
            <li>
              <strong className="text-foreground">Profile</strong> — click non-pricing suppliers for full details and
              documents.
            </li>
            <li>
              Resolve the amber banner when warehouse deliveries still need a honey supplier price.
            </li>
          </ol>
        </GuideSection>
        <GuideSection title="Categories">
          <p>
            Honey uses <strong className="text-foreground">₱/US gallon</strong> (costing converts to ₱/liter). Dept raw
            suppliers use <strong className="text-foreground">₱/1 kg bag</strong>. Packaging suppliers are catalog-only
            on this page — stock-in happens on Packaging Materials.
          </p>
        </GuideSection>
        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
