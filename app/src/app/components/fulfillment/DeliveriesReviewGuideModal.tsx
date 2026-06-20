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

interface DeliveriesReviewGuideModalProps {
  open: boolean;
  onClose: () => void;
  isExecutive: boolean;
  onStartTour: () => void;
}

export function DeliveriesReviewGuideModal({
  open,
  onClose,
  isExecutive,
  onStartTour,
}: DeliveriesReviewGuideModalProps) {
  const roleLabel = isExecutive ? "Executive" : "Production Manager";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="deliveries-review-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Deliveries review guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              {roleLabel}
            </span>
          </span>
        }
        titleId="deliveries-review-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            After parcels are scanned on <strong className="text-foreground">Add deliveries</strong>, rows appear here
            with packing photos and linked online-order details. Use it to verify shipments and fix data when something
            is wrong.
          </p>
        </GuideSection>

        <GuideSection title={isExecutive ? "Your workflow (Executive)" : "Your workflow (Production Manager)"}>
          {isExecutive ? (
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>Search or filter by date to find a tracking number.</li>
              <li>Open the photo to confirm the parcel matches the order.</li>
              <li>
                <strong className="text-foreground">Edit</strong> to change tracking, replace the photo, or delete a bad
                row — changes apply immediately.
              </li>
              <li>For courier-cancelled parcels, use the <strong className="text-foreground">Cancelled</strong> page — sealed box stays packed for re-waybill on Add deliveries.</li>
            </ol>
          ) : (
            <ol className="list-decimal pl-4 space-y-1.5">
              <li>Search or filter to find a delivery record.</li>
              <li>View the packing photo and parcel status.</li>
              <li>
                <strong className="text-foreground">Edit</strong> submits a correction request — an executive approves it
                under Inventory → Requests → Edit requests.
              </li>
              <li>Scan and ship new parcels on Add deliveries; mark courier cancels on the Cancelled page, then re-waybill.</li>
            </ol>
          )}
        </GuideSection>

        <GuideSection title="Status meanings">
          <ul className="list-disc pl-4 space-y-1">
            <li>
              <strong className="text-foreground">packing</strong> — scanned, photo taken, not yet marked shipped
            </li>
            <li>
              <strong className="text-foreground">shipped</strong> — left the warehouse; inventory already deducted
            </li>
            <li>
              <strong className="text-foreground">cancelled</strong> — courier void; sealed box re-waybilled on Add deliveries (inventory stays deducted)
            </li>
          </ul>
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
