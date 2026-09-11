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

interface DeliveriesAddGuideModalProps {
  open: boolean;
  onClose: () => void;
  isExecutive: boolean;
  onStartTour: () => void;
}

export function DeliveriesAddGuideModal({
  open,
  onClose,
  isExecutive,
  onStartTour,
}: DeliveriesAddGuideModalProps) {
  const roleLabel = isExecutive ? "Executive" : "Production Manager";

  return (
    <AppModal
      open={open}
      onClose={onClose}
      panelClassName="max-w-lg w-full max-h-[90vh] flex flex-col"
      labelledBy="deliveries-add-guide-title"
    >
      <AppModalHeader
        title={
          <span className="flex items-center gap-2 flex-wrap">
            <BookOpen size={18} className="text-[#C8900A] flex-shrink-0" />
            Add deliveries guide
            <span className="text-[10px] font-medium uppercase tracking-wide px-2 py-0.5 rounded-full bg-secondary text-muted-foreground">
              {roleLabel}
            </span>
          </span>
        }
        titleId="deliveries-add-guide-title"
        onClose={onClose}
      />
      <AppModalBody className="flex flex-col min-h-0 overflow-y-auto overscroll-contain gap-5 py-4">
        <GuideSection title="What this page is for">
          <p>
            Save a <strong className="text-foreground">before-shipment photo</strong> against the tracking number so
            RTS can show it later. No CSR parcel list and no inventory or packaging deduct. On hold and Re-waybill
            still handle sealed-box exceptions.
          </p>
        </GuideSection>

        <GuideSection title="Photo tab (normal flow)">
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Set the <strong className="text-foreground">business date</strong> for today&apos;s batch.</li>
            <li>
              Tap <strong className="text-foreground">Scan tracking</strong> and scan the waybill QR (or type
              tracking). CSR finalize is not required.
            </li>
            <li>Photograph the wrapped parcel.</li>
            <li>
              Tap <strong className="text-foreground">Done</strong> or <strong className="text-foreground">Scan next</strong>{" "}
              — photo only; stock does not change.
            </li>
            <li>Rows appear on Deliveries review; RTS looks up the image by tracking.</li>
          </ol>
        </GuideSection>

        <GuideSection title="On hold tab">
          <p>
            Wrong label on a <strong className="text-foreground">sealed</strong> box — scan to mark{" "}
            <strong className="text-foreground">on hold</strong>. CSR prints a replacement waybill in Online Orders,
            then use Re-waybill.
          </p>
        </GuideSection>

        <GuideSection title="Re-waybill tab">
          <p>One wizard for sealed boxes that need a new label:</p>
          <ol className="list-decimal pl-4 space-y-1.5">
            <li>Scan the <strong className="text-foreground">old</strong> tracking (on hold or courier cancelled).</li>
            <li>Scan the <strong className="text-foreground">new</strong> waybill from CSR.</li>
            <li>Take a fresh packing photo on the new number.</li>
          </ol>
        </GuideSection>

        <GuideSection title="Courier cancelled vs on hold">
          <ul className="list-disc pl-4 space-y-1">
            <li>
              <strong className="text-foreground">On hold</strong> — wrong label before or without courier cancel
            </li>
            <li>
              <strong className="text-foreground">Cancelled</strong> — courier voided the order; scan on sidebar →
              Deliveries → Cancelled, then re-waybill here
            </li>
            <li>Both keep the sealed box packed — not the same as an opened RTS return</li>
          </ul>
        </GuideSection>

        <GuideWalkthroughSection />
      </AppModalBody>
      <GuideTourFooter onStartTour={onStartTour} onClose={onClose} />
    </AppModal>
  );
}
