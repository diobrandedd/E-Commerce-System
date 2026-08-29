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

interface DeliveriesCancelledGuideModalProps {
  open: boolean;
  onClose: () => void;
  onStartTour: () => void;
}

export function DeliveriesCancelledGuideModal({
  open,
  onClose,
  onStartTour,
}: DeliveriesCancelledGuideModalProps) {
  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="deliveries-cancelled-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Cancelled restock guide
          </span>
        }
        titleId="deliveries-cancelled-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Return barcode units to on-hand after courier cancel (packed/shipped) or a mistaken POS walk-in
            sale (sold). Each unit goes back to <strong className="text-foreground">packaging</strong>,{" "}
            <strong className="text-foreground">finished</strong>, or <strong className="text-foreground">RTS</strong>{" "}
            based on its original pool.
          </p>
        </GuideSection>

        <GuideSection title="Workflow">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>
              Open <strong className="text-foreground">Restock</strong> and scan each honey or dept sticker
              (packed, shipped, or POS sold).
            </li>
            <li>
              A permanent SKU barcode opens a confirmation step. Enter the quantity, then choose
              <strong className="text-foreground"> Packaging / Finished</strong> or <strong className="text-foreground">RTS</strong>.
            </li>
            <li>Confirm — units become in stock again; the matching inventory pool goes up.</li>
            <li>Use <strong className="text-foreground">History</strong> to audit returns by date or product.</li>
            <li>
              Sealed box still reusable? Use <strong className="text-foreground">Hold / Re-waybill</strong> on Add
              deliveries — that path does not restock.
            </li>
          </ol>
        </GuideSection>

        <GuideSection title="What this is not">
          <ul className="list-disc pl-4 space-y-1">
            <li>Not tracking-QR parcel cancel (retired)</li>
            <li>Not RTS case recording (that still uses tracking + optional barcodes on RTS Record)</li>
            <li>Does not accept stickers that are still in stock or void</li>
            <li>Static SKU returns are quantity-based and do not create individual unit sticker records.</li>
          </ul>
        </GuideSection>

        <GuideWalkthroughSection />
        <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
      </AppModalBody>
    </AppModal>
  );
}
